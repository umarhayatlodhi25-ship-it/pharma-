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

class DoctorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);
    }

    public function test_doctors_page_requires_authentication(): void
    {
        $response = $this->get('/doctors');
        $response->assertRedirect('/login');
    }

    public function test_doctor_registration_and_list_appearance(): void
    {
        // TEST 1: Register Dr. Ahmed
        $response = $this->actingAs($this->user)->post('/doctors', [
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'qualification'    => 'MBBS',
            'phone'            => '03001234567',
            'email'            => 'ahmed@example.com',
            'gender'           => 'Male',
            'consultation_fee' => 500.00,
            'status'           => 'active',
            'address'          => 'Main Clinic, Lahore',
            'notes'            => 'Available mornings',
        ]);

        $response->assertRedirect('/doctors');
        $this->assertDatabaseHas('doctors', [
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'qualification'    => 'MBBS',
            'consultation_fee' => 500.00,
            'status'           => 'active',
            'is_active'        => 1,
        ]);

        // Verify appearance in Doctor List
        $listResponse = $this->actingAs($this->user)->get('/doctors');
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Dr. Ahmed');
        $listResponse->assertSee('General Physician');
        $listResponse->assertSee('MBBS');
        $listResponse->assertSee('500');
    }

    public function test_doctor_appears_in_opd_token_dropdown_and_populates_fee(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'qualification'    => 'MBBS',
            'consultation_fee' => 500.00,
            'status'           => 'active',
        ]);

        $patient = Patient::create([
            'name'   => 'Ali Khan',
            'phone'  => '03001234567',
            'gender' => 'Male',
            'age'    => 30,
        ]);

        // TEST 2 & TEST 3: Doctor dropdown & dynamic fee population in OPD Token
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->assertSee('Dr. Ahmed')
            ->set('doctor_id', (string) $doctor->id)
            ->assertSee('PKR 500');
    }

    public function test_consultation_fee_update_preserves_historical_opd_token_fees(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'consultation_fee' => 500.00,
            'status'           => 'active',
        ]);

        $patient = Patient::create([
            'name'   => 'First Patient',
            'phone'  => '03001234567',
            'gender' => 'Male',
            'age'    => 25,
        ]);

        // Create first token at PKR 500
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $patient->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $firstToken = PatientToken::where('patient_id', $patient->id)->first();
        $this->assertNotNull($firstToken);
        $this->assertEquals(500.00, (float) $firstToken->consultation_fee);
        $this->assertEquals(500.00, (float) $firstToken->charged_amount);

        // TEST 4: Update Doctor fee: PKR 500 -> PKR 700
        $updateResponse = $this->actingAs($this->user)->put("/doctors/{$doctor->id}", [
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'qualification'    => 'MBBS, FCPS',
            'consultation_fee' => 700.00,
            'status'           => 'active',
        ]);
        $updateResponse->assertRedirect("/doctors/{$doctor->id}");

        $this->assertDatabaseHas('doctors', [
            'id'               => $doctor->id,
            'consultation_fee' => 700.00,
        ]);

        // Generate second token for new fee
        $patient2 = Patient::create([
            'name'   => 'Second Patient',
            'phone'  => '03009876543',
            'gender' => 'Female',
            'age'    => 22,
        ]);

        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('doctor_id', $doctor->id)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $patient2->id)
            ->set('fee_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        $secondToken = PatientToken::where('patient_id', $patient2->id)->first();
        $this->assertNotNull($secondToken);
        $this->assertEquals(700.00, (float) $secondToken->consultation_fee);
        $this->assertEquals(700.00, (float) $secondToken->charged_amount);

        // Check that the first token is UNMODIFIED
        $firstToken->refresh();
        $this->assertEquals(500.00, (float) $firstToken->consultation_fee);
        $this->assertEquals(500.00, (float) $firstToken->charged_amount);
    }

    public function test_inactive_doctor_is_excluded_from_opd_dropdown_but_retains_history(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Fatima',
            'specialization'   => 'Dermatologist',
            'consultation_fee' => 1000.00,
            'status'           => 'active',
        ]);

        $patient = Patient::create([
            'name'   => 'Patient Fatima',
            'phone'  => '03001234567',
            'gender' => 'Female',
            'age'    => 35,
        ]);

        // Create historical OPD token
        $token = PatientToken::create([
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'doctor_id'        => $doctor->id,
            'patient_id'       => $patient->id,
            'consultation_fee' => 1000.00,
            'payment_type'     => 'paid',
            'charged_amount'   => 1000.00,
            'status'           => 'completed',
        ]);

        // TEST 5: Deactivate Doctor
        $this->actingAs($this->user)->patch("/doctors/{$doctor->id}/status");
        $doctor->refresh();
        $this->assertEquals('inactive', $doctor->status);
        $this->assertFalse((bool) $doctor->is_active);

        // Doctor must not appear in new OPD token dropdown
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->assertDontSeeHtml('<option value="' . $doctor->id . '">Dr. Fatima');

        // Historical token still correctly references doctor
        $token->refresh();
        $this->assertEquals($doctor->id, $token->doctor_id);
        $this->assertEquals('Dr. Fatima', $token->doctor->name);
    }

    public function test_doctor_details_page_shows_opd_summary_and_recent_visits(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Zainab',
            'specialization'   => 'Gynecologist',
            'qualification'    => 'MBBS, FCPS',
            'consultation_fee' => 800.00,
            'status'           => 'active',
        ]);

        $patient1 = Patient::create(['name' => 'Sara Bibi', 'phone' => '0300111', 'gender' => 'Female', 'age' => 28]);
        $patient2 = Patient::create(['name' => 'Ayesha Bibi', 'phone' => '0300222', 'gender' => 'Female', 'age' => 32]);

        // Paid token
        PatientToken::create([
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'doctor_id'        => $doctor->id,
            'patient_id'       => $patient1->id,
            'consultation_fee' => 800.00,
            'payment_type'     => 'paid',
            'charged_amount'   => 800.00,
            'status'           => 'completed',
        ]);

        // Free token
        PatientToken::create([
            'token_number'     => 2,
            'token_date'       => now()->toDateString(),
            'doctor_id'        => $doctor->id,
            'patient_id'       => $patient2->id,
            'consultation_fee' => 800.00,
            'payment_type'     => 'free',
            'free_reason'      => 'Poor Patient',
            'charged_amount'   => 0.00,
            'status'           => 'completed',
        ]);

        // TEST 6: Doctor Details
        $response = $this->actingAs($this->user)->get("/doctors/{$doctor->id}");
        $response->assertStatus(200);
        $response->assertSee('Dr. Zainab');
        $response->assertSee('Gynecologist');
        $response->assertSee('Total Patients');
        $response->assertSee('Paid Patients');
        $response->assertSee('Free Patients');
        $response->assertSee('Total Doctor Fees');
        $response->assertSee('Total Collection');
        $response->assertSee('Sara Bibi');
        $response->assertSee('Ayesha Bibi');
    }

    public function test_duplicate_doctor_prevention(): void
    {
        Doctor::create([
            'name'             => 'Dr. Bilal',
            'specialization'   => 'Cardiologist',
            'consultation_fee' => 1200.00,
            'status'           => 'active',
        ]);

        // Attempt to register duplicate with same name and specialization without confirmation
        $response = $this->actingAs($this->user)->from('/doctors/create')->post('/doctors', [
            'name'             => 'Dr. Bilal',
            'specialization'   => 'Cardiologist',
            'consultation_fee' => 1500.00,
            'status'           => 'active',
        ]);

        $response->assertRedirect('/doctors/create');
        $response->assertSessionHas('duplicate_warning');

        // Verify it was NOT inserted again
        $this->assertEquals(1, Doctor::where('name', 'Dr. Bilal')->count());

        // Now post with confirm_duplicate = 1
        $confirmResponse = $this->actingAs($this->user)->post('/doctors', [
            'name'              => 'Dr. Bilal',
            'specialization'    => 'Cardiologist',
            'consultation_fee'  => 1500.00,
            'status'            => 'active',
            'confirm_duplicate' => '1',
        ]);

        $confirmResponse->assertRedirect('/doctors');
        $this->assertEquals(2, Doctor::where('name', 'Dr. Bilal')->count());
    }

    public function test_doctor_with_historical_tokens_cannot_be_hard_deleted(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Kashif',
            'specialization'   => 'Pediatrician',
            'consultation_fee' => 600.00,
            'status'           => 'active',
        ]);

        $patient = Patient::create(['name' => 'Baby Omar', 'gender' => 'Male', 'age' => 4]);

        PatientToken::create([
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'doctor_id'        => $doctor->id,
            'patient_id'       => $patient->id,
            'consultation_fee' => 600.00,
            'payment_type'     => 'paid',
            'charged_amount'   => 600.00,
            'status'           => 'completed',
        ]);

        // Attempt delete
        $response = $this->actingAs($this->user)->delete("/doctors/{$doctor->id}");
        $response->assertRedirect('/doctors');
        $response->assertSessionHas('warning');

        // Verify doctor record still exists, but is deactivated
        $doctor->refresh();
        $this->assertNotNull($doctor);
        $this->assertEquals('inactive', $doctor->status);
    }

    public function test_doctor_without_tokens_can_be_deleted(): void
    {
        $doctor = Doctor::create([
            'name'             => 'Dr. Temp',
            'specialization'   => 'ENT Specialist',
            'consultation_fee' => 400.00,
            'status'           => 'active',
        ]);

        $response = $this->actingAs($this->user)->delete("/doctors/{$doctor->id}");
        $response->assertRedirect('/doctors');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('doctors', ['id' => $doctor->id]);
    }
}
