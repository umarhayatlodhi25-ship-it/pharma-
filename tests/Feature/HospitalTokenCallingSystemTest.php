<?php

namespace Tests\Feature;

use App\Models\AccountProfile;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\Role;
use App\Models\SyncOutbox;
use App\Models\TokenCallLog;
use App\Models\User;
use App\Services\TokenQueueService;
use App\Services\TokenSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalTokenCallingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $doctorUserA;
    protected User $doctorUserB;
    protected Doctor $doctorA;
    protected Doctor $doctorB;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        AccountProfile::clearCache();
        AccountProfile::create([
            'account_name'               => 'Test Memorial Hospital',
            'token_prefix'               => 'MED',
            'token_start_number'         => 1,
            'daily_token_reset'          => true,
            'queue_scope'                => 'doctor_wise',
            'voice_announcement_enabled' => true,
            'voice_announcement_language'=> 'both',
            'voice_repeat_count'         => 1,
            'voice_volume'               => 100,
            'lcd_refresh_interval_sec'   => 3,
        ]);

        $adminRole = Role::where('slug', 'admin')->first() ?? Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $doctorRole = Role::where('slug', 'doctor')->first() ?? Role::create(['name' => 'Doctor', 'slug' => 'doctor']);

        $this->adminUser = User::factory()->create([
            'email'   => 'testadmin@hospital.com',
            'role'    => 'admin',
        ]);

        $this->doctorUserA = User::factory()->create([
            'email'   => 'doctorA@hospital.com',
            'role'    => 'doctor',
        ]);

        $this->doctorA = Doctor::create([
            'user_id'                 => $this->doctorUserA->id,
            'name'                    => 'Ali Raza',
            'specialization'          => 'Cardiology',
            'room_number'             => 'Room 101',
            'consultation_fee'        => 1500.00,
            'doctor_share_percentage' => 70.00,
            'hospital_share_percentage'=> 30.00,
            'status'                  => 'active',
            'is_active'               => true,
        ]);

        $this->doctorUserB = User::factory()->create([
            'email'   => 'doctorB@hospital.com',
            'role'    => 'doctor',
        ]);

        $this->doctorB = Doctor::create([
            'user_id'                 => $this->doctorUserB->id,
            'name'                    => 'Fatima Noor',
            'specialization'          => 'Pediatrics',
            'room_number'             => 'Room 202',
            'consultation_fee'        => 1200.00,
            'doctor_share_percentage' => 80.00,
            'hospital_share_percentage'=> 20.00,
            'status'                  => 'active',
            'is_active'               => true,
        ]);

        $this->patient = Patient::create([
            'name'   => 'Muhammad Usman',
            'age'    => 35,
            'gender' => 'Male',
            'phone'  => '03001234567',
        ]);
    }

    public function test_token_number_allocation_is_atomic_and_daily_scoped()
    {
        $queueService = app(TokenQueueService::class);
        $today = now()->toDateString();

        $num1 = $queueService->allocateTokenNumbers($today, $this->doctorA->id);
        $num2 = $queueService->allocateTokenNumbers($today, $this->doctorA->id);

        $this->assertEquals(1, $num1['queue_sequence']);
        $this->assertEquals(2, $num2['queue_sequence']);
        $this->assertEquals('MED', $num1['prefix']);

        // Doctor B gets separate sequence under doctor_wise scope
        $numDocB = $queueService->allocateTokenNumbers($today, $this->doctorB->id);
        $this->assertEquals(1, $numDocB['queue_sequence']);
    }

    public function test_doctor_can_call_next_waiting_token_atomically()
    {
        $token1 = PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 1,
            'queue_sequence' => 1,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'waiting',
        ]);

        $token2 = PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 2,
            'queue_sequence' => 2,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'waiting',
        ]);

        $response = $this->actingAs($this->doctorUserA)
            ->post(route('doctor.queue.call-next'));

        $response->assertRedirect(route('doctor.queue.index'));
        $response->assertSessionHas('success');

        $token1->refresh();
        $this->assertEquals('called', $token1->status);
        $this->assertNotNull($token1->called_at);
        $this->assertEquals(1, $token1->call_count);
        $this->assertEquals('Room 101', $token1->room_number);

        // Verify call log was created
        $this->assertDatabaseHas('token_call_logs', [
            'patient_token_id' => $token1->id,
            'action'           => 'call',
            'to_status'        => 'called',
        ]);

        // Verify outbox was populated for offline-first sync
        $this->assertDatabaseHas('sync_outbox', [
            'event_type' => 'token_status_changed',
        ]);

        // Second token is still waiting
        $token2->refresh();
        $this->assertEquals('waiting', $token2->status);
    }

    public function test_complete_token_lifecycle_transitions()
    {
        $queueService = app(TokenQueueService::class);

        $token = PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 10,
            'queue_sequence' => 10,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'waiting',
        ]);

        // 1. Call
        $queueService->callToken($token, 'Room 101', $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals('called', $token->status);
        $this->assertEquals(1, $token->call_count);

        // 2. Recall
        $queueService->callToken($token, 'Room 101', $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals(2, $token->call_count);

        // 3. Start Consultation
        $queueService->startConsultation($token, $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals('in_consultation', $token->status);
        $this->assertNotNull($token->started_at);

        // 4. Hold
        $queueService->holdToken($token, 'Awaiting CBC test results', $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals('hold', $token->status);
        $this->assertNotNull($token->held_at);

        // 5. Resume
        $queueService->resumeToken($token, $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals('waiting', $token->status);

        // 6. Complete
        $queueService->completeConsultation($token, $this->doctorUserA->id);
        $token->refresh();
        $this->assertEquals('completed', $token->status);
        $this->assertNotNull($token->completed_at);
    }

    public function test_idor_protection_prevents_doctor_from_managing_another_doctors_queue()
    {
        $tokenOfDoctorB = PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'token_number'   => 5,
            'queue_sequence' => 5,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'waiting',
        ]);

        // Doctor A attempts to call Doctor B's token
        $response = $this->actingAs($this->doctorUserA)
            ->post(route('doctor.queue.call', $tokenOfDoctorB->id));

        $response->assertStatus(403);
    }

    public function test_central_lcd_display_and_live_json_endpoint()
    {
        PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 7,
            'queue_sequence' => 7,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'called',
            'called_at'      => now(),
            'room_number'    => 'Room 101',
        ]);

        PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 8,
            'queue_sequence' => 8,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'waiting',
        ]);

        // 1. Central Display Web Page
        $pageResponse = $this->get(route('token.display'));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Test Memorial Hospital');
        $pageResponse->assertSee('OPD TOKEN CALLING SYSTEM');

        // 2. Local LAN Polling API
        $apiResponse = $this->get(route('token.display.live'));
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'status',
            'server_time',
            'server_date',
            'active_tokens',
            'waiting_tokens',
            'settings' => [
                'voice_announcement_enabled',
                'voice_announcement_language',
                'voice_repeat_count',
                'voice_volume',
                'lcd_refresh_interval_sec',
                'ticker_message',
            ],
        ]);

        $json = $apiResponse->json();
        $this->assertEquals('success', $json['status']);
        $this->assertNotEmpty($json['active_tokens']);
        $this->assertEquals('MED-007', $json['active_tokens'][0]['formatted_token_number']);
        $this->assertEquals('Room 101', $json['active_tokens'][0]['room_number']);
    }

    public function test_token_reports_page_and_stats()
    {
        PatientToken::create([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_number'   => 1,
            'queue_sequence' => 1,
            'token_prefix'   => 'MED',
            'token_date'     => now()->toDateString(),
            'status'         => 'completed',
            'called_at'      => now()->subMinutes(20),
            'started_at'     => now()->subMinutes(15),
            'completed_at'   => now(),
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('reports.tokens'));

        $response->assertStatus(200);
        $response->assertSee('Token Calling', false);
        $response->assertSee('Dr. Ali Raza');
    }
}
