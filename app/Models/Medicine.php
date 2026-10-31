<?php

namespace App\Models;

use App\Services\PackagingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'product_type',
        'name',
        'generic_name',
        'brand',
        'strength',
        'dosage_form',
        'dosage_unit',
        'base_unit_id',
        'primary_unit',
        'secondary_unit',
        'base_unit',
        'primary_unit_to_secondary',
        'secondary_unit_to_base',
        'unit_price',
        'purchase_price',
        'primary_unit_selling_price',
        'secondary_unit_selling_price',
        'base_unit_selling_price',
        'manufacturer',
        'barcode',
        'sku',
        'alert_quantity',
        'reorder_level',
        'tax_rate',
        'status',
        'has_expiry',
        'track_batches',
    ];

    protected $casts = [
        'has_expiry' => 'boolean',
        'track_batches' => 'boolean',
        'unit_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'primary_unit_selling_price' => 'decimal:2',
        'secondary_unit_selling_price' => 'decimal:2',
        'base_unit_selling_price' => 'decimal:2',
        'primary_unit_to_secondary' => 'integer',
        'secondary_unit_to_base' => 'integer',
        'alert_quantity' => 'integer',
        'reorder_level' => 'integer',
        'tax_rate' => 'decimal:2',
    ];

    protected $appends = [
        'selling_price',
        'is_medicine',
        'is_general',
        'is_out_of_stock',
        'is_expired_stock',
        'stock_status',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function packagings(): HasMany
    {
        return $this->hasMany(MedicinePackaging::class, 'medicine_id')->orderBy('conversion_to_base');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(MedicineBatch::class);
    }

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class, 'medicine_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'medicine_id');
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->batches()->sum('quantity');
    }

    public function getLatestBatchAttribute()
    {
        return $this->batches()
            ->latest('id')
            ->first();
    }

    public function getSellingPriceAttribute(): float
    {
        $batch = $this->batches->where('quantity', '>', 0)->sortBy('expiry_date')->first() 
            ?? $this->batches->sortByDesc('id')->first();

        if ($batch && $batch->selling_price !== null) {
            return (float) $batch->selling_price;
        }

        return (float) ($this->base_unit_selling_price ?? $this->unit_price ?? 0);
    }

    public function getIsMedicineAttribute(): bool
    {
        return ($this->product_type ?? 'medicine') === 'medicine';
    }

    public function getIsGeneralAttribute(): bool
    {
        return ($this->product_type ?? 'medicine') === 'general';
    }

    public function scopeMedicine($query)
    {
        return $query->where(function ($q) {
            $q->where('product_type', 'medicine')
              ->orWhereNull('product_type');
        });
    }

    public function scopeGeneral($query)
    {
        return $query->where('product_type', 'general');
    }

    public function scopeProductType($query, $type)
    {
        if ($type === 'medicine') {
            return $this->scopeMedicine($query);
        }
        if ($type === 'general') {
            return $this->scopeGeneral($query);
        }
        return $query;
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock <= ($this->reorder_level ?? $this->alert_quantity ?? 10);
    }

    // =========================================================================
    // UNIT HIERARCHY CONVERSION METHODS
    // =========================================================================

    public function getPrimaryToBaseMultiplierAttribute(): int
    {
        $p2s = (int) ($this->primary_unit_to_secondary ?: 1);
        $s2b = (int) ($this->secondary_unit_to_base ?: 1);
        return max(1, $p2s * $s2b);
    }

    public function getSecondaryToBaseMultiplierAttribute(): int
    {
        return max(1, (int) ($this->secondary_unit_to_base ?: 1));
    }

    public function getMultiplierForUnit(?string $unitName): float
    {
        return app(PackagingService::class)->getConversionFactor($this, $unitName);
    }

    public function convertToBaseQuantity(float $quantity, $unitName = null): float
    {
        return app(PackagingService::class)->convertToBaseQuantity($this, $unitName, $quantity);
    }

    public function convertFromBaseQuantity(float $baseQuantity, $unitName = null): float
    {
        return app(PackagingService::class)->convertFromBaseQuantity($this, $baseQuantity, $unitName);
    }

    public function getSellingPriceForUnit($unitName = null): float
    {
        return app(PackagingService::class)->getSellingPriceForUnit($this, $unitName);
    }

    public function getPurchasePriceForUnit($unitName = null): float
    {
        return app(PackagingService::class)->getPurchasePriceForUnit($this, $unitName);
    }

    public function getAvailableUnitsAttribute(): array
    {
        $collection = app(PackagingService::class)->getAvailableSaleUnits($this);
        $result = [];
        foreach ($collection as $item) {
            $key = $item['is_base'] ? 'base' : Str::slug($item['name']);
            $result[$key] = [
                'key' => $key,
                'name' => $item['name'],
                'multiplier' => $item['multiplier'],
                'price' => $item['price'],
                'packaging_id' => $item['packaging_id'] ?? null,
            ];
        }
        return $result;
    }

    public function formatStockInUnits(?int $baseStock = null): string
    {
        return app(PackagingService::class)->formatStockBreakdown($this, $baseStock);
    }

    /**
     * Get available saleable stock in base units (excluding expired batches and zero/negative batches).
     */
    public function getAvailableStockAttribute(): float
    {
        return $this->getAvailableStock();
    }

    /**
     * Calculate available saleable stock in base units.
     */
    public function getAvailableStock(): float
    {
        $now = now()->toDateString();

        // If batches are pre-loaded on the model
        if ($this->relationLoaded('batches')) {
            return (float) $this->batches
                ->filter(function ($b) use ($now) {
                    if ((float)$b->quantity <= 0) return false;
                    if ($b->status === 'expired' || $b->status === 'depleted') return false;
                    if ($this->has_expiry && !empty($b->expiry_date)) {
                        $expStr = $b->expiry_date instanceof \DateTimeInterface 
                            ? $b->expiry_date->format('Y-m-d') 
                            : substr((string)$b->expiry_date, 0, 10);
                        if ($expStr < $now) {
                            return false;
                        }
                    }
                    return true;
                })
                ->sum('quantity');
        }

        $query = $this->batches()
            ->where('quantity', '>', 0)
            ->whereNotIn('status', ['expired', 'depleted']);

        if ($this->has_expiry) {
            $query->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>=', $now);
            });
        }

        return (float) $query->sum('quantity');
    }

    /**
     * Get total physical stock in base units (all batches with quantity > 0, regardless of expiry).
     */
    public function getPhysicalStock(): float
    {
        if ($this->relationLoaded('batches')) {
            return (float) $this->batches
                ->filter(fn ($b) => (float)$b->quantity > 0)
                ->sum('quantity');
        }

        return (float) $this->batches()
            ->where('quantity', '>', 0)
            ->sum('quantity');
    }

    public function getPhysicalStockAttribute(): float
    {
        return $this->getPhysicalStock();
    }

    /**
     * Alias for valid saleable stock in base units.
     */
    public function getValidStock(): float
    {
        return $this->getAvailableStock();
    }

    public function getValidStockAttribute(): float
    {
        return $this->getValidStock();
    }

    /**
     * Get total expired stock in base units.
     */
    public function getExpiredStock(): float
    {
        return max(0, $this->getPhysicalStock() - $this->getAvailableStock());
    }

    public function getExpiredStockAttribute(): float
    {
        return $this->getExpiredStock();
    }

    /**
     * Get the latest expired date among expired batches (for POS display).
     */
    public function getLatestExpiredDate(): ?string
    {
        $now = now()->toDateString();
        $batches = $this->relationLoaded('batches')
            ? $this->batches
            : $this->batches()->where('quantity', '>', 0)->get();

        $expired = $batches->filter(function ($b) use ($now) {
            if ((float)$b->quantity <= 0) return false;
            if ($b->status === 'expired') return true;
            if ($this->has_expiry && !empty($b->expiry_date)) {
                $expStr = $b->expiry_date instanceof \DateTimeInterface
                    ? $b->expiry_date->format('Y-m-d')
                    : substr((string)$b->expiry_date, 0, 10);
                return $expStr < $now;
            }
            return false;
        })->sortByDesc('expiry_date')->first();

        if ($expired && $expired->expiry_date) {
            return $expired->expiry_date instanceof \DateTimeInterface
                ? $expired->expiry_date->format('d M Y')
                : substr((string)$expired->expiry_date, 0, 10);
        }

        return null;
    }

    /**
     * Determine stock availability status: 'out_of_stock', 'expired_stock', or 'available'.
     */
    public function getStockStatusAttribute(): string
    {
        if ($this->getPhysicalStock() <= 0) {
            return 'out_of_stock';
        }

        if ($this->getAvailableStock() <= 0) {
            return 'expired_stock';
        }

        return 'available';
    }

    /**
     * Determine if physical stock is completely zero.
     */
    public function getIsOutOfStockAttribute(): bool
    {
        return $this->getPhysicalStock() <= 0;
    }

    /**
     * Determine if physical stock exists but is 100% expired.
     */
    public function getIsExpiredStockAttribute(): bool
    {
        return $this->getPhysicalStock() > 0 && $this->getAvailableStock() <= 0;
    }

    /**
     * Determine if medicine has valid unexpired stock available to sell.
     */
    public function getIsSaleableAttribute(): bool
    {
        return $this->getAvailableStock() > 0;
    }

    /**
     * Format available saleable stock breakdown for POS display.
     */
    public function formatAvailableStock(): string
    {
        $avail = $this->getAvailableStock();
        if ($avail <= 0) {
            return '0 ' . ($this->base_unit ?: 'Units');
        }
        return app(PackagingService::class)->formatStockBreakdown($this, $avail);
    }

    /**
     * Check if a specific packaging unit or conversion multiplier has enough available stock.
     */
    public function isPackagingUnitAvailable($packagingOrConversion): bool
    {
        $conversion = 1.0;
        if ($packagingOrConversion instanceof MedicinePackaging) {
            $conversion = (float)($packagingOrConversion->conversion_to_base ?: 1.0);
        } elseif (is_numeric($packagingOrConversion)) {
            $conversion = (float)$packagingOrConversion;
        }

        return $conversion > 0 && $this->getAvailableStock() >= $conversion;
    }
}