@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>OPD Queue Management</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-user-doctor text-teal-600"></i>
                <span>Dr. {{ $doctor->name }}</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1 flex items-center gap-3">
                <span class="inline-flex items-center font-medium text-slate-700">
                    <i class="fa-solid fa-door-open text-teal-500 mr-1.5"></i> Room: <strong class="ml-1 text-slate-900">{{ $doctor->room_number ?? 'Consultation Room' }}</strong>
                </span>
                <span>•</span>
                <span class="inline-flex items-center font-medium text-slate-700">
                    <i class="fa-solid fa-hospital-user text-teal-500 mr-1.5"></i> Dept: <strong class="ml-1 text-slate-900">{{ $doctor->specialization ?? 'General OPD' }}</strong>
                </span>
                <span>•</span>
                <span>{{ $targetDate === $todayDate ? 'Today: ' . format_app_datetime($todayDate, 'd M, Y') : 'Date: ' . format_app_datetime($targetDate, 'd M, Y') }}</span>
            </p>
        </div>

        <!-- Period Toggle & Call Next Primary Button -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Call Next Patient Form -->
            <form action="{{ route('doctor.queue.call-next') }}" method="POST" class="inline">
                @csrf
                <button type="submit" 
                        {{ $counts['waiting'] == 0 ? 'disabled' : '' }}
                        class="px-5 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center gap-2 {{ $counts['waiting'] > 0 ? 'bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white shadow-teal-700/20 active:scale-95' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                    <i class="fa-solid fa-bullhorn text-amber-300"></i>
                    <span>CALL NEXT ({{ $counts['waiting'] }})</span>
                </button>
            </form>

            <!-- Quick Date Toggles -->
            <a href="{{ route('doctor.queue.index', ['date' => $todayDate, 'status' => $statusFilter]) }}"
               class="px-3 py-2 rounded-xl text-xs font-bold transition {{ $targetDate === $todayDate ? 'bg-slate-800 text-white shadow-xs' : 'bg-white border border-gray-200 text-gray-700 hover:bg-gray-50' }}">
                <i class="fa-solid fa-calendar-day mr-1"></i> Today
            </a>

            <!-- Specific Date Selector -->
            <form action="{{ route('doctor.queue.index') }}" method="GET" class="flex items-center space-x-1.5">
                <input type="date" name="date" value="{{ $targetDate === 'all' ? '' : $targetDate }}" onchange="this.form.submit()" class="text-xs rounded-xl border-gray-300 shadow-2xs py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
                @if($statusFilter !== 'all')
                    <input type="hidden" name="status" value="{{ $statusFilter }}">
                @endif
                <button type="submit" title="Refresh Queue" class="px-3 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-arrows-rotate"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- ACTIVE CONSULTATION & NEXT WAITING TOKENS DUAL PANEL -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- LEFT: CURRENT ACTIVE TOKEN (2 COLS) -->
        <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 via-slate-800 to-teal-950 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 pointer-events-none">
                <i class="fa-solid fa-stethoscope text-[180px]"></i>
            </div>

            <div class="flex items-center justify-between pb-4 border-b border-white/10">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full {{ $nowServing ? 'bg-emerald-400 animate-ping' : 'bg-gray-400' }}"></span>
                    <span class="text-xs uppercase font-bold tracking-wider text-teal-300">Current Consultation Desk</span>
                </div>
                @if($nowServing)
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $nowServing->status === 'in_consultation' ? 'bg-indigo-500/30 text-indigo-200 border border-indigo-400/30' : 'bg-amber-500/30 text-amber-200 border border-amber-400/30' }}">
                        {{ $nowServing->status === 'in_consultation' ? 'Consultation In Progress' : 'Called (Waiting Entry)' }}
                    </span>
                @else
                    <span class="text-xs text-slate-400">Desk Available</span>
                @endif
            </div>

            @if($nowServing)
                <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
                    <!-- Token Badge -->
                    <div class="text-center sm:text-left bg-white/5 backdrop-blur-xs p-4 rounded-xl border border-white/10">
                        <span class="text-[10px] text-teal-200 uppercase font-bold block">Token Number</span>
                        <div class="text-4xl font-mono font-black text-amber-300 mt-1">
                            #{{ $nowServing->formatted_token_number }}
                        </div>
                        <span class="text-[11px] text-slate-300 block mt-1">
                            Call Count: <strong class="text-white">{{ $nowServing->call_count }}</strong>
                        </span>
                    </div>

                    <!-- Patient Details -->
                    <div class="sm:col-span-2 space-y-1.5 pl-2">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-white">{{ $nowServing->patient->name }}</h2>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-white/10 text-teal-200">{{ $nowServing->patient->patient_number }}</span>
                        </div>
                        <p class="text-xs text-slate-300 flex items-center gap-3">
                            <span><i class="fa-solid fa-venus-mars mr-1 text-teal-400"></i>{{ $nowServing->patient->gender ?: 'N/A' }}</span>
                            <span>•</span>
                            <span><i class="fa-solid fa-cake-candles mr-1 text-teal-400"></i>{{ $nowServing->patient->age ? $nowServing->patient->age . ' Years' : 'Age N/A' }}</span>
                            @if($nowServing->patient->phone)
                                <span>•</span>
                                <span><i class="fa-solid fa-phone mr-1 text-teal-400"></i>{{ $nowServing->patient->phone }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-slate-400">
                            Issued: {{ format_app_datetime($nowServing->created_at, 'h:i A') }} 
                            @if($nowServing->called_at)
                                • Called: {{ format_app_datetime($nowServing->called_at, 'h:i A') }}
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Action Controls for Active Token -->
                <div class="mt-6 pt-4 border-t border-white/10 flex flex-wrap items-center gap-2.5">
                    <!-- Workspace / Examination -->
                    <a href="{{ route('doctor.consultations.show', $nowServing->id) }}" class="px-4 py-2 bg-teal-500 hover:bg-teal-600 text-slate-900 font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                        <i class="fa-solid fa-stethoscope"></i>
                        <span>Start / Open Workspace</span>
                    </a>

                    <!-- Recall Patient -->
                    <form action="{{ route('doctor.queue.recall', $nowServing->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/20 transition flex items-center gap-1.5">
                            <i class="fa-solid fa-volume-high text-amber-300"></i>
                            <span>Recall</span>
                        </button>
                    </form>

                    <!-- Complete Consultation -->
                    <form action="{{ route('doctor.queue.complete', $nowServing->id) }}" method="POST" class="inline" onsubmit="return confirm('Complete consultation for Token #{{ $nowServing->formatted_token_number }}?');">
                        @csrf
                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md transition flex items-center gap-1.5">
                            <i class="fa-solid fa-circle-check"></i>
                            <span>Complete</span>
                        </button>
                    </form>

                    <!-- Put on Hold -->
                    <button type="button" onclick="openActionModal('hold', '{{ $nowServing->id }}', '{{ $nowServing->formatted_token_number }}')" class="px-3.5 py-2 bg-amber-600/30 hover:bg-amber-600/50 text-amber-200 font-bold text-xs rounded-xl border border-amber-500/30 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold</span>
                    </button>

                    <!-- Skip Patient -->
                    <button type="button" onclick="openActionModal('skip', '{{ $nowServing->id }}', '{{ $nowServing->formatted_token_number }}')" class="px-3.5 py-2 bg-rose-600/30 hover:bg-rose-600/50 text-rose-200 font-bold text-xs rounded-xl border border-rose-500/30 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-forward-step"></i>
                        <span>Skip</span>
                    </button>
                </div>
            @else
                <div class="py-10 text-center">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/10 text-teal-300 text-2xl mb-3">
                        <i class="fa-solid fa-stethoscope"></i>
                    </div>
                    <h3 class="text-base font-bold text-white">No Patient Currently Active</h3>
                    <p class="text-xs text-slate-300 max-w-sm mx-auto mt-1">
                        There is no patient currently inside the consultation room. Click "CALL NEXT" above or pick a specific waiting patient below to begin.
                    </p>
                </div>
            @endif
        </div>

        <!-- RIGHT: NEXT 5 TOKENS IN QUEUE -->
        <div class="bg-white rounded-2xl p-5 border border-gray-200 shadow-sm flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                    <h3 class="text-xs uppercase font-bold text-gray-700 tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-list-ol text-teal-600"></i>
                        <span>Up Next in Lobby ({{ $counts['waiting'] }})</span>
                    </h3>
                    <span class="text-[11px] font-semibold text-teal-600">Next in sequence</span>
                </div>

                <div class="divide-y divide-gray-100 mt-2">
                    @forelse($nextTokens as $nxt)
                        <div class="py-2.5 flex items-center justify-between hover:bg-gray-50 rounded-lg px-2 transition">
                            <div class="flex items-center space-x-3">
                                <span class="font-mono text-xs font-black bg-slate-900 text-white w-8 h-8 rounded-lg flex items-center justify-center">
                                    #{{ $nxt->queue_sequence ?? $nxt->token_number }}
                                </span>
                                <div>
                                    <div class="text-xs font-bold text-gray-900 truncate max-w-[130px]">{{ $nxt->patient->name }}</div>
                                    <div class="text-[10px] text-gray-400">Waiting since {{ format_app_datetime($nxt->created_at, 'h:i A') }}</div>
                                </div>
                            </div>
                            <form action="{{ route('doctor.queue.call', $nxt->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-2.5 py-1 bg-teal-50 hover:bg-teal-600 text-teal-700 hover:text-white rounded-lg text-xs font-bold transition border border-teal-200 flex items-center gap-1">
                                    <i class="fa-solid fa-phone text-[10px]"></i>
                                    <span>Call</span>
                                </button>
                            </form>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-400 text-xs">
                            <i class="fa-solid fa-mug-hot text-2xl text-gray-300 mb-1.5 block"></i>
                            <span>No waiting patients in queue.</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <div class="pt-3 border-t border-gray-100 text-[11px] text-gray-500 text-center">
                Room: <strong class="text-gray-800">{{ $doctor->room_number ?? 'OPD Room' }}</strong> • Scope: <span class="capitalize font-semibold text-teal-700">{{ \App\Models\AccountProfile::current()->queue_scope ?? 'Doctor-wise' }}</span>
            </div>
        </div>

    </div>

    <!-- QUEUE SUMMARY FILTER CHIPS -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2.5">
        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'all', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'all' ? 'bg-teal-600 border-teal-600 text-white font-bold shadow-xs' : 'bg-white border-gray-200 text-gray-700 hover:border-teal-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">All</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['total'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'waiting', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'waiting' ? 'bg-amber-500 border-amber-500 text-white font-bold shadow-xs' : 'bg-white border-amber-200 text-amber-800 hover:border-amber-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Waiting</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['waiting'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'called', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'called' ? 'bg-blue-600 border-blue-600 text-white font-bold shadow-xs' : 'bg-white border-blue-200 text-blue-800 hover:border-blue-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Called</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['called'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'in_consultation', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'in_consultation' ? 'bg-indigo-600 border-indigo-600 text-white font-bold shadow-xs' : 'bg-white border-indigo-200 text-indigo-800 hover:border-indigo-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Consulting</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['in_consultation'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'hold', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'hold' ? 'bg-orange-500 border-orange-500 text-white font-bold shadow-xs' : 'bg-white border-orange-200 text-orange-800 hover:border-orange-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">On Hold</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['hold'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'skipped', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'skipped' ? 'bg-purple-600 border-purple-600 text-white font-bold shadow-xs' : 'bg-white border-purple-200 text-purple-800 hover:border-purple-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Skipped</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['skipped'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'completed', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'completed' ? 'bg-emerald-600 border-emerald-600 text-white font-bold shadow-xs' : 'bg-white border-emerald-200 text-emerald-800 hover:border-emerald-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Completed</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['completed'] }}</div>
        </a>

        <a href="{{ route('doctor.queue.index', ['date' => $targetDate, 'status' => 'cancelled', 'search' => $search]) }}" 
           class="p-2.5 rounded-xl border text-xs transition {{ $statusFilter === 'cancelled' ? 'bg-rose-600 border-rose-600 text-white font-bold shadow-xs' : 'bg-white border-rose-200 text-rose-800 hover:border-rose-400' }}">
            <div class="text-[10px] uppercase font-bold opacity-80">Cancelled</div>
            <div class="font-mono text-base font-black mt-0.5">{{ $counts['cancelled'] }}</div>
        </a>
    </div>

    <!-- SEARCH & TABLE CONTAINER -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden space-y-4">
        
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
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Prescription</th>
                        <th class="py-3 px-4 text-right">Workflow Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tokens as $token)
                    <tr class="hover:bg-slate-50/60 transition {{ in_array($token->status, ['called', 'in_consultation']) ? 'bg-indigo-50/30' : '' }}">
                        <!-- Token # -->
                        <td class="py-3.5 px-4">
                            <span class="inline-flex items-center justify-center px-2.5 py-1.5 rounded-xl font-mono text-xs font-black {{ in_array($token->status, ['called', 'in_consultation']) ? 'bg-indigo-600 text-white' : ($token->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-900 text-white') }} shadow-xs">
                                #{{ $token->formatted_token_number }}
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
                            @elseif($token->payment_type === 'partial')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    Partial (PKR {{ number_format($token->charged_amount, 0) }})
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    Free Visit
                                </span>
                            @endif
                        </td>

                        <!-- Status -->
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
                                    <i class="fa-solid fa-bullhorn mr-1.5 text-[10px]"></i> Called ({{ $token->call_count }})
                                </span>
                            @elseif($token->status === 'hold')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                    <i class="fa-solid fa-pause mr-1.5 text-[10px]"></i> On Hold
                                </span>
                            @elseif($token->status === 'skipped')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <i class="fa-solid fa-forward-step mr-1.5 text-[10px]"></i> Skipped
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
                        <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                            @if($token->status === 'waiting')
                                <form action="{{ route('doctor.queue.call', $token->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                                        <i class="fa-solid fa-phone mr-1"></i> Call
                                    </button>
                                </form>
                            @elseif($token->status === 'called')
                                <form action="{{ route('doctor.queue.recall', $token->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-xs transition" title="Re-announce">
                                        <i class="fa-solid fa-volume-high"></i>
                                    </button>
                                </form>
                                <a href="{{ route('doctor.consultations.show', $token->id) }}" class="inline-flex items-center px-2.5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs transition">
                                    <i class="fa-solid fa-stethoscope mr-1"></i> Start
                                </a>
                            @elseif($token->status === 'in_consultation')
                                <a href="{{ route('doctor.consultations.show', $token->id) }}" class="inline-flex items-center px-2.5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs transition">
                                    <i class="fa-solid fa-pen-to-square mr-1"></i> Workspace
                                </a>
                                <form action="{{ route('doctor.queue.complete', $token->id) }}" method="POST" class="inline" onsubmit="return confirm('Complete consultation for Token #{{ $token->formatted_token_number }}?');">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs transition">
                                        <i class="fa-solid fa-check mr-1"></i> Finish
                                    </button>
                                </form>
                            @elseif(in_array($token->status, ['hold', 'skipped']))
                                <form action="{{ route('doctor.queue.resume', $token->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-2.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg font-bold text-xs transition">
                                        <i class="fa-solid fa-rotate-left mr-1"></i> Resume
                                    </button>
                                </form>
                            @endif

                            @if(!in_array($token->status, ['completed', 'cancelled']))
                                <button type="button" onclick="openActionModal('cancel', '{{ $token->id }}', '{{ $token->formatted_token_number }}')" class="p-1.5 text-gray-400 hover:text-rose-600 transition" title="Cancel Token">
                                    <i class="fa-solid fa-ban"></i>
                                </button>
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

<!-- REASON MODAL (FOR HOLD / SKIP / CANCEL) -->
<div id="actionModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl mx-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 id="modalTitle" class="text-sm font-bold text-gray-900">Manage Token</h3>
            <button type="button" onclick="closeActionModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>
        <form id="modalForm" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label id="modalReasonLabel" class="block text-xs font-semibold text-gray-700 mb-1">Reason / Notes</label>
                <textarea id="modalReasonInput" name="reason" rows="3" required class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500" placeholder="Enter reason..."></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeActionModal()" class="px-3.5 py-2 text-xs font-bold text-gray-600 hover:bg-gray-100 rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" id="modalSubmitBtn" class="px-4 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 rounded-xl shadow-xs transition">
                    Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openActionModal(action, tokenId, tokenNumber) {
    const modal = document.getElementById('actionModal');
    const form = document.getElementById('modalForm');
    const title = document.getElementById('modalTitle');
    const label = document.getElementById('modalReasonLabel');
    const input = document.getElementById('modalReasonInput');
    const btn = document.getElementById('modalSubmitBtn');

    input.value = '';

    if (action === 'hold') {
        form.action = `/doctor-portal/opd-queue/${tokenId}/hold`;
        input.name = 'reason';
        input.placeholder = 'e.g. Waiting for laboratory reports or vital checks...';
        title.innerText = `Put Token #${tokenNumber} On Hold`;
        label.innerText = 'Hold Reason (Optional)';
        input.required = false;
        btn.innerText = 'Put On Hold';
        btn.className = 'px-4 py-2 text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 rounded-xl shadow-xs transition';
    } else if (action === 'skip') {
        form.action = `/doctor-portal/opd-queue/${tokenId}/skip`;
        input.name = 'reason';
        input.placeholder = 'e.g. Patient not present when token was announced...';
        title.innerText = `Skip Token #${tokenNumber}`;
        label.innerText = 'Skip Reason (Optional)';
        input.required = false;
        btn.innerText = 'Skip Token';
        btn.className = 'px-4 py-2 text-xs font-bold text-white bg-purple-600 hover:bg-purple-700 rounded-xl shadow-xs transition';
    } else if (action === 'cancel') {
        form.action = `/doctor-portal/opd-queue/${tokenId}/cancel`;
        input.name = 'cancel_reason';
        input.placeholder = 'Provide reason for token cancellation...';
        title.innerText = `Cancel Token #${tokenNumber}`;
        label.innerText = 'Cancellation Reason (Required)';
        input.required = true;
        btn.innerText = 'Cancel Token';
        btn.className = 'px-4 py-2 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-xl shadow-xs transition';
    }

    modal.classList.remove('hidden');
}

function closeActionModal() {
    document.getElementById('actionModal').classList.add('hidden');
}
</script>
@endsection
