<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\DoctorSettlement;
use App\Models\HospitalBill;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoctorPortalDataSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $doctorUser;
    protected Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name'  => 'Admin User',
            'email' => 'admin@hospital.com',
            'role'  => 'admin',
        ]);

        $this->doctorUser = User::factory()->create([
            'name'     => 'Dr. Akram Lodhi',
            'email'    => 'akram@hospital.com',
            'role'     => 'doctor',
            'password' => Hash::make('password123'),
        ]);

        $this->doctor = Doctor::create([
            'user_id'                   => $this->doctorUser->id,
            'name'                      => 'Dr Akram lodhi',
            'email'                     => 'akram@hospital.com',
            'specialization'            => 'General Physician',
            'consultation_fee'          => 250.00,
            'doctor_share_percentage'   => 80.00,
            'hospital_share_percentage' => 20.00,
            'status'                    => 'active',
            'is_active'                 => true,
            'room_number'               => 'Room 10',
        ]);
    }

    /**
     * TEST 1: Reconcile financial figures between Admin Doctor Ledger and Doctor Portal.
     * Verifies:
     * - Gross Collection: PKR 16,750
     * - Doctor Share Earned: PKR 13,400
     * - Settled Payouts: PKR 130
     * - Remaining Payable: PKR 13,270
     */
    public function test_admin_and_doctor_portal_financial_totals_reconcile_exactly(): void
    {
        $tz = config('app.display_timezone', 'Asia/Karachi');
        $today = now($tz)->toDateString();
        $pastDate = now($tz)->subDays(3)->toDateString();

        $service = HospitalService::create([
            'name'          => 'General Consultation',
            'service_type'  => 'opd',
            'default_price' => 250.00,
            'is_active'     => true,
        ]);

        // Generate 67 consultations: 27 today, 40 on past date
        // 67 * 250 = PKR 16,750 Gross Collection
        // 67 * 200 = PKR 13,400 Doctor Share (80%)
        $patients = [];
        for ($i = 1; $i <= 65; $i++) {
            $patients[] = Patient::create([
                'patient_number' => 'PT-202610-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'name'           => "Patient {$i}",
                'phone'          => "030000000{$i}",
                'age'            => 30,
                'gender'         => 'Male',
            ]);
        }

        $runningPayable = 0.00;
        for ($i = 1; $i <= 67; $i++) {
            $patient = $patients[($i - 1) % 65];
            $date = ($i <= 27) ? $today : $pastDate;

            $token = PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctor->id,
                'token_number'     => $i,
                'token_date'       => $date,
                'status'           => 'completed',
                'payment_type'     => 'paid',
                'consultation_fee' => 250.00,
                'charged_amount'   => 250.00,
                'doctor_share_percentage' => 80.00,
                'hospital_share_percentage' => 20.00,
                'doctor_share_amount' => 200.00,
                'hospital_share_amount' => 50.00,
            ]);

            $bill = HospitalBill::create([
                'bill_number'             => 'HB-202610-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'patient_id'              => $patient->id,
                'doctor_id'               => $this->doctor->id,
                'patient_token_id'        => $token->id,
                'hospital_service_id'     => $service->id,
                'bill_date'               => $date,
                'total_amount'            => 250.00,
                'paid_amount'             => 250.00,
                'doctor_share_percentage' => 80.00,
                'hospital_share_percentage' => 20.00,
                'doctor_share'            => 200.00,
                'hospital_share'          => 50.00,
                'payment_status'          => 'paid',
                'billing_type'            => 'opd',
            ]);

            $token->update(['hospital_bill_id' => $bill->id]);

            $runningPayable += 200.00;
            DoctorLedger::create([
                'doctor_id'        => $this->doctor->id,
                'hospital_bill_id' => $bill->id,
                'entry_date'       => $date,
                'transaction_type' => 'credit',
                'amount'           => 200.00,
                'balance_after'    => $runningPayable,
                'description'      => "OPD Consultation Share - Token #{$i}",
                'created_by'       => $this->admin->id,
            ]);
        }

        // Payout settlement: PKR 130
        $runningPayable -= 130.00;
        $settlement = DoctorSettlement::create([
            'settlement_number' => 'SET-20261019-0002',
            'doctor_id'         => $this->doctor->id,
            'settlement_date'   => $pastDate,
            'previous_payable'  => 13400.00,
            'paid_amount'       => 130.00,
            'remaining_payable' => $runningPayable,
            'payment_method'    => 'cash',
            'reference_note'    => 'Doctor share settlement',
            'created_by'        => $this->admin->id,
        ]);

        DoctorLedger::create([
            'doctor_id'            => $this->doctor->id,
            'doctor_settlement_id' => $settlement->id,
            'entry_date'           => $pastDate,
            'transaction_type'     => 'debit',
            'amount'               => 130.00,
            'balance_after'        => $runningPayable,
            'description'          => 'Settlement Payout SET-20261019-0002',
            'created_by'           => $this->admin->id,
        ]);

        // 1. Check Admin Doctor Ledger calculation
        $adminResponse = $this->actingAs($this->admin)->get(route('doctor-ledgers.index'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('16,750');
        $adminResponse->assertSee('13,400');
        $adminResponse->assertSee('13,270');
        $adminResponse->assertSee('67'); // 67 total visits

        // 2. Check Doctor Portal Dashboard calculation
        $portalResponse = $this->actingAs($this->doctorUser)->get('/doctor-portal/dashboard');
        $portalResponse->assertStatus(200);
        $portalResponse->assertSee('16,750.00'); // Gross Practice Collection
        $portalResponse->assertSee('13,400.00'); // Doctor Share Earned
        $portalResponse->assertSee('130.00');    // Settled Payouts
        $portalResponse->assertSee('13,270.00'); // Current Outstanding Payable
        $portalResponse->assertSee('67');        // Lifetime visits
        $portalResponse->assertSee('27');        // Today's assigned tokens

        // 3. Check Doctor Portal Earnings page calculation
        $earningsResponse = $this->actingAs($this->doctorUser)->get('/doctor-portal/earnings');
        $earningsResponse->assertStatus(200);
        $earningsResponse->assertSee('16,750.00');
        $earningsResponse->assertSee('13,400.00');
        $earningsResponse->assertSee('130.00');
        $earningsResponse->assertSee('13,270.00');
        $earningsResponse->assertSee('67');
        $earningsResponse->assertSee('Completed'); // Status badge for settlement
    }

    /**
     * TEST 2: Clarify 67 lifetime visits vs 27 today's tokens.
     * Verifies that Today's Queue and Lifetime figures use strictly distinct scopes.
     */
    public function test_today_queue_and_lifetime_totals_use_distinct_scopes(): void
    {
        $tz = config('app.display_timezone', 'Asia/Karachi');
        $today = now($tz)->toDateString();
        $pastDate = now($tz)->subDays(2)->toDateString();

        $patient = Patient::create([
            'patient_number' => 'PT-1001',
            'name'           => 'John Doe',
            'age'            => 30,
            'gender'         => 'Male',
        ]);

        // 27 tokens for today
        for ($i = 1; $i <= 27; $i++) {
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctor->id,
                'token_number'     => $i,
                'token_date'       => $today,
                'status'           => 'completed',
                'payment_type'     => 'paid',
                'consultation_fee' => 250.00,
                'charged_amount'   => 250.00,
            ]);
        }

        // 40 tokens on past date
        for ($i = 28; $i <= 67; $i++) {
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctor->id,
                'token_number'     => $i,
                'token_date'       => $pastDate,
                'status'           => 'completed',
                'payment_type'     => 'paid',
                'consultation_fee' => 250.00,
                'charged_amount'   => 250.00,
            ]);
        }

        // Dashboard assertions
        $response = $this->actingAs($this->doctorUser)->get('/doctor-portal/dashboard');
        $response->assertStatus(200);
        $response->assertViewHas('todayTokensCount', 27);
        $response->assertViewHas('totalLifetimeTokens', 67);
        $response->assertViewHas('totalLifetimeVisits', 67);

        // Queue view defaults to today
        $queueToday = $this->actingAs($this->doctorUser)->get('/doctor-portal/opd-queue');
        $queueToday->assertStatus(200);
        $queueToday->assertViewHas('todayTokensCount', 27);
        $queueToday->assertViewHas('totalLifetimeTokens', 67);
        $this->assertCount(20, $queueToday->viewData('tokens')); // first page of 27 tokens for today

        // Queue view with 'all' returns all 67 lifetime tokens
        $queueAll = $this->actingAs($this->doctorUser)->get('/doctor-portal/opd-queue?date=all');
        $queueAll->assertStatus(200);
        $this->assertEquals(67, $queueAll->viewData('counts')['total']);
    }

    /**
     * TEST 3: Visit counts vs token counts distinguish cancelled tokens.
     */
    public function test_visit_counts_distinguish_completed_and_cancelled_tokens(): void
    {
        $patient = Patient::create([
            'patient_number' => 'PT-2001',
            'name'           => 'Jane Smith',
            'age'            => 28,
            'gender'         => 'Female',
        ]);

        // 5 completed visits
        for ($i = 1; $i <= 5; $i++) {
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctor->id,
                'token_number'     => $i,
                'token_date'       => now()->toDateString(),
                'status'           => 'completed',
                'payment_type'     => 'paid',
                'consultation_fee' => 250.00,
                'charged_amount'   => 250.00,
            ]);
        }

        // 2 cancelled tokens
        for ($i = 6; $i <= 7; $i++) {
            PatientToken::create([
                'patient_id'       => $patient->id,
                'doctor_id'        => $this->doctor->id,
                'token_number'     => $i,
                'token_date'       => now()->toDateString(),
                'status'           => 'cancelled',
                'payment_type'     => 'paid',
                'consultation_fee' => 250.00,
                'charged_amount'   => 0.00,
            ]);
        }

        $response = $this->actingAs($this->doctorUser)->get('/doctor-portal/dashboard');
        $response->assertStatus(200);
        $response->assertViewHas('totalLifetimeTokens', 7);  // 7 total tokens
        $response->assertViewHas('totalLifetimeVisits', 5);  // 5 valid visits (excluding cancelled)
        $response->assertViewHas('totalCompletedVisits', 5); // 5 completed
    }

    /**
     * TEST 4: Multiple doctors remain strictly isolated from one another.
     */
    public function test_multiple_doctors_remain_strictly_isolated(): void
    {
        $doctorUserB = User::factory()->create([
            'name'  => 'Dr. Bob Smith',
            'email' => 'bob@hospital.com',
            'role'  => 'doctor',
        ]);

        $doctorB = Doctor::create([
            'user_id'                   => $doctorUserB->id,
            'name'                      => 'Dr Bob Smith',
            'consultation_fee'          => 500.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'status'                    => 'active',
            'is_active'                 => true,
        ]);

        $patientA = Patient::create(['patient_number' => 'PT-A', 'name' => 'Patient A', 'age' => 30, 'gender' => 'Male']);
        $patientB = Patient::create(['patient_number' => 'PT-B', 'name' => 'Patient B', 'age' => 45, 'gender' => 'Female']);

        // Doctor A Token
        PatientToken::create([
            'patient_id'       => $patientA->id,
            'doctor_id'        => $this->doctor->id,
            'token_number'     => 1,
            'token_date'       => now()->toDateString(),
            'status'           => 'completed',
            'consultation_fee' => 250.00,
            'charged_amount'   => 250.00,
        ]);

        // Doctor B Token
        PatientToken::create([
            'patient_id'       => $patientB->id,
            'doctor_id'        => $doctorB->id,
            'token_number'     => 2,
            'token_date'       => now()->toDateString(),
            'status'           => 'completed',
            'consultation_fee' => 500.00,
            'charged_amount'   => 500.00,
        ]);

        // Doctor A visits portal
        $resA = $this->actingAs($this->doctorUser)->get('/doctor-portal/dashboard');
        $resA->assertViewHas('totalLifetimeTokens', 1);
        $resA->assertViewHas('authorizedPatientsCount', 1);

        // Doctor B visits portal
        $resB = $this->actingAs($doctorUserB)->get('/doctor-portal/dashboard');
        $resB->assertViewHas('totalLifetimeTokens', 1);
        $resB->assertViewHas('authorizedPatientsCount', 1);

        // Doctor A cannot access Doctor B's patient
        $unauthPatient = $this->actingAs($this->doctorUser)->get("/doctor-portal/patients/{$patientB->id}");
        $unauthPatient->assertStatus(403);
    }
}
