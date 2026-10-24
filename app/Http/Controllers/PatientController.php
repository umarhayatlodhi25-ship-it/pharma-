<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\HospitalBill;
use App\Models\HospitalBillPayment;
use App\Models\DoctorLedger;
use App\Models\HospitalAccountLedger;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    /**
     * Display a listing of registered patients with date, doctor, and status filters.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $period = $request->input('period', 'all');
        $doctorId = $request->input('doctor_id', '');
        $status = $request->input('status', 'all');
        $fromDate = $request->input('from_date', '');
        $toDate = $request->input('to_date', '');

        $tz = config('app.display_timezone', 'Asia/Karachi');
        $now = Carbon::now($tz);
        $todayDate = $now->toDateString();
        $yesterdayDate = $now->copy()->subDay()->toDateString();
        $weekStartDate = $now->copy()->startOfWeek()->toDateString();
        $weekEndDate = $now->copy()->endOfWeek()->toDateString();
        $monthStartDate = $now->copy()->startOfMonth()->toDateString();
        $monthEndDate = $now->copy()->endOfMonth()->toDateString();

        // Global KPI Counters
        $stats = [
            'total_patients' => Patient::count(),
            'today_patients' => Patient::where(function ($q) use ($todayDate) {
                $q->whereDate('created_at', $todayDate)
                  ->orWhereHas('tokens', fn($t) => $t->where('token_date', $todayDate));
            })->count(),
            'today_new_registered' => Patient::whereDate('created_at', $todayDate)->count(),
            'today_visits' => PatientToken::where('token_date', $todayDate)->count(),
            'today_completed_visits' => PatientToken::where('token_date', $todayDate)->where('status', 'completed')->count(),
            'month_patients' => Patient::where(function ($q) use ($monthStartDate, $monthEndDate) {
                $q->whereDate('created_at', '>=', $monthStartDate)->whereDate('created_at', '<=', $monthEndDate)
                  ->orWhereHas('tokens', fn($t) => $t->whereBetween('token_date', [$monthStartDate, $monthEndDate]));
            })->count(),
            'month_new_registered' => Patient::whereDate('created_at', '>=', $monthStartDate)
                ->whereDate('created_at', '<=', $monthEndDate)->count(),
            'month_visits' => PatientToken::whereBetween('token_date', [$monthStartDate, $monthEndDate])->count(),
            'month_completed_visits' => PatientToken::whereBetween('token_date', [$monthStartDate, $monthEndDate])
                ->where('status', 'completed')->count(),
            'total_visits' => PatientToken::count(),
            'total_completed_visits' => PatientToken::where('status', 'completed')->count(),
        ];

        // Doctor Stats Summary ("kis doctor ne kitne dekhe")
        $doctorStats = Doctor::withCount([
            'tokens as total_tokens_count',
            'tokens as completed_tokens_count' => function ($q) {
                $q->where('status', 'completed');
            },
            'tokens as total_patients_count' => function ($q) {
                $q->select(DB::raw('count(distinct(patient_id))'));
            },
            'tokens as today_tokens_count' => function ($q) use ($todayDate) {
                $q->where('token_date', $todayDate);
            },
            'tokens as month_tokens_count' => function ($q) use ($monthStartDate, $monthEndDate) {
                $q->whereBetween('token_date', [$monthStartDate, $monthEndDate]);
            },
        ])
        ->orderBy('name')
        ->get();

        $allDoctors = $doctorStats;

        // Resolve dates based on preset period
        if ($period === 'today') {
            $fromDate = $todayDate;
            $toDate = $todayDate;
        } elseif ($period === 'yesterday') {
            $fromDate = $yesterdayDate;
            $toDate = $yesterdayDate;
        } elseif ($period === 'this_week') {
            $fromDate = $weekStartDate;
            $toDate = $weekEndDate;
        } elseif ($period === 'this_month') {
            $fromDate = $monthStartDate;
            $toDate = $monthEndDate;
        }

        $query = Patient::query();

        // 1. Text Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        // 2. Doctor Filter
        if (!empty($doctorId)) {
            $query->whereHas('tokens', function ($q) use ($doctorId, $fromDate, $toDate, $status) {
                $q->where('doctor_id', $doctorId);
                if (!empty($fromDate) && !empty($toDate)) {
                    $q->whereBetween('token_date', [$fromDate, $toDate]);
                } elseif (!empty($fromDate)) {
                    $q->where('token_date', '>=', $fromDate);
                } elseif (!empty($toDate)) {
                    $q->where('token_date', '<=', $toDate);
                }
                if (!empty($status) && !in_array($status, ['all', 'has_visits', 'no_visits'])) {
                    $q->where('status', $status);
                }
            });
        } else {
            // General status filtering without specific doctor
            if ($status === 'has_visits') {
                $query->has('tokens');
            } elseif ($status === 'no_visits') {
                $query->doesntHave('tokens');
            } elseif (!empty($status) && $status !== 'all') {
                $query->whereHas('tokens', function ($q) use ($status, $fromDate, $toDate) {
                    $q->where('status', $status);
                    if (!empty($fromDate) && !empty($toDate)) {
                        $q->whereBetween('token_date', [$fromDate, $toDate]);
                    }
                });
            }

            // General date range filtering (when not filtering non-visit patients)
            if (!empty($fromDate) && !empty($toDate) && (empty($status) || in_array($status, ['all', 'has_visits']))) {
                $query->where(function ($q) use ($fromDate, $toDate) {
                    $q->whereDate('created_at', '>=', $fromDate)
                      ->whereDate('created_at', '<=', $toDate)
                      ->orWhereHas('tokens', function ($t) use ($fromDate, $toDate) {
                          $t->whereBetween('token_date', [$fromDate, $toDate]);
                      });
                });
            } elseif (!empty($fromDate) && !empty($toDate) && $status === 'no_visits') {
                $query->whereDate('created_at', '>=', $fromDate)
                      ->whereDate('created_at', '<=', $toDate);
            }
        }

        $selectedDoctor = !empty($doctorId) ? Doctor::find($doctorId) : null;

        // Eager load tokens and doctors
        $patients = $query->with([
            'tokens' => function ($q) use ($doctorId, $fromDate, $toDate) {
                if (!empty($doctorId)) {
                    $q->where('doctor_id', $doctorId);
                }
                if (!empty($fromDate) && !empty($toDate)) {
                    $q->whereBetween('token_date', [$fromDate, $toDate]);
                }
                $q->with('doctor')->orderBy('token_date', 'desc')->orderBy('id', 'desc');
            },
            'latestToken.doctor'
        ])
        ->withCount('tokens')
        ->latest()
        ->paginate(15)
        ->withQueryString();

        return view('admin.patients.index', compact(
            'patients',
            'search',
            'period',
            'doctorId',
            'status',
            'fromDate',
            'toDate',
            'stats',
            'doctorStats',
            'allDoctors',
            'selectedDoctor'
        ));
    }

    /**
     * Show the form for registering a new patient.
     */
    public function create()
    {
        return view('admin.patients.create');
    }

    /**
     * Store a newly created patient in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'father_husband_name' => 'nullable|string|max:255',
            'age'                 => 'required|integer|min:0|max:150',
            'gender'              => 'required|in:Male,Female,Other',
            'phone'               => 'nullable|string|max:30',
            'address'             => 'nullable|string|max:500',
            'cnic'                => 'nullable|string|max:30',
        ]);

        $patient = Patient::create($validated);

        return redirect()->route('patients.show', $patient->id)
            ->with('success', 'Patient registered successfully.')
            ->with('message', 'Patient registered successfully.');
    }

    /**
     * Display the specified patient profile.
     */
    public function show(Patient $patient)
    {
        $patient->load([
            'bills' => function ($q) {
                $q->with(['doctor', 'service', 'token', 'payments.receivedBy'])->orderBy('id', 'desc');
            }
        ]);
        return view('admin.patients.show', compact('patient'));
    }

    /**
     * AJAX Search for patients by ID, Name, Phone, or CNIC.
     */
    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $today = now()->toDateString();

        $patients = Patient::where(function ($query) use ($q) {
            $query->where('patient_number', 'like', "%{$q}%")
                  ->orWhere('name', 'like', "%{$q}%")
                  ->orWhere('phone', 'like', "%{$q}%")
                  ->orWhere('cnic', 'like', "%{$q}%");
        })
        ->limit(10)
        ->get()
        ->map(function ($patient) use ($today) {
            $activeToken = $patient->activeTokenToday($today);
            return [
                'id'                  => $patient->id,
                'patient_number'      => $patient->patient_number,
                'name'                => $patient->name,
                'father_husband_name' => $patient->father_husband_name ?? '',
                'age'                 => $patient->age,
                'gender'              => $patient->gender,
                'phone'               => $patient->phone ?? '',
                'cnic'                => $patient->cnic ?? '',
                'address'             => $patient->address ?? '',
                'has_active_token'    => !is_null($activeToken),
                'active_token_id'     => $activeToken?->id,
                'active_token_number' => $activeToken?->formatted_token_number,
                'active_token_status' => $activeToken?->status,
                'pending_balance'     => $patient->pendingBalance(),
                'unpaid_bills_count'  => $patient->unpaidBills()->count(),
            ];
        });

        return response()->json($patients);
    }

    /**
     * AJAX Check if CNIC or Phone is already registered.
     */
    public function checkDuplicate(Request $request)
    {
        $cnic = trim($request->input('cnic', ''));
        $phone = trim($request->input('phone', ''));

        if (empty($cnic) && empty($phone)) {
            return response()->json(['found' => false]);
        }

        $query = Patient::query();
        if (!empty($cnic) && !empty($phone)) {
            $query->where(function ($q) use ($cnic, $phone) {
                $q->where('cnic', $cnic)->orWhere('phone', $phone);
            });
        } elseif (!empty($cnic)) {
            $query->where('cnic', $cnic);
        } else {
            $query->where('phone', $phone);
        }

        $existing = $query->first();

        if ($existing) {
            return response()->json([
                'found'   => true,
                'patient' => [
                    'id'                  => $existing->id,
                    'patient_number'      => $existing->patient_number,
                    'name'                => $existing->name,
                    'father_husband_name' => $existing->father_husband_name ?? '',
                    'age'                 => $existing->age,
                    'gender'              => $existing->gender,
                    'phone'               => $existing->phone ?? '',
                    'cnic'                => $existing->cnic ?? '',
                    'address'             => $existing->address ?? '',
                    'pending_balance'     => $existing->pendingBalance(),
                ]
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * AJAX Get Patient Pending Balance and Breakdown of Unpaid Bills.
     */
    public function pendingBalance(Patient $patient): \Illuminate\Http\JsonResponse
    {
        $unpaidBills = $patient->unpaidBills()->with('doctor')->get();
        return response()->json([
            'patient_id'         => $patient->id,
            'patient_number'     => $patient->patient_number,
            'name'               => $patient->name,
            'pending_balance'    => $patient->pendingBalance(),
            'unpaid_bills'       => $unpaidBills->count(),
            'unpaid_bills_count' => $unpaidBills->count(),
            'unpaid_bills_list'  => $unpaidBills->map(function ($b) {
                return [
                    'id'             => $b->id,
                    'bill_number'    => $b->bill_number,
                    'bill_date'      => $b->bill_date ? $b->bill_date->format('d-M-Y') : '',
                    'total_amount'   => (float) $b->total_amount,
                    'paid_amount'    => (float) $b->paid_amount,
                    'due_amount'     => (float) $b->due_amount,
                    'doctor_name'    => $b->doctor ? $b->doctor->name : 'N/A',
                    'payment_status' => $b->payment_status,
                ];
            }),
        ]);
    }

    /**
     * Remove the specified patient and cleanly cascade/clean related records.
     */
    public function destroy(Patient $patient)
    {
        $patientName = $patient->name;
        $patientNumber = $patient->patient_number;

        try {
            DB::transaction(function () use ($patient) {
                $tokenIds = $patient->tokens()->pluck('id');
                $billIds = $patient->bills()->pluck('id');

                // 1. Break circular FK references between tokens and bills
                if ($tokenIds->isNotEmpty()) {
                    PatientToken::whereIn('id', $tokenIds)->update(['hospital_bill_id' => null]);
                }

                if ($billIds->isNotEmpty()) {
                    HospitalBill::whereIn('id', $billIds)->update(['patient_token_id' => null]);

                    // Clean doctor ledgers referring to these bills or payments
                    DoctorLedger::whereIn('hospital_bill_id', $billIds)->delete();

                    $paymentIds = HospitalBillPayment::whereIn('hospital_bill_id', $billIds)->pluck('id');
                    if ($paymentIds->isNotEmpty()) {
                        DoctorLedger::whereIn('hospital_bill_payment_id', $paymentIds)->delete();
                        HospitalBillPayment::whereIn('id', $paymentIds)->delete();
                    }

                    if (Schema::hasTable('hospital_account_ledgers')) {
                        HospitalAccountLedger::whereIn('hospital_bill_id', $billIds)->delete();
                    }

                    HospitalBill::whereIn('id', $billIds)->delete();
                }

                // 2. Remove any direct payments or ledgers linked to this patient
                HospitalBillPayment::where('patient_id', $patient->id)->delete();
                if (Schema::hasTable('hospital_account_ledgers')) {
                    HospitalAccountLedger::where('patient_id', $patient->id)->delete();
                }

                // 3. Delete tokens
                $patient->tokens()->delete();

                // 4. Delete the patient
                $patient->delete();
            });

            return redirect()->route('patients.index')
                ->with('success', "Patient '{$patientName}' ({$patientNumber}) deleted successfully.");
        } catch (\Throwable $e) {
            Log::error("Failed to delete patient {$patient->id}: " . $e->getMessage());

            return redirect()->route('patients.index')
                ->with('error', "Could not delete patient: " . $e->getMessage());
        }
    }
}
