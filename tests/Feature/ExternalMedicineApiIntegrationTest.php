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

        // 4. Authorized role (admin) must succeed
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

    public function test_search_endpoint_returns_source_and_excludes_promoted_master_duplicates(): void
    {
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
        $response1 = $this->getJson('/api/medicines/search?q=Unique Test Syrup');
        $response1->assertStatus(200);
        $items1 = $response1->json();
        $this->assertCount(1, $items1);
        $this->assertEquals('master', $items1[0]['source']);
        $this->assertEquals('Unique Test Syrup', $items1[0]['name']);

        // 3. Promote it into operational medicines table
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/api/medicines/promote', [
            'master_id'  => $master->id,
            'unit_price' => 50.00,
        ])->assertStatus(200);

        // 4. Search after promotion -> must have source 'inventory' and NO duplicates!
        $response2 = $this->getJson('/api/medicines/search?q=Unique Test Syrup');
        $response2->assertStatus(200);
        $items2 = $response2->json();

        // Must ONLY appear once (no duplicate as master)
        $this->assertCount(1, $items2);
        $this->assertEquals('inventory', $items2[0]['source']);
        $this->assertEquals('Unique Test Syrup', $items2[0]['name']);
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
}
