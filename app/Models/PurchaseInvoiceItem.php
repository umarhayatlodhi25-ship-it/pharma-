<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_invoice_id',
        'medicine_id',
        'packaging_id',
        'unit_id',
        'batch_number',
        'unit',
        'conversion_to_base',
        'quantity',
        'base_quantity',
        'purchase_price',
        'selling_price',
        'expiry_date',
        'tax_percent',
        'total'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'conversion_to_base' => 'decimal:4',
        'base_quantity' => 'decimal:4',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'total' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class)->withTrashed();
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(MedicinePackaging::class, 'packaging_id');
    }

    public function unitModel(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}