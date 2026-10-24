@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Prescriptions</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-prescription text-teal-600"></i>
                <span>Doctor Prescriptions</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Issued prescriptions under Dr. {{ $doctor->name }} (Total: {{ $totalPrescriptions }})
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('doctor.prescriptions.create') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-plus"></i>
                <span>Write New Prescription</span>
            </a>
        </div>
    </div>

    <!-- SEARCH & PRESCRIPTIONS TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden space-y-4">
        
        <!-- Search bar -->
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form action="{{ route('doctor.prescriptions.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by RX Number, Patient Name, or Diagnosis..." class="w-full text-xs pl-9 pr-4 py-2 rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Search Prescriptions
                </button>
                @if($search)
                    <a href="{{ route('doctor.prescriptions.index') }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition inline-flex items-center justify-center">
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
                        <th class="py-3 px-4">RX #</th>
                        <th class="py-3 px-4">Date Issued</th>
                        <th class="py-3 px-4">Patient</th>
                        <th class="py-3 px-4">Diagnosis</th>
                        <th class="py-3 px-4 text-center">Medicines</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($prescriptions as $rx)
                    <tr class="hover:bg-slate-50/60 transition">
                        <!-- RX # -->
                        <td class="py-3.5 px-4 font-mono font-bold text-teal-700 text-sm">
                            <a href="{{ route('doctor.prescriptions.show', $rx->id) }}" class="hover:underline">
                                {{ $rx->prescription_number }}
                            </a>
                        </td>

                        <!-- Date -->
                        <td class="py-3.5 px-4 text-gray-600 whitespace-nowrap">
                            <span class="font-semibold block text-gray-900">{{ format_app_datetime($rx->created_at, 'd M, Y') }}</span>
                            <span class="text-[10px] text-gray-400">{{ format_app_datetime($rx->created_at, 'h:i A') }}</span>
                        </td>

                        <!-- Patient -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('doctor.patients.show', $rx->patient_id) }}" class="font-bold text-gray-900 hover:text-teal-600 hover:underline block text-sm">
                                {{ $rx->patient->name }}
                            </a>
                            <span class="text-[11px] text-gray-400 font-mono">{{ $rx->patient->patient_number }} • {{ $rx->patient->gender ?: '—' }}, {{ $rx->patient->age ? $rx->patient->age.' yrs' : '—' }}</span>
                        </td>

                        <!-- Diagnosis -->
                        <td class="py-3.5 px-4">
                            @if($rx->diagnosis)
                                <span class="font-semibold text-gray-800">{{ $rx->diagnosis }}</span>
                            @else
                                <span class="text-gray-400 italic">None specified</span>
                            @endif
                        </td>

                        <!-- Items Count -->
                        <td class="py-3.5 px-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-100 text-slate-800">
                                {{ $rx->items->count() }} items
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-check mr-1 text-[8px]"></i> Issued
                            </span>
                        </td>

                        <!-- Actions -->
                        <td class="py-3.5 px-4 text-right space-x-1">
                            <a href="{{ route('doctor.prescriptions.show', $rx->id) }}" class="inline-flex items-center px-2.5 py-1.5 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 rounded-lg font-bold text-xs transition">
                                <i class="fa-solid fa-eye mr-1"></i> View
                            </a>
                            <a href="{{ route('doctor.prescriptions.print', $rx->id) }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold text-xs transition">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400 text-xs">
                            <i class="fa-solid fa-prescription text-4xl mb-2 text-gray-300 block"></i>
                            <p class="font-semibold">No prescriptions found.</p>
                            <p class="text-[11px] text-gray-400 mt-1">Prescriptions written by Dr. {{ $doctor->name }} will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($prescriptions->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $prescriptions->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
