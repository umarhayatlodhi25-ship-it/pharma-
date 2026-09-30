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
        <h2 class="text-base font-bold text-gray-800 mt-1">Doctor Payable Liability Statement</h2>
        <div class="flex justify-between items-center text-xs text-gray-600 mt-2">
            <span><strong>Generated:</strong> {{ now()->format('d M Y, h:i A') }}</span>
        </div>
    </div>

    <!-- ON-SCREEN HEADER -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between border-b border-gray-200 pb-4 no-print">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/reports" class="hover:underline">Reports</a>
                <span>/</span>
                <span>Doctor Payable Report</span>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-scale-balanced text-blue-600 text-xl"></i>
                <span>Doctor Payable Report</span>
            </h2>
            <p class="text-xs text-gray-500 mt-1">Outstanding liabilities owed to doctors, settlement history and quick payout actions</p>
        </div>
        <div class="mt-4 md:mt-0 flex space-x-3">
            <a href="{{ route('reports.doctor-settlements') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-semibold text-xs transition flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Settlement History</span>
            </a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-semibold text-xs shadow-xs hover:bg-blue-700 transition flex items-center space-x-2">
                <i class="fa-solid fa-print"></i>
                <span>Print Report</span>
            </button>
        </div>
    </div>

    <!-- SUMMARY KPI CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl shadow-xs border border-gray-100">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider block">Total Doctors Earned (All Time)</span>
            <span class="text-2xl font-bold text-slate-800 mt-1 block font-mono">PKR {{ number_format($grandTotal['total_earned'], 2) }}</span>
            <span class="text-[11px] text-gray-400">Total doctor share credited from collected fees</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-emerald-100 bg-emerald-50/20">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider block">Total Settled / Paid</span>
            <span class="text-2xl font-bold text-emerald-700 mt-1 block font-mono">PKR {{ number_format($grandTotal['total_settled'], 2) }}</span>
            <span class="text-[11px] text-emerald-600">Disbursed to doctors via settlements</span>
        </div>

        <div class="bg-white p-5 rounded-xl shadow-xs border border-amber-200 bg-amber-50/30">
            <span class="text-xs font-semibold text-amber-800 uppercase tracking-wider block">Current Total Hospital Liability</span>
            <span class="text-2xl font-black text-amber-700 mt-1 block font-mono">PKR {{ number_format($grandTotal['current_payable'], 2) }}</span>
            <span class="text-[11px] text-amber-600 font-semibold">Total outstanding payable to all doctors</span>
        </div>
    </div>

    <!-- PAYABLE TABLE -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-bold text-gray-800 text-sm">Doctor Payables & Balances</h3>
            <span class="text-xs font-semibold text-gray-500">{{ count($rows) }} Doctors</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 border-b border-gray-200 font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="p-3">Doctor Name</th>
                        <th class="p-3">Specialization</th>
                        <th class="p-3 text-right">Total Earned</th>
                        <th class="p-3 text-right">Total Settled</th>
                        <th class="p-3 text-right text-amber-700 bg-amber-50/50">Current Payable</th>
                        <th class="p-3 text-center">Last Settlement Date</th>
                        <th class="p-3 text-center no-print">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($rows as $row)
                    <tr class="hover:bg-gray-50/80 transition">
                        <td class="p-3">
                            <span class="font-bold text-gray-900 block text-sm">{{ $row['doctor']->name }}</span>
                            <span class="text-[10px] text-gray-400">{{ $row['doctor']->phone ?? 'No phone' }}</span>
                        </td>
                        <td class="p-3">
                            <span class="inline-block px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                                {{ $row['doctor']->specialization }}
                            </span>
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-slate-800">
                            PKR {{ number_format($row['earned'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-emerald-600">
                            PKR {{ number_format($row['settled'], 2) }}
                        </td>
                        <td class="p-3 text-right font-mono font-bold text-amber-700 bg-amber-50/30 text-sm">
                            PKR {{ number_format($row['payable'], 2) }}
                        </td>
                        <td class="p-3 text-center text-slate-500 font-medium">
                            {{ $row['last_settlement_date'] }}
                        </td>
                        <td class="p-3 text-center no-print">
                            <div class="flex items-center justify-center space-x-2">
                                <a href="{{ route('doctor-ledgers.show', $row['doctor']->id) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                                    <i class="fa-solid fa-money-bill-transfer text-[10px]"></i>
                                    <span>Settle Now</span>
                                </a>
                                <a href="{{ route('reports.doctor-collection', ['doctor_id' => $row['doctor']->id]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-1 rounded-lg text-xs font-semibold transition" title="View Patient Details">
                                    <i class="fa-solid fa-list text-[10px]"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="p-8 text-center text-gray-400">
                            No doctors registered.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-900 text-white font-bold border-t-2 border-slate-700">
                    <tr>
                        <td colspan="2" class="p-3 uppercase tracking-wider text-xs">Grand Total:</td>
                        <td class="p-3 text-right font-mono">PKR {{ number_format($grandTotal['total_earned'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-emerald-300">PKR {{ number_format($grandTotal['total_settled'], 2) }}</td>
                        <td class="p-3 text-right font-mono text-amber-300 text-sm">PKR {{ number_format($grandTotal['current_payable'], 2) }}</td>
                        <td colspan="2" class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
