<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Display a listing of doctors with search, status filters, and summary KPIs.
     */
    public function index(Request $request)
    {
        $search = trim($request->input('search', ''));
        $statusFilter = $request->input('status', 'active'); // Default: Active

        $query = Doctor::query();

        // Status Filter
        if ($statusFilter === 'active') {
            $query->active();
        } elseif ($statusFilter === 'inactive') {
            $query->inactive();
        }

        // Search Filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('specialization', 'like', "%{$search}%")
                  ->orWhere('qualification', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $doctors = $query->withCount('tokens')->orderBy('name')->paginate(15)->withQueryString();

        // Summary counts
        $totalDoctors = Doctor::count();
        $activeDoctors = Doctor::active()->count();
        $inactiveDoctors = Doctor::inactive()->count();

        return view('admin.doctors.index', compact(
            'doctors',
            'search',
            'statusFilter',
            'totalDoctors',
            'activeDoctors',
            'inactiveDoctors'
        ));
    }

    /**
     * Show the doctor registration form.
     */
    public function create()
    {
        return view('admin.doctors.create');
    }

    /**
     * Store a newly created doctor in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'specialization'   => 'required|string|max:255',
            'qualification'    => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:100',
            'gender'           => 'nullable|in:Male,Female,Other',
            'consultation_fee' => 'required|numeric|min:0',
            'status'           => 'required|in:active,inactive',
            'address'          => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:1000',
        ]);

        // Duplicate doctor detection: check same name AND specialization (Section 14)
        if (!$request->boolean('confirm_duplicate')) {
            $duplicate = Doctor::where('name', trim($validated['name']))
                ->where('specialization', trim($validated['specialization']))
                ->first();

            if ($duplicate) {
                return redirect()->back(fallback: route('doctors.create'))
                    ->withInput()
                    ->with('warning', "Doctor with this name and specialization already exists.")
                    ->with('duplicate_warning', "Doctor with this name and specialization already exists.")
                    ->with('duplicate_doctor_id', $duplicate->id)
                    ->with('duplicate_doctor_name', $duplicate->name);
            }
        }

        $doctor = Doctor::create($validated);

        return redirect()->route('doctors.index')
            ->with('success', "Doctor '{$doctor->name}' registered successfully.");
    }

    /**
     * Display the specified doctor profile and OPD statistics.
     */
    public function show(Doctor $doctor)
    {
        // Historical OPD Tokens for this doctor
        $tokens = $doctor->tokens()
            ->with('patient')
            ->orderBy('token_date', 'desc')
            ->orderBy('token_number', 'desc')
            ->paginate(15);

        // OPD Summary Statistics (Section 8)
        $totalTokens = $doctor->tokens()->count();
        $totalPatients = $doctor->tokens()->distinct('patient_id')->count('patient_id');
        $paidPatients = $doctor->tokens()->where('payment_type', 'paid')->count();
        $freePatients = $doctor->tokens()->where('payment_type', 'free')->count();

        // Financial totals (excluding cancelled visits)
        $validTokens = $doctor->tokens()->where('status', '!=', 'cancelled');
        $totalDoctorFees = (float) (clone $validTokens)->sum('consultation_fee');
        $totalCollection = (float) (clone $validTokens)->sum('charged_amount');

        return view('admin.doctors.show', compact(
            'doctor',
            'tokens',
            'totalTokens',
            'totalPatients',
            'paidPatients',
            'freePatients',
            'totalDoctorFees',
            'totalCollection'
        ));
    }

    /**
     * Show the form for editing the doctor.
     */
    public function edit(Doctor $doctor)
    {
        return view('admin.doctors.edit', compact('doctor'));
    }

    /**
     * Update the specified doctor in storage.
     */
    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'specialization'   => 'required|string|max:255',
            'qualification'    => 'nullable|string|max:255',
            'phone'            => 'nullable|string|max:30',
            'email'            => 'nullable|email|max:100',
            'gender'           => 'nullable|in:Male,Female,Other',
            'consultation_fee' => 'required|numeric|min:0',
            'status'           => 'required|in:active,inactive',
            'address'          => 'nullable|string|max:500',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $doctor->update($validated);

        return redirect()->route('doctors.show', $doctor->id)
            ->with('success', "Doctor '{$doctor->name}' updated successfully.");
    }

    /**
     * Toggle doctor active/inactive status.
     */
    public function toggleStatus(Doctor $doctor)
    {
        $newStatus = ($doctor->status === 'active') ? 'inactive' : 'active';
        $doctor->update(['status' => $newStatus, 'is_active' => ($newStatus === 'active')]);

        return redirect()->back(fallback: route('doctors.index'))
            ->with('success', "Doctor '{$doctor->name}' status changed to " . ucfirst($newStatus) . '.');
    }

    /**
     * Remove the specified doctor or deactivate if historical OPD tokens exist (Section 17).
     */
    public function destroy(Doctor $doctor)
    {
        if ($doctor->hasTokens()) {
            $doctor->update(['status' => 'inactive', 'is_active' => false]);

            return redirect()->route('doctors.index')
                ->with('warning', 'This doctor has historical OPD records and cannot be deleted. You can deactivate the doctor.');
        }

        $doctor->delete();

        return redirect()->route('doctors.index')
            ->with('success', 'Doctor deleted successfully.');
    }
}
