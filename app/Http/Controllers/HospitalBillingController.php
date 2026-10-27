<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\HospitalBill;
use App\Models\HospitalService;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Services\HospitalBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HospitalBillingController extends Controller
{
    protected HospitalBillingService $billingService;

    public function __construct(HospitalBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Display list of hospital bills and real-time revenue collection summary.
     */
    public function index(Request $request)
    {
        $filter = $request->input('filter', 'today');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $doctorId = $request->input('doctor_id');
        $serviceId = $request->input('hospital_service_id');
        $paymentStatus = $request->input('payment_status');
        $paymentMethod = $request->input('payment_method');
        $billingType = $request->input('billing_type');
        $search = $request->input('search');

        // Resolve Date Range
        if ($filter === 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($filter === 'yesterday') {
            $startDate = Carbon::yesterday()->toDateString();
            $endDate = Carbon::yesterday()->toDateString();
        } elseif ($filter === 'weekly') {
            $startDate = Carbon::now()->startOfWeek()->toDateString();
            $endDate = Carbon::now()->endOfWeek()->toDateString();
        } elseif ($filter === 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($filter === 'custom' && $startDate && $endDate) {
            // Keep custom dates
        } else {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        }

        $query = HospitalBill::with(['patient', 'doctor', 'service', 'token', 'items', 'payments'])
            ->whereBetween('bill_date', [$startDate, $endDate]);

        if (!empty($doctorId)) {
            $query->where('doctor_id', $doctorId);
        }

        if (!empty($serviceId)) {
            $query->where(function ($q) use ($serviceId) {
                $q->where('hospital_service_id', $serviceId)
                  ->orWhereHas('items', function ($iq) use ($serviceId) {
                      $iq->where('hospital_service_id', $serviceId);
                  });
            });
        }

        if (!empty($billingType)) {
            $query->where('billing_type', $billingType);
        }

        if (!empty($paymentStatus)) {
            $query->where('payment_status', $paymentStatus);
        }

        if (!empty($paymentMethod)) {
            $query->where('payment_method', $paymentMethod);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('patient_number', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Clone query for KPI aggregates
        $aggregateQuery = clone $query;
        $totalGross = (float) $aggregateQuery->sum('total_amount');
        $totalCollected = (float) $aggregateQuery->sum('paid_amount');
        $totalDiscount = (float) $aggregateQuery->sum('discount_amount');
        $totalDoctorShare = (float) $aggregateQuery->sum('doctor_share');
        $totalHospitalShare = (float) $aggregateQuery->sum('hospital_share');
        $totalOutstandingDue = (float) $aggregateQuery->sum('due_amount');
        $totalBillsCount = $aggregateQuery->count();

        $bills = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        $allDoctors = Doctor::active()->orderBy('name')->get();
        $allServices = HospitalService::active()->orderBy('name')->get();

        return view('admin.hospital-billing.index', compact(
            'bills',
            'allDoctors',
            'allServices',
            'filter',
            'startDate',
            'endDate',
            'doctorId',
            'serviceId',
            'paymentStatus',
            'paymentMethod',
            'billingType',
            'search',
            'totalGross',
            'totalCollected',
            'totalDiscount',
            'totalDoctorShare',
            'totalHospitalShare',
            'totalOutstandingDue',
            'totalBillsCount'
        ));
    }

    /**
     * Show form for creating a new hospital bill.
     */
    public function create(Request $request)
    {
        $allDoctors = Doctor::active()->orderBy('name')->get();
        $allServices = HospitalService::active()->orderBy('name')->get();

        $selectedPatientId = $request->input('patient_id');
        $selectedPatient = $selectedPatientId ? Patient::find($selectedPatientId) : null;

        $selectedTokenId = $request->input('token_id');
        $selectedToken = $selectedTokenId ? PatientToken::with(['doctor', 'service', 'hospitalBill'])->find($selectedTokenId) : null;
        if ($selectedToken && !$selectedPatient) {
            $selectedPatient = $selectedToken->patient;
        }

        $selectedDoctorId = $request->input('doctor_id') ?? ($selectedToken ? $selectedToken->doctor_id : null);
        $selectedDoctor = $selectedDoctorId ? Doctor::find($selectedDoctorId) : null;

        $selectedServiceId = $request->input('service_id');
        $selectedService = $selectedServiceId ? HospitalService::find($selectedServiceId) : null;

        $initialMode = $request->input('mode', ($selectedToken ? 'opd' : 'direct'));

        return view('admin.hospital-billing.create', compact(
            'allDoctors',
            'allServices',
            'selectedPatient',
            'selectedDoctor',
            'selectedService',
            'selectedToken',
            'initialMode'
        ));
    }

    /**
     * Store newly created bill supporting Mode A (OPD) and Mode B (Direct Service).
     */
    public function store(Request $request)
    {
        $billingMode = $request->input('billing_mode', 'direct'); // 'opd' or 'direct'
        $patientMode = $request->input('patient_mode', 'existing'); // 'existing' or 'new'

        $rules = [
            'billing_mode'              => 'required|in:opd,direct',
            'patient_mode'              => 'required|in:existing,new',
            'patient_token_id'          => 'nullable|exists:patient_tokens,id',
            'doctor_id'                 => 'nullable|exists:doctors,id',
            'hospital_service_id'       => 'nullable|exists:hospital_services,id',
            'total_amount'              => 'required|numeric|min:0',
            'discount_amount'           => 'nullable|numeric|min:0',
            'payment_type'              => 'required|in:paid,free,partial,pending',
            'payment_method'            => 'required|in:cash,card,bank_transfer,other',
            'notes'                     => 'nullable|string|max:500',
            'free_reason'               => 'nullable|string|max:255',
            'include_previous_balance'  => 'nullable|boolean',
            'items'                     => 'nullable', // JSON string or array
        ];

        if ($billingMode === 'opd') {
            $rules['patient_token_id'] = 'required|exists:patient_tokens,id';
            $rules['doctor_id']        = 'required|exists:doctors,id';
        }

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
            $rules['free_reason'] = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        // Server-side validation of doctor-token-patient relationships
        if ($billingMode === 'opd') {
            $token = PatientToken::with(['doctor', 'patient'])->find($validated['patient_token_id']);
            if (!$token) {
                return redirect()->back()->withErrors(['patient_token_id' => 'The selected OPD token does not exist.'])->withInput();
            }
            if ($patientMode === 'existing' && (int)$token->patient_id !== (int)$validated['patient_id']) {
                return redirect()->back()->withErrors(['patient_token_id' => 'The selected OPD token does not belong to the selected patient.'])->withInput();
            }
            if ($token->doctor_id && (int)$token->doctor_id !== (int)$validated['doctor_id']) {
                $docName = $token->doctor ? $token->doctor->name : 'another doctor';
                return redirect()->back()->withErrors([
                    'doctor_id' => "Selected OPD Token #{$token->formatted_token_number} is assigned to Dr. {$docName}. Mismatched doctor-token billing is not permitted.",
                ])->withInput();
            }
        }

        // Parse items if provided as JSON string
        $rawItems = $request->input('items');
        if (is_string($rawItems)) {
            $decoded = json_decode($rawItems, true);
            $parsedItems = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawItems)) {
            $parsedItems = $rawItems;
        } else {
            $parsedItems = [];
        }

        // Validate items server-side
        $validatedItems = [];
        $totalItemSum = 0.00;
        foreach ($parsedItems as $itm) {
            $srvId = !empty($itm['hospital_service_id']) ? intval($itm['hospital_service_id']) : null;
            $srv = $srvId ? HospitalService::find($srvId) : null;
            $name = $itm['service_name'] ?? ($srv ? $srv->name : 'Hospital Service');
            $code = $itm['service_code'] ?? ($srv ? $srv->code : null);
            $qty = max(0.01, round(floatval($itm['quantity'] ?? 1), 2));
            $unitPrice = max(0.00, round(floatval($itm['unit_price'] ?? ($srv ? $srv->default_fee : 0)), 2));
            $itemDiscount = max(0.00, round(floatval($itm['discount_amount'] ?? 0), 2));
            $subtotal = round($qty * $unitPrice, 2);
            if ($itemDiscount > $subtotal) {
                $itemDiscount = $subtotal;
            }
            $lineTotal = max(0.00, round($subtotal - $itemDiscount, 2));
            $totalItemSum += $lineTotal;

            $validatedItems[] = [
                'hospital_service_id' => $srvId,
                'service_name'        => $name,
                'service_code'        => $code,
                'quantity'            => $qty,
                'unit_price'          => $unitPrice,
                'subtotal'            => $subtotal,
                'discount_amount'     => $itemDiscount,
                'total_price'         => $lineTotal,
                'notes'               => $itm['notes'] ?? null,
            ];
        }

        // Recalculate authoritative total
        $effectiveGross = count($validatedItems) > 0 ? $totalItemSum : round(floatval($validated['total_amount']), 2);

        // Atomic transaction to create Patient (if new) and Bill
        $bill = DB::transaction(function () use ($request, $patientMode, $billingMode, $validated, $validatedItems, $effectiveGross) {
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
                $patientId = $validated['patient_id'];
            }

            return $this->billingService->createBill([
                'patient_id'                => $patientId,
                'billing_type'              => $billingMode,
                'patient_token_id'          => ($billingMode === 'opd') ? ($validated['patient_token_id'] ?? null) : null,
                'doctor_id'                 => $validated['doctor_id'] ?? null,
                'hospital_service_id'       => $validated['hospital_service_id'] ?? null,
                'total_amount'              => $effectiveGross,
                'discount_amount'           => $request->input('discount_amount', 0),
                'doctor_share_percentage'   => $request->input('doctor_share_percentage'),
                'payment_type'              => $validated['payment_type'],
                'paid_amount'               => $request->input('paid_amount'),
                'payment_method'            => $validated['payment_method'],
                'free_reason'               => $request->input('free_reason'),
                'notes'                     => $request->input('notes'),
                'include_previous_balance'  => $request->boolean('include_previous_balance'),
                'items'                     => $validatedItems,
                'created_by'                => auth()->id(),
            ]);
        });

        if ($request->input('action') === 'print_receipt' || $request->has('print_after_save')) {
            return redirect()->route('hospital-billing.receipt', $bill->id)
                ->with('success', "Bill #{$bill->bill_number} generated successfully.");
        }

        return redirect()->route('hospital-billing.show', $bill->id)
            ->with('success', "Bill #{$bill->bill_number} generated successfully.");
    }

    /**
     * Show bill details.
     */
    public function show(HospitalBill $bill)
    {
        $bill->load(['patient', 'doctor', 'service', 'token', 'items.service', 'payments.receivedBy', 'ledgerEntries', 'hospitalLedgers']);
        return view('admin.hospital-billing.show', compact('bill'));
    }

    /**
     * Receive partial/remaining payment on an outstanding bill.
     */
    public function receivePayment(Request $request, HospitalBill $bill)
    {
        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . $bill->due_amount,
            'payment_method' => 'required|in:cash,card,bank_transfer,other',
            'reference_note' => 'nullable|string|max:255',
        ]);

        try {
            $payment = $bill->recordPayment(
                floatval($validated['amount']),
                $validated['payment_method'],
                $validated['reference_note'] ?? 'Cashier counter payment',
                auth()->id()
            );

            return redirect()->back()
                ->with('success', "Payment of PKR " . number_format($payment->amount, 2) . " received successfully. Remaining Balance: PKR " . number_format($bill->fresh()->due_amount, 2));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Display printable thermal receipt for a bill.
     */
    public function printReceipt(HospitalBill $bill)
    {
        $bill->load(['patient', 'doctor', 'service', 'token', 'items.service', 'payments.receivedBy']);
        return view('admin.hospital-billing.receipt', compact('bill'));
    }

    /**
     * AJAX Search for OPD Tokens (Mode A)
     * Search by token number, patient ID, name, or phone.
     */
    public function searchTokens(Request $request)
    {
        $query = trim($request->input('query', ''));
        $doctorId = $request->input('doctor_id');
        $filterDoctor = $request->boolean('filter_doctor', false);

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $tokenQuery = PatientToken::with(['patient', 'doctor', 'service', 'hospitalBill.payments'])
            ->where(function ($q) use ($query) {
                $q->where('token_number', 'like', "%{$query}%")
                  ->orWhereHas('patient', function ($pq) use ($query) {
                      $pq->where('name', 'like', "%{$query}%")
                         ->orWhere('patient_number', 'like', "%{$query}%")
                         ->orWhere('phone', 'like', "%{$query}%");
                  });
            });

        if (!empty($doctorId)) {
            if ($filterDoctor) {
                $tokenQuery->where('doctor_id', (int)$doctorId);
            } else {
                $tokenQuery->orderByRaw('CASE WHEN doctor_id = ? THEN 0 ELSE 1 END', [(int)$doctorId]);
            }
        }

        $tokens = $tokenQuery
            ->latest('token_date')
            ->latest('id')
            ->take(15)
            ->get();

        $results = $tokens->map(function ($t) use ($doctorId) {
            $patient = $t->patient;
            $bill = $t->hospitalBill;
            $consultationFee = (float)$t->consultation_fee;
            $paidAgainstConsultation = $bill ? (float)$bill->paid_amount : (float)$t->charged_amount;
            $consultationBalance = $bill ? (float)$bill->due_amount : (float)$t->balance_due;

            return [
                'token_id'                     => $t->id,
                'token_number'                 => $t->token_number,
                'formatted_token_number'       => $t->formatted_token_number,
                'token_date'                   => $t->token_date->format('d-M-Y'),
                'token_status'                 => ucfirst($t->status),
                'patient_id'                   => $patient->id,
                'patient_name'                 => $patient->name,
                'patient_number'               => $patient->patient_number,
                'patient_phone'                => $patient->phone,
                'patient_age'                  => $patient->age,
                'patient_gender'               => $patient->gender,
                'doctor_id'                    => $t->doctor_id,
                'doctor_name'                  => $t->doctor ? $t->doctor->name : 'N/A',
                'doctor_matches'               => !empty($doctorId) && (int)$t->doctor_id === (int)$doctorId,
                'consultation_fee'             => $consultationFee,
                'consultation_paid'            => $paidAgainstConsultation,
                'consultation_balance'         => $consultationBalance,
                'consultation_outstanding'     => $patient ? $patient->consultation_outstanding : 0.00,
                'hospital_service_outstanding' => $patient ? $patient->hospital_service_outstanding : 0.00,
                'total_outstanding'            => $patient ? $patient->pending_balance : 0.00,
            ];
        });

        return response()->json($results);
    }

    /**
     * AJAX Search for Patients (Mode B & general lookup)
     */
    public function searchPatients(Request $request)
    {
        $query = trim($request->input('query', ''));
        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $patients = Patient::where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('patient_number', 'like', "%{$query}%")
              ->orWhere('phone', 'like', "%{$query}%")
              ->orWhere('cnic', 'like', "%{$query}%");
        })
        ->take(15)
        ->get();

        $results = $patients->map(function ($p) {
            return [
                'id'                           => $p->id,
                'name'                         => $p->name,
                'patient_number'               => $p->patient_number,
                'phone'                        => $p->phone,
                'age'                          => $p->age,
                'gender'                       => $p->gender,
                'address'                      => $p->address,
                'cnic'                         => $p->cnic,
                'father_husband_name'          => $p->father_husband_name,
                'consultation_outstanding'     => $p->consultation_outstanding,
                'hospital_service_outstanding' => $p->hospital_service_outstanding,
                'total_outstanding'            => $p->pending_balance,
            ];
        });

        return response()->json($results);
    }

    /**
     * AJAX Patient Context & History
     */
    public function patientContext(Patient $patient, Request $request)
    {
        $tokenId = $request->input('token_id');
        $context = $this->billingService->getPatientBillingContext($patient->id, $tokenId ? intval($tokenId) : null);

        return response()->json([
            'patient'                      => [
                'id'                           => $patient->id,
                'name'                         => $patient->name,
                'patient_number'               => $patient->patient_number,
                'phone'                        => $patient->phone,
                'age'                          => $patient->age,
                'gender'                       => $patient->gender,
                'address'                      => $patient->address,
                'cnic'                         => $patient->cnic,
            ],
            'token'                        => $context['token'] ? [
                'id'                     => $context['token']->id,
                'token_number'           => $context['token']->token_number,
                'formatted_token_number' => $context['token']->formatted_token_number,
                'token_date'             => $context['token']->token_date->format('d-M-Y'),
                'doctor_id'              => $context['token']->doctor_id,
                'doctor_name'            => $context['token']->doctor ? $context['token']->doctor->name : 'N/A',
                'consultation_fee'       => (float)$context['token']->consultation_fee,
                'consultation_paid'      => $context['token']->hospitalBill ? (float)$context['token']->hospitalBill->paid_amount : (float)$context['token']->charged_amount,
                'consultation_balance'   => $context['token']->hospitalBill ? (float)$context['token']->hospitalBill->due_amount : (float)$context['token']->balance_due,
            ] : null,
            'consultation_outstanding'     => $context['consultation_outstanding'],
            'hospital_service_outstanding' => $context['hospital_service_outstanding'],
            'total_outstanding'            => $context['total_outstanding'],
            'existing_bills'               => $context['existing_bills']->map(function ($b) {
                return [
                    'id'             => $b->id,
                    'bill_number'    => $b->bill_number,
                    'bill_date'      => $b->bill_date->format('d-M-Y'),
                    'billing_type'   => $b->billing_type ?? 'direct',
                    'service_name'   => $b->service ? $b->service->name : ($b->items->first() ? $b->items->first()->service_name : 'Hospital Service'),
                    'total_amount'   => (float)$b->total_amount,
                    'paid_amount'    => (float)$b->paid_amount,
                    'due_amount'     => (float)$b->due_amount,
                    'payment_status' => $b->payment_status,
                ];
            }),
        ]);
    }
}
