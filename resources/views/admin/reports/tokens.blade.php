@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Reports</span>
                <span>/</span>
                <span>OPD & Token Analytics</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-chart-line text-teal-600"></i>
                <span>Token Calling & Queue Reports</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Analytical summary of daily patient tokens, waiting durations, consultation times, and doctor performance.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.print()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
            <a href="{{ route('token.display') }}" target="_blank" class="px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-tv"></i>
                <span>Launch LCD Display</span>
            </a>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs">
        <form action="{{ route('reports.tokens') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Filter by Doctor</label>
                <select name="doctor_id" class="w-full text-xs rounded-xl border-gray-300 py-2 px-3 focus:ring-teal-500 focus:border-teal-500">
                    <option value="">All Attending Doctors</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>Dr. {{ $doc->name }} ({{ $doc->specialization ?? 'General' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 py-2 px-4 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Apply Filter
                </button>
                <a href="{{ route('reports.tokens') }}" class="py-2 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- CLOUD SYNCHRONIZATION STATUS WIDGET -->
    <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl p-5 shadow-sm border border-slate-700">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2 text-xs font-bold text-teal-400 uppercase tracking-wider mb-1">
                    <span class="w-2.5 h-2.5 rounded-full {{ $syncStats['cloud_online'] ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                    <span>Offline-First Engine: {{ $syncStats['cloud_online'] ? 'Cloud Synchronization Online' : 'Local Standalone Mode (Cloud Offline)' }}</span>
                </div>
                <h3 class="text-lg font-bold text-white">Local Database Source of Truth</h3>
                <p class="text-xs text-slate-300 mt-0.5">
                    Pending Outbox: <strong class="text-amber-300 font-mono">{{ $syncStats['pending_count'] }}</strong> record(s) • Synced to Cloud: <strong class="text-emerald-300 font-mono">{{ $syncStats['synced_count'] }}</strong>
                    @if($syncStats['last_synced_at'])
                        • Last Synced: <span class="text-slate-400">{{ format_app_datetime($syncStats['last_synced_at'], 'd M Y, h:i A') }}</span>
                    @endif
                </p>
            </div>
            <form action="{{ route('admin.token-sync.trigger') }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-teal-500 hover:bg-teal-600 text-slate-950 font-black text-xs rounded-xl shadow-md transition flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up"></i>
                    <span>Sync Outbox Now</span>
                </button>
            </form>
        </div>
    </div>

    <!-- SUMMARY KPI STATS TILES -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-2xs">
            <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider block">Total Issued</span>
            <div class="text-2xl font-black font-mono text-gray-900 mt-1">{{ $totalIssued }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-emerald-200 shadow-2xs">
            <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider block">Completed</span>
            <div class="text-2xl font-black font-mono text-emerald-600 mt-1">{{ $completedCount }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-amber-200 shadow-2xs">
            <span class="text-[10px] font-bold text-amber-700 uppercase tracking-wider block">Avg Wait Time</span>
            <div class="text-2xl font-black font-mono text-amber-600 mt-1">{{ $avgWaitMinutes }} <span class="text-xs font-normal text-gray-500">min</span></div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-indigo-200 shadow-2xs">
            <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider block">Avg Consult Time</span>
            <div class="text-2xl font-black font-mono text-indigo-600 mt-1">{{ $avgConsultMinutes }} <span class="text-xs font-normal text-gray-500">min</span></div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-blue-200 shadow-2xs">
            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-wider block">Waiting / Calling</span>
            <div class="text-2xl font-black font-mono text-blue-600 mt-1">{{ $waitingCount + $calledCount }}</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-rose-200 shadow-2xs">
            <span class="text-[10px] font-bold text-rose-700 uppercase tracking-wider block">Cancelled</span>
            <div class="text-2xl font-black font-mono text-rose-600 mt-1">{{ $cancelledCount }}</div>
        </div>
    </div>

    <!-- DOCTOR-WISE PERFORMANCE TABLE -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50/70 flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                <i class="fa-solid fa-stethoscope text-teal-600"></i>
                <span>Doctor-wise OPD Queue & Wait Time Breakdown</span>
            </h3>
            <span class="text-[11px] font-semibold text-gray-500">Period: {{ format_app_datetime($startDate, 'd M Y') }} – {{ format_app_datetime($endDate, 'd M Y') }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 uppercase font-semibold text-[10px] tracking-wider">
                        <th class="py-3 px-4">Doctor Name</th>
                        <th class="py-3 px-4">Specialization</th>
                        <th class="py-3 px-4">Room No</th>
                        <th class="py-3 px-4 text-center">Total Tokens</th>
                        <th class="py-3 px-4 text-center">Completed</th>
                        <th class="py-3 px-4 text-center">Waiting</th>
                        <th class="py-3 px-4 text-center">Cancelled</th>
                        <th class="py-3 px-4 text-right">Avg Wait (min)</th>
                        <th class="py-3 px-4 text-right">Avg Consult (min)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($doctorStats as $row)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-gray-900">
                            Dr. {{ $row['doctor']->name }}
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ $row['doctor']->specialization ?? 'General OPD' }}
                        </td>
                        <td class="py-3 px-4 text-gray-600">
                            {{ $row['doctor']->room_number ?? 'Consultation Desk' }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-gray-900">
                            {{ $row['total'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-emerald-600">
                            {{ $row['completed'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-amber-600">
                            {{ $row['waiting'] }}
                        </td>
                        <td class="py-3 px-4 text-center font-mono font-bold text-rose-600">
                            {{ $row['cancelled'] }}
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                            {{ $row['avg_wait_min'] }} min
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-slate-800">
                            {{ $row['avg_consult_min'] }} min
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="py-10 text-center text-gray-400">
                            No tokens issued during the selected date range.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

