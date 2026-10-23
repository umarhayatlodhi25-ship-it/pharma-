<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalAccountLedger extends Model
{
    use HasFactory;

    protected $table = 'hospital_account_ledgers';

    protected $fillable = [
        'transaction_number',
        'entry_date',
        'patient_id',
        'hospital_bill_id',
        'hospital_bill_payment_id',
        'patient_token_id',
        'transaction_type',
        'description',
        'gross_amount',
        'discount_amount',
        'amount_received',
        'outstanding_balance',
        'payment_method',
        'status',
        'created_by',
    ];

    protected $casts = [
        'entry_date'          => 'date',
        'gross_amount'        => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'amount_received'     => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (HospitalAccountLedger $ledger) {
            if (empty($ledger->transaction_number)) {
                $ledger->transaction_number = self::generateTransactionNumber();
            }
            if (empty($ledger->entry_date)) {
                $ledger->entry_date = now()->toDateString();
            }
        });
    }

    /**
     * Generate unique transaction number for hospital ledger.
     */
    public static function generateTransactionNumber(): string
    {
        $prefix = 'HAL-' . date('Ymd') . '-';
        $latest = self::where('transaction_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->transaction_number, $matches)) {
            $seq = intval($matches[1]) + 1;
        }

        do {
            $txNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $exists = self::where('transaction_number', $txNo)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $txNo;
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(HospitalBillPayment::class, 'hospital_bill_payment_id');
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PatientToken::class, 'patient_token_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeCollections($query)
    {
        return $query->where('transaction_type', 'collection')->where('status', 'posted');
    }

    public function scopeReceivables($query)
    {
        return $query->where('transaction_type', 'receivable')->where('status', 'posted');
    }
}
