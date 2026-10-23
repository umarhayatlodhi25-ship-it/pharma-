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
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Service Collection Report (Actual Cash Collections)</h2>
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
                <span>Hospital Service Collections</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
                <span>Hospital Service Collection Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Audit of actual payments and revenues collected at counter (cash basis)</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 no-print">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Collected</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">PKR {{ number_format($totalCollected, 2) }}</h3>
                <span class="text-[10px] text-gray-500">{{ $totalCount }} receipts issued</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Hospital Net Share</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1 font-mono">PKR {{ number_format($totalHospitalShare, 2) }}</h3>
                <span class="text-[10px] text-blue-600 font-medium">Hospital Retained</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-building-columns"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Doctor Split Share</p>
                <h3 class="text-2xl font-black text-indigo-600 mt-1 font-mono">PKR {{ number_format($totalDoctorShare, 2) }}</h3>
                <span class="text-[10px] text-indigo-600 font-medium">Earned Doctor Payable</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-center space-y-1 text-xs font-mono">
            <div class="flex justify-between text-gray-600">
                <span>Cash:</span>
                <span class="font-bold text-gray-900">PKR {{ number_format($cashTotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Card:</span>
                <span class="font-bold text-blue-600">PKR {{ number_format($cardTotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Bank/Online:</span>
                <span class="font-bold text-emerald-600">PKR {{ number_format($bankTotal, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.hospital-service-collections') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
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
                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Method</label>
                <select name="payment_method" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="">All Methods</option>
                    <option value="cash" {{ $paymentMethod == 'cash' ? 'selected' : '' }}>Cash Counter</option>
                    <option value="card" {{ $paymentMethod == 'card' ? 'selected' : '' }}>Card</option>
                    <option value="bank_transfer" {{ $paymentMethod == 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="other" {{ $paymentMethod == 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Cashier User</label>
                    <select name="user_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                        <option value="">All Cashiers</option>
                        @foreach($allUsers as $u)
                            <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 rounded-lg text-xs hover:bg-blue-700 transition" title="Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- COLLECTIONS TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Receipt #</th>
                        <th class="py-3 px-4">Date & Time</th>
                        <th class="py-3 px-4">Patient</th>
                        <th class="py-3 px-4">Bill Reference</th>
                        <th class="py-3 px-4">Method</th>
                        <th class="py-3 px-4 text-right">Amount Collected</th>
                        <th class="py-3 px-4 text-right">Hospital Share</th>
                        <th class="py-3 px-4 text-right">Doctor Share</th>
                        <th class="py-3 px-4 text-center">Cashier</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                    @forelse($payments as $p)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4 font-bold text-gray-900">{{ $p->payment_number }}</td>
                        <td class="py-3 px-4 text-gray-600 font-sans">
                            {{ $p->payment_date->format('d M Y') }}
                            <div class="text-[10px] text-gray-400 font-mono">{{ format_app_datetime($p->created_at, 'h:i A') }}</div>
                        </td>
                        <td class="py-3 px-4 font-sans">
                            <div class="font-bold text-gray-900">{{ $p->bill && $p->bill->patient ? $p->bill->patient->name : 'Patient' }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $p->bill && $p->bill->patient ? $p->bill->patient->patient_number : '—' }}</div>
                        </td>
                        <td class="py-3 px-4">
                            @if($p->bill)
                                <a href="{{ route('hospital-billing.show', $p->bill->id) }}" class="text-blue-600 hover:underline font-bold">
                                    {{ $p->bill->bill_number }}
                                </a>
                                <span class="px-1.5 py-0.2 rounded text-[9px] uppercase font-bold {{ $p->bill->billing_type === 'opd' ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700' }}">
                                    {{ $p->bill->billing_type === 'opd' ? 'OPD' : 'Direct' }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 font-sans capitalize">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $p->payment_method }}
                            </span>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600 text-sm">
                            PKR {{ number_format($p->amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-blue-600">
                            PKR {{ number_format($p->hospital_share, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-indigo-600">
                            PKR {{ number_format($p->doctor_share, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center font-sans text-gray-500">
                            {{ $p->receivedBy ? $p->receivedBy->name : 'System' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-8 text-gray-400 font-sans">
                            No collection records found for the selected period.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold text-gray-900 border-t-2 border-slate-300">
                        <td colspan="5" class="py-3 px-4 font-sans text-right uppercase text-xs">Total Collected:</td>
                        <td class="py-3 px-4 text-right text-emerald-700 text-sm">PKR {{ number_format($totalCollected, 2) }}</td>
                        <td class="py-3 px-4 text-right text-blue-700">PKR {{ number_format($totalHospitalShare, 2) }}</td>
                        <td class="py-3 px-4 text-right text-indigo-700">PKR {{ number_format($totalDoctorShare, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
