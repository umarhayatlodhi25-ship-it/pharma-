<?php

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\Unit;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PackagingService
{
    /**
     * Resolve Medicine model from instance or ID.
     */
    public function resolveMedicine($medicine): Medicine
    {
        if ($medicine instanceof Medicine) {
            return $medicine;
        }
        return Medicine::with('packagings.unit')->findOrFail($medicine);
    }

    /**
     * Convert given quantity in a specific packaging unit to Base Unit quantity.
     * 
     * Rule: baseQuantity = quantity * conversionToBase
     */
    public function convertToBaseQuantity($medicine, $packagingOrUnit, float $quantity): float
    {
        $med = $this->resolveMedicine($medicine);
        $conversion = $this->getConversionFactor($med, $packagingOrUnit);
        return round($quantity * $conversion, 4);
    }

    /**
     * Convert Base Unit quantity to quantity in a specific packaging unit.
     */
    public function convertFromBaseQuantity($medicine, float $baseQuantity, $packagingOrUnit): float
    {
        $med = $this->resolveMedicine($medicine);
        $conversion = $this->getConversionFactor($med, $packagingOrUnit);
        return $conversion > 0 ? round($baseQuantity / $conversion, 4) : $baseQuantity;
    }

    /**
     * Get conversion multiplier (conversion_to_base) for a given medicine and unit/packaging.
     */
    public function getConversionFactor($medicine, $packagingOrUnit): float
    {
        $medicine = $this->resolveMedicine($medicine);

        if (empty($packagingOrUnit)) {
            return 1.0;
        }

        // If instance of MedicinePackaging passed
        if ($packagingOrUnit instanceof MedicinePackaging) {
            return (float) ($packagingOrUnit->conversion_to_base ?: 1.0);
        }

        // If numeric packaging ID passed
        if (is_numeric($packagingOrUnit)) {
            $pkg = MedicinePackaging::find((int) $packagingOrUnit);
            if ($pkg && (int)$pkg->medicine_id === (int)$medicine->id) {
                return (float) ($pkg->conversion_to_base ?: 1.0);
            }
        }

        $unitStr = trim(strtolower((string) $packagingOrUnit));

        // Check if medicine has packagings configured in `medicine_packaging` table
        $packagings = $medicine->packagings;
        if ($packagings && $packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $unitName = strtolower(trim($pkg->unit?->name ?? ''));
                $unitId = strtolower(trim($pkg->unit?->unit_id ?? ''));
                $displayName = strtolower(trim($pkg->display_name ?? ''));

                if ($unitStr === $unitName || $unitStr === $unitId || $unitStr === $displayName || $unitStr === (string)$pkg->id) {
                    return (float) ($pkg->conversion_to_base ?: 1.0);
                }
            }
        }

        // Fallback for legacy / columns primary_unit & secondary_unit
        if (!empty($medicine->primary_unit) && ($unitStr === strtolower(trim($medicine->primary_unit)) || $unitStr === 'primary')) {
            $p2s = (int) ($medicine->primary_unit_to_secondary ?: 1);
            $s2b = (int) ($medicine->secondary_unit_to_base ?: 1);
            return (float) max(1, $p2s * $s2b);
        }

        if (!empty($medicine->secondary_unit) && ($unitStr === strtolower(trim($medicine->secondary_unit)) || $unitStr === 'secondary')) {
            return (float) max(1, (int) ($medicine->secondary_unit_to_base ?: 1));
        }

        return 1.0;
    }

    /**
     * Get selling price for a medicine packaging unit.
     */
    /**
     * Get selling price for a medicine packaging unit.
     */
    public function getSellingPriceForUnit($medicine, $packagingOrUnit): float
    {
        $medicine = $this->resolveMedicine($medicine);

        if (empty($packagingOrUnit)) {
            return (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0);
        }

        if ($packagingOrUnit instanceof MedicinePackaging) {
            if ($packagingOrUnit->sale_price !== null && (float)$packagingOrUnit->sale_price > 0) {
                return (float) $packagingOrUnit->sale_price;
            }
            $basePrice = (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0);
            return round($basePrice * (float)($packagingOrUnit->conversion_to_base ?: 1), 2);
        }

        if (is_numeric($packagingOrUnit)) {
            $pkg = MedicinePackaging::find((int) $packagingOrUnit);
            if ($pkg && (int)$pkg->medicine_id === (int)$medicine->id) {
                return $this->getSellingPriceForUnit($medicine, $pkg);
            }
        }

        $unitStr = trim(strtolower((string) $packagingOrUnit));

        // Check configured packagings
        $packagings = $medicine->packagings;
        if ($packagings && $packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $unitName = strtolower(trim($pkg->unit?->name ?? ''));
                $unitId = strtolower(trim($pkg->unit?->unit_id ?? ''));
                if ($unitStr === $unitName || $unitStr === $unitId || $unitStr === (string)$pkg->id) {
                    return $this->getSellingPriceForUnit($medicine, $pkg);
                }
            }
        }

        // Legacy columns
        if (!empty($medicine->primary_unit) && ($unitStr === strtolower(trim($medicine->primary_unit)) || $unitStr === 'primary')) {
            if ($medicine->primary_unit_selling_price !== null && (float)$medicine->primary_unit_selling_price > 0) {
                return (float) $medicine->primary_unit_selling_price;
            }
            $mult = $this->getConversionFactor($medicine, 'primary');
            $basePrice = (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0);
            return round($basePrice * $mult, 2);
        }

        if (!empty($medicine->secondary_unit) && ($unitStr === strtolower(trim($medicine->secondary_unit)) || $unitStr === 'secondary')) {
            if ($medicine->secondary_unit_selling_price !== null && (float)$medicine->secondary_unit_selling_price > 0) {
                return (float) $medicine->secondary_unit_selling_price;
            }
            $mult = $this->getConversionFactor($medicine, 'secondary');
            $basePrice = (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0);
            return round($basePrice * $mult, 2);
        }

        return (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0);
    }

    /**
     * Get purchase price for a medicine packaging unit.
     */
    public function getPurchasePriceForUnit($medicine, $packagingOrUnit): float
    {
        $medicine = $this->resolveMedicine($medicine);

        if (empty($packagingOrUnit)) {
            return (float) ($medicine->purchase_price ?? 0);
        }

        if ($packagingOrUnit instanceof MedicinePackaging) {
            if ($packagingOrUnit->purchase_price !== null && (float)$packagingOrUnit->purchase_price > 0) {
                return (float) $packagingOrUnit->purchase_price;
            }
            $basePrice = (float) ($medicine->purchase_price ?? 0);
            return round($basePrice * (float)($packagingOrUnit->conversion_to_base ?: 1), 2);
        }

        if (is_numeric($packagingOrUnit)) {
            $pkg = MedicinePackaging::find((int) $packagingOrUnit);
            if ($pkg && (int)$pkg->medicine_id === (int)$medicine->id) {
                return $this->getPurchasePriceForUnit($medicine, $pkg);
            }
        }

        $unitStr = trim(strtolower((string) $packagingOrUnit));

        $packagings = $medicine->packagings;
        if ($packagings && $packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $unitName = strtolower(trim($pkg->unit?->name ?? ''));
                $unitId = strtolower(trim($pkg->unit?->unit_id ?? ''));
                if ($unitStr === $unitName || $unitStr === $unitId || $unitStr === (string)$pkg->id) {
                    return $this->getPurchasePriceForUnit($medicine, $pkg);
                }
            }
        }

        $mult = $this->getConversionFactor($medicine, $packagingOrUnit);
        return round(((float) ($medicine->purchase_price ?? 0)) * $mult, 2);
    }

    /**
     * Get all available units for sale for a medicine.
     */
    public function getAvailableSaleUnits($medicine): Collection
    {
        $medicine = $this->resolveMedicine($medicine);
        $units = collect();

        // 1. From medicine_packaging relations
        $packagings = $medicine->packagings()->with('unit')->forSale()->orderByRaw('CAST(conversion_to_base AS REAL) DESC')->get();
        if ($packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $units->push([
                    'packaging_id' => $pkg->id,
                    'unit_id' => $pkg->unit_id,
                    'name' => $pkg->unit?->name ?? 'Unit',
                    'multiplier' => (float) $pkg->conversion_to_base,
                    'price' => $this->getSellingPriceForUnit($medicine, $pkg),
                    'barcode' => $pkg->barcode,
                    'is_base' => ((float)$pkg->conversion_to_base === 1.0),
                ]);
            }
            return $units;
        }

        // 2. Fallback from legacy columns
        if (!empty($medicine->primary_unit)) {
            $pMult = $this->getConversionFactor($medicine, $medicine->primary_unit);
            $units->push([
                'packaging_id' => null,
                'unit_id' => null,
                'name' => $medicine->primary_unit,
                'multiplier' => $pMult,
                'price' => $this->getSellingPriceForUnit($medicine, $medicine->primary_unit),
                'barcode' => null,
                'is_base' => false,
            ]);
        }

        if (!empty($medicine->secondary_unit)) {
            $sMult = $this->getConversionFactor($medicine, $medicine->secondary_unit);
            $units->push([
                'packaging_id' => null,
                'unit_id' => null,
                'name' => $medicine->secondary_unit,
                'multiplier' => $sMult,
                'price' => $this->getSellingPriceForUnit($medicine, $medicine->secondary_unit),
                'barcode' => null,
                'is_base' => false,
            ]);
        }

        $baseName = $medicine->base_unit ?: ($medicine->dosage_unit ?: ($medicine->is_general ? 'Piece' : 'Tablet'));
        $units->push([
            'packaging_id' => null,
            'unit_id' => null,
            'name' => $baseName,
            'multiplier' => 1.0,
            'price' => (float) ($medicine->base_unit_selling_price ?? $medicine->unit_price ?? 0),
            'barcode' => $medicine->barcode,
            'is_base' => true,
        ]);

        return $units;
    }

    /**
     * Get all available units for purchase for a medicine.
     */
    public function getAvailablePurchaseUnits($medicine): Collection
    {
        $medicine = $this->resolveMedicine($medicine);
        $units = collect();

        $packagings = $medicine->packagings()->with('unit')->forPurchase()->orderByRaw('CAST(conversion_to_base AS REAL) DESC')->get();
        if ($packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $units->push([
                    'packaging_id' => $pkg->id,
                    'unit_id' => $pkg->unit_id,
                    'name' => $pkg->unit?->name ?? 'Unit',
                    'multiplier' => (float) $pkg->conversion_to_base,
                    'purchase_price' => $this->getPurchasePriceForUnit($medicine, $pkg),
                    'sale_price' => $this->getSellingPriceForUnit($medicine, $pkg),
                    'barcode' => $pkg->barcode,
                    'is_base' => ((float)$pkg->conversion_to_base === 1.0),
                ]);
            }
            return $units;
        }

        // Fallback
        return $this->getAvailableSaleUnits($medicine)->map(function ($item) use ($medicine) {
            $item['purchase_price'] = $this->getPurchasePriceForUnit($medicine, $item['name']);
            return $item;
        });
    }

    /**
     * Calculate and format human-readable stock breakdown from Base Unit stock.
     * 
     * Example: 850 Tablets with Pack(100), Strip(10), Tablet(1)
     * Output: "8 Packs, 5 Strips, 0 Tablets (Total: 850 Tablets)"
     */
    public function formatStockBreakdown($medicine, ?float $baseStock = null): string
    {
        $medicine = $this->resolveMedicine($medicine);
        $stock = $baseStock !== null ? $baseStock : (float) $medicine->total_stock;
        $baseUnitName = $medicine->base_unit ?: ($medicine->dosage_unit ?: ($medicine->is_general ? 'Piece' : 'Tablet'));

        if ($stock <= 0) {
            return "0 " . Str::plural($baseUnitName, 0);
        }

        // Load and sort packagings descending by numeric conversion_to_base
        $packagings = $medicine->packagings ? $medicine->packagings->sortByDesc(fn($p) => (float)$p->conversion_to_base) : collect();
        if ($packagings->isEmpty()) {
            $packagings = $medicine->packagings()->with('unit')->get()->sortByDesc(fn($p) => (float)$p->conversion_to_base);
        }

        $parts = [];
        $remaining = (int) $stock;

        if ($packagings->isNotEmpty()) {
            foreach ($packagings as $pkg) {
                $conv = (int) $pkg->conversion_to_base;
                $unitName = $pkg->unit?->name ?? 'Unit';

                if ($conv > 1) {
                    $qty = intdiv($remaining, $conv);
                    if ($qty > 0) {
                        $parts[] = "{$qty} " . Str::plural($unitName, $qty);
                    }
                    $remaining = $remaining % $conv;
                }
            }
            if ($remaining > 0 || empty($parts)) {
                $parts[] = "{$remaining} " . Str::plural($baseUnitName, $remaining);
            }
        } else {
            // Legacy columns
            $pMult = (int) $this->getConversionFactor($medicine, $medicine->primary_unit);
            $sMult = (int) $this->getConversionFactor($medicine, $medicine->secondary_unit);

            if (!empty($medicine->primary_unit) && $pMult > 1) {
                $pQty = intdiv($remaining, $pMult);
                if ($pQty > 0) {
                    $parts[] = "{$pQty} " . Str::plural($medicine->primary_unit, $pQty);
                }
                $remaining = $remaining % $pMult;
            }

            if (!empty($medicine->secondary_unit) && $sMult > 1) {
                $sQty = intdiv($remaining, $sMult);
                if ($sQty > 0) {
                    $parts[] = "{$sQty} " . Str::plural($medicine->secondary_unit, $sQty);
                }
                $remaining = $remaining % $sMult;
            }

            if ($remaining > 0 || empty($parts)) {
                $parts[] = "{$remaining} " . Str::plural($baseUnitName, $remaining);
            }
        }

        $breakdown = implode(', ', array_filter($parts));
        $hasHigherPacks = ($packagings->isNotEmpty() && $packagings->contains(fn($p) => (float)$p->conversion_to_base > 1))
            || (!empty($medicine->primary_unit) && (int)$this->getConversionFactor($medicine, $medicine->primary_unit) > 1)
            || (!empty($medicine->secondary_unit) && (int)$this->getConversionFactor($medicine, $medicine->secondary_unit) > 1);

        if (!$hasHigherPacks) {
            return $breakdown;
        }

        return "{$breakdown} (Total: {$stock} " . Str::plural($baseUnitName, (int)$stock) . ")";
    }

    /**
     * Validate stock availability for a requested quantity and unit.
     */
    public function validateStockAvailability($medicine, $packagingOrUnit, float $quantity): array
    {
        $medicine = $this->resolveMedicine($medicine);
        $conversion = $this->getConversionFactor($medicine, $packagingOrUnit);
        $requestedBaseQty = round($quantity * $conversion, 4);

        // Get total non-expired available stock
        $availableBaseQty = (float) $medicine->getAvailableStock();

        if ($availableBaseQty <= 0) {
            return [
                'valid' => false,
                'requested_base_qty' => $requestedBaseQty,
                'available_base_qty' => 0.0,
                'message' => 'This medicine is currently out of stock.'
            ];
        }

        if ($requestedBaseQty > $availableBaseQty) {
            $unitName = is_object($packagingOrUnit) ? ($packagingOrUnit->unit?->name ?? 'Unit') : (string)$packagingOrUnit;
            $baseName = $medicine->base_unit ?: ($medicine->dosage_unit ?: 'Tablet');
            $maxPossibleInUnit = $conversion > 0 ? floor($availableBaseQty / $conversion) : 0;

            return [
                'valid' => false,
                'requested_base_qty' => $requestedBaseQty,
                'available_base_qty' => $availableBaseQty,
                'message' => "Insufficient stock. Requested: {$quantity} {$unitName} ({$requestedBaseQty} {$baseName}s). Available: {$maxPossibleInUnit} {$unitName} ({$availableBaseQty} {$baseName}s)."
            ];
        }

        return [
            'valid' => true,
            'requested_base_qty' => $requestedBaseQty,
            'available_base_qty' => $availableBaseQty,
            'message' => 'Stock is available.'
        ];
    }

    /**
     * Batch allocation strategy: FEFO (First Expiry, First Out).
     * Excludes expired batches.
     */
    public function allocateBatchesFefo($medicine, float $requiredBaseQuantity): array
    {
        $medicine = $this->resolveMedicine($medicine);
        $now = now()->toDateString();

        $batchQuery = MedicineBatch::where('medicine_id', $medicine->id)
            ->where('quantity', '>', 0)
            ->whereNotIn('status', ['expired', 'depleted']);

        if ($medicine->has_expiry) {
            $batchQuery->where(function ($q) use ($now) {
                $q->whereNull('expiry_date')
                  ->orWhereDate('expiry_date', '>=', $now);
            });
        }

        $batches = $batchQuery
            ->orderByRaw('CASE WHEN expiry_date IS NULL THEN 1 ELSE 0 END, expiry_date ASC, id ASC')
            ->lockForUpdate()
            ->get();

        $allocations = [];
        $remainingNeeded = $requiredBaseQuantity;

        foreach ($batches as $batch) {
            if ($remainingNeeded <= 0) {
                break;
            }

            $avail = (float) $batch->quantity;
            $take = min($avail, $remainingNeeded);

            $allocations[] = [
                'batch' => $batch,
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'base_quantity' => $take,
                'allocated_quantity' => $take,
                'allocated_base_quantity' => $take,
                'expiry_date' => $batch->expiry_date?->format('Y-m-d'),
                'purchase_price' => $batch->purchase_price,
                'selling_price' => $batch->selling_price,
            ];

            $remainingNeeded -= $take;
        }

        if ($remainingNeeded > 0) {
            throw new \Exception("Insufficient non-expired stock for {$medicine->name}. Needed: {$requiredBaseQuantity} base units, but only " . ($requiredBaseQuantity - $remainingNeeded) . " available.");
        }

        return $allocations;
    }

    /**
     * Find medicine and packaging level by barcode.
     */
    public function findByBarcode(string $barcode): ?array
    {
        $barcode = trim($barcode);
        if (empty($barcode)) {
            return null;
        }

        // 1. Check medicine_packaging barcode
        $pkg = MedicinePackaging::with(['medicine', 'unit'])->where('barcode', $barcode)->first();
        if ($pkg && $pkg->medicine) {
            return [
                'medicine' => $pkg->medicine,
                'packaging' => $pkg,
                'unit_name' => $pkg->unit?->name ?? 'Unit',
                'conversion_to_base' => (float) $pkg->conversion_to_base,
                'price' => (float) ($pkg->sale_price ?? $pkg->medicine->selling_price),
            ];
        }

        // 2. Check medicine barcode
        $medicine = Medicine::where('barcode', $barcode)->first();
        if ($medicine) {
            $baseName = $medicine->base_unit ?: ($medicine->dosage_unit ?: 'Piece');
            return [
                'medicine' => $medicine,
                'packaging' => null,
                'unit_name' => $baseName,
                'conversion_to_base' => 1.0,
                'price' => (float) $medicine->selling_price,
            ];
        }

        return null;
    }
}
