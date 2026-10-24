<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoctorPortalSecurityAndWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $doctorUserA;
    protected User $doctorUserB;
    protected Doctor $doctorA;
    protected Doctor $doctorB;
    protected Patient $patientA;
    protected Patient $patientB;
    protected PatientToken $tokenA;
    protected PatientToken $tokenB;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin User
        $this->adminUser = User::factory()->create([
            'email' => 'admin@hospital.com',
            'role'  => 'admin',
        ]);

        // 2. Doctor A Profile & Login User
        $this->doctorUserA = User::factory()->create([
            'name'     => 'Dr. Alice Smith',
            'email'    => 'alice@hospital.com',
            'role'     => 'doctor',
            'password' => Hash::make('password123'),
        ]);

        $this->doctorA = Doctor::create([
            'user_id'                   => $this->doctorUserA->id,
            'name'                      => 'Alice Smith',
            'specialization'            => 'Cardiologist',
            'qualification'             => 'MBBS, FCPS',
            'room_number'               => 'Room 101',
            'consultation_fee'          => 1500.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'status'                    => 'active',
            'is_active'                 => true,
            'phone'                     => '03001111111',
            'email'                     => 'alice@hospital.com',
        ]);

        // 3. Doctor B Profile & Login User
        $this->doctorUserB = User::factory()->create([
            'name'     => 'Dr. Bob Jones',
            'email'    => 'bob@hospital.com',
            'role'     => 'doctor',
            'password' => Hash::make('password123'),
        ]);

        $this->doctorB = Doctor::create([
            'user_id'                   => $this->doctorUserB->id,
            'name'                      => 'Bob Jones',
            'specialization'            => 'Dermatologist',
            'qualification'             => 'MBBS, MD',
            'room_number'               => 'Room 202',
            'consultation_fee'          => 1000.00,
            'doctor_share_percentage'   => 60.00,
            'hospital_share_percentage' => 40.00,
            'status'                    => 'active',
            'is_active'                 => true,
            'phone'                     => '03002222222',
            'email'                     => 'bob@hospital.com',
        ]);

        // 4. Patients
        $this->patientA = Patient::create([
            'patient_number' => 'PT-00101',
            'name'           => 'John Doe',
            'gender'         => 'Male',
            'age'            => 45,
            'phone'          => '03111111111',
        ]);

        $this->patientB = Patient::create([
            'patient_number' => 'PT-00202',
            'name'           => 'Jane Roe',
            'gender'         => 'Female',
            'age'            => 30,
            'phone'          => '03222222222',
        ]);

        // 5. Tokens: Token A assigned to Doctor A; Token B assigned to Doctor B
        $this->tokenA = PatientToken::create([
            'patient_id'                => $this->patientA->id,
            'doctor_id'                 => $this->doctorA->id,
            'token_number'              => 1,
            'token_date'                => now()->toDateString(),
            'payment_type'              => 'paid',
            'consultation_fee'          => 1500.00,
            'charged_amount'            => 1500.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'doctor_share_amount'       => 1050.00,
            'hospital_share_amount'     => 450.00,
            'status'                    => 'waiting',
        ]);

        $this->tokenB = PatientToken::create([
            'patient_id'                => $this->patientB->id,
            'doctor_id'                 => $this->doctorB->id,
            'token_number'              => 2,
            'token_date'                => now()->toDateString(),
            'payment_type'              => 'paid',
            'consultation_fee'          => 1000.00,
            'charged_amount'            => 1000.00,
            'doctor_share_percentage'   => 60.00,
            'hospital_share_percentage' => 40.00,
            'doctor_share_amount'       => 600.00,
            'hospital_share_amount'     => 400.00,
            'status'                    => 'waiting',
        ]);
    }

    /**
     * TEST 1: Admin can create and link a login account to an unlinked doctor profile.
     */
    public function test_admin_can_create_and_link_doctor_account(): void
    {
        $unlinkedDoctor = Doctor::create([
            'name'                      => 'Dr. Charlie Day',
            'specialization'            => 'Neurologist',
            'consultation_fee'          => 2000.00,
            'doctor_share_percentage'   => 75.00,
            'hospital_share_percentage' => 25.00,
            'status'                    => 'active',
            'is_active'                 => true,
        ]);

        $this->assertNull($unlinkedDoctor->user_id);

        $response = $this->actingAs($this->adminUser)->post("/doctors/{$unlinkedDoctor->id}/account", [
            'action_type'           => 'create',
            'name'                  => 'Dr. Charlie Day',
            'email'                 => 'charlie@hospital.com',
            'password'              => 'securepass123',
            'password_confirmation' => 'securepass123',
        ]);

        $response->assertRedirect();
        $unlinkedDoctor->refresh();

        $this->assertNotNull($unlinkedDoctor->user_id);
        $this->assertEquals('charlie@hospital.com', $unlinkedDoctor->user->email);
        $this->assertEquals('doctor', $unlinkedDoctor->user->role);
        $this->assertTrue(Hash::check('securepass123', $unlinkedDoctor->user->password));
    }

    /**
     * TEST 2: Unauthenticated and non-doctor users are forbidden from accessing the Doctor Portal.
     */
    public function test_non_doctor_and_unauthenticated_cannot_access_doctor_portal(): void
    {
        // Unauthenticated
        $this->get('/doctor-portal/dashboard')->assertRedirect('/login');

        // Cashier user
        $cashier = User::factory()->create(['role' => 'cashier']);
        $this->actingAs($cashier)->get('/doctor-portal/dashboard')->assertStatus(403);

        // Pharmacist user
        $pharmacist = User::factory()->create(['role' => 'pharmacist']);
        $this->actingAs($pharmacist)->get('/doctor-portal/dashboard')->assertStatus(403);
    }

    /**
     * TEST 3: Deactivated or unlinked doctor accounts are denied portal access.
     */
    public function test_inactive_doctor_cannot_access_portal(): void
    {
        // Deactivate Doctor A
        $this->doctorA->update(['status' => 'inactive', 'is_active' => false]);

        $response = $this->actingAs($this->doctorUserA)->get('/doctor-portal/dashboard');
        $response->assertStatus(403);
    }

    /**
     * TEST 4: IDOR Prevention - Doctor A cannot view or manipulate Doctor B's consultation token.
     */
    public function test_doctor_a_cannot_access_doctor_b_consultation_token_idor(): void
    {
        // Doctor A tries to view Doctor B's consultation workspace -> 403 Forbidden
        $response = $this->actingAs($this->doctorUserA)->get("/doctor-portal/consultations/{$this->tokenB->id}");
        $response->assertStatus(403);

        // Doctor A tries to call Doctor B's token -> 403 Forbidden
        $callResponse = $this->actingAs($this->doctorUserA)->post("/doctor-portal/opd-queue/{$this->tokenB->id}/call");
        $callResponse->assertStatus(403);

        // Token B remains untouched
        $this->tokenB->refresh();
        $this->assertEquals('waiting', $this->tokenB->status);
    }

    /**
     * TEST 5: IDOR Prevention - Doctor A cannot access Doctor B's patient records.
     */
    public function test_doctor_a_cannot_access_doctor_b_patient_records_idor(): void
    {
        // Patient B only has visits with Doctor B
        $response = $this->actingAs($this->doctorUserA)->get("/doctor-portal/patients/{$this->patientB->id}");
        $response->assertStatus(403);

        // Doctor A can access Patient A (authorized)
        $allowedResponse = $this->actingAs($this->doctorUserA)->get("/doctor-portal/patients/{$this->patientA->id}");
        $allowedResponse->assertStatus(200);
        $allowedResponse->assertSee('John Doe');
    }

    /**
     * TEST 6: Doctor can access own dashboard and OPD queue with correct real-time data.
     */
    public function test_doctor_can_view_own_dashboard_and_queue(): void
    {
        $dashResponse = $this->actingAs($this->doctorUserA)->get('/doctor-portal/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Dr. Alice Smith');
        $dashResponse->assertSee('John Doe');

        $queueResponse = $this->actingAs($this->doctorUserA)->get('/doctor-portal/opd-queue');
        $queueResponse->assertStatus(200);
        $queueResponse->assertSee('#1');
        $queueResponse->assertSee('John Doe');
        $queueResponse->assertDontSee('Jane Roe'); // Doctor B's patient should not appear in Doctor A's queue
    }

    /**
     * TEST 7: Doctor can update consultation status and save clinical examination details.
     */
    public function test_doctor_can_update_queue_status_and_clinical_notes(): void
    {
        // 1. Call token into consultation
        $this->actingAs($this->doctorUserA)->post("/doctor-portal/opd-queue/{$this->tokenA->id}/call")
            ->assertRedirect();

        $this->tokenA->refresh();
        $this->assertEquals('in_consultation', $this->tokenA->status);

        // 2. Save clinical examination
        $updateResponse = $this->actingAs($this->doctorUserA)->put("/doctor-portal/consultations/{$this->tokenA->id}", [
            'symptoms'               => 'Mild chest discomfort and palpitations',
            'diagnosis'              => 'Sinus Tachycardia',
            'clinical_notes'         => 'ECG shows sinus rhythm with rate 105. Advised hydration and lifestyle moderation.',
            'blood_pressure'         => '130/85',
            'pulse_rate'             => '105',
            'temperature'            => '98.4',
            'weight'                 => '74',
            'follow_up_date'         => now()->addDays(7)->toDateString(),
            'follow_up_instructions' => 'Follow up in 1 week with 24hr Holter report',
            'action'                 => 'save_and_complete',
        ]);

        $updateResponse->assertRedirect('/doctor-portal/opd-queue');

        $this->tokenA->refresh();
        $this->assertEquals('completed', $this->tokenA->status);
        $this->assertEquals('Sinus Tachycardia', $this->tokenA->diagnosis);
        $this->assertEquals('130/85', $this->tokenA->blood_pressure);
        $this->assertNotNull($this->tokenA->completed_at);
    }

    /**
     * TEST 8: Doctor can create a prescription with medicines and view/print it.
     */
    public function test_doctor_can_create_prescription(): void
    {
        $category = \App\Models\Category::create(['name' => 'General', 'slug' => 'general']);

        $med = Medicine::create([
            'category_id'     => $category->id,
            'name'            => 'Panadol Extra',
            'strength'        => '500mg',
            'dosage_form'     => 'Tablet',
            'unit_price'      => 10.00,
            'purchase_price'  => 8.00,
            'status'          => 'active',
        ]);

        $response = $this->actingAs($this->doctorUserA)->post('/doctor-portal/prescriptions', [
            'patient_id'             => $this->patientA->id,
            'patient_token_id'       => $this->tokenA->id,
            'diagnosis'              => 'Tension Headache',
            'clinical_notes'         => 'Rest in dark room, avoid screen time',
            'follow_up_date'         => now()->addDays(3)->toDateString(),
            'follow_up_instructions' => 'Revisit if pain persists',
            'items'                  => [
                [
                    'medicine_id'   => $med->id,
                    'medicine_name' => 'Panadol Extra 500mg (Tablet)',
                    'dosage'        => '500mg',
                    'frequency'     => '1-0-1',
                    'duration'      => '3 Days',
                    'instructions'  => 'After meal',
                    'quantity'      => 6,
                ],
            ],
        ]);

        $response->assertRedirect();

        $prescription = Prescription::where('patient_token_id', $this->tokenA->id)->first();
        $this->assertNotNull($prescription);
        $this->assertEquals('Tension Headache', $prescription->diagnosis);
        $this->assertEquals($this->doctorA->id, $prescription->doctor_id);
        $this->assertCount(1, $prescription->items);

        // View Prescription
        $viewResponse = $this->actingAs($this->doctorUserA)->get("/doctor-portal/prescriptions/{$prescription->id}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Panadol Extra');

        // Print View
        $printResponse = $this->actingAs($this->doctorUserA)->get("/doctor-portal/prescriptions/{$prescription->id}/print");
        $printResponse->assertStatus(200);
        $printResponse->assertSee($prescription->prescription_number);
    }

    /**
     * TEST 9: IDOR Prevention - Doctor A cannot view or print Doctor B's prescription.
     */
    public function test_doctor_a_cannot_view_or_print_doctor_b_prescription(): void
    {
        $rxB = Prescription::create([
            'patient_id'       => $this->patientB->id,
            'doctor_id'        => $this->doctorB->id,
            'patient_token_id' => $this->tokenB->id,
            'diagnosis'        => 'Eczema Flareup',
            'status'           => 'issued',
            'is_finalized'     => true,
            'created_by'       => $this->doctorUserB->id,
        ]);

        // Doctor A tries to view Rx B -> 403 Forbidden
        $this->actingAs($this->doctorUserA)->get("/doctor-portal/prescriptions/{$rxB->id}")->assertStatus(403);

        // Doctor A tries to print Rx B -> 403 Forbidden
        $this->actingAs($this->doctorUserA)->get("/doctor-portal/prescriptions/{$rxB->id}/print")->assertStatus(403);
    }

    /**
     * TEST 10: Doctor ledger is strictly isolated and read-only.
     */
    public function test_doctor_earnings_and_ledger_are_strictly_isolated(): void
    {
        // Credit entry for Doctor A
        DoctorLedger::create([
            'doctor_id'        => $this->doctorA->id,
            'entry_date'       => now()->toDateString(),
            'transaction_type' => 'credit',
            'amount'           => 1050.00,
            'balance_after'    => 1050.00,
            'description'      => 'Consultation share for Token #1',
            'created_by'       => $this->adminUser->id,
        ]);

        // Credit entry for Doctor B
        DoctorLedger::create([
            'doctor_id'        => $this->doctorB->id,
            'entry_date'       => now()->toDateString(),
            'transaction_type' => 'credit',
            'amount'           => 600.00,
            'balance_after'    => 600.00,
            'description'      => 'Consultation share for Token #1',
            'created_by'       => $this->adminUser->id,
        ]);

        // Doctor A checks ledger
        $responseA = $this->actingAs($this->doctorUserA)->get('/doctor-portal/ledger');
        $responseA->assertStatus(200);
        $responseA->assertSee('1,050.00');
        $responseA->assertDontSee('600.00'); // Doctor B's financial entries must not be visible

        // Doctor A checks earnings overview
        $earningsA = $this->actingAs($this->doctorUserA)->get('/doctor-portal/earnings');
        $earningsA->assertStatus(200);
        $earningsA->assertSee('1,050.00');
        $earningsA->assertDontSee('600.00');
    }

    /**
     * TEST 11: Doctor login redirects straight to Doctor Portal dashboard.
     */
    public function test_doctor_login_redirects_to_doctor_portal_dashboard(): void
    {
        $response = $this->post('/login', [
            'email'    => 'alice@hospital.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/doctor-portal/dashboard');
    }

    /**
     * TEST 12: Doctor cannot access admin settings, inventory, or billing modules.
     */
    public function test_doctor_cannot_access_admin_modules(): void
    {
        // Settings -> redirected to fallback /doctor-portal/dashboard with restricted error
        $settingsResponse = $this->actingAs($this->doctorUserA)->get('/settings');
        $settingsResponse->assertRedirect('/doctor-portal/dashboard');

        // Medicines -> redirected to fallback /doctor-portal/dashboard
        $medResponse = $this->actingAs($this->doctorUserA)->get('/medicines');
        $medResponse->assertRedirect('/doctor-portal/dashboard');
    }

    /**
     * TEST 13: Admin cannot access Doctor Portal without a linked doctor profile.
     */
    public function test_admin_cannot_access_doctor_portal(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/doctor-portal/dashboard');
        $response->assertStatus(403);
    }

    /**
     * TEST 14: Consultations index route redirects to OPD queue.
     */
    public function test_consultations_index_redirects_to_opd_queue(): void
    {
        $response = $this->actingAs($this->doctorUserA)->get('/doctor-portal/consultations');
        $response->assertRedirect(route('doctor.queue.index'));
    }
}
