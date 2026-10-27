<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\PackagingService;
use App\Services\StockLedgerService;
use App\Services\ProductBulkDeletionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;

class AddMedicine extends Component
{
    use WithPagination;

    // Form Add properties
    public string $product_type = 'medicine';
    public string $name = '';
    public string $generic_name = '';
    public string $brand = '';
    public string $strength = '';
    public string $dosage_form = '';
    public string $manufacturer = '';
    public string $barcode = '';
    public string $sku = '';
    public string $category_id = '';
    public string $category_search = '';
    public string $supplier_id = '';
    public string $batch_number = '';
    public string $quantity = '';
    public string $initial_stock_unit = 'base';
    public string $purchase_price = '';
    public string $selling_price = '';
    public string $expiry_date = '';
    public string $alert_quantity = '10';
    public string $reorder_level = '10';
    public string $tax_rate = '0';

    public bool $has_expiry = true;
    public bool $track_batches = true;

    // Unit Hierarchy Properties
    public string $primary_unit = '';
    public string $secondary_unit = '';
    public string $base_unit = 'Tablet';
    public string $dosage_unit = '';
    public string $primary_unit_to_secondary = '10';
    public string $secondary_unit_to_base = '10';

    public string $primary_unit_purchase_price = '';
    public string $secondary_unit_purchase_price = '';
    public string $base_unit_purchase_price = '';

    public string $primary_unit_selling_price = '';
    public string $secondary_unit_selling_price = '';
    public string $base_unit_selling_price = '';

    public string $primary_unit_barcode = '';
    public string $secondary_unit_barcode = '';
    public string $base_unit_barcode = '';

    // Data Table Filters & State
    public string $search = '';
    public string $productTypeFilter = 'all';
    public string $categoryFilter = '';
    public string $supplierFilter = '';
    public string $stockFilter = '';
    public string $expiryFilter = '';
    public int $perPage = 15;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Selection & Bulk actions
    public array $selectedMedicines = [];
    public bool $selectAll = false;
    public array $expandedRows = [];

    // Quick View Modal
    public bool $showViewModal = false;
    public ?int $viewMedicineId = null;

    // Edit Modal
    public bool $showEditModal = false;
    public ?int $editMedicineId = null;
    public string $edit_name = '';
    public string $edit_generic_name = '';
    public string $edit_brand = '';
    public string $edit_strength = '';
    public string $edit_dosage_form = '';
    public string $edit_category_id = '';
    public string $edit_manufacturer = '';
    public string $edit_barcode = '';
    public string $edit_sku = '';
    public string $edit_alert_quantity = '10';
    public string $edit_reorder_level = '10';
    public string $edit_tax_rate = '0';
    public string $edit_status = 'active';
    public string $edit_product_type = 'medicine';
    public string $edit_primary_unit = '';
    public string $edit_secondary_unit = '';
    public string $edit_base_unit = '';
    public string $edit_primary_unit_to_secondary = '10';
    public string $edit_secondary_unit_to_base = '10';
    public string $edit_unit_price = '';
    public string $edit_purchase_price = '';
    public string $edit_primary_unit_selling_price = '';
    public string $edit_secondary_unit_selling_price = '';
    public string $edit_base_unit_selling_price = '';

    // Add Batch / Restock Modal
    public bool $showAddBatchModal = false;
    public ?int $batchMedicineId = null;
    public string $batchMedicineName = '';
    public string $new_batch_number = '';
    public string $new_batch_supplier_id = '';
    public string $new_batch_quantity = '';
    public string $new_batch_unit = 'base';
    public string $new_batch_purchase_price = '';
    public string $new_batch_selling_price = '';
    public string $new_batch_expiry_date = '';

    // Stock Adjustment Modal
    public bool $showAdjustStockModal = false;
    public ?int $adjustBatchId = null;
    public ?int $adjustMedicineId = null;
    public string $adjustBatchNumber = '';
    public string $adjustMedicineName = '';
    public float $adjustCurrentQty = 0;
    public string $adjustType = 'ADJUSTMENT_IN';
    public string $adjustQuantity = '';
    public string $adjustNotes = '';

    // Delete Modals
    public bool $showDeleteModal = false;
    public ?int $deleteMedicineId = null;
    public string $deleteMedicineName = '';
    public int $deleteMedicineStock = 0;
    public int $deleteMedicineBatchesCount = 0;
    public bool $deleteHasSales = false;
    public bool $showBulkDeleteModal = false;

    // Delete All Products Modal (Admin Only)
    public bool $showDeleteAllModal = false;
    public string $deleteAllConfirmationInput = '';
    public array $deleteAllPreview = [];

    // Barcode Modal
    public bool $showBarcodeModal = false;
    public ?int $barcodeMedicineId = null;
    public int $barcodeCopies = 1;
    public bool $barcodeShowPrice = true;
    public bool $barcodeShowExpiry = true;
    public bool $barcodeShowGeneric = false;

    // Mount hook
    public function mount(): void
    {
        $this->batch_number = 'BAT-' . strtoupper(Str::random(6));
    }

    // Reset pagination when filters change
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingProductTypeFilter(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }

    public string $product_search = '';
    public bool $showProductSuggestions = false;
    public bool $verified_product_match = false;
    public ?int $verified_medicine_id = null;
    public bool $showNewProductConfirmation = false;
    public ?array $ai_suggestion = null;

    public function updatedName(): void { $this->verified_product_match = false; $this->verified_medicine_id = null; }
    public function updatedStrength(): void { $this->verified_product_match = false; $this->verified_medicine_id = null; }
    public function updatedDosageForm(string $form): void
    {
        $this->verified_product_match = false;
        $this->verified_medicine_id = null;
    }

    public function updatedProductSearch($value): void
    {
        $value = trim((string)$value);
        $this->name = $value; // keep name in sync with free text
        $this->verified_product_match = false;
        $this->verified_medicine_id = null;
        
        if (empty($value) || strlen($value) < 2) {
            $this->showProductSuggestions = false;
            return;
        }

        $this->showProductSuggestions = true;
    }

    #[Computed]
    public function suggestedProducts()
    {
        $q = trim((string)$this->product_search);
        if (empty($q) || strlen($q) < 2) {
            return collect();
        }

        $terms = array_filter(explode(' ', strtolower($q)));

        $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $results = \App\Models\Medicine::where(function($query) use ($q, $terms, $like) {
            $query->where('barcode', '=', $q)
                  ->orWhere('name', $like, "%{$q}%")
                  ->orWhere('generic_name', $like, "%{$q}%")
                  ->orWhere('brand', $like, "%{$q}%")
                  ->orWhere(function($subq) use ($terms, $like) {
                      foreach ($terms as $term) {
                          $subq->where(function($q2) use ($term, $like) {
                              $q2->where('name', $like, "%{$term}%")
                                 ->orWhere('strength', $like, "%{$term}%")
                                 ->orWhere('dosage_form', $like, "%{$term}%");
                          });
                      }
                  });
        })
        ->select('id', 'name', 'dosage_unit', 'dosage_form', 'strength', 'manufacturer', 'brand', 'barcode')
        ->limit(10)
        ->get();

        if ($results->count() > 0) {
            $this->ai_suggestion = null;
            return $results;
        }

        return collect();
    }
    
    public function askAiForProduct(): void
    {
        $q = trim((string)$this->product_search);
        if (empty($q) || strlen($q) < 2) {
            return;
        }

        $aiService = app(\App\Services\AiNormalizationService::class);
        $normalized = $aiService->normalizeMedicineSearch($q, $this->product_type);

        if ($normalized && isset($normalized['confidence']) && $normalized['confidence'] >= 60) {
            $this->ai_suggestion = $normalized;
            $this->applyAiSuggestion();
            session()->flash('ai_success', 'Product details auto-filled by AI!');
        } else {
            session()->flash('ai_error', 'AI could not find this product or API limit reached. Please enter details manually.');
        }
    }

    public function enableManualEntry(): void
    {
        $this->showProductSuggestions = false;
        $this->ai_suggestion = null;
    }

    public function applyAiSuggestion(): void
    {
        if (!$this->ai_suggestion) return;

        $this->name = $this->ai_suggestion['normalized_name'] ?? $this->name;
        $this->generic_name = $this->ai_suggestion['generic_name'] ?? $this->generic_name;
        $this->strength = $this->ai_suggestion['strength'] ?? $this->strength;
        
        if (!empty($this->ai_suggestion['dosage_form'])) {
            $this->dosage_form = $this->ai_suggestion['dosage_form'];
            $this->dosage_unit = $this->ai_suggestion['dosage_form'];
        }
        
        $this->brand = $this->ai_suggestion['brand'] ?? $this->brand;
        $this->manufacturer = $this->ai_suggestion['manufacturer'] ?? $this->manufacturer;
        
        if (!empty($this->ai_suggestion['category_name'])) {
            $catName = $this->ai_suggestion['category_name'];
            $category = \App\Models\Category::where('name', 'ilike', "%{$catName}%")->first();
            if ($category) {
                $this->selectCategory($category->id, $category->name);
            }
        }
        
        // Clear suggestion
        $this->ai_suggestion = null;
        $this->showProductSuggestions = false;
        
        // Explicitly unverified
        $this->verified_product_match = false;
        $this->verified_medicine_id = null;
    }

    public function selectProduct(int $id, string $name): void
    {
        $this->verified_product_match = true;
        $this->verified_medicine_id = $id;
        $this->name = $name;
        $this->product_search = $name;
        $this->showProductSuggestions = false;

        $existing = \App\Models\Medicine::find($id);

        if ($existing) {
            $this->category_id = (string) $existing->category_id;
            
            $cat = \App\Models\Category::find($existing->category_id);
            $this->category_search = $cat ? $cat->name : '';

            // Basic Info
            $this->brand = $existing->brand ?? '';
            $this->generic_name = $existing->generic_name ?? '';
            $this->manufacturer = $existing->manufacturer ?? '';
            $this->strength = $existing->strength ?? '';
            $this->dosage_form = $existing->dosage_form ?? '';
            $this->barcode = $existing->barcode ?? '';
            $this->sku = $existing->sku ?? '';
            
            // Preferences
            $this->alert_quantity = (string) ($existing->alert_quantity ?? 10);
            $this->reorder_level = (string) ($existing->reorder_level ?? 10);
            $this->tax_rate = (string) ($existing->tax_rate ?? 0);
            $this->has_expiry = (bool) $existing->has_expiry;
            $this->track_batches = (bool) $existing->track_batches;

            // Packaging & Units
            $this->base_unit = $existing->base_unit ?? 'Tablet';
            $this->primary_unit = $existing->primary_unit ?? '';
            $this->secondary_unit = $existing->secondary_unit ?? '';
            $this->dosage_unit = $existing->dosage_unit ?? '';
            $this->primary_unit_to_secondary = (string) ($existing->primary_unit_to_secondary ?? 10);
            $this->secondary_unit_to_base = (string) ($existing->secondary_unit_to_base ?? 10);

            // Base Prices
            $this->purchase_price = (string) ($existing->purchase_price ?? 0);
            $this->selling_price = (string) ($existing->unit_price ?? 0);

            // Hierarchy Prices
            $this->base_unit_purchase_price = (string) ($existing->purchase_price ?? '');
            $this->base_unit_selling_price = (string) ($existing->unit_price ?? '');
            
            if (!empty($existing->primary_unit_selling_price)) {
                $this->primary_unit_selling_price = (string) $existing->primary_unit_selling_price;
            }
            if (!empty($existing->secondary_unit_selling_price)) {
                $this->secondary_unit_selling_price = (string) $existing->secondary_unit_selling_price;
            }
            
            // Latest Batch details for continuity
            $latestBatch = \App\Models\MedicineBatch::where('medicine_id', $existing->id)->latest()->first();
            if ($latestBatch) {
                // We keep the old batch number string logic so the user can easily overwrite it, 
                // but we also pull supplier
                $this->supplier_id = (string) $latestBatch->supplier_id;
            }
        }
    }

    public function updatingSupplierFilter(): void { $this->resetPage(); }
    public function updatingStockFilter(): void { $this->resetPage(); }
    public function updatingExpiryFilter(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function updatedCategoryId($val): void
    {
        if (!empty($val)) {
            $cat = Category::find($val);
            $this->category_search = $cat?->name ?? '';
        } else {
            $this->category_search = '';
        }
    }

    public function selectCategory(int $categoryId, ?string $name = null): void
    {
        $this->category_id = (string) $categoryId;
        if ($name) {
            $this->category_search = $name;
        } else {
            $cat = Category::find($categoryId);
            $this->category_search = $cat?->name ?? '';
        }
        $this->resetValidation('category_id');
    }

    public function clearCategory(): void
    {
        $this->category_id = '';
        $this->category_search = '';
    }

    public function quickCreateCategory(string $name): void
    {
        $name = trim($name);
        if (empty($name)) {
            return;
        }

        $slug = Str::slug($name);
        if (empty($slug)) {
            $slug = 'cat-' . time();
        }

        $cat = Category::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'product_type' => $this->product_type,
            ]
        );

        $this->category_id = (string) $cat->id;
        $this->category_search = $cat->name;
        $this->resetValidation('category_id');
    }

    // Removed updatedDosageForm to prevent bugs

    public function updatedInitialStockUnit(string $unit): void
    {
        if ($unit !== 'base' && $unit !== 'secondary' && $unit !== 'primary') {
            $this->base_unit = $unit;
            $this->initial_stock_unit = 'base';
        }
    }

    public function updatedProductType(string $type): void
    {
        $this->category_id = '';
        $this->category_search = '';
        $this->barcode = '';
        if ($type === 'general') {
            $this->primary_unit = '';
            $this->secondary_unit = '';
            $this->base_unit = 'Piece';
            $this->primary_unit_to_secondary = '1';
            $this->secondary_unit_to_base = '1';
            $this->has_expiry = false;
            $this->track_batches = false;
            $this->dosage_unit = '';
            $this->generic_name = '';
            $this->manufacturer = '';
        } else {
            $this->primary_unit = '';
            $this->secondary_unit = '';
            $this->base_unit = 'Tablet';
            $this->primary_unit_to_secondary = '10';
            $this->secondary_unit_to_base = '10';
            $this->has_expiry = true;
            $this->track_batches = true;
        }
    }

    public function setProductType(string $type): void
    {
        $this->product_type = $type;
        $this->updatedProductType($type);
    }

    public function getCalculatedSecondaryConversionProperty(): int
    {
        return max(1, (int) ($this->secondary_unit_to_base ?: 1));
    }

    public function getCalculatedPrimaryConversionProperty(): int
    {
        $p2s = max(1, (int) ($this->primary_unit_to_secondary ?: 1));
        $s2b = max(1, (int) ($this->secondary_unit_to_base ?: 1));
        return $p2s * $s2b;
    }

    public function confirmSaveNewProduct(): void
    {
        $this->showNewProductConfirmation = false;
        $this->save(true);
    }

    public function cancelSaveNewProduct(): void
    {
        $this->showNewProductConfirmation = false;
    }

    public function save($skipConfirmation = false): void
    {
        $rules = [
            'product_type' => 'required|in:medicine,general',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'brand' => 'nullable|string|max:255',
            'strength' => 'nullable|string|max:100',
            'dosage_form' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:255',
            'alert_quantity' => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'tax_rate' => 'nullable|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'primary_unit' => 'nullable|string|max:50',
            'secondary_unit' => 'nullable|string|max:50',
            'base_unit' => 'required|string|max:50',
            'primary_unit_to_secondary' => 'nullable|integer|min:1',
            'secondary_unit_to_base' => 'nullable|integer|min:1',
            'primary_unit_purchase_price' => 'nullable|numeric|min:0',
            'secondary_unit_purchase_price' => 'nullable|numeric|min:0',
            'base_unit_purchase_price' => 'nullable|numeric|min:0',
            'primary_unit_selling_price' => 'nullable|numeric|min:0',
            'secondary_unit_selling_price' => 'nullable|numeric|min:0',
            'base_unit_selling_price' => 'nullable|numeric|min:0',
            'primary_unit_barcode' => 'nullable|string|max:255',
            'secondary_unit_barcode' => 'nullable|string|max:255',
            'base_unit_barcode' => 'nullable|string|max:255',
        ];

        if ($this->product_type === 'medicine') {
            $rules['generic_name'] = 'nullable|string|max:255';
            $rules['manufacturer'] = 'nullable|string|max:255';
            $rules['expiry_date'] = 'required|date';
            $rules['batch_number'] = 'required|string|max:255';
        } else {
            $rules['expiry_date'] = $this->has_expiry ? 'required|date' : 'nullable|date';
            $rules['batch_number'] = 'nullable|string|max:255';
        }

        $this->validate($rules);

        // Safety Gate: Do not auto-fill/save a new product without confirmation if not verified.
        if (!$this->verified_product_match && !$skipConfirmation) {
            $exists = Medicine::where('name', trim($this->name))->first();
            if ($exists) {
                session()->flash('error', "SAFETY WARNING: A product named '{$this->name}' already exists. Please select it from the dropdown to prevent duplicates, or use a different name.");
                return;
            }
            $this->showNewProductConfirmation = true;
            return;
        }

            $baseUnitSlug = Str::slug($this->base_unit);
            $baseUnit = Unit::firstOrCreate(
                ['unit_id' => $baseUnitSlug],
                [
                    'name' => trim($this->base_unit),
                    'symbol' => substr(trim($this->base_unit), 0, 4),
                    'allow_decimal' => in_array($baseUnitSlug, ['ml', 'liter', 'gram', 'kg']),
                    'status' => 'active',
                ]
            );

            $basePrice = $this->base_unit_selling_price !== '' ? (float)$this->base_unit_selling_price : (float)$this->selling_price;
            $basePurchasePrice = $this->base_unit_purchase_price !== '' ? (float)$this->base_unit_purchase_price : (float)$this->purchase_price;

            $batchNum = trim($this->batch_number);
            if (empty($batchNum)) {
                $batchNum = ($this->product_type === 'general' ? 'GEN-' : 'BAT-') . strtoupper(Str::random(6));
            }

            $primaryToSecondary = max(1, (int) ($this->primary_unit_to_secondary ?: 1));
            $secondaryToBase = $this->product_type === 'medicine' ? max(1, (int) ($this->secondary_unit_to_base ?: 1)) : 1;

            $medicineData = [
                'category_id' => $this->category_id,
                'product_type' => $this->product_type,
                'generic_name' => $this->product_type === 'medicine' ? ($this->generic_name ?: null) : null,
                'brand' => $this->brand ?: null,
                'strength' => $this->strength ?: null,
                'dosage_form' => $this->dosage_form ?: null,
                'dosage_unit' => $this->product_type === 'medicine' ? ($this->dosage_unit ?: $this->base_unit) : $this->base_unit,
                'base_unit_id' => $baseUnit->id,
                'manufacturer' => $this->product_type === 'medicine' ? ($this->manufacturer ?: null) : null,
                'barcode' => $this->barcode ?: ($this->base_unit_barcode ?: null),
                'sku' => $this->sku ?: null,
                'alert_quantity' => (int) ($this->alert_quantity ?: 10),
                'reorder_level' => (int) ($this->reorder_level ?: ($this->alert_quantity ?: 10)),
                'tax_rate' => (float) ($this->tax_rate ?: 0),
                'has_expiry' => $this->has_expiry,
                'track_batches' => $this->track_batches,
                'primary_unit' => $this->primary_unit ?: null,
                'secondary_unit' => $this->product_type === 'medicine' ? ($this->secondary_unit ?: null) : null,
                'base_unit' => $this->base_unit,
                'primary_unit_to_secondary' => $primaryToSecondary,
                'secondary_unit_to_base' => $secondaryToBase,
                'unit_price' => $basePrice,
                'purchase_price' => $basePurchasePrice,
                'primary_unit_selling_price' => $this->primary_unit_selling_price !== '' ? (float)$this->primary_unit_selling_price : null,
                'secondary_unit_selling_price' => ($this->product_type === 'medicine' && $this->secondary_unit_selling_price !== '') ? (float)$this->secondary_unit_selling_price : null,
                'base_unit_selling_price' => $basePrice,
                'status' => 'active',
            ];

        try {
            DB::transaction(function () use ($medicineData, $baseUnit, $basePrice, $basePurchasePrice, $batchNum, $primaryToSecondary, $secondaryToBase) {
                $medicine = Medicine::firstOrCreate(
                    ['name' => trim($this->name)],
                    $medicineData
                );

                $medicine->update($medicineData);

                // Base Unit Packaging
                $basePkg = MedicinePackaging::updateOrCreate(
                    [
                        'medicine_id' => $medicine->id,
                        'unit_id' => $baseUnit->id,
                    ],
                    [
                        'conversion_to_base' => 1.0,
                        'quantity_in_parent' => 1.0,
                        'parent_packaging_id' => null,
                        'display_name' => $this->base_unit,
                        'barcode' => $this->base_unit_barcode ?: $this->barcode ?: null,
                        'purchase_price' => $basePurchasePrice,
                        'sale_price' => $basePrice,
                        'allow_purchase' => true,
                        'allow_sale' => true,
                        'status' => 'active',
                    ]
                );

                $secondaryPkg = null;
                if (!empty($this->secondary_unit) && $this->product_type === 'medicine') {
                    $secUnitSlug = Str::slug($this->secondary_unit);
                    $secUnit = Unit::firstOrCreate(
                        ['unit_id' => $secUnitSlug],
                        ['name' => trim($this->secondary_unit), 'symbol' => substr(trim($this->secondary_unit), 0, 4), 'allow_decimal' => false, 'status' => 'active']
                    );

                    $secConversion = (float) $secondaryToBase;
                    $secSalePrice = $this->secondary_unit_selling_price !== '' ? (float)$this->secondary_unit_selling_price : round($basePrice * $secConversion, 2);
                    $secPurchasePrice = $this->secondary_unit_purchase_price !== '' ? (float)$this->secondary_unit_purchase_price : round($basePurchasePrice * $secConversion, 2);

                    $secondaryPkg = MedicinePackaging::updateOrCreate(
                        [
                            'medicine_id' => $medicine->id,
                            'unit_id' => $secUnit->id,
                        ],
                        [
                            'conversion_to_base' => $secConversion,
                            'quantity_in_parent' => (float)$secondaryToBase,
                            'parent_packaging_id' => $basePkg->id,
                            'display_name' => "{$this->secondary_unit} ({$secondaryToBase} {$this->base_unit}s)",
                            'barcode' => $this->secondary_unit_barcode ?: null,
                            'purchase_price' => $secPurchasePrice,
                            'sale_price' => $secSalePrice,
                            'allow_purchase' => true,
                            'allow_sale' => true,
                            'status' => 'active',
                        ]
                    );
                }

                if (!empty($this->primary_unit)) {
                    $primUnitSlug = Str::slug($this->primary_unit);
                    $primUnit = Unit::firstOrCreate(
                        ['unit_id' => $primUnitSlug],
                        ['name' => trim($this->primary_unit), 'symbol' => substr(trim($this->primary_unit), 0, 4), 'allow_decimal' => false, 'status' => 'active']
                    );

                    $primConversion = (float) ($primaryToSecondary * $secondaryToBase);
                    $primSalePrice = $this->primary_unit_selling_price !== '' ? (float)$this->primary_unit_selling_price : round($basePrice * $primConversion, 2);
                    $primPurchasePrice = $this->primary_unit_purchase_price !== '' ? (float)$this->primary_unit_purchase_price : round($basePurchasePrice * $primConversion, 2);

                    MedicinePackaging::updateOrCreate(
                        [
                            'medicine_id' => $medicine->id,
                            'unit_id' => $primUnit->id,
                        ],
                        [
                            'conversion_to_base' => $primConversion,
                            'quantity_in_parent' => (float)$primaryToSecondary,
                            'parent_packaging_id' => $secondaryPkg ? $secondaryPkg->id : $basePkg->id,
                            'display_name' => "{$this->primary_unit} ({$primConversion} {$this->base_unit}s)",
                            'barcode' => $this->primary_unit_barcode ?: null,
                            'purchase_price' => $primPurchasePrice,
                            'sale_price' => $primSalePrice,
                            'allow_purchase' => true,
                            'allow_sale' => true,
                            'status' => 'active',
                        ]
                    );
                }

                $inputQty = (float) $this->quantity;
                $selectedConversion = 1.0;
                $selectedUnitName = $this->base_unit;

                if ($this->initial_stock_unit === 'primary' && !empty($this->primary_unit)) {
                    $selectedConversion = (float) ($primaryToSecondary * $secondaryToBase);
                    $selectedUnitName = $this->primary_unit;
                } elseif ($this->initial_stock_unit === 'secondary' && !empty($this->secondary_unit)) {
                    $selectedConversion = (float) $secondaryToBase;
                    $selectedUnitName = $this->secondary_unit;
                }

                $initialBaseQuantity = round($inputQty * $selectedConversion, 4);

                $batch = MedicineBatch::create([
                    'medicine_id' => $medicine->id,
                    'supplier_id' => $this->supplier_id ?: null,
                    'batch_number' => $batchNum,
                    'quantity' => $initialBaseQuantity,
                    'purchase_price' => $basePurchasePrice,
                    'selling_price' => $basePrice,
                    'purchase_price_per_base_unit' => $basePurchasePrice,
                    'selling_price_per_base_unit' => $basePrice,
                    'expiry_date' => $this->has_expiry ? ($this->expiry_date ?: null) : null,
                    'status' => 'active',
                ]);

                if ($initialBaseQuantity > 0) {
                    app(StockLedgerService::class)->recordMovement(
                        medicineId: $medicine->id,
                        batchId: $batch->id,
                        type: 'OPENING_STOCK',
                        referenceId: null,
                        referenceType: null,
                        selectedUnitId: $baseUnit->id,
                        quantity: $inputQty,
                        conversionToBase: $selectedConversion,
                        baseQuantity: $initialBaseQuantity,
                        userId: auth()->id() ?? 1,
                        notes: "Initial opening stock entered as {$inputQty} {$selectedUnitName}"
                    );
                } else {
                    app(StockLedgerService::class)->syncInventory($medicine->id);
                }
            });

            $label = $this->product_type === 'general' ? 'General Store Item' : 'Medicine';
            session()->flash('message', "{$label} Added Successfully with complete packaging hierarchy!");
        } catch (\Exception $e) {
            session()->flash('error', 'Error saving product: ' . $e->getMessage());
            return;
        }

        $this->reset([
            'name', 'generic_name', 'brand', 'strength', 'dosage_form', 'manufacturer',
            'barcode', 'sku', 'category_id', 'category_search', 'supplier_id', 'batch_number',
            'quantity', 'initial_stock_unit', 'purchase_price', 'selling_price', 'expiry_date',
            'primary_unit', 'secondary_unit', 'base_unit', 'dosage_unit',
            'primary_unit_to_secondary', 'secondary_unit_to_base',
            'primary_unit_purchase_price', 'secondary_unit_purchase_price', 'base_unit_purchase_price',
            'primary_unit_selling_price', 'secondary_unit_selling_price', 'base_unit_selling_price',
            'primary_unit_barcode', 'secondary_unit_barcode', 'base_unit_barcode',
        ]);

        $this->batch_number = 'BAT-' . strtoupper(Str::random(6));

        $this->alert_quantity = '10';
        $this->reorder_level = '10';
        $this->tax_rate = '0';
        if ($this->product_type === 'general') {
            $this->base_unit = 'Piece';
            $this->has_expiry = false;
            $this->track_batches = false;
            $this->primary_unit_to_secondary = '1';
            $this->secondary_unit_to_base = '1';
        } else {
            $this->base_unit = 'Tablet';
            $this->has_expiry = true;
            $this->track_batches = true;
            $this->primary_unit_to_secondary = '10';
            $this->secondary_unit_to_base = '10';
        }

        $this->resetPage();
    }

    // =========================================================================
    // TABLE ACTIONS & METHODS
    // =========================================================================

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedMedicines = Medicine::latest()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedMedicines = [];
        }
    }

    public function toggleExpandRow(int $id): void
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = array_values(array_diff($this->expandedRows, [$id]));
        } else {
            $this->expandedRows[] = $id;
        }
    }

    public function openViewModal(int $id): void
    {
        $this->viewMedicineId = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewMedicineId = null;
    }

    public function openBarcodeModal(int $id): void
    {
        $this->barcodeMedicineId = $id;
        $this->barcodeCopies = 1;
        $this->showBarcodeModal = true;
    }

    public function closeBarcodeModal(): void
    {
        $this->showBarcodeModal = false;
        $this->barcodeMedicineId = null;
    }

    // =========================================================================
    // EDIT MEDICINE MODAL
    // =========================================================================

    public function openEditModal(int $id): void
    {
        $med = Medicine::with(['packagings.unit', 'category'])->find($id);
        if (!$med) return;

        $this->editMedicineId = $med->id;
        $this->edit_name = $med->name;
        $this->edit_generic_name = $med->generic_name ?? '';
        $this->edit_brand = $med->brand ?? '';
        $this->edit_strength = $med->strength ?? '';
        $this->edit_dosage_form = $med->dosage_form ?? '';
        $this->edit_category_id = (string)($med->category_id ?? '');
        $this->edit_manufacturer = $med->manufacturer ?? '';
        $this->edit_barcode = $med->barcode ?? '';
        $this->edit_sku = $med->sku ?? '';
        $this->edit_alert_quantity = (string)($med->alert_quantity ?? 10);
        $this->edit_reorder_level = (string)($med->reorder_level ?? 10);
        $this->edit_tax_rate = (string)($med->tax_rate ?? 0);
        $this->edit_status = $med->status ?? 'active';
        $this->edit_product_type = $med->product_type ?? 'medicine';
        $this->edit_primary_unit = $med->primary_unit ?? '';
        $this->edit_secondary_unit = $med->secondary_unit ?? '';
        $this->edit_base_unit = $med->base_unit ?? 'Tablet';
        $this->edit_primary_unit_to_secondary = (string)($med->primary_unit_to_secondary ?? 10);
        $this->edit_secondary_unit_to_base = (string)($med->secondary_unit_to_base ?? 10);
        $this->edit_unit_price = (string)($med->unit_price ?? 0);
        $this->edit_purchase_price = (string)($med->purchase_price ?? 0);
        $this->edit_primary_unit_selling_price = (string)($med->primary_unit_selling_price ?? '');
        $this->edit_secondary_unit_selling_price = (string)($med->secondary_unit_selling_price ?? '');
        $this->edit_base_unit_selling_price = (string)($med->base_unit_selling_price ?? $med->unit_price ?? '');

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editMedicineId = null;
    }

    public function updateMedicine(): void
    {
        if (!$this->editMedicineId) return;

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_category_id' => 'required|exists:categories,id',
            'edit_unit_price' => 'required|numeric|min:0',
            'edit_purchase_price' => 'nullable|numeric|min:0',
            'edit_alert_quantity' => 'nullable|integer|min:0',
            'edit_reorder_level' => 'nullable|integer|min:0',
            'edit_tax_rate' => 'nullable|numeric|min:0',
        ]);

        $medicine = Medicine::find($this->editMedicineId);
        if (!$medicine) {
            $this->closeEditModal();
            return;
        }

        DB::transaction(function () use ($medicine) {
            $basePrice = (float)$this->edit_unit_price;
            $basePurchasePrice = (float)($this->edit_purchase_price ?: 0);

            $medicine->update([
                'name' => trim($this->edit_name),
                'generic_name' => $this->edit_product_type === 'medicine' ? ($this->edit_generic_name ?: null) : null,
                'brand' => $this->edit_brand ?: null,
                'strength' => $this->edit_strength ?: null,
                'dosage_form' => $this->edit_dosage_form ?: null,
                'category_id' => $this->edit_category_id,
                'manufacturer' => $this->edit_manufacturer ?: null,
                'barcode' => $this->edit_barcode ?: null,
                'sku' => $this->edit_sku ?: null,
                'alert_quantity' => (int)($this->edit_alert_quantity ?: 10),
                'reorder_level' => (int)($this->edit_reorder_level ?: 10),
                'tax_rate' => (float)($this->edit_tax_rate ?: 0),
                'status' => $this->edit_status ?: 'active',
                'unit_price' => $basePrice,
                'purchase_price' => $basePurchasePrice,
                'primary_unit' => $this->edit_primary_unit ?: null,
                'secondary_unit' => $this->edit_secondary_unit ?: null,
                'primary_unit_to_secondary' => max(1, (int)($this->edit_primary_unit_to_secondary ?: 1)),
                'secondary_unit_to_base' => max(1, (int)($this->edit_secondary_unit_to_base ?: 1)),
                'primary_unit_selling_price' => $this->edit_primary_unit_selling_price !== '' ? (float)$this->edit_primary_unit_selling_price : null,
                'secondary_unit_selling_price' => ($this->edit_product_type === 'medicine' && $this->edit_secondary_unit_selling_price !== '') ? (float)$this->edit_secondary_unit_selling_price : null,
                'base_unit_selling_price' => $basePrice,
            ]);

            MedicinePackaging::where('medicine_id', $medicine->id)
                ->where('conversion_to_base', 1.0)
                ->update([
                    'sale_price' => $basePrice,
                    'purchase_price' => $basePurchasePrice,
                    'barcode' => $this->edit_barcode ?: null,
                ]);
        });

        session()->flash('message', "Product '{$medicine->name}' updated successfully.");
        $this->closeEditModal();
    }

    // =========================================================================
    // ADD BATCH / RESTOCK MODAL
    // =========================================================================

    public function openAddBatchModal(int $id): void
    {
        $med = Medicine::find($id);
        if (!$med) return;

        $this->batchMedicineId = $med->id;
        $this->batchMedicineName = $med->name;
        $this->new_batch_number = ($med->product_type === 'general' ? 'GEN-' : 'BAT-') . strtoupper(Str::random(6));
        $this->new_batch_supplier_id = '';
        $this->new_batch_quantity = '';
        $this->new_batch_unit = 'base';
        $this->new_batch_purchase_price = (string)($med->purchase_price ?? '');
        $this->new_batch_selling_price = (string)($med->unit_price ?? '');
        $this->new_batch_expiry_date = '';

        $this->showAddBatchModal = true;
    }

    public function closeAddBatchModal(): void
    {
        $this->showAddBatchModal = false;
        $this->batchMedicineId = null;
    }

    public function saveNewBatch(): void
    {
        if (!$this->batchMedicineId) return;

        $medicine = Medicine::with('packagings')->find($this->batchMedicineId);
        if (!$medicine) return;

        $this->validate([
            'new_batch_number' => 'required|string|max:255',
            'new_batch_quantity' => 'required|numeric|min:0.01',
            'new_batch_selling_price' => 'required|numeric|min:0',
            'new_batch_purchase_price' => 'nullable|numeric|min:0',
            'new_batch_expiry_date' => $medicine->has_expiry ? 'required|date' : 'nullable|date',
        ]);

        DB::transaction(function () use ($medicine) {
            $multiplier = 1.0;
            if ($this->new_batch_unit === 'primary') {
                $multiplier = (float)$medicine->primary_to_base_multiplier;
            } elseif ($this->new_batch_unit === 'secondary') {
                $multiplier = (float)$medicine->secondary_to_base_multiplier;
            }

            $baseQty = round((float)$this->new_batch_quantity * $multiplier, 4);
            $sellPrice = (float)$this->new_batch_selling_price;
            $costPrice = (float)($this->new_batch_purchase_price ?: $medicine->purchase_price ?: 0);

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->id,
                'supplier_id' => $this->new_batch_supplier_id ?: null,
                'batch_number' => trim($this->new_batch_number),
                'quantity' => $baseQty,
                'purchase_price' => $costPrice,
                'selling_price' => $sellPrice,
                'purchase_price_per_base_unit' => $costPrice / max(1, $multiplier),
                'selling_price_per_base_unit' => $sellPrice / max(1, $multiplier),
                'expiry_date' => $medicine->has_expiry ? ($this->new_batch_expiry_date ?: null) : null,
                'status' => 'active',
            ]);

            app(StockLedgerService::class)->recordMovement(
                medicineId: $medicine->id,
                batchId: $batch->id,
                type: 'PURCHASE',
                referenceId: null,
                referenceType: null,
                selectedUnitId: $medicine->base_unit_id,
                quantity: (float)$this->new_batch_quantity,
                conversionToBase: $multiplier,
                baseQuantity: $baseQty,
                userId: auth()->id() ?? 1,
                notes: "Restocked via Inventory List: Batch {$batch->batch_number}"
            );
        });

        session()->flash('message', "New batch added & stock updated for '{$medicine->name}'.");
        $this->closeAddBatchModal();
    }

    // =========================================================================
    // STOCK ADJUSTMENT MODAL
    // =========================================================================

    public function openAdjustStockModal(int $batchId): void
    {
        $batch = MedicineBatch::with('medicine')->find($batchId);
        if (!$batch) return;

        $this->adjustBatchId = $batch->id;
        $this->adjustMedicineId = $batch->medicine_id;
        $this->adjustBatchNumber = $batch->batch_number;
        $this->adjustMedicineName = $batch->medicine->name ?? 'Medicine';
        $this->adjustCurrentQty = (float)$batch->quantity;
        $this->adjustType = 'ADJUSTMENT_IN';
        $this->adjustQuantity = '';
        $this->adjustNotes = '';

        $this->showAdjustStockModal = true;
    }

    public function closeAdjustStockModal(): void
    {
        $this->showAdjustStockModal = false;
        $this->adjustBatchId = null;
    }

    public function saveStockAdjustment(): void
    {
        if (!$this->adjustBatchId) return;

        $this->validate([
            'adjustQuantity' => 'required|numeric|min:0.01',
            'adjustType' => 'required|in:ADJUSTMENT_IN,ADJUSTMENT_OUT,DAMAGE,EXPIRED',
            'adjustNotes' => 'nullable|string|max:255',
        ]);

        $batch = MedicineBatch::with('medicine')->find($this->adjustBatchId);
        if (!$batch) return;

        $qty = (float)$this->adjustQuantity;
        $current = (float)$batch->quantity;

        if (in_array($this->adjustType, ['ADJUSTMENT_OUT', 'DAMAGE', 'EXPIRED']) && $qty > $current) {
            $this->addError('adjustQuantity', "Adjustment quantity ({$qty}) cannot exceed current batch stock ({$current}).");
            return;
        }

        DB::transaction(function () use ($batch, $qty) {
            $isIncoming = in_array($this->adjustType, ['ADJUSTMENT_IN']);
            $newQty = $isIncoming ? ($batch->quantity + $qty) : max(0, $batch->quantity - $qty);

            $batch->update([
                'quantity' => $newQty,
                'status' => $newQty <= 0 ? 'depleted' : 'active',
            ]);

            app(StockLedgerService::class)->recordMovement(
                medicineId: $batch->medicine_id,
                batchId: $batch->id,
                type: $this->adjustType,
                referenceId: null,
                referenceType: null,
                selectedUnitId: $batch->medicine?->base_unit_id,
                quantity: $qty,
                conversionToBase: 1.0,
                baseQuantity: $qty,
                userId: auth()->id() ?? 1,
                notes: $this->adjustNotes ?: "Stock Adjustment ({$this->adjustType})"
            );
        });

        session()->flash('message', "Stock adjusted successfully for Batch {$batch->batch_number}.");
        $this->closeAdjustStockModal();
    }

    // =========================================================================
    // DELETE FUNCTIONALITY (PERMANENT & CLEAN CASCADE)
    // =========================================================================

    public function confirmDelete(int $id): void
    {
        $medicine = Medicine::with(['batches', 'inventory'])->find($id);
        if (!$medicine) return;

        $this->deleteMedicineId = $id;
        $this->deleteMedicineName = $medicine->name;
        $this->deleteMedicineStock = $medicine->batches->sum('quantity');
        $this->deleteMedicineBatchesCount = $medicine->batches->count();
        $this->deleteHasSales = false;

        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteMedicineId = null;
        $this->deleteMedicineName = '';
    }

    public function deleteMedicine(): void
    {
        if (!$this->deleteMedicineId) return;

        $medicine = Medicine::find($this->deleteMedicineId);
        if (!$medicine) {
            $this->closeDeleteModal();
            return;
        }

        try {
            DB::transaction(function () use ($medicine) {
                $id = $medicine->id;

                // Delete all referencing transaction items
                DB::table('sale_items')->where('medicine_id', $id)->delete();
                DB::table('sales_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_invoice_items')->where('medicine_id', $id)->delete();
                
                if (\Illuminate\Support\Facades\Schema::hasTable('purchases')) {
                    DB::table('purchases')->where('medicine_id', $id)->delete();
                }

                StockMovement::where('medicine_id', $id)->delete();
                MedicineBatch::where('medicine_id', $id)->delete();
                MedicinePackaging::where('medicine_id', $id)->delete();
                Inventory::where('medicine_id', $id)->delete();

                $medicineName = $medicine->name;
                $medicine->delete();

                session()->flash('message', "Product '{$medicineName}' has been permanently deleted from inventory.");
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Could not delete product: ' . $e->getMessage());
        }

        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function deleteBatch(int $batchId): void
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = MedicineBatch::find($batchId);
                if (!$batch) return;

                $medicineId = $batch->medicine_id;
                $batchNum = $batch->batch_number;

                DB::table('sale_items')->where('batch_id', $batchId)->delete();
                DB::table('sales_return_items')->where('batch_id', $batchId)->delete();
                DB::table('purchase_return_items')->where('batch_id', $batchId)->delete();
                StockMovement::where('batch_id', $batchId)->delete();
                $batch->delete();

                app(StockLedgerService::class)->syncInventory($medicineId);
                session()->flash('message', "Batch '{$batchNum}' deleted permanently.");
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting batch: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // BULK ACTIONS (PERMANENT & CLEAN CASCADE)
    // =========================================================================

    public function confirmBulkDelete(): void
    {
        if (empty($this->selectedMedicines)) {
            session()->flash('error', 'No products selected.');
            return;
        }
        $this->showBulkDeleteModal = true;
    }

    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function bulkDelete(ProductBulkDeletionService $deletionService): void
    {
        if (empty($this->selectedMedicines)) {
            $this->closeBulkDeleteModal();
            return;
        }

        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $archivedCount = 0;
        $deletedCount = 0;

        DB::transaction(function () use (&$archivedCount, &$deletedCount, $deletionService) {
            foreach ($this->selectedMedicines as $id) {
                $medicine = Medicine::find($id);
                if (!$medicine) continue;

                $dep = $deletionService->checkProductDependencies((int)$id);
                if ($dep['has_history']) {
                    $medicine->status = 'archived';
                    $medicine->save();
                    $medicine->delete();
                    $archivedCount++;
                } else {
                    MedicinePackaging::where('medicine_id', $id)->delete();
                    Inventory::where('medicine_id', $id)->delete();
                    MedicineBatch::where('medicine_id', $id)->delete();
                    $medicine->forceDelete();
                    $deletedCount++;
                }
            }
        });

        $this->selectedMedicines = [];
        $this->selectAll = false;
        $this->closeBulkDeleteModal();

        $msgParts = [];
        if ($deletedCount > 0) $msgParts[] = "{$deletedCount} unreferenced products permanently deleted";
        if ($archivedCount > 0) $msgParts[] = "{$archivedCount} products archived to preserve historical records and accounting balances";
        $msg = empty($msgParts) ? "No products were modified." : implode(', ', $msgParts) . ".";

        session()->flash('message', $msg);
        $this->resetPage();
    }

    public function openDeleteAllModal(ProductBulkDeletionService $deletionService): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $this->deleteAllConfirmationInput = '';
        $this->deleteAllPreview = $deletionService->getPreviewStats();
        $this->showDeleteAllModal = true;
    }

    public function closeDeleteAllModal(): void
    {
        $this->showDeleteAllModal = false;
        $this->deleteAllConfirmationInput = '';
    }

    public function executeDeleteAll(ProductBulkDeletionService $deletionService): void
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        if (trim($this->deleteAllConfirmationInput) !== 'DELETE ALL PRODUCTS') {
            session()->flash('error', 'You must type DELETE ALL PRODUCTS exactly to confirm.');
            return;
        }

        $result = $deletionService->execute($user, request()->ip(), request()->userAgent());

        $this->closeDeleteAllModal();
        $this->selectedMedicines = [];
        $this->selectAll = false;

        $flashKey = ($result['status'] === 'success') ? 'message' : 'warning';
        session()->flash($flashKey, $result['message']);
        $this->resetPage();
    }

    public function bulkUpdateStatus(string $status): void
    {
        if (empty($this->selectedMedicines)) return;

        Medicine::whereIn('id', $this->selectedMedicines)->update(['status' => $status]);
        session()->flash('message', count($this->selectedMedicines) . " products updated to '{$status}'.");
        $this->selectedMedicines = [];
        $this->selectAll = false;
    }

    public function exportCsv()
    {
        $medicines = Medicine::with(['category', 'batches', 'packagings.unit', 'inventory'])->get();

        $filename = 'inventory-products-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($medicines) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Type', 'Name', 'Generic Name', 'Brand', 'Category', 'Base Unit', 
                'Total Stock', 'Purchase Price (PKR)', 'Selling Price (PKR)', 'Barcode', 
                'SKU', 'Batches Count', 'Status'
            ]);

            foreach ($medicines as $m) {
                $totalStock = $m->batches->sum('quantity');
                $firstBatch = $m->batches->first();
                fputcsv($file, [
                    $m->id,
                    $m->product_type,
                    $m->name,
                    $m->generic_name ?? '',
                    $m->brand ?? '',
                    $m->category?->name ?? '',
                    $m->base_unit,
                    $totalStock,
                    $firstBatch->purchase_price ?? $m->purchase_price ?? 0,
                    $firstBatch->selling_price ?? $m->unit_price ?? 0,
                    $m->barcode ?? '',
                    $m->sku ?? '',
                    $m->batches->count(),
                    $m->status ?? 'active',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // RENDER METHOD
    // =========================================================================

    public function render()
    {
        $query = Medicine::with([
            'category',
            'packagings.unit',
            'batches.supplier',
            'inventory',
        ]);

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('batches', function ($bq) use ($search) {
                        $bq->where('batch_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->productTypeFilter !== 'all') {
            $query->productType($this->productTypeFilter);
        }

        if ($this->categoryFilter !== '') {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->supplierFilter !== '') {
            $query->whereHas('batches', function ($q) {
                $q->where('supplier_id', $this->supplierFilter);
            });
        }

        if ($this->stockFilter === 'out_of_stock') {
            $query->whereDoesntHave('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        } elseif ($this->stockFilter === 'in_stock') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        } elseif ($this->stockFilter === 'expired') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereDate('expiry_date', '<', now()->toDateString());
            });
        } elseif ($this->stockFilter === 'low_stock') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        }

        if ($this->expiryFilter === '30_days') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(30)->toDateString()]);
            });
        } elseif ($this->expiryFilter === '60_days') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(60)->toDateString()]);
            });
        } elseif ($this->expiryFilter === '90_days') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereBetween('expiry_date', [now()->toDateString(), now()->addDays(90)->toDateString()]);
            });
        }

        // Sorting
        if ($this->sortField === 'name') {
            $query->orderBy('name', $this->sortDirection);
        } elseif ($this->sortField === 'unit_price') {
            $query->orderBy('unit_price', $this->sortDirection);
        } elseif ($this->sortField === 'category') {
            $query->join('categories', 'medicines.category_id', '=', 'categories.id')
                ->orderBy('categories.name', $this->sortDirection)
                ->select('medicines.*');
        } else {
            $query->orderBy('medicines.created_at', $this->sortDirection);
        }

        $medicines = $query->paginate($this->perPage);

        // Overall Stats
        $allMedicines = Medicine::with('batches')->get();
        $totalMedicines = $allMedicines->count();
        $totalMedicineProducts = $allMedicines->where('product_type', 'medicine')->count();
        $totalGeneralProducts = $allMedicines->where('product_type', 'general')->count();

        $totalStock = $allMedicines->sum(function ($medicine) {
            return $medicine->batches->sum('quantity');
        });

        $totalStockValue = $allMedicines->sum(function ($medicine) {
            return $medicine->batches->sum(function ($b) {
                return (float)($b->quantity * ($b->selling_price_per_base_unit ?? $b->selling_price ?? 0));
            });
        });

        $lowStock = $allMedicines->filter(function ($medicine) {
            $stock = $medicine->batches->sum('quantity');
            $alert = $medicine->reorder_level ?? $medicine->alert_quantity ?? 10;
            return $stock > 0 && $stock <= $alert;
        })->count();

        $expired = $allMedicines->filter(function ($medicine) {
            return $medicine->batches
                ->where('quantity', '>', 0)
                ->contains(function ($batch) {
                    return $batch->expiry_date && $batch->expiry_date->isPast();
                });
        })->count();

        $nearExpiry = $allMedicines->filter(function ($medicine) {
            return $medicine->batches
                ->where('quantity', '>', 0)
                ->contains(function ($batch) {
                    return $batch->expiry_date &&
                        !$batch->expiry_date->isPast() &&
                        $batch->expiry_date->lessThanOrEqualTo(now()->addDays(90));
                });
        })->count();

        $categories = Category::orderBy('name')->get();
        $formCategories = Category::forProductType($this->product_type)->orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $availableUnits = Unit::active()->orderBy('name')->get();

        // Selected Modals Data
        $viewMedicine = $this->viewMedicineId ? Medicine::with(['category', 'batches.supplier', 'packagings.unit', 'inventory', 'stockMovements' => fn($q) => $q->latest()->take(10)])->find($this->viewMedicineId) : null;
        $barcodeMedicine = $this->barcodeMedicineId ? Medicine::with(['batches', 'packagings'])->find($this->barcodeMedicineId) : null;

        // Auto-suggestions
        if ($this->product_type === 'general') {
            $standardProducts = [
                'Lux Beauty Soap 100g', 'Lux Soap Rose 140g', 'Dettol Soap Original 100g', 'Dettol Cool Soap 100g',
                'Lifebuoy Total Soap 100g', 'Safeguard Pure White 100g', 'Dove White Beauty Bar 100g', 'Palmolive Soap 100g',
                'Sunsilk Black Shine Shampoo 180ml', 'Sunsilk Soft & Smooth Shampoo 180ml', 'Head & Shoulders Classic Clean 180ml',
                'Pantene Pro-V Shampoo 180ml', 'Dove Intense Repair Shampoo 180ml', 'Lifebuoy Shampoo 175ml',
                'Colgate Maximum Cavity Protection 100g', 'Colgate Total 100g', 'Sensodyne Rapid Relief 100g',
                'Sensodyne Multi Care 100g', 'Close Up Deep Action 100g', 'Oral-B Toothbrush Medium',
                'Aquafina Mineral Water 500ml', 'Aquafina Mineral Water 1.5L', 'Nestle Pure Life 500ml', 'Nestle Pure Life 1.5L',
                'Pampers Active Baby Size 3 (56 Diapers)', 'Pampers Premium Protection Size 4', 'Huggies Extra Care Diapers Size 3',
                'Canbebe Baby Diapers Size 4', 'Johnson\'s Baby Shampoo 200ml', 'Johnson\'s Baby Oil 200ml', 'Johnson\'s Baby Powder 200g',
                'Johnson\'s Baby Lotion 200ml', 'Johnson\'s Baby Wipes (80 Pack)', 'Dettol Disinfectant Liquid 500ml',
                'Dettol Antiseptic Liquid 100ml', 'Vaseline Petroleum Jelly Original 100ml', 'Nivea Soft Moisturizing Cream 100ml',
                'Nivea Crème Tin 100ml', 'Gillette Mach 3 Razor', 'Gillette Blue 2 Plus (5 Pack)', 'Gillette Foamy Shaving Foam 200ml',
                'Lipton Yellow Label Tea 400g', 'Tapal Danedar Tea 400g', 'Everyday Tea Whitener 375g',
                'Olper\'s Milk 1 Liter', 'Nestle MilkPak 1 Liter', 'Red Bull Energy Drink 250ml', 'Sting Energy Drink 300ml',
                'Hand Sanitizer Gel 500ml', 'KN95 Protective Face Masks (10 Pack)', 'Surgical Face Masks 3-Ply (50 Pack)',
                'Crepe Bandage 4 Inch (1 Roll)', 'Cotton Wool Absorbent 100g', 'First Aid Adhesive Bandages (100 Strips)',
                'Pyodine Solution 60ml', 'Digital Thermometer'
            ];
            $dbProducts = Medicine::where('product_type', 'general')->pluck('name')->toArray();

            $standardBrands = [
                'Unilever', 'Procter & Gamble (P&G)', 'Nestle Pakistan', 'Reckitt Benckiser', 'Colgate-Palmolive',
                'Johnson & Johnson', 'Engro Foods', 'National Foods', 'Shan Foods', 'Tapal Tea',
                'Lipton', 'Dalda Foods', 'Hilal Confectionery', 'Shield Corporation', 'Canbebe / Ontex',
                'Kimberly-Clark (Huggies)', 'Pampers', 'Nivea (Beiersdorf)', 'L\'Oreal Pakistan',
                'Gillette', 'Dettol', 'Lux', 'Lifebuoy', 'Sunsilk', 'Safeguard', 'Head & Shoulders',
                'Dove', 'Vaseline', 'Coca-Cola Company', 'PepsiCo', 'Aquafina', 'Nestle Pure Life',
                'Mitchell\'s', 'Mitchell\'s Farms', 'English Biscuits (Peek Freans)',
                'Continental Biscuits (LU)', 'Knorr', 'Rafhan', 'Marico (Parachute)', 'Dabur'
            ];
            $dbBrands = Medicine::where('product_type', 'general')->whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand')->toArray();
        } else {
            $standardProducts = [
                'Panadol 500mg (Tablet)', 'Panadol Extra (Tablet)', 'Panadol CF (Tablet)', 'Panadol Children Syrup 120ml',
                'Disprin 300mg (Effervescent Tablet)', 'Disprin Direct (Tablet)', 'Brufen 400mg (Tablet)', 'Brufen 600mg (Tablet)',
                'Brufen DS Suspension 120ml', 'Augmentin 625mg (Tablet)', 'Augmentin 1g (Tablet)', 'Augmentin DS Syrup 156.25mg/5ml',
                'Flagyl 400mg (Tablet)', 'Flagyl 200mg/5ml Suspension 60ml', 'Arinac Forte (Tablet)', 'Arinac Syrup 60ml',
                'Flyxotic 500mg (Tablet)', 'Ciproxin 500mg (Tablet)', 'Risek 20mg (Capsule)', 'Risek 40mg (Capsule)', 'Risek 40mg (Insta Sachet)',
                'Kestine 10mg (Tablet)', 'Softin 10mg (Tablet)', 'Rigix 10mg (Tablet)', 'Zyrtec 10mg (Tablet)', 'Gravinate 50mg (Tablet)',
                'Buscopan 10mg (Tablet)', 'Buscopan Plus (Tablet)', 'Ponstan 500mg (Tablet)', 'Ponstan Forte (Tablet)',
                'CaC 1000 Plus Effervescent (10 Tablets)', 'Surbex Z (Tablet)', 'Neurobion (Tablet)', 'Neurobion Injection',
                'Entox-P (Tablet)', 'Hydryllin Syrup 120ml', 'Pulmonol Syrup 120ml', 'Acefyl Syrup 120ml', 'T-Day 5mg (Tablet)',
                'Polyfax Skin Ointment 20g', 'Polyfax Eye Ointment 6g', 'Fastum Gel 50g', 'Voltral Emulgel 1% 50g',
                'Calamox 625mg (Tablet)', 'Novidat 500mg (Tablet)', 'Leflox 500mg (Tablet)', 'Velosef 500mg (Capsule)',
                'Methycobal 500mcg (Tablet)', 'Gabbro 500mg (Tablet)', 'Lowplat 75mg (Tablet)', 'Ascard 75mg (Tablet)'
            ];
            $dbProducts = Medicine::where('product_type', 'medicine')->pluck('name')->toArray();

            $standardBrands = [
                'GSK (GlaxoSmithKline)', 'Abbott Laboratories', 'Getz Pharma', 'The Searle Company',
                'Sanofi-Aventis', 'Sami Pharmaceuticals', 'Hilton Pharma', 'Pfizer Pakistan', 'Novartis',
                'Bayer Pakistan', 'Ferozsons Laboratories', 'CCL Pharmaceuticals', 'Bosch Pharmaceuticals',
                'PharmEvo', 'Highnoon Laboratories', 'Platinum Pharmaceuticals', 'AGP Limited',
                'Martin Dow Marker', 'Barrett Hodgson', 'Macter International', 'Reckitt Benckiser Health',
                'Bio-Labs', 'Atco Laboratories', 'Zafa Pharmaceuticals', 'BPL', 'Remington Pharma'
            ];
            $dbBrands = Medicine::where('product_type', 'medicine')->whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand')->toArray();
        }

        $suggestedProductNames = collect(array_merge($standardProducts, $dbProducts))->unique()->sort()->values();
        $suggestedBrands = collect(array_merge($standardBrands, $dbBrands))->unique()->sort()->values();

        $standardGenerics = [
            'Paracetamol', 'Ibuprofen', 'Amoxicillin', 'Amoxicillin + Clavulanic Acid (Co-Amoxiclav)',
            'Ciprofloxacin', 'Omeprazole', 'Esomeprazole', 'Azithromycin', 'Metformin HCl',
            'Cefixime', 'Cefradine', 'Diclofenac Sodium', 'Diclofenac Potassium',
            'Loratadine', 'Cetirizine HCl', 'Levocetirizine', 'Montelukast Sodium',
            'Metronidazole', 'Doxycycline', 'Fluconazole', 'Artemether + Lumefantrine',
            'Amlodipine', 'Losartan Potassium', 'Atorvastatin', 'Rosuvastatin',
            'Pantoprazole', 'Domperidone', 'Ondansetron', 'Tramadol HCl', 'Mefenamic Acid',
            'Chlorpheniramine Maleate', 'Dextromethorphan', 'Salbutamol', 'Budesonide',
            'Prednisolone', 'Hydrocortisone', 'Dexamethasone', 'Sitagliptin', 'Glimepiride',
            'Gliclazide', 'Clopidogrel', 'Aspirin (Acetylsalicylic Acid)', 'Bisoprolol Fumarate',
            'Valsartan', 'Spironolactone', 'Furosemide', 'Gatifloxacin', 'Moxifloxacin',
            'Levofloxacin', 'Clarithromycin', 'Tranexamic Acid', 'Iron Polymaltose + Folic Acid',
            'Calcium Carbonate + Vitamin D3', 'Cholecalciferol (Vitamin D3)', 'Ascorbic Acid (Vitamin C)',
            'Zinc Sulfate', 'Oral Rehydration Salts (ORS)'
        ];
        $dbGenerics = Medicine::whereNotNull('generic_name')->where('generic_name', '!=', '')->distinct()->pluck('generic_name')->toArray();
        $suggestedGenerics = collect(array_merge($standardGenerics, $dbGenerics))->unique()->sort()->values();

        $standardManufacturers = [
            'GlaxoSmithKline (GSK) Pakistan Ltd', 'Abbott Laboratories Pakistan Ltd',
            'Getz Pharma (Pvt) Ltd', 'The Searle Company Ltd', 'Sanofi-Aventis Pakistan Ltd',
            'Sami Pharmaceuticals (Pvt) Ltd', 'Hilton Pharma (Pvt) Ltd', 'Pfizer Pakistan Ltd',
            'Novartis Pharma Pakistan Ltd', 'Bayer Pakistan (Pvt) Ltd', 'Ferozsons Laboratories Ltd',
            'CCL Pharmaceuticals (Pvt) Ltd', 'Bosch Pharmaceuticals (Pvt) Ltd', 'PharmEvo (Pvt) Ltd',
            'Highnoon Laboratories Ltd', 'Platinum Pharmaceuticals', 'AGP Limited',
            'Martin Dow Marker Ltd', 'Barrett Hodgson Pakistan', 'Macter International Ltd',
            'Reckitt Benckiser Pakistan', 'Unilever Pakistan Ltd', 'Procter & Gamble Pakistan Ltd',
            'Nestle Pakistan Ltd', 'Colgate-Palmolive Pakistan Ltd', 'Engro Foods Pakistan'
        ];
        $dbManufacturers = Medicine::whereNotNull('manufacturer')->where('manufacturer', '!=', '')->distinct()->pluck('manufacturer')->toArray();
        $suggestedManufacturers = collect(array_merge($standardManufacturers, $dbManufacturers))->unique()->sort()->values();

        $suggestedDosageForms = [
            'Tablet', 'Film-Coated Tablet', 'Dispersible Tablet', 'Chewable Tablet',
            'Effervescent Tablet', 'Capsule', 'Softgel Capsule', 'Syrup', 'Suspension',
            'Oral Drops', 'Elixir', 'Injection (IV/IM)', 'Infusion', 'Cream', 'Ointment',
            'Gel', 'Lotion', 'Eye Drops', 'Ear Drops', 'Nasal Spray', 'Inhaler',
            'Nebulizer Solution', 'Suppository', 'Sachet / Powder', 'Mouthwash'
        ];

        $suggestedStrengths = [
            '500mg', '250mg', '125mg', '62.5mg', '1000mg (1g)', '100mg', '200mg', '400mg',
            '50mg', '25mg', '20mg', '10mg', '5mg', '2.5mg', '1mg', '0.5mg', '20mg/ml',
            '10mg/5ml', '100mg/5ml', '125mg/5ml', '200mg/5ml', '250mg/5ml', '400mg/5ml',
            '10%', '5%', '2%', '1%', '0.1%', '0.05%'
        ];

        return view('livewire.admin.add-medicine', [
            'medicines' => $medicines,
            'categories' => $categories,
            'formCategories' => $formCategories,
            'suppliers' => $suppliers,
            'availableUnits' => $availableUnits,
            'totalMedicines' => $totalMedicines,
            'totalMedicineProducts' => $totalMedicineProducts,
            'totalGeneralProducts' => $totalGeneralProducts,
            'totalStock' => $totalStock,
            'totalStockValue' => $totalStockValue,
            'lowStock' => $lowStock,
            'expired' => $expired,
            'nearExpiry' => $nearExpiry,
            'viewMedicine' => $viewMedicine,
            'barcodeMedicine' => $barcodeMedicine,
            'suggestedProductNames' => $suggestedProductNames,
            'suggestedBrands' => $suggestedBrands,
            'suggestedGenerics' => $suggestedGenerics,
            'suggestedManufacturers' => $suggestedManufacturers,
            'suggestedDosageForms' => $suggestedDosageForms,
            'suggestedStrengths' => $suggestedStrengths,
        ])->layout('layouts.app');
    }
}