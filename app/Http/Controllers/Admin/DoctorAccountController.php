<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorAccountController extends Controller
{
    /**
     * Create a new login account or link an existing account for this doctor.
     */
    public function createOrLink(Request $request, Doctor $doctor)
    {
        $actionType = $request->input('action_type', 'create');

        if ($actionType === 'create') {
            $validated = $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            DB::transaction(function () use ($validated, $doctor) {
                // If doctor was previously linked to another user, clear it
                $user = User::create([
                    'name'     => trim($validated['name']),
                    'email'    => strtolower(trim($validated['email'])),
                    'password' => Hash::make($validated['password']),
                    'role'     => 'doctor',
                ]);

                $doctor->user_id = $user->id;
                $doctor->status = 'active';
                $doctor->is_active = true;
                $doctor->save();
            });

            return redirect()->back()->with('success', "Login account successfully created and linked to Dr. {$doctor->name}.");
        }

        // Link existing user
        $validated = $request->validate([
            'existing_user_id' => 'required|exists:users,id',
        ]);

        $targetUser = User::findOrFail($validated['existing_user_id']);

        // Do not allow linking super admin accounts
        if ($targetUser->role === 'admin') {
            return redirect()->back()->withErrors([
                'existing_user_id' => 'Administrator accounts cannot be linked as doctor portal accounts for security reasons.',
            ]);
        }

        // Ensure user is not already linked to another doctor
        $alreadyLinked = Doctor::where('user_id', $targetUser->id)
            ->where('id', '!=', $doctor->id)
            ->first();

        if ($alreadyLinked) {
            return redirect()->back()->withErrors([
                'existing_user_id' => "This user account is already linked to Dr. {$alreadyLinked->name}.",
            ]);
        }

        DB::transaction(function () use ($targetUser, $doctor) {
            $targetUser->role = 'doctor';
            $targetUser->save();

            $doctor->user_id = $targetUser->id;
            $doctor->save();
        });

        return redirect()->back()->with('success', "Account {$targetUser->email} successfully linked to Dr. {$doctor->name}.");
    }

    /**
     * Securely reset password for the linked doctor user account.
     */
    public function resetPassword(Request $request, Doctor $doctor)
    {
        if (!$doctor->user) {
            return redirect()->back()->withErrors(['password' => 'No login account is currently linked to this doctor.']);
        }

        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $doctor->user;
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->back()->with('success', "Password for Dr. {$doctor->name}'s portal account updated successfully.");
    }

    /**
     * Unlink the user account from the doctor.
     */
    public function unlink(Doctor $doctor)
    {
        if ($doctor->user_id) {
            $doctor->user_id = null;
            $doctor->save();
        }

        return redirect()->back()->with('success', "Login account successfully unlinked from Dr. {$doctor->name}.");
    }

    /**
     * Toggle doctor activation status along with linked portal account access.
     */
    public function toggleStatus(Doctor $doctor)
    {
        $newActive = !$doctor->is_active;
        $doctor->is_active = $newActive;
        $doctor->status = $newActive ? 'active' : 'inactive';
        $doctor->save();

        $statusLabel = $newActive ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Dr. {$doctor->name}'s account and portal access has been {$statusLabel}.");
    }
}
