<?php

namespace Tests\Feature;

use App\Models\Doctor;
use App\Models\HospitalAccountLedger;
use App\Models\HospitalBill;
use App\Models\HospitalBillPayment;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\User;
use App\Services\HospitalBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PatientTokenSchemaCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Doctor $doctor;
    protected HospitalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->user);

        $this->doctor = Doctor::create([
            'name'                    => 'Dr. Sarah Khan',
            'specialization'          => 'General Physician',
            'consultation_fee'        => 1000.00,
            'doctor_share_percentage' => 70.00,
            'status'                  => 'active',
        ]);

        $this->service = HospitalService::create([
            'name'             => 'OPD General Consultation',
            'code'             => 'SRV-CONSULT',
            'service_type'     => 'consultation',
            'default_fee'      => 0.00,
            'is_active'        => true,
            'requires_doctor'  => true,
        ]);
    }

    /**
     * 1. Schema has all required columns after migration.
     */
    public function test_patient_tokens_table_has_all_required_columns(): void
    {
        $expectedColumns = [
            'id',
            'patient_id',
            'doctor_id',
            'hospital_service_id',
            'hospital_bill_id',
            'token_number',
            'token_date',
            'payment_type',
            'consultation_fee',
            'charged_amount',
            'doctor_share_percentage',
            'hospital_share_percentage',
            'doctor_share_amount',
            'hospital_share_amount',
            'discount_amount',
            'previous_due',
            'paid_today',
            'balance_due',
            'free_reason',
            'other_reason',
            'status',
            'called_at',
            'completed_at',
            'notes',
        ];

        foreach ($expectedColumns as $col) {
            $this->assertTrue(
                Schema::hasColumn('patient_tokens', $col),
                "Column {$col} must exist on patient_tokens table."
            );
        }
    }

    /**
     * 2. OPD token creation with no previous balance.
     */
    public function test_opd_token_creation_with_no_previous_balance(): void
    {
        $patient = Patient::create([
            'name'   => 'Ali Ahmed',
            'gender' => 'Male',
            'age'    => 30,
            'phone'  => '03001234567',
        ]);

        $billingService = app(HospitalBillingService::class);
        $token = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'paid',
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $this->assertEquals(0.00, (float) $token->previous_due);
        $this->assertEquals(1000.00, (float) $token->paid_today);
        $this->assertEquals(0.00, (float) $token->balance_due);
        $this->assertEquals('paid', $token->payment_type);
        $this->assertEquals(1, $token->token_number);

        $this->assertDatabaseHas('patient_tokens', [
            'id'           => $token->id,
            'patient_id'   => $patient->id,
            'previous_due' => 0.00,
            'paid_today'   => 1000.00,
            'balance_due'  => 0.00,
        ]);
    }

    /**
     * 3. OPD token creation with a previous outstanding balance.
     */
    public function test_opd_token_creation_with_previous_outstanding_balance(): void
    {
        $patient = Patient::create([
            'name'   => 'Usman Tariq',
            'gender' => 'Male',
            'age'    => 40,
            'phone'  => '03112223344',
        ]);

        $billingService = app(HospitalBillingService::class);

        // First visit: fee 1000, paid 400, leaving 600 pending
        $firstToken = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'partial',
            'paid_amount'         => 400.00,
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $this->assertEquals(0.00, (float) $firstToken->previous_due);
        $this->assertEquals(400.00, (float) $firstToken->paid_today);
        $this->assertEquals(600.00, (float) $firstToken->balance_due);
        $this->assertEquals(600.00, $patient->pendingBalance());

        // Second visit: new fee 1000 + previous due 600 = 1600 total. Patient pays 1000.
        $secondToken = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'partial',
            'paid_amount'         => 1000.00,
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $this->assertEquals(600.00, (float) $secondToken->previous_due);
        $this->assertEquals(1000.00, (float) $secondToken->paid_today);
        $this->assertEquals(600.00, (float) $secondToken->balance_due);
        $this->assertEquals(2, $secondToken->token_number);

        // Verification of FIFO: previous 600 bill is fully settled, current bill has 600 remaining
        $this->assertEquals(600.00, $patient->pendingBalance());
    }

    /**
     * 4. Free consultation token creation.
     */
    public function test_free_consultation_token_creation(): void
    {
        $patient = Patient::create([
            'name'   => 'Fatima Bibi',
            'gender' => 'Female',
            'age'    => 55,
            'phone'  => '03219988776',
        ]);

        $billingService = app(HospitalBillingService::class);
        $token = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'free',
            'free_reason'         => 'Hospital Staff Relative',
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $this->assertEquals('free', $token->payment_type);
        $this->assertEquals(0.00, (float) $token->charged_amount);
        $this->assertEquals(1000.00, (float) $token->discount_amount);
        $this->assertEquals('Hospital Staff Relative', $token->free_reason);
        $this->assertEquals(0.00, (float) $token->paid_today);
        $this->assertEquals(0.00, (float) $token->balance_due);
    }

    /**
     * 5. Newly registered patient creates token atomically.
     */
    public function test_new_patient_registration_with_token_creation(): void
    {
        $billingService = app(HospitalBillingService::class);

        $patient = Patient::create([
            'name'                => 'Zahid Mahmood',
            'father_husband_name' => 'Mahmood Khan',
            'age'                 => 45,
            'gender'              => 'Male',
            'phone'               => '03451122334',
        ]);

        $token = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'paid',
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $this->assertNotNull($token);
        $this->assertEquals($patient->id, $token->patient_id);
        $this->assertDatabaseHas('patients', ['id' => $patient->id, 'name' => 'Zahid Mahmood']);
    }

    /**
     * 6. Re-running migration is idempotent and doesn't fail.
     */
    public function test_migration_re_run_is_idempotent(): void
    {
        $exitCode = Artisan::call('migrate', ['--force' => true]);
        $this->assertEquals(0, $exitCode);

        // Re-run again
        $exitCodeAgain = Artisan::call('migrate', ['--force' => true]);
        $this->assertEquals(0, $exitCodeAgain);
    }

    /**
     * 7. Existing records are preserved after running migrations.
     */
    public function test_existing_token_and_ledger_records_remain_preserved(): void
    {
        $patient = Patient::create([
            'name'   => 'Kareem Ullah',
            'gender' => 'Male',
            'age'    => 28,
            'phone'  => '03335554433',
        ]);

        $billingService = app(HospitalBillingService::class);
        $token = $billingService->generateOpdTokenWithBilling([
            'patient_id'          => $patient->id,
            'doctor_id'           => $this->doctor->id,
            'hospital_service_id' => $this->service->id,
            'fee_type'            => 'paid',
            'payment_method'      => 'cash',
            'token_date'          => now()->toDateString(),
            'created_by'          => $this->user->id,
        ]);

        $tokenId = $token->id;
        $billCountBefore = HospitalBill::count();
        $tokenCountBefore = PatientToken::count();
        $ledgerCountBefore = HospitalAccountLedger::count();

        // Run migrate again
        Artisan::call('migrate', ['--force' => true]);

        $this->assertEquals($tokenCountBefore, PatientToken::count());
        $this->assertEquals($billCountBefore, HospitalBill::count());
        $this->assertEquals($ledgerCountBefore, HospitalAccountLedger::count());

        $reloadedToken = PatientToken::find($tokenId);
        $this->assertNotNull($reloadedToken);
        $this->assertEquals(1000.00, (float) $reloadedToken->consultation_fee);
        $this->assertEquals(1000.00, (float) $reloadedToken->paid_today);
    }
}
