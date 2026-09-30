<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalBillPayment extends Model
{
    use HasFactory;

    protected $table = 'hospital_bill_payments';

    protected $fillable = [
        'hospital_bill_id',
        'payment_number',
        'payment_date',
        'amount',
        'doctor_share',
        'hospital_share',
        'payment_method',
        'reference_note',
        'received_by',
    ];

    protected $casts = [
        'payment_date'   => 'date',
        'amount'         => 'decimal:2',
        'doctor_share'   => 'decimal:2',
        'hospital_share' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
