<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    /**
     * Display listing of patients authorized to be viewed by this doctor.
     * Only patients who have had at least one token/consultation with this doctor are accessible.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;
        $search = trim($request->input('search', ''));

        // Query only patients who have at least one token assigned to THIS doctor
        $query = Patient::whereHas('tokens', function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        })->withCount(['tokens' => function ($q) use ($doctor) {
            $q->where('doctor_id', $doctor->id);
        }]);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('patient_number', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('cnic', 'like', "%{$search}%")
                  ->orWhere('father_husband_name', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('name')->paginate(15)->withQueryString();
        $totalDoctorPatients = Patient::whereHas('tokens', fn($q) => $q->where('doctor_id', $doctor->id))->count();

        return view('doctor-portal.patients.index', compact('doctor', 'patients', 'search', 'totalDoctorPatients'));
    }

    /**
     * Display authorized patient profile and consultation history.
     */
    public function show(Patient $patient)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        // Server-Side Authorization Check: Has this doctor ever been assigned to this patient?
        $hasAccess = PatientToken::where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Unauthorized Medical Record Access: You do not have authorization to view this patient profile as you have no recorded consultations with them.');
        }

        // Load consultations conducted by this doctor, as well as general visit history with conducting doctor indicated
        $doctorTokens = $patient->tokens()
            ->where('doctor_id', $doctor->id)
            ->with(['prescription.items'])
            ->orderBy('token_date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        // Prescriptions written by THIS doctor for this patient
        $doctorPrescriptions = $patient->prescriptions()
            ->where('doctor_id', $doctor->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        // Active token today with this doctor, if any
        $activeTokenToday = $doctor->tokens()
            ->where('patient_id', $patient->id)
            ->where('token_date', now()->toDateString())
            ->whereIn('status', ['waiting', 'called', 'in_consultation'])
            ->first();

        return view('doctor-portal.patients.show', compact(
            'doctor',
            'patient',
            'doctorTokens',
            'doctorPrescriptions',
            'activeTokenToday'
        ));
    }
}
