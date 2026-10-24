@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- HEADER & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('doctors.index') }}" class="hover:underline">Doctors</a>
                <span>/</span>
                <span>{{ $doctor->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-user-doctor text-blue-600"></i>
                <span>{{ $doctor->name }}</span>
                @if($doctor->status === 'active' || $doctor->is_active)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        <i class="fa-solid fa-circle text-[6px] mr-1.5 text-emerald-500"></i> Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-200">
                        <i class="fa-solid fa-circle text-[6px] mr-1.5 text-gray-400"></i> Inactive
                    </span>
                @endif
            </h1>
            <p class="text-xs text-gray-500 mt-1">{{ $doctor->specialization ?: 'General Physician' }} {{ $doctor->qualification ? '• '.$doctor->qualification : '' }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('doctors.edit', $doctor->id) }}" class="inline-flex items-center px-3.5 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-lg transition shadow-2xs">
                <i class="fa-solid fa-pen-to-square mr-1.5"></i>
                Edit Doctor
            </a>

            <form action="{{ route('doctors.status', $doctor->id) }}" method="POST" class="inline">
                @csrf
                @method('PATCH')
                <button type="submit" class="inline-flex items-center px-3.5 py-2 {{ $doctor->status === 'active' ? 'bg-slate-700 hover:bg-slate-800 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }} text-xs font-bold rounded-lg transition shadow-2xs">
                    <i class="fa-solid {{ $doctor->status === 'active' ? 'fa-user-slash' : 'fa-user-check' }} mr-1.5"></i>
                    {{ $doctor->status === 'active' ? 'Deactivate Doctor' : 'Activate Doctor' }}
                </button>
            </form>

            <form action="{{ route('doctors.destroy', $doctor->id) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete or deactivate this doctor?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-lg transition">
                    <i class="fa-solid fa-trash mr-1.5"></i>
                    Delete
                </button>
            </form>

            <a href="{{ route('doctors.index') }}" class="inline-flex items-center px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                Doctors List
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                <span class="font-semibold">{{ session('warning') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- DOCTOR DETAILS CARD (Section 8) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                Doctor Profile Information
            </h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-100">
                Revenue Split Configured
            </span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-gray-400 block font-medium">Specialization</span>
                <span class="font-bold text-gray-800 text-sm">{{ $doctor->specialization ?: 'General Physician' }}</span>
            </div>

            <div>
                <span class="text-gray-400 block font-medium">Qualification</span>
                <span class="font-bold text-gray-800 text-sm">{{ $doctor->qualification ?: '—' }}</span>
            </div>

            <div>
                <span class="text-gray-400 block font-medium">Gender</span>
                <span class="font-bold text-gray-800 text-sm">{{ $doctor->gender ?: '—' }}</span>
            </div>

            <div>
                <span class="text-gray-400 block font-medium">Phone</span>
                <span class="font-mono font-semibold text-gray-800">{{ $doctor->phone ?: '—' }}</span>
            </div>

            <div>
                <span class="text-gray-400 block font-medium">Email</span>
                <span class="font-semibold text-gray-800">{{ $doctor->email ?: '—' }}</span>
            </div>

            <div class="sm:col-span-3">
                <span class="text-gray-400 block font-medium">Address</span>
                <span class="font-semibold text-gray-800">{{ $doctor->address ?: '—' }}</span>
            </div>

            @if($doctor->notes)
            <div class="sm:col-span-4 pt-2 border-t border-gray-50">
                <span class="text-gray-400 block font-medium">Internal Notes</span>
                <p class="text-gray-700 italic">{{ $doctor->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Consultation & Revenue Share Profile Configuration -->
        <div class="mt-4 pt-4 border-t border-blue-50 bg-gradient-to-r from-blue-50/50 to-indigo-50/40 p-4 rounded-xl border border-blue-100">
            <div class="flex items-center space-x-2 mb-3">
                <i class="fa-solid fa-scale-balanced text-blue-600 text-sm"></i>
                <h4 class="text-xs font-bold text-blue-950 uppercase tracking-wide">Consultation & Revenue Split Settings</h4>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
                <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                    <span class="text-[10px] font-bold text-gray-400 uppercase block">Consultation Fee</span>
                    <span class="text-base font-black text-gray-900 font-mono">PKR {{ number_format($doctor->consultation_fee, 0) }}</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                    <span class="text-[10px] font-bold text-blue-600 uppercase block">Doctor Share %</span>
                    <span class="text-base font-black text-blue-700 font-mono">{{ (float)$doctor->doctor_share_percentage }}%</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-blue-100 shadow-2xs">
                    <span class="text-[10px] font-bold text-indigo-600 uppercase block">Hospital Share %</span>
                    <span class="text-base font-black text-indigo-700 font-mono">{{ (float)$doctor->hospital_share_percentage }}%</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-emerald-100 shadow-2xs">
                    <span class="text-[10px] font-bold text-emerald-600 uppercase block">Doctor / Visit</span>
                    <span class="text-base font-black text-emerald-700 font-mono">PKR {{ number_format($doctor->doctor_share_amount, 2) }}</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-200 shadow-2xs">
                    <span class="text-[10px] font-bold text-slate-600 uppercase block">Hospital / Visit</span>
                    <span class="text-base font-black text-slate-800 font-mono">PKR {{ number_format($doctor->hospital_share_amount, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- DOCTOR PORTAL LOGIN ACCOUNT (Admin Management) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                    <span>Doctor Portal Login & Security Account</span>
                </h3>
                <p class="text-xs text-gray-500">Manage portal access credentials for Dr. {{ $doctor->name }}.</p>
            </div>
            @if($doctor->user)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <i class="fa-solid fa-circle-check mr-1.5 text-indigo-600"></i> Account Linked: {{ $doctor->user->email }}
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                    <i class="fa-solid fa-triangle-exclamation mr-1.5 text-amber-600"></i> No Login Account Linked
                </span>
            @endif
        </div>

        @if($errors->any())
            <div class="p-3.5 rounded-lg bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                <p class="font-bold mb-1">Please fix the following issues:</p>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($doctor->user)
            <!-- LINKED ACCOUNT DETAILS & ACTIONS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                <div>
                    <span class="text-gray-400 block font-medium">Account Name</span>
                    <span class="font-bold text-gray-800 text-sm">{{ $doctor->user->name }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block font-medium">Login Email</span>
                    <span class="font-bold text-indigo-700 font-mono text-sm">{{ $doctor->user->email }}</span>
                </div>
                <div>
                    <span class="text-gray-400 block font-medium">Assigned System Role</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-bold bg-indigo-100 text-indigo-800 text-xs">
                        {{ $doctor->user->role }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-2">
                <!-- RESET PASSWORD FORM -->
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 space-y-3">
                    <h4 class="text-xs font-bold text-gray-700 flex items-center space-x-1.5">
                        <i class="fa-solid fa-key text-amber-600"></i>
                        <span>Reset Account Password</span>
                    </h4>
                    <form action="{{ route('admin.doctors.account.reset-password', $doctor->id) }}" method="POST" class="space-y-3">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">New Password</label>
                                <input type="password" name="password" required minlength="6" placeholder="Min. 6 characters" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-gray-600 mb-1">Confirm New Password</label>
                                <input type="password" name="password_confirmation" required minlength="6" placeholder="Re-type password" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                        </div>
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-lock mr-1.5"></i> Update Password
                        </button>
                    </form>
                </div>

                <!-- UNLINK ACCOUNT -->
                <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200 flex flex-col justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-rose-700 flex items-center space-x-1.5 mb-1.5">
                            <i class="fa-solid fa-link-slash"></i>
                            <span>Unlink Portal Login Account</span>
                        </h4>
                        <p class="text-xs text-gray-500 leading-relaxed">
                            Unlinking removes portal login capability for this doctor profile. Historical patient visits, prescriptions, and financial ledger records remain intact.
                        </p>
                    </div>
                    <form action="{{ route('admin.doctors.account.unlink', $doctor->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to unlink the login account for this doctor? The doctor will no longer be able to log in to the Doctor Portal.');" class="pt-3">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                            <i class="fa-solid fa-unlink mr-1.5"></i> Unlink Login Account
                        </button>
                    </form>
                </div>
            </div>
        @else
            <!-- CREATE OR LINK TABS -->
            <div x-data="{ tab: 'create' }" class="space-y-4">
                <div class="flex border-b border-gray-200 text-xs font-semibold">
                    <button type="button" @click="tab = 'create'" :class="tab === 'create' ? 'border-b-2 border-indigo-600 text-indigo-700 font-bold pb-2 px-4' : 'text-gray-500 hover:text-gray-700 pb-2 px-4'">
                        <i class="fa-solid fa-user-plus mr-1"></i> Option A: Create New Login Account
                    </button>
                    <button type="button" @click="tab = 'link'" :class="tab === 'link' ? 'border-b-2 border-indigo-600 text-indigo-700 font-bold pb-2 px-4' : 'text-gray-500 hover:text-gray-700 pb-2 px-4'">
                        <i class="fa-solid fa-link mr-1"></i> Option B: Link Existing Account
                    </button>
                </div>

                <!-- OPTION A: CREATE NEW USER -->
                <div x-show="tab === 'create'" class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <form action="{{ route('admin.doctors.account.create-or-link', $doctor->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="action_type" value="create">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', 'Dr. ' . $doctor->name) }}" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Portal Login Email</label>
                                <input type="email" name="email" value="{{ old('email', $doctor->email) }}" required placeholder="doctor@hospital.com" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Password</label>
                                <input type="password" name="password" required minlength="6" placeholder="Min. 6 characters" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                            <div>
                                <label class="block font-semibold text-gray-700 mb-1">Confirm Password</label>
                                <input type="password" name="password_confirmation" required minlength="6" placeholder="Confirm password" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                            </div>
                        </div>
                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                                <i class="fa-solid fa-user-shield mr-1.5"></i> Create & Link Doctor Account
                            </button>
                        </div>
                    </form>
                </div>

                <!-- OPTION B: LINK EXISTING USER -->
                <div x-show="tab === 'link'" style="display: none;" class="bg-gray-50/70 p-4 rounded-xl border border-gray-200">
                    <form action="{{ route('admin.doctors.account.create-or-link', $doctor->id) }}" method="POST" class="space-y-3">
                        @csrf
                        <input type="hidden" name="action_type" value="link">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-700 mb-1">Select Existing System User</label>
                                <select name="existing_user_id" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-1.5 px-3">
                                    <option value="">-- Choose User Account --</option>
                                    @foreach($availableUsers as $u)
                                        <option value="{{ $u->id }}">
                                            {{ $u->name }} ({{ $u->email }}) [Role: {{ $u->role }}]
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                                    <i class="fa-solid fa-link mr-1.5"></i> Link Selected User
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>

    <!-- DOCTOR REVENUE SHARE & PAYABLE LEDGER (User Spec Section 9) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-gray-100 pb-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                    <i class="fa-solid fa-wallet text-emerald-600"></i>
                    <span>Doctor Revenue Share & Payable Ledger</span>
                </h3>
                <p class="text-xs text-gray-500">Live ledger balances computed from completed and collected consultations.</p>
            </div>
            @if(Route::has('doctor-settlements.create'))
            <a href="{{ route('doctor-settlements.create', ['doctor_id' => $doctor->id]) }}" class="inline-flex items-center px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-2xs transition">
                <i class="fa-solid fa-money-bill-transfer mr-1.5"></i>
                Pay Doctor / Settlement
            </a>
            @endif
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                <span class="text-[11px] font-bold text-gray-500 uppercase block">Total Patients</span>
                <span class="text-xl font-black text-gray-900 font-mono">{{ $totalPatients }}</span>
                <span class="text-[10px] text-gray-400 block">{{ $totalTokens }} visits</span>
            </div>

            <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100">
                <span class="text-[11px] font-bold text-emerald-700 uppercase block">Paid Patients</span>
                <span class="text-xl font-black text-emerald-900 font-mono">{{ $paidPatients }}</span>
                <span class="text-[10px] text-emerald-600 block">Active paid visits</span>
            </div>

            <div class="p-3 bg-purple-50/50 rounded-xl border border-purple-100">
                <span class="text-[11px] font-bold text-purple-700 uppercase block">Free Patients</span>
                <span class="text-xl font-black text-purple-900 font-mono">{{ $freePatients }}</span>
                <span class="text-[10px] text-purple-600 block">Complimentary</span>
            </div>

            <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                <span class="text-[11px] font-bold text-blue-700 uppercase block">Total Doctor Fees</span>
                <span class="text-xl font-black text-blue-900 font-mono">PKR {{ number_format($totalDoctorFees, 2) }}</span>
                <span class="text-[10px] text-blue-600 block">Gross consultation fee</span>
            </div>

            <div class="p-3 bg-teal-50/60 rounded-xl border border-teal-100">
                <span class="text-[11px] font-bold text-teal-700 uppercase block">Total Collection</span>
                <span class="text-xl font-black text-teal-900 font-mono">PKR {{ number_format($totalCollection, 2) }}</span>
                <span class="text-[10px] text-teal-600 block">Cash collected</span>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-2 border-t border-gray-100">
            <div class="p-2.5 bg-emerald-50/40 rounded-lg border border-emerald-100/60">
                <span class="text-[10px] font-bold text-emerald-700 uppercase block">Doctor Share</span>
                <span class="text-base font-black text-emerald-800 font-mono">PKR {{ number_format($totalEarned, 2) }}</span>
            </div>

            <div class="p-2.5 bg-indigo-50/40 rounded-lg border border-indigo-100/60">
                <span class="text-[10px] font-bold text-indigo-700 uppercase block">Paid to Doctor</span>
                <span class="text-base font-black text-indigo-800 font-mono">PKR {{ number_format($totalPaid, 2) }}</span>
            </div>

            <div class="p-2.5 bg-amber-50/50 rounded-lg border border-amber-200/70">
                <span class="text-[10px] font-bold text-amber-800 uppercase block">Remaining Payable</span>
                <span class="text-base font-black text-amber-900 font-mono">PKR {{ number_format($currentPayable, 2) }}</span>
            </div>

            <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                <span class="text-[10px] font-bold text-slate-700 uppercase block">Doctor Share %</span>
                <span class="text-base font-black text-slate-900 font-mono">{{ (float)$doctor->doctor_share_percentage }}%</span>
            </div>
        </div>
    </div>

    <!-- RECENT OPD VISITS TABLE (Section 8) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-base flex items-center space-x-2">
                <i class="fa-solid fa-list-ol text-blue-600"></i>
                <span>Recent OPD Visits for {{ $doctor->name }}</span>
            </h3>
            <span class="text-xs text-gray-500 font-semibold bg-gray-50 px-3 py-1 rounded-full border border-gray-200">
                {{ $tokens->total() }} Visits Recorded
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-gray-200">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Token</th>
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-4">Time</th>
                        <th class="py-3 px-4">Patient</th>
                        <th class="py-3 px-4 text-right">Doctor Fee</th>
                        <th class="py-3 px-4 text-center">Fee Type</th>
                        <th class="py-3 px-4 text-right">Collected</th>
                        <th class="py-3 px-4 text-center">Doc %</th>
                        <th class="py-3 px-4 text-right">Doc Share</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($tokens as $t)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-mono font-bold text-blue-600 text-sm">
                                #{{ $t->formatted_token_number }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 whitespace-nowrap">
                                {{ Carbon\Carbon::parse($t->token_date)->format('d M Y') }}
                            </td>
                            <td class="py-3 px-4 font-mono text-gray-600 whitespace-nowrap">
                                {{ format_app_datetime($t->created_at, 'h:i A') }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-gray-900 block">{{ $t->patient ? $t->patient->name : 'Walk-in' }}</span>
                                <span class="text-[11px] text-gray-500 font-mono">{{ $t->patient ? $t->patient->patient_number : '' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-semibold text-gray-700">
                                PKR {{ number_format($t->consultation_fee, 0) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if(strtolower($t->payment_type) === 'free')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 uppercase border border-purple-200">
                                        Free
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase border border-blue-200">
                                        Paid
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono whitespace-nowrap">
                                @if(strtolower($t->payment_type) === 'free' || floatval($t->charged_amount) == 0)
                                    <span class="font-black text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                        FREE
                                    </span>
                                @else
                                    <span class="font-bold text-emerald-700">
                                        PKR {{ number_format($t->charged_amount, 0) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-blue-700">
                                {{ (float)($t->doctor_share_percentage ?? $doctor->doctor_share_percentage ?? 70) }}%
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-black text-emerald-700 whitespace-nowrap">
                                PKR {{ number_format($t->doctor_share_amount ?? 0, 2) }}
                            </td>
                            <td class="py-3 px-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                    {{ $t->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : ($t->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ $t->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-400">
                                No OPD visits recorded for this doctor yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tokens->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $tokens->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
