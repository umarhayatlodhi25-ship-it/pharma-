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
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider border-b border-gray-100 pb-2">
            Doctor Profile Information
        </h3>

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
                <span class="text-gray-400 block font-medium">Consultation Fee</span>
                <span class="font-black text-emerald-700 text-base font-mono">PKR {{ number_format($doctor->consultation_fee, 0) }}</span>
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

            <div class="sm:col-span-2">
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
    </div>

    <!-- OPD SUMMARY STATISTICS (Section 8) -->
    <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">
            OPD Summary
        </h3>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-bold text-gray-400 uppercase block">Total Patients</span>
                <span class="text-xl font-black text-gray-800 font-mono">{{ $totalPatients }}</span>
                <span class="text-[10px] text-gray-400 block">Unique individuals</span>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-bold text-emerald-500 uppercase block">Paid Patients</span>
                <span class="text-xl font-black text-emerald-600 font-mono">{{ $paidPatients }}</span>
                <span class="text-[10px] text-emerald-600 block">Paying visits</span>
            </div>

            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                <span class="text-[11px] font-bold text-purple-500 uppercase block">Free Patients</span>
                <span class="text-xl font-black text-purple-600 font-mono">{{ $freePatients }}</span>
                <span class="text-[10px] text-purple-600 block">Exempted visits</span>
            </div>

            <div class="bg-white p-4 rounded-xl border border-blue-100 bg-blue-50/20 shadow-sm">
                <span class="text-[11px] font-bold text-blue-700 uppercase block">Total Doctor Fees</span>
                <span class="text-xl font-black text-blue-700 font-mono">PKR {{ number_format($totalDoctorFees, 0) }}</span>
                <span class="text-[10px] text-blue-500 block">Scheduled fees</span>
            </div>

            <div class="bg-white p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 shadow-sm">
                <span class="text-[11px] font-bold text-emerald-700 uppercase block">Total Collection</span>
                <span class="text-xl font-black text-emerald-700 font-mono">PKR {{ number_format($totalCollection, 0) }}</span>
                <span class="text-[10px] text-emerald-600 block">Net collected</span>
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
                        <th class="py-3 px-4">Free Reason</th>
                        <th class="py-3 px-4 text-right">Final Amount</th>
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
                                {{ $t->created_at ? $t->created_at->format('h:i A') : '—' }}
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
                            <td class="py-3 px-4 text-gray-600">
                                {{ $t->free_reason ?: '—' }}
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
