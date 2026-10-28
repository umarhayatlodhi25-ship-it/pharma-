<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Medicine;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Rap2hpoutre\FastExcel\FastExcel;

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
        'dosage_unit' => [
            'dosage unit', 'dosageunit', 'unit', 'dosage', 'form',
            'dosage form', 'unit of measure', 'uom', 'pack unit'
        ],
        'unit_price' => [
            'sale price', 'saleprice', 'unit price', 'unitprice', 'selling price',
            'sellingprice', 'price', 'retail price', 'retailprice', 'mrp',
            'sales price', 'rate', 'unit rate'
        ],
        'purchase_price' => [
            'purchase price', 'purchaseprice', 'cost price', 'costprice',
            'cost', 'buy price', 'buyprice', 'purchasing price', 'purchase rate'
        ],
        'alert_quantity' => [
            'alert quantity', 'alertquantity', 'alert qty', 'alertqty',
            'min quantity', 'min qty', 'reorder level', 'reorder quantity',
            'min stock', 'alert stock', 'minimum stock'
        ],
        'barcode' => [
            'barcode', 'bar code', 'code', 'upc', 'ean', 'sku'
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
    public function parseFile(UploadedFile|string $file): array
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

        return $this->parseRawData($rawRows);
    }

    /**
     * Parse raw row data (either associative or positional arrays).
     */
    public function parseRawData(array $rawRows): array
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
            $headerMap = [
                'name' => 0,
                'category' => 1,
                'generic_name' => 2,
                'brand' => 3,
                'manufacturer' => 4,
                'dosage_unit' => 5,
                'unit_price' => 6,
                'purchase_price' => 7,
                'alert_quantity' => 8,
                'barcode' => 9,
            ];
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

                if (Medicine::whereRaw('LOWER(name) = ?', [$nameLower])->exists()) {
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

                if (Medicine::where('barcode', $barcode)->exists()) {
                    $errors[] = "Row {$rowNumber}: Barcode '{$barcode}' already exists in database.";
                    $duplicatesCount++;
                }
            }

            // 7. Dosage Unit, Generic Name, Brand, Manufacturer
            $dosageUnit = trim((string) ($mapped['dosage_unit'] ?? ''));
            if ($dosageUnit === '') {
                $dosageUnit = 'Tablet';
            }

            $genericName = trim((string) ($mapped['generic_name'] ?? ''));
            $brand = trim((string) ($mapped['brand'] ?? ''));
            $manufacturer = trim((string) ($mapped['manufacturer'] ?? ''));

            $formattedRow = [
                'name' => $name,
                'category_id' => $resolvedCategoryId ?: '',
                'category_name_raw' => $resolvedCategoryId ? '' : $rawCat,
                'generic_name' => $genericName,
                'brand' => $brand,
                'manufacturer' => $manufacturer,
                'dosage_unit' => $dosageUnit,
                'unit_price' => $salePrice,
                'purchase_price' => $purchasePrice,
                'alert_quantity' => $alertQty,
                'barcode' => $barcode,
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

        // For XLSX/XLS, use FastExcel
        if (in_array($originalExt, ['xlsx', 'xls', 'ods'])) {
            $imported = (new FastExcel())->import($file);
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
    protected function sanitizePrice(string $rawPrice): ?float
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
}
