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
        <h2 class="text-base font-bold text-gray-800 mt-1">Hospital Outstanding Receivables & Patient Aging Statement</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Total Unpaid:</strong> PKR {{ number_format($totalOutstanding, 2) }}</span>
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('reports.index') }}" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Hospital Outstanding</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-amber-600"></i>
                <span>Hospital Outstanding / Receivables Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Tracking all patients with pending balances across hospital procedures and consultation services</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <button onclick="window.print()" class="bg-slate-800 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow hover:bg-slate-900 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 no-print">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Outstanding Due</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1 font-mono">PKR {{ number_format($totalOutstanding, 2) }}</h3>
                <span class="text-[10px] text-amber-600 font-medium">Pending Collections</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Unpaid / Partial Invoices</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1 font-mono">{{ $totalBillsCount }}</h3>
                <span class="text-[10px] text-gray-500">Invoices awaiting settlement</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Debtor Patients</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1 font-mono">{{ $totalPatientsCount }}</h3>
                <span class="text-[10px] text-blue-600 font-medium">Distinct patients with dues</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-users"></i>
            </div>
        </div>
    </div>

    <!-- FILTER BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
        <form method="GET" action="{{ route('reports.hospital-service-outstanding') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search Patient / Bill # / Phone</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Type name, phone, or bill #..." class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
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
                <label class="block text-xs font-semibold text-gray-600 mb-1">Attending Doctor</label>
                <select name="doctor_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium">
                    <option value="">All Doctors</option>
                    @foreach($allDoctors as $doc)
                        <option value="{{ $doc->id }}" {{ ($doctorId ?? '') == $doc->id ? 'selected' : '' }}>{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <button type="submit" class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg text-xs transition flex items-center justify-center space-x-1.5">
                    <i class="fa-solid fa-filter"></i>
                    <span>Apply Filter</span>
                </button>
            </div>
        </form>
    </div>

    <!-- OUTSTANDING TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Patient Details</th>
                        <th class="py-3 px-4">Bill Reference</th>
                        <th class="py-3 px-4">Mode & Doctor</th>
                        <th class="py-3 px-4 text-right">Net Billed</th>
                        <th class="py-3 px-4 text-right">Amount Paid</th>
                        <th class="py-3 px-4 text-right">Outstanding Due</th>
                        <th class="py-3 px-4 text-center no-print">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                    @forelse($bills as $b)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4 font-sans">
                            <div class="font-bold text-gray-900">{{ $b->patient ? $b->patient->name : 'Patient' }}</div>
                            <div class="text-[10px] text-gray-500 flex items-center space-x-2">
                                <span class="font-mono">{{ $b->patient ? $b->patient->patient_number : '—' }}</span>
                                <span>&bull;</span>
                                <span>{{ $b->patient ? $b->patient->phone : 'No phone' }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <a href="{{ route('hospital-billing.show', $b->id) }}" class="font-bold text-blue-600 hover:underline">
                                {{ $b->bill_number }}
                            </a>
                            <div class="text-[10px] text-gray-400 font-sans mt-0.5">
                                {{ $b->bill_date->format('d M Y') }}
                                ({{ Carbon\Carbon::parse($b->bill_date)->diffForHumans() }})
                            </div>
                        </td>
                        <td class="py-3 px-4 font-sans">
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $b->billing_type === 'opd' ? 'bg-blue-100 text-blue-800' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ $b->billing_type === 'opd' ? 'OPD' : 'Direct' }}
                            </span>
                            <div class="text-[11px] text-gray-600 mt-1">{{ $b->doctor ? $b->doctor->name : 'Hospital Service' }}</div>
                        </td>
                        <td class="py-3 px-4 text-right font-bold text-gray-900">
                            PKR {{ number_format($b->net_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right text-emerald-600">
                            PKR {{ number_format($b->paid_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-right font-black text-amber-600 text-sm">
                            PKR {{ number_format($b->due_amount, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center no-print">
                            <a href="{{ route('hospital-billing.show', $b->id) }}" class="inline-flex items-center px-3 py-1 bg-amber-500 hover:bg-amber-600 text-white rounded text-[11px] font-bold transition">
                                <i class="fa-solid fa-hand-holding-dollar mr-1"></i>
                                <span>Collect</span>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400 font-sans">
                            No outstanding balances found! All bills are fully settled.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100 font-mono font-bold text-gray-900 border-t-2 border-slate-300">
                        <td colspan="3" class="py-3 px-4 font-sans text-right uppercase text-xs">Total Outstanding Receivables:</td>
                        <td class="py-3 px-4 text-right">PKR {{ number_format($totalGross, 2) }}</td>
                        <td class="py-3 px-4 text-right text-emerald-700">PKR {{ number_format($totalPaid, 2) }}</td>
                        <td class="py-3 px-4 text-right text-amber-700 text-sm">PKR {{ number_format($totalOutstanding, 2) }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
