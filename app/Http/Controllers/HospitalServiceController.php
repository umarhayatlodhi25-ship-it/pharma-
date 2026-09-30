<?php

namespace App\Http\Controllers;

use App\Models\HospitalService;
use Illuminate\Http\Request;

class HospitalServiceController extends Controller
{
    /**
     * Display listing of hospital services.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('service_type');
        $status = $request->input('status');

        $query = HospitalService::query();

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($type)) {
            $query->where('service_type', $type);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $services = $query->orderBy('name')->paginate(15)->withQueryString();

        $serviceTypes = [
            'consultation' => 'Doctor Consultation',
            'diagnostic'   => 'Diagnostic / Lab / Sugar / BP',
            'procedure'    => 'Procedure / Injection / Dressing',
            'nursing'      => 'Nursing / Drip / Infusion',
            'other'        => 'Other Hospital Service',
        ];

        return view('admin.hospital-services.index', compact('services', 'serviceTypes', 'search', 'type', 'status'));
    }

    /**
     * Store a newly created hospital service.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'                      => 'required|string|max:255',
            'code'                      => 'nullable|string|max:50',
            'service_type'              => 'required|string|in:consultation,diagnostic,procedure,nursing,other',
            'default_fee'               => 'required|numeric|min:0',
            'doctor_share_percentage'   => 'required|numeric|min:0|max:100',
            'hospital_share_percentage' => 'required|numeric|min:0|max:100',
            'is_active'                 => 'boolean',
            'description'               => 'nullable|string|max:500',
        ]);

        // Auto-enforce doctor_share + hospital_share = 100%
        $validated['hospital_share_percentage'] = round(100.00 - floatval($validated['doctor_share_percentage']), 2);
        $validated['is_active'] = $request->has('is_active');

        HospitalService::create($validated);

        return redirect()->route('hospital-services.index')
            ->with('success', 'Hospital Service created successfully.');
    }

    /**
     * Update the specified hospital service.
     */
    public function update(Request $request, HospitalService $hospitalService)
    {
        $validated = $request->validate([
            'name'                      => 'required|string|max:255',
            'code'                      => 'nullable|string|max:50',
            'service_type'              => 'required|string|in:consultation,diagnostic,procedure,nursing,other',
            'default_fee'               => 'required|numeric|min:0',
            'doctor_share_percentage'   => 'required|numeric|min:0|max:100',
            'hospital_share_percentage' => 'required|numeric|min:0|max:100',
            'is_active'                 => 'boolean',
            'description'               => 'nullable|string|max:500',
        ]);

        $validated['hospital_share_percentage'] = round(100.00 - floatval($validated['doctor_share_percentage']), 2);
        $validated['is_active'] = $request->has('is_active');

        $hospitalService->update($validated);

        return redirect()->route('hospital-services.index')
            ->with('success', "Hospital Service '{$hospitalService->name}' updated successfully.");
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(HospitalService $hospitalService)
    {
        $hospitalService->update([
            'is_active' => !$hospitalService->is_active,
        ]);

        $statusStr = $hospitalService->is_active ? 'Activated' : 'Deactivated';
        return redirect()->back()
            ->with('success', "Service '{$hospitalService->name}' {$statusStr} successfully.");
    }
}
