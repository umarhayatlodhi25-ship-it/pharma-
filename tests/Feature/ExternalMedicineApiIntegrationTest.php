<?php

namespace Tests\Feature;

use App\Livewire\Admin\PurchaseCreate;
use App\Livewire\Pos\PosCounter;
use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineMaster;
use App\Models\User;
use App\Services\ExternalMedicineApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ExternalMedicineApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.medicine_api.base_url' => 'https://api.fakepharma.com',
            'services.medicine_api.api_key'  => 'test_key_123',
            'services.medicine_api.timeout'  => 5,
        ]);
    }

    public function test_indexes_exist_on_medicine_master_table(): void
    {
        $this->assertTrue(Schema::hasIndex('medicine_master', ['medicine_id'], 'unique'));
        $this->assertTrue(Schema::hasIndex('medicine_master', ['medicine_name']));
        $this->assertTrue(Schema::hasIndex('medicine_master', ['brand_name']));
        $this->assertTrue(Schema::hasIndex('medicine_master', ['generic_name']));
    }

    public function test_api_service_can_fetch_and_normalize_external_medicines(): void
    {
        Http::fake([
            'https://api.fakepharma.com/api/medicines*' => Http::response([
                'total' => 2,
                'page'  => 1,
                'pages' => 1,
                'limit' => 200,
                'data'  => [
                    [
                        'id'           => 101,
                        'name'         => 'Amoxicillin 500mg',
                        'generic_name' => 'Amoxicillin',
                        'form'         => 'Capsule',
                        'strength'     => '500mg',
                        'category'     => 'Antibiotics & Antivirals',
                        'company'      => 'GlaxoSmithKline',
                    ],
                    [
                        'id'           => 102,
                        'name'         => 'Brufen 400mg',
                        'generic_name' => 'Ibuprofen',
                        'form'         => 'Tablet',
                        'strength'     => '400mg',
                        'category'     => 'Pain Relief & Analgesics',
                        'company'      => 'Abbott Laboratories',
                    ],
                ],
            ], 200),
        ]);

        $service = app(ExternalMedicineApiService::class);
        $page = $service->fetchPage(1, 10, 'Amox');

        $this->assertEquals(2, $page['total']);
        $this->assertCount(2, $page['data']);

        $normalized = $service->normalize($page['data'][0]);
        $this->assertEquals('ext_101', $normalized['medicine_id']);
        $this->assertEquals('Amoxicillin 500mg', $normalized['medicine_name']);
        $this->assertEquals('Amoxicillin', $normalized['generic_name']);
        $this->assertEquals('Capsule', $normalized['dosage_form']);
        $this->assertEquals('GlaxoSmithKline', $normalized['manufacturer']);
        $this->assertEquals('External_API', $normalized['source']);
    }

    public function test_upsert_chunk_inserts_and_updates_medicine_master(): void
    {
        $service = app(ExternalMedicineApiService::class);

        $fakeItems = [
            [
                'id'           => 201,
                'name'         => 'Panadol CF',
                'generic_name' => 'Paracetamol + Pseudoephedrine',
                'form'         => 'Tablet',
                'strength'     => '500mg/30mg',
                'company'      => 'GSK',
            ],
            [
                'id'           => 202,
                'name'         => 'Cac 1000 Plus',
                'generic_name' => 'Calcium + Vitamin C + D',
                'form'         => 'Effervescent Tablet',
                'strength'     => '1000mg',
                'company'      => 'GSK',
            ],
        ];

        $upserted = $service->upsertChunk($fakeItems, 'verified');
        $this->assertEquals(2, $upserted);

        $this->assertDatabaseHas('medicine_master', [
            'medicine_id'   => 'ext_201',
            'medicine_name' => 'Panadol CF',
        ]);
        $this->assertDatabaseHas('medicine_master', [
            'medicine_id'   => 'ext_202',
            'medicine_name' => 'Cac 1000 Plus',
        ]);
    }

    public function test_promote_to_operational_creates_medicine_with_packaging_and_inventory(): void
    {
        $service = app(ExternalMedicineApiService::class);

        $rawMedicine = [
            'id'             => 301,
            'name'           => 'Test Promote Drug 250mg',
            'generic_name'   => 'Promotium Active',
            'brand'          => 'Test Brand',
            'dosage_form'    => 'Tablet',
            'strength'       => '250mg',
            'manufacturer'   => 'Pharma Lab',
            'unit_price'     => 15.50,
            'purchase_price' => 10.00,
        ];

        $operational = $service->promoteToOperational($rawMedicine);

        $this->assertInstanceOf(Medicine::class, $operational);
        $this->assertDatabaseHas('medicines', [
            'id'   => $operational->id,
            'name' => 'Test Promote Drug 250mg',
        ]);

        $this->assertDatabaseHas('medicine_packaging', [
            'medicine_id'        => $operational->id,
            'conversion_to_base' => 1.0,
            'allow_sale'         => 1,
        ]);

        $this->assertDatabaseHas('inventory', [
            'medicine_id'         => $operational->id,
            'total_base_quantity' => 0,
        ]);
    }

    public function test_sync_command_runs_successfully(): void
    {
        Http::fake([
            'https://api.fakepharma.com/api/medicines*' => Http::response([
                'total' => 1,
                'page'  => 1,
                'pages' => 1,
                'limit' => 200,
                'data'  => [
                    [
                        'id'           => 401,
                        'name'         => 'Sync Command Drug 10mg',
                        'generic_name' => 'Syncium',
                        'form'         => 'Tablet',
                        'company'      => 'Sync Pharma',
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('medicines:sync-external', [
            '--limit'     => 10,
            '--max-pages' => 1,
            '--target'    => 'master',
        ])
        ->assertSuccessful();

        $this->assertDatabaseHas('medicine_master', [
            'medicine_id'   => 'ext_401',
            'medicine_name' => 'Sync Command Drug 10mg',
        ]);
    }

    public function test_unauthenticated_access_rejected_on_all_endpoints(): void
    {
        $this->getJson('/api/medicines/search?q=test')->assertStatus(401);
        $this->getJson('/api/medicines/master?q=test')->assertStatus(401);
        $this->postJson('/api/medicines/promote', ['name' => 'Test'])->assertStatus(401);
    }

    public function test_promote_endpoint_is_protected_by_auth_and_roles(): void
    {
        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_auth_1',
            'medicine_name'       => 'Auth Protected Drug',
            'source'              => 'External_API',
            'source_reference'    => 'auth_1',
            'verification_status' => 'verified',
        ]);

        // 1. Guest request must be rejected (401)
        $guestResponse = $this->postJson('/api/medicines/promote', [
            'master_id' => $master->id,
        ]);
        $guestResponse->assertStatus(401);

        // 2. Unauthorized role (doctor) must be forbidden (403)
        $doctor = User::factory()->create(['role' => 'doctor']);
        $doctorResponse = $this->actingAs($doctor)->postJson('/api/medicines/promote', [
            'master_id' => $master->id,
        ]);
        $doctorResponse->assertStatus(403);

        // 3. Authorized role (cashier) must succeed (200)
        $cashier = User::factory()->create(['role' => 'cashier']);
        $cashierResponse = $this->actingAs($cashier)->postJson('/api/medicines/promote', [
            'master_id'  => $master->id,
            'unit_price' => 12.00,
        ]);
        $cashierResponse->assertStatus(200);
        $cashierResponse->assertJson(['success' => true]);

        // 4. Authorized role (pharmacist) must succeed (200)
        $pharmacist = User::factory()->create(['role' => 'pharmacist']);
        $masterPharm = MedicineMaster::create([
            'medicine_id'         => 'ext_auth_pharm',
            'medicine_name'       => 'Pharm Protected Drug',
            'source'              => 'External_API',
            'source_reference'    => 'auth_pharm',
            'verification_status' => 'verified',
        ]);
        $pharmacistResponse = $this->actingAs($pharmacist)->postJson('/api/medicines/promote', [
            'master_id'  => $masterPharm->id,
            'unit_price' => 15.00,
        ]);
        $pharmacistResponse->assertStatus(200);
        $pharmacistResponse->assertJson(['success' => true]);

        // 5. Authorized role (admin) must succeed (200)
        $admin = User::factory()->create(['role' => 'admin']);
        $master2 = MedicineMaster::create([
            'medicine_id'         => 'ext_auth_2',
            'medicine_name'       => 'Admin Drug',
            'source'              => 'External_API',
            'source_reference'    => 'auth_2',
            'verification_status' => 'verified',
        ]);
        $adminResponse = $this->actingAs($admin)->postJson('/api/medicines/promote', [
            'master_id' => $master2->id,
        ]);
        $adminResponse->assertStatus(200);
    }

    public function test_search_order_prioritizes_inventory_first_then_master_with_starts_with_before_contains(): void
    {
        $user = User::factory()->create(['role' => 'cashier']);
        $cat = Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Cat', 'product_type' => 'medicine']);

        // Create inventory medicines: one starts-with, one contains
        $invStartsWith = Medicine::create([
            'category_id'  => $cat->id,
            'name'         => 'Acogest 10mg Tab',
            'product_type' => 'medicine',
            'status'       => 'active',
            'base_unit'    => 'Tablet',
            'unit_price'   => 10.00,
        ]);
        $invContains = Medicine::create([
            'category_id'  => $cat->id,
            'name'         => 'Plus Acogest 20mg Tab',
            'product_type' => 'medicine',
            'status'       => 'active',
            'base_unit'    => 'Tablet',
            'unit_price'   => 15.00,
        ]);

        // Create master medicines: one starts-with, one contains
        $masterStartsWith = MedicineMaster::create([
            'medicine_id'         => 'ext_order_1',
            'medicine_name'       => 'Acogest Forte Syrup',
            'dosage_form'         => 'Syrup',
            'source'              => 'External_API',
            'source_reference'    => 'order_1',
            'verification_status' => 'verified',
        ]);
        $masterContains = MedicineMaster::create([
            'medicine_id'         => 'ext_order_2',
            'medicine_name'       => 'Super Acogest Drops',
            'dosage_form'         => 'Drop',
            'source'              => 'External_API',
            'source_reference'    => 'order_2',
            'verification_status' => 'verified',
        ]);

        $response = $this->actingAs($user)->getJson('/api/medicines/search?q=Acogest');
        $response->assertStatus(200);
        $items = $response->json();

        $this->assertCount(4, $items);

        // Inventory results first: starts-with then contains
        $this->assertEquals('inventory', $items[0]['source']);
        $this->assertEquals('Acogest 10mg Tab', $items[0]['name']);

        $this->assertEquals('inventory', $items[1]['source']);
        $this->assertEquals('Plus Acogest 20mg Tab', $items[1]['name']);

        // Master results second: starts-with then contains
        $this->assertEquals('master', $items[2]['source']);
        $this->assertEquals('Acogest Forte Syrup', $items[2]['name']);

        $this->assertEquals('master', $items[3]['source']);
        $this->assertEquals('Super Acogest Drops', $items[3]['name']);
    }

    public function test_search_endpoint_returns_source_and_excludes_promoted_master_duplicates(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // 1. Create a Master Drug
        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_dup_100',
            'medicine_name'       => 'Unique Test Syrup',
            'dosage_form'         => 'Syrup',
            'source'              => 'External_API',
            'source_reference'    => 'dup_100',
            'verification_status' => 'verified',
        ]);

        // 2. Search before promotion -> must have source 'master'
        $response1 = $this->actingAs($admin)->getJson('/api/medicines/search?q=Unique Test Syrup');
        $response1->assertStatus(200);
        $items1 = $response1->json();
        $this->assertCount(1, $items1);
        $this->assertEquals('master', $items1[0]['source']);
        $this->assertEquals('Unique Test Syrup', $items1[0]['name']);

        // 3. Promote it into operational medicines table
        $this->actingAs($admin)->postJson('/api/medicines/promote', [
            'master_id'  => $master->id,
            'unit_price' => 50.00,
        ])->assertStatus(200);

        // 4. Search after promotion -> must have source 'inventory' and NO duplicates!
        $response2 = $this->actingAs($admin)->getJson('/api/medicines/search?q=Unique Test Syrup');
        $response2->assertStatus(200);
        $items2 = $response2->json();

        // Must ONLY appear once (no duplicate as master)
        $this->assertCount(1, $items2);
        $this->assertEquals('inventory', $items2[0]['source']);
        $this->assertEquals('Unique Test Syrup', $items2[0]['name']);
    }

    public function test_double_promote_creates_only_one_medicine_and_one_packaging_record(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_double_1',
            'medicine_name'       => 'Double Promote Medicine',
            'dosage_form'         => 'Tablet',
            'source'              => 'External_API',
            'source_reference'    => 'double_1',
            'verification_status' => 'verified',
        ]);

        // First promotion
        $res1 = $this->actingAs($cashier)->postJson('/api/medicines/promote', [
            'master_id'  => $master->id,
            'unit_price' => 20.00,
        ]);
        $res1->assertStatus(200);
        $medId1 = $res1->json('data.id');

        // Second promotion of the exact same master item
        $res2 = $this->actingAs($cashier)->postJson('/api/medicines/promote', [
            'master_id'  => $master->id,
            'unit_price' => 20.00,
        ]);
        $res2->assertStatus(200);
        $medId2 = $res2->json('data.id');

        // Must return the exact same medicine ID
        $this->assertEquals($medId1, $medId2);

        // Assert exactly 1 medicine record in database
        $this->assertEquals(1, Medicine::where('name', 'Double Promote Medicine')->count());

        // Assert exactly 1 packaging record in database for this medicine
        $this->assertEquals(1, \App\Models\MedicinePackaging::where('medicine_id', $medId1)->count());

        // Assert exactly 1 inventory record in database
        $this->assertEquals(1, \App\Models\Inventory::where('medicine_id', $medId1)->count());
    }

    public function test_pos_counter_can_promote_and_add_to_cart(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_pos_99',
            'medicine_name'       => 'POS Master Special',
            'dosage_form'         => 'Tablet',
            'source'              => 'External_API',
            'source_reference'    => 'pos_99',
            'verification_status' => 'verified',
        ]);

        Livewire::actingAs($cashier)
            ->test(PosCounter::class)
            ->set('search', 'POS Master')
            ->call('promoteAndAddToCart', $master->id)
            ->assertSet('search', '')
            ->assertSee('POS Master Special');

        $this->assertDatabaseHas('medicines', [
            'name' => 'POS Master Special',
        ]);
    }

    public function test_purchase_create_can_promote_and_add_to_purchase_row(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_purch_88',
            'medicine_name'       => 'Purchase Master Drug',
            'dosage_form'         => 'Tablet',
            'source'              => 'External_API',
            'source_reference'    => 'purch_88',
            'verification_status' => 'verified',
        ]);

        Livewire::actingAs($admin)
            ->test(PurchaseCreate::class)
            ->set('medicineSearch', 'Purchase Master')
            ->call('promoteAndAddToPurchase', $master->id)
            ->assertSee('Purchase Master Drug');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Purchase Master Drug',
        ]);
    }

    public function test_purchase_create_can_promote_and_select_master_for_price_entry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_purch_77',
            'medicine_name'       => 'Purchase Select Drug',
            'dosage_form'         => 'Tablet',
            'source'              => 'External_API',
            'source_reference'    => 'purch_77',
            'verification_status' => 'verified',
        ]);

        Livewire::actingAs($admin)
            ->test(PurchaseCreate::class)
            ->set('medicineSearch', 'Purchase Select')
            ->call('promoteAndSelectMaster', $master->id)
            ->assertSet('medicineSearch', 'Purchase Select Drug')
            ->assertSet('purchase_price', '');

        $this->assertDatabaseHas('medicines', [
            'name' => 'Purchase Select Drug',
        ]);
    }

    public function test_add_medicine_screen_searches_and_auto_fills_from_master_formulary(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $master = MedicineMaster::create([
            'medicine_id'         => 'ext_add_med_99',
            'medicine_name'       => 'Panadol CF Caplet',
            'brand_name'          => 'Panadol',
            'generic_name'        => 'Paracetamol + Pseudoephedrine',
            'dosage_form'         => 'Tablet',
            'category'            => 'Pain Relief & Analgesics',
            'manufacturer'        => 'GSK Pakistan',
            'source'              => 'External_API',
            'source_reference'    => 'add_med_99',
            'verification_status' => 'verified',
        ]);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\AddMedicine::class)
            ->set('product_search', 'Panadol CF')
            ->assertSee('Panadol CF Caplet')
            ->assertSee('Master Formulary Catalog')
            ->call('selectMasterProduct', $master->id)
            ->assertSet('name', 'Panadol CF Caplet')
            ->assertSet('generic_name', 'Paracetamol + Pseudoephedrine')
            ->assertSet('manufacturer', 'GSK Pakistan')
            ->assertSet('dosage_form', 'Tablet')
            ->assertSet('base_unit', 'Tablet');
    }
}
