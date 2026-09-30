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
        <h2 class="text-base font-bold text-gray-800 mt-1">All Doctors Collection & Settlement Summary</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Date Range:</strong> {{ ucfirst($filter) }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>All Doctors Collection</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-users-viewfinder text-blue-600 text-xl"></i>
                <span>All Doctors Collection (Summary & Comparison)</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Comparative revenue performance, collection efficiency, doctor liability and settlements</p>
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
        <form method="GET" action="{{ route('reports.all-doctors-collection') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
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
                <a href="{{ route('reports.all-doctors-collection') }}" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg text-xs font-semibold transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- COMPARISON TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Doctor Performance & Liability Table</h3>
            <span class="text-xs font-semibold text-gray-500">{{ count($doctorSummaries) }} Doctors</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Doctor Name</th>
                        <th class="p-3 text-center">Patients</th>
                        <th class="p-3 text-right">Total Billed</th>
                        <th class="p-3 text-right">Total Collected</th>
                        <th class="p-3 text-right text-blue-700 bg-blue-50/50">Total Doctor Share</th>
                        <th class="p-3 text-right text-indigo-700 bg-indigo-50/50">Total Hospital Share</th>
                        <th class="p-3 text-right text-emerald-700 bg-emerald-50/50">Paid to Doctor</th>
                        <th class="p-3 text-right text-amber-700 bg-amber-50/50">Balance Payable</th>
                        <th class="p-3 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($doctorSummaries as $row)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3">
                            <span class="font-bold text-gray-900 block text-sm">{{ $row['doctor']->name }}</span>
                            <span class="text-[10px] text-gray-400">{{ $row['doctor']->specialization }}</span>
                        </td>
                        <td class="p-3 text-center font-bold text-slate-800">
                            {{ $row['patients_count'] }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-slate-900">
                            PKR {{ number_format($row['total_billed'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-600">
                            PKR {{ number_format($row['total_collected'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-blue-700 bg-blue-50/30">
                            PKR {{ number_format($row['doctor_share'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-indigo-700 bg-indigo-50/30">
                            PKR {{ number_format($row['hospital_share'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-700 bg-emerald-50/30">
                            PKR {{ number_format($row['total_settled'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-amber-700 bg-amber-50/30">
                            PKR {{ number_format($row['current_payable'], 2) }}
                        </td>
                        <td class="p-3 text-center no-print">
                            <div class="flex items-center justify-center space-x-1">
                                <a href="{{ route('reports.doctor-collection', ['doctor_id' => $row['doctor']->id]) }}" class="text-blue-600 hover:text-blue-800 p-1 text-xs font-semibold" title="Detailed Report">
                                    <i class="fa-solid fa-list-check"></i>
                                </a>
                                <a href="{{ route('doctor-ledgers.show', $row['doctor']->id) }}" class="text-emerald-600 hover:text-emerald-800 p-1 text-xs font-semibold" title="Ledger & Settle">
                                    <i class="fa-solid fa-wallet"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="p-8 text-center text-gray-400">
                            No doctors registered in the system.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-900 text-white font-bold border-t-2 border-slate-700">
                    <tr>
                        <td class="p-3 uppercase tracking-wider text-xs">Hospital-Wide Totals:</td>
                        <td class="p-3 text-center font-mono">{{ number_format($hospitalTotals['total_patients']) }}</td>
                        <td class="p-3 text-right font-mono">PKR {{ number_format($hospitalTotals['total_billed'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-emerald-400">PKR {{ number_format($hospitalTotals['total_collected'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-blue-300">PKR {{ number_format($hospitalTotals['total_doctor_share'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-300">PKR {{ number_format($hospitalTotals['total_hospital_share'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-emerald-300">PKR {{ number_format($hospitalTotals['total_settled'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-amber-300">PKR {{ number_format($hospitalTotals['balance_payable'], 2) }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
