<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\AccountProfile;
use App\Services\HospitalBillingService;

echo "=================================================================\n";
echo "TESTING OPD TOKEN THERMAL RECEIPT\n";
echo "=================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertReceiptTest($label, $condition, $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "[PASS] $label\n";
        if ($details) echo "       $details\n";
    } else {
        $failCount++;
        echo "[FAIL] $label\n";
        if ($details) echo "       ERROR: $details\n";
    }
}

// 1. Prepare Doctor with revenue share configured
$doctor = Doctor::updateOrCreate(
    ['name' => 'Dr. Snapshot Test'],
    [
        'specialization' => 'General Physician',
        'phone' => '0300-9999999',
        'consultation_fee' => 500.00,
        'doctor_share_percentage' => 70.00,
        'hospital_share_percentage' => 30.00,
        'is_active' => true,
    ]
);

// 2. Prepare Patient
$patient = Patient::where('patient_number', 'PT-00013')->first();
if (!$patient) {
    $patient = Patient::where('name', 'Patient Snapshot A')->first();
    if ($patient) {
        $patient->update(['patient_number' => 'PT-00013']);
    } else {
        $patient = Patient::create([
            'patient_number' => 'PT-00013',
            'name' => 'Patient Snapshot A',
            'age' => 32,
            'gender' => 'Male',
            'phone' => '0300-1234567',
        ]);
    }
} else {
    $patient->update(['name' => 'Patient Snapshot A']);
}

// 3. Create or find Token #809
$today = '2026-09-30';
$token = PatientToken::where('token_date', $today)->where('token_number', 809)->first();

if (!$token) {
    $token = PatientToken::create([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'token_number' => 809,
        'token_date' => $today,
        'payment_type' => 'paid',
        'consultation_fee' => 500.00,
        'charged_amount' => 500.00,
        'discount_amount' => 0.00,
        'doctor_share_percentage' => 70.00,
        'hospital_share_percentage' => 30.00,
        'doctor_share_amount' => 350.00,
        'hospital_share_amount' => 150.00,
        'status' => 'in_consultation',
    ]);
} else {
    $token->update([
        'patient_id' => $patient->id,
        'doctor_id' => $doctor->id,
        'consultation_fee' => 500.00,
        'charged_amount' => 500.00,
        'doctor_share_percentage' => 70.00,
        'hospital_share_percentage' => 30.00,
        'doctor_share_amount' => 350.00,
        'hospital_share_amount' => 150.00,
        'status' => 'in_consultation',
    ]);
}

$token->load(['patient', 'doctor']);

// 4. Render the Blade View
$html = view('admin.patient-tokens.print', ['token' => $token])->render();

// Check Account Profile settings
$profile = account_profile();
$expectedHospitalName = $profile->account_name ?: config('app.name');

// ASSERTION 1: Hospital Name & OPD TOKEN in header
assertReceiptTest(
    "TEST 1: Hospital Name and OPD TOKEN header present from settings",
    str_contains($html, $expectedHospitalName) && str_contains($html, 'OPD TOKEN'),
    "Header contains: '{$expectedHospitalName}' and 'OPD TOKEN'"
);

// ASSERTION 2: Token #809 prominent
assertReceiptTest(
    "TEST 2: Prominent Token #809 displayed",
    str_contains($html, '#809') && str_contains($html, 'TOKEN NO.'),
    "HTML contains TOKEN NO. and #809"
);

// ASSERTION 3: Patient Information (Patient, Patient ID, Doctor, Date)
assertReceiptTest(
    "TEST 3: Patient Name and ID displayed correctly",
    str_contains($html, 'Patient Snapshot A') && str_contains($html, 'PT-00013'),
    "Patient: Patient Snapshot A, ID: PT-00013"
);

assertReceiptTest(
    "TEST 4: Doctor Name and Date displayed correctly",
    str_contains($html, 'Dr. Snapshot Test') && str_contains($html, '30 Sep 2026'),
    "Doctor: Dr. Snapshot Test, Date: 30 Sep 2026"
);

// ASSERTION 5: Fee is Consultation Fee PKR 500 ONLY
assertReceiptTest(
    "TEST 5: Consultation Fee PKR 500 displayed",
    str_contains($html, 'Consultation Fee') && str_contains($html, 'PKR 500'),
    "Contains 'Consultation Fee' and 'PKR 500'"
);

// ASSERTION 6: Status IN CONSULTATION
assertReceiptTest(
    "TEST 6: Status IN CONSULTATION displayed dynamically",
    str_contains($html, 'STATUS') && str_contains($html, 'IN CONSULTATION'),
    "Status displays: IN CONSULTATION"
);

// ASSERTION 7: Footer messages
assertReceiptTest(
    "TEST 7: Thermal receipt footer text matches prompt",
    str_contains($html, 'Please wait for your token') && str_contains($html, 'Thank you for choosing'),
    "Footer text present"
);

// ASSERTION 8: Thermal Printer CSS is present
assertReceiptTest(
    "TEST 8: Thermal 80mm print CSS rules present",
    str_contains($html, '@media print') && str_contains($html, '80mm'),
    "Contains @media print and 80mm styling"
);

// ASSERTION 9: ABSOLUTELY NO DOCTOR/HOSPITAL REVENUE SPLIT ON RECEIPT
$forbiddenTermsGlobal = [
    'Doctor Share',
    'Hospital Share',
    'Doctor %',
    'Hospital %',
    'Doctor Amount',
    'Hospital Amount',
    'doctor_share',
    'hospital_share',
];

$foundForbidden = [];
foreach ($forbiddenTermsGlobal as $term) {
    if (stripos($html, $term) !== false) {
        $foundForbidden[] = $term;
    }
}

// Also check ticket body text specifically for share amounts/percentages
preg_match('/<div class="ticket">([\s\S]*?)<\/div>\s*<script>/', $html, $ticketMatches);
$ticketText = isset($ticketMatches[1]) ? strip_tags($ticketMatches[1]) : '';

$forbiddenInTicketText = ['70%', '30%', '350', '150', 'share', 'Share'];
foreach ($forbiddenInTicketText as $term) {
    if (stripos($ticketText, $term) !== false) {
        $foundForbidden[] = "ticket text contains '$term'";
    }
}

assertReceiptTest(
    "TEST 9: Zero revenue share data on printed receipt (Doctor Share %, Hospital Share %, Amounts)",
    empty($foundForbidden),
    empty($foundForbidden) ? "No revenue split terms found anywhere" : "FORBIDDEN TERMS FOUND: " . implode(', ', $foundForbidden)
);

// ASSERTION 10: Backend token still retains internal revenue share snapshot
$token->refresh();
assertReceiptTest(
    "TEST 10: Backend PatientToken model still retains internal revenue share data",
    $token->doctor_share_percentage == 70.00 &&
    $token->hospital_share_percentage == 30.00 &&
    $token->doctor_share_amount == 350.00 &&
    $token->hospital_share_amount == 150.00,
    "Token retain internal split: Doc %: {$token->doctor_share_percentage}%, Hosp %: {$token->hospital_share_percentage}%, Doc Share: {$token->doctor_share_amount}, Hosp Share: {$token->hospital_share_amount}"
);

echo "\n=================================================================\n";
echo "RECEIPT TESTS SUMMARY: $passCount PASSED, $failCount FAILED\n";
echo "=================================================================\n\n";

echo "--- VISUAL PRINT PREVIEW (ASCII REPRESENTATION) ---\n";
echo "--------------------------------\n";
if ($profile->hasLogo()) {
    echo "         [HOSPITAL LOGO]\n";
}
echo "       " . strtoupper($expectedHospitalName) . "\n";
echo "          OPD TOKEN\n";
echo "--------------------------------\n";
echo "TOKEN NO.\n";
echo "#" . ($token->token_number ?: $token->formatted_token_number) . "\n";
echo "--------------------------------\n";
printf("%-12s %s\n", "Patient", $token->patient->name);
printf("%-12s %s\n", "Patient ID", $token->patient->patient_number);
printf("%-12s %s\n", "Doctor", $token->doctor->name);
printf("%-12s %s\n", "Date", \Carbon\Carbon::parse($token->token_date)->format('d M Y'));
printf("%-12s %s\n", "Time", $token->created_at ? $token->created_at->format('h:i A') : now()->format('h:i A'));
echo "--------------------------------\n";
echo "Consultation Fee\n";
echo "PKR " . number_format($token->consultation_fee, 0) . "\n";
echo "--------------------------------\n";
echo "STATUS\n";
echo "IN CONSULTATION\n";
echo "--------------------------------\n";
echo "Please wait for your token\nto be called.\n\n";
echo "Thank you for choosing\nour healthcare services.\n";
echo "--------------------------------\n";

if ($failCount > 0) {
    exit(1);
}

