<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HospitalBillItem extends Model
{
    use HasFactory;

    protected $table = 'hospital_bill_items';

    protected $fillable = [
        'hospital_bill_id',
        'hospital_service_id',
        'service_name',
        'service_code',
        'quantity',
        'unit_price',
        'subtotal',
        'discount_amount',
        'total_price',
        'notes',
    ];

    protected $casts = [
        'quantity'        => 'decimal:2',
        'unit_price'      => 'decimal:2',
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price'     => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(HospitalBill::class, 'hospital_bill_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospitalService::class, 'hospital_service_id');
    }
}
