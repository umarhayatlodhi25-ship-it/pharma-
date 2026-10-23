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
    @if($selectedPatient)
    <div class="print-only mb-6 border-b-2 border-black pb-4 text-center">
        <h1 class="text-xl font-black uppercase tracking-wider">{{ config('app.name', 'HOSPITAL MANAGEMENT') }}</h1>
        <h2 class="text-base font-bold text-gray-800 mt-1">Patient Clinical & Hospital Billing Statement</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Patient:</strong> {{ $selectedPatient->name }} ({{ $selectedPatient->patient_number }})</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>
    @endif

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Patient Service History</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-hospital-user text-indigo-600"></i>
                <span>Patient Clinical & Hospital Billing History</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Chronological record of OPD visits, medical procedures, payments, and account ledger statements</p>
        </div>
        @if($selectedPatient)
        <div class="mt-4 md:mt-0 flex space-x-3 no-print">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print History</span>
            </button>
        </div>
        @endif
    </div>

    <!-- PATIENT SELECTOR & SEARCH BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.patient-service-history') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Select from Recent Patients</label>
                <select name="patient_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="">-- Choose Patient --</option>
                    @foreach($patients as $p)
                        <option value="{{ $p->id }}" {{ ($selectedPatient && $selectedPatient->id == $p->id) ? 'selected' : '' }}>
                            {{ $p->name }} ({{ $p->patient_number }}) - {{ $p->phone }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Or Search by Name, Phone, CNIC</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Type name, phone, or MR #..." class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
            </div>

            <div>
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Search History</span>
                </button>
            </div>
        </form>
    </div>

    @if($selectedPatient)
        <!-- PATIENT PROFILE HEADER CARD -->
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-100">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-full bg-indigo-600 text-white flex items-center justify-center font-bold text-lg shadow-sm">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-900">{{ $selectedPatient->name }}</h3>
                        <p class="text-xs text-gray-500 flex items-center space-x-2 mt-0.5">
                            <span class="font-mono font-bold text-gray-700">{{ $selectedPatient->patient_number }}</span>
                            <span>&bull;</span>
                            <span>{{ $selectedPatient->gender }}, {{ $selectedPatient->age }} Years</span>
                            <span>&bull;</span>
                            <span><i class="fa-solid fa-phone text-[10px] mr-1"></i>{{ $selectedPatient->phone ?: 'No phone' }}</span>
                        </p>
                    </div>
                </div>

                <!-- SEPARATE DEBT BREAKDOWN BADGES -->
                <div class="flex items-center space-x-2 font-mono text-xs">
                    <div class="p-2.5 rounded-lg bg-amber-50 border border-amber-200">
                        <span class="text-[9px] uppercase font-bold text-amber-700 block">Consultation Debt</span>
                        <span class="font-bold text-amber-900">PKR {{ number_format($selectedPatient->consultation_outstanding, 2) }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-red-50 border border-red-200">
                        <span class="text-[9px] uppercase font-bold text-red-700 block">Hospital Service Debt</span>
                        <span class="font-bold text-red-900">PKR {{ number_format($selectedPatient->hospital_service_outstanding, 2) }}</span>
                    </div>
                    <div class="p-2.5 rounded-lg bg-slate-100 border border-slate-200">
                        <span class="text-[9px] uppercase font-bold text-slate-700 block">Total Due</span>
                        <span class="font-black text-slate-900">PKR {{ number_format($selectedPatient->pending_balance, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- 1. HOSPITAL SERVICE BILLS SECTION -->
            <div class="space-y-3 pt-2">
                <h4 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                    <i class="fa-solid fa-file-invoice text-blue-600"></i>
                    <span>Hospital Service Invoices & Procedures</span>
                </h4>

                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                <th class="py-2.5 px-3">Bill #</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Mode</th>
                                <th class="py-2.5 px-3">Items / Services</th>
                                <th class="py-2.5 px-3 text-right">Net Amount</th>
                                <th class="py-2.5 px-3 text-right">Paid</th>
                                <th class="py-2.5 px-3 text-right">Remaining Due</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                                <th class="py-2.5 px-3 text-right no-print">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-mono">
                            @forelse($selectedPatient->bills as $bill)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-gray-900">{{ $bill->bill_number }}</td>
                                <td class="py-2.5 px-3 font-sans">{{ $bill->bill_date->format('d M Y') }}</td>
                                <td class="py-2.5 px-3 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $bill->billing_type === 'opd' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                        {{ $bill->billing_type === 'opd' ? 'OPD' : 'Direct' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-sans">
                                    @if($bill->items->count() > 0)
                                        {{ $bill->items->pluck('service_name')->join(', ') }}
                                    @else
                                        {{ $bill->service ? $bill->service->name : 'Hospital Service' }}
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-right font-bold text-gray-900">PKR {{ number_format($bill->net_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">PKR {{ number_format($bill->paid_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold {{ $bill->due_amount > 0 ? 'text-amber-600' : 'text-gray-400' }}">PKR {{ number_format($bill->due_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-center font-sans capitalize">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $bill->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                        {{ $bill->payment_status }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 text-right font-sans no-print">
                                    <a href="{{ route('hospital-billing.show', $bill->id) }}" class="text-blue-600 hover:underline font-bold text-[11px]">View &rarr;</a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-gray-400 font-sans">No hospital service bills recorded for this patient.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 2. OPD CONSULTATION TOKENS SECTION -->
            <div class="space-y-3 pt-4 border-t border-gray-100">
                <h4 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                    <i class="fa-solid fa-ticket-simple text-blue-600"></i>
                    <span>OPD Consultation Tokens</span>
                </h4>

                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                <th class="py-2.5 px-3">Token #</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Doctor</th>
                                <th class="py-2.5 px-3 text-right">Fee</th>
                                <th class="py-2.5 px-3 text-right">Paid</th>
                                <th class="py-2.5 px-3 text-right">Balance</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-mono">
                            @forelse($selectedPatient->tokens as $tok)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-blue-600">#{{ $tok->formatted_token_number }}</td>
                                <td class="py-2.5 px-3 font-sans">{{ $tok->token_date->format('d M Y') }}</td>
                                <td class="py-2.5 px-3 font-sans font-bold text-gray-800">{{ $tok->doctor ? $tok->doctor->name : 'N/A' }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-gray-900">PKR {{ number_format($tok->consultation_fee, 2) }}</td>
                                <td class="py-2.5 px-3 text-right text-emerald-600">PKR {{ number_format($tok->charged_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right {{ $tok->balance_due > 0 ? 'text-amber-600 font-bold' : 'text-gray-400' }}">PKR {{ number_format($tok->balance_due, 2) }}</td>
                                <td class="py-2.5 px-3 text-center font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold capitalize bg-slate-100 text-slate-700">{{ $tok->status }}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-gray-400 font-sans">No OPD tokens found for this patient.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 3. HOSPITAL ACCOUNT LEDGER SECTION -->
            <div class="space-y-3 pt-4 border-t border-gray-100">
                <h4 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                    <i class="fa-solid fa-book-journal-whills text-emerald-600"></i>
                    <span>Hospital Account Financial Ledger Trail</span>
                </h4>

                <div class="overflow-x-auto border border-gray-100 rounded-xl">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-emerald-50/60 text-emerald-800 font-bold border-b border-emerald-100 text-[11px]">
                                <th class="py-2.5 px-3">Voucher #</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Type</th>
                                <th class="py-2.5 px-3">Description</th>
                                <th class="py-2.5 px-3 text-right">Amount Received</th>
                                <th class="py-2.5 px-3 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-mono text-gray-700">
                            @forelse($selectedPatient->hospitalLedgers as $led)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-gray-900">{{ $led->transaction_number }}</td>
                                <td class="py-2.5 px-3 font-sans">{{ $led->entry_date->format('d M Y') }}</td>
                                <td class="py-2.5 px-3 font-sans">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $led->transaction_type === 'collection' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $led->transaction_type }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-sans">{{ $led->description }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">
                                    {{ $led->amount_received > 0 ? 'PKR ' . number_format($led->amount_received, 2) : '—' }}
                                </td>
                                <td class="py-2.5 px-3 text-right">PKR {{ number_format($led->outstanding_balance, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-400 font-sans">No financial ledger entries found for this patient.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center space-y-3">
            <div class="w-16 h-16 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-2xl">
                <i class="fa-solid fa-user-magnifying-glass"></i>
            </div>
            <h3 class="text-base font-bold text-gray-800">Select or Search a Patient</h3>
            <p class="text-xs text-gray-500 max-w-md mx-auto">Choose a patient from the dropdown above or enter their name/phone number to view their complete service history.</p>
        </div>
    @endif

</div>
@endsection
