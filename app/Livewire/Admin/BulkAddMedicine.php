<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithFileUploads;

class BulkAddMedicine extends Component
{
    use WithFileUploads;

    public array $rows = [];
    public $importFile;

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
            'dosage_unit' => 'Tablet',
            'unit_price' => '',
            'purchase_price' => '',
            'alert_quantity' => '10',
            'barcode' => '',
        ];
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
        $hasDuplicateError = false;

        foreach ($nonEmptyIndices as $index) {
            $name = trim($this->rows[$index]['name']);
            $nameLower = strtolower($name);
            $barcode = trim($this->rows[$index]['barcode'] ?? '');
            $barcodeLower = strtolower($barcode);
            $rawCategory = trim($this->rows[$index]['category_name_raw'] ?? '');
            $categoryId = $this->rows[$index]['category_id'] ?? '';

            // Check if there was an invalid category from CSV
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

            // Check duplicate name in Database
            if (Medicine::where('name', $name)->exists()) {
                $this->addError("rows.{$index}.name", "Medicine '{$name}' already exists in database.");
                $hasDuplicateError = true;
            }

            // Check duplicate barcode in Database
            if ($barcode !== '' && Medicine::where('barcode', $barcode)->exists()) {
                $this->addError("rows.{$index}.barcode", "Barcode '{$barcode}' already exists in database.");
                $hasDuplicateError = true;
            }
        }

        // 3. Perform standard validation on non-empty rows
        $rules = [];
        $attributes = [];

        foreach ($nonEmptyIndices as $index) {
            $rules["rows.{$index}.name"] = 'required|string|max:255';
            $rules["rows.{$index}.category_id"] = 'required|exists:categories,id';
            $rules["rows.{$index}.generic_name"] = 'nullable|string|max:255';
            $rules["rows.{$index}.brand"] = 'nullable|string|max:255';
            $rules["rows.{$index}.manufacturer"] = 'nullable|string|max:255';
            $rules["rows.{$index}.dosage_unit"] = 'nullable|string|max:50';
            $rules["rows.{$index}.unit_price"] = 'required|numeric|min:0';
            $rules["rows.{$index}.purchase_price"] = 'nullable|numeric|min:0';
            $rules["rows.{$index}.alert_quantity"] = 'nullable|integer|min:0';
            $rules["rows.{$index}.barcode"] = 'nullable|string|max:255';

            $rowNum = $index + 1;
            $attributes["rows.{$index}.name"] = "Row {$rowNum} Medicine Name";
            $attributes["rows.{$index}.category_id"] = "Row {$rowNum} Category";
            $attributes["rows.{$index}.unit_price"] = "Row {$rowNum} Unit/Sale Price";
            $attributes["rows.{$index}.purchase_price"] = "Row {$rowNum} Purchase Price";
            $attributes["rows.{$index}.alert_quantity"] = "Row {$rowNum} Alert Quantity";
            $attributes["rows.{$index}.barcode"] = "Row {$rowNum} Barcode";
        }

        $validator = \Illuminate\Support\Facades\Validator::make(
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

        if ($hasDuplicateError) {
            return;
        }

        // 4. Save inside Database Transaction
        try {
            DB::transaction(function () use ($nonEmptyIndices) {
                foreach ($nonEmptyIndices as $index) {
                    $row = $this->rows[$index];
                    $name = trim($row['name']);
                    $dosageUnit = !empty(trim($row['dosage_unit'] ?? '')) ? trim($row['dosage_unit']) : 'Tablet';
                    $unitPrice = (float) $row['unit_price'];
                    $purchasePrice = ($row['purchase_price'] !== '' && $row['purchase_price'] !== null) ? (float) $row['purchase_price'] : null;
                    $alertQty = ($row['alert_quantity'] !== '' && $row['alert_quantity'] !== null) ? (int) $row['alert_quantity'] : 10;
                    $barcode = !empty(trim($row['barcode'] ?? '')) ? trim($row['barcode']) : null;

                    $baseUnitSlug = Str::slug($dosageUnit);
                    $baseUnit = \App\Models\Unit::firstOrCreate(
                        ['unit_id' => $baseUnitSlug],
                        ['name' => $dosageUnit, 'symbol' => substr($dosageUnit, 0, 4), 'allow_decimal' => false, 'status' => 'active']
                    );

                    $med = Medicine::create([
                        'category_id' => $row['category_id'],
                        'product_type' => 'medicine',
                        'name' => $name,
                        'generic_name' => !empty(trim($row['generic_name'] ?? '')) ? trim($row['generic_name']) : null,
                        'brand' => !empty(trim($row['brand'] ?? '')) ? trim($row['brand']) : null,
                        'dosage_unit' => $dosageUnit,
                        'base_unit_id' => $baseUnit->id,
                        'primary_unit' => null,
                        'secondary_unit' => null,
                        'base_unit' => $dosageUnit,
                        'primary_unit_to_secondary' => 1,
                        'secondary_unit_to_base' => 1,
                        'unit_price' => $unitPrice,
                        'purchase_price' => $purchasePrice,
                        'primary_unit_selling_price' => null,
                        'secondary_unit_selling_price' => null,
                        'base_unit_selling_price' => $unitPrice,
                        'manufacturer' => !empty(trim($row['manufacturer'] ?? '')) ? trim($row['manufacturer']) : null,
                        'barcode' => $barcode,
                        'alert_quantity' => $alertQty,
                        'reorder_level' => $alertQty,
                        'has_expiry' => true,
                        'track_batches' => true,
                        'status' => 'active',
                    ]);

                    \App\Models\MedicinePackaging::create([
                        'medicine_id' => $med->id,
                        'unit_id' => $baseUnit->id,
                        'conversion_to_base' => 1.0,
                        'quantity_in_parent' => 1.0,
                        'parent_packaging_id' => null,
                        'display_name' => $dosageUnit,
                        'barcode' => $barcode,
                        'purchase_price' => $purchasePrice,
                        'sale_price' => $unitPrice,
                        'allow_purchase' => true,
                        'allow_sale' => true,
                        'status' => 'active',
                    ]);

                    app(\App\Services\StockLedgerService::class)->syncInventory($med->id);
                }
            });

            $count = count($nonEmptyIndices);
            session()->flash('message', "{$count} medicines added successfully.");


            // Reset rows back to 3 empty rows
            $this->rows = [];
            for ($i = 0; $i < 3; $i++) {
                $this->addRow();
            }

        } catch (\Exception $e) {
            $this->addError('general', 'An error occurred while saving medicines: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Category', 'Generic Name', 'Brand', 'Manufacturer', 'Dosage Unit', 'Sale Price', 'Purchase Price', 'Alert Quantity', 'Barcode']);
            // Example row
            fputcsv($handle, ['Sample Medicine', 'Tablets & Capsules', 'Sample Generic', 'Sample Brand', 'Sample Mfg', 'Tablet', '10.50', '8.00', '10', '123456']);
            fclose($handle);
        }, 'medicines_template.csv');
    }

    public function importData()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        $path = $this->importFile->getRealPath();
        $file = fopen($path, 'r');
        $header = fgetcsv($file);

        $importedRows = 0;
        
        // Remove empty rows if any
        foreach ($this->rows as $index => $row) {
            if (empty(trim($row['name'] ?? ''))) {
                unset($this->rows[$index]);
            }
        }
        $this->rows = array_values($this->rows);

        // Map existing categories
        $categoryMap = [];
        foreach (\App\Models\Category::all() as $cat) {
            $categoryMap[strtolower(trim($cat->name))] = $cat->id;
        }

        while (($row = fgetcsv($file)) !== false) {
            $row = array_map(function ($value) {
                return mb_convert_encoding($value ?? '', 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
            }, $row);

            if (count($row) < 7) continue; // Skip incomplete rows
            if (empty(trim($row[0]))) continue;
            
            $csvCategory = trim($row[1] ?? '');
            $catKey = strtolower($csvCategory);
            $categoryId = '';
            $rawCategory = '';

            if ($csvCategory !== '') {
                if (isset($categoryMap[$catKey])) {
                    $categoryId = $categoryMap[$catKey];
                } else {
                    $rawCategory = $csvCategory;
                }
            }
            
            $this->rows[] = [
                'name' => trim($row[0] ?? ''),
                'category_id' => $categoryId,
                'category_name_raw' => $rawCategory,
                'generic_name' => trim($row[2] ?? ''),
                'brand' => trim($row[3] ?? ''),
                'manufacturer' => trim($row[4] ?? ''),
                'dosage_unit' => trim($row[5] ?? 'Tablet'),
                'unit_price' => trim($row[6] ?? ''),
                'purchase_price' => trim($row[7] ?? ''),
                'alert_quantity' => trim($row[8] ?? '10'),
                'barcode' => trim($row[9] ?? ''),
            ];
            $importedRows++;
        }
        fclose($file);

        $this->reset('importFile');
        
        if (empty($this->rows)) {
            $this->addRow();
        }

        session()->flash('message', "{$importedRows} rows loaded from CSV. Please review and click Save All.");
    }

    public function render()
    {
        $categories = Category::forProductType('medicine')->orderBy('name')->get();

        $standardMedicineProducts = [
            'Panadol 500mg', 'Panadol Extra', 'Panadol CF', 'Disprin 300mg', 'Brufen 400mg', 'Brufen DS',
            'Augmentin 625mg', 'Augmentin 1g', 'Flagyl 400mg', 'Arinac Forte', 'Flyxotic 500mg',
            'Risek 20mg', 'Risek 40mg', 'Kestine 10mg', 'Softin 10mg', 'Rigix 10mg', 'Gravinate 50mg',
            'Buscopan 10mg', 'Ponstan 500mg', 'Ponstan Forte', 'CaC 1000 Plus', 'Surbex Z', 'Neurobion',
            'Hydryllin Syrup', 'Polyfax Skin Ointment', 'Fastum Gel', 'Voltral Emulgel', 'Calamox 625mg'
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
            'Atorvastatin', 'Pantoprazole', 'Domperidone', 'Ondansetron', 'Tramadol HCl'
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
            'Tablet', 'Capsule', 'Syrup', 'Suspension', 'Injection', 'Cream', 'Ointment', 'Eye Drops', 'Ear Drops', 'Sachet', 'Piece', 'Bottle'
        ];

        return view('livewire.admin.bulk-add-medicine', [
            'categories' => $categories,
            'suggestedProductNames' => $suggestedProductNames,
            'suggestedBrands' => $suggestedBrands,
            'suggestedGenerics' => $suggestedGenerics,
            'suggestedManufacturers' => $suggestedManufacturers,
            'suggestedDosageUnits' => $suggestedDosageUnits,
        ])->layout('layouts.app');
    }
}
