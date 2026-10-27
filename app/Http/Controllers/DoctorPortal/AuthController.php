<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorRegistrationRequest;
use App\Models\User;
use App\Services\ContactNormalizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Display the dedicated Doctor Portal login page.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->role === 'doctor') {
                if ($user->isPendingApproval()) {
                    return redirect()->route('doctor.pending-approval');
                }
                if ($user->isActive() && $user->doctor) {
                    return redirect()->route('doctor.dashboard');
                }
            }
        }

        return view('doctor-portal.auth.login');
    }

    /**
     * Process Doctor Portal login.
     */
    public function login(Request $request)
    {
        $normalizedEmail = ContactNormalizationService::normalizeEmail($request->input('email'));
        $request->merge(['email' => $normalizedEmail]);

        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate Limiting (5 attempts per minute per IP + email)
        $throttleKey = Str::transliterate('doctor-login|' . $normalizedEmail . '|' . $request->ip());
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => "Too many login attempts. Please wait {$seconds} seconds before retrying.",
            ]);
        }

        $credentials = [
            'email'    => $normalizedEmail,
            'password' => $request->password,
        ];

        // 1. Look up user by email
        $user = User::where('email', $normalizedEmail)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Auth::logout();
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        // 2. Check user status
        if ($user->status === 'pending_approval') {
            Auth::logout();
            RateLimiter::clear($throttleKey);
            return redirect()->route('doctor.pending-approval')
                ->with('info', 'Your doctor portal registration request is currently pending administrator verification and approval.');
        }

        if ($user->status === 'rejected') {
            Auth::logout();
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Your doctor registration request has been rejected. Please contact the hospital administrator.',
            ]);
        }

        if ($user->status === 'suspended') {
            Auth::logout();
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Your Doctor Portal account has been suspended. Please contact the hospital administrator.',
            ]);
        }

        // 3. User must have doctor role
        if ($user->role !== 'doctor') {
            RateLimiter::hit($throttleKey, 60);
            if ($user->isAdmin()) {
                Auth::login($user, $request->boolean('remember'));
                return redirect('/dashboard');
            }
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Access Restricted: This portal is reserved exclusively for authorized medical doctors.',
            ]);
        }

        // 4. Must have an active linked doctor profile
        $doctor = $user->doctor;

        // Safe fallback: if doctor profile was pre-existing with exact matching email and unlinked
        if (!$doctor && !empty($user->email)) {
            $candidate = Doctor::whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->whereNull('user_id')
                ->first();

            if ($candidate && ($candidate->status === 'active' || $candidate->is_active)) {
                $candidate->user_id = $user->id;
                $candidate->status = 'active';
                $candidate->is_active = true;
                $candidate->save();
                $doctor = $candidate;
            }
        }

        if (!$doctor) {
            RateLimiter::clear($throttleKey);
            return redirect()->route('doctor.pending-approval')
                ->with('info', 'No verified doctor profile is currently linked to your portal account. Please contact the administrator.');
        }

        if ($doctor->status !== 'active' || !$doctor->is_active) {
            RateLimiter::hit($throttleKey, 60);
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Your doctor profile is currently deactivated. Please contact the hospital administrator.',
            ]);
        }

        // Success: Log in doctor
        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        Log::info("Doctor Portal Login Successful: User #{$user->id} (Doctor #{$doctor->id} - Dr. {$doctor->name}) from IP {$request->ip()}");

        return redirect()->intended(route('doctor.dashboard'));
    }

    /**
     * Display the public Doctor Self-Registration page.
     */
    public function showRegisterForm()
    {
        return view('doctor-portal.auth.register');
    }

    /**
     * Process public Doctor Self-Registration.
     */
    public function register(Request $request)
    {
        $normalizedEmail = ContactNormalizationService::normalizeEmail($request->input('email'));
        $normalizedPhone = ContactNormalizationService::normalizePhone($request->input('phone'));
        $doctorIdentifier = trim((string) $request->input('doctor_identifier', ''));

        $request->merge([
            'doctor_identifier' => $doctorIdentifier,
            'email'             => $normalizedEmail,
            'phone'             => $normalizedPhone,
        ]);

        $request->validate([
            'doctor_identifier' => 'required|string|max:100',
            'name'              => 'required|string|max:255',
            'email'             => 'required|email|max:255',
            'phone'             => 'required|string|min:7|max:30',
            'license_number'    => 'required|string|max:100',
            'password'          => 'required|string|min:6|confirmed',
            'terms'             => 'accepted',
        ]);

        // Generic safe response message to prevent data disclosure (Phase 2)
        $genericSafeSuccessMessage = 'Your registration request has been submitted successfully! If your doctor profile is registered in our hospital system, your request will be reviewed and verified by an administrator.';

        // Rate Limiting (10 registration submissions per hour per IP)
        $throttleKey = 'doctor-register|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 10)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors([
                'doctor_identifier' => "Too many registration attempts. Please wait {$seconds} seconds.",
            ]);
        }
        RateLimiter::hit($throttleKey, 3600);

        // 1. Existing Doctor Profile Lookup
        // Lookup using doctor identifier (ID, staff code) and contact details
        $rawIdentifier = trim($request->doctor_identifier);
        $doctor = null;

        // Lookup by Doctor ID if numeric
        if (is_numeric($rawIdentifier)) {
            $doctor = Doctor::find((int) $rawIdentifier);
        }

        // If not found by numeric ID, lookup by registered email
        if (!$doctor && !empty($normalizedEmail)) {
            $doctor = Doctor::whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
        }

        // If not found yet, lookup by registered phone
        if (!$doctor && !empty($normalizedPhone)) {
            $doctor = Doctor::all()->first(function ($doc) use ($normalizedPhone) {
                return ContactNormalizationService::phoneMatches($doc->phone, $normalizedPhone);
            });
        }

        // CRITICAL SECURITY RULE:
        // DO NOT allow this workflow to create a new doctor profile automatically.
        // If no existing doctor profile is found:
        if (!$doctor) {
            Log::warning("Doctor Self-Registration lookup failed for identifier '{$rawIdentifier}', email '{$normalizedEmail}', phone '{$normalizedPhone}'. No doctor profile created.");
            // Return safe generic response without disclosing that doctor was not found
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // CRITICAL SECURITY RULE:
        // Name-only matching cannot claim another doctor's identity!
        // Require a verified existing contact or an admin-approved identity verification process.
        $hasDocPhone = !empty(trim((string)$doctor->phone));
        $hasDocEmail = !empty(trim((string)$doctor->email));
        $phoneMatches = ContactNormalizationService::phoneMatches($normalizedPhone, $doctor->phone);
        $emailMatches = !empty($doctor->email) && (strtolower(trim($doctor->email)) === $normalizedEmail);

        $isVerified = false;
        $verificationNotes = '';

        if ($phoneMatches || $emailMatches) {
            $isVerified = true;
            $verificationNotes = 'Contact details matched existing hospital doctor profile (' .
                ($emailMatches ? 'Email matched; ' : '') .
                ($phoneMatches ? 'Phone matched;' : '') . ').';
        } elseif (!$hasDocPhone && !$hasDocEmail) {
            // "If the current doctor profile lacks a verified contact, send the request for manual admin verification."
            $isVerified = false;
            $verificationNotes = 'Current doctor profile lacks contact details in hospital records. Manual admin identity verification required. PMDC/License: ' . $request->license_number;
        } else {
            // Doctor profile HAS existing contact details, but neither matched what user entered!
            // Name-only or identifier-only matching MUST NOT claim another doctor's identity!
            Log::warning("Doctor Self-Registration attempted for Doctor #{$doctor->id} (Dr. {$doctor->name}), but contact mismatch. Entered: [{$normalizedEmail}, {$normalizedPhone}], Profile has: [{$doctor->email}, {$doctor->phone}].");
            // Return safe generic response without claiming doctor or disclosing details
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // DUPLICATE PREVENTION:
        // 1. Check if doctor profile already has a linked portal user account
        if (!empty($doctor->user_id)) {
            Log::info("Doctor Self-Registration rejected: Doctor #{$doctor->id} is already linked to User #{$doctor->user_id}.");
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // 2. Check if a pending or approved registration request already exists for this doctor
        $existingDoctorRequest = DoctorRegistrationRequest::where('doctor_id', $doctor->id)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingDoctorRequest) {
            Log::info("Doctor Self-Registration duplicate suppressed: Pending or approved request #{$existingDoctorRequest->id} already exists for Doctor #{$doctor->id}.");
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // 3. Check if user with this email already exists
        $existingUser = User::where('email', $normalizedEmail)->first();
        if ($existingUser) {
            Log::info("Doctor Self-Registration email duplicate: User already exists for email {$normalizedEmail}.");
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // 4. Check if phone is already used in another pending registration request
        $existingPhoneRequest = DoctorRegistrationRequest::where('phone', $normalizedPhone)
            ->where('status', 'pending')
            ->first();

        if ($existingPhoneRequest) {
            Log::info("Doctor Self-Registration phone duplicate suppressed: Pending request already exists with phone {$normalizedPhone}.");
            return redirect()->route('doctor.pending-approval')
                ->with('success', $genericSafeSuccessMessage);
        }

        // Atomic Transaction: Create pending portal user and registration request
        DB::transaction(function () use (
            $doctor,
            $request,
            $normalizedEmail,
            $normalizedPhone,
            $isVerified,
            $verificationNotes
        ) {
            // Create User account in pending_approval status (never plaintext password)
            $user = User::create([
                'name'     => $doctor->name,
                'email'    => $normalizedEmail,
                'password' => Hash::make($request->password),
                'role'     => 'doctor',
                'status'   => 'pending_approval',
            ]);

            // Create DoctorRegistrationRequest
            DoctorRegistrationRequest::create([
                'doctor_id'              => $doctor->id,
                'user_id'                => $user->id,
                'doctor_identifier'      => $request->doctor_identifier,
                'name'                   => trim($request->name),
                'email'                  => $normalizedEmail,
                'phone'                  => $normalizedPhone,
                'license_number'         => trim($request->license_number),
                'identity_document_type' => 'pmdc_license',
                'is_verified'            => $isVerified,
                'verification_notes'     => $verificationNotes,
                'status'                 => 'pending',
                'terms_accepted_at'      => now(),
                'ip_address'             => $request->ip(),
            ]);

            Log::info("Doctor Registration Request created for Doctor #{$doctor->id} (Dr. {$doctor->name}), User #{$user->id}, Verified: " . ($isVerified ? 'YES' : 'NO (Manual Review Needed)'));
        });

        return redirect()->route('doctor.pending-approval')
            ->with('success', $genericSafeSuccessMessage);
    }

    /**
     * Display the Pending Approval status screen.
     */
    public function pendingApproval()
    {
        return view('doctor-portal.auth.pending-approval');
    }

    /**
     * Log out from the Doctor Portal.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login')
            ->with('success', 'You have been successfully logged out of the Doctor Portal.');
    }
}
