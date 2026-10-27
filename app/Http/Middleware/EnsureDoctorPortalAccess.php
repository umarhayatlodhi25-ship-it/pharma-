<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorPortalAccess
{
    /**
     * Handle an incoming request for doctor portal routes.
     * Enforces strict server-side authorization and data isolation.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('doctor.login');
        }

        $user = auth()->user();

        // 1. Account Status Enforcement
        if ($user->status === 'pending_approval') {
            return redirect()->route('doctor.pending-approval');
        }

        if ($user->status === 'rejected') {
            abort(403, 'Access Restricted: Your doctor portal registration request has been rejected. Please contact the hospital administrator.');
        }

        if ($user->status === 'suspended') {
            abort(403, 'Access Suspended: Your doctor portal account has been suspended. Please contact the administrator.');
        }

        // 2. Must have doctor role
        if ($user->role !== 'doctor') {
            abort(403, 'Access Restricted: This portal is reserved exclusively for authorized medical doctors.');
        }

        // 3. Must have an associated doctor record
        $doctor = $user->doctor;

        // Exact verified email link fallback (for admin-created doctor accounts)
        if (!$doctor && !empty($user->email)) {
            $matchingDoctor = \App\Models\Doctor::whereRaw('LOWER(email) = ?', [strtolower(trim($user->email))])
                ->whereNull('user_id')
                ->first();

            if ($matchingDoctor && ($matchingDoctor->status === 'active' || $matchingDoctor->is_active)) {
                $matchingDoctor->user_id = $user->id;
                $matchingDoctor->status = 'active';
                $matchingDoctor->is_active = true;
                $matchingDoctor->save();
                $doctor = $matchingDoctor;
            }
        }

        // Legacy fallback: Auto-provision doctor profile strictly if user is already an active approved doctor account
        if (!$doctor && $user->isActive() && !$user->isPendingApproval()) {
            $cleanName = trim($user->name ?: 'Doctor');
            if (!preg_match('/^(dr\.?|doctor)\s+/i', $cleanName)) {
                $cleanName = 'Dr. ' . $cleanName;
            }

            $doctor = \App\Models\Doctor::create([
                'user_id'                   => $user->id,
                'name'                      => $cleanName,
                'email'                     => $user->email,
                'specialization'            => 'General Physician',
                'qualification'             => 'MBBS',
                'room_number'               => 'OPD Room 1',
                'start_time'                => '09:00',
                'end_time'                  => '17:00',
                'schedule_days'             => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                'consultation_fee'          => 1000.00,
                'doctor_share_percentage'   => 70.00,
                'hospital_share_percentage' => 30.00,
                'status'                    => 'active',
                'is_active'                 => true,
            ]);
        }

        if (!$doctor) {
            abort(403, 'Access Restricted: No active doctor profile is linked to this portal account.');
        }

        // 4. Doctor profile must be active
        if ($doctor->status !== 'active' || !$doctor->is_active) {
            abort(403, 'Access Suspended: Your doctor account has been deactivated. Please contact the administrator.');
        }

        // Share the active doctor on the request and user instance for easy controller access
        $user->setRelation('doctor', $doctor);
        $request->attributes->set('doctor', $doctor);

        return $next($request);
    }
}
