<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\HospitalBill;
use App\Models\HospitalService;
use App\Services\HospitalBillingService;
use Illuminate\Support\Facades\DB;

echo "=================================================================\n";
echo "RUNNING COMPREHENSIVE DOCTOR REVENUE SHARE & TOKEN BILLING TESTS\n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest($name, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        echo "[PASS] $name\n";
        if ($details) echo "       $details\n";
        $passCount++;
    } else {
        echo "[FAIL] $name\n";
        if ($details) echo "       ERROR: $details\n";
        $failCount++;
    }
}

// -----------------------------------------------------------------
// TEST 1: Fee = 500, Doctor Share = 70% -> Doctor = 350, Hospital = 150
// -----------------------------------------------------------------
$doc1 = new Doctor([
    'name' => 'Doctor 1 Test',
    'consultation_fee' => 500,
    'doctor_share_percentage' => 70,
]);
$doc1->save(); // boots saving hook to compute hospital_share_percentage
$split1 = $doc1->calculateConsultationSplit();

assertTest(
    "TEST 1: 500 @ 70% Split Calculation",
    $split1['doctor_amount'] == 350.00 && $split1['hospital_amount'] == 150.00 && $doc1->hospital_share_percentage == 30.00,
    "Doctor: {$split1['doctor_amount']}, Hospital: {$split1['hospital_amount']}, Hosp Pct: {$doc1->hospital_share_percentage}%"
);

// -----------------------------------------------------------------
// TEST 2: Fee = 450, Doctor Share = 70% -> Doctor = 315, Hospital = 135
// -----------------------------------------------------------------
$doc2 = new Doctor([
    'name' => 'Doctor 2 Test',
    'consultation_fee' => 450,
    'doctor_share_percentage' => 70,
]);
$doc2->save();
$split2 = $doc2->calculateConsultationSplit();

assertTest(
    "TEST 2: 450 @ 70% Split Calculation",
    $split2['doctor_amount'] == 315.00 && $split2['hospital_amount'] == 135.00 && $doc2->hospital_share_percentage == 30.00,
    "Doctor: {$split2['doctor_amount']}, Hospital: {$split2['hospital_amount']}, Hosp Pct: {$doc2->hospital_share_percentage}%"
);

// -----------------------------------------------------------------
// TEST 3: Fee = 250, Doctor Share = 80% -> Doctor = 200, Hospital = 50
// -----------------------------------------------------------------
$doc3 = new Doctor([
    'name' => 'Doctor 3 Test',
    'consultation_fee' => 250,
    'doctor_share_percentage' => 80,
]);
$doc3->save();
$split3 = $doc3->calculateConsultationSplit();

assertTest(
    "TEST 3: 250 @ 80% Split Calculation",
    $split3['doctor_amount'] == 200.00 && $split3['hospital_amount'] == 50.00 && $doc3->hospital_share_percentage == 20.00,
    "Doctor: {$split3['doctor_amount']}, Hospital: {$split3['hospital_amount']}, Hosp Pct: {$doc3->hospital_share_percentage}%"
);

// -----------------------------------------------------------------
// TEST 4: Historical Snapshot Immutability
// Token #001 created at 70% ($350).
// Doctor Profile updated to 75%.
// Token #002 created at 75% ($375).
// Token #001 MUST remain 70% ($350)!
// -----------------------------------------------------------------
$testDoctor = Doctor::create([
    'name' => 'Dr. Snapshot Test',
    'specialization' => 'Cardiology',
    'consultation_fee' => 500,
    'doctor_share_percentage' => 70,
    'status' => 'active',
]);

$testPatient = Patient::create([
    'name' => 'Patient Snapshot A',
    'age' => 30,
    'gender' => 'Male',
    'phone' => '0300-1111111',
]);

$billingService = app(HospitalBillingService::class);
$today = now()->toDateString();
$nextToken = (PatientToken::where('token_date', $today)->max('token_number') ?: 0) + 1;

// Generate Token 1 with 70% split
$token1 = PatientToken::create([
    'patient_id' => $testPatient->id,
    'doctor_id' => $testDoctor->id,
    'token_number' => $nextToken++,
    'token_date' => $today,
    'payment_type' => 'paid',
    'consultation_fee' => $testDoctor->consultation_fee,
    'charged_amount' => $testDoctor->consultation_fee,
    'discount_amount' => 0,
    'doctor_share_percentage' => $testDoctor->doctor_share_percentage,
    'hospital_share_percentage' => $testDoctor->hospital_share_percentage,
    'doctor_share_amount' => round(500 * (70 / 100), 2),
    'hospital_share_amount' => round(500 - round(500 * (70 / 100), 2), 2),
    'status' => 'waiting',
]);

$bill1 = $billingService->createBill([
    'patient_id' => $testPatient->id,
    'doctor_id' => $testDoctor->id,
    'patient_token_id' => $token1->id,
    'total_amount' => 500,
    'doctor_share_percentage' => 70,
    'payment_type' => 'paid',
    'paid_amount' => 500,
    'payment_method' => 'cash',
]);
$token1->update(['hospital_bill_id' => $bill1->id]);

// Now update doctor profile to 75%
$testDoctor->update([
    'doctor_share_percentage' => 75,
]);
$testDoctor->refresh();

// Generate Token 2 with new 75% profile
$splitForToken2 = $testDoctor->calculateConsultationSplit();
$token2 = PatientToken::create([
    'patient_id' => $testPatient->id,
    'doctor_id' => $testDoctor->id,
    'token_number' => $nextToken++,
    'token_date' => $today,
    'payment_type' => 'paid',
    'consultation_fee' => $testDoctor->consultation_fee,
    'charged_amount' => $testDoctor->consultation_fee,
    'discount_amount' => 0,
    'doctor_share_percentage' => $testDoctor->doctor_share_percentage,
    'hospital_share_percentage' => $testDoctor->hospital_share_percentage,
    'doctor_share_amount' => $splitForToken2['doctor_amount'],
    'hospital_share_amount' => $splitForToken2['hospital_amount'],
    'status' => 'waiting',
]);

$bill2 = $billingService->createBill([
    'patient_id' => $testPatient->id,
    'doctor_id' => $testDoctor->id,
    'patient_token_id' => $token2->id,
    'total_amount' => 500,
    'doctor_share_percentage' => 75,
    'payment_type' => 'paid',
    'paid_amount' => 500,
    'payment_method' => 'cash',
]);
$token2->update(['hospital_bill_id' => $bill2->id]);

// Reload token1 and bill1 from database
$token1->refresh();
$bill1->refresh();
$token2->refresh();
$bill2->refresh();

assertTest(
    "TEST 4A: Token #001 retains historical 70% share and PKR 350 doctor amount",
    $token1->doctor_share_percentage == 70.00 && $token1->doctor_share_amount == 350.00 && $bill1->doctor_share == 350.00 && $bill1->doctor_share_percentage == 70.00,
    "Token #1 doc%: {$token1->doctor_share_percentage}, doc amount: {$token1->doctor_share_amount}, bill1 doc share: {$bill1->doctor_share}"
);

assertTest(
    "TEST 4B: Token #002 correctly applies updated 75% share and PKR 375 doctor amount",
    $token2->doctor_share_percentage == 75.00 && $token2->doctor_share_amount == 375.00 && $bill2->doctor_share == 375.00 && $bill2->doctor_share_percentage == 75.00,
    "Token #2 doc%: {$token2->doctor_share_percentage}, doc amount: {$token2->doctor_share_amount}, bill2 doc share: {$bill2->doctor_share}"
);

// -----------------------------------------------------------------
// TEST 5: Free Patient
// Fee = 500, Collected = 0 -> Doctor = 0, Hospital = 0, Status = FREE
// -----------------------------------------------------------------
$freePatient = Patient::create([
    'name' => 'Free Patient Test',
    'age' => 45,
    'gender' => 'Female',
]);

$tokenFree = PatientToken::create([
    'patient_id' => $freePatient->id,
    'doctor_id' => $testDoctor->id,
    'token_number' => $nextToken++,
    'token_date' => $today,
    'payment_type' => 'free',
    'consultation_fee' => 500,
    'charged_amount' => 0,
    'discount_amount' => 500,
    'doctor_share_percentage' => $testDoctor->doctor_share_percentage,
    'hospital_share_percentage' => $testDoctor->hospital_share_percentage,
    'doctor_share_amount' => 0,
    'hospital_share_amount' => 0,
    'free_reason' => 'Poor Patient',
    'status' => 'waiting',
]);

$billFree = $billingService->createBill([
    'patient_id' => $freePatient->id,
    'doctor_id' => $testDoctor->id,
    'patient_token_id' => $tokenFree->id,
    'total_amount' => 500,
    'discount_amount' => 500,
    'doctor_share_percentage' => $testDoctor->doctor_share_percentage,
    'payment_type' => 'free',
    'paid_amount' => 0,
    'free_reason' => 'Poor Patient',
]);
$tokenFree->update(['hospital_bill_id' => $billFree->id]);

assertTest(
    "TEST 5: Free Patient sets Doctor Share = 0, Hospital Share = 0, Status = FREE",
    $tokenFree->doctor_share_amount == 0.00 && $tokenFree->hospital_share_amount == 0.00 && $billFree->doctor_share == 0.00 && $billFree->payment_status === 'free',
    "Token Doc: {$tokenFree->doctor_share_amount}, Hosp: {$tokenFree->hospital_share_amount}, Bill Status: {$billFree->payment_status}, Bill Paid: {$billFree->paid_amount}"
);

// -----------------------------------------------------------------
// TEST 6: Partial Payment
// Fee = 500, Paid = 300 -> Remaining = 200, Status = PARTIAL
// Doctor Share (75% on 300) = 225, Hospital Share = 75
// -----------------------------------------------------------------
$partialPatient = Patient::create([
    'name' => 'Partial Patient Test',
    'age' => 50,
    'gender' => 'Male',
]);

$paidAmount = 300.00;
$fee = 500.00;
$docPct = 75.00;
$partialDocShare = round($paidAmount * ($docPct / 100), 2);
$partialHospShare = round($paidAmount - $partialDocShare, 2);

$tokenPartial = PatientToken::create([
    'patient_id' => $partialPatient->id,
    'doctor_id' => $testDoctor->id,
    'token_number' => $nextToken++,
    'token_date' => $today,
    'payment_type' => 'partial',
    'consultation_fee' => $fee,
    'charged_amount' => $paidAmount,
    'discount_amount' => 0,
    'doctor_share_percentage' => $docPct,
    'hospital_share_percentage' => round(100 - $docPct, 2),
    'doctor_share_amount' => $partialDocShare,
    'hospital_share_amount' => $partialHospShare,
    'status' => 'waiting',
]);

$billPartial = $billingService->createBill([
    'patient_id' => $partialPatient->id,
    'doctor_id' => $testDoctor->id,
    'patient_token_id' => $tokenPartial->id,
    'total_amount' => $fee,
    'doctor_share_percentage' => $docPct,
    'payment_type' => 'partial',
    'paid_amount' => $paidAmount,
    'payment_method' => 'cash',
]);
$tokenPartial->update(['hospital_bill_id' => $billPartial->id]);

assertTest(
    "TEST 6: Partial Payment: Paid = 300, Remaining = 200, Status = PARTIAL, Doc Share on collected cash = 225",
    $billPartial->paid_amount == 300.00 && $billPartial->due_amount == 200.00 && $billPartial->payment_status === 'partial' && $billPartial->doctor_share == 225.00 && $billPartial->hospital_share == 75.00,
    "Paid: {$billPartial->paid_amount}, Due: {$billPartial->due_amount}, Status: {$billPartial->payment_status}, Doc Share: {$billPartial->doctor_share}, Hosp Share: {$billPartial->hospital_share}"
);

// -----------------------------------------------------------------
// TEST 7: Doctor Payable and Ledger verification
// Total credited to Dr. Snapshot Test should be:
// Bill 1: 350
// Bill 2: 375
// Bill Free: 0
// Bill Partial: 225
// Total Expected Doctor Earned = 350 + 375 + 0 + 225 = 950
// -----------------------------------------------------------------
$testDoctor->refresh();
$totalEarned = (float) $testDoctor->total_earned;
$currentPayable = (float) $testDoctor->current_payable;

assertTest(
    "TEST 7: Doctor Ledger accurately tallies earned shares (350 + 375 + 0 + 225 = 950)",
    $totalEarned == 950.00 && $currentPayable == 950.00,
    "Doctor Total Earned: PKR {$totalEarned}, Current Payable: PKR {$currentPayable}"
);

// -----------------------------------------------------------------
// TEST 8: Doctor Settlement Integration
// Settle 450 to Dr. Snapshot Test
// Remaining should become: 950 - 450 = 500
// -----------------------------------------------------------------
$settlement = $billingService->settleDoctor($testDoctor, 450.00, 'cash', 'Partial settlement payment');
$testDoctor->refresh();

assertTest(
    "TEST 8: Doctor Settlement payout (PKR 450) reduces Current Payable to PKR 500",
    $testDoctor->total_paid == 450.00 && $testDoctor->current_payable == 500.00 && $settlement->remaining_payable == 500.00,
    "Total Paid: PKR {$testDoctor->total_paid}, Remaining Payable: PKR {$testDoctor->current_payable}"
);
// -----------------------------------------------------------------
// TEST 9: Hospital Procedure Isolation (Section 16)
// A procedure (e.g. Minor Procedure, Fee = 100, Doc % = 10%, Hosp % = 90%)
// must preserve its own split and NOT use Doctor 75% consultation share.
// -----------------------------------------------------------------
$testProcedure = HospitalService::firstOrCreate(
    ['code' => 'TEST-PROC-10'],
    [
        'name' => 'Minor Procedure Test',
        'service_type' => 'procedure',
        'default_fee' => 100.00,
        'doctor_share_percentage' => 10.00,
        'hospital_share_percentage' => 90.00,
        'is_active' => true,
    ]
);

$procedureBill = $billingService->createBill([
    'patient_id' => $testPatient->id,
    'doctor_id' => $testDoctor->id,
    'hospital_service_id' => $testProcedure->id,
    'total_amount' => $testProcedure->default_fee,
    'payment_type' => 'paid',
    'paid_amount' => $testProcedure->default_fee,
    'payment_method' => 'cash',
]);

assertTest(
    "TEST 9: Hospital procedure uses procedure split (10/90) and NOT doctor profile consultation split (75/25)",
    $procedureBill->doctor_share_percentage == 10.00 && $procedureBill->hospital_share_percentage == 90.00 && $procedureBill->doctor_share == 10.00 && $procedureBill->hospital_share == 90.00,
    "Bill Doc%: {$procedureBill->doctor_share_percentage}%, Hosp%: {$procedureBill->hospital_share_percentage}%, Doc Share: {$procedureBill->doctor_share}, Hosp Share: {$procedureBill->hospital_share}"
);

echo "\n=================================================================\n";
echo "TEST RESULTS SUMMARY: $passCount PASSED, $failCount FAILED\n";
echo "=================================================================\n";

if ($failCount > 0) {
    exit(1);
}
