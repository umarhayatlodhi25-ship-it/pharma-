@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Customer Report</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800">6. Customer Report</h2>
            <p class="text-xs text-gray-500 mt-1">Customer purchase analytics and top spending customer leaderboard</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- Summary KPI Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Registered Customers</p>
            <h3 class="text-2xl font-bold text-teal-600 mt-1">{{ number_format($totalCustomers) }}</h3>
        </div>
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <p class="text-xs font-semibold text-gray-400 uppercase">Total Customer Purchase Volume</p>
            <h3 class="text-2xl font-bold text-emerald-600 mt-1">PKR {{ number_format($totalCustomerRevenue, 2) }}</h3>
        </div>
    </div>

    <!-- Customer Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Customer Purchase History Leaderboard</h3>
            <span class="text-xs text-gray-400 font-semibold">{{ $customerSales->count() }} active buyers</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 uppercase border-b border-gray-100 font-semibold">
                    <tr>
                        <th class="p-3.5">Customer Name</th>
                        <th class="p-3.5">Contact Info</th>
                        <th class="p-3.5 text-center">Total Orders</th>
                        <th class="p-3.5 text-right">Total Spent</th>
                        <th class="p-3.5 text-right text-rose-600">Outstanding</th>
                        <th class="p-3.5 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customerSales as $saleGroup)
                    @php
                        $cust = $customers->firstWhere('id', $saleGroup->customer_id);
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3.5 font-bold text-gray-800">{{ $cust->name ?? 'Walk-in Customer' }}</td>
                        <td class="p-3.5 text-gray-600">
                            {{ $cust->phone ?? 'N/A' }} 
                            <span class="block text-[10px] text-gray-400">{{ $cust->email ?? '' }}</span>
                        </td>
                        <td class="p-3.5 text-center font-bold text-blue-600">{{ number_format($saleGroup->total_orders) }}</td>
                        <td class="p-3.5 text-right font-extrabold text-emerald-600">PKR {{ number_format($saleGroup->total_spent, 2) }}</td>
                        <td class="p-3.5 text-right font-extrabold text-rose-600">
                            @if($cust && $cust->outstanding_balance > 0)
                                PKR {{ number_format($cust->outstanding_balance, 2) }}
                            @else
                                <span class="text-gray-400 font-normal">No Dues</span>
                            @endif
                        </td>
                        <td class="p-3.5 text-center">
                            @if($cust)
                                <a href="{{ route('pos.receive-payment', $cust->id) }}" target="_blank" class="bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white px-3 py-1.5 rounded-lg font-bold text-[10px] uppercase transition">
                                    Ledger / Pay
                                </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-400">No customer purchase data available yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
