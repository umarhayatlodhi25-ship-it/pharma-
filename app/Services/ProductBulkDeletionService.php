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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
                'requiring_archival_count' => 0,
                'without_history_count' => 0,
                'eligible_permanent_count' => 0,
                'sales_history_count' => 0,
                'batches_count' => 0,
                'stock_units' => 0,
                'estimated_seconds' => 0,
                'estimated_duration_text' => '< 1 second',
                'estimated_time_text' => '< 1 second',
                'warning' => 'Inventory is already empty. No products eligible for deletion.',
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
        $salesHistoryCount = count($saleMedicineIds);

        $batchesCount = DB::table('medicine_batches')->whereIn('medicine_id', $medicineIds)->count();
        $totalStockUnits = (int) DB::table('medicine_batches')->whereIn('medicine_id', $medicineIds)->sum('quantity');

        $estimatedSeconds = match (true) {
            $totalCount <= 20 => 1,
            $totalCount <= 200 => 2,
            $totalCount <= 1000 => 3,
            $totalCount <= 3000 => 5,
            default => (int) ceil($totalCount / 500) + 2,
        };

        $estimatedTimeText = match (true) {
            $totalCount <= 20 => 'Approx. 1 - 2 seconds',
            $totalCount <= 200 => 'Approx. 2 - 3 seconds',
            $totalCount <= 1000 => 'Approx. 3 - 5 seconds',
            $totalCount <= 3000 => 'Approx. 4 - 7 seconds',
            default => "Approx. {$estimatedSeconds} - " . ($estimatedSeconds + 5) . ' seconds',
        };

        return [
            'total_products' => $totalCount,
            'with_history_count' => $withHistoryCount,
            'requiring_archival_count' => $withHistoryCount,
            'without_history_count' => $withoutHistoryCount,
            'eligible_permanent_count' => $withoutHistoryCount,
            'sales_history_count' => $salesHistoryCount,
            'batches_count' => $batchesCount,
            'stock_units' => $totalStockUnits,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_duration_text' => $estimatedTimeText,
            'estimated_time_text' => $estimatedTimeText,
            'warning' => 'Irreversible Action: Products with zero transaction history will be permanently deleted from the database. Products with past sales, purchases, or ledger entries will be safely archived to protect accounting and legal compliance.',
            'historical_breakdown' => [
                'sales_items' => $salesHistoryCount,
                'purchase_items' => count(array_unique(array_merge($purchaseMedicineIds, $purchaseInvoiceMedicineIds))),
                'returns' => count(array_unique(array_merge($salesReturnMedicineIds, $purchaseReturnMedicineIds))),
                'stock_movements' => count($stockMovementMedicineIds),
                'prescriptions' => count($prescriptionMedicineIds),
            ],
        ];
    }

    /**
     * Get preview statistics for a specific set of selected medicine IDs.
     *
     * @param array<int|string> $medicineIds
     * @return array<string, mixed>
     */
    public function getPreviewStatsForIds(array $medicineIds): array
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $medicineIds))));
        $totalCount = count($cleanIds);

        if ($totalCount === 0) {
            return [
                'total_products' => 0,
                'with_history_count' => 0,
                'requiring_archival_count' => 0,
                'without_history_count' => 0,
                'eligible_permanent_count' => 0,
                'sales_history_count' => 0,
                'batches_count' => 0,
                'estimated_seconds' => 1,
                'estimated_duration_text' => '< 1 second',
                'estimated_time_text' => '< 1 second',
                'warning' => 'No products selected for deletion.',
            ];
        }

        $referencedIds = [];
        $allSaleIds = [];
        $chunks = array_chunk($cleanIds, 1000);

        foreach ($chunks as $chunk) {
            $saleIds = DB::table('sale_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $purchaseIds = DB::table('purchases')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $purchaseInvoiceIds = DB::table('purchase_invoice_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $salesReturnIds = DB::table('sales_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $purchaseReturnIds = DB::table('purchase_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $stockMovementIds = DB::table('stock_movements')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
            $prescriptionIds = DB::table('prescription_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();

            $batchStockIds = DB::table('medicine_batches')
                ->whereIn('medicine_id', $chunk)
                ->where('quantity', '>', 0)
                ->distinct()
                ->pluck('medicine_id')
                ->toArray();

            $allSaleIds = array_unique(array_merge($allSaleIds, $saleIds));

            $referencedIds = array_unique(array_merge(
                $referencedIds,
                $saleIds,
                $purchaseIds,
                $purchaseInvoiceIds,
                $salesReturnIds,
                $purchaseReturnIds,
                $stockMovementIds,
                $prescriptionIds,
                $batchStockIds
            ));
        }

        $withHistoryCount = count(array_intersect($cleanIds, $referencedIds));
        $withoutHistoryCount = max(0, $totalCount - $withHistoryCount);
        $salesHistoryCount = count(array_intersect($cleanIds, $allSaleIds));
        $batchesCount = DB::table('medicine_batches')->whereIn('medicine_id', $cleanIds)->count();

        $estimatedSeconds = match (true) {
            $totalCount <= 20 => 1,
            $totalCount <= 200 => 2,
            $totalCount <= 1000 => 3,
            $totalCount <= 3000 => 5,
            default => (int) ceil($totalCount / 500) + 2,
        };

        $estimatedTimeText = match (true) {
            $totalCount <= 20 => 'Approx. 1 - 2 seconds',
            $totalCount <= 200 => 'Approx. 2 - 3 seconds',
            $totalCount <= 1000 => 'Approx. 3 - 5 seconds',
            $totalCount <= 3000 => 'Approx. 4 - 7 seconds',
            default => "Approx. {$estimatedSeconds} - " . ($estimatedSeconds + 5) . ' seconds',
        };

        return [
            'total_products' => $totalCount,
            'with_history_count' => $withHistoryCount,
            'requiring_archival_count' => $withHistoryCount,
            'without_history_count' => $withoutHistoryCount,
            'eligible_permanent_count' => $withoutHistoryCount,
            'sales_history_count' => $salesHistoryCount,
            'batches_count' => $batchesCount,
            'estimated_seconds' => $estimatedSeconds,
            'estimated_duration_text' => $estimatedTimeText,
            'estimated_time_text' => $estimatedTimeText,
            'warning' => 'Irreversible Action: Products with zero transaction history will be permanently deleted from the database. Products with past sales, purchases, or ledger entries will be safely archived to protect accounting and financial compliance.',
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

    /**
     * Securely and efficiently execute bulk deletion/archiving for selected medicine IDs.
     *
     * @param array<int|string> $medicineIds
     * @param User|null $admin
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function executeForIds(array $medicineIds, ?User $admin = null, ?string $ipAddress = null, ?string $userAgent = null): array
    {
        $admin = $admin ?? auth()->user();
        if (!$admin || (!$admin->isAdmin() && $admin->role !== 'admin')) {
            Log::warning("Unauthorized bulk selected deletion attempt from IP: {$ipAddress}");
            throw new AuthorizationException('Access Denied: Only authorized administrators can perform bulk product deletion.');
        }

        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $medicineIds))));
        $totalCount = count($cleanIds);

        if ($totalCount === 0) {
            return [
                'status' => 'success',
                'total_processed' => 0,
                'archived_count' => 0,
                'deleted_count' => 0,
                'message' => 'No products selected.',
            ];
        }

        $timestamp = Carbon::now();
        $archivedIds = [];
        $deletedIds = [];

        DB::transaction(function () use ($cleanIds, $timestamp, &$archivedIds, &$deletedIds) {
            $chunks = array_chunk($cleanIds, 500);

            foreach ($chunks as $chunk) {
                // Find all referenced IDs in this chunk
                $saleIds = DB::table('sale_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $purchaseIds = DB::table('purchases')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $purchaseInvoiceIds = DB::table('purchase_invoice_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $salesReturnIds = DB::table('sales_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $purchaseReturnIds = DB::table('purchase_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $stockMovementIds = DB::table('stock_movements')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
                $prescriptionIds = DB::table('prescription_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();

                $batchStockIds = DB::table('medicine_batches')
                    ->whereIn('medicine_id', $chunk)
                    ->where('quantity', '>', 0)
                    ->distinct()
                    ->pluck('medicine_id')
                    ->toArray();

                $referenced = array_unique(array_merge(
                    $saleIds,
                    $purchaseIds,
                    $purchaseInvoiceIds,
                    $salesReturnIds,
                    $purchaseReturnIds,
                    $stockMovementIds,
                    $prescriptionIds,
                    $batchStockIds
                ));

                $withHistory = array_values(array_intersect($chunk, $referenced));
                $withoutHistory = array_values(array_diff($chunk, $referenced));

                // 1. Archive items that have historical transactions to protect financial records
                if (!empty($withHistory)) {
                    Medicine::whereIn('id', $withHistory)->update([
                        'status' => 'archived',
                        'deleted_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                    $archivedIds = array_merge($archivedIds, $withHistory);
                }

                // 2. Permanently delete items without history
                if (!empty($withoutHistory)) {
                    MedicinePackaging::whereIn('medicine_id', $withoutHistory)->delete();
                    Inventory::whereIn('medicine_id', $withoutHistory)->delete();
                    MedicineBatch::whereIn('medicine_id', $withoutHistory)->delete();
                    Medicine::whereIn('id', $withoutHistory)->forceDelete();
                    $deletedIds = array_merge($deletedIds, $withoutHistory);
                }
            }
        });

        $archivedCount = count($archivedIds);
        $deletedCount = count($deletedIds);
        $totalProcessed = $archivedCount + $deletedCount;

        $parts = [];
        if ($deletedCount > 0) {
            $parts[] = "{$deletedCount} unreferenced products permanently removed";
        }
        if ($archivedCount > 0) {
            $parts[] = "{$archivedCount} products archived to preserve accounting ledgers and sales records";
        }
        $message = empty($parts) ? 'No products were affected.' : 'Bulk deletion completed: ' . implode('; ', $parts) . '.';

        // Audit Logging
        try {
            ProductAuditLog::create([
                'user_id' => $admin->id,
                'user_name' => $admin->name,
                'user_email' => $admin->email,
                'action' => 'bulk_delete_selected_products',
                'description' => $message,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'affected_product_ids' => $cleanIds,
                'summary' => [
                    'total_processed' => $totalProcessed,
                    'archived_count' => $archivedCount,
                    'deleted_count' => $deletedCount,
                    'executed_at' => $timestamp->toIso8601String(),
                ],
                'status' => 'success',
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to write bulk deletion audit log: ' . $e->getMessage());
        }

        Log::notice("BULK OPERATION [Delete Selected Products] executed by Admin #{$admin->id} ({$admin->email}) for {$totalProcessed} items", [
            'admin_id' => $admin->id,
            'archived_count' => $archivedCount,
            'deleted_count' => $deletedCount,
            'total_processed' => $totalProcessed,
        ]);

        return [
            'status' => 'success',
            'total_processed' => $totalProcessed,
            'archived_count' => $archivedCount,
            'deleted_count' => $deletedCount,
            'message' => $message,
        ];
    }

    /**
     * Initialize a tracked background bulk deletion job.
     *
     * @param User $admin
     * @param array<int|string>|string $targetIds
     * @param string $type ('selected' or 'all')
     * @param string|null $ipAddress
     * @param string|null $userAgent
     * @return array<string, mixed>
     *
     * @throws AuthorizationException
     */
    public function initJob(User $admin, array|string $targetIds, string $type = 'selected', ?string $ipAddress = null, ?string $userAgent = null, bool $force = false): array
    {
        if (!$admin->isAdmin() && $admin->role !== 'admin') {
            Log::warning("Unauthorized bulk product deletion job init attempt by user #{$admin->id} ({$admin->email}) from IP: {$ipAddress}");
            throw new AuthorizationException('Access Denied: Only authorized administrators can perform bulk product deletion.');
        }

        // Prevent duplicate deletion jobs: Check if user already has an active processing job
        if (!$force) {
            $activeJobId = Cache::get("active_bulk_delete_user_{$admin->id}");
            if ($activeJobId) {
                $existing = Cache::get("bulk_delete_job_{$activeJobId}");
                if ($existing && in_array($existing['status'] ?? '', ['processing', 'pending'])) {
                    // If the job was updated in the last 60 seconds, it's actively running
                    if (microtime(true) - ($existing['last_updated_at'] ?? 0) < 60) {
                        return $existing;
                    }
                }
            }
        }

        if ($targetIds === 'all' || $type === 'all') {
            $cleanIds = Medicine::query()->pluck('id')->map(fn($id) => (int)$id)->toArray();
            $type = 'all';
        } else {
            $rawIds = array_values(array_unique(array_filter(array_map('intval', (array) $targetIds))));
            $existingIds = Medicine::withTrashed()->whereIn('id', $rawIds)->pluck('id')->map(fn($id) => (int)$id)->toArray();
            $cleanIds = array_values(array_intersect($rawIds, $existingIds));
            $type = 'selected';
        }

        $totalCount = count($cleanIds);
        $jobId = 'bdel_' . Str::random(24);
        $now = microtime(true);
        $batchSize = 75;

        $jobData = [
            'job_id' => $jobId,
            'user_id' => $admin->id,
            'user_name' => $admin->name,
            'user_email' => $admin->email,
            'type' => $type,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'total' => $totalCount,
            'pending_ids' => $cleanIds,
            'processed_ids' => [],
            'processed' => 0,
            'deleted' => 0,
            'archived' => 0,
            'failed' => 0,
            'deleted_ids' => [],
            'archived_ids' => [],
            'failed_items' => [],
            'status' => $totalCount === 0 ? 'completed' : 'processing',
            'batch_size' => $batchSize,
            'current_batch' => 0,
            'total_batches' => $totalCount === 0 ? 0 : (int) ceil($totalCount / $batchSize),
            'started_at' => $now,
            'last_updated_at' => $now,
            'completed_at' => $totalCount === 0 ? $now : null,
            'elapsed_seconds' => 0,
            'speed' => 0,
            'percent' => $totalCount === 0 ? 100 : 0,
            'remaining' => $totalCount,
            'estimated_remaining_seconds' => $totalCount === 0 ? 0 : (int) ceil($totalCount / 50),
            'message' => $totalCount === 0 ? 'No products selected.' : 'Bulk deletion job initialized.',
        ];

        Cache::put("bulk_delete_job_{$jobId}", $jobData, now()->addHours(4));
        Cache::put("active_bulk_delete_user_{$admin->id}", $jobId, now()->addHours(4));

        return $jobData;
    }

    /**
     * Get live status and telemetry of a deletion job.
     *
     * @param string $jobId
     * @return array<string, mixed>|null
     */
    public function getJobStatus(string $jobId): ?array
    {
        $jobData = Cache::get("bulk_delete_job_{$jobId}");
        if (!$jobData) {
            return null;
        }

        if ($jobData['status'] === 'processing') {
            $now = microtime(true);
            $elapsed = max(0.05, $now - ($jobData['started_at'] ?? $now));
            $jobData['elapsed_seconds'] = round($elapsed, 2);
            $speed = round($jobData['processed'] / $elapsed, 1);
            $jobData['speed'] = $speed;
            $remaining = count($jobData['pending_ids'] ?? []);
            $jobData['remaining'] = $remaining;
            $jobData['estimated_remaining_seconds'] = ($speed > 0 && $remaining > 0) ? (int) ceil($remaining / $speed) : 0;
            $jobData['percent'] = $jobData['total'] > 0 ? min(100, round(($jobData['processed'] / $jobData['total']) * 100, 1)) : 100;
        }

        return $jobData;
    }

    /**
     * Safely process the next batch of products within an isolated database transaction.
     *
     * @param string $jobId
     * @param int|null $batchSize
     * @return array<string, mixed>
     */
    public function processNextBatch(string $jobId, ?int $batchSize = null): array
    {
        $jobData = Cache::get("bulk_delete_job_{$jobId}");
        if (!$jobData) {
            return [
                'status' => 'not_found',
                'message' => 'Bulk deletion job not found or expired.',
                'percent' => 0,
                'remaining' => 0,
                'total' => 0,
                'processed' => 0,
                'deleted' => 0,
                'archived' => 0,
                'failed' => 0,
                'speed' => 0,
                'estimated_remaining_seconds' => 0,
                'failed_items' => [],
            ];
        }

        if ($jobData['status'] === 'completed' || empty($jobData['pending_ids'])) {
            $jobData['status'] = 'completed';
            $jobData['remaining'] = 0;
            $jobData['percent'] = 100;
            $jobData['estimated_remaining_seconds'] = 0;
            return $jobData;
        }

        $batchSize = $batchSize ?? ($jobData['batch_size'] ?? 75);
        $chunk = array_slice($jobData['pending_ids'], 0, $batchSize);
        $timestamp = Carbon::now();

        $chunkDeleted = [];
        $chunkArchived = [];
        $chunkFailed = [];

        // Check dependencies for this chunk across historical tables
        $saleIds = DB::table('sale_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $purchaseIds = DB::table('purchases')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $purchaseInvoiceIds = DB::table('purchase_invoice_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $salesReturnIds = DB::table('sales_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $purchaseReturnIds = DB::table('purchase_return_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $stockMovementIds = DB::table('stock_movements')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();
        $prescriptionIds = DB::table('prescription_items')->whereIn('medicine_id', $chunk)->distinct()->pluck('medicine_id')->toArray();

        $batchStockIds = DB::table('medicine_batches')
            ->whereIn('medicine_id', $chunk)
            ->where('quantity', '>', 0)
            ->distinct()
            ->pluck('medicine_id')
            ->toArray();

        $referenced = array_unique(array_merge(
            $saleIds,
            $purchaseIds,
            $purchaseInvoiceIds,
            $salesReturnIds,
            $purchaseReturnIds,
            $stockMovementIds,
            $prescriptionIds,
            $batchStockIds
        ));

        $withHistory = array_values(array_intersect($chunk, $referenced));
        $withoutHistory = array_values(array_diff($chunk, $referenced));

        try {
            DB::transaction(function () use ($withHistory, $withoutHistory, $timestamp, &$chunkDeleted, &$chunkArchived) {
                // 1. Archive items that have historical transactions to protect financial records
                if (!empty($withHistory)) {
                    Medicine::whereIn('id', $withHistory)->update([
                        'status' => 'archived',
                        'deleted_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ]);
                    $chunkArchived = $withHistory;
                }

                // 2. Permanently delete items without history
                if (!empty($withoutHistory)) {
                    MedicinePackaging::whereIn('medicine_id', $withoutHistory)->delete();
                    Inventory::whereIn('medicine_id', $withoutHistory)->delete();
                    MedicineBatch::whereIn('medicine_id', $withoutHistory)->delete();
                    Medicine::whereIn('id', $withoutHistory)->forceDelete();
                    $chunkDeleted = $withoutHistory;
                }
            });
        } catch (Throwable $e) {
            Log::warning("Batch deletion failed at set level, falling back to item-by-item processing: " . $e->getMessage());

            // Safe fallback item-by-item processing within individual transactions
            $chunkDeleted = [];
            $chunkArchived = [];
            foreach ($chunk as $singleId) {
                try {
                    $isArchival = in_array($singleId, $referenced);
                    DB::transaction(function () use ($singleId, $isArchival, $timestamp, &$chunkDeleted, &$chunkArchived) {
                        $med = Medicine::withTrashed()->find($singleId);
                        if (!$med) return;

                        if ($isArchival) {
                            $med->status = 'archived';
                            $med->deleted_at = $timestamp;
                            $med->save();
                            $chunkArchived[] = $singleId;
                        } else {
                            MedicinePackaging::where('medicine_id', $singleId)->delete();
                            Inventory::where('medicine_id', $singleId)->delete();
                            MedicineBatch::where('medicine_id', $singleId)->delete();
                            $med->forceDelete();
                            $chunkDeleted[] = $singleId;
                        }
                    });
                } catch (Throwable $itemEx) {
                    $name = Medicine::withTrashed()->where('id', $singleId)->value('name') ?? "Product #{$singleId}";
                    $chunkFailed[] = [
                        'id' => $singleId,
                        'name' => $name,
                        'error' => $itemEx->getMessage(),
                    ];
                    Log::error("Failed to delete individual medicine #{$singleId}: " . $itemEx->getMessage());
                }
            }
        }

        // Update Job Data in cache
        $jobData['pending_ids'] = array_values(array_slice($jobData['pending_ids'], count($chunk)));
        $jobData['processed_ids'] = array_merge($jobData['processed_ids'], array_diff($chunk, array_column($chunkFailed, 'id')));
        $jobData['deleted_ids'] = array_merge($jobData['deleted_ids'], $chunkDeleted);
        $jobData['archived_ids'] = array_merge($jobData['archived_ids'], $chunkArchived);
        $jobData['failed_items'] = array_merge($jobData['failed_items'], $chunkFailed);

        $jobData['processed'] += count($chunk);
        $jobData['deleted'] += count($chunkDeleted);
        $jobData['archived'] += count($chunkArchived);
        $jobData['failed'] += count($chunkFailed);
        $jobData['current_batch']++;

        $remaining = count($jobData['pending_ids']);
        $jobData['remaining'] = $remaining;

        $now = microtime(true);
        $jobData['last_updated_at'] = $now;
        $elapsed = max(0.05, $now - $jobData['started_at']);
        $jobData['elapsed_seconds'] = round($elapsed, 2);

        $speed = round($jobData['processed'] / $elapsed, 1);
        $jobData['speed'] = $speed;
        $jobData['percent'] = $jobData['total'] > 0 ? min(100, round(($jobData['processed'] / $jobData['total']) * 100, 1)) : 100;
        $jobData['estimated_remaining_seconds'] = ($speed > 0 && $remaining > 0) ? (int) ceil($remaining / $speed) : 0;

        // When all pending items are processed
        if ($remaining === 0) {
            $jobData['status'] = ($jobData['failed'] > 0) ? 'partial' : 'completed';
            $jobData['completed_at'] = $now;
            $jobData['percent'] = 100;
            $jobData['estimated_remaining_seconds'] = 0;

            $parts = [];
            if ($jobData['deleted'] > 0) {
                $parts[] = "{$jobData['deleted']} unreferenced products permanently removed";
            }
            if ($jobData['archived'] > 0) {
                $parts[] = "{$jobData['archived']} products safely archived to protect sales and accounting ledgers";
            }
            if ($jobData['failed'] > 0) {
                $parts[] = "{$jobData['failed']} products failed to process";
            }
            $jobData['message'] = empty($parts) ? 'No products affected.' : 'Bulk deletion finished: ' . implode('; ', $parts) . '.';

            // Record in ProductAuditLog
            try {
                ProductAuditLog::create([
                    'user_id' => $jobData['user_id'],
                    'user_name' => $jobData['user_name'],
                    'user_email' => $jobData['user_email'],
                    'action' => ($jobData['type'] === 'all') ? 'bulk_delete_all_products' : 'bulk_delete_selected_products',
                    'description' => $jobData['message'],
                    'ip_address' => $jobData['ip_address'],
                    'user_agent' => $jobData['user_agent'],
                    'affected_product_ids' => $jobData['processed_ids'],
                    'summary' => [
                        'total_processed' => $jobData['processed'],
                        'archived_count' => $jobData['archived'],
                        'deleted_count' => $jobData['deleted'],
                        'failed_count' => $jobData['failed'],
                        'failed_details' => $jobData['failed_items'],
                        'duration_seconds' => $jobData['elapsed_seconds'],
                        'executed_at' => Carbon::now()->toIso8601String(),
                    ],
                    'status' => $jobData['status'],
                ]);
            } catch (Throwable $e) {
                Log::error('Failed to write bulk deletion audit log: ' . $e->getMessage());
            }

            Log::notice("BULK DELETION COMPLETED: {$jobData['message']}", [
                'job_id' => $jobId,
                'user_id' => $jobData['user_id'],
                'total' => $jobData['total'],
                'deleted' => $jobData['deleted'],
                'archived' => $jobData['archived'],
                'failed' => $jobData['failed'],
                'duration' => $jobData['elapsed_seconds'],
            ]);

            Cache::forget("active_bulk_delete_user_{$jobData['user_id']}");
        }

        Cache::put("bulk_delete_job_{$jobId}", $jobData, now()->addHours(4));
        return $jobData;
    }

    /**
     * Retry failed items from a previous job. Only reprocesses failed items!
     *
     * @param string $jobId
     * @param User $admin
     * @return array<string, mixed>
     */
    public function retryFailedJob(string $jobId, User $admin): array
    {
        $jobData = Cache::get("bulk_delete_job_{$jobId}");
        if (!$jobData) {
            throw new \RuntimeException('Original bulk deletion job not found.');
        }

        $failedItems = $jobData['failed_items'] ?? [];
        if (empty($failedItems)) {
            return [
                'status' => 'completed',
                'message' => 'No failed products to retry.',
                'total' => 0,
                'processed' => 0,
                'deleted' => 0,
                'archived' => 0,
                'failed' => 0,
                'remaining' => 0,
                'percent' => 100,
            ];
        }

        // Retry ONLY the failed product IDs - never reprocess completed products!
        $failedIds = array_column($failedItems, 'id');
        return $this->initJob($admin, $failedIds, 'selected', request()->ip(), request()->userAgent(), true);
    }

    /**
     * Cancel an active deletion job. Preserves completed batches.
     *
     * @param string $jobId
     * @param User $admin
     * @return array<string, mixed>
     */
    public function cancelJob(string $jobId, User $admin): array
    {
        $jobData = Cache::get("bulk_delete_job_{$jobId}");
        if ($jobData) {
            $jobData['status'] = 'cancelled';
            $jobData['message'] = 'Bulk deletion job cancelled by user. Completed operations were preserved.';
            Cache::put("bulk_delete_job_{$jobId}", $jobData, now()->addHours(4));
            Cache::forget("active_bulk_delete_user_{$admin->id}");
        }
        return $jobData ?? ['status' => 'not_found'];
    }
}
