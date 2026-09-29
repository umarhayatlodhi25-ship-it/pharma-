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
            .border {
                border-color: #cbd5e1 !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            th, td {
                border: 1px solid #cbd5e1 !important;
                padding: 6px 8px !important;
                color: #000000 !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm;
            }
        }
        @media screen {
            .print-only {
                display: none !important;
            }
        }
    </style>

    <!-- PRINT-ONLY HEADER BANNER -->
    <div class="print-only mb-6 border-b-2 border-black pb-4 text-center">
        <h1 class="text-xl font-black uppercase tracking-wider">{{ config('app.name', 'PHARMACY / HOSPITAL') }}</h1>
        <h2 class="text-base font-bold text-gray-800 mt-1">OPD Collection & Visit Report</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Date Range:</strong> {{ Carbon\Carbon::parse($startDate)->format('d M Y') }} — {{ Carbon\Carbon::parse($endDate)->format('d M Y') }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>OPD Reports</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-hospital-user text-blue-600 text-xl"></i>
                <span>OPD Reports</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Patient visits, tokens, doctor fees and OPD collections</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow-xs hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- TOP FILTERS (Section 2 & 9 & 10) -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 no-print">
        <form method="GET" action="/reports/opd" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
            <!-- Time Period Preset -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                </select>
            </div>

            <!-- Start Date -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date From</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Date To</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
            </div>

            <!-- Doctor Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Doctor</label>
                <select name="doctor_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="">All Doctors</option>
                    @foreach($allDoctors as $doc)
                        <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>
                            {{ $doc->name }} {{ $doc->specialization ? '('.$doc->specialization.')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Fee Type Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Fee Type</label>
                <select name="fee_type" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="">All Fee Types</option>
                    <option value="paid" {{ $feeType == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="free" {{ $feeType == 'free' ? 'selected' : '' }}>Free</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="waiting" {{ $status == 'waiting' ? 'selected' : '' }}>Waiting</option>
                    <option value="in_consultation" {{ $status == 'in_consultation' ? 'selected' : '' }}>In Consultation</option>
                    <option value="completed" {{ $status == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ $status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <!-- Buttons -->
            <div class="sm:col-span-2 md:col-span-6 flex justify-end space-x-2 pt-2">
                <button type="submit" class="bg-slate-800 text-white py-2 px-5 rounded-lg text-xs font-bold hover:bg-slate-900 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-filter text-[11px]"></i>
                    <span>Apply Filters</span>
                </button>
                <a href="/reports/opd" class="bg-gray-100 text-gray-600 py-2 px-4 rounded-lg text-xs font-bold hover:bg-gray-200 transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-rotate-left text-[11px]"></i>
                    <span>Reset</span>
                </a>
            </div>
        </form>
    </div>

    <!-- SUMMARY KPI CARDS (Section 3) -->
    <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
        <!-- 1. Total OPD Patients -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Patients</p>
            <h3 class="text-2xl font-black text-gray-800 mt-1 font-mono">{{ $totalPatients }}</h3>
            <span class="text-[11px] text-gray-400">Unique visitors</span>
        </div>

        <!-- 2. Total Tokens -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Tokens</p>
            <h3 class="text-2xl font-black text-blue-600 mt-1 font-mono">{{ $totalTokens }}</h3>
            <span class="text-[11px] text-gray-400">Issued slips</span>
        </div>

        <!-- 3. Paid Patients -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Paid Patients</p>
            <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">{{ $paidPatients }}</h3>
            <span class="text-[11px] text-emerald-600 font-medium">Paying visits</span>
        </div>

        <!-- 4. Free Patients -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Free Patients</p>
            <h3 class="text-2xl font-black text-purple-600 mt-1 font-mono">{{ $freePatients }}</h3>
            <span class="text-[11px] text-purple-600 font-medium">Complimentary</span>
        </div>

        <!-- 5. Total Doctor Fees -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
            <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Doctor Fees</p>
            <h3 class="text-xl font-black text-slate-800 mt-1 font-mono">PKR {{ number_format($totalDoctorFees, 0) }}</h3>
            <span class="text-[11px] text-gray-400">Before exemptions</span>
        </div>

        <!-- 6. Total Collection -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 bg-emerald-50/20">
            <p class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Total Collection</p>
            <h3 class="text-xl font-black text-emerald-700 mt-1 font-mono">PKR {{ number_format($totalCollection, 0) }}</h3>
            <span class="text-[11px] text-purple-600 font-semibold">Free: PKR {{ number_format($totalFreeAmount, 0) }}</span>
        </div>
    </div>

    <!-- ALL DOCTORS COLLECTION REPORT (Section 5, 6, 7, 8) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="font-bold text-gray-800 text-base flex items-center space-x-2">
                    <i class="fa-solid fa-user-doctor text-blue-600"></i>
                    <span>Doctor-wise Collection</span>
                </h3>
                <p class="text-xs text-gray-500">Summary of consultation fees, actual amounts collected, and collection rates</p>
            </div>
            <span class="text-xs font-semibold text-gray-500 bg-gray-50 px-3 py-1 rounded-full border border-gray-200 self-start sm:self-auto">
                {{ count($doctorReports) }} Doctors Listed
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 uppercase border-b border-gray-200 font-bold tracking-wider">
                    <tr>
                        <th class="p-3">Doctor</th>
                        <th class="p-3 text-center">Total Patients</th>
                        <th class="p-3 text-center">Paid Patients</th>
                        <th class="p-3 text-center">Free Patients</th>
                        <th class="p-3 text-right">Doctor Fees</th>
                        <th class="p-3 text-right">Collected</th>
                        <th class="p-3 text-right">Free Amount</th>
                        <th class="p-3 text-center">Collection %</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($doctorReports as $docReport)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="p-3">
                                <span class="font-bold text-gray-900 block text-sm">{{ $docReport['doctor']->name }}</span>
                                <span class="text-[11px] text-gray-500">{{ $docReport['doctor']->specialization ?: 'General Practitioner' }}</span>
                            </td>
                            <td class="p-3 text-center font-mono font-semibold">{{ $docReport['total_patients'] }}</td>
                            <td class="p-3 text-center font-mono text-emerald-600 font-semibold">{{ $docReport['paid_patients'] }}</td>
                            <td class="p-3 text-center font-mono text-purple-600 font-semibold">{{ $docReport['free_patients'] }}</td>
                            <td class="p-3 text-right font-mono font-semibold text-gray-700">
                                PKR {{ number_format($docReport['doctor_fees'], 0) }}
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-emerald-700">
                                PKR {{ number_format($docReport['collected'], 0) }}
                            </td>
                            <td class="p-3 text-right font-mono font-semibold text-purple-700">
                                PKR {{ number_format($docReport['free_amount'], 0) }}
                            </td>
                            <td class="p-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold font-mono
                                    {{ $docReport['collection_pct'] >= 80 ? 'bg-emerald-100 text-emerald-800' : ($docReport['collection_pct'] > 0 ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600') }}">
                                    {{ number_format($docReport['collection_pct'], 1) }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-400">No doctor records found.</td>
                        </tr>
                    @endforelse
                </tbody>
                <!-- GRAND TOTAL ROW (Section 8) -->
                <tfoot class="bg-slate-100 border-t-2 border-slate-300 font-bold text-slate-900">
                    <tr>
                        <td class="p-3.5 text-xs font-black uppercase tracking-wider">
                            GRAND TOTAL
                        </td>
                        <td class="p-3.5 text-center font-mono text-sm font-black">{{ $grandTotal['total_patients'] }}</td>
                        <td class="p-3.5 text-center font-mono text-sm font-black text-emerald-700">{{ $grandTotal['paid_patients'] }}</td>
                        <td class="p-3.5 text-center font-mono text-sm font-black text-purple-700">{{ $grandTotal['free_patients'] }}</td>
                        <td class="p-3.5 text-right font-mono text-sm font-black text-slate-800">
                            PKR {{ number_format($grandTotal['doctor_fees'], 0) }}
                        </td>
                        <td class="p-3.5 text-right font-mono text-sm font-black text-emerald-700">
                            PKR {{ number_format($grandTotal['collected'], 0) }}
                        </td>
                        <td class="p-3.5 text-right font-mono text-sm font-black text-purple-700">
                            PKR {{ number_format($grandTotal['free_amount'], 0) }}
                        </td>
                        <td class="p-3.5 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black font-mono bg-slate-900 text-white">
                                {{ number_format($grandTotal['collection_pct'], 1) }}%
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- OPD VISIT DETAIL TABLE (Section 4) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
                <h3 class="font-bold text-gray-800 text-base flex items-center space-x-2">
                    <i class="fa-solid fa-list-check text-blue-600"></i>
                    <span>OPD Visit Detail Table</span>
                </h3>
                <p class="text-xs text-gray-500">Individual patient consultations, fee categories, and consultation statuses</p>
            </div>
            <span class="text-xs text-gray-500 font-semibold bg-gray-50 px-3 py-1 rounded-full border border-gray-200 self-start sm:self-auto">
                {{ $tokens->count() }} Visits Found
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 uppercase border-b border-gray-200 font-bold tracking-wider">
                    <tr>
                        <th class="p-3">Token</th>
                        <th class="p-3">Date</th>
                        <th class="p-3">Time</th>
                        <th class="p-3">Patient</th>
                        <th class="p-3">Doctor</th>
                        <th class="p-3 text-right">Doctor Fee</th>
                        <th class="p-3 text-center">Fee Type</th>
                        <th class="p-3">Free Reason</th>
                        <th class="p-3 text-right">Final Amount</th>
                        <th class="p-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($tokens as $t)
                        <tr class="hover:bg-gray-50/80 transition">
                            <!-- Token Number -->
                            <td class="p-3 font-mono font-bold text-blue-600 text-sm">
                                #{{ $t->formatted_token_number }}
                            </td>

                            <!-- Date -->
                            <td class="p-3 text-gray-600 whitespace-nowrap">
                                {{ Carbon\Carbon::parse($t->token_date)->format('d M Y') }}
                            </td>

                            <!-- Time -->
                            <td class="p-3 text-gray-600 font-mono whitespace-nowrap">
                                {{ $t->created_at ? $t->created_at->format('h:i A') : '—' }}
                            </td>

                            <!-- Patient -->
                            <td class="p-3">
                                <span class="font-bold text-gray-900 block">{{ $t->patient ? $t->patient->name : 'Walk-in' }}</span>
                                <span class="text-[11px] text-gray-500 font-mono">
                                    {{ $t->patient ? $t->patient->patient_number : '' }}
                                    @if($t->patient && $t->patient->phone)
                                        • {{ $t->patient->phone }}
                                    @endif
                                </span>
                            </td>

                            <!-- Doctor -->
                            <td class="p-3">
                                <span class="font-semibold text-gray-800 block">{{ $t->doctor ? $t->doctor->name : '—' }}</span>
                                <span class="text-[11px] text-gray-500">{{ $t->doctor ? $t->doctor->specialization : '' }}</span>
                            </td>

                            <!-- Doctor Fee -->
                            <td class="p-3 text-right font-mono font-semibold text-gray-700 whitespace-nowrap">
                                PKR {{ number_format($t->consultation_fee, 0) }}
                            </td>

                            <!-- Fee Type -->
                            <td class="p-3 text-center">
                                @if(strtolower($t->payment_type) === 'free')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 uppercase border border-purple-200">
                                        Free
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 uppercase border border-blue-200">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            <!-- Free Reason -->
                            <td class="p-3 text-gray-600">
                                @if(strtolower($t->payment_type) === 'free' && $t->free_reason)
                                    <span class="inline-block text-purple-800 font-medium">
                                        {{ $t->free_reason }}
                                        @if($t->free_reason === 'Other' && $t->other_reason)
                                            <span class="text-[11px] text-gray-500">({{ $t->other_reason }})</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>

                            <!-- Final Amount -->
                            <td class="p-3 text-right whitespace-nowrap font-mono">
                                @if(strtolower($t->payment_type) === 'free' || floatval($t->charged_amount) == 0)
                                    <span class="font-black text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                        FREE
                                    </span>
                                @else
                                    <span class="font-bold text-emerald-700">
                                        PKR {{ number_format($t->charged_amount, 0) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="p-3 text-center">
                                @php
                                    $st = strtolower($t->status);
                                @endphp
                                @if($st === 'waiting')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 uppercase border border-amber-200">
                                        Waiting
                                    </span>
                                @elseif($st === 'in_consultation' || $st === 'called')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase border border-blue-200">
                                        In Consultation
                                    </span>
                                @elseif($st === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 uppercase border border-emerald-200">
                                        Completed
                                    </span>
                                @elseif($st === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 uppercase border border-rose-200">
                                        Cancelled
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 uppercase">
                                        {{ $t->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-gray-400">
                                No OPD visits found for the selected filter parameters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
