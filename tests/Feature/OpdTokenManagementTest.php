<?php

namespace Tests\Feature;

use App\Livewire\Opd\TokenManagement;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpdTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Doctor $doctor;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->doctor = Doctor::create([
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'consultation_fee' => 500.00,
            'phone'            => '0300-1111222',
            'is_active'        => true,
        ]);

        $this->patient = Patient::create([
            'name'                => 'Muhammad Ali',
            'father_husband_name' => 'Ali Raza',
            'age'                 => 28,
            'gender'              => 'Male',
            'phone'               => '03001234567',
            'cnic'                => '35201-1234567-1',
            'address'             => 'Lahore',
        ]);
    }

    public function test_opd_tokens_page_requires_authentication(): void
    {
        $response = $this->get('/opd/tokens');
        $response->assertRedirect('/login');
    }

    public function test_opd_tokens_page_loads_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->user)->get('/opd/tokens');
        $response->assertStatus(200);
        $response->assertSee('OPD Token Management');
    }

    /**
     * TEST 1: Existing Patient + Paid Doctor Visit
     * Expected: Patient auto-fills, doctor fee appears, token generated #001
     */
    public function test_1_existing_patient_paid_doctor_visit(): void
    {
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->assertSet('selected_patient_id', (string) $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(1, $token->token_number);
        $this->assertEquals('001', $token->formatted_token_number);
        $this->assertEquals('paid', $token->payment_type);
        $this->assertEquals(500.00, (float) $token->consultation_fee);
        $this->assertEquals(500.00, (float) $token->charged_amount);
        $this->assertEquals('waiting', $token->status);
    }

    /**
     * TEST 2: Existing Patient + Free Visit
     * Expected: Fee Type = Free, free reason required, token generated, fee displayed as FREE
     */
    public function test_2_existing_patient_free_visit(): void
    {
        // Try generating without free reason -> should fail validation
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'free')
            ->set('free_reason', '')
            ->call('generateToken')
            ->assertHasErrors(['free_reason']);

        // Now provide free reason -> success
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'free')
            ->set('free_reason', 'Poor Patient')
            ->call('generateToken')
            ->assertHasNoErrors();

        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('free', $token->payment_type);
        $this->assertEquals(500.00, (float) $token->consultation_fee); // preserved original fee
        $this->assertEquals(0.00, (float) $token->charged_amount);
        $this->assertEquals('Poor Patient', $token->free_reason);
        $this->assertEquals('FREE', $token->display_fee);
    }

    /**
     * TEST 3: New Patient
     * Expected: Manual patient registration, patient saved, token generated
     */
    public function test_3_new_patient_registration_and_token_generation(): void
    {
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'new')
            ->set('new_name', 'Sara Khan')
            ->set('new_father_husband_name', 'Tariq Khan')
            ->set('new_age', 25)
            ->set('new_gender', 'Female')
            ->set('new_phone', '03219988776')
            ->set('new_cnic', '35201-9988776-2')
            ->set('new_address', 'Gulberg, Lahore')
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $newPatient = Patient::where('name', 'Sara Khan')->first();
        $this->assertNotNull($newPatient);
        $this->assertEquals('Female', $newPatient->gender);
        $this->assertEquals('03219988776', $newPatient->phone);

        $token = PatientToken::where('patient_id', $newPatient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(1, $token->token_number);
    }

    /**
     * TEST 4: Multiple Patients (#001, #002, #003)
     */
    public function test_4_multiple_patients_sequential_token_numbering(): void
    {
        $patient2 = Patient::create(['name' => 'Patient Two', 'age' => 40, 'gender' => 'Male']);
        $patient3 = Patient::create(['name' => 'Patient Three', 'age' => 50, 'gender' => 'Female']);

        $component = Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id);

        // Token 1
        $component->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        // Token 2
        $component->set('patient_mode', 'existing')
            ->call('selectPatient', $patient2->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        // Token 3
        $component->set('patient_mode', 'existing')
            ->call('selectPatient', $patient3->id)
            ->set('fee_type', 'free')
            ->set('free_reason', 'Emergency')
            ->call('generateToken')
            ->assertHasNoErrors();

        $tokens = PatientToken::orderBy('token_number', 'asc')->get();
        $this->assertCount(3, $tokens);
        $this->assertEquals(1, $tokens[0]->token_number);
        $this->assertEquals('001', $tokens[0]->formatted_token_number);
        $this->assertEquals(2, $tokens[1]->token_number);
        $this->assertEquals('002', $tokens[1]->formatted_token_number);
        $this->assertEquals(3, $tokens[2]->token_number);
        $this->assertEquals('003', $tokens[2]->formatted_token_number);
    }

    /**
     * TEST 5: New Day Token Numbering starts from #001
     */
    public function test_5_new_day_token_numbering_starts_from_001(): void
    {
        // Yesterday's token
        PatientToken::create([
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'token_number'     => 15,
            'token_date'       => now()->subDay()->toDateString(),
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        // Today's token generation
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $todayToken = PatientToken::where('token_date', now()->toDateString())->first();
        $this->assertNotNull($todayToken);
        $this->assertEquals(1, $todayToken->token_number);
        $this->assertEquals('001', $todayToken->formatted_token_number);
    }

    /**
     * TEST 6: Queue Status Workflow (Waiting → In Consultation → Completed)
     */
    public function test_6_queue_status_workflow(): void
    {
        $token = PatientToken::create([
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'waiting',
        ]);

        $this->assertEquals('waiting', $token->status);

        // Start consultation
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->call('startConsultation', $token->id);

        $token->refresh();
        $this->assertEquals('in_consultation', $token->status);
        $this->assertNotNull($token->called_at);

        // Complete consultation
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->call('completeConsultation', $token->id);

        $token->refresh();
        $this->assertEquals('completed', $token->status);
        $this->assertNotNull($token->completed_at);
    }

    /**
     * TEST 7: Cancel Token
     */
    public function test_7_cancel_token(): void
    {
        $token = PatientToken::create([
            'patient_id'       => $this->patient->id,
            'doctor_id'        => $this->doctor->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'waiting',
        ]);

        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->call('cancelToken', $token->id);

        $token->refresh();
        $this->assertEquals('cancelled', $token->status);

        // Next token should be 2 (#002), not reusing 1
        $patient2 = Patient::create(['name' => 'Next Patient', 'age' => 30, 'gender' => 'Male']);

        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $patient2->id)
            ->set('fee_type', 'paid')
            ->call('generateToken');

        $nextToken = PatientToken::where('patient_id', $patient2->id)->first();
        $this->assertEquals(2, $nextToken->token_number);
    }
}
