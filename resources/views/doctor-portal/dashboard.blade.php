@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- WELCOME BANNER -->
    <div class="relative overflow-hidden bg-gradient-to-r from-teal-700 via-teal-800 to-slate-900 rounded-2xl p-6 text-white shadow-xl">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-teal-400/20 text-teal-200 border border-teal-300/30">
                        <i class="fa-solid fa-stethoscope mr-1"></i> Consultant Workspace
                    </span>
                    <span class="text-xs text-teal-200/80">Room: {{ $doctor->room_number ?: 'Not assigned' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                    Welcome, Dr. {{ $doctor->name }}
                </h1>
                <p class="text-xs sm:text-sm text-teal-100/90 max-w-2xl">
                    {{ $doctor->specialization ?: 'Specialist Consultant' }} {{ $doctor->qualification ? '• '.$doctor->qualification : '' }} • Consultation Fee: PKR {{ number_format($doctor->consultation_fee, 0) }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('doctor.queue.index') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs rounded-xl shadow-lg transition">
                    <i class="fa-solid fa-play"></i>
                    <span>Open Live OPD Queue</span>
                </a>
                <a href="{{ route('doctor.prescriptions.create') }}" class="inline-flex items-center space-x-2 px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-xs rounded-xl border border-white/20 transition backdrop-blur-xs">
                    <i class="fa-solid fa-prescription"></i>
                    <span>New Prescription</span>
                </a>
            </div>
        </div>
        <!-- Decorative bg circle -->
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-teal-500/10 rounded-full blur-2xl"></div>
    </div>

    <!-- OPD QUEUE KPI STATS -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center space-x-2">
                <i class="fa-solid fa-ticket-simple text-teal-600"></i>
                <span>Today's OPD Queue Overview ({{ format_app_datetime(now(config('app.display_timezone', 'Asia/Karachi')), 'd M, Y') }})</span>
            </h2>
            <span class="text-[11px] text-gray-400 font-medium">Timezone: {{ config('app.display_timezone', 'Asia/Karachi') }}</span>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Today -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Today's Tokens</span>
                    <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-gray-900 font-mono">{{ $todayTokensCount }}</span>
                    <span class="text-[11px] text-gray-400">assigned today</span>
                </div>
            </div>

            <!-- Waiting -->
            <div class="bg-white rounded-xl border border-amber-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-amber-700">Waiting in Lobby</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-amber-700 font-mono">{{ $waitingTokensCount }}</span>
                    <span class="text-[11px] text-amber-600">in queue</span>
                </div>
            </div>

            <!-- In Consultation -->
            <div class="bg-white rounded-xl border border-indigo-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-indigo-700">In Consultation</span>
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-headset"></i>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-indigo-700 font-mono">{{ $inConsultationCount }}</span>
                    <span class="text-[11px] text-indigo-600">active now</span>
                </div>
            </div>

            <!-- Completed Today -->
            <div class="bg-white rounded-xl border border-emerald-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-emerald-700">Completed Today</span>
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                </div>
                <div class="mt-2 flex items-baseline space-x-2">
                    <span class="text-2xl font-black text-emerald-700 font-mono">{{ $completedTokensCount }}</span>
                    <span class="text-[11px] text-emerald-600">examined today</span>
                </div>
            </div>
        </div>
    </div>

    <!-- PRACTICE & FINANCIAL METRICS (REAL AUTHORITATIVE LEDGER DATA) -->
    <div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-3">
            <h2 class="text-xs font-bold text-gray-500 uppercase tracking-wider flex items-center space-x-2">
                <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                <span>Practice Summary & Ledger Balance (Authoritative Audit Records)</span>
            </h2>
            <span class="text-[11px] text-emerald-700 font-semibold flex items-center">
                <i class="fa-solid fa-shield-halved mr-1 text-[10px]"></i> Reconciled with Hospital Ledger
            </span>
        </div>
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Current Outstanding Payable -->
            <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl border border-emerald-200 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-emerald-800">Current Payable Balance</span>
                    <i class="fa-solid fa-wallet text-emerald-600"></i>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-emerald-700 font-mono">
                        PKR {{ number_format($currentPayable, 2) }}
                    </span>
                    <p class="text-[10px] text-emerald-600 mt-0.5">Owed to you by hospital cash office</p>
                </div>
            </div>

            <!-- Total Doctor Share Earned -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Doctor Share Earned</span>
                    <i class="fa-solid fa-arrow-trend-up text-blue-500"></i>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-gray-900 font-mono">
                        PKR {{ number_format($totalEarned, 2) }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Total lifetime earned credits ({{ (float)$doctor->doctor_share_percentage }}% share)</p>
                </div>
            </div>

            <!-- Gross Practice Collection -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Gross Practice Collection</span>
                    <i class="fa-solid fa-receipt text-teal-600"></i>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-teal-900 font-mono">
                        PKR {{ number_format($grossCollection, 2) }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Eligible consultation/service billing</p>
                </div>
            </div>

            <!-- Total Paid / Settled -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Settled Payouts</span>
                    <i class="fa-solid fa-money-bill-transfer text-slate-500"></i>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-gray-900 font-mono">
                        PKR {{ number_format($totalPaid, 2) }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">Verified payouts disbursed to date</p>
                </div>
            </div>

            <!-- Lifetime Visits & Consultations -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Total Lifetime Visits</span>
                    <i class="fa-solid fa-hospital-user text-indigo-500"></i>
                </div>
                <div class="mt-2">
                    <span class="text-xl sm:text-2xl font-black text-gray-900 font-mono">
                        {{ $totalLifetimeVisits }}
                    </span>
                    <p class="text-[10px] text-gray-400 mt-0.5">{{ $totalLifetimeTokens }} total tokens • {{ $totalCompletedVisits }} completed visits</p>
                </div>
            </div>

            <!-- Authorized Patients & Prescriptions -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">Patients & Prescriptions</span>
                    <i class="fa-solid fa-user-check text-purple-500"></i>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <div>
                        <span class="text-xl font-black text-gray-900 font-mono">{{ $authorizedPatientsCount }}</span>
                        <p class="text-[10px] text-gray-400">Unique Patients</p>
                    </div>
                    <div class="text-right border-l border-gray-100 pl-4">
                        <span class="text-xl font-black text-purple-700 font-mono">{{ $prescriptionsCount }}</span>
                        <p class="text-[10px] text-gray-400">Prescriptions</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TWO COLUMN: RECENT CONSULTATIONS & PRACTICE SCHEDULE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- RECENT CONSULTATIONS LIST (2 COLS) -->
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                        <i class="fa-solid fa-clock-rotate-left text-teal-600"></i>
                        <span>Recent Assigned Consultations</span>
                    </h3>
                    <p class="text-xs text-gray-500">Latest tokens assigned to Dr. {{ $doctor->name }}</p>
                </div>
                <a href="{{ route('doctor.queue.index') }}" class="text-xs font-bold text-teal-600 hover:text-teal-700 hover:underline">
                    View All Queue →
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/70 border-b border-gray-100 text-gray-500 uppercase font-semibold text-[10px] tracking-wider">
                            <th class="py-3 px-4">Token #</th>
                            <th class="py-3 px-4">Patient</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentTokens as $token)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 font-mono font-bold text-gray-900">
                                #{{ $token->token_number }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="font-bold text-gray-800 block">{{ $token->patient ? $token->patient->name : 'OPD Patient' }}</span>
                                <span class="text-[11px] text-gray-400 font-mono">{{ $token->patient ? $token->patient->patient_number : '—' }} • {{ $token->patient && $token->patient->gender ? $token->patient->gender : '—' }}, {{ $token->patient && $token->patient->age ? $token->patient->age.'y' : '—' }}</span>
                            </td>
                            <td class="py-3 px-4 text-gray-500 whitespace-nowrap">
                                {{ format_app_datetime($token->token_date, 'd M, Y') }}
                            </td>
                            <td class="py-3 px-4">
                                @if($token->status === 'in_consultation')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        <i class="fa-solid fa-spinner fa-spin mr-1 text-[8px]"></i> In Consultation
                                    </span>
                                @elseif($token->status === 'waiting')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i class="fa-solid fa-clock mr-1 text-[8px]"></i> Waiting
                                    </span>
                                @elseif($token->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <i class="fa-solid fa-circle-check mr-1 text-[8px]"></i> Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-600">
                                        {{ ucfirst($token->status) }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('doctor.consultations.show', $token->id) }}" class="inline-flex items-center px-2.5 py-1 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 rounded-lg font-bold text-[11px] transition">
                                    <i class="fa-solid fa-file-medical mr-1"></i> Consult
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400 text-xs">
                                <i class="fa-solid fa-inbox text-3xl mb-2 text-gray-300 block"></i>
                                No consultations found for Dr. {{ $doctor->name }} yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PRACTICE & CLINIC SCHEDULE WIDGET (1 COL) -->
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-1.5">
                        <i class="fa-regular fa-calendar-check text-teal-600"></i>
                        <span>Clinic & Duty Timings</span>
                    </h3>
                    <a href="{{ route('doctor.schedule.index') }}" class="text-[11px] font-bold text-teal-600 hover:underline">
                        Edit
                    </a>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-500">Clinic Room:</span>
                        <span class="font-bold text-gray-900">{{ $doctor->room_number ?: 'Not Set' }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-500">Consultation Fee:</span>
                        <span class="font-bold text-gray-900 font-mono">PKR {{ number_format($doctor->consultation_fee, 0) }}</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-500">Doctor Share:</span>
                        <span class="font-bold text-emerald-700 font-mono">{{ (float)$doctor->doctor_share_percentage }}%</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-50">
                        <span class="text-gray-500">Daily Shift:</span>
                        <span class="font-semibold text-gray-800">
                            {{ $doctor->start_time ?: '09:00 AM' }} - {{ $doctor->end_time ?: '05:00 PM' }}
                        </span>
                    </div>
                </div>

                <!-- Duty Days Chips -->
                <div class="pt-2">
                    <span class="text-[11px] font-semibold text-gray-400 block mb-2">Active Duty Days:</span>
                    <div class="flex flex-wrap gap-1.5">
                        @php
                            $days = is_array($doctor->schedule_days) && count($doctor->schedule_days) > 0 
                                ? $doctor->schedule_days 
                                : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        @endphp
                        @foreach($days as $d)
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-800 border border-teal-200">
                                {{ substr($d, 0, 3) }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- QUICK SHORTCUTS CARD -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-xl p-5 text-white shadow-sm space-y-3">
                <h4 class="text-xs font-bold uppercase tracking-wider text-teal-400">Quick Navigation</h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('doctor.patients.index') }}" class="p-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700 flex items-center space-x-2 transition">
                        <i class="fa-solid fa-hospital-user text-teal-400"></i>
                        <span>My Patients</span>
                    </a>
                    <a href="{{ route('doctor.prescriptions.index') }}" class="p-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700 flex items-center space-x-2 transition">
                        <i class="fa-solid fa-prescription text-teal-400"></i>
                        <span>Prescriptions</span>
                    </a>
                    <a href="{{ route('doctor.earnings.index') }}" class="p-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700 flex items-center space-x-2 transition">
                        <i class="fa-solid fa-chart-line text-teal-400"></i>
                        <span>Earnings</span>
                    </a>
                    <a href="{{ route('doctor.ledger.index') }}" class="p-2.5 rounded-lg bg-slate-800/80 hover:bg-slate-700 border border-slate-700 flex items-center space-x-2 transition">
                        <i class="fa-solid fa-scale-balanced text-teal-400"></i>
                        <span>Audit Ledger</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
