<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineMaster;
use App\Services\ExternalMedicineApiService;
use App\Services\MedicineMasterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MedicineSearchController extends Controller
{
    /**
     * Multi-tiered search across Operational Inventory and Master Catalog.
     *
     * Guarantee:
     * - Every result has a `source` field: 'inventory' or 'master'.
     * - Master items already promoted to `medicines` are strictly excluded to prevent duplicates.
     *
     * Query Parameters:
     * - q: search term (required)
     * - limit: max results (default 10, max 50)
     * - source: 'all' (default), 'inventory', 'master'
     * - include_external: boolean (default false)
     */
    public function search(Request $request, ExternalMedicineApiService $apiService, MedicineMasterService $masterService): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $limit = min(50, max(1, (int) $request->query('limit', 10)));
        $source = strtolower((string) $request->query('source', 'all'));
        $includeExternal = $request->boolean('include_external', false);

        if ($search === '') {
            return response()->json([]);
        }

        $results = [];

        // Preload all active operational medicine names (and brands) to guarantee no master duplicates
        $operationalNames = Medicine::query()
            ->get(['name', 'brand'])
            ->flatMap(function ($m) {
                $keys = [strtolower(trim($m->name))];
                if (!empty($m->brand)) {
                    $keys[] = strtolower(trim($m->brand));
                }
                return $keys;
            })
            ->filter()
            ->flip()
            ->all();

        $prefix = strtolower($search) . '%';

        // 1. Tier 1: Search Operational Inventory (medicines table)
        if (in_array($source, ['all', 'inventory', 'operational'], true)) {
            $local = Medicine::query()
                ->select([
                    'id',
                    'name',
                    'generic_name',
                    'brand',
                    'dosage_form',
                    'dosage_unit',
                    'base_unit',
                    'unit_price',
                    'purchase_price',
                    'manufacturer',
                    'barcode',
                ])
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                })
                ->orderByRaw(
                    "CASE 
                        WHEN LOWER(name) LIKE ? THEN 0 
                        WHEN LOWER(brand) LIKE ? THEN 1 
                        WHEN LOWER(generic_name) LIKE ? THEN 2 
                        ELSE 3 
                    END, name ASC",
                    [$prefix, $prefix, $prefix]
                )
                ->limit($limit)
                ->get();

            foreach ($local as $med) {
                $results[] = [
                    'id'               => $med->id,
                    'name'             => $med->name,
                    'generic_name'     => $med->generic_name,
                    'brand'            => $med->brand,
                    'dosage_form'      => $med->dosage_form,
                    'dosage_unit'      => $med->dosage_unit ?: $med->base_unit,
                    'selling_price'    => (float) ($med->unit_price ?? 0),
                    'purchase_price'   => (float) ($med->purchase_price ?? 0),
                    'manufacturer'     => $med->manufacturer,
                    'barcode'          => $med->barcode,
                    'source'           => 'inventory',
                    'is_inventory'     => true,
                    'is_operational'   => true,
                ];
            }
        }

        // 2. Tier 2: Search Master Catalog (medicine_master table)
        // Master items already promoted to `medicines` must NOT appear again.
        if (in_array($source, ['all', 'master'], true) && (count($results) < $limit || $source === 'master')) {
            $remaining = $limit - count($results);
            $masterItems = MedicineMaster::query()
                ->where(function ($q) use ($search) {
                    $q->where('medicine_name', 'like', "%{$search}%")
                        ->orWhere('brand_name', 'like', "%{$search}%")
                        ->orWhere('generic_name', 'like', "%{$search}%")
                        ->orWhere('manufacturer', 'like', "%{$search}%");
                })
                ->orderByRaw(
                    "CASE 
                        WHEN LOWER(medicine_name) LIKE ? THEN 0 
                        WHEN LOWER(brand_name) LIKE ? THEN 1 
                        WHEN LOWER(generic_name) LIKE ? THEN 2 
                        ELSE 3 
                    END, medicine_name ASC",
                    [$prefix, $prefix, $prefix]
                )
                ->limit($remaining * 3) // Fetch slightly more to account for filtered duplicates
                ->get();

            foreach ($masterItems as $m) {
                $nameKey = strtolower(trim($m->medicine_name));
                $brandKey = !empty($m->brand_name) ? strtolower(trim($m->brand_name)) : null;

                // Duplicate Prevention: Check if already present in operational inventory
                if (isset($operationalNames[$nameKey]) || ($brandKey && isset($operationalNames[$brandKey]))) {
                    continue;
                }

                // Add to operationalNames to avoid duplicates within master query itself
                $operationalNames[$nameKey] = true;
                if ($brandKey) {
                    $operationalNames[$brandKey] = true;
                }

                $results[] = [
                    'id'               => $m->id,
                    'master_id'        => $m->id,
                    'medicine_id'      => $m->medicine_id,
                    'name'             => $m->medicine_name,
                    'generic_name'     => $m->generic_name,
                    'brand'            => $m->brand_name,
                    'dosage_form'      => $m->dosage_form,
                    'dosage_unit'      => $m->stock_unit ?: 'Tablet',
                    'selling_price'    => null,
                    'purchase_price'   => null,
                    'manufacturer'     => $m->manufacturer,
                    'barcode'          => null,
                    'source'           => 'master',
                    'is_inventory'     => false,
                    'is_operational'   => false,
                ];

                if (count($results) >= $limit) {
                    break;
                }
            }
        }

        // 3. Tier 3: Search External API (Live Fallback with Caching)
        if (($source === 'external' || ($includeExternal && count($results) < $limit))) {
            $cacheKey = 'ext_med_search_' . md5(strtolower($search));
            try {
                $externalData = Cache::remember($cacheKey, 60, function () use ($apiService, $search) {
                    return $apiService->fetchPage(1, 10, $search);
                });

                $extItems = $externalData['data'] ?? [];
                foreach ($extItems as $item) {
                    $norm = $apiService->normalize($item);
                    $nameKey = strtolower(trim($norm['medicine_name']));

                    // Skip if already in operational inventory or master results
                    if (isset($promotedNames[$nameKey])) {
                        continue;
                    }
                    $promotedNames[$nameKey] = true;

                    $results[] = [
                        'id'               => $norm['source_reference'] ?? $norm['medicine_id'],
                        'master_id'        => null,
                        'medicine_id'      => $norm['medicine_id'],
                        'name'             => $norm['medicine_name'],
                        'generic_name'     => $norm['generic_name'],
                        'brand'            => $norm['brand_name'],
                        'dosage_form'      => $norm['dosage_form'],
                        'dosage_unit'      => $norm['stock_unit'] ?: 'Tablet',
                        'selling_price'    => null,
                        'purchase_price'   => null,
                        'manufacturer'     => $norm['manufacturer'],
                        'barcode'          => null,
                        'source'           => 'master', // Tagged as master catalog
                        'is_inventory'     => false,
                        'is_operational'   => false,
                    ];

                    if (count($results) >= $limit) {
                        break;
                    }
                }
            } catch (\Throwable $e) {
                Log::debug("External medicine search skipped: " . $e->getMessage());
            }
        }

        return response()->json($results);
    }

    /**
     * Search directly inside medicine_master catalog.
     */
    public function masterSearch(Request $request, MedicineMasterService $masterService): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $limit = min(100, max(1, (int) $request->query('limit', 20)));

        $collection = $masterService->search($search, $page, $limit);
        return response()->json([
            'page'  => $page,
            'limit' => $limit,
            'data'  => $collection,
        ]);
    }

    /**
     * Promote a master or external medicine into the operational inventory medicines table.
     * Protected by auth and role middleware (admin, pharmacist, cashier).
     */
    public function promote(Request $request, ExternalMedicineApiService $apiService): JsonResponse
    {
        $request->validate([
            'master_id'      => 'nullable|integer|exists:medicine_master,id',
            'external_id'    => 'nullable|string',
            'name'           => 'nullable|string|max:255',
            'category_id'    => 'nullable|integer|exists:categories,id',
            'unit_price'     => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
        ]);

        try {
            $masterItem = null;

            if ($request->has('master_id')) {
                $masterItem = MedicineMaster::find($request->input('master_id'));
            } elseif ($request->has('external_id')) {
                $masterItem = MedicineMaster::where('source_reference', $request->input('external_id'))
                    ->orWhere('medicine_id', $request->input('external_id'))
                    ->first();

                if (!$masterItem) {
                    $extRaw = $apiService->fetchMedicine($request->input('external_id'));
                    $masterItem = $apiService->normalize($extRaw);
                }
            } elseif ($request->has('name')) {
                $masterItem = [
                    'medicine_name' => $request->input('name'),
                    'generic_name'  => $request->input('generic_name'),
                    'brand_name'    => $request->input('brand'),
                    'dosage_form'   => $request->input('dosage_form'),
                    'manufacturer'  => $request->input('manufacturer'),
                    'category'      => $request->input('category'),
                ];
            }

            if (!$masterItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'No medicine found to promote.',
                ], 404);
            }

            $overrides = array_filter([
                'category_id'    => $request->input('category_id'),
                'unit_price'     => $request->input('unit_price'),
                'purchase_price' => $request->input('purchase_price'),
                'barcode'        => $request->input('barcode'),
            ]);

            $operationalMedicine = $apiService->promoteToOperational($masterItem, $overrides);

            return response()->json([
                'success' => true,
                'message' => 'Medicine successfully added to operational inventory.',
                'data'    => $operationalMedicine->load('category', 'baseUnit', 'packagings'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to promote medicine: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get statistics on Master Formulary and Operational Inventory counts.
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'medicine_master' => [
                'total_items' => MedicineMaster::count(),
                'verified'    => MedicineMaster::where('verification_status', 'verified')->count(),
                'pending'     => MedicineMaster::where('verification_status', 'pending')->count(),
                'rejected'    => MedicineMaster::where('verification_status', 'rejected')->count(),
            ],
            'operational_inventory' => [
                'total_medicines' => Medicine::count(),
            ],
        ]);
    }
}
