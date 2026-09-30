<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorLedger extends Model
{
    use HasFactory;

    protected $table = 'doctor_ledgers';

    protected $fillable = [
        'doctor_id',
        'hospital_bill_id',
        'hospital_bill_payment_id',
        'doctor_settlement_id',
        'entry_date',
        'transaction_type',
        'amount',
        'balance_after',
        'description',
        'created_by',
    ];

    protected $casts = [
        'entry_date'    => 'date',
        'amount'        => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(HospitalBillPayment::class, 'hospital_bill_payment_id');
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(DoctorSettlement::class, 'doctor_settlement_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
