<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\ProductAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductBulkDeletionService
{
    /**
     * Determine preview statistics for bulk deletion.
     *
     * @return array<string, mixed>
     */
    public function getPreviewStats(): array
    {
        // Only consider non-deleted (active) medicines
        $activeMedicines = Medicine::query()->get();
        $totalCount = $activeMedicines->count();

        if ($totalCount === 0) {
            return [
                'total_products' => 0,
                'with_history_count' => 0,
                'without_history_count' => 0,
                'batches_count' => 0,
                'stock_units' => 0,
                'historical_breakdown' => [
                    'sales_items' => 0,
                    'purchase_items' => 0,
                    'returns' => 0,
                    'stock_movements' => 0,
                    'prescriptions' => 0,
                ],
            ];
        }

        $medicineIds = $activeMedicines->pluck('id')->toArray();

        // Check which medicines have references in historical records
        $saleMedicineIds = DB::table('sale_items')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $purchaseMedicineIds = DB::table('purchases')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $purchaseInvoiceMedicineIds = DB::table('purchase_invoice_items')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $salesReturnMedicineIds = DB::table('sales_return_items')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $purchaseReturnMedicineIds = DB::table('purchase_return_items')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $stockMovementMedicineIds = DB::table('stock_movements')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();
        $prescriptionMedicineIds = DB::table('prescription_items')->whereIn('medicine_id', $medicineIds)->distinct()->pluck('medicine_id')->toArray();

        // Check batches that have stock or are linked to batches
        $batchesWithStockOrLinks = DB::table('medicine_batches')
            ->whereIn('medicine_id', $medicineIds)
            ->where(function ($q) {
                $q->where('quantity', '>', 0);
            })
            ->distinct()
            ->pluck('medicine_id')
            ->toArray();

        $referencedIds = array_unique(array_merge(
            $saleMedicineIds,
            $purchaseMedicineIds,
            $purchaseInvoiceMedicineIds,
            $salesReturnMedicineIds,
            $purchaseReturnMedicineIds,
            $stockMovementMedicineIds,
            $prescriptionMedicineIds,
            $batchesWithStockOrLinks
        ));

        $withHistoryCount = count(array_intersect($medicineIds, $referencedIds));
        $withoutHistoryCount = max(0, $totalCount - $withHistoryCount);

        $batchesCount = DB::table('medicine_batches')->whereIn('medicine_id', $medicineIds)->count();
        $totalStockUnits = (int) DB::table('medicine_batches')->whereIn('medicine_id', $medicineIds)->sum('quantity');

        return [
            'total_products' => $totalCount,
            'with_history_count' => $withHistoryCount,
            'without_history_count' => $withoutHistoryCount,
            'batches_count' => $batchesCount,
            'stock_units' => $totalStockUnits,
            'historical_breakdown' => [
                'sales_items' => count($saleMedicineIds),
                'purchase_items' => count(array_unique(array_merge($purchaseMedicineIds, $purchaseInvoiceMedicineIds))),
                'returns' => count(array_unique(array_merge($salesReturnMedicineIds, $purchaseReturnMedicineIds))),
                'stock_movements' => count($stockMovementMedicineIds),
                'prescriptions' => count($prescriptionMedicineIds),
            ],
        ];
    }

    /**
     * Check if a single medicine has any historical dependencies.
     *
     * @param int $medicineId
     * @return array{has_history: bool, reasons: list<string>}
     */
    public function checkProductDependencies(int $medicineId): array
    {
        $reasons = [];

        if (DB::table('sale_items')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in sales transactions (sale_items)';
        }

        if (DB::table('purchases')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in historical purchases (purchases)';
        }

        if (DB::table('purchase_invoice_items')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in purchase invoice items (purchase_invoice_items)';
        }

        if (DB::table('sales_return_items')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in customer sales returns (sales_return_items)';
        }

        if (DB::table('purchase_return_items')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in supplier purchase returns (purchase_return_items)';
        }

        if (DB::table('stock_movements')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in inventory stock movements audit trail (stock_movements)';
        }

        if (DB::table('prescription_items')->where('medicine_id', $medicineId)->exists()) {
            $reasons[] = 'Referenced in doctor prescriptions (prescription_items)';
        }

        $batches = DB::table('medicine_batches')->where('medicine_id', $medicineId)->get();
        if ($batches->isNotEmpty()) {
            $batchIds = $batches->pluck('id')->toArray();

            $batchReferencedInSales = DB::table('sale_items')->whereIn('batch_id', $batchIds)->exists();
            $batchReferencedInReturns = DB::table('sales_return_items')->whereIn('batch_id', $batchIds)->exists()
                || DB::table('purchase_return_items')->whereIn('batch_id', $batchIds)->exists();
            $batchReferencedInMovements = DB::table('stock_movements')->whereIn('batch_id', $batchIds)->exists();
            $batchHasRemainingStock = $batches->sum('quantity') > 0;

            if ($batchReferencedInSales || $batchReferencedInReturns || $batchReferencedInMovements || $batchHasRemainingStock) {
                $reasons[] = 'Has stock batches with transactions or remaining quantity';
            }
        }

        return [
            'has_history' => !empty($reasons),
            'reasons' => $reasons,
        ];
    }

    /**
     * Securely execute the bulk deletion of all products.
     *
     * @param User $admin
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function execute(User $admin, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        // 1. Server-side Authorization Check
        if (!$admin->isAdmin() && $admin->role !== 'admin') {
            Log::warning("Unauthorized bulk product deletion attempt by user #{$admin->id} ({$admin->email}) from IP: {$ipAddress}");
            throw new AuthorizationException('Access Denied: Only authorized administrators can perform bulk product deletion.');
        }

        $timestamp = Carbon::now();
        $archivedIds = [];
        $deletedIds = [];
        $skipped = [];
        $affectedProductIds = [];

        // 2. Atomic Database Transaction with row locking
        $result = DB::transaction(function () use ($admin, $timestamp, &$archivedIds, &$deletedIds, &$skipped, &$affectedProductIds) {
            // Re-fetch all active medicines with pessimistic lock where supported
            $medicines = Medicine::query()
                ->lockForUpdate()
                ->get();

            if ($medicines->isEmpty()) {
                return [
                    'status' => 'success',
                    'total_processed' => 0,
                    'archived_count' => 0,
                    'deleted_count' => 0,
                    'skipped_count' => 0,
                    'archived_ids' => [],
                    'deleted_ids' => [],
                    'skipped' => [],
                    'message' => 'Inventory is already empty. No active products to delete.',
                ];
            }

            foreach ($medicines as $medicine) {
                $affectedProductIds[] = $medicine->id;

                try {
                    // Revalidate dependencies at execution time
                    $depCheck = $this->checkProductDependencies($medicine->id);

                    if ($depCheck['has_history']) {
                        // PRESERVE HISTORICAL RECORDS:
                        // Archive/Deactivate and Soft-delete. Do not delete batches or financial records.
                        $medicine->status = 'archived';
                        $medicine->save();
                        $medicine->delete(); // Soft-deletes: sets deleted_at = now()

                        $archivedIds[] = $medicine->id;
                    } else {
                        // SAFE PERMANENT DELETION:
                        // Clean up orphan packaging & empty inventory cache row
                        MedicinePackaging::where('medicine_id', $medicine->id)->delete();
                        Inventory::where('medicine_id', $medicine->id)->delete();
                        MedicineBatch::where('medicine_id', $medicine->id)->delete();

                        // Force delete medicine row completely
                        $medicine->forceDelete();

                        $deletedIds[] = $medicine->id;
                    }
                } catch (Throwable $e) {
                    Log::error("Failed to process product #{$medicine->id} during bulk deletion: " . $e->getMessage(), [
                        'exception' => $e,
                        'medicine_id' => $medicine->id,
                    ]);

                    $skipped[] = [
                        'id' => $medicine->id,
                        'name' => $medicine->name,
                        'reason' => 'Database constraint or error: ' . $e->getMessage(),
                    ];
                }
            }

            return [
                'status' => empty($skipped) ? 'success' : 'partial',
                'total_processed' => count($affectedProductIds),
                'archived_count' => count($archivedIds),
                'deleted_count' => count($deletedIds),
                'skipped_count' => count($skipped),
                'archived_ids' => $archivedIds,
                'deleted_ids' => $deletedIds,
                'skipped' => $skipped,
            ];
        });

        // 3. Compose detailed outcome message
        $totalProcessed = $result['total_processed'];
        $archivedCount = $result['archived_count'];
        $deletedCount = $result['deleted_count'];
        $skippedCount = $result['skipped_count'];

        if ($totalProcessed === 0) {
            $message = 'Inventory is already empty. No active products were affected.';
        } else {
            $parts = [];
            if ($deletedCount > 0) {
                $parts[] = "{$deletedCount} product(s) with no historical dependencies were permanently removed";
            }
            if ($archivedCount > 0) {
                $parts[] = "{$archivedCount} product(s) with historical transactions (sales, purchases, returns, stock movements) were securely archived/soft-deleted to preserve financial ledgers";
            }
            if ($skippedCount > 0) {
                $parts[] = "{$skippedCount} product(s) could not be safely processed";
            }
            $message = 'Bulk operation completed successfully: ' . implode('; ', $parts) . '.';
        }

        $result['message'] = $message;

        // 4. Record in Audit Log Table
        try {
            ProductAuditLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'user_email' => $admin->email,
                'action' => 'bulk_delete_all_products',
                'description' => $message,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'affected_product_ids' => $affectedProductIds,
                'summary' => [
                    'total_processed' => $totalProcessed,
                    'archived_count' => $archivedCount,
                    'deleted_count' => $deletedCount,
                    'skipped_count' => $skippedCount,
                    'skipped_details' => $skipped,
                    'executed_at' => $timestamp->toIso8601String(),
                ],
                'status' => $result['status'],
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to write bulk deletion audit log: ' . $e->getMessage());
        }

        // 5. Record structured audit log in Laravel standard log
        Log::notice("BULK OPERATION [Delete All Products] executed by Admin #{$admin->id} ({$admin->email}) at {$timestamp->toDateTimeString()}", [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'ip_address' => $ipAddress,
            'timestamp' => $timestamp->toIso8601String(),
            'total_processed' => $totalProcessed,
            'archived_count' => $archivedCount,
            'deleted_count' => $deletedCount,
            'skipped_count' => $skippedCount,
            'affected_product_ids' => $affectedProductIds,
            'outcome' => $result['status'],
        ]);

        return $result;
    }
}
