<?php

namespace App\Http\Controllers;

use App\Models\AccountProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Display the centralized settings area.
     */
    public function index(Request $request)
    {
        $profile = AccountProfile::current();
        $activeTab = $request->query('tab', 'profile');

        // Common currencies list
        $currencies = [
            'PKR' => 'PKR — Pakistani Rupee (Rs)',
            'USD' => 'USD — US Dollar ($)',
            'EUR' => 'EUR — Euro (€)',
            'GBP' => 'GBP — British Pound (£)',
            'AED' => 'AED — UAE Dirham',
            'SAR' => 'SAR — Saudi Riyal',
            'INR' => 'INR — Indian Rupee (₹)',
        ];

        // Supported timezones
        $timezones = [
            'Asia/Karachi'   => 'Asia/Karachi (PKT +05:00)',
            'Asia/Dubai'     => 'Asia/Dubai (GST +04:00)',
            'Asia/Riyadh'    => 'Asia/Riyadh (AST +03:00)',
            'UTC'            => 'UTC (Coordinated Universal Time)',
            'Asia/Kolkata'   => 'Asia/Kolkata (IST +05:30)',
            'Europe/London'  => 'Europe/London (GMT/BST)',
            'America/New_York' => 'America/New_York (EST/EDT)',
        ];

        // User Management Logic
        $usersData = [];
        if ($activeTab === 'users') {
            $status = $request->query('status', 'active');
            $perPage = $request->query('per_page', 'all');

            if (!in_array($status, ['active', 'trashed', 'all'])) {
                $status = 'active';
            }

            $query = \App\Models\User::query();

            if ($status === 'trashed') {
                $query->onlyTrashed();
            } elseif ($status === 'all') {
                $query->withTrashed();
            } else {
                $query->whereNull('deleted_at');
            }

            if ($perPage === 'all') {
                $users = $query->orderByDesc('id')->paginate(1000)->withQueryString();
            } else {
                $limit = in_array((int) $perPage, [5, 10, 25, 50, 100]) ? (int) $perPage : 10;
                $users = $query->orderByDesc('id')->paginate($limit)->withQueryString();
            }

            $usersData = [
                'users' => $users,
                'status' => $status,
                'perPage' => $perPage,
                'counts' => [
                    'active' => \App\Models\User::whereNull('deleted_at')->count(),
                    'trashed' => \App\Models\User::onlyTrashed()->count(),
                    'all' => \App\Models\User::withTrashed()->count(),
                ]
            ];
        }

        return view('admin.settings.index', compact('profile', 'activeTab', 'currencies', 'timezones') + $usersData);
    }

    /**
     * Update the Account / Organization profile.
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'account_name'        => 'required|string|max:255',
            'phone'               => 'nullable|string|max:50',
            'email'               => 'nullable|email|max:100',
            'address'             => 'nullable|string|max:500',
            'city'                => 'nullable|string|max:100',
            'website'             => 'nullable|string|max:150',
            'registration_number' => 'nullable|string|max:100',
            'footer_text'         => 'nullable|string|max:500',
            'currency'            => 'required|string|max:10',
            'timezone'            => 'required|string|max:50',
            'logo'                => 'nullable|image|mimes:jpeg,png,jpg,webp,svg|max:2048',
            'remove_logo'         => 'nullable|boolean',
        ]);

        $profile = AccountProfile::current();

        // 1. Handle Remove Logo checkbox
        if ($request->boolean('remove_logo') && $profile->logo) {
            if (Storage::disk('public')->exists($profile->logo)) {
                Storage::disk('public')->delete($profile->logo);
            }
            $validated['logo'] = null;
        }

        // 2. Handle New Logo Upload
        if ($request->hasFile('logo')) {
            // Delete previous logo if present
            if ($profile->logo && Storage::disk('public')->exists($profile->logo)) {
                Storage::disk('public')->delete($profile->logo);
            }

            // Store in dedicated public 'logos' directory
            $path = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $path;
        }

        unset($validated['remove_logo']);

        $profile->update($validated);
        AccountProfile::clearCache();

        return redirect()->route('settings.index', ['tab' => 'profile'])
            ->with('success', 'Account Profile updated successfully.');
    }

    /**
     * Remove the current logo file.
     */
    public function deleteLogo()
    {
        $profile = AccountProfile::current();

        if ($profile->logo && Storage::disk('public')->exists($profile->logo)) {
            Storage::disk('public')->delete($profile->logo);
        }

        $profile->update(['logo' => null]);
        AccountProfile::clearCache();

        return redirect()->route('settings.index', ['tab' => 'profile'])
            ->with('success', 'Logo removed successfully.');
    }

    /**
     * Update Print & Token Settings.
     */
    public function updatePrintToken(Request $request)
    {
        $validated = $request->validate([
            'token_prefix'           => 'nullable|string|max:10',
            'token_start_number'     => 'required|integer|min:1',
            'daily_token_reset'      => 'nullable|boolean',
            'auto_print_token'       => 'nullable|boolean',
            'default_token_status'   => 'required|string|max:50',
            'thermal_paper_size'     => 'required|in:58,80',
            'print_copies'           => 'required|in:1,2',
            'show_logo'              => 'nullable|boolean',
            'show_organization_name' => 'nullable|boolean',
            'show_patient_id'        => 'nullable|boolean',
            'show_doctor_name'       => 'nullable|boolean',
            'show_date'              => 'nullable|boolean',
            'show_time'              => 'nullable|boolean',
            'show_consultation_fee'  => 'nullable|boolean',
            'show_token_status'      => 'nullable|boolean',
            'footer_text'            => 'nullable|string|max:500',
        ]);

        // Fix boolean values for checkboxes
        $booleans = [
            'daily_token_reset', 'auto_print_token', 'show_logo', 'show_organization_name',
            'show_patient_id', 'show_doctor_name', 'show_date', 'show_time', 
            'show_consultation_fee', 'show_token_status'
        ];

        foreach ($booleans as $field) {
            $validated[$field] = $request->has($field);
        }

        $profile = AccountProfile::current();
        $profile->update($validated);
        AccountProfile::clearCache();

        return redirect()->route('settings.index', ['tab' => 'print_token'])
            ->with('success', 'Print & Token Settings updated successfully.');
    }
}
