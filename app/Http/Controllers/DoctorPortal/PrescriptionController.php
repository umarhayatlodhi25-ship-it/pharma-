<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Display prescriptions written by the authenticated doctor.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;
        $search = trim($request->input('search', ''));

        $query = $doctor->prescriptions()->with(['patient', 'items']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('prescription_number', 'like', "%{$search}%")
                  ->orWhere('diagnosis', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('patient_number', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $prescriptions = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();
        $totalPrescriptions = $doctor->prescriptions()->count();

        return view('doctor-portal.prescriptions.index', compact('doctor', 'prescriptions', 'search', 'totalPrescriptions'));
    }

    /**
     * Show form to create a new prescription.
     */
    public function create(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;
        $tokenId = $request->input('token_id');
        $token = null;
        $patient = null;

        if ($tokenId) {
            $token = PatientToken::with('patient')->findOrFail($tokenId);
            // Server-side Ownership Verification (IDOR protection)
            if ($token->doctor_id !== $doctor->id) {
                abort(403, 'Unauthorized Access: You are not assigned to this patient consultation.');
            }
            $patient = $token->patient;
        } elseif ($request->has('patient_id')) {
            $patient = Patient::findOrFail($request->input('patient_id'));
            // Check if doctor has ever seen this patient
            $hasAccess = PatientToken::where('doctor_id', $doctor->id)->where('patient_id', $patient->id)->exists();
            if (!$hasAccess) {
                abort(403, 'Unauthorized Access: You have no consultation record with this patient.');
            }
        }

        // Available active medicines for dropdown autocomplete
        $medicines = Medicine::select('id', 'name', 'strength', 'dosage_form')
            ->orderBy('name')
            ->limit(300)
            ->get();

        // Patients authorized for this doctor
        $authorizedPatients = Patient::whereHas('tokens', fn($q) => $q->where('doctor_id', $doctor->id))
            ->orderBy('name')
            ->get();

        return view('doctor-portal.prescriptions.create', compact('doctor', 'token', 'patient', 'medicines', 'authorizedPatients'));
    }

    /**
     * Store a new prescription in database atomically.
     */
    public function store(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        $validated = $request->validate([
            'patient_id'             => 'required|exists:patients,id',
            'patient_token_id'       => 'nullable|exists:patient_tokens,id',
            'diagnosis'              => 'nullable|string|max:1000',
            'clinical_notes'         => 'nullable|string|max:3000',
            'follow_up_date'         => 'nullable|date',
            'follow_up_instructions' => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.medicine_id'    => 'nullable|exists:medicines,id',
            'items.*.medicine_name'  => 'required|string|max:255',
            'items.*.dosage'         => 'nullable|string|max:100',
            'items.*.frequency'      => 'nullable|string|max:100',
            'items.*.duration'       => 'nullable|string|max:100',
            'items.*.instructions'   => 'nullable|string|max:255',
            'items.*.quantity'       => 'nullable|integer|min:1',
        ]);

        // Security / Authorization Checks:
        $patientId = $validated['patient_id'];
        if (!empty($validated['patient_token_id'])) {
            $token = PatientToken::findOrFail($validated['patient_token_id']);
            if ($token->doctor_id !== $doctor->id || $token->patient_id != $patientId) {
                abort(403, 'Unauthorized Access: Token does not match your assigned consultation.');
            }
        } else {
            // Patient authorization check
            $authorized = PatientToken::where('doctor_id', $doctor->id)
                ->where('patient_id', $patientId)
                ->exists();
            if (!$authorized) {
                abort(403, 'Unauthorized Access: You have no authorized consultation history with this patient.');
            }
        }

        $prescription = DB::transaction(function () use ($validated, $doctor) {
            $rx = Prescription::create([
                'patient_id'             => $validated['patient_id'],
                'doctor_id'              => $doctor->id,
                'patient_token_id'       => $validated['patient_token_id'] ?? null,
                'diagnosis'              => $validated['diagnosis'] ?? null,
                'clinical_notes'         => $validated['clinical_notes'] ?? null,
                'follow_up_date'         => $validated['follow_up_date'] ?? null,
                'follow_up_instructions' => $validated['follow_up_instructions'] ?? null,
                'status'                 => 'issued',
                'is_finalized'           => true,
                'created_by'             => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                PrescriptionItem::create([
                    'prescription_id' => $rx->id,
                    'medicine_id'     => $itemData['medicine_id'] ?? null,
                    'medicine_name'   => trim($itemData['medicine_name']),
                    'dosage'          => $itemData['dosage'] ?? null,
                    'frequency'       => $itemData['frequency'] ?? null,
                    'duration'        => $itemData['duration'] ?? null,
                    'instructions'    => $itemData['instructions'] ?? null,
                    'quantity'        => $itemData['quantity'] ?? 1,
                ]);
            }

            // Also update token clinical fields if token exists
            if (!empty($validated['patient_token_id'])) {
                $tok = PatientToken::find($validated['patient_token_id']);
                if ($tok) {
                    if (empty($tok->diagnosis) && !empty($validated['diagnosis'])) {
                        $tok->diagnosis = $validated['diagnosis'];
                    }
                    if (empty($tok->clinical_notes) && !empty($validated['clinical_notes'])) {
                        $tok->clinical_notes = $validated['clinical_notes'];
                    }
                    if (empty($tok->follow_up_date) && !empty($validated['follow_up_date'])) {
                        $tok->follow_up_date = $validated['follow_up_date'];
                    }
                    if (empty($tok->follow_up_instructions) && !empty($validated['follow_up_instructions'])) {
                        $tok->follow_up_instructions = $validated['follow_up_instructions'];
                    }
                    $tok->save();
                }
            }

            return $rx;
        });

        return redirect()->route('doctor.prescriptions.show', $prescription->id)
            ->with('success', "Prescription {$prescription->prescription_number} generated successfully.");
    }

    /**
     * Show prescription details.
     */
    public function show(Prescription $prescription)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($prescription->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not authorized to view prescriptions written by another doctor.');
        }

        $prescription->load(['patient', 'items.medicine', 'token', 'doctor']);

        return view('doctor-portal.prescriptions.show', compact('doctor', 'prescription'));
    }

    /**
     * Printable view of prescription.
     */
    public function print(Prescription $prescription)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($prescription->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not authorized to print prescriptions written by another doctor.');
        }

        $prescription->load(['patient', 'items.medicine', 'token', 'doctor']);

        return view('doctor-portal.prescriptions.print', compact('doctor', 'prescription'));
    }
}
