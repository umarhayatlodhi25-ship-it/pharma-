@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Patients</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-hospital-user text-teal-600"></i>
                <span>Authorized Patient Directory</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Displaying only patients who have recorded consultations with Dr. {{ $doctor->name }} (Total: {{ $totalDoctorPatients }})
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-teal-50 text-teal-800 border border-teal-200 text-xs font-bold">
                <i class="fa-solid fa-shield-check mr-1.5 text-teal-600"></i> Doctor-Isolated Medical Access
            </span>
        </div>
    </div>

    <!-- SEARCH & PATIENT LIST TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden space-y-4">
        
        <!-- Search bar -->
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form action="{{ route('doctor.patients.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search authorized patients by Name, PT Number, Phone, or CNIC..." class="w-full text-xs pl-9 pr-4 py-2 rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Search Patients
                </button>
                @if($search)
                    <a href="{{ route('doctor.patients.index') }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition inline-flex items-center justify-center">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 uppercase font-semibold text-[10px] tracking-wider">
                        <th class="py-3 px-4">Patient ID</th>
                        <th class="py-3 px-4">Patient Name</th>
                        <th class="py-3 px-4">Age / Gender</th>
                        <th class="py-3 px-4">Contact Phone</th>
                        <th class="py-3 px-4">CNIC</th>
                        <th class="py-3 px-4 text-center">Visits With You</th>
                        <th class="py-3 px-4 text-right">Medical Profile</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($patients as $patient)
                    <tr class="hover:bg-slate-50/60 transition">
                        <!-- Patient ID -->
                        <td class="py-3.5 px-4 font-mono font-bold text-teal-700">
                            {{ $patient->patient_number }}
                        </td>

                        <!-- Name -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('doctor.patients.show', $patient->id) }}" class="font-bold text-gray-900 hover:text-teal-600 block text-sm">
                                {{ $patient->name }}
                            </a>
                            @if($patient->father_husband_name)
                                <span class="text-[11px] text-gray-400">S/O, D/O, W/O: {{ $patient->father_husband_name }}</span>
                            @endif
                        </td>

                        <!-- Age / Gender -->
                        <td class="py-3.5 px-4 text-gray-700">
                            {{ $patient->gender ?: '—' }}, {{ $patient->age ? $patient->age.' yrs' : '—' }}
                        </td>

                        <!-- Phone -->
                        <td class="py-3.5 px-4 font-mono text-gray-700">
                            {{ $patient->phone ?: '—' }}
                        </td>

                        <!-- CNIC -->
                        <td class="py-3.5 px-4 font-mono text-gray-500">
                            {{ $patient->cnic ?: '—' }}
                        </td>

                        <!-- Visits Count with this doctor -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-50 text-teal-800 border border-teal-200 font-mono">
                                {{ $patient->tokens_count }}
                            </span>
                        </td>

                        <!-- Action -->
                        <td class="py-3.5 px-4 text-right">
                            <a href="{{ route('doctor.patients.show', $patient->id) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                                <i class="fa-solid fa-folder-open mr-1.5"></i> View History
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400 text-xs">
                            <i class="fa-solid fa-users-slash text-4xl mb-2 text-gray-300 block"></i>
                            <p class="font-semibold">No authorized patients found.</p>
                            <p class="text-[11px] text-gray-400 mt-1">Patients will appear here once an OPD consultation token is assigned to you.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
