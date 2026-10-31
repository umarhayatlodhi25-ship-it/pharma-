<?php

namespace Tests\Feature;

use App\Livewire\Pos\PosCounter;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosStockAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->category = Category::create([
            'name' => 'Tablets',
            'slug' => 'tablets',
            'product_type' => 'medicine',
        ]);
    }

    /**
     * Requirement 1: If physical stock = 0:
     * - Show OUT OF STOCK.
     * - Disable Add button.
     * - Prevent adding to cart.
     */
    public function test_zero_physical_stock_shows_out_of_stock_and_disables_add(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Zero Stock Med 100mg',
            'product_type' => 'medicine',
            'unit_price' => 50.00,
            'base_unit' => 'Tablet',
        ]);

        $this->assertEquals(0, $medicine->getPhysicalStock());
        $this->assertEquals(0, $medicine->getAvailableStock());
        $this->assertTrue($medicine->is_out_of_stock);
        $this->assertFalse($medicine->is_expired_stock);
        $this->assertEquals('out_of_stock', $medicine->stock_status);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->assertSee('OUT OF STOCK')
            ->assertDontSee('EXPIRED STOCK')
            ->assertSeeHtml('disabled')
            ->call('addToCart', $medicine->id)
            ->assertSet('cart', [])
            ->assertSee('Zero Stock Med 100mg is currently out of stock.');
    }

    /**
     * Requirement 2: If physical stock > 0 but all batches are expired:
     * - Show EXPIRED STOCK.
     * - Disable Add button.
     * - Show physical quantity and expiry information.
     */
    public function test_physical_stock_positive_all_expired_shows_expired_stock_and_disables_add(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Expired Panadol 500mg',
            'product_type' => 'medicine',
            'unit_price' => 25.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);

        $expiredDate = now()->subDays(10)->toDateString();

        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'EXP-BATCH-20',
            'quantity' => 20, // Physical stock = 20
            'purchase_price' => 15.00,
            'selling_price' => 25.00,
            'expiry_date' => $expiredDate, // Expired 10 days ago
            'status' => 'active',
        ]);

        $this->assertEquals(20, $medicine->getPhysicalStock());
        $this->assertEquals(0, $medicine->getAvailableStock());
        $this->assertFalse($medicine->is_out_of_stock); // Has physical stock
        $this->assertTrue($medicine->is_expired_stock);  // But 100% expired
        $this->assertEquals('expired_stock', $medicine->stock_status);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->assertSee('EXPIRED STOCK')
            ->assertDontSee('OUT OF STOCK')
            ->assertSee('20 Tablets (Expired')
            ->assertSeeHtml('disabled')
            ->call('addToCart', $medicine->id)
            ->assertSet('cart', [])
            ->assertSee('Cannot add Expired Panadol 500mg: All available physical stock (20 Tablets) is expired!');
    }

    /**
     * Requirement 3: If some batches are expired but other batches are valid:
     * - Calculate sellable stock using only valid, unexpired batches.
     * - Enable the product if valid stock is available.
     * - Exclude expired batches from sale.
     * Example: Physical stock 20, valid stock 10 -> AVAILABLE (10 Tablets)
     */
    public function test_some_batches_expired_calculates_sellable_stock_and_excludes_expired_batches(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Partial Valid Med 250mg',
            'product_type' => 'medicine',
            'unit_price' => 30.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);

        // Batch 1: 10 tablets, expired
        $expiredBatch = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-EXP-10',
            'quantity' => 10,
            'purchase_price' => 18.00,
            'selling_price' => 30.00,
            'expiry_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        // Batch 2: 10 tablets, valid and unexpired
        $validBatch = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-VAL-10',
            'quantity' => 10,
            'purchase_price' => 18.00,
            'selling_price' => 30.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        // Physical stock = 20, Valid sellable stock = 10
        $this->assertEquals(20, $medicine->getPhysicalStock());
        $this->assertEquals(10, $medicine->getAvailableStock());
        $this->assertEquals('available', $medicine->stock_status);
        $this->assertTrue($medicine->is_saleable);

        $component = Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->assertSee('AVAILABLE (10 Tablets)')
            ->assertSee('10 expired excluded')
            ->assertDontSee('EXPIRED STOCK')
            ->assertDontSee('OUT OF STOCK');

        // Add 10 tablets to cart (all available sellable stock)
        for ($i = 0; $i < 10; $i++) {
            $component->call('addToCart', $medicine->id);
        }

        $component->assertSet('cart.' . $medicine->id . '_base.qty', 10);

        // Attempting to add 11th item must be rejected (cannot sell expired batch)
        $component->call('addToCart', $medicine->id)
            ->assertSee('Maximum available stock (10 Tablets) already added to cart.');

        // Checkout the 10 valid tablets
        $component->call('checkout')
            ->assertSet('showInvoiceModal', true)
            ->assertSee('Sale Successful & Stock Deducted in Base Units!');

        // Check database batches:
        // Valid batch should be decremented to 0
        $this->assertEquals(0, (float)$validBatch->fresh()->quantity);
        $this->assertEquals('depleted', $validBatch->fresh()->status);

        // Expired batch MUST remain untouched at 10!
        $this->assertEquals(10, (float)$expiredBatch->fresh()->quantity);

        // Medicine now has physical stock 10, but valid stock 0 -> EXPIRED STOCK
        $medicine->refresh();
        $this->assertEquals(10, $medicine->getPhysicalStock());
        $this->assertEquals(0, $medicine->getAvailableStock());
        $this->assertEquals('expired_stock', $medicine->stock_status);
    }

    /**
     * Requirement 4: If valid stock = 0:
     * - Prevent adding the medicine to cart.
     * - Validate again on backend during checkout.
     */
    public function test_valid_stock_zero_prevents_adding_and_checkout_validates_again(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Pre Checkout Depleted Med',
            'product_type' => 'medicine',
            'unit_price' => 40.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);

        $batch = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-DEP-01',
            'quantity' => 2,
            'purchase_price' => 20.00,
            'selling_price' => 40.00,
            'expiry_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->call('addToCart', $medicine->id); // 1 in cart

        // Simulate batch expiring right before checkout
        $batch->update(['expiry_date' => now()->subDay()->toDateString()]);

        // Attempt checkout -> backend validation detects valid stock = 0 and throws exception
        $component->call('checkout')
            ->assertSee('Cannot checkout Pre Checkout Depleted Med: All available batches are expired.');

        $this->assertEquals(0, Sale::count());
        $this->assertEquals(2, $batch->fresh()->quantity); // Not sold or deducted
    }

    /**
     * Test: Clean in-stock medicine enables product and Add button.
     */
    public function test_in_stock_medicine_renders_enabled_and_can_be_added_to_cart(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Standard In Stock Med',
            'product_type' => 'medicine',
            'unit_price' => 15.00,
            'base_unit' => 'Tablet',
        ]);

        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-STD-01',
            'quantity' => 50,
            'purchase_price' => 10.00,
            'selling_price' => 15.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->assertSee('AVAILABLE (50 Tablets)')
            ->assertDontSee('OUT OF STOCK')
            ->assertDontSee('EXPIRED STOCK')
            ->call('addToCart', $medicine->id)
            ->assertSet('cart', function ($cart) {
                return count($cart) === 1;
            });
    }

    /**
     * Test: Incrementing and updating quantity validates against valid sellable stock.
     */
    public function test_quantity_changes_validate_against_valid_stock(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Strict Valid Qty Med',
            'product_type' => 'medicine',
            'unit_price' => 20.00,
            'base_unit' => 'Tablet',
        ]);

        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-STRICT-01',
            'quantity' => 2,
            'purchase_price' => 10.00,
            'selling_price' => 20.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $cartKey = $medicine->id . '_base';

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->call('addToCart', $medicine->id)
            ->call('incrementQty', $cartKey)
            ->assertSet('cart.' . $cartKey . '.qty', 2)
            ->call('incrementQty', $cartKey) // Beyond limit of 2
            ->assertSet('cart.' . $cartKey . '.qty', 2)
            ->assertSee('Insufficient stock! Only 2 Tablets available in total.')
            ->call('updateQuantity', $cartKey, 10) // Beyond limit of 2
            ->assertSet('cart.' . $cartKey . '.qty', 2)
            ->assertSee('Insufficient stock for Strict Valid Qty Med! Only 2 Tablets available in total.');
    }

    /**
     * Test: Receiving stock enables medicine automatically and transitions from EXPIRED STOCK to AVAILABLE.
     */
    public function test_receiving_stock_automatically_enables_medicine(): void
    {
        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Restock Transition Med',
            'product_type' => 'medicine',
            'unit_price' => 12.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);

        // Start with expired batch
        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-OLD-EXP',
            'quantity' => 15,
            'purchase_price' => 8.00,
            'selling_price' => 12.00,
            'expiry_date' => now()->subDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $component = Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->assertSee('EXPIRED STOCK')
            ->assertSee('15 Tablets (Expired');

        // New valid stock received
        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-NEW-RESTOCK',
            'quantity' => 25,
            'purchase_price' => 8.00,
            'selling_price' => 12.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        // Trigger Livewire event
        $component->dispatch('stockUpdated');

        $component->assertDontSee('EXPIRED STOCK')
            ->assertSee('AVAILABLE (25 Tablets)')
            ->call('addToCart', $medicine->id)
            ->assertSet('cart', function ($cart) {
                return count($cart) === 1;
            });
    }

    /**
     * Test: Out of stock and expired stock medicines remain visible in search.
     */
    public function test_out_of_stock_and_expired_medicines_remain_visible_in_search(): void
    {
        $outMed = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Search Out Stock Med',
            'product_type' => 'medicine',
            'unit_price' => 100.00,
            'base_unit' => 'Tablet',
        ]);

        $expMed = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Search Expired Stock Med',
            'product_type' => 'medicine',
            'unit_price' => 100.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);

        MedicineBatch::create([
            'medicine_id' => $expMed->id,
            'batch_number' => 'B-EXP-SEARCH',
            'quantity' => 10,
            'purchase_price' => 50.00,
            'selling_price' => 100.00,
            'expiry_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->set('search', 'Search Out Stock Med')
            ->assertSee('Search Out Stock Med')
            ->assertSee('OUT OF STOCK')
            ->assertSeeHtml('disabled')
            ->set('search', 'Search Expired Stock Med')
            ->assertSee('Search Expired Stock Med')
            ->assertSee('EXPIRED STOCK')
            ->assertSeeHtml('disabled');
    }

    /**
     * Test: Packaging unit respects valid unexpired stock.
     */
    public function test_packaging_unit_disabled_if_valid_stock_insufficient(): void
    {
        $boxUnit = Unit::firstOrCreate(['unit_id' => 'box'], ['name' => 'Box', 'type' => 'medicine']);
        $tabUnit = Unit::firstOrCreate(['unit_id' => 'tab'], ['name' => 'Tablet', 'type' => 'medicine']);

        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Box Packaging Med',
            'product_type' => 'medicine',
            'unit_price' => 5.00,
            'base_unit' => 'Tablet',
            'base_unit_id' => $tabUnit->id,
        ]);

        $pkgBox = MedicinePackaging::create([
            'medicine_id' => $medicine->id,
            'unit_id' => $boxUnit->id,
            'conversion_to_base' => 10.0, // 1 Box = 10 Tablets
            'sale_price' => 45.00,
            'allow_sale' => true,
        ]);

        // Only 5 tablets in valid stock
        MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-PACK-01',
            'quantity' => 5,
            'purchase_price' => 3.00,
            'selling_price' => 5.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->call('addToCart', $medicine->id, $pkgBox->id) // 10 tablets needed, only 5 available
            ->assertSet('cart', [])
            ->assertSee('Insufficient stock for Box Packaging Med (Box)! Available: 5 Tablets.')
            ->call('addToCart', $medicine->id) // 1 tablet fits in 5 available
            ->assertSet('cart', function ($cart) {
                return count($cart) === 1;
            });
    }

    /**
     * Test: Explicit verification of the exact 4 UI Examples from the prompt:
     * 1. Zero physical stock → OUT OF STOCK
     * 2. Physical stock 20, all expired → EXPIRED STOCK
     * 3. Physical stock 20, valid stock 10 → AVAILABLE (10 Tablets)
     * 4. Physical stock 20, valid stock 0 → EXPIRED STOCK
     */
    public function test_exact_four_ui_examples_from_prompt(): void
    {
        // 1. Zero physical stock
        $med1 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'UI Med Zero Stock',
            'product_type' => 'medicine',
            'unit_price' => 10.00,
            'base_unit' => 'Tablet',
        ]);

        // 2. Physical stock 20, all expired
        $med2 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'UI Med All Expired',
            'product_type' => 'medicine',
            'unit_price' => 15.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $med2->id,
            'batch_number' => 'BATCH-UI-EXP',
            'quantity' => 20,
            'purchase_price' => 8.00,
            'selling_price' => 15.00,
            'expiry_date' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        // 3. Physical stock 20, valid stock 10
        $med3 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'UI Med Partial Valid',
            'product_type' => 'medicine',
            'unit_price' => 20.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $med3->id,
            'batch_number' => 'BATCH-UI-P1',
            'quantity' => 10,
            'purchase_price' => 10.00,
            'selling_price' => 20.00,
            'expiry_date' => now()->subDay()->toDateString(), // expired 10
            'status' => 'active',
        ]);
        MedicineBatch::create([
            'medicine_id' => $med3->id,
            'batch_number' => 'BATCH-UI-P2',
            'quantity' => 10,
            'purchase_price' => 10.00,
            'selling_price' => 20.00,
            'expiry_date' => now()->addYear()->toDateString(), // valid 10
            'status' => 'active',
        ]);

        // 4. Physical stock 20, valid stock 0 -> EXPIRED STOCK
        $med4 = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'UI Med Physical 20 Valid 0',
            'product_type' => 'medicine',
            'unit_price' => 20.00,
            'base_unit' => 'Tablet',
            'has_expiry' => true,
        ]);
        MedicineBatch::create([
            'medicine_id' => $med4->id,
            'batch_number' => 'BATCH-UI-P20V0',
            'quantity' => 20,
            'purchase_price' => 10.00,
            'selling_price' => 20.00,
            'expiry_date' => now()->subMonth()->toDateString(), // valid stock = 0
            'status' => 'active',
        ]);

        $this->assertEquals(0, $med1->getPhysicalStock());
        $this->assertEquals('out_of_stock', $med1->stock_status);

        $this->assertEquals(20, $med2->getPhysicalStock());
        $this->assertEquals(0, $med2->getAvailableStock());
        $this->assertEquals('expired_stock', $med2->stock_status);

        $this->assertEquals(20, $med3->getPhysicalStock());
        $this->assertEquals(10, $med3->getAvailableStock());
        $this->assertEquals('available', $med3->stock_status);

        $this->assertEquals(20, $med4->getPhysicalStock());
        $this->assertEquals(0, $med4->getAvailableStock());
        $this->assertEquals('expired_stock', $med4->stock_status);
        $this->assertTrue($med4->is_expired_stock);
        $this->assertFalse($med4->is_out_of_stock);
        $this->assertFalse($med4->is_saleable);

        Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            // Example 1: Zero physical stock → OUT OF STOCK
            ->assertSee('OUT OF STOCK')
            // Example 2: Physical stock 20, all expired → EXPIRED STOCK
            ->assertSee('EXPIRED STOCK')
            ->assertSee('20 Tablets (Expired')
            // Example 3: Physical stock 20, valid stock 10 → AVAILABLE (10 Tablets)
            ->assertSee('AVAILABLE (10 Tablets)');
    }

    /**
     * Test: Switching packaging unit rejects expired stock.
     */
    public function test_cart_packaging_change_rejects_expired_stock(): void
    {
        $boxUnit = Unit::firstOrCreate(['unit_id' => 'box2'], ['name' => 'Box', 'type' => 'medicine']);
        $tabUnit = Unit::firstOrCreate(['unit_id' => 'tab2'], ['name' => 'Tablet', 'type' => 'medicine']);

        $medicine = Medicine::create([
            'category_id' => $this->category->id,
            'name' => 'Switch Pkg Expired Med',
            'product_type' => 'medicine',
            'unit_price' => 5.00,
            'base_unit' => 'Tablet',
            'base_unit_id' => $tabUnit->id,
            'has_expiry' => true,
        ]);

        $pkgTab = MedicinePackaging::create([
            'medicine_id' => $medicine->id,
            'unit_id' => $tabUnit->id,
            'conversion_to_base' => 1.0,
            'sale_price' => 5.00,
            'allow_sale' => true,
        ]);

        $pkgBox = MedicinePackaging::create([
            'medicine_id' => $medicine->id,
            'unit_id' => $boxUnit->id,
            'conversion_to_base' => 10.0,
            'sale_price' => 45.00,
            'allow_sale' => true,
        ]);

        $batch = MedicineBatch::create([
            'medicine_id' => $medicine->id,
            'batch_number' => 'BATCH-SW-01',
            'quantity' => 20,
            'purchase_price' => 3.00,
            'selling_price' => 5.00,
            'expiry_date' => now()->addYear()->toDateString(),
            'status' => 'active',
        ]);

        $cartKey = $medicine->id . '_' . $pkgTab->id;

        $component = Livewire::actingAs($this->user)
            ->test(PosCounter::class)
            ->call('addToCart', $medicine->id, $pkgTab->id); // 1 tablet added

        // Batch expires
        $batch->update(['expiry_date' => now()->subDay()->toDateString()]);

        // Attempting to change packaging unit when stock is expired
        $component->call('updateCartPackaging', $cartKey, $pkgBox->id)
            ->assertSee("Cannot change packaging for Switch Pkg Expired Med: All available stock is expired.");
    }
}
