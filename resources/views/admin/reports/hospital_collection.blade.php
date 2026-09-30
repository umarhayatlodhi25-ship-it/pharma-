@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- PRINT STYLESHEET -->
    <style>
        @media print {
            aside, nav, header, .no-print, form, button, a {
                display: none !important;
            }
            .print-only {
                display: block !important;
            }
            body, main, .space-y-6 {
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
                color: #000000 !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    <!-- PRINT BANNER -->
    <div class="print-only mb-6 border-b-2 border-black pb-4 text-center">
        <h1 class="text-xl font-black uppercase tracking-wider">{{ config('app.name', 'HOSPITAL MANAGEMENT') }}</h1>
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Overall Revenue & Cash Position Report</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Period:</strong> {{ ucfirst($filter) }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Hospital Collection</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-building-columns text-blue-600 text-xl"></i>
                <span>Hospital Collection Report (Overall Revenue)</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Executive financial health, net hospital earnings, doctor liabilities and actual cash in hand</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow-xs hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 no-print">
        <form method="GET" action="{{ route('reports.hospital-collection') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
            <!-- Time Period -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
            </div>

            <!-- Actions -->
            <div class="flex space-x-2 md:col-span-2">
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg font-semibold text-xs hover:bg-blue-700 transition">
                    <i class="fa-solid fa-filter mr-1"></i> Apply Filter
                </button>
                <a href="{{ route('reports.hospital-collection') }}" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg text-xs font-semibold transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- EXECUTIVE METRICS GRID (Prompt Section 11) -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- 1. Total OPD Billing Amount -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">1. Total OPD Billed</span>
            <span class="text-2xl font-bold text-slate-900 mt-1 block font-mono">PKR {{ number_format($metrics['total_billed'], 2) }}</span>
            <span class="text-[11px] text-gray-400">Gross billing charges</span>
        </div>

        <!-- 2. Total Cash/Amount Collected -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-emerald-100 bg-emerald-50/20">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">2. Cash Collected</span>
            <span class="text-2xl font-bold text-emerald-700 mt-1 block font-mono">PKR {{ number_format($metrics['total_collected'], 2) }}</span>
            <span class="text-[11px] text-emerald-600 font-medium">Actual cash received by hospital</span>
        </div>

        <!-- 3. Total Doctor Share (Liability) -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-blue-100 bg-blue-50/20">
            <span class="text-xs font-semibold text-blue-700 uppercase tracking-wider block">3. Doctor Liability</span>
            <span class="text-2xl font-bold text-blue-700 mt-1 block font-mono">PKR {{ number_format($metrics['doctor_share'], 2) }}</span>
            <span class="text-[11px] text-blue-600 font-medium">Doctor share on collected cash</span>
        </div>

        <!-- 4. Total Hospital Net Revenue -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-indigo-100 bg-indigo-50/20">
            <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider block">4. Hospital Net Revenue</span>
            <span class="text-2xl font-bold text-indigo-700 mt-1 block font-mono">PKR {{ number_format($metrics['hospital_share'], 2) }}</span>
            <span class="text-[11px] text-indigo-600 font-medium">Hospital share retained</span>
        </div>

        <!-- 5. Total Outstanding / Unpaid -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-amber-100 bg-amber-50/20">
            <span class="text-xs font-semibold text-amber-700 uppercase tracking-wider block">5. Total Unpaid Due</span>
            <span class="text-2xl font-bold text-amber-700 mt-1 block font-mono">PKR {{ number_format($metrics['total_unpaid'], 2) }}</span>
            <span class="text-[11px] text-amber-600 font-medium">Receivable from patients</span>
        </div>

        <!-- 6. Total Paid to Doctors (Settlements) -->
        <div class="bg-white p-5 rounded-xl shadow-xs border border-purple-100 bg-purple-50/20">
            <span class="text-xs font-semibold text-purple-700 uppercase tracking-wider block">6. Paid to Doctors</span>
            <span class="text-2xl font-bold text-purple-700 mt-1 block font-mono">PKR {{ number_format($metrics['total_settled'], 2) }}</span>
            <span class="text-[11px] text-purple-600 font-medium">Total settlements issued</span>
        </div>

        <!-- 7. Net Hospital Cash In Hand (KEY METRIC) -->
        <div class="col-span-2 bg-linear-to-r from-slate-900 to-blue-950 text-white p-5 rounded-xl shadow-md border border-slate-800 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold text-blue-300 uppercase tracking-wider block">7. Net Hospital Cash In Hand</span>
                <span class="text-3xl font-black text-emerald-400 mt-1 block font-mono">PKR {{ number_format($metrics['cash_in_hand'], 2) }}</span>
                <span class="text-xs text-slate-300">Total Collected ({{ number_format($metrics['total_collected'], 0) }}) &minus; Doctor Settlements ({{ number_format($metrics['total_settled'], 0) }})</span>
            </div>
            <div class="bg-white/10 p-3 rounded-xl border border-white/20 text-center">
                <i class="fa-solid fa-vault text-3xl text-emerald-400"></i>
            </div>
        </div>
    </div>

    <!-- VISUAL BREAKDOWNS (Section 11) -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- BY SERVICE TYPE -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-list-check text-blue-600"></i>
                <span>Breakdown by Service Type</span>
            </h3>

            <div class="space-y-4">
                @forelse($serviceBreakdown as $srv)
                    @php
                        $pct = $metrics['total_collected'] > 0 ? round(($srv['collected'] / $metrics['total_collected']) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1">
                            <span class="font-bold text-slate-800">{{ $srv['name'] }} ({{ $srv['count'] }})</span>
                            <span class="font-mono font-bold text-slate-900">PKR {{ number_format($srv['collected'], 2) }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-indigo-600 h-2" style="width: {{ $srv['collected'] > 0 ? ($srv['hospital_share'] / $srv['collected']) * 100 : 0 }}%" title="Hospital Share"></div>
                            <div class="bg-blue-400 h-2" style="width: {{ $srv['collected'] > 0 ? ($srv['doctor_share'] / $srv['collected']) * 100 : 0 }}%" title="Doctor Share"></div>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-gray-400 mt-1">
                            <span>Hosp: PKR {{ number_format($srv['hospital_share'], 2) }}</span>
                            <span>Doc: PKR {{ number_format($srv['doctor_share'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-4">No service data for selected period.</p>
                @endforelse
            </div>
        </div>

        <!-- BY DOCTOR -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h3 class="font-bold text-gray-800 text-sm mb-4 flex items-center space-x-2">
                <i class="fa-solid fa-user-doctor text-indigo-600"></i>
                <span>Breakdown by Doctor</span>
            </h3>

            <div class="space-y-4">
                @forelse($doctorBreakdown as $doc)
                    @php
                        $pct = $metrics['total_collected'] > 0 ? round(($doc['collected'] / $metrics['total_collected']) * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex justify-between items-center text-xs mb-1">
                            <span class="font-bold text-slate-800">{{ $doc['name'] }} ({{ $doc['count'] }} visits)</span>
                            <span class="font-mono font-bold text-slate-900">PKR {{ number_format($doc['collected'], 2) }} ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden flex">
                            <div class="bg-blue-500 h-2" style="width: {{ $doc['collected'] > 0 ? ($doc['doctor_share'] / $doc['collected']) * 100 : 0 }}%" title="Doctor Share"></div>
                            <div class="bg-indigo-600 h-2" style="width: {{ $doc['collected'] > 0 ? ($doc['hospital_share'] / $doc['collected']) * 100 : 0 }}%" title="Hospital Share"></div>
                        </div>
                        <div class="flex justify-between items-center text-[10px] text-gray-400 mt-1">
                            <span>Doctor Share: PKR {{ number_format($doc['doctor_share'], 2) }}</span>
                            <span>Hospital Share: PKR {{ number_format($doc['hospital_share'], 2) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 text-center py-4">No doctor data for selected period.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
