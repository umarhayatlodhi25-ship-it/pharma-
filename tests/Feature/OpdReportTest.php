<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpdReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Doctor $doctorA;
    protected Doctor $doctorB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->doctorA = Doctor::create([
            'name'             => 'Dr. Ahmed',
            'specialization'   => 'General Physician',
            'consultation_fee' => 500.00,
            'phone'            => '0300-1111222',
            'is_active'        => true,
        ]);

        $this->doctorB = Doctor::create([
            'name'             => 'Dr. Ali',
            'specialization'   => 'Pediatrician',
            'consultation_fee' => 1000.00,
            'phone'            => '0321-2222333',
            'is_active'        => true,
        ]);
    }

    public function test_opd_report_requires_authentication(): void
    {
        $response = $this->get('/reports/opd');
        $response->assertRedirect('/login');
    }

    public function test_report_hub_contains_opd_report_card(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports');
        $response->assertStatus(200);
        $response->assertSee('OPD Reports');
        $response->assertSee('/reports/opd');
        $response->assertSee('Patient visits, tokens, doctor fees and OPD collections');
    }

    public function test_opd_report_loads_successfully_for_authenticated_admin(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/opd');
        $response->assertStatus(200);
        $response->assertSee('OPD Reports');
        $response->assertSee('Doctor-wise Collection');
        $response->assertSee('OPD Visit Detail Table');
    }

    /**
     * TEST SECTION 19: Doctor A (5 paid, 2 free) & Doctor B (3 paid, 1 free)
     * Verify Doctor Fees, Collections, Free Amounts, and Grand Totals
     */
    public function test_doctor_collection_calculation_with_doctor_a_and_doctor_b(): void
    {
        $today = now()->toDateString();

        // Doctor A: 5 Paid Patients (Fee: 500 each)
        for ($i = 1; $i <= 5; $i++) {
            $patient = Patient::create(['name' => "DocA Paid Patient {$i}", 'age' => 30, 'gender' => 'Male']);
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctorA->id,
                'token_number'     => $i,
                'token_date'       => $today,
                'payment_type'     => 'paid',
                'consultation_fee' => 500.00,
                'charged_amount'   => 500.00,
                'status'           => 'completed',
            ]);
        }

        // Doctor A: 2 Free Patients (Fee: 500 each, charged: 0)
        for ($i = 6; $i <= 7; $i++) {
            $patient = Patient::create(['name' => "DocA Free Patient {$i}", 'age' => 25, 'gender' => 'Female']);
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctorA->id,
                'token_number'     => $i,
                'token_date'       => $today,
                'payment_type'     => 'free',
                'consultation_fee' => 500.00,
                'charged_amount'   => 0.00,
                'free_reason'      => 'Poor Patient',
                'status'           => 'completed',
            ]);
        }

        // Doctor B: 3 Paid Patients (Fee: 1000 each)
        for ($i = 8; $i <= 10; $i++) {
            $patient = Patient::create(['name' => "DocB Paid Patient {$i}", 'age' => 40, 'gender' => 'Male']);
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctorB->id,
                'token_number'     => $i,
                'token_date'       => $today,
                'payment_type'     => 'paid',
                'consultation_fee' => 1000.00,
                'charged_amount'   => 1000.00,
                'status'           => 'completed',
            ]);
        }

        // Doctor B: 1 Free Patient (Fee: 1000, charged: 0)
        $patient = Patient::create(['name' => "DocB Free Patient 11", 'age' => 35, 'gender' => 'Female']);
        PatientToken::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->doctorB->id,
            'token_number'     => 11,
            'token_date'       => $today,
            'payment_type'     => 'free',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 0.00,
            'free_reason'      => 'Emergency',
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get('/reports/opd?filter=today');
        $response->assertStatus(200);

        // Check Doctor A calculations:
        // Total Doctor Fees = 7 * 500 = 3500
        // Collected = 5 * 500 = 2500
        // Free Amount = 2 * 500 = 1000
        $docAReport = collect($response->viewData('doctorReports'))->firstWhere('doctor.id', $this->doctorA->id);
        $this->assertNotNull($docAReport);
        $this->assertEquals(7, $docAReport['total_patients']);
        $this->assertEquals(5, $docAReport['paid_patients']);
        $this->assertEquals(2, $docAReport['free_patients']);
        $this->assertEquals(3500.00, $docAReport['doctor_fees']);
        $this->assertEquals(2500.00, $docAReport['collected']);
        $this->assertEquals(1000.00, $docAReport['free_amount']);
        $this->assertEquals(71.4, $docAReport['collection_pct']);

        // Check Doctor B calculations:
        // Total Doctor Fees = 4 * 1000 = 4000
        // Collected = 3 * 1000 = 3000
        // Free Amount = 1 * 1000 = 1000
        $docBReport = collect($response->viewData('doctorReports'))->firstWhere('doctor.id', $this->doctorB->id);
        $this->assertNotNull($docBReport);
        $this->assertEquals(4, $docBReport['total_patients']);
        $this->assertEquals(3, $docBReport['paid_patients']);
        $this->assertEquals(1, $docBReport['free_patients']);
        $this->assertEquals(4000.00, $docBReport['doctor_fees']);
        $this->assertEquals(3000.00, $docBReport['collected']);
        $this->assertEquals(1000.00, $docBReport['free_amount']);
        $this->assertEquals(75.0, $docBReport['collection_pct']);

        // Check Grand Total:
        // Patients = 11, Paid = 8, Free = 3, Doctor Fees = 7500, Collected = 5500, Free Amount = 2000
        $grandTotal = $response->viewData('grandTotal');
        $this->assertEquals(11, $grandTotal['total_patients']);
        $this->assertEquals(8, $grandTotal['paid_patients']);
        $this->assertEquals(3, $grandTotal['free_patients']);
        $this->assertEquals(7500.00, $grandTotal['doctor_fees']);
        $this->assertEquals(5500.00, $grandTotal['collected']);
        $this->assertEquals(2000.00, $grandTotal['free_amount']);
        $this->assertEquals(73.3, $grandTotal['collection_pct']);

        // Check Summary Cards ViewData
        $this->assertEquals(11, $response->viewData('totalTokens'));
        $this->assertEquals(11, $response->viewData('totalPatients'));
        $this->assertEquals(8, $response->viewData('paidPatients'));
        $this->assertEquals(3, $response->viewData('freePatients'));
        $this->assertEquals(7500.00, $response->viewData('totalDoctorFees'));
        $this->assertEquals(5500.00, $response->viewData('totalCollection'));
        $this->assertEquals(2000.00, $response->viewData('totalFreeAmount'));
    }

    public function test_cancelled_tokens_are_excluded_from_collection_and_fees(): void
    {
        $today = now()->toDateString();
        $patient = Patient::create(['name' => 'Cancelled Patient', 'age' => 30, 'gender' => 'Male']);

        // Valid Token
        PatientToken::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        // Cancelled Token
        PatientToken::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 2,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'cancelled',
        ]);

        $response = $this->actingAs($this->admin)->get('/reports/opd?filter=today');
        $response->assertStatus(200);

        // Doctor Fees and Collected should only count the valid token (500), not the cancelled one
        $this->assertEquals(500.00, $response->viewData('totalDoctorFees'));
        $this->assertEquals(500.00, $response->viewData('totalCollection'));

        $grandTotal = $response->viewData('grandTotal');
        $this->assertEquals(500.00, $grandTotal['doctor_fees']);
        $this->assertEquals(500.00, $grandTotal['collected']);
    }

    public function test_filter_by_individual_doctor(): void
    {
        $today = now()->toDateString();
        $patient1 = Patient::create(['name' => 'Patient DocA', 'age' => 30, 'gender' => 'Male']);
        $patient2 = Patient::create(['name' => 'Patient DocB', 'age' => 30, 'gender' => 'Female']);

        PatientToken::create([
            'patient_id'       => $patient1->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        PatientToken::create([
            'patient_id'       => $patient2->id,
            'doctor_id'        => $this->doctorB->id,
            'token_number'     => 2,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 1000.00,
            'charged_amount'   => 1000.00,
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get("/reports/opd?filter=today&doctor_id={$this->doctorA->id}");
        $response->assertStatus(200);

        $tokens = $response->viewData('tokens');
        $this->assertCount(1, $tokens);
        $this->assertEquals($this->doctorA->id, $tokens->first()->doctor_id);

        $doctorReports = $response->viewData('doctorReports');
        $this->assertCount(1, $doctorReports);
        $this->assertEquals($this->doctorA->id, $doctorReports[0]['doctor']->id);
    }

    public function test_filter_by_fee_type_free(): void
    {
        $today = now()->toDateString();
        $patient1 = Patient::create(['name' => 'Paid Patient', 'age' => 30, 'gender' => 'Male']);
        $patient2 = Patient::create(['name' => 'Free Patient', 'age' => 30, 'gender' => 'Female']);

        PatientToken::create([
            'patient_id'       => $patient1->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        PatientToken::create([
            'patient_id'       => $patient2->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 2,
            'token_date'       => $today,
            'payment_type'     => 'free',
            'consultation_fee' => 500.00,
            'charged_amount'   => 0.00,
            'free_reason'      => 'Hospital Policy',
            'status'           => 'completed',
        ]);

        $response = $this->actingAs($this->admin)->get('/reports/opd?filter=today&fee_type=free');
        $response->assertStatus(200);

        $tokens = $response->viewData('tokens');
        $this->assertCount(1, $tokens);
        $this->assertEquals('free', $tokens->first()->payment_type);
        $this->assertEquals('Hospital Policy', $tokens->first()->free_reason);
        $response->assertSee('Hospital Policy');
        $response->assertSee('FREE');
    }

    public function test_custom_date_range_filter(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $today = now()->toDateString();

        $patient = Patient::create(['name' => 'Date Test Patient', 'age' => 30, 'gender' => 'Male']);

        // Yesterday's token
        PatientToken::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 1,
            'token_date'       => $yesterday,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        // Today's token
        PatientToken::create([
            'patient_id'       => $patient->id,
            'doctor_id'        => $this->doctorA->id,
            'token_number'     => 2,
            'token_date'       => $today,
            'payment_type'     => 'paid',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
            'status'           => 'completed',
        ]);

        // Query only yesterday
        $response = $this->actingAs($this->admin)->get("/reports/opd?filter=custom&start_date={$yesterday}&end_date={$yesterday}");
        $response->assertStatus(200);

        $tokens = $response->viewData('tokens');
        $this->assertCount(1, $tokens);
        $this->assertEquals($yesterday, $tokens->first()->token_date->toDateString());
    }
}
