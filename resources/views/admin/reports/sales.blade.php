@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Sales Report</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">1. Sales Report</h2>
            <p class="text-xs text-gray-500 mt-1">Detailed revenue, transaction counts and payment breakdown</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <form method="GET" action="/reports/sales" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Method</label>
                <select name="payment_method" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="">All Payment Methods</option>
                    <option value="cash" {{ $paymentMethod == 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="card" {{ $paymentMethod == 'card' ? 'selected' : '' }}>Card</option>
                    <option value="easypaisa" {{ $paymentMethod == 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                    <option value="bank_transfer" {{ $paymentMethod == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="w-full bg-slate-800 text-white py-2 px-3 rounded-lg text-xs font-bold hover:bg-slate-900 transition">
                    Filter
                </button>
                <a href="/reports/sales" class="bg-gray-100 text-gray-600 py-2 px-3 rounded-lg text-xs font-bold hover:bg-gray-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Total Sales Revenue</p>
            <h3 class="text-2xl font-bold text-blue-600 mt-1">PKR {{ number_format($totalRevenue, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Total Transactions</p>
            <h3 class="text-2xl font-bold text-emerald-600 mt-1">{{ number_format($totalTransactions) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Total Discounts Given</p>
            <h3 class="text-2xl font-bold text-amber-600 mt-1">PKR {{ number_format($totalDiscount, 2) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Avg Transaction Value</p>
            <h3 class="text-2xl font-bold text-indigo-600 mt-1">PKR {{ number_format($avgSaleValue, 2) }}</h3>
        </div>
    </div>

    <!-- Payment Breakdown -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-blue-50/50 p-4 rounded-xl border border-blue-100 text-center">
            <p class="text-[11px] font-bold text-blue-600 uppercase">Cash Sales</p>
            <p class="text-lg font-bold text-gray-800 mt-0.5">PKR {{ number_format($paymentBreakdown['cash'], 2) }}</p>
        </div>
        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100 text-center">
            <p class="text-[11px] font-bold text-emerald-600 uppercase">Card Sales</p>
            <p class="text-lg font-bold text-gray-800 mt-0.5">PKR {{ number_format($paymentBreakdown['card'], 2) }}</p>
        </div>
        <div class="bg-amber-50/50 p-4 rounded-xl border border-amber-100 text-center">
            <p class="text-[11px] font-bold text-amber-600 uppercase">Easypaisa</p>
            <p class="text-lg font-bold text-gray-800 mt-0.5">PKR {{ number_format($paymentBreakdown['easypaisa'], 2) }}</p>
        </div>
        <div class="bg-purple-50/50 p-4 rounded-xl border border-purple-100 text-center">
            <p class="text-[11px] font-bold text-purple-600 uppercase">Bank Transfer</p>
            <p class="text-lg font-bold text-gray-800 mt-0.5">PKR {{ number_format($paymentBreakdown['bank_transfer'], 2) }}</p>
        </div>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Detailed Sales Transactions</h3>
            <span class="text-xs text-gray-400 font-semibold">{{ $sales->count() }} records found</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase border-b border-gray-100 font-semibold">
                    <tr>
                        <th class="p-3.5">Invoice #</th>
                        <th class="p-3.5">Date & Time</th>
                        <th class="p-3.5">Sold By</th>
                        <th class="p-3.5">Payment Method</th>
                        <th class="p-3.5 text-center">Status</th>
                        <th class="p-3.5 text-right">Total Amount</th>
                        <th class="p-3.5 text-right">Paid Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3.5 font-bold text-blue-600">
                            <a href="/sales/{{ $sale->id }}" class="hover:underline">{{ $sale->invoice_number }}</a>
                        </td>
                        <td class="p-3.5 text-gray-600">{{ $sale->created_at->format('d M Y, h:i A') }}</td>
                        <td class="p-3.5 font-medium text-gray-700">{{ $sale->user->name ?? 'Admin' }}</td>
                        <td class="p-3.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $sale->payment_method == 'cash' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $sale->payment_method == 'card' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $sale->payment_method == 'easypaisa' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $sale->payment_method == 'bank_transfer' ? 'bg-purple-100 text-purple-700' : '' }}
                            ">
                                {{ $sale->payment_method }}
                            </span>
                        </td>
                        <td class="p-3.5 text-center">
                            @if($sale->payment_status === 'paid')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">Paid</span>
                            @elseif($sale->payment_status === 'partial')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-amber-100 text-amber-700">Partial</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-rose-100 text-rose-700">Unpaid</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-right font-bold text-gray-800">PKR {{ number_format($sale->total_amount, 2) }}</td>
                        <td class="p-3.5 text-right font-bold text-emerald-600">PKR {{ number_format($sale->paid_amount, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">No sales transactions found for the selected filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
