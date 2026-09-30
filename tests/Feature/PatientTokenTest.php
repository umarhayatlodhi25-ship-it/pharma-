<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTokenTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Doctor $doctorA;
    protected Doctor $doctorB;
    protected Patient $patientA;
    protected Patient $patientB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->doctorA = Doctor::create([
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'consultation_fee' => 1000.00,
            'phone'            => '0300-1111222',
            'is_active'        => true,
        ]);

        $this->doctorB = Doctor::create([
            'name'             => 'Dr. Ali',
            'specialization'   => 'Pediatrician',
            'consultation_fee' => 1200.00,
            'phone'            => '0321-2222333',
            'is_active'        => true,
        ]);

        $this->patientA = Patient::create([
            'name'                => 'Tariq Mehmood',
            'father_husband_name' => 'Bashir Ahmed',
            'age'                 => 45,
            'gender'              => 'Male',
            'phone'               => '03001112233',
            'cnic'                => '35201-1112233-1',
        ]);

        $this->patientB = Patient::create([
            'name'                => 'Ayesha Bibi',
            'father_husband_name' => 'Rashid Ali',
            'age'                 => 32,
            'gender'              => 'Female',
            'phone'               => '03214445566',
            'cnic'                => '35201-4445566-2',
        ]);
    }

    /**
     * Test unauthenticated access redirected to login.
     */
    public function test_unauthenticated_access_redirects_to_login(): void
    {
        $response = $this->get('/patient-tokens');
        $response->assertRedirect('/login');

        $response = $this->get('/patient-tokens/create');
        $response->assertRedirect('/login');

        $response = $this->post('/patient-tokens', []);
        $response->assertRedirect('/login');

        $response = $this->post('/patient-tokens/call-next');
        $response->assertRedirect('/login');
    }

    /**
     * Test generating sequential tokens for today (001, 002) with Paid Consultation.
     */
    public function test_generate_sequential_paid_tokens_for_today(): void
    {
        // 1. Generate Paid Token for Patient A with Doctor A (PKR 1,000)
        $responseA = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'paid',
            'notes'        => 'General OPD Consultation',
        ]);

        $tokenA = PatientToken::where('patient_id', $this->patientA->id)->first();
        $this->assertNotNull($tokenA);
        $responseA->assertRedirect(route('patient-tokens.show', $tokenA->id));

        $this->assertEquals(1, $tokenA->token_number);
        $this->assertEquals('001', $tokenA->formatted_token_number);
        $this->assertEquals($this->doctorA->id, $tokenA->doctor_id);
        $this->assertEquals('paid', $tokenA->payment_type);
        $this->assertEquals(1000.00, (float) $tokenA->consultation_fee);
        $this->assertEquals(1000.00, (float) $tokenA->charged_amount);
        $this->assertEquals('waiting', $tokenA->status);
        $this->assertEquals(now()->toDateString(), $tokenA->token_date->toDateString());

        // 2. Generate Paid Token for Patient B with Doctor B (PKR 1,200)
        $responseB = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorB->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientB->id,
            'payment_type' => 'paid',
        ]);

        $tokenB = PatientToken::where('patient_id', $this->patientB->id)->first();
        $this->assertNotNull($tokenB);
        $responseB->assertRedirect(route('patient-tokens.show', $tokenB->id));

        $this->assertEquals(2, $tokenB->token_number);
        $this->assertEquals('002', $tokenB->formatted_token_number);
        $this->assertEquals(1200.00, (float) $tokenB->charged_amount);
    }

    /**
     * Test generating Free Consultation Token (fee = doctor fee, charged = 0, free_reason recorded).
     */
    public function test_generate_free_consultation_token(): void
    {
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'free',
            'free_reason'  => 'Poor Patient',
        ]);

        $token = PatientToken::where('patient_id', $this->patientA->id)->first();
        $this->assertNotNull($token);
        $response->assertRedirect(route('patient-tokens.show', $token->id));

        $this->assertEquals('free', $token->payment_type);
        $this->assertEquals(1000.00, (float) $token->consultation_fee);
        $this->assertEquals(0.00, (float) $token->charged_amount);
        $this->assertEquals('Poor Patient', $token->free_reason);
        $this->assertNull($token->other_reason);
    }

    /**
     * Test free consultation with reason 'Other' requires other_reason.
     */
    public function test_free_consultation_with_other_requires_other_reason(): void
    {
        // Missing other_reason
        $responseFail = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'free',
            'free_reason'  => 'Other',
            'other_reason' => '',
        ]);

        $responseFail->assertSessionHasErrors(['other_reason']);

        // With other_reason provided
        $responseSuccess = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'free',
            'free_reason'  => 'Other',
            'other_reason' => 'Hospital Director Discretionary Free Voucher',
        ]);

        $token = PatientToken::where('patient_id', $this->patientA->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals('Other', $token->free_reason);
        $this->assertEquals('Hospital Director Discretionary Free Voucher', $token->other_reason);
    }

    /**
     * Test free consultation requires free_reason.
     */
    public function test_free_consultation_requires_reason(): void
    {
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'free',
            'free_reason'  => '',
        ]);

        $response->assertSessionHasErrors(['free_reason']);
    }

    /**
     * Test registering a new patient directly during token generation.
     */
    public function test_new_patient_mode_registers_patient_and_creates_token(): void
    {
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'           => $this->doctorA->id,
            'patient_mode'        => 'new',
            'patient_name'        => 'Kashif Mehmood',
            'father_husband_name' => 'Mehmood Ul Hassan',
            'age'                 => 28,
            'gender'              => 'Male',
            'phone'               => '03459998877',
            'cnic'                => '35201-9998877-3',
            'address'             => 'Gulberg III, Lahore',
            'payment_type'        => 'paid',
        ]);

        $newPatient = Patient::where('name', 'Kashif Mehmood')->first();
        $this->assertNotNull($newPatient);
        $this->assertStringStartsWith('PT-', $newPatient->patient_number);

        $token = PatientToken::where('patient_id', $newPatient->id)->first();
        $this->assertNotNull($token);
        $this->assertEquals(1, $token->token_number);
        $this->assertEquals('001', $token->formatted_token_number);
        $this->assertEquals(1000.00, (float) $token->charged_amount);
    }

    /**
     * Test duplicate warning when new patient has existing CNIC/phone.
     */
    public function test_new_patient_warns_on_duplicate_cnic_or_phone(): void
    {
        $initialPatientCount = Patient::count();

        // Attempt new patient registration with Patient A's CNIC
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'new',
            'patient_name' => 'Tariq Duplicate',
            'age'          => 45,
            'gender'       => 'Male',
            'cnic'         => '35201-1112233-1', // Same as patientA
            'payment_type' => 'paid',
        ]);

        $response->assertSessionHas('warning');
        $response->assertSessionHas('likely_duplicate_id', $this->patientA->id);
        $this->assertEquals($initialPatientCount, Patient::count()); // No duplicate created
    }

    /**
     * Test duplicate active token prevention for the same patient on the same day.
     */
    public function test_prevents_duplicate_active_token_for_same_patient_today(): void
    {
        // First token
        $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'paid',
        ]);

        $this->assertEquals(1, PatientToken::count());

        // Second token attempt for same patient while first is waiting
        $duplicateResponse = $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorB->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientA->id,
            'payment_type' => 'paid',
        ]);

        $duplicateResponse->assertSessionHas('warning');
        $this->assertEquals(1, PatientToken::count()); // Still only 1 token
    }

    /**
     * Test token numbering restarts from 1 on a different date.
     */
    public function test_token_numbering_restarts_daily(): void
    {
        $yesterday = now()->subDay()->toDateString();

        // Create token for yesterday
        PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 15,
            'token_date'       => $yesterday,
            'status'           => 'completed',
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        // Generate token for today
        $this->actingAs($this->user)->post('/patient-tokens', [
            'doctor_id'    => $this->doctorA->id,
            'patient_mode' => 'existing',
            'patient_id'   => $this->patientB->id,
            'payment_type' => 'paid',
        ]);

        $todayToken = PatientToken::where('token_date', now()->toDateString())->first();
        $this->assertNotNull($todayToken);
        $this->assertEquals(1, $todayToken->token_number);
        $this->assertEquals('001', $todayToken->formatted_token_number);
    }

    /**
     * Test calling next patient in queue.
     */
    public function test_call_next_patient(): void
    {
        $token1 = PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        $token2 = PatientToken::create([
            'patient_id'       => $this->patientB->id,
            'doctor_id'        => $this->doctorB->id,
            'token_number'     => 2,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'free',
            'consultation_fee' => 1200.00,
            'charged_amount'   => 0.00,
            'free_reason'      => 'Staff',
        ]);

        // Call Next: should call Token 1
        $response = $this->actingAs($this->user)->post('/patient-tokens/call-next');
        $response->assertRedirect(route('patient-tokens.index'));
        $response->assertSessionHas('success');

        $token1->refresh();
        $this->assertEquals('called', $token1->status);
        $this->assertNotNull($token1->called_at);

        $token2->refresh();
        $this->assertEquals('waiting', $token2->status);
    }

    /**
     * Test completing a called token.
     */
    public function test_complete_token(): void
    {
        $token = PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'status'           => 'called',
            'called_at'        => now()->subMinutes(5),
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        $response = $this->actingAs($this->user)->post("/patient-tokens/{$token->id}/complete");
        $response->assertSessionHas('success');

        $token->refresh();
        $this->assertEquals('completed', $token->status);
        $this->assertNotNull($token->completed_at);
    }

    /**
     * Test cancelling a token (record preserved).
     */
    public function test_cancel_token(): void
    {
        $token = PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        $response = $this->actingAs($this->user)->post("/patient-tokens/{$token->id}/cancel");
        $response->assertSessionHas('success');

        $token->refresh();
        $this->assertEquals('cancelled', $token->status);
        $this->assertDatabaseHas('patient_tokens', ['id' => $token->id, 'status' => 'cancelled']);
    }

    /**
     * Test Daily OPD Financial Summary calculates SUM(charged_amount), not consultation_fee.
     */
    public function test_daily_financial_summary_calculates_from_charged_amount(): void
    {
        // 1 Paid Token: Fee 1000, Charged 1000
        PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        // 1 Free Token: Fee 1200, Charged 0
        PatientToken::create([
            'patient_id'       => $this->patientB->id,
            'doctor_id'        => $this->doctorB->id,
            'token_number'     => 2,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'free',
            'consultation_fee' => 1200.00,
            'charged_amount'   => 0.00,
            'free_reason'      => 'Poor Patient',
        ]);

        $response = $this->actingAs($this->user)->get('/patient-tokens');
        $response->assertStatus(200);
        $response->assertSee('OPD Token Queue');
        $response->assertSee("Today's Tokens", false);
        $response->assertSee('Paid Patients');
        $response->assertSee('Free Patients');
        $response->assertSee('Total Collected');
        // Total Collected should be 1,000 (charged_amount), not 2,200 (consultation_fee)
        $response->assertSee('PKR 1,000');
    }

    /**
     * Test printable token slip endpoint.
     */
    public function test_printable_token_slip(): void
    {
        $token = PatientToken::create([
            'patient_id'       => $this->patientA->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 7,
            'token_date'       => now()->toDateString(),
            'status'           => 'waiting',
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
        ]);

        $response = $this->actingAs($this->user)->get("/patient-tokens/{$token->id}/print");
        $response->assertStatus(200);
        $response->assertSee('OPD TOKEN');
        $response->assertSee('TOKEN NO.');
        $response->assertSee('Tariq Mehmood');
        $response->assertSee('Dr. Ahmed');
        $response->assertSee('PKR 1,000');
        $response->assertSee('WAITING');
        $response->assertSee('Please wait for your token');
        // Sensitive info should NOT appear
        $response->assertDontSee('35201-1112233-1');
    }

    /**
     * Test AJAX patient search endpoint.
     */
    public function test_ajax_patient_search(): void
    {
        $response = $this->actingAs($this->user)->get('/patients/search?q=Tariq');
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name' => 'Tariq Mehmood',
        ]);

        $responseByPhone = $this->actingAs($this->user)->get('/patients/search?q=03001112233');
        $responseByPhone->assertStatus(200);
        $responseByPhone->assertJsonFragment([
            'phone' => '03001112233',
        ]);
    }

    /**
     * Test AJAX duplicate check endpoint.
     */
    public function test_ajax_patient_duplicate_check(): void
    {
        $responseFound = $this->actingAs($this->user)->get('/patients/check-duplicate?phone=03001112233');
        $responseFound->assertStatus(200);
        $responseFound->assertJson(['found' => true]);

        $responseNotFound = $this->actingAs($this->user)->get('/patients/check-duplicate?phone=03999999999');
        $responseNotFound->assertStatus(200);
        $responseNotFound->assertJson(['found' => false]);
    }
}
