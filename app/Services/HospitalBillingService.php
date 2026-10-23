<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\DoctorSettlement;
use App\Models\HospitalAccountLedger;
use App\Models\HospitalBill;
use App\Models\HospitalBillItem;
use App\Models\HospitalBillPayment;
use App\Models\HospitalBillPaymentAllocation;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\PatientToken;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HospitalBillingService
{
    /**
     * Create a hospital bill supporting:
     * - Mode A: OPD Patient Billing (linked to token & patient, separate consultation outstanding)
     * - Mode B: Direct Service Billing (walk-in/private patient, no token/doctor required)
     * - Multi-service items table
     * - Accurate previous outstanding settlement and payment allocation
     * - Atomic hospital account ledger and doctor ledger posting
     *
     * @param array $data
     * @return HospitalBill
     * @throws InvalidArgumentException
     */
    public function createBill(array $data): HospitalBill
    {
        return DB::transaction(function () use ($data) {
            $patientId = $data['patient_id'];
            $patient = Patient::findOrFail($patientId);
            $doctorId = $data['doctor_id'] ?? null;
            $serviceId = $data['hospital_service_id'] ?? null;
            $tokenId = $data['patient_token_id'] ?? null;
            $billDate = $data['bill_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paymentType = strtolower($data['payment_type'] ?? 'paid'); // 'paid', 'free', 'partial', 'pending'
            $notes = $data['notes'] ?? null;
            $freeReason = $data['free_reason'] ?? null;
            $userId = $data['created_by'] ?? auth()->id();
            $billingType = $data['billing_type'] ?? ($tokenId ? 'opd' : 'direct');
            $includePreviousBalance = !empty($data['include_previous_balance']);

            // Validate token ownership if token provided
            $token = null;
            if ($tokenId) {
                $token = PatientToken::where('id', $tokenId)->where('patient_id', $patientId)->first();
                if (!$token) {
                    throw new InvalidArgumentException("Selected OPD token does not belong to this patient.");
                }
                if (!$doctorId && $token->doctor_id) {
                    $doctorId = $token->doctor_id;
                }
            }

            // Load doctor
            $doctor = $doctorId ? Doctor::find($doctorId) : null;

            // 1. Resolve Items & Gross Fee
            $itemsData = $data['items'] ?? [];
            $computedGross = 0.00;
            $computedDiscount = 0.00;
            $normalizedItems = [];

            if (!empty($itemsData) && is_array($itemsData)) {
                foreach ($itemsData as $row) {
                    $sId = !empty($row['hospital_service_id']) ? intval($row['hospital_service_id']) : null;
                    $sModel = $sId ? HospitalService::find($sId) : null;
                    $sName = $row['service_name'] ?? ($sModel ? $sModel->name : 'Hospital Service');
                    $sCode = $row['service_code'] ?? ($sModel ? $sModel->code : null);
                    $qty = max(0.01, round(floatval($row['quantity'] ?? 1), 2));
                    $unitPrice = max(0.00, round(floatval($row['unit_price'] ?? ($sModel ? $sModel->default_fee : 0)), 2));
                    $subtotal = round($qty * $unitPrice, 2);
                    $itemDiscount = max(0.00, round(floatval($row['discount_amount'] ?? 0), 2));
                    if ($itemDiscount > $subtotal) {
                        $itemDiscount = $subtotal;
                    }
                    $lineTotal = max(0.00, round($subtotal - $itemDiscount, 2));

                    $computedGross += $subtotal;
                    $computedDiscount += $itemDiscount;

                    $normalizedItems[] = [
                        'hospital_service_id' => $sId,
                        'service_name'        => $sName,
                        'service_code'        => $sCode,
                        'quantity'            => $qty,
                        'unit_price'          => $unitPrice,
                        'subtotal'            => $subtotal,
                        'discount_amount'     => $itemDiscount,
                        'total_price'         => $lineTotal,
                        'notes'               => $row['notes'] ?? null,
                    ];
                }
            } elseif ($serviceId) {
                // Fallback to single service
                $service = HospitalService::find($serviceId);
                $unitPrice = isset($data['total_amount']) && is_numeric($data['total_amount'])
                    ? round(floatval($data['total_amount']), 2)
                    : ($service ? (float)$service->default_fee : 0.00);
                $subtotal = $unitPrice;
                $computedGross = $subtotal;
                $normalizedItems[] = [
                    'hospital_service_id' => $serviceId,
                    'service_name'        => $service ? $service->name : 'Hospital Service',
                    'service_code'        => $service ? $service->code : null,
                    'quantity'            => 1.00,
                    'unit_price'          => $unitPrice,
                    'subtotal'            => $subtotal,
                    'discount_amount'     => 0.00,
                    'total_price'         => $subtotal,
                    'notes'               => null,
                ];
            } else {
                $subtotal = isset($data['total_amount']) && is_numeric($data['total_amount'])
                    ? round(floatval($data['total_amount']), 2)
                    : 0.00;
                $computedGross = $subtotal;
                if ($computedGross > 0) {
                    $normalizedItems[] = [
                        'hospital_service_id' => null,
                        'service_name'        => 'Hospital Service',
                        'service_code'        => null,
                        'quantity'            => 1.00,
                        'unit_price'          => $computedGross,
                        'subtotal'            => $computedGross,
                        'discount_amount'     => 0.00,
                        'total_price'         => $computedGross,
                        'notes'               => null,
                    ];
                }
            }

            // Top-level discount adjustment
            if (isset($data['discount_amount']) && is_numeric($data['discount_amount']) && floatval($data['discount_amount']) > 0) {
                $extraDiscount = round(floatval($data['discount_amount']), 2);
                if ($extraDiscount > $computedDiscount) {
                    $computedDiscount = min($computedGross, $extraDiscount);
                }
            }

            $grossAmount = $computedGross;

            // 2. Determine Previous Balance
            $unpaidBills = HospitalBill::where('patient_id', $patientId)
                ->where('due_amount', '>', 0)
                ->orderBy('bill_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();
            $eligiblePreviousOutstanding = round((float) $unpaidBills->sum('due_amount'), 2);

            $previousDueRecorded = $includePreviousBalance ? $eligiblePreviousOutstanding : 0.00;

            // 3. Revenue Split Determination
            // Rule: Hospital service bills belong 100% to hospital, unless doctor procedure share is configured
            if ($billingType === 'direct') {
                $doctorPct = 0.00;
                $hospitalPct = 100.00;
            } elseif (isset($data['doctor_share_percentage']) && is_numeric($data['doctor_share_percentage'])) {
                $doctorPct = max(0.00, min(100.00, round(floatval($data['doctor_share_percentage']), 2)));
                $hospitalPct = round(100.00 - $doctorPct, 2);
            } else {
                // If this is a procedure bill linked to an OPD visit, hospital services do NOT split doctor fee
                $doctorPct = 0.00;
                $hospitalPct = 100.00;
            }

            // 4. Calculate Net, Paid, and Due Amounts
            if ($paymentType === 'free') {
                $discountAmount = $grossAmount;
                $currentNet = 0.00;
                $paidForCurrentBill = 0.00;
                $dueForCurrentBill = 0.00;
                $finalStatus = 'free';
            } elseif ($paymentType === 'pending') {
                $discountAmount = $computedDiscount;
                $currentNet = max(0.00, round($grossAmount - $discountAmount, 2));
                $paidForCurrentBill = 0.00;
                $dueForCurrentBill = $currentNet;
                $finalStatus = 'pending';
            } else {
                // paid in full or partial
                $discountAmount = $computedDiscount;
                $currentNet = max(0.00, round($grossAmount - $discountAmount, 2));

                $totalContextDue = round($currentNet + $previousDueRecorded, 2);

                if ($paymentType === 'partial') {
                    $rawPaidInput = isset($data['paid_amount']) ? round(max(0.0, floatval($data['paid_amount'])), 2) : 0.00;
                    if ($rawPaidInput > $totalContextDue) {
                        throw new InvalidArgumentException("Payment amount (PKR {$rawPaidInput}) cannot exceed total amount due (PKR {$totalContextDue}).");
                    }
                    $totalPaymentReceived = $rawPaidInput;
                } else {
                    // Paid in full
                    $totalPaymentReceived = $totalContextDue;
                }

                // If previous balance is explicitly included, allocate payment FIFO to oldest unpaid bills first
                $remainingPayment = $totalPaymentReceived;

                if ($includePreviousBalance && $remainingPayment > 0) {
                    foreach ($unpaidBills as $oldBill) {
                        if ($remainingPayment <= 0.00) break;
                        $dueOnThis = (float) $oldBill->due_amount;
                        $payForThis = min($remainingPayment, $dueOnThis);
                        if ($payForThis > 0.00) {
                            $oldPayment = $oldBill->recordPayment(
                                $payForThis,
                                $paymentMethod,
                                "Settled via Carry-forward payment during Bill generation",
                                $userId,
                                'previous_balance'
                            );
                            $remainingPayment = round($remainingPayment - $payForThis, 2);
                        }
                    }
                }

                $paidForCurrentBill = min($currentNet, $remainingPayment);
                $dueForCurrentBill = max(0.00, round($currentNet - $paidForCurrentBill, 2));
                $finalStatus = ($dueForCurrentBill <= 0.00 && $currentNet > 0.00) ? 'paid' : ($paidForCurrentBill > 0.00 ? 'partial' : 'pending');
            }

            // Recognized splits on collected amount
            $docShareCurrent = round($paidForCurrentBill * ($doctorPct / 100), 2);
            $hospShareCurrent = round($paidForCurrentBill - $docShareCurrent, 2);

            // Primary service ID for legacy reference
            $primaryServiceId = $serviceId ?? ($normalizedItems[0]['hospital_service_id'] ?? null);

            // 5. Create Hospital Bill
            $bill = HospitalBill::create([
                'bill_number'               => HospitalBill::generateBillNumber(),
                'billing_type'              => $billingType,
                'patient_id'                => $patientId,
                'doctor_id'                 => $doctorId,
                'hospital_service_id'       => $primaryServiceId,
                'patient_token_id'          => $tokenId,
                'bill_date'                 => $billDate,
                'total_amount'              => $grossAmount,
                'discount_amount'           => $discountAmount,
                'net_amount'                => $currentNet,
                'paid_amount'               => $paidForCurrentBill,
                'due_amount'                => $dueForCurrentBill,
                'doctor_share_percentage'   => $doctorPct,
                'hospital_share_percentage' => $hospitalPct,
                'doctor_share'              => $docShareCurrent,
                'hospital_share'            => $hospShareCurrent,
                'payment_method'            => $paymentMethod,
                'payment_status'            => $finalStatus,
                'free_reason'               => $freeReason,
                'notes'                     => $notes,
                'previous_due'              => $previousDueRecorded,
                'created_by'                => $userId,
            ]);

            // 6. Create Bill Items
            foreach ($normalizedItems as $item) {
                HospitalBillItem::create([
                    'hospital_bill_id'    => $bill->id,
                    'hospital_service_id' => $item['hospital_service_id'],
                    'service_name'        => $item['service_name'],
                    'service_code'        => $item['service_code'],
                    'quantity'            => $item['quantity'],
                    'unit_price'          => $item['unit_price'],
                    'subtotal'            => $item['subtotal'],
                    'discount_amount'     => $item['discount_amount'],
                    'total_price'         => $item['total_price'],
                    'notes'               => $item['notes'],
                ]);
            }

            // 7. Post Receivable to Hospital Account Ledger
            HospitalAccountLedger::create([
                'entry_date'               => $billDate,
                'patient_id'               => $patientId,
                'hospital_bill_id'         => $bill->id,
                'hospital_bill_payment_id' => null,
                'patient_token_id'         => $tokenId,
                'transaction_type'         => 'receivable',
                'description'              => "Bill #{$bill->bill_number} generated ({$billingType}) for {$patient->name}",
                'gross_amount'             => $grossAmount,
                'discount_amount'          => $discountAmount,
                'amount_received'          => 0.00,
                'outstanding_balance'      => $dueForCurrentBill,
                'payment_method'           => $paymentMethod,
                'status'                   => 'posted',
                'created_by'               => $userId,
            ]);

            // 8. If collected payment on current bill > 0, create payment, allocation & ledger entries
            if ($paidForCurrentBill > 0.00) {
                $payment = HospitalBillPayment::create([
                    'hospital_bill_id' => $bill->id,
                    'patient_id'       => $patientId,
                    'payment_number'   => 'RCPT-' . $bill->bill_number . '-1',
                    'payment_date'     => $billDate,
                    'amount'           => $paidForCurrentBill,
                    'doctor_share'     => $docShareCurrent,
                    'hospital_share'   => $hospShareCurrent,
                    'payment_method'   => $paymentMethod,
                    'reference_note'   => "Initial collected payment on Bill #{$bill->bill_number}",
                    'received_by'      => $userId,
                ]);

                HospitalBillPaymentAllocation::firstOrCreate(
                    [
                        'hospital_bill_payment_id' => $payment->id,
                        'hospital_bill_id'         => $bill->id,
                    ],
                    [
                        'allocated_amount'         => $paidForCurrentBill,
                        'allocation_type'          => 'current_bill',
                    ]
                );

                // Hospital Account Ledger Collection Entry (Idempotent)
                if (!HospitalAccountLedger::where('hospital_bill_payment_id', $payment->id)->exists()) {
                    HospitalAccountLedger::create([
                        'entry_date'               => $billDate,
                        'patient_id'               => $patientId,
                        'hospital_bill_id'         => $bill->id,
                        'hospital_bill_payment_id' => $payment->id,
                        'patient_token_id'         => $tokenId,
                        'transaction_type'         => 'collection',
                        'description'              => "Payment collected on Bill #{$bill->bill_number} (Patient: {$patient->name})",
                        'gross_amount'             => $grossAmount,
                        'discount_amount'          => $discountAmount,
                        'amount_received'          => $paidForCurrentBill,
                        'outstanding_balance'      => $dueForCurrentBill,
                        'payment_method'           => $paymentMethod,
                        'status'                   => 'posted',
                        'created_by'               => $userId,
                    ]);
                }

                // Credit Doctor Ledger ONLY if doctor is assigned and earned a share (Idempotent)
                if ($doctorId && $docShareCurrent > 0.00) {
                    if (!DoctorLedger::where('hospital_bill_payment_id', $payment->id)->exists()) {
                        $currPayable = $doctor ? (float) $doctor->current_payable : 0.00;
                        $balanceAfter = round($currPayable + $docShareCurrent, 2);

                        DoctorLedger::create([
                            'doctor_id'                => $doctorId,
                            'hospital_bill_id'         => $bill->id,
                            'hospital_bill_payment_id' => $payment->id,
                            'entry_date'               => $billDate,
                            'transaction_type'         => 'credit',
                            'amount'                   => $docShareCurrent,
                            'balance_after'            => $balanceAfter,
                            'description'              => "Doctor share earned from Bill #{$bill->bill_number} (Patient: {$patient->name})",
                            'created_by'               => $userId,
                        ]);
                    }
                }
            }

            return $bill;
        });
    }

    /**
     * Generate an OPD token with comprehensive Partial Payment, Patient Pending Balance Carry-Forward,
     * and FIFO payment allocation across historical bills.
     *
     * @param array $data
     * @return PatientToken
     * @throws InvalidArgumentException
     */
    public function generateOpdTokenWithBilling(array $data): PatientToken
    {
        return DB::transaction(function () use ($data) {
            $patientId = $data['patient_id'];
            $doctorId = $data['doctor_id'];
            $serviceId = $data['hospital_service_id'] ?? null;
            $feeType = strtolower($data['fee_type'] ?? $data['payment_type'] ?? 'paid'); // 'paid', 'partial', 'free'
            $today = $data['token_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $notes = $data['notes'] ?? null;
            $freeReason = $data['free_reason'] ?? null;
            $otherReason = $data['other_reason'] ?? null;
            $userId = $data['created_by'] ?? auth()->id();

            $patient = Patient::findOrFail($patientId);
            $doctor = Doctor::findOrFail($doctorId);
            $service = $serviceId ? HospitalService::find($serviceId) : null;

            // Validate doctor consultation fee
            if ($doctor->consultation_fee === null || !is_numeric($doctor->consultation_fee) || (float) $doctor->consultation_fee < 0) {
                throw new InvalidArgumentException('The selected doctor does not have a configured consultation fee.');
            }

            // 1. Calculate Previous Unpaid Balance (FIFO order: oldest bills first)
            $unpaidBills = HospitalBill::where('patient_id', $patientId)
                ->where('due_amount', '>', 0)
                ->orderBy('bill_date', 'asc')
                ->orderBy('id', 'asc')
                ->lockForUpdate()
                ->get();
            $previousPendingBalance = round((float) $unpaidBills->sum('due_amount'), 2);

            // 2. Calculate Gross Service Fee for Today
            $doctorConsultationFee = (float) $doctor->consultation_fee;
            $doctorConsultSharePct = (float) ($doctor->doctor_share_percentage ?? 70.00);

            $isAdditionalService = ($service && $service->service_type !== 'consultation' && !in_array($service->code, ['SRV-CONSULT', 'DOC_CONSULT']));
            if ($isAdditionalService) {
                $serviceFee = (float) $service->default_fee;
                $serviceDoctorSharePct = (float) $service->doctor_share_percentage;
            } else {
                $serviceFee = 0.00;
                $serviceDoctorSharePct = 0.00;
            }

            $totalGrossFee = round($doctorConsultationFee + $serviceFee, 2);

            $doctorShareFromConsultation = round($doctorConsultationFee * ($doctorConsultSharePct / 100), 2);
            $doctorShareFromService = $isAdditionalService ? round($serviceFee * ($serviceDoctorSharePct / 100), 2) : 0.00;
            $combinedDoctorShare = round($doctorShareFromConsultation + $doctorShareFromService, 2);

            $effectiveDoctorSharePct = ($totalGrossFee > 0) ? round(($combinedDoctorShare / $totalGrossFee) * 100, 2) : $doctorConsultSharePct;
            $effectiveHospitalSharePct = round(100.00 - $effectiveDoctorSharePct, 2);

            // 3. Determine Payment Amounts
            $totalAmountDue = round($totalGrossFee + $previousPendingBalance, 2);

            if ($feeType === 'free') {
                $discountAmount = $totalGrossFee;
                $currentNet = 0.00;
                $amountReceivedToday = isset($data['paid_amount']) ? round(max(0.0, floatval($data['paid_amount'])), 2) : 0.00;
                if ($amountReceivedToday > $previousPendingBalance) {
                    $amountReceivedToday = $previousPendingBalance;
                }
            } elseif ($feeType === 'partial') {
                $discountAmount = 0.00;
                $currentNet = $totalGrossFee;
                $amountReceivedToday = isset($data['paid_amount']) ? round(max(0.0, floatval($data['paid_amount'])), 2) : 0.00;
                if ($amountReceivedToday > $totalAmountDue) {
                    throw new InvalidArgumentException("Payment amount (PKR {$amountReceivedToday}) cannot exceed total outstanding amount (PKR {$totalAmountDue}).");
                }
            } else { // paid in full
                $discountAmount = 0.00;
                $currentNet = $totalGrossFee;
                $amountReceivedToday = $totalAmountDue;
            }

            // 4. Allocate payment FIFO: Oldest unpaid bills first
            $remainingPayment = $amountReceivedToday;

            foreach ($unpaidBills as $oldBill) {
                if ($remainingPayment <= 0.00) {
                    break;
                }
                $dueOnThis = (float) $oldBill->due_amount;
                $payForThis = min($remainingPayment, $dueOnThis);
                if ($payForThis > 0.00) {
                    $oldPmt = $oldBill->recordPayment(
                        $payForThis,
                        $paymentMethod,
                        "Carry-forward payment settled during Token generation (Patient: {$patient->name})",
                        $userId,
                        'previous_balance'
                    );
                    $remainingPayment = round($remainingPayment - $payForThis, 2);
                }
            }

            // 5. Remaining payment applies to TODAY's new bill
            $paidForCurrentBill = min($currentNet, $remainingPayment);
            $dueForCurrentBill = max(0.00, round($currentNet - $paidForCurrentBill, 2));

            if ($feeType === 'free') {
                $currentBillStatus = 'free';
                $docShareCurrent = 0.00;
                $hospShareCurrent = 0.00;
            } elseif ($dueForCurrentBill <= 0.00 && $currentNet > 0.00) {
                $currentBillStatus = 'paid';
                $docShareCurrent = round($paidForCurrentBill * ($effectiveDoctorSharePct / 100), 2);
                $hospShareCurrent = round($paidForCurrentBill - $docShareCurrent, 2);
            } elseif ($paidForCurrentBill > 0.00 || $feeType === 'partial') {
                $currentBillStatus = 'partial';
                $docShareCurrent = round($paidForCurrentBill * ($effectiveDoctorSharePct / 100), 2);
                $hospShareCurrent = round($paidForCurrentBill - $docShareCurrent, 2);
            } else {
                $currentBillStatus = 'pending';
                $docShareCurrent = 0.00;
                $hospShareCurrent = 0.00;
            }

            // Compute overall remaining balance of patient
            $totalBalanceRemaining = max(0.00, round($totalAmountDue - $amountReceivedToday, 2));

            // Determine token payment_type
            if ($feeType === 'free') {
                $tokenPaymentType = 'free';
            } elseif ($totalBalanceRemaining <= 0.00) {
                $tokenPaymentType = 'paid';
            } else {
                $tokenPaymentType = 'partial';
            }

            // 6. Generate sequential token number for today
            $nextTokenNumber = PatientToken::generateNextTokenNumber($today, true);

            $defaultStatus = 'waiting';
            $profile = \App\Models\AccountProfile::current();
            if ($profile && !empty($profile->default_token_status)) {
                $cfgStatus = strtolower($profile->default_token_status);
                if (in_array($cfgStatus, ['waiting', 'called', 'completed'])) {
                    $defaultStatus = $cfgStatus;
                }
            }

            // 7. Create Token
            $token = PatientToken::create([
                'patient_id'                => $patientId,
                'doctor_id'                 => $doctor->id,
                'hospital_service_id'       => $service ? $service->id : null,
                'token_number'              => $nextTokenNumber,
                'token_date'                => $today,
                'payment_type'              => $tokenPaymentType,
                'consultation_fee'          => $totalGrossFee,
                'charged_amount'            => $paidForCurrentBill,
                'discount_amount'           => $discountAmount,
                'doctor_share_percentage'   => $effectiveDoctorSharePct,
                'hospital_share_percentage' => $effectiveHospitalSharePct,
                'doctor_share_amount'       => $docShareCurrent,
                'hospital_share_amount'     => $hospShareCurrent,
                'free_reason'               => ($feeType === 'free') ? $freeReason : null,
                'other_reason'              => ($feeType === 'free' && $freeReason === 'Other') ? $otherReason : null,
                'status'                    => $defaultStatus,
                'notes'                     => $notes,
                'previous_due'              => $previousPendingBalance,
                'paid_today'                => $amountReceivedToday,
                'balance_due'               => $totalBalanceRemaining,
            ]);

            // 8. Create Hospital Bill
            $bill = HospitalBill::create([
                'bill_number'               => HospitalBill::generateBillNumber(),
                'billing_type'              => 'opd',
                'patient_id'                => $patientId,
                'doctor_id'                 => $doctor->id,
                'hospital_service_id'       => $service ? $service->id : null,
                'patient_token_id'          => $token->id,
                'bill_date'                 => $today,
                'total_amount'              => $totalGrossFee,
                'discount_amount'           => $discountAmount,
                'net_amount'                => $currentNet,
                'paid_amount'               => $paidForCurrentBill,
                'due_amount'                => $dueForCurrentBill,
                'doctor_share_percentage'   => $effectiveDoctorSharePct,
                'hospital_share_percentage' => $effectiveHospitalSharePct,
                'doctor_share'              => $docShareCurrent,
                'hospital_share'            => $hospShareCurrent,
                'payment_method'            => $paymentMethod,
                'payment_status'            => $currentBillStatus,
                'free_reason'               => ($feeType === 'free') ? $freeReason : null,
                'notes'                     => $notes,
                'created_by'                => $userId,
                'previous_due'              => $previousPendingBalance,
            ]);

            $token->update(['hospital_bill_id' => $bill->id]);

            // Create bill items for consultation and any procedure
            HospitalBillItem::create([
                'hospital_bill_id'    => $bill->id,
                'hospital_service_id' => null,
                'service_name'        => "OPD Consultation - {$doctor->name}",
                'service_code'        => 'DOC_CONSULT',
                'quantity'            => 1.00,
                'unit_price'          => $doctorConsultationFee,
                'subtotal'            => $doctorConsultationFee,
                'discount_amount'     => ($feeType === 'free') ? $doctorConsultationFee : 0.00,
                'total_price'         => ($feeType === 'free') ? 0.00 : $doctorConsultationFee,
                'notes'               => "Token #{$token->formatted_token_number}",
            ]);

            if ($isAdditionalService && $service) {
                HospitalBillItem::create([
                    'hospital_bill_id'    => $bill->id,
                    'hospital_service_id' => $service->id,
                    'service_name'        => $service->name,
                    'service_code'        => $service->code,
                    'quantity'            => 1.00,
                    'unit_price'          => $serviceFee,
                    'subtotal'            => $serviceFee,
                    'discount_amount'     => 0.00,
                    'total_price'         => $serviceFee,
                    'notes'               => "Prescribed with token",
                ]);
            }

            // Post Receivable to Hospital Account Ledger
            HospitalAccountLedger::create([
                'entry_date'               => $today,
                'patient_id'               => $patientId,
                'hospital_bill_id'         => $bill->id,
                'hospital_bill_payment_id' => null,
                'patient_token_id'         => $token->id,
                'transaction_type'         => 'receivable',
                'description'              => "OPD Token #{$token->formatted_token_number} generated for {$patient->name} (Dr. {$doctor->name})",
                'gross_amount'             => $totalGrossFee,
                'discount_amount'          => $discountAmount,
                'amount_received'          => 0.00,
                'outstanding_balance'      => $dueForCurrentBill,
                'payment_method'           => $paymentMethod,
                'status'                   => 'posted',
                'created_by'               => $userId,
            ]);

            // 9. If paid amount on current bill > 0, create HospitalBillPayment, Allocation & DoctorLedger entry
            if ($paidForCurrentBill > 0.00) {
                $payment = HospitalBillPayment::create([
                    'hospital_bill_id' => $bill->id,
                    'patient_id'       => $patientId,
                    'payment_number'   => 'RCPT-' . $bill->bill_number . '-1',
                    'payment_date'     => $today,
                    'amount'           => $paidForCurrentBill,
                    'doctor_share'     => $docShareCurrent,
                    'hospital_share'   => $hospShareCurrent,
                    'payment_method'   => $paymentMethod,
                    'reference_note'   => 'Initial collected payment on OPD token generation',
                    'received_by'      => $userId,
                ]);

                HospitalBillPaymentAllocation::firstOrCreate(
                    [
                        'hospital_bill_payment_id' => $payment->id,
                        'hospital_bill_id'         => $bill->id,
                    ],
                    [
                        'allocated_amount'         => $paidForCurrentBill,
                        'allocation_type'          => 'current_bill',
                    ]
                );

                if (!HospitalAccountLedger::where('hospital_bill_payment_id', $payment->id)->exists()) {
                    HospitalAccountLedger::create([
                        'entry_date'               => $today,
                        'patient_id'               => $patientId,
                        'hospital_bill_id'         => $bill->id,
                        'hospital_bill_payment_id' => $payment->id,
                        'patient_token_id'         => $token->id,
                        'transaction_type'         => 'collection',
                        'description'              => "Payment collected on Token #{$token->formatted_token_number} (Patient: {$patient->name})",
                        'gross_amount'             => $totalGrossFee,
                        'discount_amount'          => $discountAmount,
                        'amount_received'          => $paidForCurrentBill,
                        'outstanding_balance'      => $dueForCurrentBill,
                        'payment_method'           => $paymentMethod,
                        'status'                   => 'posted',
                        'created_by'               => $userId,
                    ]);
                }

                if ($doctor && $docShareCurrent > 0.00) {
                    if (!DoctorLedger::where('hospital_bill_payment_id', $payment->id)->exists()) {
                        $currPayable = (float) $doctor->current_payable;
                        $balanceAfter = round($currPayable + $docShareCurrent, 2);

                        DoctorLedger::create([
                            'doctor_id'                => $doctor->id,
                            'hospital_bill_id'         => $bill->id,
                            'hospital_bill_payment_id' => $payment->id,
                            'entry_date'               => $today,
                            'transaction_type'         => 'credit',
                            'amount'                   => $docShareCurrent,
                            'balance_after'            => $balanceAfter,
                            'description'              => "Doctor share earned from Bill #{$bill->bill_number} (Patient: {$patient->name})",
                            'created_by'               => $userId,
                        ]);
                    }
                }
            }

            return $token;
        });
    }

    /**
     * Settle payout to a doctor.
     *
     * @param Doctor $doctor
     * @param float $amount
     * @param string $paymentMethod
     * @param string|null $referenceNote
     * @param int|null $userId
     * @return DoctorSettlement
     * @throws InvalidArgumentException
     */
    public function settleDoctor(Doctor $doctor, float $amount, string $paymentMethod = 'cash', ?string $referenceNote = null, ?int $userId = null): DoctorSettlement
    {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Settlement payment amount must be greater than zero.');
        }

        $currentPayable = (float) $doctor->current_payable;
        if ($amount > $currentPayable) {
            throw new InvalidArgumentException("Payment amount (PKR {$amount}) cannot exceed outstanding payable balance (PKR {$currentPayable}).");
        }

        return DB::transaction(function () use ($doctor, $amount, $currentPayable, $paymentMethod, $referenceNote, $userId) {
            $remaining = round($currentPayable - $amount, 2);

            $settlement = DoctorSettlement::create([
                'doctor_id'         => $doctor->id,
                'settlement_number' => 'DS-' . date('Ymd') . '-' . str_pad((string) (DoctorSettlement::whereDate('created_at', now()->toDateString())->count() + 1), 4, '0', STR_PAD_LEFT),
                'settlement_date'   => now()->toDateString(),
                'previous_payable'  => $currentPayable,
                'paid_amount'       => $amount,
                'remaining_payable' => $remaining,
                'payment_method'    => $paymentMethod,
                'reference_note'    => $referenceNote,
                'created_by'        => $userId ?? auth()->id(),
            ]);

            DoctorLedger::create([
                'doctor_id'            => $doctor->id,
                'doctor_settlement_id' => $settlement->id,
                'entry_date'           => now()->toDateString(),
                'transaction_type'     => 'debit',
                'amount'               => $amount,
                'balance_after'        => $remaining,
                'description'          => "Settlement payment voucher #{$settlement->settlement_number} ({$paymentMethod})",
                'created_by'           => $userId ?? auth()->id(),
            ]);

            return $settlement;
        });
    }

    /**
     * Retrieve complete patient billing context:
     * - Patient profile
     * - Token details (if applicable)
     * - Distinct Consultation debt vs Hospital Service debt
     * - Existing hospital service bills history
     */
    public function getPatientBillingContext(int $patientId, ?int $tokenId = null): array
    {
        $patient = Patient::with(['tokens' => function ($q) {
            $q->latest('token_date')->latest('id')->take(5);
        }])->findOrFail($patientId);

        $selectedToken = null;
        if ($tokenId) {
            $selectedToken = PatientToken::with(['doctor', 'service', 'hospitalBill'])->where('id', $tokenId)->where('patient_id', $patientId)->first();
        }

        $consultationDebt = $patient->consultation_outstanding;
        $hospitalServiceDebt = $patient->hospital_service_outstanding;
        $totalDebt = $patient->pending_balance;

        $existingBills = HospitalBill::with(['items', 'doctor', 'service', 'payments'])
            ->where('patient_id', $patientId)
            ->latest('bill_date')
            ->latest('id')
            ->take(10)
            ->get();

        return [
            'patient'                     => $patient,
            'token'                       => $selectedToken,
            'consultation_outstanding'    => $consultationDebt,
            'hospital_service_outstanding'=> $hospitalServiceDebt,
            'total_outstanding'           => $totalDebt,
            'existing_bills'              => $existingBills,
        ];
    }
}
