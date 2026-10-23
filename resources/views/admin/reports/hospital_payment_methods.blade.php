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
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Collections by Payment Channel Statement</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Period:</strong> {{ ucfirst($filter) }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Payment Methods</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-credit-card text-blue-600"></i>
                <span>Hospital Payment Method Breakdown Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Distribution of collections across physical cash, card point of sale, and bank transfers</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.hospital-payment-methods') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date From</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date To</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
            </div>

            <div>
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- METHOD CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
        @foreach($methodRows as $row)
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm space-y-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider">{{ $row['label'] }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 font-mono">{{ $row['percentage'] }}%</span>
            </div>
            <div class="text-2xl font-black text-gray-900 font-mono">
                PKR {{ number_format($row['amount'], 2) }}
            </div>
            <div class="text-[11px] text-gray-500 flex justify-between items-center">
                <span>{{ $row['count'] }} payment transactions</span>
            </div>
            <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
                <div class="bg-blue-600 h-full rounded-full" style="width: {{ $row['percentage'] }}%"></div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- COMPARISON TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Payment Method Channel</th>
                        <th class="py-3 px-4 text-center">Transaction Count</th>
                        <th class="py-3 px-4 text-right">Collected Amount (PKR)</th>
                        <th class="py-3 px-4 text-right">Share of Collections</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                    @foreach($methodRows as $row)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4 font-sans font-bold text-gray-900 flex items-center space-x-2">
                            @if($row['key'] === 'cash')
                                <i class="fa-solid fa-money-bill-wave text-emerald-600"></i>
                            @elseif($row['key'] === 'card')
                                <i class="fa-solid fa-credit-card text-blue-600"></i>
                            @elseif($row['key'] === 'bank_transfer')
                                <i class="fa-solid fa-building-columns text-indigo-600"></i>
                            @else
                                <i class="fa-solid fa-receipt text-slate-600"></i>
                            @endif
                            <span>{{ $row['label'] }}</span>
                        </td>
                        <td class="py-3 px-4 text-center">{{ $row['count'] }}</td>
                        <td class="py-3 px-4 text-right font-bold text-gray-900">PKR {{ number_format($row['amount'], 2) }}</td>
                        <td class="py-3 px-4 text-right font-bold text-blue-600">{{ $row['percentage'] }}%</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold text-gray-900 border-t-2 border-slate-300">
                        <td class="py-3 px-4 font-sans uppercase">Total Collected:</td>
                        <td class="py-3 px-4 text-center">{{ $grandTotalCount }}</td>
                        <td class="py-3 px-4 text-right text-emerald-700 text-sm">PKR {{ number_format($grandTotalAmount, 2) }}</td>
                        <td class="py-3 px-4 text-right">100.0%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
