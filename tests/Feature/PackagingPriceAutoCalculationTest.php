<?php

namespace Tests\Feature;

use App\Livewire\Admin\AddMedicine;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicinePackaging;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PackagingPriceAutoCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['role' => 'admin']);
        $this->category = Category::create([
            'name' => 'General Medicine',
            'slug' => 'general-medicine',
            'product_type' => 'medicine',
        ]);
    }

    public function test_entering_base_prices_auto_fills_secondary_and_primary_prices(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '8')
            ->set('selling_price', '12')
            ->assertSet('base_unit_purchase_price', '8.00')
            ->assertSet('base_unit_selling_price', '12.00')
            ->assertSet('secondary_unit_purchase_price', '80.00')
            ->assertSet('secondary_unit_selling_price', '120.00')
            ->assertSet('primary_unit_purchase_price', '800.00')
            ->assertSet('primary_unit_selling_price', '1200.00');
    }

    public function test_changing_multipliers_recalculates_prices(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '8')
            ->set('selling_price', '12')
            ->assertSet('secondary_unit_purchase_price', '80.00')
            ->assertSet('primary_unit_purchase_price', '800.00')
            // Change secondary multiplier from 10 to 15
            ->set('secondary_unit_to_base', '15')
            ->assertSet('secondary_unit_purchase_price', '120.00') // 8 * 15
            ->assertSet('secondary_unit_selling_price', '180.00')  // 12 * 15
            ->assertSet('primary_unit_purchase_price', '1200.00')  // 8 * (10 * 15)
            ->assertSet('primary_unit_selling_price', '1800.00')   // 12 * (10 * 15)
            // Change primary multiplier from 10 to 5
            ->set('primary_unit_to_secondary', '5')
            ->assertSet('primary_unit_purchase_price', '600.00')   // 8 * (5 * 15)
            ->assertSet('primary_unit_selling_price', '900.00');   // 12 * (5 * 15)
    }

    public function test_manually_edited_field_is_not_overwritten_by_base_price_changes(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '8')
            ->set('selling_price', '12')
            ->assertSet('secondary_unit_purchase_price', '80.00')
            // User manually overrides secondary purchase price to 95
            ->set('secondary_unit_purchase_price', '95.00')
            ->assertSet('manual_secondary_purchase', true)
            // Now update the base purchase price to 10
            ->set('purchase_price', '10')
            // Base updates to 10.00
            ->assertSet('base_unit_purchase_price', '10.00')
            // Manually edited field retains custom 95.00 value
            ->assertSet('secondary_unit_purchase_price', '95.00')
            // Selling price was not manual, so it recalculates with base selling price (12 * 10 = 120.00)
            ->assertSet('secondary_unit_selling_price', '120.00')
            // Primary purchase price recalculates with new base purchase (10 * 100 = 1000.00)
            ->assertSet('primary_unit_purchase_price', '1000.00');
    }

    public function test_reset_packaging_price_restores_auto_calculation(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '10')
            ->set('selling_price', '15')
            // Manually override
            ->set('secondary_unit_purchase_price', '95.00')
            ->assertSet('manual_secondary_purchase', true)
            // Click "Reset to auto"
            ->call('resetPackagingPrice', 'secondary_purchase')
            ->assertSet('manual_secondary_purchase', false)
            ->assertSet('secondary_unit_purchase_price', '100.00'); // 10 * 10
    }

    public function test_clearing_manual_field_resumes_auto_calculation(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '10')
            ->set('selling_price', '15')
            ->set('secondary_unit_purchase_price', '95.00')
            ->assertSet('manual_secondary_purchase', true)
            // Clear the field
            ->set('secondary_unit_purchase_price', '')
            ->assertSet('manual_secondary_purchase', false)
            ->assertSet('secondary_unit_purchase_price', '100.00');
    }

    public function test_empty_secondary_and_primary_unit_leaves_prices_empty(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', '')
            ->set('primary_unit', '')
            ->set('purchase_price', '8')
            ->set('selling_price', '12')
            ->assertSet('base_unit_purchase_price', '8.00')
            ->assertSet('base_unit_selling_price', '12.00')
            ->assertSet('secondary_unit_purchase_price', '')
            ->assertSet('secondary_unit_selling_price', '')
            ->assertSet('primary_unit_purchase_price', '')
            ->assertSet('primary_unit_selling_price', '');
    }

    public function test_saving_stores_final_calculated_and_manual_prices(): void
    {
        Livewire::actingAs($this->user)
            ->test(AddMedicine::class)
            ->set('name', 'Amoxicillin Test 500mg')
            ->set('category_id', (string) $this->category->id)
            ->set('quantity', '100')
            ->set('batch_number', 'BAT-TEST-99')
            ->set('expiry_date', now()->addYear()->format('Y-m-d'))
            ->set('base_unit', 'Tablet')
            ->set('secondary_unit', 'Strip')
            ->set('secondary_unit_to_base', '10')
            ->set('primary_unit', 'Pack')
            ->set('primary_unit_to_secondary', '10')
            ->set('purchase_price', '8')
            ->set('selling_price', '12')
            // Manually edit secondary selling price
            ->set('secondary_unit_selling_price', '115.00')
            ->call('confirmSaveNewProduct');

        $medicine = Medicine::where('name', 'Amoxicillin Test 500mg')->first();
        $this->assertNotNull($medicine);
        $this->assertEquals(8.00, (float)$medicine->purchase_price);
        $this->assertEquals(12.00, (float)$medicine->unit_price);
        $this->assertEquals(115.00, (float)$medicine->secondary_unit_selling_price);

        // Verify MedicinePackaging entries
        $basePkg = MedicinePackaging::where('medicine_id', $medicine->id)
            ->where('conversion_to_base', 1.0)
            ->first();
        $this->assertNotNull($basePkg);
        $this->assertEquals(8.00, (float)$basePkg->purchase_price);
        $this->assertEquals(12.00, (float)$basePkg->sale_price);

        $secPkg = MedicinePackaging::where('medicine_id', $medicine->id)
            ->where('conversion_to_base', 10.0)
            ->first();
        $this->assertNotNull($secPkg);
        $this->assertEquals(80.00, (float)$secPkg->purchase_price);
        $this->assertEquals(115.00, (float)$secPkg->sale_price);

        $primPkg = MedicinePackaging::where('medicine_id', $medicine->id)
            ->where('conversion_to_base', 100.0)
            ->first();
        $this->assertNotNull($primPkg);
        $this->assertEquals(800.00, (float)$primPkg->purchase_price);
        $this->assertEquals(1200.00, (float)$primPkg->sale_price);
    }
}
