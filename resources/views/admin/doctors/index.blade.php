@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- HEADER & TOP ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/dashboard" class="hover:underline">Dashboard</a>
                <span>/</span>
                <span>Hospital</span>
                <span>/</span>
                <span>Doctors</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-user-doctor text-blue-600"></i>
                <span>Doctor Management</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Manage doctors and OPD consultation fees</p>
        </div>

        <div>
            <a href="{{ route('doctors.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-lg shadow-sm transition">
                <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
                + Register Doctor
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

    <!-- SUMMARY KPI CARDS (Section 16) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Doctors</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1 font-mono">{{ $totalDoctors }}</h3>
                <span class="text-[11px] text-gray-500">Registered staff</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Doctors</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">{{ $activeDoctors }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium">Available in OPD Queue</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Inactive Doctors</p>
                <h3 class="text-2xl font-black text-slate-500 mt-1 font-mono">{{ $inactiveDoctors }}</h3>
                <span class="text-[11px] text-slate-500">Deactivated from OPD</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-slash"></i>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR (Section 5 & 6) -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <form method="GET" action="{{ route('doctors.index') }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Search -->
            <div class="relative flex-1 max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search doctor by name, specialization, qualification, phone..." 
                    class="w-full pl-9 pr-4 py-2 rounded-lg border border-gray-200 text-xs text-gray-800 placeholder:text-gray-400 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 text-xs">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
            </div>

            <!-- Status Filter Pills -->
            <div class="flex items-center space-x-2">
                <span class="text-xs text-gray-500 font-semibold">Status:</span>
                <div class="inline-flex p-1 bg-gray-100 rounded-lg text-xs font-semibold">
                    <a href="{{ route('doctors.index', ['status' => 'all', 'search' => $search]) }}" 
                       class="px-3 py-1 rounded-md transition {{ $statusFilter === 'all' ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        All
                    </a>
                    <a href="{{ route('doctors.index', ['status' => 'active', 'search' => $search]) }}" 
                       class="px-3 py-1 rounded-md transition {{ $statusFilter === 'active' ? 'bg-white text-emerald-700 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        Active
                    </a>
                    <a href="{{ route('doctors.index', ['status' => 'inactive', 'search' => $search]) }}" 
                       class="px-3 py-1 rounded-md transition {{ $statusFilter === 'inactive' ? 'bg-white text-slate-700 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        Inactive
                    </a>
                </div>

                @if(!empty($search) || $statusFilter !== 'active')
                    <a href="{{ route('doctors.index') }}" class="p-2 text-gray-400 hover:text-gray-600 text-xs" title="Reset filter">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- DOCTOR LIST TABLE (Section 4) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-gray-800 text-base">Doctor List</h3>
            <span class="text-xs text-gray-500 font-semibold bg-gray-50 px-3 py-1 rounded-full border border-gray-200">
                {{ $doctors->total() }} Doctors Found
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs divide-y divide-gray-200">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Doctor</th>
                        <th class="py-3 px-4">Specialization</th>
                        <th class="py-3 px-4">Qualification</th>
                        <th class="py-3 px-4">Phone</th>
                        <th class="py-3 px-4 text-right">Consultation Fee</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($doctors as $index => $doc)
                        <tr class="hover:bg-gray-50/80 transition">
                            <!-- # -->
                            <td class="py-3 px-4 font-mono text-gray-400 font-semibold">
                                {{ $doctors->firstItem() + $index }}
                            </td>

                            <!-- Doctor -->
                            <td class="py-3 px-4">
                                <a href="{{ route('doctors.show', $doc->id) }}" class="font-bold text-gray-900 hover:text-blue-600 text-sm block">
                                    {{ $doc->name }}
                                </a>
                                @if($doc->gender)
                                    <span class="text-[11px] text-gray-400">{{ $doc->gender }}</span>
                                @endif
                            </td>

                            <!-- Specialization -->
                            <td class="py-3 px-4 font-semibold text-gray-700">
                                {{ $doc->specialization ?: 'General Physician' }}
                            </td>

                            <!-- Qualification -->
                            <td class="py-3 px-4 text-gray-600 font-medium">
                                {{ $doc->qualification ?: '—' }}
                            </td>

                            <!-- Phone -->
                            <td class="py-3 px-4 font-mono text-gray-600">
                                {{ $doc->phone ?: '—' }}
                            </td>

                            <!-- Consultation Fee -->
                            <td class="py-3 px-4 text-right font-mono font-bold text-emerald-700 text-sm">
                                PKR {{ number_format($doc->consultation_fee, 0) }}
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-4 text-center">
                                @if($doc->status === 'active' || $doc->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase border border-emerald-200">
                                        <i class="fa-solid fa-circle text-[6px] mr-1.5 text-emerald-500"></i> Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600 uppercase border border-gray-200">
                                        <i class="fa-solid fa-circle text-[6px] mr-1.5 text-gray-400"></i> Inactive
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-4 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('doctors.show', $doc->id) }}" class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="View Doctor & OPD Stats">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <a href="{{ route('doctors.edit', $doc->id) }}" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Edit Doctor">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>

                                    <form action="{{ route('doctors.status', $doc->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-1.5 {{ $doc->status === 'active' ? 'text-slate-400 hover:text-slate-700' : 'text-emerald-600 hover:text-emerald-800' }} hover:bg-gray-100 rounded-lg transition" title="{{ $doc->status === 'active' ? 'Deactivate Doctor' : 'Activate Doctor' }}">
                                            <i class="fa-solid {{ $doc->status === 'active' ? 'fa-toggle-on text-emerald-600 text-sm' : 'fa-toggle-off text-gray-400 text-sm' }}"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-400">
                                <i class="fa-solid fa-user-doctor text-2xl block mb-2 text-gray-300"></i>
                                No doctors found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($doctors->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $doctors->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
