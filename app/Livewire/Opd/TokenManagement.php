<?php

namespace App\Livewire\Opd;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class TokenManagement extends Component
{
    use WithPagination;

    // Doctor Selection
    public $doctor_id = '';
    public $doctor_search = '';

    // Patient Selection Mode ('existing' or 'new')
    public $patient_mode = 'existing';

    // Existing Patient Search & Selection
    public $patient_search = '';
    public $selected_patient_id = '';
    public $has_active_token_warning = false;
    public $active_token_number = '';
    public $active_token_status = '';

    // New Patient Form Fields
    public $new_name = '';
    public $new_father_husband_name = '';
    public $new_age = '';
    public $new_gender = 'Male';
    public $new_phone = '';
    public $new_address = '';
    public $new_cnic = '';
    public $duplicate_patient_warning = null;

    // Fee & Payment
    public $hospital_service_id = '';
    public $fee_type = 'paid'; // 'paid', 'free', or 'partial'
    public $paid_amount = '';
    public $payment_method = 'cash';
    public $free_reason = '';
    public $other_reason = '';
    public $notes = '';

    // Token Display / Card after Generation
    public $generatedTokenId = null;
    public $isSubmitting = false;

    // Queue Filter
    public $queue_filter_status = 'all';
    public $queue_search = '';

    // Print target token ID (defaults to generatedTokenId)
    public $printTokenId = null;

    protected $queryString = [
        'queue_filter_status' => ['except' => 'all'],
    ];

    public function mount()
    {
        // Auto-select first active doctor if only one exists or pre-selected via query string
        if (request()->has('doctor_id')) {
            $this->doctor_id = request('doctor_id');
        } else {
            $firstDoc = Doctor::active()->first();
            if ($firstDoc) {
                $this->doctor_id = (string) $firstDoc->id;
            }
        }

        // Auto-select default consultation service
        $defaultService = \App\Models\HospitalService::active()->where('service_type', 'consultation')->first()
            ?? \App\Models\HospitalService::active()->first();
        if ($defaultService) {
            $this->hospital_service_id = (string) $defaultService->id;
        }

        if (request()->has('patient_id')) {
            $this->selectPatient(request('patient_id'));
        }
    }

    public function updatedHospitalServiceId($serviceId)
    {
        // When service changes, re-sync fee if needed
    }


    public function updatedDoctorId()
    {
        $this->resetValidation('doctor_id');
    }

    public function setPatientMode($mode)
    {
        $this->patient_mode = $mode;
        $this->resetValidation();
        $this->duplicate_patient_warning = null;
    }

    public function selectPatient($patientId)
    {
        $patient = Patient::find($patientId);
        if (!$patient) {
            return;
        }

        $this->selected_patient_id = (string) $patient->id;
        $this->patient_search = '';
        $this->resetValidation('selected_patient_id');

        // Check if patient already has an active token today
        $today = now()->toDateString();
        $activeToken = PatientToken::where('patient_id', $patient->id)
            ->where('token_date', $today)
            ->whereIn('status', ['waiting', 'called', 'in_consultation'])
            ->first();

        if ($activeToken) {
            $this->has_active_token_warning = true;
            $this->active_token_number = $activeToken->formatted_token_number;
            $this->active_token_status = $activeToken->formatted_status;
        } else {
            $this->has_active_token_warning = false;
            $this->active_token_number = '';
            $this->active_token_status = '';
        }
    }

    public function clearSelectedPatient()
    {
        $this->selected_patient_id = '';
        $this->patient_search = '';
        $this->has_active_token_warning = false;
        $this->active_token_number = '';
        $this->active_token_status = '';
    }

    public function updatedNewPhone($value)
    {
        $this->checkDuplicatePatient();
    }

    public function updatedNewCnic($value)
    {
        $this->checkDuplicatePatient();
    }

    protected function checkDuplicatePatient()
    {
        $phone = trim($this->new_phone);
        $cnic = trim($this->new_cnic);

        if (empty($phone) && empty($cnic)) {
            $this->duplicate_patient_warning = null;
            return;
        }

        $query = Patient::query();
        if (!empty($phone) && !empty($cnic)) {
            $query->where(function ($q) use ($phone, $cnic) {
                $q->where('phone', $phone)->orWhere('cnic', $cnic);
            });
        } elseif (!empty($phone)) {
            $query->where('phone', $phone);
        } else {
            $query->where('cnic', $cnic);
        }

        $found = $query->first();
        if ($found) {
            $this->duplicate_patient_warning = [
                'id' => $found->id,
                'name' => $found->name,
                'patient_number' => $found->patient_number,
                'phone' => $found->phone,
                'cnic' => $found->cnic,
            ];
        } else {
            $this->duplicate_patient_warning = null;
        }
    }

    public function useDuplicatePatient($patientId)
    {
        $this->patient_mode = 'existing';
        $this->duplicate_patient_warning = null;
        $this->selectPatient($patientId);
    }

    public function updatedFeeType($value)
    {
        if ($value === 'paid') {
            $this->free_reason = '';
            $this->other_reason = '';
            $this->paid_amount = '';
        } elseif ($value === 'free') {
            if (empty($this->free_reason)) {
                $this->free_reason = 'Poor Patient';
            }
            $this->paid_amount = 0;
        } elseif ($value === 'partial') {
            $this->free_reason = '';
            $this->other_reason = '';
        }
    }

    public function generateToken()
    {
        if ($this->isSubmitting) {
            return;
        }
        $this->isSubmitting = true;

        // Validation rules
        $rules = [
            'doctor_id'           => 'required|exists:doctors,id',
            'hospital_service_id' => 'nullable|exists:hospital_services,id',
            'fee_type'            => 'required|in:paid,free,partial',
            'payment_method'      => 'required|in:cash,card,bank_transfer,other',
            'notes'               => 'nullable|string|max:500',
        ];

        if ($this->patient_mode === 'existing') {
            $rules['selected_patient_id'] = 'required|exists:patients,id';
        } else {
            $rules['new_name']                = 'required|string|min:2|max:255';
            $rules['new_father_husband_name'] = 'nullable|string|max:255';
            $rules['new_age']                 = 'required|integer|min:0|max:150';
            $rules['new_gender']              = 'required|in:Male,Female,Other';
            $rules['new_phone']               = 'nullable|string|max:30';
            $rules['new_address']             = 'nullable|string|max:500';
            $rules['new_cnic']                = 'nullable|string|max:30';
        }

        if ($this->fee_type === 'free') {
            $rules['free_reason']  = 'required|string|max:100';
            if ($this->free_reason === 'Other') {
                $rules['other_reason'] = 'required|string|max:255';
            }
        } elseif ($this->fee_type === 'partial') {
            $rules['paid_amount'] = 'required|numeric|min:0.01';
        }

        $messages = [
            'doctor_id.required'           => 'Please select a doctor.',
            'selected_patient_id.required' => 'Please search and select an existing patient.',
            'new_name.required'            => 'Patient Name is required.',
            'new_age.required'             => 'Age is required.',
            'new_gender.required'          => 'Gender is required.',
            'free_reason.required'         => 'Reason for Free Visit is required.',
            'other_reason.required'        => 'Please specify the free visit reason.',
            'paid_amount.required'         => 'Please enter the collected payment amount.',
        ];

        $this->validate($rules, $messages);

        $doctor = Doctor::findOrFail($this->doctor_id);
        $service = !empty($this->hospital_service_id) ? \App\Models\HospitalService::find($this->hospital_service_id) : null;
        $isConsultation = (!$service || $service->service_type === 'consultation' || in_array($service->code, ['SRV-CONSULT', 'DOC_CONSULT']));

        if ($isConsultation) {
            $doctorFee = (float) $doctor->consultation_fee;
            $doctorSharePct = (float) ($doctor->doctor_share_percentage ?? 70.00);
            $hospitalSharePct = round(100.00 - $doctorSharePct, 2);
        } else {
            $doctorFee = (float) $service->default_fee;
            $doctorSharePct = (float) $service->doctor_share_percentage;
            $hospitalSharePct = (float) $service->hospital_share_percentage;
        }

        $discountAmount = 0.00;
        if ($this->fee_type === 'free') {
            $finalAmount = 0.00;
            $discountAmount = $doctorFee;
            $doctorShareAmount = 0.00;
            $hospitalShareAmount = 0.00;
        } elseif ($this->fee_type === 'partial') {
            $finalAmount = round(floatval($this->paid_amount), 2);
            if ($finalAmount > $doctorFee) {
                $finalAmount = $doctorFee;
            }
            // Revenue split recognized ONLY on the amount actually collected
            $doctorShareAmount = round($finalAmount * ($doctorSharePct / 100), 2);
            $hospitalShareAmount = round($finalAmount - $doctorShareAmount, 2);
        } else {
            $finalAmount = $doctorFee;
            $doctorShareAmount = round($finalAmount * ($doctorSharePct / 100), 2);
            $hospitalShareAmount = round($finalAmount - $doctorShareAmount, 2);
        }

        $freeReason = ($this->fee_type === 'free') ? $this->free_reason : null;
        $otherReason = ($this->fee_type === 'free' && $this->free_reason === 'Other') ? $this->other_reason : null;
        $today = now()->toDateString();

        try {
            $token = DB::transaction(function () use (
                $doctor,
                $service,
                $doctorFee,
                $finalAmount,
                $discountAmount,
                $doctorSharePct,
                $hospitalSharePct,
                $doctorShareAmount,
                $hospitalShareAmount,
                $freeReason,
                $otherReason,
                $today
            ) {
                // 1. Create Patient if New Patient mode
                if ($this->patient_mode === 'new') {
                    $patient = Patient::create([
                        'name'                => trim($this->new_name),
                        'father_husband_name' => trim($this->new_father_husband_name) ?: null,
                        'age'                 => intval($this->new_age),
                        'gender'              => $this->new_gender,
                        'phone'               => trim($this->new_phone) ?: null,
                        'address'             => trim($this->new_address) ?: null,
                        'cnic'                => trim($this->new_cnic) ?: null,
                    ]);
                    $patientId = $patient->id;
                } else {
                    $patientId = intval($this->selected_patient_id);
                }

                // 2. Compute Next Sequential Token Number for Today (resets daily to 001)
                $maxToken = PatientToken::where('token_date', $today)->lockForUpdate()->max('token_number');
                $nextTokenNumber = ($maxToken ? intval($maxToken) : 0) + 1;

                // 3. Create Token record
                $createdToken = PatientToken::create([
                    'patient_id'                => $patientId,
                    'doctor_id'                 => $doctor->id,
                    'hospital_service_id'       => $service ? $service->id : null,
                    'token_number'              => $nextTokenNumber,
                    'token_date'                => $today,
                    'payment_type'              => $this->fee_type,
                    'consultation_fee'          => $doctorFee,
                    'charged_amount'            => $finalAmount,
                    'discount_amount'           => $discountAmount,
                    'doctor_share_percentage'   => $doctorSharePct,
                    'hospital_share_percentage' => $hospitalSharePct,
                    'doctor_share_amount'       => $doctorShareAmount,
                    'hospital_share_amount'     => $hospitalShareAmount,
                    'free_reason'               => $freeReason,
                    'other_reason'              => $otherReason,
                    'status'                    => 'waiting',
                    'notes'                     => trim($this->notes) ?: null,
                ]);

                // 4. Create Hospital Bill & Revenue Split atomically
                $billingService = app(\App\Services\HospitalBillingService::class);

                $bill = $billingService->createBill([
                    'patient_id'                => $patientId,
                    'doctor_id'                 => $doctor->id,
                    'hospital_service_id'       => $service ? $service->id : null,
                    'patient_token_id'          => $createdToken->id,
                    'total_amount'              => $doctorFee,
                    'discount_amount'           => $discountAmount,
                    'doctor_share_percentage'   => $doctorSharePct,
                    'payment_type'              => $this->fee_type,
                    'paid_amount'               => $finalAmount,
                    'payment_method'            => $this->payment_method,
                    'free_reason'               => $freeReason,
                    'notes'                     => trim($this->notes) ?: null,
                    'created_by'                => auth()->id(),
                ]);

                $createdToken->update([
                    'hospital_bill_id' => $bill->id,
                ]);

                return $createdToken;
            });


            // Set generated token for preview card and print
            $this->generatedTokenId = $token->id;
            $this->printTokenId = $token->id;

            // Reset patient and form fields for next entry
            $this->resetPatientFields();

            session()->flash('success', "Token #{$token->formatted_token_number} generated successfully!");

        } catch (\Exception $e) {
            session()->flash('error', 'Error generating token: ' . $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function resetPatientFields()
    {
        $this->selected_patient_id = '';
        $this->patient_search = '';
        $this->has_active_token_warning = false;
        $this->active_token_number = '';
        $this->active_token_status = '';
        $this->new_name = '';
        $this->new_father_husband_name = '';
        $this->new_age = '';
        $this->new_gender = 'Male';
        $this->new_phone = '';
        $this->new_address = '';
        $this->new_cnic = '';
        $this->duplicate_patient_warning = null;
        $this->notes = '';
    }

    public function startNewToken()
    {
        $this->generatedTokenId = null;
        $this->resetPatientFields();
    }

    public function printToken($tokenId = null)
    {
        if ($tokenId) {
            $this->printTokenId = $tokenId;
        } elseif ($this->generatedTokenId) {
            $this->printTokenId = $this->generatedTokenId;
        }
        $this->dispatch('trigger-print');
    }

    // Queue Action: Start Consultation
    public function startConsultation($tokenId)
    {
        $token = PatientToken::findOrFail($tokenId);
        $token->update([
            'status'    => 'in_consultation',
            'called_at' => now(),
        ]);
        session()->flash('queue_success', "Token #{$token->formatted_token_number} is now In Consultation.");
    }

    // Queue Action: Mark as Completed
    public function completeConsultation($tokenId)
    {
        $token = PatientToken::findOrFail($tokenId);
        $token->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);
        session()->flash('queue_success', "Token #{$token->formatted_token_number} marked as Completed.");
    }

    // Queue Action: Cancel Token
    public function cancelToken($tokenId)
    {
        $token = PatientToken::findOrFail($tokenId);
        $token->update([
            'status' => 'cancelled',
        ]);
        session()->flash('queue_success', "Token #{$token->formatted_token_number} has been Cancelled.");
    }

    public function render()
    {
        $today = now()->toDateString();
        $doctors = Doctor::active()->orderBy('name')->get();
        $selectedDoctor = $this->doctor_id ? Doctor::find($this->doctor_id) : null;
        $selectedPatient = $this->selected_patient_id ? Patient::find($this->selected_patient_id) : null;

        // Search patients if typing in existing patient search
        $patientSearchResults = [];
        if ($this->patient_mode === 'existing' && strlen(trim($this->patient_search)) >= 1) {
            $q = trim($this->patient_search);
            $patientSearchResults = Patient::where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('cnic', 'like', "%{$q}%")
                ->orWhere('patient_number', 'like', "%{$q}%")
                ->take(8)
                ->get();
        }

        // Today's Queue Query
        $queueQuery = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today);

        if ($this->queue_filter_status !== 'all') {
            if ($this->queue_filter_status === 'in_consultation') {
                $queueQuery->whereIn('status', ['called', 'in_consultation']);
            } else {
                $queueQuery->where('status', $this->queue_filter_status);
            }
        }

        if (!empty(trim($this->queue_search))) {
            $qs = trim($this->queue_search);
            $queueQuery->where(function ($q) use ($qs) {
                $q->where('token_number', 'like', "%{$qs}%")
                  ->orWhereHas('patient', function ($pq) use ($qs) {
                      $pq->where('name', 'like', "%{$qs}%")
                         ->orWhere('phone', 'like', "%{$qs}%")
                         ->orWhere('patient_number', 'like', "%{$qs}%");
                  })
                  ->orWhereHas('doctor', function ($dq) use ($qs) {
                      $dq->where('name', 'like', "%{$qs}%");
                  });
            });
        }

        $queueTokens = $queueQuery->orderBy('token_number', 'asc')->paginate(20);

        // Queue Statistics
        $totalQueueCount     = PatientToken::where('token_date', $today)->count();
        $waitingCount        = PatientToken::where('token_date', $today)->where('status', 'waiting')->count();
        $inConsultationCount = PatientToken::where('token_date', $today)->whereIn('status', ['called', 'in_consultation'])->count();
        $completedCount      = PatientToken::where('token_date', $today)->where('status', 'completed')->count();
        $cancelledCount      = PatientToken::where('token_date', $today)->where('status', 'cancelled')->count();
        $paidCount           = PatientToken::where('token_date', $today)->where('payment_type', 'paid')->count();
        $freeCount           = PatientToken::where('token_date', $today)->where('payment_type', 'free')->count();

        // Active token for printing/displaying
        $generatedToken = $this->generatedTokenId ? PatientToken::with(['patient', 'doctor'])->find($this->generatedTokenId) : null;
        $printToken = $this->printTokenId ? PatientToken::with(['patient', 'doctor'])->find($this->printTokenId) : $generatedToken;

        $services = \App\Models\HospitalService::active()->orderBy('name')->get();
        $selectedService = !empty($this->hospital_service_id) ? \App\Models\HospitalService::find($this->hospital_service_id) : null;

        return view('livewire.opd.token-management', compact(
            'doctors',
            'selectedDoctor',
            'services',
            'selectedService',
            'selectedPatient',
            'patientSearchResults',
            'queueTokens',
            'totalQueueCount',
            'waitingCount',
            'inConsultationCount',
            'completedCount',
            'cancelledCount',
            'paidCount',
            'freeCount',
            'generatedToken',
            'printToken',
            'today'
        ))->layout('layouts.app');
    }

}
