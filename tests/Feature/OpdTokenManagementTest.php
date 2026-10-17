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

    /**
     * TEST 8: Automatically fetch Doctor Consultation Fee
     * Verifies that the doctor's consultation fee configured during registration is fetched and used as base.
     */
    public function test_8_doctor_consultation_fee_is_automatically_fetched_and_used_in_billing(): void
    {
        $specialist = Doctor::create([
            'name'             => 'Dr. Fatima',
            'specialization'   => 'Cardiologist',
            'consultation_fee' => 1500.00,
            'doctor_share_percentage' => 80.00,
            'is_active'        => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $specialist->id)
            ->set('hospital_service_id', '') // standard consultation only
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $token = PatientToken::where('patient_id', $this->patient->id)
            ->where('doctor_id', $specialist->id)
            ->first();

        $this->assertNotNull($token);
        $this->assertEquals(1500.00, (float) $token->consultation_fee);
        $this->assertEquals(1500.00, (float) $token->charged_amount);
        $this->assertEquals(80.00, (float) $token->doctor_share_percentage);
        $this->assertEquals(1200.00, (float) $token->doctor_share_amount); // 80% of 1500
        $this->assertEquals(300.00, (float) $token->hospital_share_amount); // 20% of 1500

        // Hospital Bill verification
        $this->assertNotNull($token->bill);
        $this->assertEquals(1500.00, (float) $token->bill->total_amount);
        $this->assertEquals(1500.00, (float) $token->bill->paid_amount);
        $this->assertEquals(1200.00, (float) $token->bill->doctor_share);
        $this->assertEquals(300.00, (float) $token->bill->hospital_share);
    }

    /**
     * TEST 9: Adding Additional Hospital Service adds its fee to Doctor Consultation Fee
     */
    public function test_9_additional_hospital_service_fee_adds_to_doctor_fee(): void
    {
        $procedure = \App\Models\HospitalService::create([
            'name' => 'Sugar Check Procedure',
            'code' => 'TEST-SUGAR',
            'service_type' => 'diagnostic',
            'default_fee' => 200.00,
            'doctor_share_percentage' => 0.00,
            'hospital_share_percentage' => 100.00,
            'is_active' => true,
        ]);

        // Doctor has consultation_fee = 500 (70% doc, 30% hosp)
        // Additional service fee = 200 (0% doc, 100% hosp)
        // Total Gross Fee = 500 + 200 = 700.00
        // Doctor Share = 350.00 (500 * 70%) + 0 = 350.00
        // Hospital Share = 150.00 + 200.00 = 350.00
        // Effective doc pct = 50.00%
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('hospital_service_id', (string) $procedure->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(700.00, (float) $token->consultation_fee);
        $this->assertEquals(700.00, (float) $token->charged_amount);
        $this->assertEquals(350.00, (float) $token->doctor_share_amount);
        $this->assertEquals(350.00, (float) $token->hospital_share_amount);
        $this->assertEquals(50.00, (float) $token->doctor_share_percentage);
    }

    /**
     * TEST 10: Safe validation message when Doctor has missing consultation fee
     */
    public function test_10_doctor_with_missing_consultation_fee_displays_validation_error(): void
    {
        $docNoFee = Doctor::create([
            'name'             => 'Dr. Incomplete',
            'specialization'   => 'Surgeon',
            'consultation_fee' => null,
            'is_active'        => true,
        ]);

        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $docNoFee->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasErrors(['doctor_id']);
    }

    /**
     * TEST 11: Partial Payment on Combined Doctor Fee and Additional Service Fee
     */
    public function test_11_partial_payment_with_doctor_fee_and_additional_service(): void
    {
        $dressing = \App\Models\HospitalService::create([
            'name' => 'Wound Dressing',
            'code' => 'TEST-DRESS',
            'service_type' => 'procedure',
            'default_fee' => 500.00,
            'doctor_share_percentage' => 20.00,
            'hospital_share_percentage' => 80.00,
            'is_active' => true,
        ]);

        // Doctor consultation fee = 500 (70% doc = 350)
        // Dressing fee = 500 (20% doc = 100)
        // Total Gross = 1000, Total Doc Share = 450 (45%)
        // Partial paid amount = 600
        // Doc share recognized on 600 = round(600 * 45%) = 270.00
        // Hospital share on 600 = 330.00
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $this->doctor->id)
            ->set('hospital_service_id', (string) $dressing->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->set('fee_type', 'partial')
            ->set('paid_amount', 600.00)
            ->call('generateToken')
            ->assertHasNoErrors();

        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(1000.00, (float) $token->consultation_fee);
        $this->assertEquals(600.00, (float) $token->charged_amount);
        $this->assertEquals(270.00, (float) $token->doctor_share_amount);
        $this->assertEquals(330.00, (float) $token->hospital_share_amount);
        $this->assertEquals(45.00, (float) $token->doctor_share_percentage);
    }

    /**
     * TEST 12: Controller store endpoint automatically uses doctor consultation fee and adds additional service
     */
    public function test_12_controller_store_auto_fetches_doctor_fee_and_adds_additional_service(): void
    {
        $procedure = \App\Models\HospitalService::create([
            'name' => 'IV Drip Administration',
            'code' => 'TEST-DRIP',
            'service_type' => 'nursing',
            'default_fee' => 300.00,
            'doctor_share_percentage' => 10.00,
            'hospital_share_percentage' => 90.00,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('patient-tokens.store'), [
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $procedure->id,
            'patient_mode'        => 'existing',
            'patient_id'          => $this->patient->id,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
        ]);

        $response->assertSessionHasNoErrors();

        // Doctor fee 500 + Drip 300 = 800
        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(800.00, (float) $token->consultation_fee);
        $this->assertEquals(800.00, (float) $token->charged_amount);
        // Doc share = (500 * 70%) + (300 * 10%) = 350 + 30 = 380.00
        $this->assertEquals(380.00, (float) $token->doctor_share_amount);
        $this->assertEquals(420.00, (float) $token->hospital_share_amount);
    }

    /**
     * TEST 13: Controller store endpoint rejects doctor with missing consultation fee
     */
    public function test_13_controller_store_rejects_doctor_with_missing_fee(): void
    {
        $docNoFee = Doctor::create([
            'name'             => 'Dr. NullFee',
            'specialization'   => 'Physician',
            'consultation_fee' => null,
            'is_active'        => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('patient-tokens.store'), [
            'doctor_id'           => $docNoFee->id,
            'patient_mode'        => 'existing',
            'patient_id'          => $this->patient->id,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
        ]);

        $response->assertSessionHasErrors(['doctor_id']);
    }
}
