<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;
use Rap2hpoutre\FastExcel\SheetCollection;

class MedicineImportService
{
    /**
     * Supported header synonyms mapped to internal field names.
     */
    protected array $headerSynonyms = [
        'name' => [
            'name', 'medicine name', 'med name', 'medicinename', 'medicine',
            'product name', 'productname', 'product', 'item name', 'itemname',
            'item', 'drug name', 'drugname', 'drug', 'title'
        ],
        'category' => [
            'category', 'category name', 'categoryname', 'cat', 'cat name',
            'medicine category', 'product category', 'group'
        ],
        'generic_name' => [
            'generic name', 'genericname', 'generic', 'salt', 'salt name',
            'formula', 'composition', 'active ingredient'
        ],
        'brand' => [
            'brand', 'brand name', 'brandname', 'company', 'company name'
        ],
        'manufacturer' => [
            'manufacturer', 'mfg', 'mfr', 'manufacturer name', 'mfg name',
            'producer', 'maker', 'vendor'
        ],
        'barcode' => [
            'barcode', 'bar code', 'code', 'upc', 'ean', 'sku'
        ],
        'dosage_unit' => [
            'dosage unit', 'dosageunit', 'unit', 'dosage', 'form',
            'dosage form', 'unit of measure', 'uom', 'pack unit',
            'base unit', 'base_unit', 'baseunit', 'base'
        ],
        'base_unit' => [
            'base unit', 'base_unit', 'baseunit', 'dosage unit', 'dosage_unit',
            'dosageunit', 'unit', 'dosage', 'form', 'dosage form', 'unit of measure',
            'uom', 'pack unit', 'base'
        ],
        'strip_enabled' => [
            'strip enabled', 'strip_enabled', 'stripenabled', 'has strip',
            'has_strip', 'hasstrip', 'strip', 'strip_allowed', 'strip allowed'
        ],
        'units_per_strip' => [
            'units per strip', 'units_per_strip', 'unitsperstrip', 'tablets per strip',
            'tablets_per_strip', 'capsules per strip', 'capsules_per_strip',
            'units/strip', 'units in strip', 'secondary_unit_to_base', 'strip conversion',
            'strip size', 'strip qty', 'tablets/strip', 'capsules/strip'
        ],
        'pack_enabled' => [
            'pack enabled', 'pack_enabled', 'packenabled', 'has pack',
            'has_pack', 'haspack', 'pack', 'pack_allowed', 'pack allowed'
        ],
        'strips_per_pack' => [
            'strips per pack', 'strips_per_pack', 'stripsperpack', 'units per pack',
            'units_per_pack', 'unitsperpack', 'bottles per pack', 'bottles_per_pack',
            'strips/pack', 'strips in pack', 'primary_unit_to_secondary', 'pack conversion',
            'pack size', 'pack qty', 'packs'
        ],
        'box_enabled' => [
            'box enabled', 'box_enabled', 'boxenabled', 'carton enabled',
            'carton_enabled', 'has box', 'has carton', 'has_box', 'has_carton',
            'box', 'carton'
        ],
        'packs_per_box' => [
            'packs per box', 'packs_per_box', 'packsperbox', 'boxes per carton',
            'units per box', 'units_per_box', 'units/box', 'items per box',
            'items_per_box', 'box size', 'carton size'
        ],
        'stock_quantity' => [
            'stock quantity', 'stock_quantity', 'stockquantity', 'quantity',
            'stock', 'initial stock', 'initial_stock', 'initialstock', 'qty',
            'opening stock', 'opening_stock', 'inventory', 'starting stock',
            'import quantity', 'import_quantity', 'import qty'
        ],
        'stock_entry_unit' => [
            'stock entry unit', 'stock_entry_unit', 'stockentryunit', 'stock unit',
            'stock_unit', 'stockunit', 'entry unit', 'entry_unit', 'entryunit',
            'initial stock unit', 'initial_stock_unit', 'unit of stock',
            'stock in unit', 'stock in', 'stock packaging', 'selected stock unit'
        ],
        'purchase_price' => [
            'purchase price', 'purchaseprice', 'cost price', 'costprice',
            'cost', 'buy price', 'buyprice', 'purchasing price', 'purchase rate'
        ],
        'purchase_price_unit' => [
            'purchase price unit', 'purchase_price_unit', 'purchasepriceunit',
            'buy price unit', 'buy price unit', 'cost price unit', 'cost unit',
            'purchase unit', 'purchase_unit', 'buying unit'
        ],
        'unit_price' => [
            'sale price', 'saleprice', 'unit price', 'unitprice', 'selling price',
            'sellingprice', 'price', 'retail price', 'retailprice', 'mrp',
            'sales price', 'rate', 'unit rate'
        ],
        'sale_price_unit' => [
            'sale price unit', 'sale_price_unit', 'salepriceunit', 'selling price unit',
            'sellingpriceunit', 'retail price unit', 'price unit', 'price_unit',
            'selling unit', 'sales unit'
        ],
        'alert_quantity' => [
            'alert quantity', 'alertquantity', 'alert qty', 'alertqty',
            'min quantity', 'min qty', 'reorder level', 'reorder quantity',
            'min stock', 'alert stock', 'minimum stock'
        ],
        'batch_number' => [
            'batch number', 'batch_number', 'batchnumber', 'batch', 'batch no',
            'batch_no', 'batch #', 'lot', 'lot number', 'lot no', 'lot #'
        ],
        'expiry_date' => [
            'expiry date', 'expiry_date', 'expirydate', 'exp date', 'exp_date',
            'exp', 'expiry', 'expiration date', 'expiration', 'exp date (yyyy-mm-dd)'
        ],
        'supplier' => [
            'supplier', 'supplier name', 'supplier_name', 'distributor',
            'distributor name', 'distributor_name', 'vendor', 'vendor name'
        ],
    ];

    /**
     * Parse an uploaded file or file path into normalized medicine rows and validation results.
     *
     * @param UploadedFile|string $file
     * @return array{
     *     success: bool,
     *     error: ?string,
     *     rows: array,
     *     row_errors: array,
     *     stats: array{parsed: int, valid: int, invalid: int, duplicates: int}
     * }
     */
    public function parseFile(UploadedFile|string $file, bool $updateExisting = false): array
    {
        try {
            $rawRows = $this->extractRawRows($file);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'Failed to parse file: ' . $e->getMessage(),
                'rows' => [],
                'row_errors' => [],
                'stats' => ['parsed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0],
            ];
        }

        if (empty($rawRows)) {
            $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
            $hasContent = file_exists($path) && filesize($path) > 0;
            return [
                'success' => false,
                'error' => $hasContent
                    ? 'The uploaded file contains headers but no data rows.'
                    : 'The uploaded file is empty.',
                'rows' => [],
                'row_errors' => [],
                'stats' => ['parsed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0],
            ];
        }

        return $this->parseRawData($rawRows, $updateExisting);
    }

    /**
     * Parse raw row data (either associative or positional arrays).
     */
    public function parseRawData(array $rawRows, bool $updateExisting = false): array
    {
        if (empty($rawRows)) {
            return [
                'success' => false,
                'error' => 'No data provided to parse.',
                'rows' => [],
                'row_errors' => [],
                'stats' => ['parsed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0],
            ];
        }

        $firstRow = reset($rawRows);
        $firstKeys = array_keys($firstRow);
        $isPositional = !empty($firstKeys) && is_int($firstKeys[0]);

        if ($isPositional) {
            if (count($firstKeys) >= 21) {
                // 21-column template
                $headerMap = [
                    'name' => 0,
                    'category' => 1,
                    'generic_name' => 2,
                    'brand' => 3,
                    'manufacturer' => 4,
                    'barcode' => 5,
                    'base_unit' => 6,
                    'dosage_unit' => 6,
                    'strip_enabled' => 7,
                    'units_per_strip' => 8,
                    'pack_enabled' => 9,
                    'strips_per_pack' => 10,
                    'stock_quantity' => 11,
                    'stock_entry_unit' => 12,
                    'purchase_price' => 13,
                    'purchase_price_unit' => 14,
                    'unit_price' => 15,
                    'sale_price_unit' => 16,
                    'alert_quantity' => 17,
                    'batch_number' => 18,
                    'expiry_date' => 19,
                    'supplier' => 20,
                ];
            } elseif (count($firstKeys) >= 16) {
                // Legacy 16-column template
                $headerMap = [
                    'name' => 0,
                    'category' => 1,
                    'generic_name' => 2,
                    'brand' => 3,
                    'manufacturer' => 4,
                    'base_unit' => 5,
                    'dosage_unit' => 5,
                    'strip_enabled' => 6,
                    'units_per_strip' => 7,
                    'pack_enabled' => 8,
                    'strips_per_pack' => 9,
                    'stock_quantity' => 10,
                    'stock_entry_unit' => 11,
                    'unit_price' => 12,
                    'purchase_price' => 13,
                    'alert_quantity' => 14,
                    'barcode' => 15,
                ];
            } else {
                // Legacy 10-column template
                $headerMap = [
                    'name' => 0,
                    'category' => 1,
                    'generic_name' => 2,
                    'brand' => 3,
                    'manufacturer' => 4,
                    'dosage_unit' => 5,
                    'base_unit' => 5,
                    'unit_price' => 6,
                    'purchase_price' => 7,
                    'alert_quantity' => 8,
                    'barcode' => 9,
                ];
            }
        } else {
            $headerMap = $this->detectHeaderMapping($firstKeys);
            if (!isset($headerMap['name'])) {
                return [
                    'success' => false,
                    'error' => 'Missing required column: "Medicine Name" (or "Product Name"). Please check your file headers.',
                    'rows' => [],
                    'row_errors' => [],
                    'stats' => ['parsed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0],
                ];
            }
        }

        // Cache existing categories for fast case-insensitive & slug lookup
        $categories = Category::forProductType('medicine')->get();
        $categoryLookup = $this->buildCategoryLookup($categories);

        $parsedRows = [];
        $rowErrors = [];
        $namesSeen = [];
        $barcodesSeen = [];
        $batchesSeen = [];
        $validCount = 0;
        $invalidCount = 0;
        $duplicatesCount = 0;

        foreach ($rawRows as $index => $row) {
            // Check if the entire row is empty
            if ($this->isRowBlank($row)) {
                continue;
            }

            $mapped = $this->mapRowFields($row, $headerMap);
            $rowIndex = count($parsedRows);
            $rowNumber = $index + 2; // Accounting for 1-indexed file + 1 header row

            $errors = [];

            // 1. Validate Medicine Name
            $name = trim((string) ($mapped['name'] ?? ''));
            $existingMed = null;
            if ($name === '') {
                $errors[] = "Row {$rowNumber}: Medicine Name is required.";
            } else {
                $nameLower = strtolower($name);
                if (isset($namesSeen[$nameLower])) {
                    $errors[] = "Row {$rowNumber}: Duplicate medicine name '{$name}' in file.";
                    $duplicatesCount++;
                } else {
                    $namesSeen[$nameLower] = $rowIndex;
                }

                $existingMed = Medicine::whereRaw('LOWER(name) = ?', [$nameLower])->first();
                if (!$updateExisting && $existingMed) {
                    $errors[] = "Row {$rowNumber}: Medicine '{$name}' already exists in database.";
                    $duplicatesCount++;
                }
            }

            // 2. Resolve Category
            $rawCat = trim((string) ($mapped['category'] ?? ''));
            $resolvedCategoryId = '';
            if ($rawCat !== '') {
                $resolvedCategoryId = $this->resolveCategory($rawCat, $categoryLookup);
                if (!$resolvedCategoryId) {
                    $errors[] = "Row {$rowNumber}: Category \"{$rawCat}\" does not exist.";
                }
            } else {
                $errors[] = "Row {$rowNumber}: Category is required.";
            }

            // 3. Validate Sale Price (unit_price)
            $rawSalePrice = trim((string) ($mapped['unit_price'] ?? ''));
            $salePrice = '';
            if ($rawSalePrice === '') {
                $errors[] = "Row {$rowNumber}: Sale Price is required.";
            } else {
                $cleanedSalePrice = $this->sanitizePrice($rawSalePrice);
                if ($cleanedSalePrice === null || $cleanedSalePrice < 0) {
                    $errors[] = "Row {$rowNumber}: Sale Price must be a valid non-negative number.";
                    $salePrice = $rawSalePrice;
                } else {
                    $salePrice = number_format($cleanedSalePrice, 2, '.', '');
                }
            }

            // 4. Validate Purchase Price
            $rawPurchasePrice = trim((string) ($mapped['purchase_price'] ?? ''));
            $purchasePrice = '';
            if ($rawPurchasePrice !== '') {
                $cleanedPurchasePrice = $this->sanitizePrice($rawPurchasePrice);
                if ($cleanedPurchasePrice === null || $cleanedPurchasePrice < 0) {
                    $errors[] = "Row {$rowNumber}: Purchase Price must be a valid non-negative number.";
                    $purchasePrice = $rawPurchasePrice;
                } else {
                    $purchasePrice = number_format($cleanedPurchasePrice, 2, '.', '');
                }
            }

            // 5. Alert Quantity
            $rawAlertQty = trim((string) ($mapped['alert_quantity'] ?? ''));
            $alertQty = '10';
            if ($rawAlertQty !== '') {
                $cleanedQty = preg_replace('/[^\d]/', '', $rawAlertQty);
                if ($cleanedQty !== '' && is_numeric($cleanedQty)) {
                    $alertQty = (string) (int) $cleanedQty;
                }
            }

            // 6. Barcode & Duplicates
            $barcode = trim((string) ($mapped['barcode'] ?? ''));
            if (is_numeric($barcode) && stripos($barcode, 'e') !== false) {
                $barcode = number_format((float) $barcode, 0, '', '');
            }
            if ($barcode !== '') {
                $barcodeLower = strtolower($barcode);
                if (isset($barcodesSeen[$barcodeLower])) {
                    $errors[] = "Row {$rowNumber}: Duplicate barcode '{$barcode}' in file.";
                    $duplicatesCount++;
                } else {
                    $barcodesSeen[$barcodeLower] = $rowIndex;
                }

                $barcodeQuery = Medicine::where('barcode', $barcode);
                if ($updateExisting && $name !== '') {
                    $barcodeQuery->whereRaw('LOWER(name) != ?', [strtolower($name)]);
                }
                if ($barcodeQuery->exists()) {
                    $errors[] = "Row {$rowNumber}: Barcode '{$barcode}' already exists in database.";
                    $duplicatesCount++;
                }
            }

            // 7. Generic Name, Brand, Manufacturer, Supplier
            $genericName = trim((string) ($mapped['generic_name'] ?? ''));
            $brand = trim((string) ($mapped['brand'] ?? ''));
            $manufacturer = trim((string) ($mapped['manufacturer'] ?? ''));
            $supplier = trim((string) ($mapped['supplier'] ?? ''));

            // 8. Batch Number Validation & Duplicate Detection
            $batchNumber = trim((string) ($mapped['batch_number'] ?? ''));
            if ($batchNumber !== '' && $name !== '') {
                $batchKey = strtolower($name) . '::' . strtolower($batchNumber);
                if (isset($batchesSeen[$batchKey])) {
                    $errors[] = "Row {$rowNumber}: Duplicate batch number '{$batchNumber}' for medicine '{$name}' in file.";
                    $duplicatesCount++;
                } else {
                    $batchesSeen[$batchKey] = $rowIndex;
                }

                if ($existingMed) {
                    $batchExists = MedicineBatch::where('medicine_id', $existingMed->id)
                        ->whereRaw('LOWER(batch_number) = ?', [strtolower($batchNumber)])
                        ->exists();
                    if ($batchExists) {
                        $errors[] = "Row {$rowNumber}: Batch '{$batchNumber}' already exists for medicine '{$name}' in database. Duplicate stock prevented.";
                        $duplicatesCount++;
                    }
                }
            }

            // 9. Packaging Hierarchy & Automatic Conversion Calculation
            $pkgResult = $this->calculateRowPackaging($mapped, $rowNumber);
            if (!empty($pkgResult['errors'])) {
                foreach ($pkgResult['errors'] as $pkgErr) {
                    $errors[] = $pkgErr;
                }
            }

            $formattedRow = [
                'name' => $name,
                'category_id' => $resolvedCategoryId ?: '',
                'category_name_raw' => $resolvedCategoryId ? '' : $rawCat,
                'generic_name' => $genericName,
                'brand' => $brand,
                'manufacturer' => $manufacturer,
                'barcode' => $barcode,
                'base_unit' => $pkgResult['base_unit'],
                'dosage_unit' => $pkgResult['dosage_unit'],
                'strip_enabled' => $pkgResult['strip_enabled'],
                'units_per_strip' => $pkgResult['units_per_strip'],
                'pack_enabled' => $pkgResult['pack_enabled'],
                'strips_per_pack' => $pkgResult['strips_per_pack'],
                'box_enabled' => $pkgResult['box_enabled'],
                'packs_per_box' => $pkgResult['packs_per_box'],
                'stock_quantity' => $pkgResult['stock_quantity'],
                'stock_entry_unit' => $pkgResult['stock_entry_unit'],
                'converted_base_stock' => $pkgResult['converted_base_stock'],
                'packaging_display' => $pkgResult['packaging_display'],
                'strip_conversion_display' => $pkgResult['strip_conversion_display'],
                'pack_conversion_display' => $pkgResult['pack_conversion_display'],
                'unit_price' => $salePrice,
                'sale_price_unit' => $pkgResult['sale_price_unit'],
                'base_unit_selling_price' => $pkgResult['base_unit_selling_price'],
                'strip_selling_price' => $pkgResult['strip_selling_price'],
                'pack_selling_price' => $pkgResult['pack_selling_price'],
                'purchase_price' => $purchasePrice,
                'purchase_price_unit' => $pkgResult['purchase_price_unit'],
                'base_unit_purchase_price' => $pkgResult['base_unit_purchase_price'],
                'strip_purchase_price' => $pkgResult['strip_purchase_price'],
                'pack_purchase_price' => $pkgResult['pack_purchase_price'],
                'alert_quantity' => $alertQty,
                'batch_number' => $batchNumber,
                'expiry_date' => $pkgResult['expiry_date'],
                'supplier' => $supplier,
                'is_valid' => empty($errors),
                'validation_errors' => $errors,
                'allowed_units' => $pkgResult['allowed_units'],
            ];

            $parsedRows[] = $formattedRow;

            if (!empty($errors)) {
                $invalidCount++;
                $rowErrors[$rowIndex] = $errors;
            } else {
                $validCount++;
            }
        }

        if (empty($parsedRows)) {
            return [
                'success' => false,
                'error' => 'The uploaded file contains headers but no valid medicine data rows.',
                'rows' => [],
                'row_errors' => [],
                'stats' => ['parsed' => 0, 'valid' => 0, 'invalid' => 0, 'duplicates' => 0],
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'rows' => $parsedRows,
            'row_errors' => $rowErrors,
            'stats' => [
                'parsed' => count($parsedRows),
                'valid' => $validCount,
                'invalid' => $invalidCount,
                'duplicates' => $duplicatesCount,
            ],
        ];
    }

    /**
     * Extract raw rows from file using FastExcel with fallback to CSV parser.
     */
    protected function extractRawRows(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $originalExt = $file instanceof UploadedFile
            ? strtolower((string) $file->getClientOriginalExtension())
            : strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (!file_exists($path) || filesize($path) === 0) {
            return [];
        }

        // For XLSX/XLS, use FastExcel on Sheet 1 (Medicines)
        if (in_array($originalExt, ['xlsx', 'xls', 'ods'])) {
            $imported = (new FastExcel())->sheet(1)->import($file);
            return $imported instanceof Collection ? $imported->toArray() : (array) $imported;
        }

        // For CSV/TXT: attempt FastExcel first, fallback to native fgetcsv
        try {
            $imported = (new FastExcel())->import($file);
            if ($imported instanceof Collection && $imported->isNotEmpty()) {
                return $imported->toArray();
            }
        } catch (\Throwable) {
            // FastExcel failed, fall back to native CSV parser
        }

        return $this->parseCsvNative($path);
    }

    /**
     * Native CSV parser with automatic delimiter detection, UTF-8 BOM removal, and quoted field handling.
     */
    protected function parseCsvNative(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Strip UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Convert potential ISO-8859-1 or Windows-1252 to UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1, Windows-1252');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Detect delimiter: comma, semicolon, tab, pipe
        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $delimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $d) {
            $count = substr_count($firstLine, $d);
            if ($count > $maxCount) {
                $maxCount = $count;
                $delimiter = $d;
            }
        }

        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);

        $header = fgetcsv($handle, 0, $delimiter);
        if ($header === false || empty($header)) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($h) {
            return trim(preg_replace('/[\x{FEFF}\x{200B}]/u', '', (string) $h));
        }, $header);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($this->isRowBlank($row)) {
                continue;
            }

            $namedRow = [];
            foreach ($header as $colIdx => $colName) {
                $namedRow[$colName] = $row[$colIdx] ?? '';
            }
            $rows[] = $namedRow;
        }

        fclose($handle);
        return $rows;
    }

    /**
     * Map raw header columns to recognized canonical attributes.
     */
    protected function detectHeaderMapping(array $rawHeaders): array
    {
        $mapping = [];

        foreach ($rawHeaders as $header) {
            $normalized = $this->normalizeHeaderString($header);
            if ($normalized === '') {
                continue;
            }

            foreach ($this->headerSynonyms as $field => $synonyms) {
                if (isset($mapping[$field])) {
                    continue; // Already mapped this field
                }

                foreach ($synonyms as $synonym) {
                    $cleanSynonym = $this->normalizeHeaderString($synonym);
                    if ($normalized === $cleanSynonym) {
                        $mapping[$field] = $header;
                        break;
                    }
                }
            }
        }

        return $mapping;
    }

    /**
     * Normalize a header string for comparison: trim, strip BOM, remove special chars, lowercase.
     */
    protected function normalizeHeaderString(string $header): string
    {
        $clean = preg_replace('/[\x{FEFF}\x{200B}]/u', '', $header);
        $clean = strtolower(trim((string) $clean));
        $clean = str_replace(['_', '-'], ' ', $clean);
        $clean = preg_replace('/[^a-z0-9 ]/', '', $clean);
        return preg_replace('/\s+/', ' ', $clean);
    }

    /**
     * Map a raw row's values according to detected header map.
     */
    protected function mapRowFields(array $row, array $headerMap): array
    {
        $mapped = [];
        foreach ($headerMap as $field => $headerKey) {
            $mapped[$field] = $row[$headerKey] ?? null;
        }
        return $mapped;
    }

    /**
     * Build category lookup dictionary for fast resolution.
     */
    protected function buildCategoryLookup(Collection $categories): array
    {
        $lookup = [
            'by_id' => [],
            'by_name' => [],
            'by_slug' => [],
        ];

        foreach ($categories as $cat) {
            $lookup['by_id'][(string) $cat->id] = $cat->id;

            $nameKey = strtolower(trim($cat->name));
            $lookup['by_name'][$nameKey] = $cat->id;

            $slug = $cat->slug ? strtolower(trim($cat->slug)) : Str::slug($cat->name);
            $lookup['by_slug'][$slug] = $cat->id;
            $lookup['by_slug'][Str::slug($cat->name)] = $cat->id;
        }

        return $lookup;
    }

    /**
     * Resolve a category string against existing categories without creating duplicates.
     */
    protected function resolveCategory(string $rawCategory, array $lookup): ?int
    {
        $trimmed = trim($rawCategory);
        if ($trimmed === '') {
            return null;
        }

        // 1. Direct ID match if numeric
        if (is_numeric($trimmed) && isset($lookup['by_id'][$trimmed])) {
            return $lookup['by_id'][$trimmed];
        }

        // 2. Exact name match (case-insensitive)
        $lowerName = strtolower($trimmed);
        if (isset($lookup['by_name'][$lowerName])) {
            return $lookup['by_name'][$lowerName];
        }

        // 3. Slug match
        $slug = Str::slug($trimmed);
        if ($slug !== '' && isset($lookup['by_slug'][$slug])) {
            return $lookup['by_slug'][$slug];
        }

        // 4. Safe partial match (e.g., "Tablets" matches "tablets-capsules")
        foreach ($lookup['by_slug'] as $catSlug => $id) {
            if ($slug !== '' && (str_starts_with($catSlug, $slug) || str_starts_with($slug, $catSlug))) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Sanitize and extract float price from formatted string ($12.50, 1,200.00, etc.).
     */
    public function sanitizePrice(string $rawPrice): ?float
    {
        $trimmed = trim($rawPrice);
        if ($trimmed === '') {
            return null;
        }

        // Remove currency symbols and non-numeric/non-decimal characters except minus and period
        $cleaned = preg_replace('/[^\d.,-]/', '', $trimmed);
        if ($cleaned === '' || $cleaned === '-') {
            return null;
        }

        // Handle commas as thousand separators or decimal separators
        if (str_contains($cleaned, ',') && str_contains($cleaned, '.')) {
            $cleaned = str_replace(',', '', $cleaned);
        } elseif (str_contains($cleaned, ',') && !str_contains($cleaned, '.')) {
            $parts = explode(',', $cleaned);
            if (count($parts) === 2 && strlen($parts[1]) <= 2) {
                $cleaned = $parts[0] . '.' . $parts[1];
            } else {
                $cleaned = str_replace(',', '', $cleaned);
            }
        }

        if (!is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }

    /**
     * Check if an imported row is completely blank.
     */
    protected function isRowBlank(array $row): bool
    {
        foreach ($row as $val) {
            if (trim((string) $val) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Parse loose boolean values (Yes/No, Y/N, true/false, 1/0, Enabled/Disabled).
     */
    public function parseBoolean($val): ?bool
    {
        if ($val === null || $val === '') {
            return null;
        }
        if (is_bool($val)) {
            return $val;
        }
        $v = strtolower(trim((string) $val));
        if (in_array($v, ['yes', 'y', 'true', '1', 'enabled', 'enable'])) {
            return true;
        }
        if (in_array($v, ['no', 'n', 'false', '0', 'disabled', 'disable'])) {
            return false;
        }
        return null;
    }

    /**
     * Parse loose date string or Excel date serial to Y-m-d.
     */
    public function parseExpiryDate($rawDate): ?string
    {
        if (empty($rawDate)) {
            return null;
        }

        if ($rawDate instanceof \DateTimeInterface) {
            return $rawDate->format('Y-m-d');
        }

        $str = trim((string) $rawDate);
        if ($str === '') {
            return null;
        }

        // Check if Excel serial integer (e.g. 45658)
        if (is_numeric($str) && (float)$str > 30000 && (float)$str < 100000) {
            try {
                return Carbon::create(1899, 12, 30)->addDays((int)$str)->format('Y-m-d');
            } catch (\Throwable) {}
        }

        $formats = [
            'Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y', 'Y/m/d',
            'd.m.Y', 'm.d.Y', 'Y.m.d', 'M Y', 'F Y', 'm/Y', 'm-Y', 'Y-m'
        ];

        foreach ($formats as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $str);
                if ($parsed !== false) {
                    if (in_array($fmt, ['M Y', 'F Y', 'm/Y', 'm-Y', 'Y-m'])) {
                        return $parsed->endOfMonth()->format('Y-m-d');
                    }
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {}
        }

        try {
            $carbon = Carbon::parse($str);
            return $carbon->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Calculate packaging conversions, validate hierarchy, and compute base stock.
     *
     * @param array $data
     * @param int|null $rowNumber
     * @return array
     */
    public function calculateRowPackaging(array $data, ?int $rowNumber = null): array
    {
        $errors = [];
        $prefix = $rowNumber ? "Row {$rowNumber}: " : "";

        // 1. Base Unit
        $baseUnitRaw = trim((string)($data['base_unit'] ?? $data['dosage_unit'] ?? ''));
        $baseUnit = $baseUnitRaw !== '' ? ucfirst(strtolower($baseUnitRaw)) : 'Tablet';

        // 2. Strip Configuration
        $stripEnabledRaw = $data['strip_enabled'] ?? null;
        $stripEnabled = $this->parseBoolean($stripEnabledRaw);
        $unitsPerStripRaw = trim((string)($data['units_per_strip'] ?? ''));
        $unitsPerStrip = is_numeric($unitsPerStripRaw) ? (float)$unitsPerStripRaw : null;

        if ($unitsPerStripRaw !== '' && ($unitsPerStrip === null || $unitsPerStrip <= 0)) {
            $errors[] = "{$prefix}Units per Strip must be a positive number greater than 0.";
        }

        if ($stripEnabled === true) {
            if ($unitsPerStripRaw === '') {
                $errors[] = "{$prefix}Units per Strip is required when Strip is enabled.";
            } elseif ($unitsPerStrip === null || $unitsPerStrip <= 0) {
                // already recorded above
            }
        } elseif ($stripEnabled === null) {
            // Auto-detect if units_per_strip was specified > 1
            if ($unitsPerStrip !== null && $unitsPerStrip > 1) {
                $stripEnabled = true;
            } else {
                $stripEnabled = false;
            }
        } else {
            $stripEnabled = false;
        }

        // 3. Pack Configuration
        $packEnabledRaw = $data['pack_enabled'] ?? null;
        $packEnabled = $this->parseBoolean($packEnabledRaw);
        $stripsPerPackRaw = trim((string)($data['strips_per_pack'] ?? ''));
        $stripsPerPack = is_numeric($stripsPerPackRaw) ? (float)$stripsPerPackRaw : null;

        if ($stripsPerPackRaw !== '' && ($stripsPerPack === null || $stripsPerPack <= 0)) {
            $errors[] = "{$prefix}Strips/Units per Pack must be a positive number greater than 0.";
        }

        if ($packEnabled === true) {
            if ($stripsPerPackRaw === '') {
                $errors[] = "{$prefix}Strips/Units per Pack is required when Pack is enabled.";
            } elseif ($stripsPerPack === null || $stripsPerPack <= 0) {
                // already recorded above
            }
        } elseif ($packEnabled === null) {
            if ($stripsPerPack !== null && $stripsPerPack > 1) {
                $packEnabled = true;
            } else {
                $packEnabled = false;
            }
        } else {
            $packEnabled = false;
        }

        // 4. Box / Carton Configuration (if applicable)
        $boxEnabledRaw = $data['box_enabled'] ?? null;
        $boxEnabled = $this->parseBoolean($boxEnabledRaw);
        $packsPerBoxRaw = trim((string)($data['packs_per_box'] ?? ''));
        $packsPerBox = is_numeric($packsPerBoxRaw) ? (float)$packsPerBoxRaw : null;

        if ($packsPerBoxRaw !== '' && ($packsPerBox === null || $packsPerBox <= 0)) {
            $errors[] = "{$prefix}Packs per Box must be a positive number greater than 0.";
        }
        if ($boxEnabled === true && $packsPerBoxRaw === '') {
            $errors[] = "{$prefix}Packs per Box is required when Box is enabled.";
        }
        $boxEnabled = $boxEnabled === true && $packsPerBox > 0;

        // 5. Conversions Calculation
        $secConversion = ($stripEnabled && $unitsPerStrip > 0) ? (float)$unitsPerStrip : 1.0;

        if ($packEnabled && $stripsPerPack > 0) {
            $primConversion = $stripEnabled
                ? (float)($stripsPerPack * $secConversion)
                : (float)$stripsPerPack;
        } else {
            $primConversion = 1.0;
        }

        $boxConversion = ($boxEnabled && $packsPerBox > 0)
            ? (float)($packsPerBox * ($packEnabled ? $primConversion : ($stripEnabled ? $secConversion : 1.0)))
            : 1.0;

        // Display descriptions
        $stripDisplay = $stripEnabled ? "1 Strip = {$unitsPerStrip} {$baseUnit}s" : "No Strip";
        if ($packEnabled) {
            $stripDisplayNoun = $stripEnabled ? "{$stripsPerPack} Strips" : "{$stripsPerPack} {$baseUnit}s";
            $packDisplay = "1 Pack = {$stripDisplayNoun} ({$primConversion} {$baseUnit}s)";
        } else {
            $packDisplay = "No Pack";
        }

        if ($stripEnabled && $packEnabled) {
            $packagingDisplay = "1 Strip = {$unitsPerStrip} {$baseUnit}s | 1 Pack = {$stripsPerPack} Strips ({$primConversion} {$baseUnit}s)";
        } elseif ($packEnabled) {
            $packagingDisplay = "1 Pack = {$stripsPerPack} {$baseUnit}s";
        } elseif ($stripEnabled) {
            $packagingDisplay = "1 Strip = {$unitsPerStrip} {$baseUnit}s";
        } else {
            $packagingDisplay = "Base only (1 {$baseUnit})";
        }

        // 6. Stock Quantity
        $stockQtyRaw = trim((string)($data['stock_quantity'] ?? $data['quantity'] ?? ''));
        $stockQty = 0.0;
        if ($stockQtyRaw !== '') {
            if (!is_numeric($stockQtyRaw) || (float)$stockQtyRaw < 0) {
                $errors[] = "{$prefix}Stock Quantity must be a valid non-negative number.";
            } else {
                $stockQty = (float)$stockQtyRaw;
            }
        }

        // 7. Allowed Units & Stock Entry Unit Resolution
        $allowedUnits = [
            'base' => $baseUnit,
            strtolower($baseUnit) => $baseUnit,
            strtolower(Str::plural($baseUnit)) => $baseUnit,
        ];
        if ($stripEnabled) {
            $allowedUnits['strip'] = 'Strip';
            $allowedUnits['strips'] = 'Strip';
            $allowedUnits['secondary'] = 'Strip';
        }
        if ($packEnabled) {
            $allowedUnits['pack'] = 'Pack';
            $allowedUnits['packs'] = 'Pack';
            $allowedUnits['primary'] = 'Pack';
        }
        if ($boxEnabled) {
            $allowedUnits['box'] = 'Box';
            $allowedUnits['boxes'] = 'Box';
            $allowedUnits['carton'] = 'Box';
            $allowedUnits['cartons'] = 'Box';
        }

        $rawEntryUnit = trim((string)($data['stock_entry_unit'] ?? $data['stock_unit'] ?? ''));
        $resolvedStockUnit = $baseUnit;
        $stockMultiplier = 1.0;

        if ($rawEntryUnit !== '') {
            $cleanEntryUnit = strtolower($rawEntryUnit);
            if (isset($allowedUnits[$cleanEntryUnit])) {
                $resolvedStockUnit = $allowedUnits[$cleanEntryUnit];
            } else {
                if (in_array($cleanEntryUnit, ['strip', 'strips', 'secondary']) && !$stripEnabled) {
                    $errors[] = "{$prefix}Stock unit 'Strip' is not enabled in the packaging configuration for this medicine.";
                } elseif (in_array($cleanEntryUnit, ['pack', 'packs', 'primary']) && !$packEnabled) {
                    $errors[] = "{$prefix}Stock unit 'Pack' is not enabled in the packaging configuration for this medicine.";
                } elseif (in_array($cleanEntryUnit, ['box', 'boxes', 'carton', 'cartons']) && !$boxEnabled) {
                    $errors[] = "{$prefix}Stock unit 'Box' is not enabled in the packaging configuration for this medicine.";
                } else {
                    $validList = implode(', ', array_unique(array_values($allowedUnits)));
                    $errors[] = "{$prefix}Stock unit '{$rawEntryUnit}' does not match any configured unit ({$validList}).";
                }
                $resolvedStockUnit = $rawEntryUnit;
            }
        }

        if ($resolvedStockUnit === 'Box' && $boxEnabled) {
            $stockMultiplier = $boxConversion;
        } elseif ($resolvedStockUnit === 'Pack' && $packEnabled) {
            $stockMultiplier = $primConversion;
        } elseif ($resolvedStockUnit === 'Strip' && $stripEnabled) {
            $stockMultiplier = $secConversion;
        } else {
            $stockMultiplier = 1.0;
        }

        $convertedBaseStock = round($stockQty * $stockMultiplier, 4);

        // 8. Packaging-Aware Pricing Calculations
        $rawSalePrice = trim((string)($data['unit_price'] ?? $data['sale_price'] ?? ''));
        $salePriceVal = is_numeric($rawSalePrice) ? (float)$rawSalePrice : null;
        $rawSaleUnit = trim((string)($data['sale_price_unit'] ?? ''));
        $resolvedSaleUnit = $baseUnit;
        $saleMultiplier = 1.0;

        if ($rawSaleUnit !== '') {
            $cleanSaleUnit = strtolower($rawSaleUnit);
            if (isset($allowedUnits[$cleanSaleUnit])) {
                $resolvedSaleUnit = $allowedUnits[$cleanSaleUnit];
            }
        }

        if ($resolvedSaleUnit === 'Box' && $boxEnabled) {
            $saleMultiplier = $boxConversion;
        } elseif ($resolvedSaleUnit === 'Pack' && $packEnabled) {
            $saleMultiplier = $primConversion;
        } elseif ($resolvedSaleUnit === 'Strip' && $stripEnabled) {
            $saleMultiplier = $secConversion;
        } else {
            $saleMultiplier = 1.0;
        }

        $baseSalePrice = $salePriceVal !== null ? round($salePriceVal / $saleMultiplier, 4) : null;
        $stripSalePrice = ($stripEnabled && $baseSalePrice !== null) ? round($baseSalePrice * $secConversion, 2) : null;
        $packSalePrice = ($packEnabled && $baseSalePrice !== null) ? round($baseSalePrice * $primConversion, 2) : null;

        // Purchase Price calculations
        $rawPurchasePrice = trim((string)($data['purchase_price'] ?? ''));
        $purchasePriceVal = is_numeric($rawPurchasePrice) ? (float)$rawPurchasePrice : null;
        $rawPurchaseUnit = trim((string)($data['purchase_price_unit'] ?? ''));
        $resolvedPurchaseUnit = $baseUnit;
        $purchaseMultiplier = 1.0;

        if ($rawPurchaseUnit !== '') {
            $cleanPurchaseUnit = strtolower($rawPurchaseUnit);
            if (isset($allowedUnits[$cleanPurchaseUnit])) {
                $resolvedPurchaseUnit = $allowedUnits[$cleanPurchaseUnit];
            }
        }

        if ($resolvedPurchaseUnit === 'Box' && $boxEnabled) {
            $purchaseMultiplier = $boxConversion;
        } elseif ($resolvedPurchaseUnit === 'Pack' && $packEnabled) {
            $purchaseMultiplier = $primConversion;
        } elseif ($resolvedPurchaseUnit === 'Strip' && $stripEnabled) {
            $purchaseMultiplier = $secConversion;
        } else {
            $purchaseMultiplier = 1.0;
        }

        $basePurchasePrice = $purchasePriceVal !== null ? round($purchasePriceVal / $purchaseMultiplier, 4) : null;
        $stripPurchasePrice = ($stripEnabled && $basePurchasePrice !== null) ? round($basePurchasePrice * $secConversion, 2) : null;
        $packPurchasePrice = ($packEnabled && $basePurchasePrice !== null) ? round($basePurchasePrice * $primConversion, 2) : null;

        // 9. Expiry Date Validation
        $rawExpiry = $data['expiry_date'] ?? null;
        $expiryDate = null;
        if (!empty($rawExpiry)) {
            $expiryDate = $this->parseExpiryDate($rawExpiry);
            if ($expiryDate === null) {
                $errors[] = "{$prefix}Expiry Date '{$rawExpiry}' is invalid. Please use YYYY-MM-DD or DD/MM/YYYY format.";
            } elseif ($expiryDate < now()->toDateString()) {
                $errors[] = "{$prefix}Expiry Date '{$expiryDate}' has already expired. Expired batches cannot be imported as active stock.";
            }
        }

        return [
            'base_unit' => $baseUnit,
            'dosage_unit' => $baseUnit,
            'strip_enabled' => $stripEnabled,
            'units_per_strip' => $unitsPerStrip !== null ? (string)$unitsPerStrip : '',
            'pack_enabled' => $packEnabled,
            'strips_per_pack' => $stripsPerPack !== null ? (string)$stripsPerPack : '',
            'box_enabled' => $boxEnabled,
            'packs_per_box' => $packsPerBox !== null ? (string)$packsPerBox : '',
            'stock_quantity' => $stockQty > 0 ? (string)$stockQty : ($stockQtyRaw !== '' ? $stockQtyRaw : '0'),
            'stock_entry_unit' => $resolvedStockUnit,
            'stock_multiplier' => $stockMultiplier,
            'converted_base_stock' => $convertedBaseStock,
            'packaging_display' => $packagingDisplay,
            'strip_conversion_display' => $stripDisplay,
            'pack_conversion_display' => $packDisplay,
            'sale_price_unit' => $resolvedSaleUnit,
            'base_unit_selling_price' => $baseSalePrice,
            'strip_selling_price' => $stripSalePrice,
            'pack_selling_price' => $packSalePrice,
            'purchase_price_unit' => $resolvedPurchaseUnit,
            'base_unit_purchase_price' => $basePurchasePrice,
            'strip_purchase_price' => $stripPurchasePrice,
            'pack_purchase_price' => $packPurchasePrice,
            'expiry_date' => $expiryDate,
            'allowed_units' => array_values(array_unique($allowedUnits)),
            'errors' => $errors,
        ];
    }

    /**
     * Get sample rows for the 21-column template.
     */
    public function getSampleTemplateRows(): Collection
    {
        return collect([
            [
                'Medicine Name' => 'Panadol Extra 500mg',
                'Category' => 'Tablets & Capsules',
                'Generic Name' => 'Paracetamol + Caffeine',
                'Brand' => 'GSK',
                'Manufacturer' => 'GlaxoSmithKline Pakistan Ltd',
                'Barcode' => '8964000101',
                'Base Unit' => 'Tablet',
                'Strip Enabled' => 'Yes',
                'Units Per Strip' => '10',
                'Pack Enabled' => 'Yes',
                'Strips Per Pack' => '10',
                'Stock Quantity' => '50',
                'Stock Unit' => 'Pack',
                'Purchase Price' => '800.00',
                'Purchase Price Unit' => 'Pack',
                'Sale Price' => '1000.00',
                'Sale Price Unit' => 'Pack',
                'Alert Quantity' => '20',
                'Batch Number' => 'BAT-PAN-01',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'GlaxoSmithKline',
            ],
            [
                'Medicine Name' => 'Augmentin 625mg',
                'Category' => 'Tablets & Capsules',
                'Generic Name' => 'Amoxicillin + Clavulanic Acid',
                'Brand' => 'GSK',
                'Manufacturer' => 'GlaxoSmithKline Pakistan Ltd',
                'Barcode' => '8964000102',
                'Base Unit' => 'Tablet',
                'Strip Enabled' => 'Yes',
                'Units Per Strip' => '10',
                'Pack Enabled' => 'No',
                'Strips Per Pack' => '1',
                'Stock Quantity' => '20',
                'Stock Unit' => 'Strip',
                'Purchase Price' => '150.00',
                'Purchase Price Unit' => 'Strip',
                'Sale Price' => '190.00',
                'Sale Price Unit' => 'Strip',
                'Alert Quantity' => '10',
                'Batch Number' => 'BAT-AUG-02',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'GlaxoSmithKline',
            ],
            [
                'Medicine Name' => 'Brufen 400mg',
                'Category' => 'Tablets & Capsules',
                'Generic Name' => 'Ibuprofen',
                'Brand' => 'Abbott',
                'Manufacturer' => 'Abbott Laboratories Pakistan Ltd',
                'Barcode' => '8964000103',
                'Base Unit' => 'Tablet',
                'Strip Enabled' => 'No',
                'Units Per Strip' => '1',
                'Pack Enabled' => 'No',
                'Strips Per Pack' => '1',
                'Stock Quantity' => '1000',
                'Stock Unit' => 'Tablet',
                'Purchase Price' => '5.50',
                'Purchase Price Unit' => 'Tablet',
                'Sale Price' => '8.00',
                'Sale Price Unit' => 'Tablet',
                'Alert Quantity' => '50',
                'Batch Number' => 'BAT-BRU-03',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'Abbott Laboratories',
            ],
            [
                'Medicine Name' => 'Hydryllin Syrup 120ml',
                'Category' => 'Syrups & Suspensions',
                'Generic Name' => 'Aminophylline + Diphenhydramine',
                'Brand' => 'Searle',
                'Manufacturer' => 'The Searle Company Ltd',
                'Barcode' => '8964000104',
                'Base Unit' => 'Bottle',
                'Strip Enabled' => 'No',
                'Units Per Strip' => '1',
                'Pack Enabled' => 'No',
                'Strips Per Pack' => '1',
                'Stock Quantity' => '100',
                'Stock Unit' => 'Bottle',
                'Purchase Price' => '95.00',
                'Purchase Price Unit' => 'Bottle',
                'Sale Price' => '125.00',
                'Sale Price Unit' => 'Bottle',
                'Alert Quantity' => '15',
                'Batch Number' => 'BAT-HYD-04',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'The Searle Company',
            ],
            [
                'Medicine Name' => 'Ceftriaxone 1g Injection',
                'Category' => 'Injections & Infusions',
                'Generic Name' => 'Ceftriaxone Sodium',
                'Brand' => 'Getz',
                'Manufacturer' => 'Getz Pharma (Pvt) Ltd',
                'Barcode' => '8964000105',
                'Base Unit' => 'Vial',
                'Strip Enabled' => 'No',
                'Units Per Strip' => '1',
                'Pack Enabled' => 'No',
                'Strips Per Pack' => '1',
                'Stock Quantity' => '500',
                'Stock Unit' => 'Vial',
                'Purchase Price' => '180.00',
                'Purchase Price Unit' => 'Vial',
                'Sale Price' => '240.00',
                'Sale Price Unit' => 'Vial',
                'Alert Quantity' => '25',
                'Batch Number' => 'BAT-CEF-05',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'Getz Pharma',
            ],
            [
                'Medicine Name' => 'Polyfax Skin Ointment 20g',
                'Category' => 'Ointments & Creams',
                'Generic Name' => 'Polymyxin B + Bacitracin',
                'Brand' => 'GSK',
                'Manufacturer' => 'GlaxoSmithKline Pakistan Ltd',
                'Barcode' => '8964000106',
                'Base Unit' => 'Tube',
                'Strip Enabled' => 'No',
                'Units Per Strip' => '1',
                'Pack Enabled' => 'No',
                'Strips Per Pack' => '1',
                'Stock Quantity' => '60',
                'Stock Unit' => 'Tube',
                'Purchase Price' => '65.00',
                'Purchase Price Unit' => 'Tube',
                'Sale Price' => '85.00',
                'Sale Price Unit' => 'Tube',
                'Alert Quantity' => '10',
                'Batch Number' => 'BAT-PLY-07',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'GlaxoSmithKline',
            ],
            [
                'Medicine Name' => 'ORS Orange Powder',
                'Category' => 'Sachets & Powders',
                'Generic Name' => 'Oral Rehydration Salts',
                'Brand' => 'Searle',
                'Manufacturer' => 'The Searle Company Ltd',
                'Barcode' => '8964000107',
                'Base Unit' => 'Sachet',
                'Strip Enabled' => 'No',
                'Units Per Strip' => '1',
                'Pack Enabled' => 'Yes',
                'Strips Per Pack' => '20',
                'Stock Quantity' => '10',
                'Stock Unit' => 'Pack',
                'Purchase Price' => '260.00',
                'Purchase Price Unit' => 'Pack',
                'Sale Price' => '360.00',
                'Sale Price Unit' => 'Pack',
                'Alert Quantity' => '5',
                'Batch Number' => 'BAT-ORS-08',
                'Expiry Date' => now()->addYears(2)->toDateString(),
                'Supplier' => 'The Searle Company',
            ],
        ]);
    }

    /**
     * Get instructions for Sheet 2 of the template.
     */
    public function getTemplateInstructions(): Collection
    {
        return collect([
            [
                'Column Name' => 'Medicine Name',
                'Required / Optional' => 'REQUIRED',
                'Allowed Values / Format' => 'Text (Max 255 chars)',
                'Description' => 'The trade or brand name of the medicine (e.g. Panadol Extra 500mg).',
                'Example' => 'Panadol Extra 500mg',
            ],
            [
                'Column Name' => 'Category',
                'Required / Optional' => 'REQUIRED',
                'Allowed Values / Format' => 'Text or Category ID',
                'Description' => 'Category name or slug in your system (e.g. Tablets & Capsules, Syrups, Injections).',
                'Example' => 'Tablets & Capsules',
            ],
            [
                'Column Name' => 'Generic Name',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Text (Max 255 chars)',
                'Description' => 'Active pharmaceutical ingredient (API) or chemical formulation.',
                'Example' => 'Paracetamol + Caffeine',
            ],
            [
                'Column Name' => 'Brand',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Text (Max 255 chars)',
                'Description' => 'Marketing brand name.',
                'Example' => 'GSK',
            ],
            [
                'Column Name' => 'Manufacturer',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Text (Max 255 chars)',
                'Description' => 'Manufacturing company or pharmaceutical laboratory.',
                'Example' => 'GlaxoSmithKline Pakistan Ltd',
            ],
            [
                'Column Name' => 'Barcode',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Text / EAN / UPC',
                'Description' => 'Product barcode for POS scanner identification.',
                'Example' => '8964000101',
            ],
            [
                'Column Name' => 'Base Unit',
                'Required / Optional' => 'REQUIRED',
                'Allowed Values / Format' => 'Tablet, Capsule, Bottle, Vial, Ampoule, Tube, Sachet, Piece, etc.',
                'Description' => 'The lowest indivisible inventory unit. Each medicine defines its own base unit independently.',
                'Example' => 'Tablet (or Bottle, Vial)',
            ],
            [
                'Column Name' => 'Strip Enabled',
                'Required / Optional' => 'Optional (Defaults to No)',
                'Allowed Values / Format' => 'Yes / No (True / False)',
                'Description' => 'Set Yes if this medicine is packaged or sold in Strips or Blisters.',
                'Example' => 'Yes',
            ],
            [
                'Column Name' => 'Units Per Strip',
                'Required / Optional' => 'Required if Strip Enabled is Yes',
                'Allowed Values / Format' => 'Positive number (e.g. 10)',
                'Description' => 'Number of Base Units (e.g. Tablets) inside 1 Strip.',
                'Example' => '10',
            ],
            [
                'Column Name' => 'Pack Enabled',
                'Required / Optional' => 'Optional (Defaults to No)',
                'Allowed Values / Format' => 'Yes / No (True / False)',
                'Description' => 'Set Yes if this medicine is packaged or sold in Packs or Boxes.',
                'Example' => 'Yes',
            ],
            [
                'Column Name' => 'Strips Per Pack',
                'Required / Optional' => 'Required if Pack Enabled is Yes',
                'Allowed Values / Format' => 'Positive number (e.g. 10)',
                'Description' => 'If Strip is Yes: Number of Strips per Pack. If Strip is No: Number of Base Units per Pack.',
                'Example' => '10',
            ],
            [
                'Column Name' => 'Stock Quantity',
                'Required / Optional' => 'Optional (Defaults to 0)',
                'Allowed Values / Format' => 'Non-negative number',
                'Description' => 'The quantity of initial opening stock you are importing.',
                'Example' => '50',
            ],
            [
                'Column Name' => 'Stock Unit',
                'Required / Optional' => 'Required if Stock Quantity > 0',
                'Allowed Values / Format' => 'Base Unit, Strip, or Pack',
                'Description' => 'The unit of the entered Stock Quantity. Automatically converted into Base Units.',
                'Example' => 'Pack (50 Packs = 5,000 Tablets)',
            ],
            [
                'Column Name' => 'Purchase Price',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Non-negative number',
                'Description' => 'Cost price for the selected Purchase Price Unit.',
                'Example' => '800.00',
            ],
            [
                'Column Name' => 'Purchase Price Unit',
                'Required / Optional' => 'Optional (Defaults to Base Unit)',
                'Allowed Values / Format' => 'Base Unit, Strip, or Pack',
                'Description' => 'The packaging level that the Purchase Price applies to.',
                'Example' => 'Pack (Rs. 800 per Pack)',
            ],
            [
                'Column Name' => 'Sale Price',
                'Required / Optional' => 'REQUIRED',
                'Allowed Values / Format' => 'Non-negative number',
                'Description' => 'Retail selling price for the selected Sale Price Unit.',
                'Example' => '1000.00',
            ],
            [
                'Column Name' => 'Sale Price Unit',
                'Required / Optional' => 'Optional (Defaults to Base Unit)',
                'Allowed Values / Format' => 'Base Unit, Strip, or Pack',
                'Description' => 'The packaging level that the Sale Price applies to.',
                'Example' => 'Pack (Rs. 1,000 per Pack)',
            ],
            [
                'Column Name' => 'Alert Quantity',
                'Required / Optional' => 'Optional (Defaults to 10)',
                'Allowed Values / Format' => 'Non-negative integer',
                'Description' => 'Minimum stock threshold for low-stock warnings.',
                'Example' => '20',
            ],
            [
                'Column Name' => 'Batch Number',
                'Required / Optional' => 'Optional (Auto-generated if empty)',
                'Allowed Values / Format' => 'Text (Max 255 chars)',
                'Description' => 'Manufacturer lot or batch number. Prevents duplicate stock on re-import.',
                'Example' => 'BAT-2026-001',
            ],
            [
                'Column Name' => 'Expiry Date',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'YYYY-MM-DD or DD/MM/YYYY',
                'Description' => 'Batch expiration date. Expired dates will fail validation.',
                'Example' => '2028-12-31',
            ],
            [
                'Column Name' => 'Supplier',
                'Required / Optional' => 'Optional',
                'Allowed Values / Format' => 'Text (Supplier Name)',
                'Description' => 'Name of the supplier / distributor. Linked to batch purchase records.',
                'Example' => 'GlaxoSmithKline',
            ],
        ]);
    }
}
