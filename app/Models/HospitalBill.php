<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class HospitalBill extends Model
{
    use HasFactory;

    protected $table = 'hospital_bills';

    protected $fillable = [
        'bill_number',
        'billing_type',
        'patient_id',
        'doctor_id',
        'hospital_service_id',
        'patient_token_id',
        'bill_date',
        'total_amount',
        'discount_amount',
        'net_amount',
        'paid_amount',
        'due_amount',
        'doctor_share_percentage',
        'hospital_share_percentage',
        'doctor_share',
        'hospital_share',
        'payment_method',
        'payment_status',
        'free_reason',
        'notes',
        'previous_due',
        'created_by',
    ];

    protected $casts = [
        'bill_date'                 => 'date',
        'total_amount'              => 'decimal:2',
        'discount_amount'           => 'decimal:2',
        'net_amount'                => 'decimal:2',
        'paid_amount'               => 'decimal:2',
        'due_amount'                => 'decimal:2',
        'previous_due'              => 'decimal:2',
        'doctor_share_percentage'   => 'decimal:2',
        'hospital_share_percentage' => 'decimal:2',
        'doctor_share'              => 'decimal:2',
        'hospital_share'            => 'decimal:2',
    ];

    /**
     * Boot logic for auto-generating bill numbers.
     */
    protected static function booted(): void
    {
        static::creating(function (HospitalBill $bill) {
            if (empty($bill->bill_number)) {
                $bill->bill_number = self::generateBillNumber();
            }
            if (empty($bill->bill_date)) {
                $bill->bill_date = now()->toDateString();
            }
        });
    }

    /**
     * Generate unique sequential bill number (e.g. HB-20260930-0001).
     */
    public static function generateBillNumber(): string
    {
        $prefix = 'HB-' . date('Ymd') . '-';
        $latest = self::where('bill_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->bill_number, $matches)) {
            $seq = intval($matches[1]) + 1;
        }

        do {
            $billNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $exists = self::where('bill_number', $billNo)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $billNo;
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) ($this->due_amount ?? 0.00);
    }

    /**
     * Record a payment installment against this bill with cash-basis revenue recognition.
     *
     * Accounting logic:
     * - Recognizes doctor share and hospital share ONLY for the amount actually collected.
     * - Adds the incremental doctor share to the doctor's payable ledger.
     * - Updates bill balances and status.
     *
     * @param float $amount
     * @param string $paymentMethod
     * @param string|null $referenceNote
     * @param int|null $userId
     * @return HospitalBillPayment
     * @throws \Exception
     */
    public function recordPayment(
        float $amount,
        string $paymentMethod = 'cash',
        ?string $referenceNote = null,
        ?int $userId = null,
        string $allocationType = 'current_bill'
    ): HospitalBillPayment {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($amount, $paymentMethod, $referenceNote, $userId, $allocationType) {
            $bill = self::where('id', $this->id)->lockForUpdate()->first();
            if (!$bill) {
                throw new \RuntimeException('Bill not found.');
            }

            if ($amount > (float)$bill->due_amount) {
                throw new \InvalidArgumentException("Payment amount (PKR {$amount}) cannot exceed outstanding remaining balance (PKR {$bill->due_amount}).");
            }

            // Incremental share calculation for this payment
            $docSharePct = (float)$bill->doctor_share_percentage;
            $hospSharePct = (float)$bill->hospital_share_percentage;

            $incDocShare = round($amount * ($docSharePct / 100), 2);
            $incHospShare = round($amount - $incDocShare, 2);

            // Create payment transaction
            $paymentCount = $bill->payments()->count() + 1;
            $paymentNumber = 'RCPT-' . $bill->bill_number . '-' . $paymentCount;

            $payment = HospitalBillPayment::create([
                'hospital_bill_id' => $bill->id,
                'patient_id'       => $bill->patient_id,
                'payment_number'   => $paymentNumber,
                'payment_date'     => now()->toDateString(),
                'amount'           => $amount,
                'doctor_share'     => $incDocShare,
                'hospital_share'   => $incHospShare,
                'payment_method'   => $paymentMethod,
                'reference_note'   => $referenceNote,
                'received_by'      => $userId ?? auth()->id(),
            ]);

            // Create explicit payment allocation record (guarded against duplicates)
            HospitalBillPaymentAllocation::firstOrCreate(
                [
                    'hospital_bill_payment_id' => $payment->id,
                    'hospital_bill_id'         => $bill->id,
                ],
                [
                    'allocated_amount'         => $amount,
                    'allocation_type'          => $allocationType,
                ]
            );

            // Update bill totals
            $newPaid = round((float)$bill->paid_amount + $amount, 2);
            $newDue = max(0.00, round((float)$bill->net_amount - $newPaid, 2));
            $newStatus = ($newDue <= 0.00) ? 'paid' : 'partial';

            $newDoctorShare = round((float)$bill->doctor_share + $incDocShare, 2);
            $newHospitalShare = round((float)$bill->hospital_share + $incHospShare, 2);

            $bill->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'doctor_share'   => $newDoctorShare,
                'hospital_share' => $newHospitalShare,
                'payment_status' => $newStatus,
            ]);

            // Post collection entry to Hospital Account Ledger (idempotent)
            $existingLedger = HospitalAccountLedger::where('hospital_bill_payment_id', $payment->id)->first();
            if (!$existingLedger) {
                $patientName = $bill->patient ? $bill->patient->name : 'Patient';
                HospitalAccountLedger::create([
                    'entry_date'               => now()->toDateString(),
                    'patient_id'               => $bill->patient_id,
                    'hospital_bill_id'         => $bill->id,
                    'hospital_bill_payment_id' => $payment->id,
                    'patient_token_id'         => $bill->patient_token_id,
                    'transaction_type'         => 'collection',
                    'description'              => "Payment received for Bill #{$bill->bill_number} (Patient: {$patientName})",
                    'gross_amount'             => (float)$bill->total_amount,
                    'discount_amount'          => (float)$bill->discount_amount,
                    'amount_received'          => $amount,
                    'outstanding_balance'      => $newDue,
                    'payment_method'           => $paymentMethod,
                    'status'                   => 'posted',
                    'created_by'               => $userId ?? auth()->id(),
                ]);
            }

            // If linked to an OPD token, keep token balance_due in sync
            if ($bill->patient_token_id) {
                PatientToken::where('id', $bill->patient_token_id)->update([
                    'balance_due' => $newDue,
                ]);
            }

            // Post credit to Doctor Ledger ONLY if doctor is assigned and earned a share (idempotent)
            if ($bill->doctor_id && $incDocShare > 0) {
                $existingDocLedger = DoctorLedger::where('hospital_bill_payment_id', $payment->id)->first();
                if (!$existingDocLedger) {
                    $doctor = Doctor::find($bill->doctor_id);
                    if ($doctor) {
                        $currPayable = (float)$doctor->current_payable;
                        $balanceAfter = round($currPayable + $incDocShare, 2);

                        DoctorLedger::create([
                            'doctor_id'                => $bill->doctor_id,
                            'hospital_bill_id'         => $bill->id,
                            'hospital_bill_payment_id' => $payment->id,
                            'entry_date'               => now()->toDateString(),
                            'transaction_type'         => 'credit',
                            'amount'                   => $incDocShare,
                            'balance_after'            => $balanceAfter,
                            'description'              => "Doctor share earned from Bill #{$bill->bill_number} (Patient: " . ($bill->patient ? $bill->patient->name : 'Patient') . ")",
                            'created_by'               => $userId ?? auth()->id(),
                        ]);
                    }
                }
            }

            // Sync current instance attributes
            $this->paid_amount = $newPaid;
            $this->due_amount = $newDue;
            $this->doctor_share = $newDoctorShare;
            $this->hospital_share = $newHospitalShare;
            $this->payment_status = $newStatus;

            return $payment;
        });
    }

    /**
     * Scope: Filter by unpaid or partially paid bills.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('due_amount', '>', 0);
    }

    public function scopeDirect($query)
    {
        return $query->where('billing_type', 'direct');
    }

    public function scopeOpd($query)
    {
        return $query->where('billing_type', 'opd');
    }

    public function items(): HasMany
    {
        return $this->hasMany(HospitalBillItem::class, 'hospital_bill_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(HospitalBillPaymentAllocation::class, 'hospital_bill_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospitalService::class, 'hospital_service_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PatientToken::class, 'patient_token_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(HospitalBillPayment::class, 'hospital_bill_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(DoctorLedger::class, 'hospital_bill_id');
    }

    public function hospitalLedgers(): HasMany
    {
        return $this->hasMany(HospitalAccountLedger::class, 'hospital_bill_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->payment_status);
    }
}
