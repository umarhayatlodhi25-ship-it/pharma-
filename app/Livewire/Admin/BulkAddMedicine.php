<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\MedicineImportService;
use App\Services\StockLedgerService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

class BulkAddMedicine extends Component
{
    use WithFileUploads;

    public array $rows = [];
    public $importFile;
    public ?array $importSummary = null;
    public int $totalImported = 0;
    public int $totalSkipped = 0;
    public int $totalFailed = 0;
    public bool $updateExisting = false;
    public bool $isImportedPreview = false;

    public function mount(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->addRow();
        }
    }

    public function addRow(): void
    {
        $this->rows[] = [
            'name' => '',
            'category_id' => '',
            'category_name_raw' => '',
            'generic_name' => '',
            'brand' => '',
            'manufacturer' => '',
            'barcode' => '',
            'base_unit' => 'Tablet',
            'dosage_unit' => 'Tablet',
            'strip_enabled' => false,
            'units_per_strip' => '10',
            'pack_enabled' => false,
            'strips_per_pack' => '10',
            'box_enabled' => false,
            'packs_per_box' => '',
            'stock_quantity' => '0',
            'stock_entry_unit' => 'Tablet',
            'converted_base_stock' => 0,
            'packaging_display' => 'Base only (1 Tablet)',
            'strip_conversion_display' => 'No Strip',
            'pack_conversion_display' => 'No Pack',
            'unit_price' => '',
            'sale_price_unit' => 'Tablet',
            'base_unit_selling_price' => 0,
            'strip_selling_price' => null,
            'pack_selling_price' => null,
            'purchase_price' => '',
            'purchase_price_unit' => 'Tablet',
            'base_unit_purchase_price' => 0,
            'strip_purchase_price' => null,
            'pack_purchase_price' => null,
            'alert_quantity' => '10',
            'batch_number' => '',
            'expiry_date' => '',
            'supplier' => '',
            'is_valid' => true,
            'validation_errors' => [],
            'allowed_units' => ['Tablet'],
        ];
    }

    public function clearAllRows(): void
    {
        $this->rows = [];
        $this->importSummary = null;
        $this->isImportedPreview = false;
        $this->resetErrorBag();
        for ($i = 0; $i < 3; $i++) {
            $this->addRow();
        }
    }

    public function updatedRows($value, $key): void
    {
        if (preg_match('/^(\d+)\./', $key, $matches)) {
            $this->recalculateRow((int) $matches[1]);
        }
    }

    public function recalculateRow(int $index): void
    {
        if (!isset($this->rows[$index])) {
            return;
        }

        $service = app(MedicineImportService::class);
        $calculated = $service->calculateRowPackaging($this->rows[$index], $index + 1);

        $this->rows[$index]['base_unit'] = $calculated['base_unit'];
        $this->rows[$index]['dosage_unit'] = $calculated['dosage_unit'];
        $this->rows[$index]['strip_enabled'] = $calculated['strip_enabled'];
        $this->rows[$index]['pack_enabled'] = $calculated['pack_enabled'];
        $this->rows[$index]['box_enabled'] = $calculated['box_enabled'];
        $this->rows[$index]['stock_entry_unit'] = $calculated['stock_entry_unit'];
        $this->rows[$index]['converted_base_stock'] = $calculated['converted_base_stock'];
        $this->rows[$index]['packaging_display'] = $calculated['packaging_display'];
        $this->rows[$index]['strip_conversion_display'] = $calculated['strip_conversion_display'];
        $this->rows[$index]['pack_conversion_display'] = $calculated['pack_conversion_display'];
        $this->rows[$index]['sale_price_unit'] = $calculated['sale_price_unit'];
        $this->rows[$index]['base_unit_selling_price'] = $calculated['base_unit_selling_price'];
        $this->rows[$index]['strip_selling_price'] = $calculated['strip_selling_price'];
        $this->rows[$index]['pack_selling_price'] = $calculated['pack_selling_price'];
        $this->rows[$index]['purchase_price_unit'] = $calculated['purchase_price_unit'];
        $this->rows[$index]['base_unit_purchase_price'] = $calculated['base_unit_purchase_price'];
        $this->rows[$index]['strip_purchase_price'] = $calculated['strip_purchase_price'];
        $this->rows[$index]['pack_purchase_price'] = $calculated['pack_purchase_price'];
        if (!empty($calculated['expiry_date'])) {
            $this->rows[$index]['expiry_date'] = $calculated['expiry_date'];
        }
        $this->rows[$index]['allowed_units'] = $calculated['allowed_units'];

        // Live validation for this row
        $rowErrors = [];
        $rowNum = $index + 1;
        $name = trim((string)($this->rows[$index]['name'] ?? ''));

        if ($name === '') {
            $rowErrors[] = "Row {$rowNum}: Medicine Name is required.";
        }

        if (empty($this->rows[$index]['category_id'])) {
            $rowErrors[] = "Row {$rowNum}: Category is required.";
        }

        $salePrice = trim((string)($this->rows[$index]['unit_price'] ?? ''));
        if ($salePrice === '') {
            $rowErrors[] = "Row {$rowNum}: Sale Price is required.";
        } elseif (!is_numeric($salePrice) || (float)$salePrice < 0) {
            $rowErrors[] = "Row {$rowNum}: Sale Price must be a valid non-negative number.";
        }

        $purchasePrice = trim((string)($this->rows[$index]['purchase_price'] ?? ''));
        if ($purchasePrice !== '' && (!is_numeric($purchasePrice) || (float)$purchasePrice < 0)) {
            $rowErrors[] = "Row {$rowNum}: Purchase Price must be a valid non-negative number.";
        }

        if (!empty($calculated['errors'])) {
            foreach ($calculated['errors'] as $err) {
                $rowErrors[] = $err;
            }
        }

        $this->rows[$index]['validation_errors'] = $rowErrors;
        $this->rows[$index]['is_valid'] = empty($rowErrors);

        $this->updateSummaryMetrics();
    }

    public function removeRow(int $index): void
    {
        if (isset($this->rows[$index])) {
            unset($this->rows[$index]);
            $this->rows = array_values($this->rows);
        }

        if (empty($this->rows)) {
            $this->addRow();
        }

        $this->updateSummaryMetrics();
    }

    public function updatedImportFile(): void
    {
        $this->importData();
    }

    public function updatedUpdateExisting(): void
    {
        $this->resetErrorBag();
        foreach (array_keys($this->rows) as $idx) {
            $this->recalculateRow((int) $idx);
        }
    }

    public function importData(): void
    {
        $this->resetErrorBag();

        if (!$this->importFile) {
            $this->addError('importFile', 'Please select a file to import.');
            return;
        }

        $this->validate([
            'importFile' => 'required|file|max:10240',
        ], [
            'importFile.required' => 'Please select a file to import.',
            'importFile.max' => 'The file size must not exceed 10MB.',
        ]);

        $ext = strtolower((string) $this->importFile->getClientOriginalExtension());
        if (!in_array($ext, ['csv', 'txt', 'xlsx', 'xls', 'ods'])) {
            $this->addError('importFile', 'The file must be a CSV or Excel spreadsheet (.csv, .xlsx, .xls).');
            $this->reset('importFile');
            return;
        }

        try {
            $service = app(MedicineImportService::class);
            $result = $service->parseFile($this->importFile, $this->updateExisting);

            if (!$result['success']) {
                $this->addError('general', $result['error']);
                return;
            }

            $this->applyParsedRows($result);
            $this->isImportedPreview = true;

        } catch (\Throwable $e) {
            $this->addError('general', 'Failed to import file: ' . $e->getMessage());
        } finally {
            $this->reset('importFile');
        }
    }

    public function importJson($jsonData): void
    {
        $this->resetErrorBag();

        if (empty($jsonData) || !is_array($jsonData)) {
            $this->addError('general', 'No data provided to import.');
            return;
        }

        try {
            $service = app(MedicineImportService::class);
            $result = $service->parseRawData($jsonData, $this->updateExisting);

            if (!$result['success']) {
                $this->addError('general', $result['error']);
                return;
            }

            $this->applyParsedRows($result);
            $this->isImportedPreview = true;

        } catch (\Throwable $e) {
            $this->addError('general', 'Failed to process data: ' . $e->getMessage());
        }
    }

    protected function applyParsedRows(array $result): void
    {
        $hasExistingData = false;
        foreach ($this->rows as $r) {
            if (!empty(trim($r['name'] ?? '')) || !empty(trim($r['category_id'] ?? '')) || !empty(trim($r['unit_price'] ?? ''))) {
                $hasExistingData = true;
                break;
            }
        }

        if ($hasExistingData) {
            $existingFiltered = array_values(array_filter($this->rows, function ($r) {
                return !empty(trim($r['name'] ?? '')) || !empty(trim($r['category_id'] ?? '')) || !empty(trim($r['unit_price'] ?? ''));
            }));
            $offset = count($existingFiltered);
            $this->rows = array_merge($existingFiltered, $result['rows']);
        } else {
            $offset = 0;
            $this->rows = $result['rows'];
        }

        $allIssues = [];
        foreach ($result['row_errors'] as $rowIndex => $errors) {
            $adjustedIndex = $offset + $rowIndex;
            foreach ($errors as $err) {
                $allIssues[] = $err;
                if (str_contains($err, 'Medicine Name') || str_contains($err, 'medicine name')) {
                    $this->addError("rows.{$adjustedIndex}.name", $err);
                } elseif (str_contains($err, 'Category') || str_contains($err, 'category')) {
                    $this->addError("rows.{$adjustedIndex}.category_id", $err);
                } elseif (str_contains($err, 'Sale Price') || str_contains($err, 'sale price')) {
                    $this->addError("rows.{$adjustedIndex}.unit_price", $err);
                } elseif (str_contains($err, 'Purchase Price') || str_contains($err, 'purchase price')) {
                    $this->addError("rows.{$adjustedIndex}.purchase_price", $err);
                } elseif (str_contains($err, 'Barcode') || str_contains($err, 'barcode')) {
                    $this->addError("rows.{$adjustedIndex}.barcode", $err);
                } elseif (str_contains($err, 'Strip') || str_contains($err, 'strip')) {
                    $this->addError("rows.{$adjustedIndex}.units_per_strip", $err);
                } elseif (str_contains($err, 'Pack') || str_contains($err, 'pack')) {
                    $this->addError("rows.{$adjustedIndex}.strips_per_pack", $err);
                } elseif (str_contains($err, 'Expiry') || str_contains($err, 'expiry')) {
                    $this->addError("rows.{$adjustedIndex}.expiry_date", $err);
                } elseif (str_contains($err, 'Batch') || str_contains($err, 'batch')) {
                    $this->addError("rows.{$adjustedIndex}.batch_number", $err);
                } elseif (str_contains($err, 'Stock unit') || str_contains($err, 'stock unit') || str_contains($err, 'Stock Quantity') || str_contains($err, 'stock quantity')) {
                    $this->addError("rows.{$adjustedIndex}.stock_entry_unit", $err);
                } else {
                    $this->addError("rows.{$adjustedIndex}.name", $err);
                }
            }
        }

        $stats = $result['stats'];
        $stats['errors'] = $allIssues;
        $this->importSummary = $stats;
        $this->totalImported = $stats['valid'];
        $this->totalSkipped = $stats['duplicates'];
        $this->totalFailed = $stats['invalid'];

        if ($stats['invalid'] === 0) {
            session()->flash('message', "{$stats['valid']} medicines loaded from file. Please review and click Save All Medicines.");
        } else {
            session()->flash('message', "{$stats['parsed']} rows loaded from file ({$stats['valid']} ready, {$stats['invalid']} require attention). Please review highlighted rows.");
        }
    }

    protected function updateSummaryMetrics(): void
    {
        $nonEmpty = array_filter($this->rows, fn($r) => !empty(trim($r['name'] ?? '')));
        $valid = count(array_filter($nonEmpty, fn($r) => !empty($r['is_valid'])));
        $invalid = count($nonEmpty) - $valid;

        if ($this->importSummary) {
            $this->importSummary['parsed'] = count($nonEmpty);
            $this->importSummary['valid'] = $valid;
            $this->importSummary['invalid'] = $invalid;
        }

        $this->totalImported = $valid;
        $this->totalFailed = $invalid;
    }

    public function saveAll()
    {
        $this->resetErrorBag();

        // 1. Filter out empty rows (where name is empty)
        $nonEmptyIndices = [];
        foreach ($this->rows as $index => $row) {
            if (!empty(trim($row['name'] ?? ''))) {
                $nonEmptyIndices[] = $index;
            }
        }

        if (empty($nonEmptyIndices)) {
            $this->addError('general', 'Please enter details for at least one medicine before saving.');
            return;
        }

        // 2. Perform in-list duplicate checks & DB duplicate checks & Category checks
        $namesSeen = [];
        $barcodesSeen = [];
        $batchesSeen = [];
        $hasDuplicateError = false;

        foreach ($nonEmptyIndices as $index) {
            $name = trim($this->rows[$index]['name']);
            $nameLower = strtolower($name);
            $barcode = trim($this->rows[$index]['barcode'] ?? '');
            $barcodeLower = strtolower($barcode);
            $rawCategory = trim($this->rows[$index]['category_name_raw'] ?? '');
            $categoryId = $this->rows[$index]['category_id'] ?? '';
            $batchNumber = trim($this->rows[$index]['batch_number'] ?? '');

            // Check if there was an invalid category from file
            if (empty($categoryId) && !empty($rawCategory)) {
                $rowNum = $index + 1;
                $this->addError("rows.{$index}.category_id", "Row {$rowNum}: Category \"{$rawCategory}\" does not exist.");
                $hasDuplicateError = true;
            }

            // Check duplicate name within the bulk list
            if (isset($namesSeen[$nameLower])) {
                $this->addError("rows.{$index}.name", "Duplicate medicine name '{$name}' in list.");
                $hasDuplicateError = true;
            } else {
                $namesSeen[$nameLower] = $index;
            }

            // Check duplicate barcode within the bulk list
            if ($barcode !== '') {
                if (isset($barcodesSeen[$barcodeLower])) {
                    $this->addError("rows.{$index}.barcode", "Duplicate barcode '{$barcode}' in list.");
                    $hasDuplicateError = true;
                } else {
                    $barcodesSeen[$barcodeLower] = $index;
                }
            }

            // Check duplicate batch within list for same medicine
            if ($batchNumber !== '') {
                $batchKey = $nameLower . '::' . strtolower($batchNumber);
                if (isset($batchesSeen[$batchKey])) {
                    $this->addError("rows.{$index}.batch_number", "Duplicate batch '{$batchNumber}' for '{$name}' in list.");
                    $hasDuplicateError = true;
                } else {
                    $batchesSeen[$batchKey] = $index;
                }
            }

            // Check duplicate name in Database
            $existingMed = Medicine::whereRaw('LOWER(name) = ?', [$nameLower])->first();
            if ($existingMed && !$this->updateExisting) {
                $this->addError("rows.{$index}.name", "Medicine '{$name}' already exists in database.");
                $hasDuplicateError = true;
            }

            // Check duplicate barcode in Database
            if ($barcode !== '') {
                $barcodeQuery = Medicine::where('barcode', $barcode);
                if ($this->updateExisting && $existingMed) {
                    $barcodeQuery->where('id', '!=', $existingMed->id);
                }
                if ($barcodeQuery->exists()) {
                    $this->addError("rows.{$index}.barcode", "Barcode '{$barcode}' already exists in database.");
                    $hasDuplicateError = true;
                }
            }

            // Check duplicate batch in Database for this medicine
            if ($existingMed && $batchNumber !== '') {
                $batchExists = MedicineBatch::where('medicine_id', $existingMed->id)
                    ->whereRaw('LOWER(batch_number) = ?', [strtolower($batchNumber)])
                    ->exists();
                if ($batchExists) {
                    $this->addError("rows.{$index}.batch_number", "Batch '{$batchNumber}' already exists for '{$name}' in database. Duplicate stock prevented.");
                    $hasDuplicateError = true;
                }
            }
        }

        // 3. Packaging Calculation & Hierarchy Validation
        $packagingResults = [];
        $hasPackagingError = false;
        $service = app(MedicineImportService::class);

        foreach ($nonEmptyIndices as $index) {
            $pkg = $service->calculateRowPackaging($this->rows[$index], $index + 1);
            $packagingResults[$index] = $pkg;

            // Reflect calculated updates into row for display
            $this->rows[$index]['base_unit'] = $pkg['base_unit'];
            $this->rows[$index]['dosage_unit'] = $pkg['dosage_unit'];
            $this->rows[$index]['strip_enabled'] = $pkg['strip_enabled'];
            $this->rows[$index]['pack_enabled'] = $pkg['pack_enabled'];
            $this->rows[$index]['box_enabled'] = $pkg['box_enabled'];
            $this->rows[$index]['stock_entry_unit'] = $pkg['stock_entry_unit'];
            $this->rows[$index]['converted_base_stock'] = $pkg['converted_base_stock'];
            $this->rows[$index]['packaging_display'] = $pkg['packaging_display'];
            $this->rows[$index]['strip_conversion_display'] = $pkg['strip_conversion_display'];
            $this->rows[$index]['pack_conversion_display'] = $pkg['pack_conversion_display'];
            $this->rows[$index]['sale_price_unit'] = $pkg['sale_price_unit'];
            $this->rows[$index]['base_unit_selling_price'] = $pkg['base_unit_selling_price'];
            $this->rows[$index]['strip_selling_price'] = $pkg['strip_selling_price'];
            $this->rows[$index]['pack_selling_price'] = $pkg['pack_selling_price'];
            $this->rows[$index]['purchase_price_unit'] = $pkg['purchase_price_unit'];
            $this->rows[$index]['base_unit_purchase_price'] = $pkg['base_unit_purchase_price'];
            $this->rows[$index]['strip_purchase_price'] = $pkg['strip_purchase_price'];
            $this->rows[$index]['pack_purchase_price'] = $pkg['pack_purchase_price'];
            if (!empty($pkg['expiry_date'])) {
                $this->rows[$index]['expiry_date'] = $pkg['expiry_date'];
            }

            if (!empty($pkg['errors'])) {
                foreach ($pkg['errors'] as $err) {
                    if (str_contains($err, 'Strip') || str_contains($err, 'strip')) {
                        $this->addError("rows.{$index}.units_per_strip", $err);
                    } elseif (str_contains($err, 'Pack') || str_contains($err, 'pack')) {
                        $this->addError("rows.{$index}.strips_per_pack", $err);
                    } elseif (str_contains($err, 'Expiry') || str_contains($err, 'expiry')) {
                        $this->addError("rows.{$index}.expiry_date", $err);
                    } else {
                        $this->addError("rows.{$index}.stock_entry_unit", $err);
                    }
                }
                $hasPackagingError = true;
            }
        }

        // 4. Perform standard validation on non-empty rows
        $rules = [];
        $attributes = [];

        foreach ($nonEmptyIndices as $index) {
            $rules["rows.{$index}.name"] = 'required|string|max:255';
            $rules["rows.{$index}.category_id"] = 'required|exists:categories,id';
            $rules["rows.{$index}.generic_name"] = 'nullable|string|max:255';
            $rules["rows.{$index}.brand"] = 'nullable|string|max:255';
            $rules["rows.{$index}.manufacturer"] = 'nullable|string|max:255';
            $rules["rows.{$index}.base_unit"] = 'required|string|max:100';
            $rules["rows.{$index}.unit_price"] = 'required|numeric|min:0';
            $rules["rows.{$index}.purchase_price"] = 'nullable|numeric|min:0';
            $rules["rows.{$index}.alert_quantity"] = 'nullable|integer|min:0';
            $rules["rows.{$index}.barcode"] = 'nullable|string|max:255';
            $rules["rows.{$index}.batch_number"] = 'nullable|string|max:255';
            $rules["rows.{$index}.expiry_date"] = 'nullable|date';
            $rules["rows.{$index}.supplier"] = 'nullable|string|max:255';

            $rowNum = $index + 1;
            $attributes["rows.{$index}.name"] = "Row {$rowNum} Medicine Name";
            $attributes["rows.{$index}.category_id"] = "Row {$rowNum} Category";
            $attributes["rows.{$index}.base_unit"] = "Row {$rowNum} Base Unit";
            $attributes["rows.{$index}.unit_price"] = "Row {$rowNum} Sale Price";
            $attributes["rows.{$index}.purchase_price"] = "Row {$rowNum} Purchase Price";
            $attributes["rows.{$index}.alert_quantity"] = "Row {$rowNum} Alert Quantity";
            $attributes["rows.{$index}.barcode"] = "Row {$rowNum} Barcode";
            $attributes["rows.{$index}.batch_number"] = "Row {$rowNum} Batch Number";
            $attributes["rows.{$index}.expiry_date"] = "Row {$rowNum} Expiry Date";
            $attributes["rows.{$index}.supplier"] = "Row {$rowNum} Supplier";
        }

        $validator = Validator::make(
            ['rows' => $this->rows],
            $rules,
            [],
            $attributes
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }
            return;
        }

        if ($hasDuplicateError || $hasPackagingError) {
            return;
        }

        // 5. Save inside Database Transaction
        try {
            DB::transaction(function () use ($nonEmptyIndices, $packagingResults) {
                foreach ($nonEmptyIndices as $index) {
                    $row = $this->rows[$index];
                    $pkg = $packagingResults[$index];

                    $name = trim($row['name']);
                    $baseUnitName = $pkg['base_unit'];
                    $baseSalePrice = (float) $pkg['base_unit_selling_price'];
                    $basePurchasePrice = $pkg['base_unit_purchase_price'] !== null ? (float) $pkg['base_unit_purchase_price'] : null;
                    $alertQty = ($row['alert_quantity'] !== '' && $row['alert_quantity'] !== null) ? (int) $row['alert_quantity'] : 10;
                    $barcode = !empty(trim($row['barcode'] ?? '')) ? trim($row['barcode']) : null;
                    $supplierName = !empty(trim($row['supplier'] ?? '')) ? trim($row['supplier']) : null;

                    // Supplier resolution
                    $supplierId = null;
                    if ($supplierName) {
                        $supplier = Supplier::firstOrCreate(
                            ['name' => $supplierName],
                            ['phone' => 'N/A', 'opening_balance' => 0]
                        );
                        $supplierId = $supplier->id;
                    }

                    // Base Unit in units table
                    $baseUnitSlug = Str::slug($baseUnitName);
                    $baseUnit = Unit::firstOrCreate(
                        ['unit_id' => $baseUnitSlug],
                        ['name' => $baseUnitName, 'symbol' => substr($baseUnitName, 0, 4), 'allow_decimal' => false, 'status' => 'active']
                    );

                    $primaryToSecondary = ($pkg['pack_enabled'] && (float) $pkg['strips_per_pack'] > 0) ? (float) $pkg['strips_per_pack'] : 1.0;
                    $secondaryToBase = ($pkg['strip_enabled'] && (float) $pkg['units_per_strip'] > 0) ? (float) $pkg['units_per_strip'] : 1.0;
                    $primaryUnitName = $pkg['pack_enabled'] ? 'Pack' : null;
                    $secondaryUnitName = $pkg['strip_enabled'] ? 'Strip' : null;

                    $medicineData = [
                        'category_id' => $row['category_id'],
                        'product_type' => 'medicine',
                        'name' => $name,
                        'generic_name' => !empty(trim($row['generic_name'] ?? '')) ? trim($row['generic_name']) : null,
                        'brand' => !empty(trim($row['brand'] ?? '')) ? trim($row['brand']) : null,
                        'dosage_unit' => $baseUnitName,
                        'base_unit_id' => $baseUnit->id,
                        'primary_unit' => $primaryUnitName,
                        'secondary_unit' => $secondaryUnitName,
                        'base_unit' => $baseUnitName,
                        'primary_unit_to_secondary' => $primaryToSecondary,
                        'secondary_unit_to_base' => $secondaryToBase,
                        'unit_price' => $baseSalePrice,
                        'purchase_price' => $basePurchasePrice,
                        'primary_unit_selling_price' => $pkg['pack_selling_price'],
                        'secondary_unit_selling_price' => $pkg['strip_selling_price'],
                        'base_unit_selling_price' => $baseSalePrice,
                        'manufacturer' => !empty(trim($row['manufacturer'] ?? '')) ? trim($row['manufacturer']) : null,
                        'barcode' => $barcode,
                        'alert_quantity' => $alertQty,
                        'reorder_level' => $alertQty,
                        'has_expiry' => true,
                        'track_batches' => true,
                        'status' => 'active',
                    ];

                    if ($this->updateExisting) {
                        $med = Medicine::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
                        if ($med) {
                            $med->update($medicineData);
                        } else {
                            $med = Medicine::create($medicineData);
                        }
                    } else {
                        $med = Medicine::create($medicineData);
                    }

                    // 1. Base Unit Packaging
                    $basePkg = MedicinePackaging::updateOrCreate(
                        [
                            'medicine_id' => $med->id,
                            'unit_id' => $baseUnit->id,
                        ],
                        [
                            'conversion_to_base' => 1.0,
                            'quantity_in_parent' => 1.0,
                            'parent_packaging_id' => null,
                            'display_name' => $baseUnitName,
                            'barcode' => $barcode,
                            'purchase_price' => $basePurchasePrice,
                            'sale_price' => $baseSalePrice,
                            'allow_purchase' => true,
                            'allow_sale' => true,
                            'status' => 'active',
                        ]
                    );

                    // 2. Strip Packaging
                    $secondaryPkg = null;
                    if ($pkg['strip_enabled']) {
                        $secUnitSlug = Str::slug('strip');
                        $secUnit = Unit::firstOrCreate(
                            ['unit_id' => $secUnitSlug],
                            ['name' => 'Strip', 'symbol' => 'Str', 'allow_decimal' => false, 'status' => 'active']
                        );

                        $secConversion = (float) $secondaryToBase;
                        $secondaryPkg = MedicinePackaging::updateOrCreate(
                            [
                                'medicine_id' => $med->id,
                                'unit_id' => $secUnit->id,
                            ],
                            [
                                'conversion_to_base' => $secConversion,
                                'quantity_in_parent' => $secondaryToBase,
                                'parent_packaging_id' => $basePkg->id,
                                'display_name' => "Strip ({$secondaryToBase} {$baseUnitName}s)",
                                'barcode' => null,
                                'purchase_price' => $pkg['strip_purchase_price'],
                                'sale_price' => $pkg['strip_selling_price'],
                                'allow_purchase' => true,
                                'allow_sale' => true,
                                'status' => 'active',
                            ]
                        );
                    } else {
                        $stripUnit = Unit::where('unit_id', 'strip')->first();
                        if ($stripUnit) {
                            MedicinePackaging::where('medicine_id', $med->id)->where('unit_id', $stripUnit->id)->delete();
                        }
                    }

                    // 3. Pack Packaging
                    $primaryPkg = null;
                    if ($pkg['pack_enabled']) {
                        $primUnitSlug = Str::slug('pack');
                        $primUnit = Unit::firstOrCreate(
                            ['unit_id' => $primUnitSlug],
                            ['name' => 'Pack', 'symbol' => 'Pck', 'allow_decimal' => false, 'status' => 'active']
                        );

                        $primConversion = (float) ($pkg['strip_enabled'] ? ($primaryToSecondary * $secondaryToBase) : $primaryToSecondary);
                        $parentPkg = $secondaryPkg ?? $basePkg;
                        $primaryPkg = MedicinePackaging::updateOrCreate(
                            [
                                'medicine_id' => $med->id,
                                'unit_id' => $primUnit->id,
                            ],
                            [
                                'conversion_to_base' => $primConversion,
                                'quantity_in_parent' => (float) $primaryToSecondary,
                                'parent_packaging_id' => $parentPkg->id,
                                'display_name' => "Pack ({$primConversion} {$baseUnitName}s)",
                                'barcode' => null,
                                'purchase_price' => $pkg['pack_purchase_price'],
                                'sale_price' => $pkg['pack_selling_price'],
                                'allow_purchase' => true,
                                'allow_sale' => true,
                                'status' => 'active',
                            ]
                        );
                    } else {
                        $packUnit = Unit::where('unit_id', 'pack')->first();
                        if ($packUnit) {
                            MedicinePackaging::where('medicine_id', $med->id)->where('unit_id', $packUnit->id)->delete();
                        }
                    }

                    // 4. Box / Carton Packaging (if enabled)
                    if ($pkg['box_enabled']) {
                        $boxUnitSlug = Str::slug('box');
                        $boxUnit = Unit::firstOrCreate(
                            ['unit_id' => $boxUnitSlug],
                            ['name' => 'Box', 'symbol' => 'Box', 'allow_decimal' => false, 'status' => 'active']
                        );
                        $boxConversion = (float) ($pkg['packs_per_box'] * ($pkg['pack_enabled'] ? $primConversion : ($pkg['strip_enabled'] ? $secondaryToBase : 1.0)));
                        $boxParentPkg = $primaryPkg ?? $secondaryPkg ?? $basePkg;
                        MedicinePackaging::updateOrCreate(
                            [
                                'medicine_id' => $med->id,
                                'unit_id' => $boxUnit->id,
                            ],
                            [
                                'conversion_to_base' => $boxConversion,
                                'quantity_in_parent' => (float) $pkg['packs_per_box'],
                                'parent_packaging_id' => $boxParentPkg->id,
                                'display_name' => "Box ({$boxConversion} {$baseUnitName}s)",
                                'barcode' => null,
                                'purchase_price' => $basePurchasePrice !== null ? round($basePurchasePrice * $boxConversion, 2) : null,
                                'sale_price' => round($baseSalePrice * $boxConversion, 2),
                                'allow_purchase' => true,
                                'allow_sale' => true,
                                'status' => 'active',
                            ]
                        );
                    }

                    // 5. Stock & Batch Management
                    $convertedBaseStock = (float) $pkg['converted_base_stock'];
                    $stockQtyInput = (float) $pkg['stock_quantity'];
                    $stockUnitName = $pkg['stock_entry_unit'];
                    $stockMultiplier = (float) $pkg['stock_multiplier'];

                    if ($convertedBaseStock > 0) {
                        $batchNum = !empty(trim($row['batch_number'] ?? ''))
                            ? trim($row['batch_number'])
                            : 'IMP-' . strtoupper(Str::random(6));

                        // Check if this batch already exists for this medicine (duplicate prevention)
                        $existingBatch = MedicineBatch::where('medicine_id', $med->id)
                            ->where('batch_number', $batchNum)
                            ->first();

                        if (!$existingBatch) {
                            $expiryDateVal = !empty($pkg['expiry_date'])
                                ? $pkg['expiry_date']
                                : now()->addYears(2)->toDateString();

                            $batch = MedicineBatch::create([
                                'medicine_id' => $med->id,
                                'supplier_id' => $supplierId,
                                'batch_number' => $batchNum,
                                'quantity' => $convertedBaseStock,
                                'purchase_price' => $basePurchasePrice,
                                'selling_price' => $baseSalePrice,
                                'purchase_price_per_base_unit' => $basePurchasePrice,
                                'selling_price_per_base_unit' => $baseSalePrice,
                                'expiry_date' => $expiryDateVal,
                                'status' => 'active',
                            ]);

                            app(StockLedgerService::class)->recordMovement(
                                medicineId: $med->id,
                                batchId: $batch->id,
                                type: 'OPENING_STOCK',
                                referenceId: null,
                                referenceType: null,
                                selectedUnitId: $baseUnit->id,
                                quantity: $stockQtyInput,
                                conversionToBase: $stockMultiplier,
                                baseQuantity: $convertedBaseStock,
                                userId: auth()->id() ?? 1,
                                notes: "Bulk import opening stock entered as {$stockQtyInput} {$stockUnitName} (converted to {$convertedBaseStock} {$baseUnitName}s)"
                            );
                        } else {
                            // Batch already exists, sync inventory
                            app(StockLedgerService::class)->syncInventory($med->id);
                        }
                    } else {
                        app(StockLedgerService::class)->syncInventory($med->id);
                    }
                }
            });

            $count = count($nonEmptyIndices);
            session()->flash('message', "{$count} medicines added successfully.");

            $this->importSummary = null;
            $this->isImportedPreview = false;

            // Reset rows back to 3 empty rows
            $this->rows = [];
            for ($i = 0; $i < 3; $i++) {
                $this->addRow();
            }

        } catch (\Exception $e) {
            $this->addError('general', 'An error occurred while saving medicines: ' . $e->getMessage());
        }
    }

    /**
     * Download Excel (.xlsx) template with sample rows and instructions sheet.
     */
    public function downloadTemplate()
    {
        $service = app(MedicineImportService::class);
        $samples = $service->getSampleTemplateRows();
        $instructions = $service->getTemplateInstructions();

        $sheets = new SheetCollection([
            'Medicines' => $samples,
            'Instructions' => $instructions,
        ]);

        return (new FastExcel($sheets))->download('medicines_bulk_import_template.xlsx');
    }

    /**
     * Download CSV template.
     */
    public function downloadCsvTemplate()
    {
        $service = app(MedicineImportService::class);
        $samples = $service->getSampleTemplateRows();

        return response()->streamDownload(function () use ($samples) {
            $handle = fopen('php://output', 'w');
            
            // Header row
            if ($samples->isNotEmpty()) {
                fputcsv($handle, array_keys($samples->first()));
            }

            // Data rows
            foreach ($samples as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, 'medicines_bulk_import_template.csv');
    }

    public function render()
    {
        $categories = Category::forProductType('medicine')->orderBy('name')->get();

        $standardMedicineProducts = [
            'Panadol 500mg', 'Panadol Extra', 'Panadol CF', 'Disprin 300mg', 'Brufen 400mg', 'Brufen DS',
            'Augmentin 625mg', 'Augmentin 1g', 'Flagyl 400mg', 'Arinac Forte', 'Flyxotic 500mg',
            'Risek 20mg', 'Risek 40mg', 'Kestine 10mg', 'Softin 10mg', 'Rigix 10mg', 'Gravinate 50mg',
            'Buscopan 10mg', 'Ponstan 500mg', 'Ponstan Forte', 'CaC 1000 Plus', 'Surbex Z', 'Neurobion',
            'Hydryllin Syrup', 'Polyfax Skin Ointment', 'Fastum Gel', 'Voltral Emulgel', 'Calamox 625mg',
            'Ceftriaxone 1g Injection', 'Amikacin 500mg Injection', 'ORS Orange Powder'
        ];
        $dbProducts = Medicine::pluck('name')->toArray();
        $suggestedProductNames = collect(array_merge($standardMedicineProducts, $dbProducts))->unique()->sort()->values();

        $standardBrands = [
            'GSK (GlaxoSmithKline)', 'Abbott Laboratories', 'Getz Pharma', 'The Searle Company',
            'Sanofi-Aventis', 'Sami Pharmaceuticals', 'Hilton Pharma', 'Pfizer', 'Novartis',
            'Bayer', 'Ferozsons Laboratories', 'CCL Pharmaceuticals', 'Bosch Pharmaceuticals',
            'PharmEvo', 'Highnoon Laboratories', 'Platinum Pharmaceuticals', 'AGP Limited'
        ];
        $dbBrands = Medicine::whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand')->toArray();
        $suggestedBrands = collect(array_merge($standardBrands, $dbBrands))->unique()->sort()->values();

        $standardGenerics = [
            'Paracetamol', 'Ibuprofen', 'Amoxicillin', 'Amoxicillin + Clavulanic Acid (Co-Amoxiclav)',
            'Ciprofloxacin', 'Omeprazole', 'Esomeprazole', 'Azithromycin', 'Metformin HCl',
            'Cefixime', 'Cefradine', 'Diclofenac Sodium', 'Diclofenac Potassium',
            'Loratadine', 'Cetirizine HCl', 'Levocetirizine', 'Montelukast Sodium',
            'Metronidazole', 'Doxycycline', 'Fluconazole', 'Amlodipine', 'Losartan Potassium',
            'Atorvastatin', 'Pantoprazole', 'Domperidone', 'Ondansetron', 'Tramadol HCl',
            'Ceftriaxone Sodium', 'Oral Rehydration Salts'
        ];
        $dbGenerics = Medicine::whereNotNull('generic_name')->where('generic_name', '!=', '')->distinct()->pluck('generic_name')->toArray();
        $suggestedGenerics = collect(array_merge($standardGenerics, $dbGenerics))->unique()->sort()->values();

        $standardManufacturers = [
            'GlaxoSmithKline (GSK) Pakistan Ltd', 'Abbott Laboratories Pakistan Ltd',
            'Getz Pharma (Pvt) Ltd', 'The Searle Company Ltd', 'Sanofi-Aventis Pakistan Ltd',
            'Sami Pharmaceuticals (Pvt) Ltd', 'Hilton Pharma (Pvt) Ltd', 'Pfizer Pakistan Ltd'
        ];
        $dbManufacturers = Medicine::whereNotNull('manufacturer')->where('manufacturer', '!=', '')->distinct()->pluck('manufacturer')->toArray();
        $suggestedManufacturers = collect(array_merge($standardManufacturers, $dbManufacturers))->unique()->sort()->values();

        $suggestedDosageUnits = [
            'Tablet', 'Capsule', 'Strip', 'Blister', 'Pack', 'Bottle', 'Syrup Bottle',
            'Injection', 'Vial', 'Ampoule', 'Tube', 'Sachet', 'Piece', 'Box', 'Carton'
        ];

        $existingSuppliers = Supplier::orderBy('name')->pluck('name')->toArray();

        return view('livewire.admin.bulk-add-medicine', [
            'categories' => $categories,
            'suggestedProductNames' => $suggestedProductNames,
            'suggestedBrands' => $suggestedBrands,
            'suggestedGenerics' => $suggestedGenerics,
            'suggestedManufacturers' => $suggestedManufacturers,
            'suggestedDosageUnits' => $suggestedDosageUnits,
            'existingSuppliers' => $existingSuppliers,
        ])->layout('layouts.app');
    }
}
