<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicinePackaging extends Model
{
    use HasFactory;

    protected $table = 'medicine_packaging';

    protected $fillable = [
        'medicine_id',
        'unit_id',
        'parent_packaging_id',
        'quantity_in_parent',
        'conversion_to_base',
        'display_name',
        'barcode',
        'purchase_price',
        'sale_price',
        'allow_purchase',
        'allow_sale',
        'status',
    ];

    protected $casts = [
        'quantity_in_parent' => 'decimal:4',
        'conversion_to_base' => 'decimal:4',
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'allow_purchase' => 'boolean',
        'allow_sale' => 'boolean',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function parentPackaging(): BelongsTo
    {
        return $this->belongsTo(MedicinePackaging::class, 'parent_packaging_id');
    }

    public function childPackagings(): HasMany
    {
        return $this->hasMany(MedicinePackaging::class, 'parent_packaging_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSale($query)
    {
        return $query->whereRaw('allow_sale is true')->where('status', 'active');
    }

    public function scopeForPurchase($query)
    {
        return $query->whereRaw('allow_purchase is true')->where('status', 'active');
    }

    /**
     * Get effective unit name
     */
    public function getUnitNameAttribute(): string
    {
        return $this->unit?->name ?? 'Unit';
    }

    /**
     * Get label for display in select dropdowns
     */
    public function getSelectLabelAttribute(): string
    {
        $name = $this->unit?->name ?? 'Unit';
        $price = $this->sale_price !== null ? ' - Rs. ' . number_format($this->sale_price, 2) : '';
        return "{$name}{$price}";
    }
}
