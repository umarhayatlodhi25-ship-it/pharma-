<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display a listing of registered patients with search functionality.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));

        $query = Patient::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('patient_number', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->withQueryString();

        return view('admin.patients.index', compact('patients', 'search'));
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
                ]
            ]);
        }

        return response()->json(['found' => false]);
    }
}
