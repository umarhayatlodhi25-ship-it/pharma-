@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-800">Reports Management System</h2>
            <p class="text-xs text-gray-500 mt-1">Comprehensive real-time analytics, financial statements & inventory reports</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Summary</span>
            </button>
        </div>
    </div>

    <!-- Overview Stats Banner -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Today's Sales</p>
                <h3 class="text-2xl font-bold text-blue-600 mt-1">PKR {{ number_format($todaySales ?? 0, 2) }}</h3>
            </div>
            <div class="bg-blue-50 text-blue-600 p-3.5 rounded-xl">
                <i class="fa-solid fa-chart-line text-xl"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">This Month Sales</p>
                <h3 class="text-2xl font-bold text-emerald-600 mt-1">PKR {{ number_format($monthlySales ?? 0, 2) }}</h3>
            </div>
            <div class="bg-emerald-50 text-emerald-600 p-3.5 rounded-xl">
                <i class="fa-solid fa-wallet text-xl"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Total Stock Units</p>
                <h3 class="text-2xl font-bold text-indigo-600 mt-1">{{ number_format($totalStockUnits ?? 0) }}</h3>
            </div>
            <div class="bg-indigo-50 text-indigo-600 p-3.5 rounded-xl">
                <i class="fa-solid fa-boxes-stacked text-xl"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase">Low Stock & Expiry</p>
                <h3 class="text-2xl font-bold text-red-600 mt-1">{{ ($expiredCount ?? 0) + ($lowStockCount ?? 0) }}</h3>
            </div>
            <div class="bg-red-50 text-red-600 p-3.5 rounded-xl">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Reports Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- 1. Sales Report -->
        <a href="/reports/sales" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fa-solid fa-file-invoice-dollar text-xl"></i>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">Report #1</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-blue-600 transition">1. Sales Report</h3>
            <p class="text-xs text-gray-500 mt-1">Daily, weekly, monthly and custom date range revenue analysis with payment method breakdown.</p>
        </a>

        <!-- 2. Purchase Report -->
        <a href="/reports/purchases" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fa-solid fa-truck-ramp-box text-xl"></i>
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Report #2</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-emerald-600 transition">2. Purchase Report</h3>
            <p class="text-xs text-gray-500 mt-1">Supplier purchase transactions, total costs, paid amounts vs outstanding due amounts.</p>
        </a>

        <!-- 3. Stock Report -->
        <a href="/reports/stock" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 text-indigo-600 p-3 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i class="fa-solid fa-boxes-packing text-xl"></i>
                </div>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">Report #3</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-indigo-600 transition">3. Stock Report</h3>
            <p class="text-xs text-gray-500 mt-1">Inventory stock quantities, purchase cost valuation vs retail valuation & potential profits.</p>
        </a>

        <!-- 4. Expiry Report -->
        <a href="/reports/expiry" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 text-amber-600 p-3 rounded-xl group-hover:bg-amber-600 group-hover:text-white transition">
                    <i class="fa-solid fa-calendar-xmark text-xl"></i>
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">Report #4</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-amber-600 transition">4. Expiry Report</h3>
            <p class="text-xs text-gray-500 mt-1">Expired batches and upcoming expiries (30, 60, 90 days) with financial risk valuation.</p>
        </a>

        <!-- 5. Profit & Loss Report -->
        <a href="/reports/profit-loss" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 text-purple-600 p-3 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fa-solid fa-scale-balanced text-xl"></i>
                </div>
                <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">Report #5</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-purple-600 transition">5. Profit & Loss Report</h3>
            <p class="text-xs text-gray-500 mt-1">Financial statement calculating Revenue minus COGS minus Discounts for net profit margins.</p>
        </a>

        <!-- 6. Customer Report -->
        <a href="/reports/customers" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-teal-100 text-teal-600 p-3 rounded-xl group-hover:bg-teal-600 group-hover:text-white transition">
                    <i class="fa-solid fa-users text-xl"></i>
                </div>
                <span class="text-xs font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full">Report #6</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-teal-600 transition">6. Customer Report</h3>
            <p class="text-xs text-gray-500 mt-1">Customer purchasing behavior, total order history & top spending customer leaderboard.</p>
        </a>

        <!-- 7. Supplier Report -->
        <a href="/reports/suppliers" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-cyan-100 text-cyan-600 p-3 rounded-xl group-hover:bg-cyan-600 group-hover:text-white transition">
                    <i class="fa-solid fa-building text-xl"></i>
                </div>
                <span class="text-xs font-bold text-cyan-600 bg-cyan-50 px-2.5 py-1 rounded-full">Report #7</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-cyan-600 transition">7. Supplier Report</h3>
            <p class="text-xs text-gray-500 mt-1">Supplier account summary, total purchases, payments made & pending payable balances.</p>
        </a>

        <!-- 8. Best Selling Report -->
        <a href="/reports/best-selling" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-rose-100 text-rose-600 p-3 rounded-xl group-hover:bg-rose-600 group-hover:text-white transition">
                    <i class="fa-solid fa-fire text-xl"></i>
                </div>
                <span class="text-xs font-bold text-rose-600 bg-rose-50 px-2.5 py-1 rounded-full">Report #8</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-rose-600 transition">8. Best Selling Report</h3>
            <p class="text-xs text-gray-500 mt-1">Top performing medicines ranked by quantities sold and total sales revenue generated.</p>
        </a>

        <!-- 9. Low Stock Report -->
        <a href="/reports/low-stock" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-red-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-red-100 text-red-600 p-3 rounded-xl group-hover:bg-red-600 group-hover:text-white transition">
                    <i class="fa-solid fa-layer-group text-xl"></i>
                </div>
                <span class="text-xs font-bold text-red-600 bg-red-50 px-2.5 py-1 rounded-full">Report #9</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-red-600 transition">9. Low Stock Report</h3>
            <p class="text-xs text-gray-500 mt-1">Medicines below alert threshold, current stock status & stock deficit reorder warnings.</p>
        </a>

        <!-- 10. Discount Analysis Report -->
        <a href="/reports/discounts" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-pink-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-pink-100 text-pink-600 p-3 rounded-xl group-hover:bg-pink-600 group-hover:text-white transition">
                    <i class="fa-solid fa-percent text-xl"></i>
                </div>
                <span class="text-xs font-bold text-pink-600 bg-pink-50 px-2.5 py-1 rounded-full">Report #10</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-pink-600 transition">10. Discount Report</h3>
            <p class="text-xs text-gray-500 mt-1">Comprehensive audit of discounts given, cashier authorization, discount-to-sales ratios & invoice audit.</p>
        </a>

        <!-- 11. OPD Reports -->
        <a href="/reports/opd" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-indigo-100 text-indigo-600 p-3 rounded-xl group-hover:bg-indigo-600 group-hover:text-white transition">
                    <i class="fa-solid fa-hospital-user text-xl"></i>
                </div>
                <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-full">Report #11</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-indigo-600 transition">11. OPD Reports</h3>
            <p class="text-xs text-gray-500 mt-1">Patient visits, tokens, doctor fees and OPD collections</p>
        </a>

        <!-- 12. Doctor Collection Report -->
        <a href="/reports/doctor-collection" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-xl group-hover:bg-blue-600 group-hover:text-white transition">
                    <i class="fa-solid fa-stethoscope text-xl"></i>
                </div>
                <span class="text-xs font-bold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full">Report #12</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-blue-600 transition">12. Doctor Collection</h3>
            <p class="text-xs text-gray-500 mt-1">Detailed patient-by-patient billing, doctor earned share on collected cash & unpaid breakdown.</p>
        </a>

        <!-- 13. All Doctors Collection -->
        <a href="/reports/all-doctors-collection" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-teal-100 text-teal-600 p-3 rounded-xl group-hover:bg-teal-600 group-hover:text-white transition">
                    <i class="fa-solid fa-users-viewfinder text-xl"></i>
                </div>
                <span class="text-xs font-bold text-teal-600 bg-teal-50 px-2.5 py-1 rounded-full">Report #13</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-teal-600 transition">13. All Doctors Summary</h3>
            <p class="text-xs text-gray-500 mt-1">Comparative performance matrix of all doctors with total billed, collected, split shares and current balance.</p>
        </a>

        <!-- 14. Hospital Overall Collection -->
        <a href="/reports/hospital-collection" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl group-hover:bg-emerald-600 group-hover:text-white transition">
                    <i class="fa-solid fa-building-columns text-xl"></i>
                </div>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">Report #14</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-emerald-600 transition">14. Hospital Revenue</h3>
            <p class="text-xs text-gray-500 mt-1">Executive overview: gross billing, net hospital revenue, doctor liability & actual cash in hand.</p>
        </a>

        <!-- 15. Hospital Services Report -->
        <a href="/reports/hospital-services" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-purple-100 text-purple-600 p-3 rounded-xl group-hover:bg-purple-600 group-hover:text-white transition">
                    <i class="fa-solid fa-hand-holding-medical text-xl"></i>
                </div>
                <span class="text-xs font-bold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full">Report #15</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-purple-600 transition">15. Hospital Services</h3>
            <p class="text-xs text-gray-500 mt-1">Per-service utilization breakdown (Sugar check, BP, Drips, Injections, Dressing, Nebulization).</p>
        </a>

        <!-- 16. Doctor Payable Report -->
        <a href="/reports/doctor-payable" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-amber-100 text-amber-600 p-3 rounded-xl group-hover:bg-amber-600 group-hover:text-white transition">
                    <i class="fa-solid fa-scale-balanced text-xl"></i>
                </div>
                <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2.5 py-1 rounded-full">Report #16</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-amber-600 transition">16. Doctor Payable</h3>
            <p class="text-xs text-gray-500 mt-1">Live doctor liabilities ledger, total earned vs settled, current balances with quick "Settle Now" actions.</p>
        </a>

        <!-- 17. Doctor Settlements History -->
        <a href="/reports/doctor-settlements" class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:border-blue-500 hover:shadow-md transition group">
            <div class="flex items-center justify-between mb-4">
                <div class="bg-slate-100 text-slate-800 p-3 rounded-xl group-hover:bg-slate-800 group-hover:text-white transition">
                    <i class="fa-solid fa-receipt text-xl"></i>
                </div>
                <span class="text-xs font-bold text-slate-700 bg-slate-100 px-2.5 py-1 rounded-full">Report #17</span>
            </div>
            <h3 class="font-bold text-gray-800 text-lg group-hover:text-slate-800 transition">17. Doctor Settlements</h3>
            <p class="text-xs text-gray-500 mt-1">Disbursal history of hospital payouts to doctors with voucher numbers, references & printable vouchers.</p>
        </a>

    </div>
</div>
@endsection
