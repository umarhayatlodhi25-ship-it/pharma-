<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientTokenController extends Controller
{
    /**
     * Display today's OPD token queue and financial summary statistics.
     */
    public function index(Request $request)
    {
        $today = now()->toDateString();

        // Summary KPI Metrics for Today
        $totalToday     = PatientToken::where('token_date', $today)->count();
        $waitingCount   = PatientToken::where('token_date', $today)->where('status', 'waiting')->count();
        $calledCount    = PatientToken::where('token_date', $today)->where('status', 'called')->count();
        $completedCount = PatientToken::where('token_date', $today)->where('status', 'completed')->count();

        // Financial Metrics for Today (Part 22)
        $paidCount      = PatientToken::where('token_date', $today)->where('payment_type', 'paid')->count();
        $freeCount      = PatientToken::where('token_date', $today)->where('payment_type', 'free')->count();
        // IMPORTANT: Total Collected must use SUM(charged_amount), not consultation_fee
        $totalCollected = PatientToken::where('token_date', $today)->sum('charged_amount');

        // Currently Called / Serving Patient
        $nowServing = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->where('status', 'called')
            ->latest('called_at')
            ->first();

        // Today's Queue Listing
        $tokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->orderBy('token_number', 'asc')
            ->paginate(30)
            ->withQueryString();

        return view('admin.patient-tokens.index', compact(
            'tokens',
            'today',
            'totalToday',
            'waitingCount',
            'calledCount',
            'completedCount',
            'paidCount',
            'freeCount',
            'totalCollected',
            'nowServing'
        ));
    }

    /**
     * Show the smart OPD token generation screen.
     */
    public function create(Request $request)
    {
        $today = now()->toDateString();
        $doctors = Doctor::active()->orderBy('name')->get();

        $selectedPatientId = $request->input('patient_id');
        $selectedPatient = null;
        if (!empty($selectedPatientId)) {
            $selectedPatient = Patient::find($selectedPatientId);
        }

        $selectedDoctorId = $request->input('doctor_id');
        $selectedDoctor = null;
        if (!empty($selectedDoctorId)) {
            $selectedDoctor = Doctor::find($selectedDoctorId);
        }

        $services = \App\Models\HospitalService::active()->orderBy('name')->get();

        return view('admin.patient-tokens.create', compact(
            'doctors',
            'services',
            'today',
            'selectedPatient',
            'selectedDoctor'
        ));
    }

    /**
     * Generate a new OPD token for existing or new patient.
     */
    public function store(Request $request)
    {
        $patientMode = $request->input('patient_mode', 'existing');

        // Base validation rules
        $rules = [
            'doctor_id'           => 'required|exists:doctors,id',
            'hospital_service_id' => 'nullable|exists:hospital_services,id',
            'patient_mode'        => 'required|in:existing,new',
            'payment_type'        => 'required|in:paid,free,partial',
            'payment_method'      => 'nullable|in:cash,card,bank_transfer,other',
            'notes'               => 'nullable|string|max:500',
        ];

        if ($patientMode === 'existing') {
            $rules['patient_id'] = 'required|exists:patients,id';
        } else {
            $rules['patient_name']        = 'required|string|max:255';
            $rules['father_husband_name'] = 'nullable|string|max:255';
            $rules['age']                 = 'required|integer|min:0|max:150';
            $rules['gender']              = 'required|in:Male,Female,Other';
            $rules['phone']               = 'nullable|string|max:30';
            $rules['address']             = 'nullable|string|max:500';
            $rules['cnic']                = 'nullable|string|max:30';
        }

        if ($request->input('payment_type') === 'free') {
            $rules['free_reason']  = 'required|string|max:100';
            $rules['other_reason'] = 'required_if:free_reason,Other|nullable|string|max:255';
        } elseif ($request->input('payment_type') === 'partial') {
            $rules['paid_amount'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);

        $submitKey = 'token_submit_' . (auth()->id() ?? 'guest') . '_' . md5(json_encode([
            $patientMode,
            $patientMode === 'existing' ? $request->input('patient_id') : $request->input('patient_name'),
            $request->input('doctor_id'),
            $request->input('payment_type'),
            $request->input('paid_amount'),
        ]));

        if (\Illuminate\Support\Facades\Cache::has($submitKey)) {
            return redirect()->route('patient-tokens.index')
                ->with('warning', 'A token generation request was already submitted. Please avoid double-clicking.');
        }
        \Illuminate\Support\Facades\Cache::put($submitKey, true, now()->addSeconds(3));

        $today = now()->toDateString();
        $doctor = Doctor::findOrFail($validated['doctor_id']);
        $serviceId = $validated['hospital_service_id'] ?? null;
        $service = $serviceId ? \App\Models\HospitalService::find($serviceId) : null;

        // Check for likely duplicate when creating a new patient
        if ($patientMode === 'new' && !$request->boolean('confirm_duplicate')) {
            $cnic = trim($request->input('cnic', ''));
            $phone = trim($request->input('phone', ''));

            if (!empty($cnic) || !empty($phone)) {
                $dupQuery = Patient::query();
                if (!empty($cnic) && !empty($phone)) {
                    $dupQuery->where(function ($q) use ($cnic, $phone) {
                        $q->where('cnic', $cnic)->orWhere('phone', $phone);
                    });
                } elseif (!empty($cnic)) {
                    $dupQuery->where('cnic', $cnic);
                } else {
                    $dupQuery->where('phone', $phone);
                }

                $likelyDuplicate = $dupQuery->first();
                if ($likelyDuplicate) {
                    return redirect()->back()
                        ->withInput()
                        ->with('warning', "Patient may already be registered: {$likelyDuplicate->name} ({$likelyDuplicate->patient_number}).")
                        ->with('likely_duplicate_id', $likelyDuplicate->id)
                        ->with('likely_duplicate_name', $likelyDuplicate->name)
                        ->with('likely_duplicate_number', $likelyDuplicate->patient_number);
                }
            }
        }

        // Active token check for existing patient
        if ($patientMode === 'existing') {
            $patientId = $validated['patient_id'];
            $existingActiveToken = PatientToken::where('patient_id', $patientId)
                ->where('token_date', $today)
                ->whereIn('status', ['waiting', 'called'])
                ->first();

            if ($existingActiveToken && !$request->boolean('confirm_active_token')) {
                return redirect()->back()
                    ->withInput()
                    ->with('warning', "This patient already has an active token today (Token #{$existingActiveToken->formatted_token_number} - " . ucfirst($existingActiveToken->status) . ').')
                    ->with('existing_token_id', $existingActiveToken->id);
            }
        }

        // Safely validate that doctor has a configured consultation fee
        if ($doctor->consultation_fee === null || !is_numeric($doctor->consultation_fee) || (float) $doctor->consultation_fee < 0) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['doctor_id' => 'The selected doctor does not have a configured consultation fee. Please update the doctor profile first.']);
        }

        // Determine whether additional hospital service is selected
        $isAdditionalService = ($service && $service->service_type !== 'consultation' && !in_array($service->code, ['SRV-CONSULT', 'DOC_CONSULT']));
        $doctorConsultFee = (float) $doctor->consultation_fee;
        $serviceFee = $isAdditionalService ? (float) $service->default_fee : 0.00;
        $totalGrossFee = round($doctorConsultFee + $serviceFee, 2);

        $patientIdForCheck = ($patientMode === 'existing') ? $validated['patient_id'] : null;
        $previousDue = $patientIdForCheck ? (Patient::find($patientIdForCheck)?->pendingBalance() ?? 0.0) : 0.0;
        $totalDue = round($totalGrossFee + $previousDue, 2);

        if ($validated['payment_type'] === 'partial') {
            $paidAmtInput = floatval($request->input('paid_amount'));
            if ($paidAmtInput > $totalDue) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['paid_amount' => "Payment amount (PKR {$paidAmtInput}) cannot exceed total outstanding amount (PKR {$totalDue})."]);
            }
        }

        // Database transaction to create patient (if new), token, and hospital bill atomically
        $token = DB::transaction(function () use (
            $patientMode,
            $request,
            $doctor,
            $serviceId,
            $today
        ) {
            if ($patientMode === 'new') {
                $patient = Patient::create([
                    'name'                => $request->input('patient_name'),
                    'father_husband_name' => $request->input('father_husband_name'),
                    'age'                 => $request->input('age'),
                    'gender'              => $request->input('gender'),
                    'phone'               => $request->input('phone'),
                    'address'             => $request->input('address'),
                    'cnic'                => $request->input('cnic'),
                ]);
                $patientId = $patient->id;
            } else {
                $patientId = $request->input('patient_id');
            }

            $billingService = app(\App\Services\HospitalBillingService::class);

            return $billingService->generateOpdTokenWithBilling([
                'patient_id'          => $patientId,
                'doctor_id'           => $doctor->id,
                'hospital_service_id' => $serviceId,
                'fee_type'            => $request->input('payment_type'),
                'paid_amount'         => $request->input('paid_amount'),
                'payment_method'      => $request->input('payment_method', 'cash'),
                'free_reason'         => $request->input('free_reason'),
                'other_reason'        => $request->input('other_reason'),
                'notes'               => $request->input('notes'),
                'token_date'          => $today,
                'created_by'          => auth()->id(),
            ]);
        });

        if (\App\Models\AccountProfile::current()->auto_print_token) {
            return redirect()->route('patient-tokens.print', $token->id)
                ->with('token_generated', true)
                ->with('success', "TOKEN GENERATED SUCCESSFULLY: Token #{$token->formatted_token_number}");
        }

        return redirect()->route('patient-tokens.show', $token->id)
            ->with('token_generated', true)
            ->with('success', "TOKEN GENERATED SUCCESSFULLY: Token #{$token->formatted_token_number}");
    }


    /**
     * Display printable OPD token slip.
     */
    public function printSlip($id)
    {
        if ($id === 'preview') {
            $token = 'preview';
            return view('admin.patient-tokens.print', compact('token'));
        }

        $token = PatientToken::with(['patient', 'doctor'])->findOrFail($id);
        return view('admin.patient-tokens.print', compact('token'));
    }

    /**
     * Call next earliest waiting patient in queue.
     */
    public function callNext()
    {
        $today = now()->toDateString();

        $token = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->first();

        if (!$token) {
            return redirect()->route('patient-tokens.index')
                ->with('info', 'No waiting patients in today\'s queue.');
        }

        $token->update([
            'status'    => 'called',
            'called_at' => now(),
        ]);

        $patientName = $token->patient ? $token->patient->name : 'Patient';

        return redirect()->route('patient-tokens.index')
            ->with('success', "Now serving Token #{$token->formatted_token_number} — {$patientName}.");
    }

    /**
     * Call a specific waiting token.
     */
    public function call(PatientToken $token)
    {
        $token->update([
            'status'    => 'called',
            'called_at' => now(),
        ]);

        $patientName = $token->patient ? $token->patient->name : 'Patient';

        return redirect()->back()
            ->with('success', "Now serving Token #{$token->formatted_token_number} — {$patientName}.");
    }

    /**
     * Mark a token as completed.
     */
    public function complete(PatientToken $token)
    {
        $token->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', "Token #{$token->formatted_token_number} has been marked as Completed.");
    }

    /**
     * Mark a token as cancelled.
     */
    public function cancel(PatientToken $token)
    {
        $token->update([
            'status' => 'cancelled',
        ]);

        return redirect()->back()
            ->with('success', "Token #{$token->formatted_token_number} has been cancelled.");
    }

    /**
     * Display complete token details.
     */
    public function show(PatientToken $token)
    {
        $token->load(['patient', 'doctor']);
        return view('admin.patient-tokens.show', compact('token'));
    }
}
