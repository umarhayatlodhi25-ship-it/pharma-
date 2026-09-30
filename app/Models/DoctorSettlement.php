<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DoctorSettlement extends Model
{
    use HasFactory;

    protected $table = 'doctor_settlements';

    protected $fillable = [
        'settlement_number',
        'doctor_id',
        'settlement_date',
        'previous_payable',
        'paid_amount',
        'remaining_payable',
        'payment_method',
        'reference_note',
        'created_by',
    ];

    protected $casts = [
        'settlement_date'   => 'date',
        'previous_payable'  => 'decimal:2',
        'paid_amount'       => 'decimal:2',
        'remaining_payable' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (DoctorSettlement $settlement) {
            if (empty($settlement->settlement_number)) {
                $settlement->settlement_number = self::generateSettlementNumber();
            }
            if (empty($settlement->settlement_date)) {
                $settlement->settlement_date = now()->toDateString();
            }
        });
    }

    public static function generateSettlementNumber(): string
    {
        $prefix = 'SET-' . date('Ymd') . '-';
        $latest = self::where('settlement_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->settlement_number, $matches)) {
            $seq = intval($matches[1]) + 1;
        }

        do {
            $settleNo = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
            $exists = self::where('settlement_number', $settleNo)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $settleNo;
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(DoctorLedger::class, 'doctor_settlement_id');
    }
}
