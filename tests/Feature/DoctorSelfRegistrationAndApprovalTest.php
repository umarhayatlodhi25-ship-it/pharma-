<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorRegistrationRequest;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DoctorSelfRegistrationAndApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
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
            'name'   => 'Hospital Administrator',
            'email'  => 'admin@hospital.com',
            'role'   => 'admin',
            'status' => 'active',
        ]);

        // 2. Existing Doctor A Profile (Not yet linked to any login account)
        $this->doctorA = Doctor::create([
            'user_id'                   => null,
            'name'                      => 'Dr. Sarah Khan',
            'specialization'            => 'Cardiologist',
            'qualification'             => 'MBBS, FCPS',
            'room_number'               => 'Room 101',
            'consultation_fee'          => 1500.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'status'                    => 'active',
            'is_active'                 => true,
            'phone'                     => '03001234567',
            'email'                     => 'sarah.khan@hospital.com',
        ]);

        // 3. Existing Doctor B Profile (Already active and linked to Doctor User B)
        $doctorUserB = User::factory()->create([
            'name'     => 'Dr. Tariq Mahmood',
            'email'    => 'tariq@hospital.com',
            'role'     => 'doctor',
            'status'   => 'active',
            'password' => Hash::make('Secret123!'),
        ]);

        $this->doctorB = Doctor::create([
            'user_id'                   => $doctorUserB->id,
            'name'                      => 'Dr. Tariq Mahmood',
            'specialization'            => 'Neurologist',
            'qualification'             => 'MBBS, MD',
            'room_number'               => 'Room 102',
            'consultation_fee'          => 2000.00,
            'doctor_share_percentage'   => 75.00,
            'hospital_share_percentage' => 25.00,
            'status'                    => 'active',
            'is_active'                 => true,
            'phone'                     => '03009876543',
            'email'                     => 'tariq@hospital.com',
        ]);

        // 4. Patients
        $this->patientA = Patient::create([
            'patient_number' => 'PT-1001',
            'name'           => 'Ahmed Ali',
            'gender'         => 'Male',
            'age'            => 42,
            'phone'          => '03211111111',
        ]);

        $this->patientB = Patient::create([
            'patient_number' => 'PT-1002',
            'name'           => 'Fatima Noor',
            'gender'         => 'Female',
            'age'            => 29,
            'phone'          => '03222222222',
        ]);

        // 5. Patient Tokens
        $this->tokenA = PatientToken::create([
            'token_number'    => 1,
            'token_date'      => now()->toDateString(),
            'patient_id'      => $this->patientA->id,
            'doctor_id'       => $this->doctorA->id,
            'fee'             => 1500.00,
            'payment_status'  => 'paid',
            'status'          => 'waiting',
        ]);

        $this->tokenB = PatientToken::create([
            'token_number'    => 2,
            'token_date'      => now()->toDateString(),
            'patient_id'      => $this->patientB->id,
            'doctor_id'       => $this->doctorB->id,
            'fee'             => 2000.00,
            'payment_status'  => 'paid',
            'status'          => 'waiting',
        ]);
    }

    /**
     * TEST 1: An existing doctor can submit a registration request.
     */
    public function test_existing_doctor_can_submit_registration_request(): void
    {
        $response = $this->post('/doctor-portal/register', [
            'doctor_identifier'     => $this->doctorA->id,
            'name'                  => 'Dr. Sarah Khan',
            'email'                 => 'sarah.khan@hospital.com',
            'phone'                 => '03001234567',
            'license_number'        => 'PMDC-12345-P',
            'password'              => 'DoctorSecret123!',
            'password_confirmation' => 'DoctorSecret123!',
            'terms'                 => '1',
        ]);

        $response->assertRedirect(route('doctor.pending-approval'));

        // Verify that registration request was created with pending status
        $regRequest = DoctorRegistrationRequest::where('doctor_id', $this->doctorA->id)->first();
        $this->assertNotNull($regRequest);
        $this->assertEquals('pending', $regRequest->status);
        $this->assertTrue($regRequest->is_verified);
        $this->assertEquals('sarah.khan@hospital.com', $regRequest->email);

        // Verify that user was created in pending_approval state
        $user = User::where('email', 'sarah.khan@hospital.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('pending_approval', $user->status);
        $this->assertEquals('doctor', $user->role);

        // Verify doctor profile user_id is NOT yet linked (approval required)
        $this->assertNull($this->doctorA->fresh()->user_id);
    }

    /**
     * TEST 2: A nonexistent doctor cannot create a doctor profile through this workflow.
     */
    public function test_nonexistent_doctor_cannot_create_doctor_profile_through_this_workflow(): void
    {
        $initialDoctorCount = Doctor::count();

        $response = $this->post('/doctor-portal/register', [
            'doctor_identifier'     => '999999',
            'name'                  => 'Nonexistent Doctor',
            'email'                 => 'stranger@nowhere.com',
            'phone'                 => '03990000000',
            'license_number'        => 'FAKE-999',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => '1',
        ]);

        // Returns safe generic response
        $response->assertRedirect(route('doctor.pending-approval'));

        // Assert NO new doctor was created
        $this->assertEquals($initialDoctorCount, Doctor::count());
        $this->assertNull(Doctor::where('email', 'stranger@nowhere.com')->first());
    }

    /**
     * TEST 3: Name-only matching cannot claim another doctor's identity.
     */
    public function test_name_only_matching_cannot_claim_another_doctor_identity(): void
    {
        // An attacker tries to claim Dr. Sarah Khan by name, but with attacker's own contact
        $response = $this->post('/doctor-portal/register', [
            'doctor_identifier'     => 'UNKNOWN-CODE',
            'name'                  => 'Dr. Sarah Khan', // Matching name
            'email'                 => 'attacker@evil.com', // Mismatched email
            'phone'                 => '03449999999', // Mismatched phone
            'license_number'        => 'UNVERIFIED-123',
            'password'              => 'AttackerPass123!',
            'password_confirmation' => 'AttackerPass123!',
            'terms'                 => '1',
        ]);

        $response->assertRedirect(route('doctor.pending-approval'));

        // Dr. Sarah Khan must NOT be claimed or linked to attacker
        $this->assertNull($this->doctorA->fresh()->user_id);
        $this->assertFalse(
            DoctorRegistrationRequest::where('doctor_id', $this->doctorA->id)
                ->where('email', 'attacker@evil.com')
                ->exists()
        );
    }

    /**
     * TEST 4: Duplicate requests and duplicate doctor-account links are prevented.
     */
    public function test_duplicate_requests_and_duplicate_doctor_account_links_are_prevented(): void
    {
        // 1. Submit first legitimate request
        $this->post('/doctor-portal/register', [
            'doctor_identifier'     => $this->doctorA->id,
            'name'                  => 'Dr. Sarah Khan',
            'email'                 => 'sarah.khan@hospital.com',
            'phone'                 => '03001234567',
            'license_number'        => 'PMDC-12345-P',
            'password'              => 'DoctorSecret123!',
            'password_confirmation' => 'DoctorSecret123!',
            'terms'                 => '1',
        ]);

        $firstRequestCount = DoctorRegistrationRequest::count();

        // 2. Submit second duplicate request for the same doctor
        $dupResponse = $this->post('/doctor-portal/register', [
            'doctor_identifier'     => $this->doctorA->id,
            'name'                  => 'Dr. Sarah Khan',
            'email'                 => 'sarah.khan@hospital.com',
            'phone'                 => '03001234567',
            'license_number'        => 'PMDC-12345-P',
            'password'              => 'DifferentSecret123!',
            'password_confirmation' => 'DifferentSecret123!',
            'terms'                 => '1',
        ]);

        $dupResponse->assertRedirect(route('doctor.pending-approval'));
        // Assert no duplicate request record was created
        $this->assertEquals($firstRequestCount, DoctorRegistrationRequest::count());

        // 3. Attempting to register for Doctor B who is ALREADY linked to an account
        $alreadyLinkedResponse = $this->post('/doctor-portal/register', [
            'doctor_identifier'     => $this->doctorB->id,
            'name'                  => 'Dr. Tariq Mahmood',
            'email'                 => 'another.tariq@hospital.com',
            'phone'                 => '03009876543',
            'license_number'        => 'PMDC-99999-P',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'terms'                 => '1',
        ]);

        $alreadyLinkedResponse->assertRedirect(route('doctor.pending-approval'));
        // Original link to doctorUserB must remain undisturbed
        $this->assertNotEquals(null, $this->doctorB->fresh()->user_id);
    }

    /**
     * TEST 5: Pending accounts cannot access protected routes.
     */
    public function test_pending_accounts_cannot_access_protected_routes(): void
    {
        $pendingUser = User::factory()->create([
            'name'     => 'Dr. Pending',
            'email'    => 'pending@hospital.com',
            'role'     => 'doctor',
            'status'   => 'pending_approval',
            'password' => Hash::make('password123'),
        ]);

        // Direct GET to protected doctor dashboard while acting as pending user
        $response = $this->actingAs($pendingUser)->get('/doctor-portal/dashboard');
        $response->assertRedirect(route('doctor.pending-approval'));

        // Reset auth session before testing login endpoint
        auth()->logout();

        // Attempt login via Doctor Portal Login page
        $loginResponse = $this->post('/doctor-portal/login', [
            'email'    => 'pending@hospital.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertRedirect(route('doctor.pending-approval'));
        $this->assertFalse(auth()->check());
    }

    /**
     * TEST 6: Approval enables access to the correct doctor profile.
     */
    public function test_approval_enables_access_to_the_correct_doctor_profile(): void
    {
        // 1. Submit registration
        $this->post('/doctor-portal/register', [
            'doctor_identifier'     => $this->doctorA->id,
            'name'                  => 'Dr. Sarah Khan',
            'email'                 => 'sarah.khan@hospital.com',
            'phone'                 => '03001234567',
            'license_number'        => 'PMDC-12345-P',
            'password'              => 'DoctorSecret123!',
            'password_confirmation' => 'DoctorSecret123!',
            'terms'                 => '1',
        ]);

        $regRequest = DoctorRegistrationRequest::where('doctor_id', $this->doctorA->id)->first();
        $user = User::where('email', 'sarah.khan@hospital.com')->first();

        // 2. Admin approves request
        $approveResponse = $this->actingAs($this->adminUser)->post("/doctor-requests/{$regRequest->id}/approve", [
            'admin_remarks' => 'Verified PMDC license against official database.',
        ]);
        $approveResponse->assertRedirect();

        // 3. Assert status and database link
        $this->assertEquals('approved', $regRequest->fresh()->status);
        $this->assertEquals($this->adminUser->id, $regRequest->fresh()->reviewed_by);
        $this->assertEquals('active', $user->fresh()->status);
        $this->assertEquals($user->id, $this->doctorA->fresh()->user_id);
        $this->assertTrue($this->doctorA->fresh()->is_active);

        $user = $user->fresh();

        // 4. Approved doctor can log in and view their dashboard
        $loginResponse = $this->post('/doctor-portal/login', [
            'email'    => 'sarah.khan@hospital.com',
            'password' => 'DoctorSecret123!',
        ]);
        $loginResponse->assertRedirect(route('doctor.dashboard'));
        $this->assertAuthenticatedAs($user);

        // 5. Dashboard loads with correct doctor's data
        $dashResponse = $this->actingAs($user)->get('/doctor-portal/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Sarah Khan');
        $dashResponse->assertSee('Cardiologist');
    }

    /**
     * TEST 7: Rejected and suspended accounts cannot access the portal.
     */
    public function test_rejected_and_suspended_accounts_cannot_access_the_portal(): void
    {
        // 1. Rejected account test
        $rejectedUser = User::factory()->create([
            'name'     => 'Dr. Rejected',
            'email'    => 'rejected@hospital.com',
            'role'     => 'doctor',
            'status'   => 'rejected',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($rejectedUser)->get('/doctor-portal/dashboard');
        $response->assertStatus(403);

        // Reset auth session before testing login
        auth()->logout();

        $loginResponse = $this->post('/doctor-portal/login', [
            'email'    => 'rejected@hospital.com',
            'password' => 'password123',
        ]);
        $loginResponse->assertSessionHasErrors('email');
        $this->assertFalse(auth()->check());

        // 2. Suspended account test
        $suspendedUser = $this->doctorB->user;
        $suspendedUser->status = 'suspended';
        $suspendedUser->save();
        $this->doctorB->status = 'inactive';
        $this->doctorB->is_active = false;
        $this->doctorB->save();

        $susResponse = $this->actingAs($suspendedUser)->get('/doctor-portal/dashboard');
        $susResponse->assertStatus(403);

        $susQueueResponse = $this->actingAs($suspendedUser)->get('/doctor-portal/opd-queue');
        $susQueueResponse->assertStatus(403);
    }

    /**
     * TEST 8: One doctor cannot access another doctor's patients, tokens, or reports.
     */
    public function test_one_doctor_cannot_access_another_doctor_patients_tokens_or_reports(): void
    {
        // Link Doctor A to an active user
        $doctorUserA = User::factory()->create([
            'name'   => 'Dr. Sarah Khan',
            'email'  => 'sarah.khan@hospital.com',
            'role'   => 'doctor',
            'status' => 'active',
        ]);
        $this->doctorA->user_id = $doctorUserA->id;
        $this->doctorA->save();

        $doctorUserB = $this->doctorB->user;

        // 1. Doctor A cannot call, complete, or cancel Doctor B's token (IDOR)
        $callResponse = $this->actingAs($doctorUserA)->post("/doctor-portal/opd-queue/{$this->tokenB->id}/call");
        $callResponse->assertStatus(403);

        $compResponse = $this->actingAs($doctorUserA)->post("/doctor-portal/opd-queue/{$this->tokenB->id}/complete");
        $compResponse->assertStatus(403);

        // 2. Doctor A cannot view consultation workspace of Doctor B's token
        $consultResponse = $this->actingAs($doctorUserA)->get("/doctor-portal/consultations/{$this->tokenB->id}");
        $consultResponse->assertStatus(403);

        // 3. Doctor A cannot access Patient B's details (Patient B has only seen Doctor B)
        $patientResponse = $this->actingAs($doctorUserA)->get("/doctor-portal/patients/{$this->patientB->id}");
        $patientResponse->assertStatus(403);

        // 4. Doctor A cannot view or print Prescription written by Doctor B
        $rxB = Prescription::create([
            'prescription_number' => 'RX-B-001',
            'patient_id'          => $this->patientB->id,
            'doctor_id'           => $this->doctorB->id,
            'status'              => 'issued',
            'is_finalized'        => true,
            'created_by'          => $doctorUserB->id,
        ]);

        $rxShowResponse = $this->actingAs($doctorUserA)->get("/doctor-portal/prescriptions/{$rxB->id}");
        $rxShowResponse->assertStatus(403);

        $rxPrintResponse = $this->actingAs($doctorUserA)->get("/doctor-portal/prescriptions/{$rxB->id}/print");
        $rxPrintResponse->assertStatus(403);
    }

    /**
     * TEST 9: Doctors cannot access administrator functions.
     */
    public function test_doctors_cannot_access_administrator_functions(): void
    {
        $doctorUserB = $this->doctorB->user;

        // 1. Cannot access admin doctor registration requests
        $response1 = $this->actingAs($doctorUserB)->get('/doctor-requests');
        $this->assertTrue(in_array($response1->status(), [403, 302], true));

        // 2. Cannot approve or reject requests
        $response2 = $this->actingAs($doctorUserB)->post('/doctor-requests/1/approve');
        $this->assertTrue(in_array($response2->status(), [403, 302], true));

        // 3. Cannot access user management or settings
        $response3 = $this->actingAs($doctorUserB)->get('/settings');
        $this->assertTrue(in_array($response3->status(), [403, 302], true));

        $response4 = $this->actingAs($doctorUserB)->get('/settings/users');
        $this->assertTrue(in_array($response4->status(), [403, 302], true));
    }

    /**
     * TEST 10: Existing Admin login, Doctor Portal, OPD Token, and billing workflows remain functional.
     */
    public function test_existing_admin_login_doctor_portal_opd_tokens_and_billing_remain_functional(): void
    {
        // 1. Admin login & dashboard
        $adminLogin = $this->post('/login', [
            'email'    => 'admin@hospital.com',
            'password' => 'password', // User::factory default password
        ]);
        $adminLogin->assertRedirect('/dashboard');

        // Admin can view dashboard
        $this->actingAs($this->adminUser)->get('/dashboard')->assertStatus(200);

        // Admin can view doctor registration requests list
        $requestsList = $this->actingAs($this->adminUser)->get('/doctor-requests');
        $requestsList->assertStatus(200);
        $requestsList->assertSee('Doctor Portal Registration Requests');

        // 2. OPD Tokens page works
        $opdResponse = $this->actingAs($this->adminUser)->get('/opd/tokens');
        $opdResponse->assertStatus(200);

        // 3. Hospital Billing index works
        $billingResponse = $this->actingAs($this->adminUser)->get('/hospital-billing');
        $billingResponse->assertStatus(200);

        // 4. Doctor Portal Dashboard works for active doctor
        $doctorDashboard = $this->actingAs($this->doctorB->user)->get('/doctor-portal/dashboard');
        $doctorDashboard->assertStatus(200);
        $doctorDashboard->assertSee('Dr. Tariq Mahmood');
    }
}
