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
        'created_by',
    ];

    protected $casts = [
        'bill_date'                 => 'date',
        'total_amount'              => 'decimal:2',
        'discount_amount'           => 'decimal:2',
        'net_amount'                => 'decimal:2',
        'paid_amount'               => 'decimal:2',
        'due_amount'                => 'decimal:2',
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
    public function recordPayment(float $amount, string $paymentMethod = 'cash', ?string $referenceNote = null, ?int $userId = null): HospitalBillPayment
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        if ($amount > (float)$this->due_amount) {
            throw new \InvalidArgumentException("Payment amount (PKR {$amount}) cannot exceed outstanding remaining balance (PKR {$this->due_amount}).");
        }

        return DB::transaction(function () use ($amount, $paymentMethod, $referenceNote, $userId) {
            // Incremental share calculation for this payment
            $docSharePct = (float)$this->doctor_share_percentage;
            $hospSharePct = (float)$this->hospital_share_percentage;

            $incDocShare = round($amount * ($docSharePct / 100), 2);
            $incHospShare = round($amount - $incDocShare, 2);

            // Create payment transaction
            $paymentCount = $this->payments()->count() + 1;
            $paymentNumber = 'RCPT-' . $this->bill_number . '-' . $paymentCount;

            $payment = HospitalBillPayment::create([
                'hospital_bill_id' => $this->id,
                'payment_number'   => $paymentNumber,
                'payment_date'     => now()->toDateString(),
                'amount'           => $amount,
                'doctor_share'     => $incDocShare,
                'hospital_share'   => $incHospShare,
                'payment_method'   => $paymentMethod,
                'reference_note'   => $referenceNote,
                'received_by'      => $userId ?? auth()->id(),
            ]);

            // Update bill totals
            $newPaid = round((float)$this->paid_amount + $amount, 2);
            $newDue = max(0.00, round((float)$this->net_amount - $newPaid, 2));
            $newStatus = ($newDue <= 0.00) ? 'paid' : 'partial';

            $this->update([
                'paid_amount'    => $newPaid,
                'due_amount'     => $newDue,
                'doctor_share'   => round((float)$this->doctor_share + $incDocShare, 2),
                'hospital_share' => round((float)$this->hospital_share + $incHospShare, 2),
                'payment_status' => $newStatus,
            ]);

            // Post credit to Doctor Ledger if doctor is assigned and earned a share
            if ($this->doctor_id && $incDocShare > 0) {
                $doctor = Doctor::find($this->doctor_id);
                if ($doctor) {
                    $currPayable = (float)$doctor->current_payable;
                    $balanceAfter = round($currPayable + $incDocShare, 2);

                    DoctorLedger::create([
                        'doctor_id'                => $this->doctor_id,
                        'hospital_bill_id'         => $this->id,
                        'hospital_bill_payment_id' => $payment->id,
                        'entry_date'               => now()->toDateString(),
                        'transaction_type'         => 'credit',
                        'amount'                   => $incDocShare,
                        'balance_after'            => $balanceAfter,
                        'description'              => "Doctor share earned from Bill #{$this->bill_number} (Patient: {$this->patient->name})",
                        'created_by'               => $userId ?? auth()->id(),
                    ]);
                }
            }

            return $payment;
        });
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->payment_status);
    }
}
