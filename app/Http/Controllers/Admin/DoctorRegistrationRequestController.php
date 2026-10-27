<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorRegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DoctorRegistrationRequestController extends Controller
{
    /**
     * Display a listing of doctor registration requests with status filtering and search.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'all');
        $search = trim($request->input('search', ''));

        $query = DoctorRegistrationRequest::with(['doctor', 'user', 'reviewer']);

        if ($statusFilter !== 'all' && in_array($statusFilter, ['pending', 'approved', 'rejected', 'suspended'], true)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('doctor_identifier', 'like', "%{$search}%")
                  ->orWhere('license_number', 'like', "%{$search}%")
                  ->orWhereHas('doctor', function ($dq) use ($search) {
                      $dq->where('name', 'like', "%{$search}%")
                         ->orWhere('specialization', 'like', "%{$search}%");
                  });
            });
        }

        $requests = $query->orderByRaw("
            CASE 
                WHEN status = 'pending' THEN 1
                WHEN status = 'approved' THEN 2
                WHEN status = 'suspended' THEN 3
                ELSE 4
            END ASC
        ")->latest('id')->paginate(15)->withQueryString();

        // Metrics for dashboard tabs
        $counts = [
            'all'       => DoctorRegistrationRequest::count(),
            'pending'   => DoctorRegistrationRequest::where('status', 'pending')->count(),
            'approved'  => DoctorRegistrationRequest::where('status', 'approved')->count(),
            'rejected'  => DoctorRegistrationRequest::where('status', 'rejected')->count(),
            'suspended' => DoctorRegistrationRequest::where('status', 'suspended')->count(),
        ];

        // Active doctors for linking in case of manual override
        $unlinkedDoctors = Doctor::whereNull('user_id')->orderBy('name')->get();

        return view('admin.doctor-requests.index', compact('requests', 'counts', 'statusFilter', 'search', 'unlinkedDoctors'));
    }

    /**
     * Approve a pending or reviewed doctor registration request.
     * Links user to existing doctor profile and grants active doctor role.
     */
    public function approve(Request $request, $id)
    {
        $regRequest = DoctorRegistrationRequest::with(['doctor', 'user'])->findOrFail($id);

        $validated = $request->validate([
            'admin_remarks'    => 'nullable|string|max:1000',
            'target_doctor_id' => 'nullable|exists:doctors,id',
        ]);

        $doctor = $regRequest->doctor;

        // In case an admin explicitly assigns or updates target doctor
        if (!empty($validated['target_doctor_id'])) {
            $doctor = Doctor::findOrFail($validated['target_doctor_id']);
        }

        if (!$doctor) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot approve: No existing doctor profile is linked to this request. Please select a doctor profile first.',
            ]);
        }

        $user = $regRequest->user;
        if (!$user) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot approve: Associated user account could not be found.',
            ]);
        }

        // Enforce one portal account per existing doctor profile
        if (!empty($doctor->user_id) && $doctor->user_id !== $user->id) {
            $existingLinkedUser = User::find($doctor->user_id);
            $userEmail = $existingLinkedUser ? $existingLinkedUser->email : "User #{$doctor->user_id}";
            return redirect()->back()->withErrors([
                'error' => "Cannot approve: Dr. {$doctor->name} is already linked to portal account {$userEmail}.",
            ]);
        }

        DB::transaction(function () use ($regRequest, $doctor, $user, $validated) {
            // 1. Link doctor to user account and activate
            $doctor->user_id = $user->id;
            $doctor->status = 'active';
            $doctor->is_active = true;
            if (empty($doctor->email) && !empty($user->email)) {
                $doctor->email = $user->email;
            }
            if (empty($doctor->phone) && !empty($regRequest->phone)) {
                $doctor->phone = $regRequest->phone;
            }
            $doctor->save();

            // 2. Grant doctor role and activate user
            $user->role = 'doctor';
            $user->status = 'active';
            $user->save();

            // 3. Mark request as approved
            $regRequest->doctor_id = $doctor->id;
            $regRequest->status = 'approved';
            $regRequest->reviewed_by = auth()->id();
            $regRequest->reviewed_at = now();
            if (!empty($validated['admin_remarks'])) {
                $regRequest->admin_remarks = trim($validated['admin_remarks']);
            }
            $regRequest->save();

            Log::info("Doctor Portal Registration Request #{$regRequest->id} APPROVED by Admin #" . auth()->id() . " for Doctor #{$doctor->id} (Dr. {$doctor->name}), User #{$user->id}");
        });

        return redirect()->back()->with('success', "Registration request approved! Dr. {$doctor->name} has been linked to {$user->email} and can now log in to the Doctor Portal.");
    }

    /**
     * Reject a registration request with reason.
     */
    public function reject(Request $request, $id)
    {
        $regRequest = DoctorRegistrationRequest::with(['doctor', 'user'])->findOrFail($id);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'admin_remarks'    => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($regRequest, $validated) {
            $regRequest->status = 'rejected';
            $regRequest->rejection_reason = trim($validated['rejection_reason']);
            if (!empty($validated['admin_remarks'])) {
                $regRequest->admin_remarks = trim($validated['admin_remarks']);
            }
            $regRequest->reviewed_by = auth()->id();
            $regRequest->reviewed_at = now();
            $regRequest->save();

            // Reject the user account if created
            if ($regRequest->user) {
                $regRequest->user->status = 'rejected';
                $regRequest->user->save();
            }

            // Unlink doctor if linked
            if ($regRequest->doctor && $regRequest->doctor->user_id === $regRequest->user_id) {
                $regRequest->doctor->user_id = null;
                $regRequest->doctor->save();
            }

            Log::info("Doctor Portal Registration Request #{$regRequest->id} REJECTED by Admin #" . auth()->id() . ". Reason: " . $validated['rejection_reason']);
        });

        return redirect()->back()->with('success', "Doctor registration request #{$regRequest->id} has been rejected.");
    }

    /**
     * Suspend an approved doctor's portal access immediately.
     */
    public function suspend(Request $request, $id)
    {
        $regRequest = DoctorRegistrationRequest::with(['doctor', 'user'])->findOrFail($id);

        $validated = $request->validate([
            'admin_remarks' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($regRequest, $validated) {
            $regRequest->status = 'suspended';
            if (!empty($validated['admin_remarks'])) {
                $regRequest->admin_remarks = trim($validated['admin_remarks']);
            }
            $regRequest->reviewed_by = auth()->id();
            $regRequest->reviewed_at = now();
            $regRequest->save();

            // Suspend portal user account
            if ($regRequest->user) {
                $regRequest->user->status = 'suspended';
                $regRequest->user->save();
            }

            // Deactivate doctor record
            if ($regRequest->doctor) {
                $regRequest->doctor->status = 'inactive';
                $regRequest->doctor->is_active = false;
                $regRequest->doctor->save();
            }

            Log::warning("Doctor Portal Account SUSPENDED: Request #{$regRequest->id}, Doctor #" . ($regRequest->doctor_id ?? 'N/A') . " by Admin #" . auth()->id());
        });

        return redirect()->back()->with('success', "Doctor portal account suspended. Portal and clinical API access has been revoked immediately.");
    }
}
