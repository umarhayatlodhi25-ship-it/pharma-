<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\HospitalAccountLedger;
use App\Models\HospitalBill;
use App\Models\HospitalBillItem;
use App\Models\HospitalBillPayment;
use App\Models\HospitalBillPaymentAllocation;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use App\Services\HospitalBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HospitalBillingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Doctor $doctor;
    protected Patient $patient;
    protected HospitalService $dripService;
    protected HospitalService $sugarTestService;
    protected HospitalService $bpCheckService;
    protected HospitalBillingService $billingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billingService = app(HospitalBillingService::class);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->doctor = Doctor::create([
            'name'                     => 'Dr. Shahid Latif',
            'specialization'           => 'General Physician',
            'consultation_fee'         => 600.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'phone'                    => '0300-1234567',
            'is_active'                => true,
        ]);

        $this->patient = Patient::create([
            'name'                => 'Muhammad Usman',
            'father_husband_name' => 'Tariq Mehmood',
            'age'                 => 35,
            'gender'              => 'Male',
            'phone'               => '0321-9876543',
            'cnic'                => '35202-1234567-1',
            'address'             => 'Model Town, Lahore',
        ]);

        $this->dripService = HospitalService::create([
            'name'                     => 'IV Drip Administration',
            'code'                     => 'SRV-DRIP',
            'service_type'             => 'procedure',
            'default_fee'              => 800.00,
            'doctor_share_percentage'   => 0.00,
            'hospital_share_percentage' => 100.00,
            'is_active'                => true,
        ]);

        $this->sugarTestService = HospitalService::create([
            'name'                     => 'Blood Sugar Test (RBS)',
            'code'                     => 'SRV-SUGAR',
            'service_type'             => 'test',
            'default_fee'              => 200.00,
            'doctor_share_percentage'   => 0.00,
            'hospital_share_percentage' => 100.00,
            'is_active'                => true,
        ]);

        $this->bpCheckService = HospitalService::create([
            'name'                     => 'Blood Pressure Monitoring',
            'code'                     => 'SRV-BP',
            'service_type'             => 'checkup',
            'default_fee'              => 100.00,
            'doctor_share_percentage'   => 0.00,
            'hospital_share_percentage' => 100.00,
            'is_active'                => true,
        ]);
    }

    /**
     * Scenario 1: Existing OPD patient can receive a hospital service bill.
     */
    public function test_scenario_1_existing_opd_patient_can_receive_hospital_service_bill(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $bill = $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->dripService->id,
            'total_amount'        => 800.00,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('hospital_bills', [
            'id'               => $bill->id,
            'billing_type'     => 'opd',
            'patient_id'       => $this->patient->id,
            'patient_token_id' => $token->id,
            'total_amount'     => 800.00,
            'payment_status'   => 'paid',
        ]);
    }

    /**
     * Scenario 2: Service bill can be created without generating a new OPD token.
     */
    public function test_scenario_2_service_bill_created_without_generating_new_opd_token(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $initialTokenCount = PatientToken::where('patient_id', $this->patient->id)->count();
        $this->assertEquals(1, $initialTokenCount);

        // Add procedure bill
        $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'hospital_service_id' => $this->dripService->id,
            'total_amount'        => 800.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);

        $finalTokenCount = PatientToken::where('patient_id', $this->patient->id)->count();
        $this->assertEquals(1, $finalTokenCount, "No new OPD token must be created when adding a hospital service bill.");
    }

    /**
     * Scenario 3: Consultation fee is not charged again.
     */
    public function test_scenario_3_consultation_fee_is_not_charged_again(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // Consultation was 600
        $tokenBill = HospitalBill::where('patient_token_id', $token->id)->first();
        $this->assertEquals(600.00, (float)$tokenBill->total_amount);

        // New service bill for 800
        $serviceBill = $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'hospital_service_id' => $this->dripService->id,
            'total_amount'        => 800.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertEquals(800.00, (float)$serviceBill->total_amount, "Service bill must charge only the service fee and not duplicate the consultation fee.");
    }

    /**
     * Scenario 4: Direct service billing works without a doctor or OPD token.
     */
    public function test_scenario_4_direct_service_billing_works_without_doctor_or_opd_token(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => null,
            'doctor_id'           => null,
            'hospital_service_id' => $this->sugarTestService->id,
            'total_amount'        => 200.00,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('hospital_bills', [
            'id'               => $bill->id,
            'billing_type'     => 'direct',
            'doctor_id'        => null,
            'patient_token_id' => null,
            'total_amount'     => 200.00,
            'paid_amount'      => 200.00,
            'payment_status'   => 'paid',
        ]);
    }

    /**
     * Scenario 5: Existing patient can be selected.
     */
    public function test_scenario_5_existing_patient_can_be_selected(): void
    {
        $response = $this->actingAs($this->adminUser)->getJson(route('hospital-billing.search-patients', ['query' => 'Usman']));

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'name'           => 'Muhammad Usman',
            'patient_number' => $this->patient->patient_number,
        ]);
    }

    /**
     * Scenario 6: A new patient can be registered through the permitted workflow.
     */
    public function test_scenario_6_new_patient_can_be_registered_through_permitted_workflow(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('hospital-billing.store'), [
            'billing_mode'        => 'direct',
            'patient_mode'        => 'new',
            'patient_name'        => 'Bilal Ahmed',
            'father_husband_name' => 'Ahmed Ali',
            'age'                 => 28,
            'gender'              => 'Male',
            'phone'               => '0333-5554443',
            'total_amount'        => 300.00,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('patients', [
            'name'  => 'Bilal Ahmed',
            'phone' => '0333-5554443',
        ]);

        $newPatient = Patient::where('phone', '0333-5554443')->first();
        $this->assertDatabaseHas('hospital_bills', [
            'patient_id'   => $newPatient->id,
            'billing_type' => 'direct',
            'total_amount' => 300.00,
        ]);
    }

    /**
     * Scenario 7: Multiple hospital services calculate correctly.
     */
    public function test_scenario_7_multiple_hospital_services_calculate_correctly(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'items'        => [
                [
                    'hospital_service_id' => $this->dripService->id,
                    'service_name'        => $this->dripService->name,
                    'quantity'            => 2,
                    'unit_price'          => 800.00,
                    'discount_amount'     => 0.00,
                ],
                [
                    'hospital_service_id' => $this->sugarTestService->id,
                    'service_name'        => $this->sugarTestService->name,
                    'quantity'            => 1,
                    'unit_price'          => 200.00,
                    'discount_amount'     => 50.00,
                ],
                [
                    'hospital_service_id' => $this->bpCheckService->id,
                    'service_name'        => $this->bpCheckService->name,
                    'quantity'            => 1,
                    'unit_price'          => 100.00,
                    'discount_amount'     => 0.00,
                ],
            ],
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // Calculation: (2*800 = 1600) + (1*200 - 50 = 150) + (1*100 = 100) = 1850.00
        $this->assertEquals(1850.00, (float)$bill->net_amount);
        $this->assertEquals(3, $bill->items()->count());
    }

    /**
     * Scenario 8: Full payment marks the bill as Paid.
     */
    public function test_scenario_8_full_payment_marks_the_bill_as_paid(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 500.00,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertEquals('paid', $bill->payment_status);
        $this->assertEquals(500.00, (float)$bill->paid_amount);
        $this->assertEquals(0.00, (float)$bill->due_amount);
    }

    /**
     * Scenario 9: Partial payment marks the bill as Partially Paid.
     */
    public function test_scenario_9_partial_payment_marks_the_bill_as_partially_paid(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 800.00,
            'payment_type' => 'partial',
            'paid_amount'  => 300.00,
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertEquals('partial', $bill->payment_status);
        $this->assertEquals(300.00, (float)$bill->paid_amount);
        $this->assertEquals(500.00, (float)$bill->due_amount);
    }

    /**
     * Scenario 10: Unpaid bill remains outstanding.
     */
    public function test_scenario_10_unpaid_bill_remains_outstanding(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 800.00,
            'payment_type' => 'pending',
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertEquals('pending', $bill->payment_status);
        $this->assertEquals(0.00, (float)$bill->paid_amount);
        $this->assertEquals(800.00, (float)$bill->due_amount);
        $this->assertEquals(800.00, $this->patient->pending_balance);
    }

    /**
     * Scenario 11: Subsequent payment reduces the correct outstanding balance.
     */
    public function test_scenario_11_subsequent_payment_reduces_the_correct_outstanding_balance(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 800.00,
            'payment_type' => 'partial',
            'paid_amount'  => 300.00,
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertEquals(500.00, (float)$bill->due_amount);

        // Subsequent payment of 200
        $bill->recordPayment(200.00, 'cash', 'Installment 2', $this->adminUser->id);

        $fresh = $bill->fresh();
        $this->assertEquals(500.00, (float)$fresh->paid_amount);
        $this->assertEquals(300.00, (float)$fresh->due_amount);
        $this->assertEquals('partial', $fresh->payment_status);

        // Final payment of 300
        $bill->recordPayment(300.00, 'cash', 'Final settlement', $this->adminUser->id);
        $final = $bill->fresh();
        $this->assertEquals(800.00, (float)$final->paid_amount);
        $this->assertEquals(0.00, (float)$final->due_amount);
        $this->assertEquals('paid', $final->payment_status);
    }

    /**
     * Scenario 12: Previous balance is never counted twice.
     */
    public function test_scenario_12_previous_balance_never_counted_twice(): void
    {
        // Bill 1: 400 unpaid
        $bill1 = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 400.00,
            'payment_type' => 'pending',
            'created_by'   => $this->adminUser->id,
        ]);
        $this->assertEquals(400.00, $this->patient->pending_balance);

        // Bill 2: 200 new service, with carry-forward opted in (total due 600), paid 600
        $bill2 = $this->billingService->createBill([
            'billing_type'             => 'direct',
            'patient_id'               => $this->patient->id,
            'total_amount'             => 200.00,
            'include_previous_balance' => true,
            'payment_type'             => 'paid',
            'paid_amount'              => 600.00,
            'created_by'               => $this->adminUser->id,
        ]);

        $this->assertEquals(0.00, (float)$bill1->fresh()->due_amount);
        $this->assertEquals('paid', $bill1->fresh()->payment_status);
        $this->assertEquals(0.00, (float)$bill2->fresh()->due_amount);
        $this->assertEquals('paid', $bill2->fresh()->payment_status);

        $this->assertEquals(0.00, $this->patient->fresh()->pending_balance, "Previous balance must be settled and not counted again.");
    }

    /**
     * Scenario 13: Consultation outstanding and hospital service outstanding remain distinct.
     */
    public function test_scenario_13_consultation_outstanding_and_hospital_service_outstanding_remain_distinct(): void
    {
        // 1. Unpaid OPD token consultation: 600 gross, 200 paid => 400 due
        $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'partial',
            'paid_amount'  => 200.00,
            'created_by'   => $this->adminUser->id,
        ]);

        // 2. Unpaid Direct Hospital Service: 800 gross, 300 paid => 500 due
        $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 800.00,
            'payment_type' => 'partial',
            'paid_amount'  => 300.00,
            'created_by'   => $this->adminUser->id,
        ]);

        $freshPatient = $this->patient->fresh();

        $this->assertEquals(400.00, $freshPatient->consultation_outstanding, "Consultation outstanding must be 400");
        $this->assertEquals(500.00, $freshPatient->hospital_service_outstanding, "Hospital service outstanding must be 500");
        $this->assertEquals(900.00, $freshPatient->pending_balance, "Total pending balance must equal sum of distinct debts");
    }

    /**
     * Scenario 14: Hospital service payments create the correct hospital ledger entries.
     */
    public function test_scenario_14_hospital_service_payments_create_correct_hospital_ledger_entries(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 800.00,
            'payment_type' => 'paid',
            'paid_amount'  => 800.00,
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertDatabaseHas('hospital_account_ledgers', [
            'hospital_bill_id' => $bill->id,
            'transaction_type' => 'receivable',
            'gross_amount'     => 800.00,
        ]);

        $this->assertDatabaseHas('hospital_account_ledgers', [
            'hospital_bill_id' => $bill->id,
            'transaction_type' => 'collection',
            'amount_received'  => 800.00,
            'status'           => 'posted',
        ]);
    }

    /**
     * Scenario 15: Doctor consultation-share ledgers are not affected by hospital service payments.
     */
    public function test_scenario_15_doctor_consultation_share_ledgers_not_affected_by_hospital_service_payments(): void
    {
        $initialDoctorPayable = (float)$this->doctor->current_payable;
        $this->assertEquals(0.00, $initialDoctorPayable);

        $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 1500.00,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $this->assertEquals(0.00, (float)$this->doctor->fresh()->current_payable, "Direct hospital service payments must not credit doctor ledger.");
        $this->assertEquals(0, DoctorLedger::where('doctor_id', $this->doctor->id)->count());
    }

    /**
     * Scenario 16: Repeated submissions do not create duplicate bills or payments.
     */
    public function test_scenario_16_repeated_submissions_do_not_create_duplicate_bills_or_payments(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 500.00,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        // Overpaying or paying on an already zero balance must fail
        $bill->recordPayment(100.00, 'cash', 'Illegal overpayment', $this->adminUser->id);
    }

    /**
     * Scenario 17: Receipt reprinting does not create a new transaction.
     */
    public function test_scenario_17_receipt_reprinting_does_not_create_new_transaction(): void
    {
        $bill = $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 500.00,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $initialBillCount = HospitalBill::count();
        $initialPaymentCount = HospitalBillPayment::count();

        // Access receipt endpoint 3 times
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->adminUser)->get(route('hospital-billing.receipt', $bill->id));
            $response->assertStatus(200);
        }

        $this->assertEquals($initialBillCount, HospitalBill::count());
        $this->assertEquals($initialPaymentCount, HospitalBillPayment::count());
    }

    /**
     * Scenario 18: Cancelled or reversed transactions are handled correctly.
     */
    public function test_scenario_18_cancelled_or_reversed_transactions_handled_correctly(): void
    {
        $ledger = HospitalAccountLedger::create([
            'entry_date'          => now()->toDateString(),
            'patient_id'          => $this->patient->id,
            'transaction_type'    => 'collection',
            'description'         => 'Test ledger item',
            'amount_received'     => 500.00,
            'outstanding_balance' => 0.00,
            'status'              => 'cancelled',
        ]);

        $collections = HospitalAccountLedger::collections()->get();
        $this->assertFalse($collections->contains('id', $ledger->id), "Cancelled ledger entries must be excluded from active collections.");
    }

    /**
     * Scenario 19: Unauthorized users cannot collect or modify payments.
     */
    public function test_scenario_19_unauthorized_users_cannot_collect_or_modify_payments(): void
    {
        // Unauthenticated guest user
        $response = $this->post(route('hospital-billing.store'), [
            'billing_mode' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 500.00,
        ]);

        $response->assertRedirect('/login');
    }

    /**
     * Scenario 20: Reports calculate billed, collected, and outstanding amounts correctly.
     */
    public function test_scenario_20_reports_calculate_billed_collected_and_outstanding_amounts_correctly(): void
    {
        // Bill A: 1000 gross, paid 400, due 600
        $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 1000.00,
            'payment_type' => 'partial',
            'paid_amount'  => 400.00,
            'created_by'   => $this->adminUser->id,
        ]);

        // Bill B: 500 gross, paid 500, due 0
        $this->billingService->createBill([
            'billing_type' => 'direct',
            'patient_id'   => $this->patient->id,
            'total_amount' => 500.00,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // Test Collections Report
        $colResponse = $this->actingAs($this->adminUser)->get(route('reports.hospital-service-collections', ['filter' => 'today']));
        $colResponse->assertStatus(200);
        $colResponse->assertViewHas('totalCollected', 900.00);

        // Test Billing Report
        $billResponse = $this->actingAs($this->adminUser)->get(route('reports.hospital-service-billing', ['filter' => 'today']));
        $billResponse->assertStatus(200);
        $billResponse->assertViewHas('totalGross', 1500.00);
        $billResponse->assertViewHas('totalCollected', 900.00);
        $billResponse->assertViewHas('totalOutstanding', 600.00);

        // Test Outstanding Report
        $outResponse = $this->actingAs($this->adminUser)->get(route('reports.hospital-service-outstanding'));
        $outResponse->assertStatus(200);
        $outResponse->assertViewHas('totalOutstanding', 600.00);
    }

    /**
     * Scenario 21: Mode A requires a valid doctor and OPD token.
     */
    public function test_scenario_21_mode_a_requires_valid_doctor_and_opd_token(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // 1. Missing doctor_id in Mode A HTTP POST
        $response1 = $this->actingAs($this->adminUser)->post(route('hospital-billing.store'), [
            'billing_mode'     => 'opd',
            'patient_mode'     => 'existing',
            'patient_id'       => $this->patient->id,
            'patient_token_id' => $token->id,
            'doctor_id'        => null,
            'total_amount'     => 500.00,
            'payment_type'     => 'paid',
            'payment_method'   => 'cash',
        ]);
        $response1->assertSessionHasErrors(['doctor_id']);

        // 2. Missing patient_token_id in Mode A HTTP POST
        $response2 = $this->actingAs($this->adminUser)->post(route('hospital-billing.store'), [
            'billing_mode'     => 'opd',
            'patient_mode'     => 'existing',
            'patient_id'       => $this->patient->id,
            'patient_token_id' => null,
            'doctor_id'        => $this->doctor->id,
            'total_amount'     => 500.00,
            'payment_type'     => 'paid',
            'payment_method'   => 'cash',
        ]);
        $response2->assertSessionHasErrors(['patient_token_id']);

        // 3. Service level validation for Mode A
        try {
            $this->billingService->createBill([
                'billing_type'        => 'opd',
                'patient_id'          => $this->patient->id,
                'patient_token_id'    => null,
                'doctor_id'           => $this->doctor->id,
                'total_amount'        => 500.00,
                'payment_type'        => 'paid',
                'created_by'          => $this->adminUser->id,
            ]);
            $this->fail("Expected InvalidArgumentException for missing token in Mode A");
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("An OPD token is required", $e->getMessage());
        }

        try {
            $this->billingService->createBill([
                'billing_type'        => 'opd',
                'patient_id'          => $this->patient->id,
                'patient_token_id'    => $token->id,
                'doctor_id'           => null,
                'total_amount'        => 500.00,
                'payment_type'        => 'paid',
                'created_by'          => $this->adminUser->id,
            ]);
            // If token already has doctor_id, service automatically uses token doctor, but if neither has doctor:
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString("doctor", $e->getMessage());
        }
    }

    /**
     * Scenario 22: Searching tokens prioritizes or filters by selected doctor.
     */
    public function test_scenario_22_search_tokens_prioritizes_and_filters_by_selected_doctor(): void
    {
        $doctor2 = Doctor::create([
            'name'                     => 'Dr. Ayesha Khan',
            'specialization'           => 'Gynecologist',
            'consultation_fee'         => 800.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'phone'                    => '0300-7654321',
            'is_active'                => true,
        ]);

        $patient2 = Patient::create([
            'name'   => 'Muhammad Usman Second',
            'age'    => 40,
            'gender' => 'Male',
            'phone'  => '0322-1112233',
        ]);

        $token1 = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $token2 = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $patient2->id,
            'doctor_id'    => $doctor2->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // Prioritized search when doctor_id is passed
        $resPrioritized = $this->actingAs($this->adminUser)->getJson(route('hospital-billing.search-tokens', [
            'query'     => 'Usman',
            'doctor_id' => $this->doctor->id,
        ]));
        $resPrioritized->assertStatus(200);
        $dataPrioritized = $resPrioritized->json();
        $this->assertNotEmpty($dataPrioritized);
        $this->assertEquals($token1->id, $dataPrioritized[0]['token_id']);
        $this->assertTrue($dataPrioritized[0]['doctor_matches']);

        // Filtered search when filter_doctor=1 is passed
        $resFiltered = $this->actingAs($this->adminUser)->getJson(route('hospital-billing.search-tokens', [
            'query'         => 'Usman',
            'doctor_id'     => $this->doctor->id,
            'filter_doctor' => 1,
        ]));
        $resFiltered->assertStatus(200);
        $dataFiltered = $resFiltered->json();
        $tokenIds = array_column($dataFiltered, 'token_id');
        $this->assertContains($token1->id, $tokenIds);
        $this->assertNotContains($token2->id, $tokenIds, "Filtered search must exclude tokens from other doctors.");
    }

    /**
     * Scenario 23: Mismatched doctor/token combination is rejected on server and service.
     */
    public function test_scenario_23_mismatched_doctor_token_combination_is_rejected(): void
    {
        $doctor2 = Doctor::create([
            'name'                     => 'Dr. Farhan Ali',
            'specialization'           => 'Cardiologist',
            'consultation_fee'         => 1000.00,
            'doctor_share_percentage'   => 70.00,
            'hospital_share_percentage' => 30.00,
            'phone'                    => '0311-9988776',
            'is_active'                => true,
        ]);

        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        // HTTP request mismatch
        $response = $this->actingAs($this->adminUser)->post(route('hospital-billing.store'), [
            'billing_mode'     => 'opd',
            'patient_mode'     => 'existing',
            'patient_id'       => $this->patient->id,
            'patient_token_id' => $token->id,
            'doctor_id'        => $doctor2->id, // Mismatched doctor
            'total_amount'     => 500.00,
            'payment_type'     => 'paid',
            'payment_method'   => 'cash',
        ]);
        $response->assertSessionHasErrors(['doctor_id']);

        // Service level mismatch rejection
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Mismatched doctor-token billing is not permitted");
        $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'doctor_id'           => $doctor2->id, // Mismatched doctor
            'total_amount'        => 500.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);
    }

    /**
     * Scenario 24: Mode A bills hospital services without recharging consultation fee.
     */
    public function test_scenario_24_mode_a_bills_hospital_services_without_recharging_consultation_fee(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $bill = $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->dripService->id,
            'total_amount'        => 800.00,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertEquals(800.00, (float)$bill->total_amount, "Bill should only charge the hospital service fee.");
        $this->assertEquals(0.00, (float)$bill->due_amount);
    }

    /**
     * Scenario 25: Mode B works without an OPD token.
     */
    public function test_scenario_25_mode_b_works_without_opd_token(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('hospital-billing.store'), [
            'billing_mode'        => 'direct',
            'patient_mode'        => 'existing',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => null,
            'doctor_id'           => null,
            'hospital_service_id' => $this->bpCheckService->id,
            'total_amount'        => 100.00,
            'payment_type'        => 'paid',
            'payment_method'      => 'cash',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hospital_bills', [
            'billing_type'     => 'direct',
            'patient_id'       => $this->patient->id,
            'patient_token_id' => null,
            'doctor_id'        => null,
            'total_amount'     => 100.00,
            'paid_amount'      => 100.00,
        ]);
    }

    /**
     * Scenario 26: Optional doctor selection in Mode B follows existing share rules.
     */
    public function test_scenario_26_mode_b_optional_doctor_follows_existing_share_rules(): void
    {
        // Procedure with 30% doctor share
        $procedureService = HospitalService::create([
            'name'                     => 'Minor Surgery / Procedure',
            'code'                     => 'SRV-SURG',
            'service_type'             => 'procedure',
            'default_fee'              => 1000.00,
            'doctor_share_percentage'   => 30.00,
            'hospital_share_percentage' => 70.00,
            'is_active'                => true,
        ]);

        // Case A: Mode B without doctor -> 0% doctor share, 100% hospital share
        $billNoDoctor = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'doctor_id'           => null,
            'hospital_service_id' => $procedureService->id,
            'total_amount'        => 1000.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);
        $this->assertEquals(0.00, (float)$billNoDoctor->doctor_share);
        $this->assertEquals(1000.00, (float)$billNoDoctor->hospital_share);
        $this->assertEquals(0.00, (float)$billNoDoctor->doctor_share_percentage);

        // Case B: Mode B with doctor, but service has 0% doctor share (e.g. Sugar test)
        $billZeroShare = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->sugarTestService->id,
            'total_amount'        => 200.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);
        $this->assertEquals(0.00, (float)$billZeroShare->doctor_share, "Doctor share must not be arbitrarily created when service is 0%.");
        $this->assertEquals(200.00, (float)$billZeroShare->hospital_share);

        // Case C: Mode B with doctor and service has configured 30% doctor share
        $initialPayable = (float)$this->doctor->fresh()->current_payable;
        $billWithShare = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $procedureService->id,
            'total_amount'        => 1000.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);
        $this->assertEquals(30.00, (float)$billWithShare->doctor_share_percentage);
        $this->assertEquals(300.00, (float)$billWithShare->doctor_share);
        $this->assertEquals(700.00, (float)$billWithShare->hospital_share);

        // Doctor ledger should be credited with 300
        $this->assertEquals($initialPayable + 300.00, (float)$this->doctor->fresh()->current_payable);
        $this->assertDatabaseHas('doctor_ledgers', [
            'doctor_id'        => $this->doctor->id,
            'hospital_bill_id' => $billWithShare->id,
            'amount'           => 300.00,
            'transaction_type' => 'credit',
        ]);
    }

    /**
     * Scenario 27: Partial payments and outstanding balances with doctor shares remain accurate.
     */
    public function test_scenario_27_partial_payments_and_outstanding_balances_with_doctor_shares(): void
    {
        $procedureService = HospitalService::create([
            'name'                     => 'Specialized Therapy',
            'code'                     => 'SRV-THERAPY',
            'service_type'             => 'procedure',
            'default_fee'              => 1000.00,
            'doctor_share_percentage'   => 40.00,
            'hospital_share_percentage' => 60.00,
            'is_active'                => true,
        ]);

        $initialPayable = (float)$this->doctor->fresh()->current_payable;

        // Bill of 1000, partial payment of 500
        $bill = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $procedureService->id,
            'total_amount'        => 1000.00,
            'payment_type'        => 'partial',
            'paid_amount'         => 500.00,
            'created_by'          => $this->adminUser->id,
        ]);

        $this->assertEquals('partial', $bill->payment_status);
        $this->assertEquals(500.00, (float)$bill->paid_amount);
        $this->assertEquals(500.00, (float)$bill->due_amount);
        $this->assertEquals(200.00, (float)$bill->doctor_share); // 500 * 40% = 200
        $this->assertEquals(300.00, (float)$bill->hospital_share); // 500 - 200 = 300
        $this->assertEquals($initialPayable + 200.00, (float)$this->doctor->fresh()->current_payable);

        // Subsequent payment of remaining 500
        $bill->recordPayment(500.00, 'cash', 'Remaining settlement', $this->adminUser->id);
        $fresh = $bill->fresh();

        $this->assertEquals('paid', $fresh->payment_status);
        $this->assertEquals(1000.00, (float)$fresh->paid_amount);
        $this->assertEquals(0.00, (float)$fresh->due_amount);
        $this->assertEquals(400.00, (float)$fresh->doctor_share); // Total 400
        $this->assertEquals(600.00, (float)$fresh->hospital_share); // Total 600
        $this->assertEquals($initialPayable + 400.00, (float)$this->doctor->fresh()->current_payable);
    }

    /**
     * Scenario 28: Receipt printing correctly displays attending and referred doctors without duplicate transactions.
     */
    public function test_scenario_28_receipt_printing_displays_doctors_without_duplicate_transactions(): void
    {
        $token = $this->billingService->generateOpdTokenWithBilling([
            'patient_id'   => $this->patient->id,
            'doctor_id'    => $this->doctor->id,
            'payment_type' => 'paid',
            'created_by'   => $this->adminUser->id,
        ]);

        $billModeA = $this->billingService->createBill([
            'billing_type'        => 'opd',
            'patient_id'          => $this->patient->id,
            'patient_token_id'    => $token->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->dripService->id,
            'total_amount'        => 800.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);

        $resReceiptA = $this->actingAs($this->adminUser)->get(route('hospital-billing.receipt', $billModeA->id));
        $resReceiptA->assertStatus(200);
        $resReceiptA->assertSee($this->doctor->name);
        $resReceiptA->assertSee($token->formatted_token_number);

        $billModeB = $this->billingService->createBill([
            'billing_type'        => 'direct',
            'patient_id'          => $this->patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->sugarTestService->id,
            'total_amount'        => 200.00,
            'payment_type'        => 'paid',
            'created_by'          => $this->adminUser->id,
        ]);

        $resReceiptB = $this->actingAs($this->adminUser)->get(route('hospital-billing.receipt', $billModeB->id));
        $resReceiptB->assertStatus(200);
        $resReceiptB->assertSee($this->doctor->name);
    }
}
