<?php

namespace Tests\Feature;

use App\Livewire\Admin\BulkAddMedicine;
use App\Livewire\Pos\PosCounter;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\MedicineImportService;
use App\Services\PackagingService;
use App\Services\StockLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Rap2hpoutre\FastExcel\FastExcel;
use Tests\TestCase;

class BulkMedicineImportFlexiblePackagingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $tabletCategory;
    protected Category $syrupCategory;
    protected Category $injectionCategory;

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
        $this->injectionCategory = Category::create([
            'name' => 'Injections & Infusions',
            'slug' => 'injections-infusions',
            'product_type' => 'medicine',
        ]);
    }

    /**
     * Helper to create CSV content from array of rows with 21 columns.
     */
    protected function createCsvWithHeaders(array $rows): UploadedFile
    {
        $headers = [
            'Medicine Name',
            'Category',
            'Generic Name',
            'Brand',
            'Manufacturer',
            'Barcode',
            'Base Unit',
            'Strip Enabled',
            'Units Per Strip',
            'Pack Enabled',
            'Strips Per Pack',
            'Stock Quantity',
            'Stock Unit',
            'Purchase Price',
            'Purchase Price Unit',
            'Sale Price',
            'Sale Price Unit',
            'Alert Quantity',
            'Batch Number',
            'Expiry Date',
            'Supplier',
        ];

        $lines = [implode(',', $headers)];
        foreach ($rows as $row) {
            $escaped = array_map(function ($val) {
                $v = (string)$val;
                if (str_contains($v, ',') || str_contains($v, '"') || str_contains($v, "\n")) {
                    return '"' . str_replace('"', '""', $v) . '"';
                }
                return $v;
            }, $row);
            $lines[] = implode(',', $escaped);
        }

        return UploadedFile::fake()->createWithContent('bulk_medicines.csv', implode("\n", $lines));
    }

    /**
     * Scenario 1: Tablet-only medicine.
     */
    public function test_scenario_1_tablet_only_medicine(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Brufen 400mg Plain',
                'Tablets & Capsules',
                'Ibuprofen',
                'Abbott',
                'Abbott Laboratories',
                '8964000201',
                'Tablet',
                'No',
                '1',
                'No',
                '1',
                '1000',
                'Tablet',
                '5.50',
                'Tablet',
                '8.00',
                'Tablet',
                '50',
                'BAT-BRU-100',
                now()->addYears(2)->toDateString(),
                'Abbott Laboratories',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 1)
            ->assertSet('rows.0.converted_base_stock', 1000)
            ->assertSet('rows.0.base_unit', 'Tablet')
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Brufen 400mg Plain')->first();
        $this->assertNotNull($med);
        $this->assertEquals('Tablet', $med->base_unit);
        $this->assertEquals(1000, $med->total_stock);
        $this->assertEquals(8.00, $med->unit_price);
        $this->assertEquals(5.50, $med->purchase_price);

        // Tablet-only packaging: only Base Unit packaging exists, no strip or pack
        $this->assertDatabaseHas('medicine_packaging', [
            'medicine_id' => $med->id,
            'conversion_to_base' => 1.0,
            'display_name' => 'Tablet',
        ]);
        $stripUnit = Unit::where('unit_id', 'strip')->first();
        if ($stripUnit) {
            $this->assertDatabaseMissing('medicine_packaging', [
                'medicine_id' => $med->id,
                'unit_id' => $stripUnit->id,
            ]);
        }
    }

    /**
     * Scenario 2: Tablet + Strip (No Pack).
     */
    public function test_scenario_2_tablet_plus_strip_medicine(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Disprin Extra Strength',
                'Tablets & Capsules',
                'Aspirin',
                'Reckitt',
                'Reckitt Benckiser',
                '8964000202',
                'Tablet',
                'Yes',
                '10',
                'No',
                '1',
                '20',
                'Strip',
                '15.00',
                'Strip',
                '20.00',
                'Strip',
                '10',
                'BAT-DIS-200',
                now()->addYears(2)->toDateString(),
                'Reckitt',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 1)
            ->assertSet('rows.0.converted_base_stock', 200) // 20 Strips * 10 = 200 Tablets
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Disprin Extra Strength')->first();
        $this->assertNotNull($med);
        $this->assertEquals(200, $med->total_stock);
        // Base unit price: 20.00 / 10 = 2.00
        $this->assertEquals(2.00, $med->unit_price);
        $this->assertEquals(1.50, $med->purchase_price);

        // Strip packaging
        $stripUnit = Unit::where('unit_id', 'strip')->first();
        $this->assertNotNull($stripUnit);
        $this->assertDatabaseHas('medicine_packaging', [
            'medicine_id' => $med->id,
            'unit_id' => $stripUnit->id,
            'conversion_to_base' => 10.0,
            'sale_price' => 20.00,
            'purchase_price' => 15.00,
        ]);
    }

    /**
     * Scenario 3: Tablet + Strip + Pack (Panadol Extra: 50 Packs = 5,000 Tablets).
     */
    public function test_scenario_3_tablet_plus_strip_plus_pack(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Panadol Extra 500mg',
                'Tablets & Capsules',
                'Paracetamol + Caffeine',
                'GSK',
                'GlaxoSmithKline',
                '8964000203',
                'Tablet',
                'Yes',
                '10',
                'Yes',
                '10',
                '50',
                'Pack',
                '800.00',
                'Pack',
                '1000.00',
                'Pack',
                '20',
                'BAT-PAN-300',
                now()->addYears(2)->toDateString(),
                'GlaxoSmithKline',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 1)
            ->assertSet('rows.0.converted_base_stock', 5000) // 50 Packs * 100 = 5,000 Tablets!
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Panadol Extra 500mg')->first();
        $this->assertNotNull($med);
        $this->assertEquals(5000, $med->total_stock);
        // Base price: 1000 / 100 = 10.00 / Tablet
        $this->assertEquals(10.00, $med->unit_price);
        // Base purchase price: 800 / 100 = 8.00 / Tablet
        $this->assertEquals(8.00, $med->purchase_price);

        // Verify Packaging
        $packUnit = Unit::where('unit_id', 'pack')->first();
        $this->assertNotNull($packUnit);
        $this->assertDatabaseHas('medicine_packaging', [
            'medicine_id' => $med->id,
            'unit_id' => $packUnit->id,
            'conversion_to_base' => 100.0,
            'sale_price' => 1000.00,
            'purchase_price' => 800.00,
        ]);
    }

    /**
     * Scenario 4: Bottle-only medicine (Syrup).
     */
    public function test_scenario_4_bottle_only_medicine(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Hydryllin Syrup 120ml',
                'Syrups & Suspensions',
                'Aminophylline',
                'Searle',
                'The Searle Company',
                '8964000204',
                'Bottle',
                'No',
                '1',
                'No',
                '1',
                '100',
                'Bottle',
                '95.00',
                'Bottle',
                '125.00',
                'Bottle',
                '15',
                'BAT-HYD-400',
                now()->addYears(2)->toDateString(),
                'The Searle Company',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 1)
            ->assertSet('rows.0.converted_base_stock', 100)
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Hydryllin Syrup 120ml')->first();
        $this->assertNotNull($med);
        $this->assertEquals('Bottle', $med->base_unit);
        $this->assertEquals(100, $med->total_stock);
        $this->assertEquals(125.00, $med->unit_price);
        $this->assertEquals(95.00, $med->purchase_price);
    }

    /**
     * Scenario 5: Vial-only medicine (Injection).
     */
    public function test_scenario_5_vial_only_medicine(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Ceftriaxone 1g Vial',
                'Injections & Infusions',
                'Ceftriaxone Sodium',
                'Getz',
                'Getz Pharma',
                '8964000205',
                'Vial',
                'No',
                '1',
                'No',
                '1',
                '500',
                'Vial',
                '180.00',
                'Vial',
                '240.00',
                'Vial',
                '25',
                'BAT-CEF-500',
                now()->addYears(2)->toDateString(),
                'Getz Pharma',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 1)
            ->assertSet('rows.0.converted_base_stock', 500)
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Ceftriaxone 1g Vial')->first();
        $this->assertNotNull($med);
        $this->assertEquals('Vial', $med->base_unit);
        $this->assertEquals(500, $med->total_stock);
    }

    /**
     * Scenario 6: Mixed units in one Excel/CSV import.
     * Panadol: 50 Packs
     * Augmentin: 20 Strips
     * Syrup: 100 Bottles
     * Injection: 500 Vials
     * Another: 1,000 Tablets
     */
    public function test_scenario_6_mixed_units_in_one_excel_import(): void
    {
        $rows = [
            [
                'Panadol Mixed', 'Tablets & Capsules', 'Paracetamol', 'GSK', 'GSK',
                '8964000301', 'Tablet', 'Yes', '10', 'Yes', '10', '50', 'Pack',
                '800.00', 'Pack', '1000.00', 'Pack', '10', 'BAT-MIX-01', now()->addYears(2)->toDateString(), 'GSK'
            ],
            [
                'Augmentin Mixed', 'Tablets & Capsules', 'Co-Amoxiclav', 'GSK', 'GSK',
                '8964000302', 'Tablet', 'Yes', '10', 'No', '1', '20', 'Strip',
                '150.00', 'Strip', '190.00', 'Strip', '10', 'BAT-MIX-02', now()->addYears(2)->toDateString(), 'GSK'
            ],
            [
                'Syrup Mixed', 'Syrups & Suspensions', 'Aminophylline', 'Searle', 'Searle',
                '8964000303', 'Bottle', 'No', '1', 'No', '1', '100', 'Bottle',
                '90.00', 'Bottle', '120.00', 'Bottle', '10', 'BAT-MIX-03', now()->addYears(2)->toDateString(), 'Searle'
            ],
            [
                'Injection Mixed', 'Injections & Infusions', 'Ceftriaxone', 'Getz', 'Getz',
                '8964000304', 'Vial', 'No', '1', 'No', '1', '500', 'Vial',
                '180.00', 'Vial', '240.00', 'Vial', '10', 'BAT-MIX-04', now()->addYears(2)->toDateString(), 'Getz'
            ],
            [
                'Tablet Mixed', 'Tablets & Capsules', 'Aspirin', 'Reckitt', 'Reckitt',
                '8964000305', 'Tablet', 'No', '1', 'No', '1', '1000', 'Tablet',
                '3.00', 'Tablet', '5.00', 'Tablet', '10', 'BAT-MIX-05', now()->addYears(2)->toDateString(), 'Reckitt'
            ],
        ];

        $file = $this->createCsvWithHeaders($rows);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->assertSet('totalImported', 5)
            ->assertCount('rows', 5)
            ->assertSet('rows.0.converted_base_stock', 5000)
            ->assertSet('rows.1.converted_base_stock', 200)
            ->assertSet('rows.2.converted_base_stock', 100)
            ->assertSet('rows.3.converted_base_stock', 500)
            ->assertSet('rows.4.converted_base_stock', 1000)
            ->call('saveAll')
            ->assertHasNoErrors();

        $this->assertEquals(5000, Medicine::where('name', 'Panadol Mixed')->first()->total_stock);
        $this->assertEquals(200, Medicine::where('name', 'Augmentin Mixed')->first()->total_stock);
        $this->assertEquals(100, Medicine::where('name', 'Syrup Mixed')->first()->total_stock);
        $this->assertEquals(500, Medicine::where('name', 'Injection Mixed')->first()->total_stock);
        $this->assertEquals(1000, Medicine::where('name', 'Tablet Mixed')->first()->total_stock);
    }

    /**
     * Scenario 9: Purchase and sale price conversion.
     */
    public function test_scenario_9_purchase_and_sale_price_conversion(): void
    {
        $file = $this->createCsvWithHeaders([
            [
                'Pricing Test Medicine',
                'Tablets & Capsules',
                'Formula',
                'Brand',
                'Manufacturer',
                '8964000999',
                'Tablet',
                'Yes',
                '10',
                'Yes',
                '10',
                '10',
                'Pack',
                '800.00',
                'Pack', // Cost = 800 per Pack
                '1000.00',
                'Pack', // Sale = 1000 per Pack
                '10',
                'BAT-PRICE-01',
                now()->addYears(2)->toDateString(),
                'Supplier A',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Pricing Test Medicine')->first();
        $this->assertNotNull($med);

        // Equivalent pricing
        $pkgService = app(PackagingService::class);
        $this->assertEquals(10.00, $pkgService->getSellingPriceForUnit($med, 'Tablet'));
        $this->assertEquals(100.00, $pkgService->getSellingPriceForUnit($med, 'Strip'));
        $this->assertEquals(1000.00, $pkgService->getSellingPriceForUnit($med, 'Pack'));

        $this->assertEquals(8.00, $pkgService->getPurchasePriceForUnit($med, 'Tablet'));
        $this->assertEquals(80.00, $pkgService->getPurchasePriceForUnit($med, 'Strip'));
        $this->assertEquals(800.00, $pkgService->getPurchasePriceForUnit($med, 'Pack'));
    }

    /**
     * Scenario 10: Duplicate import prevention.
     */
    public function test_scenario_10_duplicate_import_prevention(): void
    {
        $rows = [
            [
                'Duplicate Test Medicine',
                'Tablets & Capsules',
                'Formula',
                'Brand',
                'Manufacturer',
                '8964000888',
                'Tablet',
                'No',
                '1',
                'No',
                '1',
                '100',
                'Tablet',
                '10.00',
                'Tablet',
                '15.00',
                'Tablet',
                '10',
                'BAT-DUP-01',
                now()->addYears(2)->toDateString(),
                'Supplier',
            ]
        ];

        $file1 = $this->createCsvWithHeaders($rows);

        // First import succeeds
        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file1)
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Duplicate Test Medicine')->first();
        $this->assertEquals(100, $med->total_stock);

        // Re-importing same file without updateExisting flags duplicate medicine error
        $file2 = $this->createCsvWithHeaders($rows);
        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file2)
            ->assertHasErrors('rows.0.name')
            ->assertSee('already exists in database');

        // And even if updateExisting is true, duplicate batch cannot be imported again to prevent double-counting stock!
        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('updateExisting', true)
            ->set('importFile', $file2)
            ->assertHasErrors('rows.0.batch_number')
            ->assertSee('already exists for medicine');

        // Stock remains exactly 100
        $this->assertEquals(100, $med->fresh()->total_stock);
    }

    /**
     * Scenario 11: Batch and expiry preservation.
     */
    public function test_scenario_11_batch_and_expiry_preservation(): void
    {
        $expiryDate = now()->addMonths(18)->toDateString();

        $file = $this->createCsvWithHeaders([
            [
                'Batch Preservation Med',
                'Tablets & Capsules',
                'Generic',
                'Brand',
                'Manufacturer',
                '8964000777',
                'Tablet',
                'No',
                '1',
                'No',
                '1',
                '250',
                'Tablet',
                '12.00',
                'Tablet',
                '18.00',
                'Tablet',
                '10',
                'EXACT-BATCH-999',
                $expiryDate,
                'Unique Supplier Ltd',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->call('saveAll')
            ->assertHasNoErrors();

        $med = Medicine::where('name', 'Batch Preservation Med')->first();
        $this->assertNotNull($med);

        $batch = MedicineBatch::where('medicine_id', $med->id)->first();
        $this->assertNotNull($batch);
        $this->assertEquals('EXACT-BATCH-999', $batch->batch_number);
        $this->assertEquals($expiryDate, $batch->expiry_date->toDateString());
        $this->assertEquals(250, $batch->quantity);

        // Supplier was created & linked
        $supplier = Supplier::where('name', 'Unique Supplier Ltd')->first();
        $this->assertNotNull($supplier);
        $this->assertEquals($supplier->id, $batch->supplier_id);

        // Past expiry rejected
        $expiredFile = $this->createCsvWithHeaders([
            [
                'Expired Medicine',
                'Tablets & Capsules',
                'Generic',
                'Brand',
                'Manufacturer',
                '8964000666',
                'Tablet',
                'No',
                '1',
                'No',
                '1',
                '100',
                'Tablet',
                '10.00',
                'Tablet',
                '15.00',
                'Tablet',
                '10',
                'BAT-EXP-PAST',
                now()->subMonths(2)->toDateString(), // Already expired!
                'Supplier',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $expiredFile)
            ->assertHasErrors('rows.0.expiry_date')
            ->assertSee('has already expired');
    }

    /**
     * Scenario 12: POS stock deduction.
     */
    public function test_scenario_12_pos_stock_deduction(): void
    {
        // 1. Bulk import Panadol: 50 Packs (5,000 Tablets)
        $file = $this->createCsvWithHeaders([
            [
                'Panadol POS Test',
                'Tablets & Capsules',
                'Paracetamol',
                'GSK',
                'GSK',
                '8964000555',
                'Tablet',
                'Yes',
                '10',
                'Yes',
                '10',
                '50',
                'Pack',
                '800.00',
                'Pack',
                '1000.00',
                'Pack',
                '10',
                'BAT-POS-01',
                now()->addYears(2)->toDateString(),
                'GSK',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->call('saveAll');

        $med = Medicine::where('name', 'Panadol POS Test')->first();
        $this->assertEquals(5000, $med->total_stock);

        // 2. Perform POS sale of 2 Packs (200 Tablets)
        $packPkg = MedicinePackaging::where('medicine_id', $med->id)->where('conversion_to_base', 100.0)->first();
        $this->assertNotNull($packPkg);

        $customer = Customer::create(['name' => 'Walk-in Customer', 'phone' => '03001234567']);

        Livewire::test(PosCounter::class)
            ->set('customer_id', $customer->id)
            ->call('addToCart', $med->id, $packPkg->id)
            ->call('updateQuantity', $med->id . '_' . $packPkg->id, 2) // 2 Packs
            ->call('checkout');

        // Stock must now be 5,000 - 200 = 4,800 Tablets
        $this->assertEquals(4800, $med->fresh()->total_stock);
        $this->assertEquals(4800, $med->fresh()->getAvailableStock());
    }

    /**
     * Scenario 13: Sales returns.
     */
    public function test_scenario_13_sales_returns(): void
    {
        // 1. Bulk import medicine
        $file = $this->createCsvWithHeaders([
            [
                'Return Test Medicine',
                'Tablets & Capsules',
                'Paracetamol',
                'Brand',
                'Mfg',
                '8964000444',
                'Tablet',
                'Yes',
                '10',
                'Yes',
                '10',
                '10',
                'Pack',
                '800.00',
                'Pack',
                '1000.00',
                'Pack',
                '10',
                'BAT-RET-01',
                now()->addYears(2)->toDateString(),
                'Supplier',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('importFile', $file)
            ->call('saveAll');

        $med = Medicine::where('name', 'Return Test Medicine')->first();
        $this->assertEquals(1000, $med->total_stock); // 10 Packs = 1000 Tablets

        // 2. Sale of 1 Pack (100 Tablets)
        $packPkg = MedicinePackaging::where('medicine_id', $med->id)->where('conversion_to_base', 100.0)->first();
        $batch = MedicineBatch::where('medicine_id', $med->id)->first();
        $customer = Customer::create(['name' => 'Return Customer', 'phone' => '03009999999']);

        Livewire::test(PosCounter::class)
            ->set('customer_id', $customer->id)
            ->call('addToCart', $med->id, $packPkg->id)
            ->call('checkout');

        $this->assertEquals(900, $med->fresh()->total_stock);

        // 3. Process sales return of 1 Pack (100 Tablets)
        $sale = Sale::latest('id')->first();
        $saleItem = SaleItem::where('sale_id', $sale->id)->first();

        // Simulate return via stock ledger service
        $batch->increment('quantity', 100);
        app(StockLedgerService::class)->recordMovement(
            medicineId: $med->id,
            batchId: $batch->id,
            type: 'SALE_RETURN',
            referenceId: $sale->id,
            referenceType: Sale::class,
            selectedUnitId: $packPkg->unit_id,
            quantity: 1,
            conversionToBase: 100,
            baseQuantity: 100,
            notes: 'Return 1 Pack'
        );

        $this->assertEquals(1000, $med->fresh()->total_stock);
    }

    /**
     * Scenario 14: Existing manually created medicine compatibility.
     */
    public function test_scenario_14_existing_manually_created_medicine_compatibility(): void
    {
        // 1. Manually created medicine (as created from AddMedicine page)
        $unit = Unit::firstOrCreate(['unit_id' => 'tablet'], ['name' => 'Tablet', 'status' => 'active']);
        $med = Medicine::create([
            'category_id' => $this->tabletCategory->id,
            'name' => 'Manual Panadol',
            'product_type' => 'medicine',
            'dosage_unit' => 'Tablet',
            'base_unit' => 'Tablet',
            'base_unit_id' => $unit->id,
            'unit_price' => 10.00,
            'purchase_price' => 8.00,
            'base_unit_selling_price' => 10.00,
            'has_expiry' => true,
            'track_batches' => true,
            'status' => 'active',
        ]);

        $batch = MedicineBatch::create([
            'medicine_id' => $med->id,
            'batch_number' => 'MAN-BAT-001',
            'quantity' => 500,
            'purchase_price' => 8.00,
            'selling_price' => 10.00,
            'purchase_price_per_base_unit' => 8.00,
            'selling_price_per_base_unit' => 10.00,
            'expiry_date' => now()->addYears(2)->toDateString(),
            'status' => 'active',
        ]);
        app(StockLedgerService::class)->syncInventory($med->id);

        $this->assertEquals(500, $med->total_stock);

        // 2. Verify packaging service works with it
        $units = app(PackagingService::class)->getAvailableSaleUnits($med);
        $this->assertNotEmpty($units);

        // 3. Verify updating with updateExisting = true preserves manual medicine and existing batch
        $file = $this->createCsvWithHeaders([
            [
                'Manual Panadol',
                'Tablets & Capsules',
                'Paracetamol Updated',
                'GSK',
                'GSK',
                '8964000333',
                'Tablet',
                'Yes',
                '10',
                'No',
                '1',
                '0',
                'Tablet',
                '8.00',
                'Tablet',
                '10.00',
                'Tablet',
                '15',
                '',
                '',
                '',
            ]
        ]);

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->set('updateExisting', true)
            ->set('importFile', $file)
            ->call('saveAll')
            ->assertHasNoErrors();

        $updatedMed = $med->fresh();
        $this->assertEquals('Paracetamol Updated', $updatedMed->generic_name);
        $this->assertEquals(500, $updatedMed->total_stock); // Existing batch preserved!
        $this->assertEquals(1, MedicineBatch::where('medicine_id', $med->id)->count());
    }

    /**
     * Scenario 15: Download template has 21 columns and Instructions sheet.
     */
    public function test_template_download_contains_21_columns_and_instructions_sheet(): void
    {
        $service = app(MedicineImportService::class);
        $samples = $service->getSampleTemplateRows();
        $instructions = $service->getTemplateInstructions();

        $this->assertCount(7, $samples);
        $firstRow = $samples->first();
        $this->assertArrayHasKey('Medicine Name', $firstRow);
        $this->assertArrayHasKey('Category', $firstRow);
        $this->assertArrayHasKey('Generic Name', $firstRow);
        $this->assertArrayHasKey('Brand', $firstRow);
        $this->assertArrayHasKey('Manufacturer', $firstRow);
        $this->assertArrayHasKey('Barcode', $firstRow);
        $this->assertArrayHasKey('Base Unit', $firstRow);
        $this->assertArrayHasKey('Strip Enabled', $firstRow);
        $this->assertArrayHasKey('Units Per Strip', $firstRow);
        $this->assertArrayHasKey('Pack Enabled', $firstRow);
        $this->assertArrayHasKey('Strips Per Pack', $firstRow);
        $this->assertArrayHasKey('Stock Quantity', $firstRow);
        $this->assertArrayHasKey('Stock Unit', $firstRow);
        $this->assertArrayHasKey('Purchase Price', $firstRow);
        $this->assertArrayHasKey('Purchase Price Unit', $firstRow);
        $this->assertArrayHasKey('Sale Price', $firstRow);
        $this->assertArrayHasKey('Sale Price Unit', $firstRow);
        $this->assertArrayHasKey('Alert Quantity', $firstRow);
        $this->assertArrayHasKey('Batch Number', $firstRow);
        $this->assertArrayHasKey('Expiry Date', $firstRow);
        $this->assertArrayHasKey('Supplier', $firstRow);
        $this->assertCount(21, $firstRow);

        $this->assertNotEmpty($instructions);

        // Test Livewire component download action returns downloadable response
        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->call('downloadTemplate')
            ->assertFileDownloaded('medicines_bulk_import_template.xlsx');

        Livewire::actingAs($this->user)
            ->test(BulkAddMedicine::class)
            ->call('downloadCsvTemplate')
            ->assertFileDownloaded('medicines_bulk_import_template.csv');
    }
}
