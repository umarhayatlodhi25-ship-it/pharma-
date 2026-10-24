@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>OPD Queue</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-stethoscope text-teal-600"></i>
                <span>OPD Consultation Queue</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Manage today's patient queue assigned to Dr. {{ $doctor->name }} (Room: {{ $doctor->room_number ?: 'Not Set' }})
            </p>
        </div>

        <!-- Date Selector & Quick Refresh -->
        <form action="{{ route('doctor.queue.index') }}" method="GET" class="flex items-center space-x-2">
            <input type="date" name="date" value="{{ $targetDate }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-300 shadow-2xs py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
            @if($statusFilter !== 'all')
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif
            <button type="submit" class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-arrows-rotate"></i>
            </button>
        </form>
    </div>

    <!-- QUEUE SUMMARY FILTER CHIPS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'all', 'search' => $search]) }}" 
           class="p-3.5 rounded-xl border text-xs transition {{ $statusFilter === 'all' ? 'bg-teal-600 border-teal-600 text-white font-bold shadow-md shadow-teal-900/20' : 'bg-white border-gray-200 text-gray-700 hover:border-teal-400' }}">
            <div class="flex items-center justify-between">
                <span>All Tokens</span>
                <span class="font-mono text-base font-black {{ $statusFilter === 'all' ? 'text-white' : 'text-gray-900' }}">{{ $counts['total'] }}</span>
            </div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'waiting', 'search' => $search]) }}" 
           class="p-3.5 rounded-xl border text-xs transition {{ $statusFilter === 'waiting' ? 'bg-amber-500 border-amber-500 text-white font-bold shadow-md shadow-amber-900/20' : 'bg-white border-amber-200 text-amber-800 hover:border-amber-400' }}">
            <div class="flex items-center justify-between">
                <span>Waiting in Lobby</span>
                <span class="font-mono text-base font-black {{ $statusFilter === 'waiting' ? 'text-white' : 'text-amber-700' }}">{{ $counts['waiting'] }}</span>
            </div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'in_consultation', 'search' => $search]) }}" 
           class="p-3.5 rounded-xl border text-xs transition {{ $statusFilter === 'in_consultation' ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-md shadow-indigo-900/20' : 'bg-white border-indigo-200 text-indigo-800 hover:border-indigo-400' }}">
            <div class="flex items-center justify-between">
                <span>In Consultation</span>
                <span class="font-mono text-base font-black {{ $statusFilter === 'in_consultation' ? 'text-white' : 'text-indigo-700' }}">{{ $counts['in_consultation'] }}</span>
            </div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'completed', 'search' => $search]) }}" 
           class="p-3.5 rounded-xl border text-xs transition {{ $statusFilter === 'completed' ? 'bg-emerald-600 border-emerald-600 text-white font-bold shadow-md shadow-emerald-900/20' : 'bg-white border-emerald-200 text-emerald-800 hover:border-emerald-400' }}">
            <div class="flex items-center justify-between">
                <span>Completed</span>
                <span class="font-mono text-base font-black {{ $statusFilter === 'completed' ? 'text-white' : 'text-emerald-700' }}">{{ $counts['completed'] }}</span>
            </div>
        </a>
    </div>

    <!-- SEARCH & TABLE CONTAINER -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden space-y-4">
        
        <!-- Search bar -->
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form action="{{ route('doctor.queue.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <input type="hidden" name="date" value="{{ $targetDate }}">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <div class="relative flex-1">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by Token #, Patient Name, PT Number, or Phone..." class="w-full text-xs pl-9 pr-4 py-2 rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500">
                </div>
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Search Queue
                </button>
                @if($search)
                    <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => $statusFilter]) }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition inline-flex items-center justify-center">
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
                        <th class="py-3 px-4">Token #</th>
                        <th class="py-3 px-4">Time Issued</th>
                        <th class="py-3 px-4">Patient Details</th>
                        <th class="py-3 px-4">Payment</th>
                        <th class="py-3 px-4">Consultation Status</th>
                        <th class="py-3 px-4">Prescription</th>
                        <th class="py-3 px-4 text-right">Workflow Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tokens as $token)
                    <tr class="hover:bg-slate-50/60 transition {{ $token->status === 'in_consultation' ? 'bg-indigo-50/30' : '' }}">
                        <!-- Token # -->
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center justify-center w-9 h-9 rounded-xl font-mono text-sm font-black {{ $token->status === 'in_consultation' ? 'bg-indigo-600 text-white' : ($token->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-900 text-white') }} shadow-xs">
                                #{{ $token->token_number }}
                            </span>
                        </td>

                        <!-- Time Issued -->
                        <td class="py-3.5 px-4 text-gray-600 whitespace-nowrap">
                            <span class="font-semibold block text-gray-900">{{ format_app_datetime($token->created_at, 'h:i A') }}</span>
                            <span class="text-[10px] text-gray-400">{{ format_app_datetime($token->created_at, 'd M Y') }}</span>
                        </td>

                        <!-- Patient Info -->
                        <td class="py-3.5 px-4">
                            <a href="{{ route('doctor.patients.show', $token->patient_id) }}" class="font-bold text-gray-900 hover:text-teal-600 hover:underline block text-sm">
                                {{ $token->patient->name }}
                            </a>
                            <div class="flex items-center space-x-2 text-[11px] text-gray-500 mt-0.5">
                                <span class="font-mono bg-gray-100 px-1.5 py-0.2 rounded">{{ $token->patient->patient_number }}</span>
                                <span>•</span>
                                <span>{{ $token->patient->gender ?: '—' }}, {{ $token->patient->age ? $token->patient->age.' yrs' : '—' }}</span>
                                @if($token->patient->phone)
                                    <span>•</span>
                                    <span class="font-mono text-gray-600">{{ $token->patient->phone }}</span>
                                @endif
                            </div>
                        </td>

                        <!-- Payment -->
                        <td class="py-3.5 px-4">
                            @if($token->payment_type === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-check text-[8px] mr-1"></i> Paid (PKR {{ number_format($token->charged_amount, 0) }})
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Free Visit
                                </span>
                            @endif
                        </td>

                        <!-- Consultation Status -->
                        <td class="py-3.5 px-4">
                            @if($token->status === 'in_consultation')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200 animate-pulse">
                                    <i class="fa-solid fa-spinner fa-spin mr-1.5 text-[10px]"></i> In Consultation
                                </span>
                            @elseif($token->status === 'waiting')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    <i class="fa-solid fa-clock mr-1.5 text-[10px]"></i> Waiting
                                </span>
                            @elseif($token->status === 'called')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    <i class="fa-solid fa-bullhorn mr-1.5 text-[10px]"></i> Called
                                </span>
                            @elseif($token->status === 'completed')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    <i class="fa-solid fa-circle-check mr-1.5 text-[10px]"></i> Completed
                                </span>
                            @elseif($token->status === 'cancelled')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                    <i class="fa-solid fa-ban mr-1.5 text-[10px]"></i> Cancelled
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-700">
                                    {{ ucfirst($token->status) }}
                                </span>
                            @endif
                        </td>

                        <!-- Prescription -->
                        <td class="py-3.5 px-4">
                            @if($token->prescription)
                                <a href="{{ route('doctor.prescriptions.show', $token->prescription->id) }}" class="inline-flex items-center space-x-1 text-teal-700 font-bold hover:underline">
                                    <i class="fa-solid fa-prescription text-teal-600"></i>
                                    <span>{{ $token->prescription->prescription_number }}</span>
                                </a>
                            @else
                                <a href="{{ route('doctor.prescriptions.create', ['token_id' => $token->id]) }}" class="inline-flex items-center space-x-1 text-xs text-gray-400 hover:text-teal-600">
                                    <i class="fa-solid fa-plus text-[10px]"></i>
                                    <span>Write Rx</span>
                                </a>
                            @endif
                        </td>

                        <!-- Workflow Actions -->
                        <td class="py-3.5 px-4 text-right space-x-1">
                            @if($token->status === 'waiting' || $token->status === 'called')
                                <form action="{{ route('doctor.queue.call', $token->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                                        <i class="fa-solid fa-stethoscope mr-1.5"></i> Call Patient
                                    </button>
                                </form>
                            @endif

                            <a href="{{ route('doctor.consultations.show', $token->id) }}" class="inline-flex items-center px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                                <i class="fa-solid fa-pen-to-square mr-1.5"></i> Workspace
                            </a>

                            @if($token->status === 'in_consultation')
                                <form action="{{ route('doctor.queue.complete', $token->id) }}" method="POST" class="inline" onsubmit="return confirm('Complete consultation for Token #{{ $token->token_number }}?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                                        <i class="fa-solid fa-check mr-1"></i> Finish
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-gray-400 text-xs">
                            <i class="fa-solid fa-inbox text-4xl mb-2 text-gray-300 block"></i>
                            <p class="font-semibold">No tokens in this queue view.</p>
                            <p class="text-[11px] text-gray-400 mt-1">Tokens generated for Dr. {{ $doctor->name }} will appear here automatically.</p>
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
