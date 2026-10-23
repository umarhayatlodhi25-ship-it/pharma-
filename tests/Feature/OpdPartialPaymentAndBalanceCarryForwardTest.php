<?php

namespace Tests\Feature;

use App\Livewire\Opd\TokenManagement;
use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\HospitalBill;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpdPartialPaymentAndBalanceCarryForwardTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Doctor $doctorA;
    protected Doctor $doctorB;
    protected Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->doctorA = Doctor::create([
            'name'                     => 'Dr. Ahmed Khan',
            'specialization'           => 'Cardiologist',
            'consultation_fee'         => 600.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'phone'                    => '0300-1111222',
            'is_active'                => true,
        ]);

        $this->doctorB = Doctor::create([
            'name'                     => 'Dr. Fatima Noor',
            'specialization'           => 'Neurologist',
            'consultation_fee'         => 600.00,
            'doctor_share_percentage'   => 80.00,
            'hospital_share_percentage' => 20.00,
            'phone'                    => '0300-3333444',
            'is_active'                => true,
        ]);

        $this->patient = Patient::create([
            'name'                => 'Kamran Tariq',
            'father_husband_name' => 'Tariq Mehmood',
            'age'                 => 40,
            'gender'              => 'Male',
            'phone'               => '03009998877',
            'cnic'                => '35201-9998877-1',
            'address'             => 'Lahore Cantt',
        ]);
    }

    /**
     * Test Case 1: First visit with partial payment.
     * Consultation fee: 600, Paid: 500, Pending: 100.
     */
    public function test_partial_payment_on_first_visit_creates_correct_bill_and_token(): void
    {
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 500.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $token = PatientToken::where('patient_id', $this->patient->id)->first();
        $this->assertNotNull($token);

        // Verify Token fields
        $this->assertEquals(600.00, (float) $token->consultation_fee);
        $this->assertEquals(500.00, (float) $token->charged_amount);
        $this->assertEquals(0.00, (float) $token->previous_due);
        $this->assertEquals(500.00, (float) $token->paid_today);
        $this->assertEquals(100.00, (float) $token->balance_due);
        $this->assertEquals('partial', $token->payment_type);

        // Verify Bill fields
        $bill = $token->bill;
        $this->assertNotNull($bill);
        $this->assertEquals(600.00, (float) $bill->total_amount);
        $this->assertEquals(600.00, (float) $bill->net_amount);
        $this->assertEquals(500.00, (float) $bill->paid_amount);
        $this->assertEquals(100.00, (float) $bill->due_amount);
        $this->assertEquals('partial', $bill->payment_status);

        // Verify Doctor Share credited on cash basis (70% of 500 = 350)
        $this->assertEquals(350.00, (float) $bill->doctor_share);
        $this->assertEquals(150.00, (float) $bill->hospital_share);

        // Verify Doctor Ledger
        $ledgerEntry = DoctorLedger::where('doctor_id', $this->doctorA->id)->latest('id')->first();
        $this->assertNotNull($ledgerEntry);
        $this->assertEquals(350.00, (float) $ledgerEntry->credit);

        // Verify Patient Pending Balance
        $this->assertEquals(100.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(1, $this->patient->fresh()->unpaidBills()->count());
    }

    /**
     * Test Case 2: Automatic retrieval of pending balance via JSON endpoint and search.
     */
    public function test_patient_pending_balance_endpoint_and_search_retrieves_unpaid_amount(): void
    {
        // Generate initial bill with 100 due
        $this->test_partial_payment_on_first_visit_creates_correct_bill_and_token();

        // 1. Test dedicated endpoint
        $response = $this->actingAs($this->user)->getJson("/patients/{$this->patient->id}/pending-balance");
        $response->assertStatus(200);
        $response->assertJson([
            'patient_id'      => $this->patient->id,
            'pending_balance' => 100.00,
            'unpaid_bills'    => 1,
        ]);

        // 2. Test patient search autocomplete includes pending balance
        $searchResponse = $this->actingAs($this->user)->getJson("/patients/search?q=" . urlencode($this->patient->name));
        $searchResponse->assertStatus(200);
        $data = $searchResponse->json();
        $this->assertNotEmpty($data);
        $this->assertEquals(100.00, $data[0]['pending_balance']);
        $this->assertEquals(1, $data[0]['unpaid_bills_count']);
    }

    /**
     * Test Case 3: Carry forward to second visit and full payment (700) settles both bills.
     * Visit 1: 600 fee, 500 paid, 100 due.
     * Visit 2: 600 fee + 100 previous = 700 total due. Paid: 700.
     * Result: Old bill gets 100 (due 0), new bill gets 600 (due 0), balance 0.
     */
    public function test_carry_forward_and_full_payment_settles_both_bills_and_credits_doctors(): void
    {
        // Visit 1: Dr. Ahmed, fee 600, paid 500, due 100
        $this->test_partial_payment_on_first_visit_creates_correct_bill_and_token();
        $bill1 = HospitalBill::where('patient_id', $this->patient->id)->first();
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Visit 2: Dr. Fatima (fee 600, 80/20 share), previous due 100, total payable 700. Paid: 700.
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'paid',
            'paid_amount'    => 700.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $token2 = PatientToken::where('patient_id', $this->patient->id)->orderBy('id', 'desc')->first();
        $bill2 = $token2->bill;

        // Token 2 checks
        $this->assertEquals(600.00, (float) $token2->consultation_fee);
        $this->assertEquals(100.00, (float) $token2->previous_due);
        $this->assertEquals(700.00, (float) $token2->total_due);
        $this->assertEquals(700.00, (float) $token2->paid_today);
        $this->assertEquals(0.00, (float) $token2->balance_due);

        // Bill 1 checks (Oldest settled first: received 100)
        $bill1->refresh();
        $this->assertEquals(600.00, (float) $bill1->paid_amount);
        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals('paid', $bill1->payment_status);
        // Doctor A share increased by 70% of 100 = 70 => total 350 + 70 = 420
        $this->assertEquals(420.00, (float) $bill1->doctor_share);

        // Bill 2 checks (Received remaining 600)
        $this->assertEquals(600.00, (float) $bill2->net_amount);
        $this->assertEquals(600.00, (float) $bill2->paid_amount);
        $this->assertEquals(0.00, (float) $bill2->due_amount);
        $this->assertEquals('paid', $bill2->payment_status);
        // Doctor B share: 80% of 600 = 480
        $this->assertEquals(480.00, (float) $bill2->doctor_share);

        // Patient total pending balance is now 0
        $this->assertEquals(0.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(0, $this->patient->fresh()->unpaidBills()->count());
    }

    /**
     * Test Case 4: Carry forward with partial payment (650) on 700 total due.
     * Visit 1: 100 due.
     * Visit 2: 600 fee + 100 previous = 700 due. Paid: 650.
     * Result: Old bill completely settled (gets 100), new bill gets 550 (50 due).
     */
    public function test_carry_forward_with_partial_payment_settles_oldest_first(): void
    {
        // Visit 1: fee 600, paid 500, due 100
        $this->test_partial_payment_on_first_visit_creates_correct_bill_and_token();
        $bill1 = HospitalBill::where('patient_id', $this->patient->id)->first();
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Visit 2: fee 600, total due 700, paying 650
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 650.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $token2 = PatientToken::where('patient_id', $this->patient->id)->orderBy('id', 'desc')->first();
        $bill2 = $token2->bill;

        // Token 2 checks
        $this->assertEquals(100.00, (float) $token2->previous_due);
        $this->assertEquals(700.00, (float) $token2->total_due);
        $this->assertEquals(650.00, (float) $token2->paid_today);
        $this->assertEquals(50.00, (float) $token2->balance_due);

        // Old bill is completely settled
        $bill1->refresh();
        $this->assertEquals(600.00, (float) $bill1->paid_amount);
        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals('paid', $bill1->payment_status);

        // New bill receives remainder 550, leaving 50 due
        $this->assertEquals(550.00, (float) $bill2->paid_amount);
        $this->assertEquals(50.00, (float) $bill2->due_amount);
        $this->assertEquals('partial', $bill2->payment_status);

        // Patient pending balance is exactly 50
        $this->assertEquals(50.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(1, $this->patient->fresh()->unpaidBills()->count());
    }

    /**
     * Test Case 5: Small payment (50) only partially settles the oldest bill.
     * Old bill: 100 due. Next visit fee: 600. Total due: 700. Paid: 50.
     * Result: Old bill gets 50 (50 remaining), new bill gets 0 (600 due).
     * Total remaining: 650.
     */
    public function test_small_payment_only_settles_part_of_old_bill(): void
    {
        $this->test_partial_payment_on_first_visit_creates_correct_bill_and_token();
        $bill1 = HospitalBill::where('patient_id', $this->patient->id)->first();
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Visit 2: Pay 50
        $response = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 50.00,
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $token2 = PatientToken::where('patient_id', $this->patient->id)->orderBy('id', 'desc')->first();
        $bill2 = $token2->bill;

        // Old bill received 50 (total paid 550, due 50)
        $bill1->refresh();
        $this->assertEquals(550.00, (float) $bill1->paid_amount);
        $this->assertEquals(50.00, (float) $bill1->due_amount);
        $this->assertEquals('partial', $bill1->payment_status);

        // New bill received 0 (paid 0, due 600)
        $this->assertEquals(0.00, (float) $bill2->paid_amount);
        $this->assertEquals(600.00, (float) $bill2->due_amount);
        $this->assertEquals('partial', $bill2->payment_status);

        // Token 2 totals
        $this->assertEquals(50.00, (float) $token2->paid_today);
        $this->assertEquals(650.00, (float) $token2->balance_due);

        // Total patient balance is 650
        $this->assertEquals(650.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(2, $this->patient->fresh()->unpaidBills()->count());
    }

    /**
     * Test Case 6: Payment validation rejects negative numbers and overpayments.
     */
    public function test_payment_validations_prevent_invalid_amounts(): void
    {
        // 1. Negative amount
        $responseNeg = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'partial',
            'paid_amount'    => -50.00,
            'payment_method' => 'cash',
        ]);
        $responseNeg->assertSessionHasErrors(['paid_amount']);

        // 2. Overpayment (Total fee is 600, paying 800)
        $responseOver = $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'partial',
            'paid_amount'    => 800.00,
            'payment_method' => 'cash',
        ]);
        $responseOver->assertSessionHasErrors(['paid_amount']);
    }

    /**
     * Test Case 7: Thermal receipt slip displays full 5 line items.
     */
    public function test_thermal_receipt_slip_contains_all_line_items(): void
    {
        $this->test_carry_forward_with_partial_payment_settles_oldest_first();
        $token2 = PatientToken::where('patient_id', $this->patient->id)->orderBy('id', 'desc')->first();

        $response = $this->actingAs($this->user)->get("/patient-tokens/{$token2->id}/print");
        $response->assertStatus(200);

        // Asserts lines on thermal receipt
        $response->assertSee('Consultation Fee:');
        $response->assertSee('600.00');
        $response->assertSee('Previous Balance:');
        $response->assertSee('100.00');
        $response->assertSee('Total Amount Due:');
        $response->assertSee('700.00');
        $response->assertSee('Amount Paid:');
        $response->assertSee('650.00');
        $response->assertSee('Remaining Due:');
        $response->assertSee('50.00');
        $response->assertSee('(DUE)');
    }

    /**
     * Test Case 8: Patient profile displays outstanding balance and supports subsequent payment.
     */
    public function test_patient_profile_view_and_subsequent_payment_collection(): void
    {
        $this->test_partial_payment_on_first_visit_creates_correct_bill_and_token();
        $bill1 = HospitalBill::where('patient_id', $this->patient->id)->first();

        // View patient profile
        $response = $this->actingAs($this->user)->get("/patients/{$this->patient->id}");
        $response->assertStatus(200);
        $response->assertSee('Patient Account &amp; Outstanding Balance History', false);
        $response->assertSee('Outstanding: PKR 100.00');
        $response->assertSee('Collect Due');

        // Record subsequent payment for remaining 100
        $payResponse = $this->actingAs($this->user)->post("/hospital-billing/{$bill1->id}/payment", [
            'amount'         => 100.00,
            'payment_method' => 'cash',
            'reference_note' => 'Counter follow-up collection',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $bill1->refresh();
        $this->assertEquals(600.00, (float) $bill1->paid_amount);
        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals('paid', $bill1->payment_status);

        // Doctor ledger credited with remaining 70 (70% of 100)
        $this->assertEquals(420.00, (float) $bill1->doctor_share);
        $this->assertEquals(0.00, $this->patient->fresh()->pending_balance);
    }

    /**
     * Test Case 9: Livewire component handles partial payment and balance carry forward.
     */
    public function test_livewire_token_management_partial_payment_and_carry_forward(): void
    {
        // 1. Visit 1 via Livewire: Fee 600, Partial 500, Due 100
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->assertSet('previous_pending_balance', 0.0)
            ->set('doctor_id', $this->doctorA->id)
            ->set('payment_type', 'partial')
            ->set('paid_amount', 500)
            ->call('generateToken')
            ->assertHasNoErrors()
            ->assertSee('Token generated successfully');

        $this->assertEquals(100.0, $this->patient->fresh()->pending_balance);

        // 2. Visit 2 via Livewire: Selecting patient retrieves 100 pending balance
        Livewire::actingAs($this->user)
            ->test(TokenManagement::class)
            ->set('patient_mode', 'existing')
            ->call('selectPatient', $this->patient->id)
            ->assertSet('previous_pending_balance', 100.0)
            ->assertSet('unpaid_bills_count', 1)
            ->set('doctor_id', $this->doctorB->id)
            ->set('payment_type', 'paid')
            ->call('generateToken')
            ->assertHasNoErrors();

        // 3. Since payment_type was 'paid', total 700 was paid, clearing all balances
        $this->assertEquals(0.0, $this->patient->fresh()->pending_balance);
    }

    /**
     * Test Case F: Patient has multiple unpaid visits: every original bill remains traceable and no balance is duplicated.
     */
    public function test_case_f_multiple_unpaid_visits_remain_traceable_and_fifo_settled(): void
    {
        // Visit 1: Dr A, Fee 600, Paid 400 => 200 due
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 400.00,
            'payment_method' => 'cash',
        ]);
        $bill1 = HospitalBill::where('patient_id', $this->patient->id)->latest('id')->first();
        $this->assertEquals(200.00, (float) $bill1->due_amount);
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Visit 2: Dr B, Fee 600, Previous Due 200, Total Due 800. Paid 500.
        // FIFO: 200 clears Bill 1, remaining 300 pays on Bill 2 => Bill 2 due is 300.
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 500.00,
            'payment_method' => 'cash',
        ]);
        $bill2 = HospitalBill::where('patient_id', $this->patient->id)->latest('id')->first();
        $bill1->refresh();
        $this->assertEquals(0.00, (float) $bill1->due_amount);
        $this->assertEquals('paid', $bill1->payment_status);
        $this->assertEquals(300.00, (float) $bill2->due_amount);
        $this->assertEquals('partial', $bill2->payment_status);
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Visit 3: Dr A, Fee 600, Previous Due 300, Total Due 900. Paid 100.
        // FIFO: 100 pays on Bill 2 => Bill 2 due is 200. 0 pays on Bill 3 => Bill 3 due is 600.
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'token_date'     => now()->toDateString(),
            'payment_type'   => 'partial',
            'paid_amount'    => 100.00,
            'payment_method' => 'cash',
        ]);
        $bill3 = HospitalBill::where('patient_id', $this->patient->id)->latest('id')->first();
        $bill2->refresh();

        $this->assertEquals(200.00, (float) $bill2->due_amount);
        $this->assertEquals(600.00, (float) $bill3->due_amount);
        // Total pending balance is 200 + 600 = 800
        $this->assertEquals(800.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(2, $this->patient->fresh()->unpaidBills()->count());
    }

    /**
     * Test Case G: Two different patients have outstanding balances: their balances remain separate.
     */
    public function test_case_g_two_different_patients_maintain_isolated_balances(): void
    {
        $patient2 = Patient::create([
            'name'   => 'Zahid Abbas',
            'age'    => 35,
            'gender' => 'Male',
            'phone'  => '03112223344',
        ]);

        // Patient 1 pays 400 on 600 => 200 pending
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'partial',
            'paid_amount'    => 400.00,
            'payment_method' => 'cash',
        ]);
        PatientToken::where('patient_id', $this->patient->id)->update(['status' => 'completed']);

        // Patient 2 pays 500 on 600 => 100 pending
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $patient2->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'partial',
            'paid_amount'    => 500.00,
            'payment_method' => 'cash',
        ]);
        PatientToken::where('patient_id', $patient2->id)->update(['status' => 'completed']);

        $this->assertEquals(200.00, $this->patient->fresh()->pending_balance);
        $this->assertEquals(100.00, $patient2->fresh()->pending_balance);

        // Next visit for Patient 2 carries forward ONLY 100, not 300
        $service = app(\App\Services\HospitalBillingService::class);
        $tokenP2 = $service->generateOpdTokenWithBilling([
            'patient_id'     => $patient2->id,
            'doctor_id'      => $this->doctorB->id,
            'fee_type'       => 'paid',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(100.00, (float) $tokenP2->previous_due);
        $this->assertEquals(700.00, (float) $tokenP2->total_due);
        $this->assertEquals(0.00, $patient2->fresh()->pending_balance);
        // Patient 1 balance remains untouched at 200
        $this->assertEquals(200.00, $this->patient->fresh()->pending_balance);
    }

    /**
     * Test Case H: Refreshing or resubmitting the payment form does not create duplicate transactions.
     */
    public function test_case_h_resubmission_prevention_guards_against_duplicate_tokens(): void
    {
        $payload = [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'paid',
            'paid_amount'    => 600.00,
            'payment_method' => 'cash',
        ];

        // First submission
        $res1 = $this->actingAs($this->user)->post('/patient-tokens', $payload);
        $res1->assertSessionHasNoErrors();
        $this->assertEquals(1, PatientToken::count());

        // Second immediate submission with identical payload
        $res2 = $this->actingAs($this->user)->post('/patient-tokens', $payload);
        $res2->assertSessionHas('warning');
        $this->assertEquals(1, PatientToken::count()); // Still 1 token, no duplicate created
    }

    /**
     * Test Case I: Doctor and hospital collections and reports reflect actual payments correctly.
     */
    public function test_case_i_reports_reflect_actual_collections_not_uncollected_amounts(): void
    {
        // Visit: Fee 600, Paid 400 (70% Doctor = 280, 30% Hospital = 120), Due 200
        $this->actingAs($this->user)->post('/patient-tokens', [
            'patient_mode'   => 'existing',
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'payment_type'   => 'partial',
            'paid_amount'    => 400.00,
            'payment_method' => 'cash',
        ]);

        $reportRes = $this->actingAs($this->user)->get(route('reports.doctor-collection', ['doctor_id' => $this->doctorA->id]));
        $reportRes->assertStatus(200);

        // Collected revenue must reflect 400.00, not 600.00
        $reportRes->assertSee('400.00');
        // Unpaid due reflects 200.00
        $reportRes->assertSee('200.00');
        // Doctor earned share reflects 280.00
        $reportRes->assertSee('280.00');
    }

    /**
     * Test Case J: Free consultation and existing full-payment workflows continue to work.
     */
    public function test_case_j_free_consultation_and_full_payment_workflows_continue_to_work(): void
    {
        // 1. Standard Full Payment
        $tokenPaid = app(\App\Services\HospitalBillingService::class)->generateOpdTokenWithBilling([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorA->id,
            'fee_type'       => 'paid',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(600.00, (float) $tokenPaid->charged_amount);
        $this->assertEquals('paid', $tokenPaid->payment_type);
        $this->assertEquals('paid', $tokenPaid->bill->payment_status);
        $this->assertEquals(0.00, (float) $tokenPaid->bill->due_amount);
        $this->assertEquals(0.00, $this->patient->fresh()->pending_balance);
        $tokenPaid->update(['status' => 'completed']);

        // 2. Free Consultation Visit
        $tokenFree = app(\App\Services\HospitalBillingService::class)->generateOpdTokenWithBilling([
            'patient_id'     => $this->patient->id,
            'doctor_id'      => $this->doctorB->id,
            'fee_type'       => 'free',
            'free_reason'    => 'Poor Patient',
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(0.00, (float) $tokenFree->charged_amount);
        $this->assertEquals('free', $tokenFree->payment_type);
        $this->assertEquals('FREE', $tokenFree->display_fee);
        $this->assertEquals('free', $tokenFree->bill->payment_status);
        $this->assertEquals(0.00, (float) $tokenFree->bill->due_amount);
        $this->assertEquals(0.00, $this->patient->fresh()->pending_balance);
    }
}
