<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalBillPaymentAllocation extends Model
{
    use HasFactory;

    protected $table = 'hospital_bill_payment_allocations';

    protected $fillable = [
        'hospital_bill_payment_id',
        'hospital_bill_id',
        'allocated_amount',
        'allocation_type',
    ];

    protected $casts = [
        'allocated_amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(HospitalBillPayment::class, 'hospital_bill_payment_id');
    }

    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }
}
