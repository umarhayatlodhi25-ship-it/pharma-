<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\HospitalBill;
use App\Models\HospitalService;
use App\Models\Patient;
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

        $query = HospitalBill::with(['patient', 'doctor', 'service', 'token', 'payments'])
            ->whereBetween('bill_date', [$startDate, $endDate]);

        if (!empty($doctorId)) {
            $query->where('doctor_id', $doctorId);
        }

        if (!empty($serviceId)) {
            $query->where('hospital_service_id', $serviceId);
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

        $selectedDoctorId = $request->input('doctor_id');
        $selectedDoctor = $selectedDoctorId ? Doctor::find($selectedDoctorId) : null;

        $selectedServiceId = $request->input('service_id');
        $selectedService = $selectedServiceId ? HospitalService::find($selectedServiceId) : null;

        return view('admin.hospital-billing.create', compact(
            'allDoctors',
            'allServices',
            'selectedPatient',
            'selectedDoctor',
            'selectedService'
        ));
    }

    /**
     * Store newly created bill with server-side revenue split.
     */
    public function store(Request $request)
    {
        $patientMode = $request->input('patient_mode', 'existing');

        $rules = [
            'patient_mode'              => 'required|in:existing,new',
            'hospital_service_id'       => 'nullable|exists:hospital_services,id',
            'doctor_id'                 => 'nullable|exists:doctors,id',
            'total_amount'              => 'required|numeric|min:0',
            'payment_type'              => 'required|in:paid,free,partial,pending',
            'payment_method'            => 'required|in:cash,card,bank_transfer,other',
            'notes'                     => 'nullable|string|max:500',
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
            $rules['free_reason'] = 'required|string|max:255';
        }

        if ($request->input('payment_type') === 'partial') {
            $rules['paid_amount'] = 'required|numeric|min:0.01|lte:total_amount';
        }

        $validated = $request->validate($rules);

        // Atomic transaction to create Patient (if new) and Bill
        $bill = DB::transaction(function () use ($request, $patientMode, $validated) {
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
                'doctor_id'                 => $validated['doctor_id'] ?? null,
                'hospital_service_id'       => $validated['hospital_service_id'] ?? null,
                'total_amount'              => $validated['total_amount'],
                'doctor_share_percentage'   => $request->input('doctor_share_percentage'),
                'payment_type'              => $validated['payment_type'],
                'paid_amount'               => $request->input('paid_amount'),
                'payment_method'            => $validated['payment_method'],
                'free_reason'               => $request->input('free_reason'),
                'notes'                     => $request->input('notes'),
                'created_by'                => auth()->id(),
            ]);
        });

        return redirect()->route('hospital-billing.show', $bill->id)
            ->with('success', "Bill #{$bill->bill_number} generated successfully.");
    }

    /**
     * Show bill details.
     */
    public function show(HospitalBill $bill)
    {
        $bill->load(['patient', 'doctor', 'service', 'token', 'payments.receivedBy', 'ledgerEntries']);
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
        $bill->load(['patient', 'doctor', 'service', 'token', 'payments']);
        return view('admin.hospital-billing.receipt', compact('bill'));
    }
}
