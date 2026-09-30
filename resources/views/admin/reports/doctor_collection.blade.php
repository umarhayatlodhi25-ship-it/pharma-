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
            .shadow-sm, .shadow-md, .shadow-xs {
                box-shadow: none !important;
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
        <h2 class="text-base font-bold text-gray-800 mt-1">Doctor Collection & Split Share Report</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Doctor:</strong> {{ $selectedDoctor ? $selectedDoctor->name : 'All Doctors' }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Doctor Collection Report</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-stethoscope text-blue-600 text-xl"></i>
                <span>Doctor Collection Report (Detailed)</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Detailed patient-by-patient revenue split, doctor share liability and unpaid tracking</p>
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
        <form method="GET" action="{{ route('reports.doctor-collection') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
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
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Range</option>
                    <option value="all" {{ $filter == 'all' ? 'selected' : '' }}>All Time</option>
                </select>
            </div>

            <!-- Payment Status -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Payment Status</label>
                <select name="payment_status" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-none focus:border-blue-500">
                    <option value="all" {{ $paymentStatus == 'all' ? 'selected' : '' }}>All Statuses</option>
                    <option value="paid" {{ $paymentStatus == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ $paymentStatus == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="free" {{ $paymentStatus == 'free' ? 'selected' : '' }}>Free</option>
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
                <a href="{{ route('reports.doctor-collection') }}" class="p-2 bg-gray-100 text-gray-600 hover:bg-gray-200 rounded-lg text-xs font-semibold transition" title="Reset">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY METRICS CARDS -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Patients</span>
            <span class="text-xl font-bold text-slate-800 mt-1 block">{{ number_format($metrics['total_patients']) }}</span>
            <span class="text-[10px] text-gray-400">{{ $metrics['total_bills'] }} Total Bills</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100">
            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Total Billed</span>
            <span class="text-xl font-bold text-slate-900 mt-1 block font-mono">PKR {{ number_format($metrics['total_billed'], 2) }}</span>
            <span class="text-[10px] text-gray-400">Gross Billed</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-gray-100">
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider block">Collected</span>
            <span class="text-xl font-bold text-emerald-700 mt-1 block font-mono">PKR {{ number_format($metrics['total_collected'], 2) }}</span>
            <span class="text-[10px] text-emerald-500 font-semibold">Cash/Received</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-blue-100 bg-blue-50/20">
            <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider block">Doctor Share</span>
            <span class="text-xl font-bold text-blue-700 mt-1 block font-mono">PKR {{ number_format($metrics['doctor_total_share'], 2) }}</span>
            <span class="text-[10px] text-blue-500 font-semibold">Earned on Cash</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-indigo-100 bg-indigo-50/20">
            <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider block">Hospital Share</span>
            <span class="text-xl font-bold text-indigo-700 mt-1 block font-mono">PKR {{ number_format($metrics['hospital_total_share'], 2) }}</span>
            <span class="text-[10px] text-indigo-500 font-semibold">Hospital Net</span>
        </div>

        <div class="bg-white p-4 rounded-xl shadow-xs border border-amber-100 bg-amber-50/20">
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider block">Unpaid Balance</span>
            <span class="text-xl font-bold text-amber-700 mt-1 block font-mono">PKR {{ number_format($metrics['remaining_unpaid'], 2) }}</span>
            <span class="text-[10px] text-amber-500 font-semibold">Remaining Due</span>
        </div>
    </div>

    <!-- SELECTED DOCTOR PROFILE CARD (IF FILTERED) -->
    @if($selectedDoctor)
    <div class="bg-linear-to-r from-blue-900 to-indigo-900 text-white p-5 rounded-xl shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="text-[11px] font-bold uppercase tracking-wider text-blue-200">Selected Doctor Summary</span>
            <h3 class="text-xl font-bold text-white mt-0.5">{{ $selectedDoctor->name }}</h3>
            <p class="text-xs text-blue-200">{{ $selectedDoctor->specialization }} &bull; Base Fee: PKR {{ number_format($selectedDoctor->consultation_fee, 2) }}</p>
        </div>
        <div class="flex items-center gap-6 text-sm">
            <div>
                <span class="text-[11px] text-blue-200 block">Total Earned (All Time)</span>
                <span class="font-mono font-bold text-white text-base">PKR {{ number_format($selectedDoctor->total_earned, 2) }}</span>
            </div>
            <div>
                <span class="text-[11px] text-blue-200 block">Total Settled</span>
                <span class="font-mono font-bold text-emerald-300 text-base">PKR {{ number_format($selectedDoctor->total_paid, 2) }}</span>
            </div>
            <div class="bg-white/10 px-4 py-2 rounded-lg border border-white/20">
                <span class="text-[11px] text-amber-200 block font-semibold">Current Payable</span>
                <span class="font-mono font-black text-amber-300 text-lg">PKR {{ number_format($selectedDoctor->current_payable, 2) }}</span>
            </div>
            <a href="{{ route('doctor-ledgers.show', $selectedDoctor->id) }}" class="bg-blue-600 hover:bg-blue-500 text-white px-3 py-2 rounded-lg text-xs font-bold transition">
                View Ledger
            </a>
        </div>
    </div>
    @endif

    <!-- DETAILS TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Patient Billing & Split Records</h3>
            <span class="text-xs font-semibold text-gray-500">{{ $bills->count() }} records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Bill #</th>
                        <th class="p-3">Token #</th>
                        <th class="p-3">Patient</th>
                        <th class="p-3">Doctor</th>
                        <th class="p-3">Service</th>
                        <th class="p-3 text-right">Billed (PKR)</th>
                        <th class="p-3 text-right">Paid (PKR)</th>
                        <th class="p-3 text-right">Remaining</th>
                        <th class="p-3 text-right text-blue-700 bg-blue-50/50">Doctor Share</th>
                        <th class="p-3 text-right text-indigo-700 bg-indigo-50/50">Hospital Share</th>
                        <th class="p-3 text-center">Status</th>
                        <th class="p-3 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($bills as $bill)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3 font-mono font-bold text-blue-600">
                            <a href="{{ route('hospital-billing.show', $bill->id) }}" class="hover:underline">
                                {{ $bill->bill_number }}
                            </a>
                            <span class="block text-[10px] text-gray-400 font-normal">{{ $bill->bill_date->format('d-M-Y') }}</span>
                        </td>
                        <td class="p-3 font-mono font-semibold text-slate-800">
                            {{ $bill->token ? $bill->token->token_number : '—' }}
                        </td>
                        <td class="p-3">
                            <span class="font-bold text-gray-900 block">{{ $bill->patient ? $bill->patient->name : 'N/A' }}</span>
                            <span class="text-[10px] font-mono text-gray-400">{{ $bill->patient ? $bill->patient->patient_number : '' }}</span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-gray-800">{{ $bill->doctor ? $bill->doctor->name : 'N/A' }}</span>
                        </td>
                        <td class="p-3">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                {{ $bill->service ? $bill->service->name : 'Consultation' }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-slate-900">
                            {{ number_format($bill->total_amount, 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-600">
                            {{ number_format($bill->paid_amount, 2) }}
                        </td>
                        <td class="p-3 text-right font-mono {{ $bill->remaining_amount > 0 ? 'text-amber-600 font-bold' : 'text-gray-400' }}">
                            {{ number_format($bill->remaining_amount, 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-blue-700 bg-blue-50/30">
                            {{ number_format($bill->doctor_share, 2) }}
                            <span class="block text-[10px] text-gray-400 font-normal">({{ $bill->doctor_share_percentage }}%)</span>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-indigo-700 bg-indigo-50/30">
                            {{ number_format($bill->hospital_share, 2) }}
                            <span class="block text-[10px] text-gray-400 font-normal">({{ $bill->hospital_share_percentage }}%)</span>
                        </td>
                        <td class="p-3 text-center">
                            @if($bill->payment_status === 'paid')
                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Paid</span>
                            @elseif($bill->payment_status === 'partial')
                                <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Partial</span>
                            @elseif($bill->payment_status === 'free')
                                <span class="bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Free</span>
                            @else
                                <span class="bg-rose-100 text-rose-800 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Unpaid</span>
                            @endif
                        </td>
                        <td class="p-3 text-center no-print">
                            <a href="{{ route('hospital-billing.receipt', $bill->id) }}" target="_blank" class="text-slate-600 hover:text-blue-600 font-semibold p-1" title="Print Receipt">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="p-8 text-center text-gray-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2 block"></i>
                            No billing records match the selected filter criteria.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($bills->count() > 0)
                <tfoot class="bg-slate-100 font-bold text-slate-900 border-t-2 border-slate-300">
                    <tr>
                        <td colspan="5" class="p-3 text-right uppercase tracking-wider text-[11px]">Total Summary:</td>
                        <td class="p-3 text-right font-mono">{{ number_format($metrics['total_billed'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-emerald-700">{{ number_format($metrics['total_collected'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-amber-700">{{ number_format($metrics['remaining_unpaid'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-blue-700 bg-blue-100/50">{{ number_format($metrics['doctor_total_share'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-indigo-700 bg-indigo-100/50">{{ number_format($metrics['hospital_total_share'], 2) }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</div>
@endsection
