@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Card -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patient Management</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Manage registered patients</p>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('patients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-sm hover:shadow transition flex items-center space-x-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>+ Register Patient</span>
            </a>
        </div>
    </div>

    <!-- Flash Alert -->
    @if(session('success') || session('message'))
        <div class="bg-emerald-50 border border-emerald-200/80 text-emerald-800 px-4 py-3.5 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') ?? session('message') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Search & Filter Bar -->
    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('patients.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </span>
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search ?? '' }}" 
                    placeholder="Search by Patient ID (e.g. PT-00001), Name, Phone, or CNIC..." 
                    class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition"
                >
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-semibold text-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-filter text-xs"></i>
                    <span>Search</span>
                </button>
                @if(!empty($search))
                    <a href="{{ route('patients.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-semibold text-sm transition flex items-center space-x-1.5" title="Clear Search">
                        <i class="fa-solid fa-rotate-left text-xs"></i>
                        <span>Reset</span>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Patients Data Table -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient ID</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient Name</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Father/Husband Name</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Age</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Gender</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Phone</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">CNIC</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Registration Date</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($patients as $patient)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3.5 text-slate-500 text-xs font-medium">
                                {{ $loop->iteration + ($patients->currentPage() - 1) * $patients->perPage() }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $patient->patient_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-900 whitespace-nowrap">
                                {{ $patient->name }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">
                                {{ $patient->father_husband_name ?: '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-slate-700 whitespace-nowrap">
                                <span class="font-medium">{{ $patient->age }}</span> <span class="text-xs text-slate-400">Yrs</span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($patient->gender === 'Male')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fa-solid fa-mars mr-1 text-[10px]"></i> Male
                                    </span>
                                @elseif($patient->gender === 'Female')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                        <i class="fa-solid fa-venus mr-1 text-[10px]"></i> Female
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                        <i class="fa-solid fa-genderless mr-1 text-[10px]"></i> {{ $patient->gender }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap">
                                @if($patient->phone)
                                    <span class="inline-flex items-center text-slate-700">
                                        <i class="fa-solid fa-phone text-slate-400 mr-1.5 text-[11px]"></i>
                                        {{ $patient->phone }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-600 text-xs whitespace-nowrap">
                                @if($patient->cnic)
                                    <span class="inline-flex items-center text-slate-700 font-mono">
                                        <i class="fa-regular fa-id-card text-slate-400 mr-1.5 text-[11px]"></i>
                                        {{ $patient->cnic }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap">
                                {{ $patient->created_at ? $patient->created_at->format('d M, Y h:i A') : '—' }}
                            </td>
                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <a href="{{ route('patients.show', $patient->id) }}" class="inline-flex items-center space-x-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold border border-blue-200 hover:border-blue-600 transition shadow-2xs">
                                    <i class="fa-solid fa-eye text-[11px]"></i>
                                    <span>View</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-hospital-user"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-700">No patients found</p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            @if(!empty($search))
                                                No results match your search "{{ $search }}". Try another query or reset filter.
                                            @else
                                                Start by registering the first patient in the system.
                                            @endif
                                        </p>
                                    </div>
                                    <a href="{{ route('patients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition">
                                        + Register Patient
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
