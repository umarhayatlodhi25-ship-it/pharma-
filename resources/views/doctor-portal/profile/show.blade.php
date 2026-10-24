@extends('layouts.doctor')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Profile & Security</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-id-card text-teal-600"></i>
                <span>Doctor Profile & Account Credentials</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Manage your clinical profile details and portal authentication password.
            </p>
        </div>
    </div>

    <!-- PROFILE DETAILS & CONTACT FORM -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-6">
        <form action="{{ route('doctor.profile.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-user-doctor text-teal-600"></i>
                <span>Doctor Clinical Profile Information</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Doctor Name (Admin Managed)</label>
                    <input type="text" value="{{ $doctor->name }}" disabled class="w-full text-xs rounded-xl border-gray-200 bg-gray-50 text-gray-700 py-2 px-3 font-bold">
                </div>

                <div>
                    <label class="block font-semibold text-gray-500 mb-1">Specialization (Admin Managed)</label>
                    <input type="text" value="{{ $doctor->specialization }}" disabled class="w-full text-xs rounded-xl border-gray-200 bg-gray-50 text-gray-700 py-2 px-3">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Medical Qualification / Degrees</label>
                    <input type="text" name="qualification" value="{{ old('qualification', $doctor->qualification) }}" placeholder="e.g. MBBS, FCPS (Cardiology)" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Clinic Room #</label>
                    <input type="text" name="room_number" value="{{ old('room_number', $doctor->room_number) }}" placeholder="e.g. Room 104" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $doctor->phone) }}" placeholder="e.g. 0300-1234567" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $doctor->email) }}" placeholder="doctor@clinic.com" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>

                <div class="sm:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1">Clinic / Residential Address</label>
                    <input type="text" name="address" value="{{ old('address', $doctor->address) }}" placeholder="Address" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs shadow-md shadow-teal-900/20 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Update Profile Details
                </button>
            </div>
        </form>
    </div>

    <!-- PASSWORD CHANGE FORM -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-6">
        <form action="{{ route('doctor.profile.password') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-key text-amber-600"></i>
                <span>Change Portal Account Password</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Current Password *</label>
                    <input type="password" name="current_password" required placeholder="Enter current password" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">New Password (Min. 6) *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Enter new password" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>

                <div>
                    <label class="block font-bold text-gray-700 mb-1">Confirm New Password *</label>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="Re-type new password" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl font-bold text-xs shadow-md shadow-amber-900/20 transition">
                    <i class="fa-solid fa-shield-check mr-2"></i> Update Account Password
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
