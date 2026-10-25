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

        // 1. Auto-link fallback by email if doctor record exists with matching email
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

        // 2. Auto-link fallback by name (e.g. matching "Dr. Jane Doe" with "Jane Doe")
        if (!$doctor && !empty($user->name)) {
            $cleanUserName = trim(preg_replace('/^(dr\.?|doctor)\s+/i', '', trim($user->name)));
            $candidate = \App\Models\Doctor::whereNull('user_id')
                ->get()
                ->first(function ($doc) use ($cleanUserName) {
                    $cleanDocName = trim(preg_replace('/^(dr\.?|doctor)\s+/i', '', trim($doc->name)));
                    return strcasecmp($cleanUserName, $cleanDocName) === 0;
                });

            if ($candidate) {
                $candidate->user_id = $user->id;
                $candidate->status = 'active';
                $candidate->is_active = true;
                if (empty($candidate->email) && !empty($user->email)) {
                    $candidate->email = $user->email;
                }
                $candidate->save();
                $doctor = $candidate;
            }
        }

        // 3. If there is only one unlinked doctor in the system, link to it
        if (!$doctor) {
            $unlinkedDoctors = \App\Models\Doctor::whereNull('user_id')->get();
            if ($unlinkedDoctors->count() === 1) {
                $candidate = $unlinkedDoctors->first();
                $candidate->user_id = $user->id;
                $candidate->status = 'active';
                $candidate->is_active = true;
                if (empty($candidate->email) && !empty($user->email)) {
                    $candidate->email = $user->email;
                }
                $candidate->save();
                $doctor = $candidate;
            }
        }

        // 4. Auto-provision doctor profile if user has 'doctor' role but no profile exists
        if (!$doctor) {
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
