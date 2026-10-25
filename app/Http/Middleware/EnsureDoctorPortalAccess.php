<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDoctorPortalAccess
{
    /**
     * Handle an incoming request for doctor portal routes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect('/login');
        }

        $user = auth()->user();

        // Must have doctor role
        if ($user->role !== 'doctor') {
            abort(403, 'Access Restricted: This portal is reserved exclusively for authorized medical doctors.');
        }

        // Must have an associated doctor record
        $doctor = $user->doctor;

        // Auto-heal / link by email if doctor record exists with matching email
        if (!$doctor && !empty($user->email)) {
            $matchingDoctor = \App\Models\Doctor::whereRaw('LOWER(email) = ?', [strtolower(trim($user->email))])
                ->whereNull('user_id')
                ->first();

            if ($matchingDoctor) {
                $matchingDoctor->user_id = $user->id;
                $matchingDoctor->status = 'active';
                $matchingDoctor->is_active = true;
                $matchingDoctor->save();
                $doctor = $matchingDoctor;
            }
        }

        if (!$doctor) {
            abort(403, 'Account Configuration Error: No doctor profile is linked to this login account. Please contact the administrator.');
        }

        // Doctor profile must be active
        if ($doctor->status !== 'active' || !$doctor->is_active) {
            abort(403, 'Access Suspended: Your doctor account has been deactivated. Please contact the administrator.');
        }

        // Share the active doctor on the request and user instance for easy controller access
        $user->setRelation('doctor', $doctor);
        $request->attributes->set('doctor', $doctor);

        return $next($request);
    }
}
