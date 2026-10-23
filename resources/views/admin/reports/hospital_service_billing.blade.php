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
                size: A4 landscape;
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
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Service Invoicing & Billing Audit Report</h2>
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
                <span>Hospital Service Billing</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-file-invoice text-blue-600"></i>
                <span>Hospital Service Billing Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Comprehensive audit of all invoices generated across OPD and direct hospital services</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4 no-print">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Gross Billed</p>
                <h3 class="text-xl font-black text-gray-900 mt-1 font-mono">PKR {{ number_format($totalGross, 2) }}</h3>
                <span class="text-[10px] text-gray-500">{{ $totalCount }} bills issued</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Discounts / Waivers</p>
                <h3 class="text-xl font-black text-purple-600 mt-1 font-mono">PKR {{ number_format($totalDiscount, 2) }}</h3>
                <span class="text-[10px] text-purple-600 font-medium">Free / Concessions</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-tags"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Net Amount</p>
                <h3 class="text-xl font-black text-blue-600 mt-1 font-mono">PKR {{ number_format($totalNet, 2) }}</h3>
                <span class="text-[10px] text-blue-600 font-medium">Gross - Discounts</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-file-lines"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Collected Amount</p>
                <h3 class="text-xl font-black text-emerald-600 mt-1 font-mono">PKR {{ number_format($totalCollected, 2) }}</h3>
                <span class="text-[10px] text-emerald-600 font-medium">Realized Collections</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-check-double"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Outstanding Due</p>
                <h3 class="text-xl font-black text-amber-600 mt-1 font-mono">PKR {{ number_format($totalOutstanding, 2) }}</h3>
                <span class="text-[10px] text-amber-600 font-medium">Unpaid Receivables</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-clock"></i>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.hospital-service-billing') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
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
                <label class="block text-xs font-semibold text-gray-600 mb-1">Billing Mode</label>
                <select name="billing_type" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="">All Modes</option>
                    <option value="opd" {{ ($billingMode ?? '') == 'opd' ? 'selected' : '' }}>Mode A: OPD Patient</option>
                    <option value="direct" {{ ($billingMode ?? '') == 'direct' ? 'selected' : '' }}>Mode B: Direct Service</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Status</label>
                <select name="payment_status" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ ($paymentStatus ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ ($paymentStatus ?? '') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="pending" {{ ($paymentStatus ?? '') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="free" {{ ($paymentStatus ?? '') == 'free' ? 'selected' : '' }}>Free</option>
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Doctor</label>
                    <select name="doctor_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                        <option value="">All Doctors</option>
                        @foreach($allDoctors as $doc)
                            <option value="{{ $doc->id }}" {{ ($doctorId ?? '') == $doc->id ? 'selected' : '' }}>{{ $doc->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 rounded-lg text-xs hover:bg-blue-700 transition" title="Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- BILLING TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Bill # / Date</th>
                        <th class="py-3 px-4">Mode</th>
                        <th class="py-3 px-4">Patient</th>
                        <th class="py-3 px-4">Services Prescribed / Items</th>
                        <th class="py-3 px-4 text-right">Gross Total</th>
                        <th class="py-3 px-4 text-right">Discount</th>
                        <th class="py-3 px-4 text-right">Net Payable</th>
                        <th class="py-3 px-4 text-right">Collected</th>
                        <th class="py-3 px-4 text-right">Outstanding Due</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                    @forelse($bills as $b)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4">
                            <a href="{{ route('hospital-billing.show', $b->id) }}" class="font-bold text-blue-600 hover:underline">
                                {{ $b->bill_number }}
                            </a>
                            <div class="text-[10px] text-gray-400 font-sans mt-0.5">{{ $b->bill_date->format('d M Y') }}</div>
                        </td>
                        <td class="py-3 px-4 font-sans">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $b->billing_type === 'opd' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $b->billing_type === 'opd' ? 'OPD' : 'Direct' }}
                            </span>
                        </td>
                        <td class="py-3 px-4 font-sans">
                            <div class="font-bold text-gray-900">{{ $b->patient ? $b->patient->name : 'Patient' }}</div>
                            <div class="text-[10px] text-gray-400 font-mono">{{ $b->patient ? $b->patient->patient_number : '—' }}</div>
                        </td>
                        <td class="py-3 px-4 font-sans">
                            @if($b->items->count() > 0)
                                <div class="text-gray-800 font-medium">
                                    {{ $b->items->pluck('service_name')->join(', ') }}
                                </div>
                                <div class="text-[10px] text-gray-400 font-mono">{{ $b->items->count() }} item(s)</div>
                            @else
                                <div class="text-gray-800 font-medium">{{ $b->service ? $b->service->name : 'Hospital Service' }}</div>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-gray-900">
                            PKR {{ number_format($b->total_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-purple-600">
                            {{ $b->discount_amount > 0 ? '- ' . number_format($b->discount_amount, 2) : '—' }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-blue-700">
                            PKR {{ number_format($b->net_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600">
                            PKR {{ number_format($b->paid_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-bold {{ $b->due_amount > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                            PKR {{ number_format($b->due_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center font-sans">
                            @if($b->payment_status === 'paid')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Paid</span>
                            @elseif($b->payment_status === 'partial')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Partial</span>
                            @elseif($b->payment_status === 'free')
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">Free</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-8 text-gray-400 font-sans">
                            No billing records found for the selected criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold text-gray-900 border-t-2 border-slate-300">
                        <td colspan="4" class="py-3 px-4 font-sans text-right uppercase text-xs">Grand Totals:</td>
                        <td class="py-3 px-4 text-right">PKR {{ number_format($totalGross, 2) }}</td>
                        <td class="py-3 px-4 text-right text-purple-600">- PKR {{ number_format($totalDiscount, 2) }}</td>
                        <td class="py-3 px-4 text-right text-blue-700">PKR {{ number_format($totalNet, 2) }}</td>
                        <td class="py-3 px-4 text-right text-emerald-700">PKR {{ number_format($totalCollected, 2) }}</td>
                        <td class="py-3 px-4 text-right text-amber-700">PKR {{ number_format($totalOutstanding, 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
