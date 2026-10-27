<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineBatch extends Model
{
    protected $fillable = [
        'medicine_id',
        'supplier_id',
        'batch_number',
        'manufacturing_date',
        'quantity',
        'expiry_date',
        'purchase_price',
        'selling_price',
        'purchase_price_per_base_unit',
        'selling_price_per_base_unit',
        'status',
    ];

    protected $casts = [
        'manufacturing_date' => 'date',
        'expiry_date' => 'date',
        'quantity' => 'decimal:4',
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'purchase_price_per_base_unit' => 'decimal:2',
        'selling_price_per_base_unit' => 'decimal:2',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class)->withTrashed();
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'expired')->where('status', '!=', 'depleted');
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhereDate('expiry_date', '>', now()->toDateString());
        });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date ? $this->expiry_date->isPast() : false;
    }
}