<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Login Form Show karna
    public function showLogin()
    {
        \App\Services\FirebaseService::ensureDatabaseSeeded();
        return view('auth.login');
    }

    /**
     * Get redirect path based on user role.
     */
    public function redirectForUser(User $user): string
    {
        return match ($user->role) {
            'doctor' => '/doctor-portal/dashboard',
            'cashier' => '/pos',
            'hospital' => '/opd/tokens',
            default => '/dashboard',
        };
    }

    // Login Process
    public function login(Request $request)
    {
        \App\Services\FirebaseService::ensureDatabaseSeeded();

        $request->merge([
            'email' => strtolower(trim((string)$request->input('email'))),
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        $credentials = [
            'email' => $email,
            'password' => $password,
        ];

        if (Auth::attempt($credentials, $remember)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            return redirect($this->redirectForUser(Auth::user()));
        }

        // Retry after syncing from Firebase
        \App\Services\FirebaseService::syncFirebaseUsersToLocal();
        if (Auth::attempt($credentials, $remember)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            return redirect($this->redirectForUser(Auth::user()));
        }

        return back()->withInput($request->only('email', 'remember'))->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    // Register Form Show karna
    public function showRegister()
    {
        return view('auth.register');
    }

    // Register Process
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,cashier',
        ]);

        $user = User::create([
            'name' => trim($request->name),
            'email' => strtolower(trim($request->email)),
            'password' => $request->password,
            'role' => $request->role,
        ]);

        Auth::login($user);

        return redirect($this->redirectForUser($user));
    }

    // Firebase Google Auth Login
    public function firebaseLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string',
            'uid' => 'nullable|string',
        ]);

        $email = strtolower(trim($request->email));
        $name = $request->name ?: explode('@', $email)[0];

        $user = User::withTrashed()->where('email', $email)->first();

        if ($user && $user->trashed()) {
            $user->restore();
        }

        if (!$user) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => \Illuminate\Support\Str::random(16),
                'role' => 'admin', // Google Account is Super Admin
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        $redirectUrl = $this->redirectForUser($user);

        return response()->json([
            'success' => true,
            'redirect' => $redirectUrl,
        ]);
    }

    // Logout Process
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    // Show Forgot Password View
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    // Process Forgot Password Email Verification
    public function processForgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        \App\Services\FirebaseService::ensureDatabaseSeeded();

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'No registered account found with this email address.',
            ])->withInput();
        }

        return redirect()->route('password.reset', ['email' => $user->email])
            ->with('status', 'Account verified! Please set your new password.');
    }

    // Show Reset Password View
    public function showResetPassword(Request $request, $email = null)
    {
        $email = $email ?: $request->query('email');
        return view('auth.reset-password', compact('email'));
    }

    // Process Password Reset
    public function processResetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        \App\Services\FirebaseService::ensureDatabaseSeeded();

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'User not found.',
            ])->withInput();
        }

        $user->password = $request->password;
        $user->save();

        return redirect()->route('login')->with('message', 'Password updated successfully! Please login with your new password.');
    }

    /**
     * Get current user's active role (for real-time status check).
     */
    public function userRoleStatus()
    {
        if (!Auth::check()) {
            return response()->json(['authenticated' => false], 401);
        }

        $user = Auth::user()->fresh();

        return response()->json([
            'authenticated' => true,
            'role' => $user->role ?? 'user',
            'redirect' => match ($user->role) {
                'doctor' => '/doctor-portal/dashboard',
                'admin', 'pharmacist' => '/dashboard',
                'hospital' => '/opd/tokens',
                default => '/pos',
            },
        ]);
    }
}