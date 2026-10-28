<?php

namespace Tests\Feature;

use App\Livewire\Admin\BulkAddMedicine;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class BulkAddMedicineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $tabletCategory;
    protected Category $syrupCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->tabletCategory = Category::create([
            'name' => 'Tablets & Capsules',
            'slug' => 'tablets-capsules',
            'product_type' => 'medicine',
        ]);
        $this->syrupCategory = Category::create([
            'name' => 'Syrups & Suspensions',
            'slug' => 'syrups-suspensions',
            'product_type' => 'medicine',
        ]);
    }

    /**
     * Test 1: Valid CSV import populates the table and displays correct count.
     */
    public function test_valid_csv_import_populates_table_rows_and_displays_count(): void
    {
        $csvContent = "Medicine Name,Category,Generic Name,Brand,Manufacturer,Dosage Unit,Sale Price,Purchase Price,Alert Quantity,Barcode\n";
        for ($i = 1; $i <= 10; $i++) {
            $csvContent .= "Medicine Item {$i},Tablets & Capsules,Generic {$i},Brand {$i},Mfg {$i},Tablet,15.50,10.00,10,BARCODE{$i}\n";
        }

        $file = UploadedFile::fake()->createWithContent('medicines.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 10)
            ->assertCount('rows', 10)
            ->assertSee('10 medicines loaded from file')
            ->assertDontSee('0 medicines imported and saved directly!')
            ->assertSet('rows.0.name', 'Medicine Item 1')
            ->assertSet('rows.9.name', 'Medicine Item 10')
            ->assertSet('rows.0.category_id', $this->tabletCategory->id)
            ->assertSet('rows.0.unit_price', '15.50');
    }

    /**
     * Test 2: Valid XLSX import populates rows correctly.
     */
    public function test_valid_xlsx_import_populates_table_rows(): void
    {
        $data = collect();
        for ($i = 1; $i <= 10; $i++) {
            $data->push([
                'Medicine Name' => "XLSX Medicine {$i}",
                'Category' => 'Syrups & Suspensions',
                'Generic Name' => "Generic {$i}",
                'Brand' => "Brand {$i}",
                'Manufacturer' => "Mfg {$i}",
                'Dosage Unit' => 'Syrup',
                'Sale Price' => 45.00,
                'Purchase Price' => 30.00,
                'Alert Quantity' => 15,
                'Barcode' => "XLSXBAR{$i}",
            ]);
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'test_xlsx_') . '.xlsx';
        (new FastExcel($data))->export($tempPath);

        $file = UploadedFile::fake()->createWithContent('medicines.xlsx', file_get_contents($tempPath));

        try {
            Livewire::actingAs($this->user)
                ->test(BulkAddMedicine::class)
                ->set('importFile', $file)
                ->assertSet('totalImported', 10)
                ->assertCount('rows', 10)
                ->assertSee('10 medicines loaded from file')
                ->assertSet('rows.0.name', 'XLSX Medicine 1')
                ->assertSet('rows.0.category_id', $this->syrupCategory->id)
                ->assertSet('rows.0.unit_price', '45.00')
                ->assertSet('rows.0.dosage_unit', 'Syrup');
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    /**
     * Test 3: Correct mapping of every supported column with alternate header names.
     */
    public function test_correct_mapping_of_every_supported_column(): void
    {
        $csvContent = "Product Name,Category Name,Salt,Company,Producer,Form,Retail Price,Cost Price,Reorder Level,UPC\n"
            . "Augmentin 625mg,Tablets & Capsules,Co-Amoxiclav,GSK,GSK Pakistan,Tablet,120.00,95.50,25,8964000123\n";

        $file = UploadedFile::fake()->createWithContent('alt_headers.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertCount('rows', 1)
            ->assertSet('rows.0.name', 'Augmentin 625mg')
            ->assertSet('rows.0.category_id', $this->tabletCategory->id)
            ->assertSet('rows.0.generic_name', 'Co-Amoxiclav')
            ->assertSet('rows.0.brand', 'GSK')
            ->assertSet('rows.0.manufacturer', 'GSK Pakistan')
            ->assertSet('rows.0.dosage_unit', 'Tablet')
            ->assertSet('rows.0.unit_price', '120.00')
            ->assertSet('rows.0.purchase_price', '95.50')
            ->assertSet('rows.0.alert_quantity', '25')
            ->assertSet('rows.0.barcode', '8964000123');
    }

    /**
     * Test 4: Missing required headers displays actionable error and no false success.
     */
    public function test_missing_required_headers_displays_error(): void
    {
        $csvContent = "Brand,Generic Name,Sale Price\nGSK,Paracetamol,20.00\n";
        $file = UploadedFile::fake()->createWithContent('missing_headers.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertHasErrors('general')
            ->assertSee('Missing required column: "Medicine Name"')
            ->assertDontSee('medicines imported and saved directly')
            ->assertCount('rows', 3); // Preserved original empty starter rows
    }

    /**
     * Test 5: Empty files and files containing only headers.
     */
    public function test_empty_files_and_files_containing_only_headers(): void
    {
        // 5a. Completely empty file (0 bytes)
        $emptyFile = UploadedFile::fake()->createWithContent('empty.csv', '');

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $emptyFile)
            ->assertHasErrors('general')
            ->assertDontSee('medicines imported and saved directly');

        // 5b. File with only headers
        $headerOnlyFile = UploadedFile::fake()->createWithContent(
            'header_only.csv',
            "Medicine Name,Category,Sale Price\n"
        );

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $headerOnlyFile)
            ->assertHasErrors('general')
            ->assertSee('no data rows')
            ->assertDontSee('medicines imported and saved directly');
    }

    /**
     * Test 6: Invalid category names are flagged with row-specific error without creating duplicate categories.
     */
    public function test_invalid_category_names_flagged_with_row_errors(): void
    {
        $categoryCountBefore = Category::count();

        $csvContent = "Medicine Name,Category,Sale Price\n"
            . "Strange Drug,Nonexistent Category XYZ,50.00\n";

        $file = UploadedFile::fake()->createWithContent('invalid_cat.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertCount('rows', 1)
            ->assertSet('rows.0.category_id', '')
            ->assertSet('rows.0.category_name_raw', 'Nonexistent Category XYZ')
            ->assertHasErrors('rows.0.category_id')
            ->assertSee('Nonexistent Category XYZ');

        // Verify no duplicate/accidental category was inserted
        $this->assertEquals($categoryCountBefore, Category::count());
    }

    /**
     * Test 7: Invalid prices and blank medicine names produce row-specific errors.
     */
    public function test_invalid_prices_and_blank_medicine_names(): void
    {
        $csvContent = "Medicine Name,Category,Sale Price,Purchase Price\n"
            . ",Tablets & Capsules,-15.00,not_a_number\n";

        $file = UploadedFile::fake()->createWithContent('invalid_rows.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertHasErrors('rows.0.name')
            ->assertHasErrors('rows.0.unit_price')
            ->assertHasErrors('rows.0.purchase_price');
    }

    /**
     * Test 8: Duplicate medicine records in list and database are detected.
     */
    public function test_duplicate_medicine_records_detected(): void
    {
        // Existing medicine in database
        Medicine::create([
            'category_id' => $this->tabletCategory->id,
            'name' => 'Existing Medicine',
            'product_type' => 'medicine',
            'unit_price' => 10.00,
            'base_unit' => 'Tablet',
            'barcode' => 'BAR-EXISTING-1',
        ]);

        $csvContent = "Medicine Name,Category,Sale Price,Barcode\n"
            . "Existing Medicine,Tablets & Capsules,10.00,BAR-EXISTING-1\n"
            . "Duplicate In File,Tablets & Capsules,15.00,BAR-FILE-1\n"
            . "Duplicate In File,Tablets & Capsules,15.00,BAR-FILE-1\n";

        $file = UploadedFile::fake()->createWithContent('duplicates.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertCount('rows', 3)
            // Row 0 has duplicate DB error
            ->assertHasErrors('rows.0.name')
            // Row 2 has duplicate in-file error
            ->assertHasErrors('rows.2.name');
    }

    /**
     * Test 9: Mixed valid and invalid rows populate table and highlight issues.
     */
    public function test_mixed_valid_and_invalid_rows(): void
    {
        $csvContent = "Medicine Name,Category,Sale Price\n"
            . "Valid Med 1,Tablets & Capsules,10.00\n"
            . ",Tablets & Capsules,15.00\n"
            . "Valid Med 2,Tablets & Capsules,20.00\n";

        $file = UploadedFile::fake()->createWithContent('mixed.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertCount('rows', 3)
            ->assertSet('importSummary.parsed', 3)
            ->assertSet('importSummary.valid', 2)
            ->assertSet('importSummary.invalid', 1)
            ->assertSee('3 rows loaded from file (2 ready, 1 require attention)')
            ->assertHasErrors('rows.1.name');
    }

    /**
     * Test 10: Database insertion and displayed row counts after saving.
     */
    public function test_database_insertion_and_displayed_row_counts_after_saving(): void
    {
        $csvContent = "Medicine Name,Category,Sale Price,Purchase Price,Dosage Unit,Alert Quantity\n"
            . "Panadol 500mg Import,Tablets & Capsules,15.00,10.00,Tablet,20\n"
            . "Brufen 400mg Import,Tablets & Capsules,25.00,18.00,Tablet,30\n";

        $file = UploadedFile::fake()->createWithContent('save_test.csv', $csvContent);

        $component = Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertCount('rows', 2)
            ->call('saveAll')
            ->assertHasNoErrors()
            ->assertSee('2 medicines added successfully')
            ->assertCount('rows', 3); // Reset to 3 clean starter rows

        // Verify medicines exist in DB
        $this->assertDatabaseHas('medicines', [
            'name' => 'Panadol 500mg Import',
            'category_id' => $this->tabletCategory->id,
            'unit_price' => 15.00,
            'purchase_price' => 10.00,
        ]);

        $this->assertDatabaseHas('medicines', [
            'name' => 'Brufen 400mg Import',
            'category_id' => $this->tabletCategory->id,
            'unit_price' => 25.00,
            'purchase_price' => 18.00,
        ]);

        // Verify packaging was created
        $med = Medicine::where('name', 'Panadol 500mg Import')->first();
        $this->assertNotNull($med);
        $this->assertDatabaseHas('medicine_packaging', [
            'medicine_id' => $med->id,
            'sale_price' => 15.00,
        ]);

        // Verify inventory row exists with 0 stock (no fake stock batch created)
        $this->assertDatabaseHas('inventory', [
            'medicine_id' => $med->id,
            'total_base_quantity' => 0,
        ]);
    }

    /**
     * Test 11: Upload and parser failures handled gracefully.
     */
    public function test_upload_and_parser_failures_handled_gracefully(): void
    {
        $invalidFile = UploadedFile::fake()->create('malformed.pdf', 500, 'application/pdf');

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $invalidFile)
            ->assertHasErrors('importFile')
            ->assertSee('The file must be a CSV or Excel spreadsheet')
            ->assertSet('importFile', null);
    }

    /**
     * Test 12: Loading state reset and accurate success/error messages.
     */
    public function test_loading_state_reset_and_accurate_messages(): void
    {
        $csvContent = "Medicine Name,Category,Sale Price\n"
            . "Loaded Item 1,Tablets & Capsules,12.00\n";

        $file = UploadedFile::fake()->createWithContent('loaded.csv', $csvContent);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('importFile', null) // File property reset
            ->assertSee('1 medicines loaded from file. Please review and click Save All Medicines.')
            ->assertDontSee('0 medicines imported and saved directly');
    }
}
