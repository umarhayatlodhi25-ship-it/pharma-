<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\DoctorSettlement;
use App\Models\HospitalBill;
use App\Models\HospitalBillPayment;
use App\Models\HospitalService;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class HospitalBillingService
{
    /**
     * Create a hospital bill and optionally process the initial collected payment,
     * calculate server-side revenue split, and post to doctor ledger atomically.
     *
     * @param array $data
     * @return HospitalBill
     * @throws InvalidArgumentException
     */
    public function createBill(array $data): HospitalBill
    {
        return DB::transaction(function () use ($data) {
            $patientId = $data['patient_id'];
            $doctorId = $data['doctor_id'] ?? null;
            $serviceId = $data['hospital_service_id'] ?? null;
            $tokenId = $data['patient_token_id'] ?? null;
            $billDate = $data['bill_date'] ?? now()->toDateString();
            $paymentMethod = $data['payment_method'] ?? 'cash';
            $paymentType = strtolower($data['payment_type'] ?? 'paid'); // 'paid', 'free', 'partial', 'pending'
            $notes = $data['notes'] ?? null;
            $freeReason = $data['free_reason'] ?? null;
            $userId = $data['created_by'] ?? auth()->id();

            // Load service & doctor
            $service = $serviceId ? HospitalService::find($serviceId) : null;
            $doctor = $doctorId ? Doctor::find($doctorId) : null;

            // Determine if consultation or hospital procedure
            $isConsultation = (!$service || $service->service_type === 'consultation' || in_array($service->code, ['SRV-CONSULT', 'DOC_CONSULT']));

            // Determine gross fee
            if (isset($data['total_amount']) && is_numeric($data['total_amount'])) {
                $grossAmount = round(floatval($data['total_amount']), 2);
            } elseif ($isConsultation && $doctor) {
                $grossAmount = (float) $doctor->consultation_fee;
            } elseif ($service) {
                $grossAmount = (float) $service->default_fee;
            } elseif ($doctor) {
                $grossAmount = (float) $doctor->consultation_fee;
            } else {
                $grossAmount = 0.00;
            }

            // Determine Revenue Split Percentages
            if (isset($data['doctor_share_percentage']) && is_numeric($data['doctor_share_percentage'])) {
                $doctorPct = round(floatval($data['doctor_share_percentage']), 2);
            } elseif ($isConsultation && $doctor) {
                $doctorPct = (float) ($doctor->doctor_share_percentage ?? 70.00);
            } elseif ($service) {
                $doctorPct = (float) $service->doctor_share_percentage;
            } else {
                $doctorPct = 70.00; // Default consultation split
            }

            // Enforce doctor + hospital = 100%
            $doctorPct = max(0.00, min(100.00, $doctorPct));
            $hospitalPct = round(100.00 - $doctorPct, 2);

            // Handle Payment Status and Amounts
            if ($paymentType === 'free') {
                $discountAmount = $grossAmount;
                $netAmount = 0.00;
                $paidAmount = 0.00;
                $dueAmount = 0.00;
                $finalStatus = 'free';
                $doctorShare = 0.00;
                $hospitalShare = 0.00;
            } elseif ($paymentType === 'partial') {
                $discountAmount = round(floatval($data['discount_amount'] ?? 0.00), 2);
                $netAmount = max(0.00, round($grossAmount - $discountAmount, 2));
                $paidAmount = round(floatval($data['paid_amount'] ?? 0.00), 2);

                if ($paidAmount > $netAmount) {
                    $paidAmount = $netAmount;
                }

                $dueAmount = max(0.00, round($netAmount - $paidAmount, 2));
                $finalStatus = ($dueAmount <= 0.00 && $netAmount > 0.00) ? 'paid' : 'partial';

                // Revenue split recognized ONLY on the amount actually collected
                $doctorShare = round($paidAmount * ($doctorPct / 100), 2);
                $hospitalShare = round($paidAmount - $doctorShare, 2);
            } elseif ($paymentType === 'pending') {
                $discountAmount = round(floatval($data['discount_amount'] ?? 0.00), 2);
                $netAmount = max(0.00, round($grossAmount - $discountAmount, 2));
                $paidAmount = 0.00;
                $dueAmount = $netAmount;
                $finalStatus = 'pending';
                $doctorShare = 0.00;
                $hospitalShare = 0.00;
            } else {
                // Paid in full
                $discountAmount = round(floatval($data['discount_amount'] ?? 0.00), 2);
                $netAmount = max(0.00, round($grossAmount - $discountAmount, 2));
                $paidAmount = $netAmount;
                $dueAmount = 0.00;
                $finalStatus = 'paid';

                $doctorShare = round($paidAmount * ($doctorPct / 100), 2);
                $hospitalShare = round($paidAmount - $doctorShare, 2);
            }

            // Create Hospital Bill
            $bill = HospitalBill::create([
                'patient_id'                => $patientId,
                'doctor_id'                 => $doctorId,
                'hospital_service_id'       => $serviceId,
                'patient_token_id'          => $tokenId,
                'bill_date'                 => $billDate,
                'total_amount'              => $grossAmount,
                'discount_amount'           => $discountAmount,
                'net_amount'                => $netAmount,
                'paid_amount'               => $paidAmount,
                'due_amount'                => $dueAmount,
                'doctor_share_percentage'   => $doctorPct,
                'hospital_share_percentage' => $hospitalPct,
                'doctor_share'              => $doctorShare,
                'hospital_share'            => $hospitalShare,
                'payment_method'            => $paymentMethod,
                'payment_status'            => $finalStatus,
                'free_reason'               => $freeReason,
                'notes'                     => $notes,
                'created_by'                => $userId,
            ]);

            // If initial collected payment > 0, create payment receipt and doctor ledger entry
            if ($paidAmount > 0) {
                $payment = HospitalBillPayment::create([
                    'hospital_bill_id' => $bill->id,
                    'payment_number'   => 'RCPT-' . $bill->bill_number . '-1',
                    'payment_date'     => $billDate,
                    'amount'           => $paidAmount,
                    'doctor_share'     => $doctorShare,
                    'hospital_share'   => $hospitalShare,
                    'payment_method'   => $paymentMethod,
                    'reference_note'   => 'Initial collected payment on bill generation',
                    'received_by'      => $userId,
                ]);

                // Credit Doctor Ledger if doctor share > 0
                if ($doctorId && $doctorShare > 0) {
                    $patient = Patient::find($patientId);
                    $patientName = $patient ? $patient->name : 'Patient';

                    $currPayable = $doctor ? (float) $doctor->current_payable : 0.00;
                    $balanceAfter = round($currPayable + $doctorShare, 2);

                    DoctorLedger::create([
                        'doctor_id'                => $doctorId,
                        'hospital_bill_id'         => $bill->id,
                        'hospital_bill_payment_id' => $payment->id,
                        'entry_date'               => $billDate,
                        'transaction_type'         => 'credit',
                        'amount'                   => $doctorShare,
                        'balance_after'            => $balanceAfter,
                        'description'              => "Doctor share earned from Bill #{$bill->bill_number} (Patient: {$patientName})",
                        'created_by'               => $userId,
                    ]);
                }
            }

            return $bill;
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
}
