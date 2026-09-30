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
        <h2 class="text-base font-bold text-gray-800 mt-1">Doctor Settlements & Disbursal History</h2>
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
                <span>Doctor Settlements History</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-receipt text-blue-600 text-xl"></i>
                <span>Doctor Settlements History</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Audit log of all financial payout vouchers issued from hospital cash to doctors</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <a href="{{ route('reports.doctor-payable') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-xs transition flex items-center space-x-2">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>Doctor Payable Summary</span>
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow-xs hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 no-print">
        <form method="GET" action="{{ route('reports.doctor-settlements') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
            <!-- Doctor Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Doctor</label>
                <select name="doctor_id" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="">All Doctors</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>
                            {{ $doc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Time Period -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All Time</option>
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <!-- Payment Method -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Method</label>
                <select name="payment_method" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="">All Methods</option>
                    <option value="cash" {{ $paymentMethod == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ $paymentMethod == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="cheque" {{ $paymentMethod == 'cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="online" {{ $paymentMethod == 'online' ? 'selected' : '' }}>Online</option>
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
            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg font-semibold text-xs hover:bg-blue-700 transition">
                    <i class="fa-solid fa-filter mr-1"></i> Filter
                </button>
                <a href="{{ route('reports.doctor-settlements') }}" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg text-xs font-semibold transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Vouchers Issued</span>
            <span class="text-2xl font-bold text-slate-800 mt-1 block font-mono">{{ number_format($totalCount) }}</span>
            <span class="text-[11px] text-gray-400">Total settlement transactions</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-emerald-100 bg-emerald-50/20">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Total Disbursed Amount</span>
            <span class="text-2xl font-bold text-emerald-700 mt-1 block font-mono">PKR {{ number_format($totalAmount, 2) }}</span>
            <span class="text-[11px] text-emerald-600">Total cash paid to doctors</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-blue-100 bg-blue-50/20">
            <span class="text-xs font-semibold text-blue-700 uppercase tracking-wider block">Average Payout per Voucher</span>
            <span class="text-2xl font-bold text-blue-700 mt-1 block font-mono">
                PKR {{ $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0.00' }}
            </span>
            <span class="text-[11px] text-blue-600">Per settlement disbursement</span>
        </div>
    </div>

    <!-- SETTLEMENTS TABLE (Prompt Section 13) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Settlement Payout Vouchers</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $settlements->count() }} vouchers</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Voucher #</th>
                        <th class="p-3">Doctor Name</th>
                        <th class="p-3 text-right">Amount Settled</th>
                        <th class="p-3 text-center">Payment Method</th>
                        <th class="p-3">Reference / Remarks</th>
                        <th class="p-3">Paid By (Hospital)</th>
                        <th class="p-3 text-center">Date</th>
                        <th class="p-3 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($settlements as $set)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3 font-mono font-bold text-blue-600">
                            {{ $set->settlement_number }}
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-gray-900 block text-sm">{{ $set->doctor ? $set->doctor->name : 'N/A' }}</span>
                            <span class="text-[10px] text-gray-400">{{ $set->doctor ? $set->doctor->specialization : '' }}</span>
                        </td>
                        <td class="p-3 text-right font-mono font-black text-emerald-700 text-sm">
                            PKR {{ number_format($set->amount, 2) }}
                        </td>
                        <td class="p-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-100 text-slate-800 font-mono">
                                {{ str_replace('_', ' ', $set->payment_method) }}
                            </span>
                        </td>
                        <td class="p-3 text-gray-600">
                            {{ $set->reference_number ?: ($set->notes ?: '—') }}
                        </td>
                        <td class="p-3 text-gray-800 font-medium">
                            {{ $set->settler ? $set->settler->name : 'Admin' }}
                        </td>
                        <td class="p-3 text-center text-slate-500 font-mono">
                            {{ $set->settlement_date->format('d-M-Y') }}
                        </td>
                        <td class="p-3 text-center no-print">
                            <a href="{{ route('doctor-settlements.voucher', $set->id) }}" target="_blank" class="bg-slate-800 hover:bg-slate-900 text-white px-2.5 py-1.5 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1" title="Print Voucher">
                                <i class="fa-solid fa-print text-[10px]"></i>
                                <span>Voucher</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400">
                            <i class="fa-solid fa-receipt text-3xl mb-2 block"></i>
                            No settlements recorded matching the filter.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($settlements->count() > 0)
                <tfoot class="bg-slate-900 text-white font-bold border-t-2 border-slate-700">
                    <tr>
                        <td colspan="2" class="p-3 uppercase tracking-wider text-xs">Total Settled:</td>
                        <td class="p-3 text-right font-mono text-emerald-400 text-sm">PKR {{ number_format($totalAmount, 2) }}</td>
                        <td colspan="5" class="no-print"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
