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
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Services Utilization & Revenue Report</h2>
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
                <span>Hospital Services</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-hand-holding-medical text-blue-600 text-xl"></i>
                <span>Hospital Services Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Procedure and clinical service utilization, standard split rates and earnings</p>
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
        <form method="GET" action="{{ route('reports.hospital-services') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
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
                <a href="{{ route('reports.hospital-services') }}" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg text-xs font-semibold transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Procedures</span>
            <span class="text-2xl font-bold text-slate-800 mt-1 block">{{ number_format($totals['count']) }}</span>
            <span class="text-[10px] text-gray-400">Total Services Rendered</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Billed</span>
            <span class="text-xl font-bold text-slate-900 mt-1 block font-mono">PKR {{ number_format($totals['billed'], 2) }}</span>
            <span class="text-[10px] text-gray-400">Gross Invoiced</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-emerald-100 bg-emerald-50/20">
            <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Total Collected</span>
            <span class="text-xl font-bold text-emerald-700 mt-1 block font-mono">PKR {{ number_format($totals['collected'], 2) }}</span>
            <span class="text-[10px] text-emerald-500 font-semibold">Cash Received</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-indigo-100 bg-indigo-50/20">
            <span class="text-[11px] font-bold text-indigo-700 uppercase tracking-wider block">Hospital Share</span>
            <span class="text-xl font-bold text-indigo-700 mt-1 block font-mono">PKR {{ number_format($totals['hospital_share'], 2) }}</span>
            <span class="text-[10px] text-indigo-500 font-semibold">Hospital Net Profit</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-blue-100 bg-blue-50/20">
            <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider block">Doctor Share</span>
            <span class="text-xl font-bold text-blue-700 mt-1 block font-mono">PKR {{ number_format($totals['doctor_share'], 2) }}</span>
            <span class="text-[10px] text-blue-500 font-semibold">Clinical Share Earned</span>
        </div>
    </div>

    <!-- SERVICES BREAKDOWN TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Service Performance Breakdown</h3>
            <a href="{{ route('hospital-services.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">
                <i class="fa-solid fa-gear mr-1"></i> Configure Service Split
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Service Name</th>
                        <th class="p-3 text-center">Split Ratio</th>
                        <th class="p-3 text-center">Count Performed</th>
                        <th class="p-3 text-right">Total Billed</th>
                        <th class="p-3 text-right">Total Collected</th>
                        <th class="p-3 text-right text-indigo-700 bg-indigo-50/50">Hospital Share Earned</th>
                        <th class="p-3 text-right text-blue-700 bg-blue-50/50">Doctor Share Earned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($serviceRows as $row)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3">
                            <span class="font-bold text-gray-900 block text-sm">{{ $row['service']->name }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $row['service']->service_code }} &bull; Default Fee: PKR {{ number_format($row['service']->default_fee, 2) }}</span>
                        </td>
                        <td class="p-3 text-center">
                            <span class="inline-flex items-center space-x-1 font-mono text-xs font-bold">
                                <span class="text-blue-700">{{ $row['service']->doctor_share_percentage }}% Doc</span>
                                <span class="text-gray-400">/</span>
                                <span class="text-indigo-700">{{ $row['service']->hospital_share_percentage }}% Hosp</span>
                            </span>
                        </td>
                        <td class="p-3 text-center font-bold text-slate-800">
                            {{ $row['count'] }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-slate-900">
                            PKR {{ number_format($row['billed'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-600">
                            PKR {{ number_format($row['collected'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-indigo-700 bg-indigo-50/30">
                            PKR {{ number_format($row['hospital_share'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-blue-700 bg-blue-50/30">
                            PKR {{ number_format($row['doctor_share'], 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No services recorded in the system.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-900 text-white font-bold border-t-2 border-slate-700">
                    <tr>
                        <td colspan="2" class="p-3 uppercase tracking-wider text-xs">Total Summary:</td>
                        <td class="p-3 text-center font-mono">{{ number_format($totals['count']) }}</td>
                        <td class="p-3 text-right font-mono">PKR {{ number_format($totals['billed'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-emerald-400">PKR {{ number_format($totals['collected'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-300">PKR {{ number_format($totals['hospital_share'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-blue-300">PKR {{ number_format($totals['doctor_share'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
