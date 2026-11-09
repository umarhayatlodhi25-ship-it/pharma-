<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\MedicineMaster;
use App\Models\MedicinePackaging;
use App\Models\Unit;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalMedicineApiService
{
    /**
     * Build the HTTP client with configured headers, timeout, and retry policy.
     */
    public function client(): PendingRequest
    {
        $headers = array_filter([
            'x-api-key' => config('services.medicine_api.api_key'),
            'Accept'    => 'application/json',
        ]);

        return Http::withHeaders($headers)
            ->baseUrl(rtrim(config('services.medicine_api.base_url', 'http://127.0.0.1:8001'), '/'))
            ->timeout((int) config('services.medicine_api.timeout', 15))
            ->retry(
                (int) config('services.medicine_api.retry_times', 3),
                (int) config('services.medicine_api.retry_sleep_ms', 1000)
            );
    }

    /**
     * Fetch a paginated chunk of medicines from the external API.
     * Expected JSON response format:
     * { "total": 80000, "page": 1, "limit": 200, "pages": 400, "data": [...] }
     *
     * @param int $page
     * @param int $limit
     * @param string $q
     * @return array
     */
    public function fetchPage(int $page = 1, int $limit = 200, string $q = ''): array
    {
        $response = $this->client()->get('/api/medicines', array_filter([
            'q'     => $q !== '' ? $q : null,
            'page'  => $page,
            'limit' => $limit,
        ]));

        $response->throw();

        $data = $response->json();

        // Standardize response payload format
        if (isset($data['data']) && is_array($data['data'])) {
            return [
                'total' => (int) ($data['total'] ?? count($data['data'])),
                'page'  => (int) ($data['page'] ?? $page),
                'pages' => (int) ($data['pages'] ?? ceil(($data['total'] ?? count($data['data'])) / max(1, $limit))),
                'limit' => (int) ($data['limit'] ?? $limit),
                'data'  => $data['data'],
            ];
        }

        // If returned as raw array of items
        if (is_array($data) && array_is_list($data)) {
            return [
                'total' => count($data),
                'page'  => $page,
                'pages' => 1,
                'limit' => $limit,
                'data'  => $data,
            ];
        }

        return [
            'total' => 0,
            'page'  => $page,
            'pages' => 0,
            'limit' => $limit,
            'data'  => [],
        ];
    }

    /**
     * Fetch details for a specific medicine by ID from the external API.
     *
     * @param int|string $id
     * @return array
     */
    public function fetchMedicine(int|string $id): array
    {
        $response = $this->client()->get("/api/medicines/{$id}");
        $response->throw();

        $json = $response->json();
        return $json['data'] ?? $json;
    }

    /**
     * Normalize external API medicine payload to match internal medicine_master schema.
     *
     * @param array $m
     * @param string $status
     * @return array
     */
    public function normalize(array $m, string $status = 'verified'): array
    {
        $now = now();
        $extId = (string) ($m['id'] ?? $m['medicine_id'] ?? $m['code'] ?? '');
        $name = trim((string) ($m['name'] ?? $m['medicine_name'] ?? $m['brand_name'] ?? ''));
        $generic = trim((string) ($m['generic_name'] ?? $m['generic'] ?? $m['salt'] ?? $m['composition'] ?? ''));
        $brand = trim((string) ($m['brand'] ?? $m['brand_name'] ?? $name));
        $strength = trim((string) ($m['strength'] ?? $m['potency'] ?? ''));
        $form = trim((string) ($m['form'] ?? $m['dosage_form'] ?? $m['dosage_unit'] ?? ''));
        $category = trim((string) ($m['category'] ?? $m['category_name'] ?? ''));
        $company = trim((string) ($m['company'] ?? $m['manufacturer'] ?? $m['mfg'] ?? ''));
        $regNum = trim((string) ($m['registration_number'] ?? $m['reg_no'] ?? ''));
        $packSize = trim((string) ($m['pack_size'] ?? $m['pack'] ?? ''));
        $stockUnit = trim((string) ($m['stock_unit'] ?? $m['unit'] ?? ''));

        // Generate clean unique medicine_id if none provided
        $medId = !empty($extId)
            ? (str_starts_with($extId, 'ext_') ? $extId : 'ext_' . $extId)
            : 'ext_' . substr(md5(strtolower($name . '|' . $generic . '|' . $strength)), 0, 16);

        return [
            'medicine_id'         => $medId,
            'medicine_name'       => $name,
            'brand_name'          => !empty($brand) ? $brand : null,
            'generic_name'        => !empty($generic) ? $generic : null,
            'composition'         => !empty($generic) ? $generic : null,
            'strength'            => !empty($strength) ? $strength : null,
            'dosage_form'         => !empty($form) ? $form : null,
            'category'            => !empty($category) ? $category : null,
            'manufacturer'        => !empty($company) ? $company : null,
            'registration_number' => !empty($regNum) ? $regNum : null,
            'pack_size'           => !empty($packSize) ? $packSize : null,
            'stock_unit'          => !empty($stockUnit) ? $stockUnit : null,
            'source'              => 'External_API',
            'source_reference'    => !empty($extId) ? $extId : $medId,
            'verification_status' => $status,
            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    /**
     * Fast batch upsert into medicine_master table.
     *
     * @param array $rawItems
     * @param string $status
     * @return int
     */
    public function upsertChunk(array $rawItems, string $status = 'verified'): int
    {
        if (empty($rawItems)) {
            return 0;
        }

        $normalized = [];
        foreach ($rawItems as $raw) {
            if (!is_array($raw)) {
                continue;
            }
            $record = $this->normalize($raw, $status);
            if (!empty($record['medicine_name'])) {
                $normalized[] = $record;
            }
        }

        if (empty($normalized)) {
            return 0;
        }

        // Chunk into groups of 100 to stay well within SQLite host variable limits (999 or 32766)
        $totalUpserted = 0;
        foreach (array_chunk($normalized, 100) as $chunk) {
            DB::table('medicine_master')->upsert(
                $chunk,
                ['medicine_id'],
                [
                    'medicine_name',
                    'brand_name',
                    'generic_name',
                    'composition',
                    'strength',
                    'dosage_form',
                    'category',
                    'manufacturer',
                    'registration_number',
                    'pack_size',
                    'stock_unit',
                    'verification_status',
                    'updated_at',
                ]
            );
            $totalUpserted += count($chunk);
        }

        return $totalUpserted;
    }

    /**
     * Promote / Clone a master or external medicine into the operational `medicines` table
     * with base unit packaging and inventory record so it can be sold at POS or purchased.
     *
     * @param array|MedicineMaster $source
     * @param array $overrides
     * @return Medicine
     */
    public function promoteToOperational(array|MedicineMaster $source, array $overrides = []): Medicine
    {
        if ($source instanceof MedicineMaster) {
            $data = $source->toArray();
        } else {
            $data = is_array($source) ? $source : [];
        }

        $name = trim((string) ($overrides['name'] ?? $data['medicine_name'] ?? $data['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Medicine name is required for operational promotion.');
        }

        $existing = Medicine::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
        if ($existing) {
            return $existing;
        }

        $dosageForm = $overrides['dosage_form'] ?? $data['dosage_form'] ?? $data['form'] ?? 'Tablet';
        $categoryName = $overrides['category'] ?? $data['category'] ?? null;
        $categoryId = $overrides['category_id'] ?? $this->resolveCategory($categoryName, $dosageForm);
        $baseUnit = $this->resolveBaseUnit($dosageForm, $data['stock_unit'] ?? null);

        $unitPrice = isset($overrides['unit_price']) ? (float) $overrides['unit_price'] : (float) ($data['unit_price'] ?? $data['price'] ?? 0);
        $purchasePrice = isset($overrides['purchase_price']) ? (float) $overrides['purchase_price'] : (float) ($data['purchase_price'] ?? 0);

        return DB::transaction(function () use ($data, $name, $dosageForm, $categoryId, $baseUnit, $unitPrice, $purchasePrice, $overrides) {
            $med = Medicine::create([
                'category_id'                 => $categoryId,
                'product_type'                => 'medicine',
                'name'                        => $name,
                'generic_name'                => $overrides['generic_name'] ?? $data['generic_name'] ?? null,
                'brand'                       => $overrides['brand'] ?? $data['brand_name'] ?? $data['brand'] ?? $name,
                'strength'                    => $overrides['strength'] ?? $data['strength'] ?? null,
                'dosage_form'                 => $dosageForm,
                'dosage_unit'                 => $baseUnit->name,
                'base_unit_id'                => $baseUnit->id,
                'base_unit'                   => $baseUnit->name,
                'primary_unit'                => null,
                'secondary_unit'              => null,
                'primary_unit_to_secondary'   => 1,
                'secondary_unit_to_base'      => 1,
                'unit_price'                  => $unitPrice > 0 ? $unitPrice : null,
                'purchase_price'              => $purchasePrice > 0 ? $purchasePrice : null,
                'base_unit_selling_price'     => $unitPrice > 0 ? $unitPrice : null,
                'manufacturer'                => $overrides['manufacturer'] ?? $data['manufacturer'] ?? $data['company'] ?? null,
                'barcode'                     => $overrides['barcode'] ?? $data['barcode'] ?? null,
                'alert_quantity'              => (int) ($overrides['alert_quantity'] ?? 10),
                'reorder_level'               => (int) ($overrides['reorder_level'] ?? 10),
                'tax_rate'                    => (float) ($overrides['tax_rate'] ?? 0),
                'status'                      => 'active',
                'has_expiry'                  => true,
                'track_batches'               => true,
            ]);

            // Create Base Packaging level
            MedicinePackaging::updateOrCreate(
                [
                    'medicine_id' => $med->id,
                    'unit_id'     => $baseUnit->id,
                ],
                [
                    'conversion_to_base' => 1.0,
                    'quantity_in_parent' => 1.0,
                    'parent_packaging_id'=> null,
                    'display_name'       => $baseUnit->name,
                    'barcode'            => $med->barcode,
                    'purchase_price'     => $purchasePrice > 0 ? $purchasePrice : null,
                    'sale_price'         => $unitPrice > 0 ? $unitPrice : null,
                    'allow_purchase'     => true,
                    'allow_sale'         => true,
                    'status'             => 'active',
                ]
            );

            // Initialize 0 Stock Inventory Ledger
            Inventory::firstOrCreate(
                ['medicine_id' => $med->id],
                [
                    'total_base_quantity'     => 0,
                    'reserved_base_quantity'  => 0,
                    'available_base_quantity' => 0,
                ]
            );

            return $med;
        });
    }

    /**
     * Automatically resolve category ID based on external category name or dosage form.
     */
    public function resolveCategory(?string $categoryName, ?string $dosageForm = null): int
    {
        $text = strtolower(trim(($categoryName ?? '') . ' ' . ($dosageForm ?? '')));

        $name = 'Tablets & Capsules';
        $slug = 'tablets-capsules';

        if (str_contains($text, 'syrup') || str_contains($text, 'suspension') || str_contains($text, 'liquid') || str_contains($text, 'solution')) {
            $name = 'Syrups & Suspensions';
            $slug = 'syrups-suspensions';
        } elseif (str_contains($text, 'inject') || str_contains($text, 'infusion') || str_contains($text, 'iv') || str_contains($text, 'ampoule') || str_contains($text, 'vial')) {
            $name = 'Injections & Infusions';
            $slug = 'injections-infusions';
        } elseif (str_contains($text, 'ointment') || str_contains($text, 'cream') || str_contains($text, 'gel') || str_contains($text, 'lotion')) {
            $name = 'Ointments, Creams & Gels';
            $slug = 'ointments-creams-gels';
        } elseif (str_contains($text, 'antibiotic') || str_contains($text, 'antiviral') || str_contains($text, 'anti-infective')) {
            $name = 'Antibiotics & Antivirals';
            $slug = 'antibiotics-antivirals';
        } elseif (str_contains($text, 'pain') || str_contains($text, 'analgesic') || str_contains($text, 'nsaid') || str_contains($text, 'fever')) {
            $name = 'Pain Relief & Analgesics';
            $slug = 'pain-relief-analgesics';
        } elseif (str_contains($text, 'cardio') || str_contains($text, 'blood pressure') || str_contains($text, 'hypertension') || str_contains($text, 'heart')) {
            $name = 'Cardiovascular & Blood Pressure';
            $slug = 'cardiovascular-blood-pressure';
        } elseif (str_contains($text, 'gastro') || str_contains($text, 'antacid') || str_contains($text, 'ulcer') || str_contains($text, 'stomach')) {
            $name = 'Gastrointestinal & Antacids';
            $slug = 'gastrointestinal-antacids';
        } elseif (str_contains($text, 'respiratory') || str_contains($text, 'allergy') || str_contains($text, 'cough') || str_contains($text, 'asthma')) {
            $name = 'Respiratory & Anti-Allergy';
            $slug = 'respiratory-anti-allergy';
        } elseif (str_contains($text, 'diabet') || str_contains($text, 'insulin') || str_contains($text, 'sugar')) {
            $name = 'Diabetes Care & Insulin';
            $slug = 'diabetes-care-insulin';
        } elseif (str_contains($text, 'eye') || str_contains($text, 'ear') || str_contains($text, 'drop')) {
            $name = 'Eye & Ear Drops';
            $slug = 'eye-ear-drops';
        } elseif (str_contains($text, 'vitamin') || str_contains($text, 'mineral') || str_contains($text, 'supplement')) {
            $name = 'Vitamins, Minerals & Supplements';
            $slug = 'vitamins-minerals-supplements';
        } elseif (!empty($categoryName)) {
            $name = trim($categoryName);
            $slug = Str::slug($name);
        }

        // 1. Check if category exists by name OR by slug
        $cat = Category::where('name', $name)
            ->orWhere('slug', $slug)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($cat) {
            return (int) $cat->id;
        }

        // 2. Ensure unique slug if generating new
        $uniqueSlug = $slug;
        $counter = 1;
        while (Category::where('slug', $uniqueSlug)->exists()) {
            $uniqueSlug = $slug . '-' . $counter++;
        }

        $cat = Category::create([
            'name'         => $name,
            'slug'         => $uniqueSlug,
            'product_type' => 'medicine',
        ]);

        return (int) $cat->id;
    }

    /**
     * Automatically resolve base unit based on dosage form or external unit.
     */
    public function resolveBaseUnit(?string $dosageForm, ?string $stockUnit = null): Unit
    {
        $raw = strtolower(trim(($dosageForm ?? '') . ' ' . ($stockUnit ?? '')));

        $unitId = 'tablet';
        $name = 'Tablet';
        $symbol = 'Tab';

        if (str_contains($raw, 'syrup') || str_contains($raw, 'suspension') || str_contains($raw, 'bottle')) {
            $unitId = 'bottle';
            $name = 'Bottle';
            $symbol = 'Btl';
        } elseif (str_contains($raw, 'ampoule')) {
            $unitId = 'ampoule';
            $name = 'Ampoule';
            $symbol = 'Amp';
        } elseif (str_contains($raw, 'vial') || str_contains($raw, 'inject')) {
            $unitId = 'vial';
            $name = 'Vial';
            $symbol = 'Vil';
        } elseif (str_contains($raw, 'capsule') || str_contains($raw, 'cap')) {
            $unitId = 'capsule';
            $name = 'Capsule';
            $symbol = 'Cap';
        } elseif (str_contains($raw, 'tube') || str_contains($raw, 'ointment') || str_contains($raw, 'cream')) {
            $unitId = 'tube';
            $name = 'Tube';
            $symbol = 'Tub';
        } elseif (str_contains($raw, 'sachet')) {
            $unitId = 'sachet';
            $name = 'Sachet';
            $symbol = 'Sch';
        } elseif (str_contains($raw, 'drop')) {
            $unitId = 'drop';
            $name = 'Drop';
            $symbol = 'Drp';
        }

        $unit = Unit::where('unit_id', $unitId)
            ->orWhereRaw('LOWER(name) = ?', [strtolower($name)])
            ->first();

        if ($unit) {
            return $unit;
        }

        return Unit::create([
            'unit_id' => $unitId,
            'name'    => $name,
            'symbol'  => $symbol,
        ]);
    }
}
