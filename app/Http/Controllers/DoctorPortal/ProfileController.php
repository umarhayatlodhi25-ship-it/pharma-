<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show the authenticated doctor's profile and security settings.
     */
    public function show()
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;
        $user = auth()->user();

        return view('doctor-portal.profile.show', compact('doctor', 'user'));
    }

    /**
     * Update doctor profile contact and basic details.
     */
    public function update(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        $validated = $request->validate([
            'phone'         => 'nullable|string|max:30',
            'email'         => 'nullable|email|max:100',
            'qualification' => 'nullable|string|max:255',
            'room_number'   => 'nullable|string|max:50',
            'address'       => 'nullable|string|max:500',
        ]);

        $doctor->update($validated);

        return redirect()->back()->with('success', 'Profile information updated successfully.');
    }

    /**
     * Securely update password for the doctor's login account.
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:6|confirmed',
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->back()->with('success', 'Your portal account password has been changed successfully.');
    }
}
