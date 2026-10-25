@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Earnings & Revenue</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-chart-line text-teal-600"></i>
                <span>Doctor Earnings & Financial Overview</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Live financial statistics computed strictly from verified hospital billing ledger records for Dr. {{ $doctor->name }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('doctor.ledger.index') }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-scale-balanced"></i>
                <span>View Full Audit Ledger</span>
            </a>
        </div>
    </div>

    <!-- PRIMARY FINANCIAL KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Current Outstanding Payable -->
        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-2xl p-5 shadow-lg shadow-emerald-900/20">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-100">Outstanding Payable</span>
                <i class="fa-solid fa-wallet text-emerald-200 text-lg"></i>
            </div>
            <div class="mt-3">
                <span class="text-2xl sm:text-3xl font-black font-mono">PKR {{ number_format($currentPayable, 2) }}</span>
                <p class="text-[11px] text-emerald-100/80 mt-1">Net pending settlement balance</p>
            </div>
        </div>

        <!-- Total Earned -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Doctor Share Earned</span>
                <div class="w-8 h-8 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-black text-gray-900 font-mono">PKR {{ number_format($totalEarned, 2) }}</span>
                <p class="text-[11px] text-gray-400 mt-1">Lifetime consultation revenue credits ({{ (float)$doctor->doctor_share_percentage }}% share)</p>
            </div>
        </div>

        <!-- Gross Practice Collection -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Gross Collection</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-receipt"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-black text-teal-900 font-mono">PKR {{ number_format($grossCollection, 2) }}</span>
                <p class="text-[11px] text-gray-400 mt-1">Total gross billing attributed to doctor</p>
            </div>
        </div>

        <!-- Total Settled -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Settled Payouts</span>
                <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-700 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-money-bill-transfer"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-black text-gray-900 font-mono">PKR {{ number_format($totalPaid, 2) }}</span>
                <p class="text-[11px] text-gray-400 mt-1">Disbursed by hospital cash office to date</p>
            </div>
        </div>

        <!-- Lifetime Consultations / Visits Volume -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Total Consultations / Visits</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-black text-gray-900 font-mono">{{ $totalLifetimeVisits }}</span>
                <p class="text-[11px] text-gray-400 mt-1">{{ $totalTokens }} tokens • {{ $paidTokens }} paid • {{ $freeTokens }} free</p>
            </div>
        </div>

        <!-- Period Earnings -->
        <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Period Earnings</span>
                <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm">
                    <i class="fa-regular fa-calendar"></i>
                </div>
            </div>
            <div class="mt-3 space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500">This Month:</span>
                    <span class="font-bold text-gray-900 font-mono">PKR {{ number_format($monthEarned, 2) }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500">Today:</span>
                    <span class="font-bold text-teal-700 font-mono">PKR {{ number_format($todayEarned, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- REVENUE SPLIT CONFIGURATION BANNER -->
    <div class="bg-gradient-to-r from-teal-50 to-indigo-50/60 rounded-xl p-5 border border-teal-100">
        <div class="flex items-center space-x-2 mb-3">
            <i class="fa-solid fa-percent text-teal-700 text-sm"></i>
            <h3 class="text-xs font-bold text-teal-900 uppercase tracking-wider">Configured Practice Split Matrix</h3>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div class="bg-white p-3 rounded-xl border border-teal-100 shadow-2xs">
                <span class="text-[10px] font-bold text-gray-500 uppercase block">Consultation Fee</span>
                <span class="text-base font-black text-gray-900 font-mono">PKR {{ number_format($doctor->consultation_fee, 0) }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-teal-100 shadow-2xs">
                <span class="text-[10px] font-bold text-teal-700 uppercase block">Doctor Revenue Share</span>
                <span class="text-base font-black text-teal-700 font-mono">{{ (float)$doctor->doctor_share_percentage }}%</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-teal-100 shadow-2xs">
                <span class="text-[10px] font-bold text-indigo-700 uppercase block">Hospital Share</span>
                <span class="text-base font-black text-indigo-700 font-mono">{{ (float)$doctor->hospital_share_percentage }}%</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-teal-100 shadow-2xs">
                <span class="text-[10px] font-bold text-emerald-700 uppercase block">Earned Per Paid Visit</span>
                <span class="text-base font-black text-emerald-700 font-mono">PKR {{ number_format($doctor->doctor_share_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <!-- RECENT CREDITS & SETTLEMENTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- RECENT CONSULTATION REVENUE POSTINGS -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                        <i class="fa-solid fa-circle-plus text-teal-600"></i>
                        <span>Recent Credited Consultations</span>
                    </h3>
                    <p class="text-xs text-gray-500">Live share credits posted to your ledger</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/70 text-gray-500 uppercase font-semibold text-[10px] tracking-wider border-b border-gray-100">
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Patient</th>
                            <th class="py-2.5 px-3">Bill / Token</th>
                            <th class="py-2.5 px-3 text-right">Earned Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentCredits as $credit)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">
                                {{ format_app_datetime($credit->entry_date, 'd M, Y') }}
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-gray-900 block">{{ $credit->bill && $credit->bill->patient ? $credit->bill->patient->name : 'OPD Patient' }}</span>
                            </td>
                            <td class="py-3 px-3 font-mono text-gray-500">
                                @if($credit->bill)
                                    <span>{{ $credit->bill->bill_number }}</span>
                                    @if($credit->bill->token)
                                        <span class="text-[10px] text-teal-600 block">Token #{{ $credit->bill->token->token_number }}</span>
                                    @endif
                                @else
                                    <span>—</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-emerald-700">
                                + PKR {{ number_format($credit->amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-400 text-xs">
                                No consultation credit entries recorded yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- RECENT SETTLEMENT PAYOUTS -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                        <i class="fa-solid fa-receipt text-slate-700"></i>
                        <span>Settlement Payout History</span>
                    </h3>
                    <p class="text-xs text-gray-500">Recorded payments disbursed to Dr. {{ $doctor->name }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50/70 text-gray-500 uppercase font-semibold text-[10px] tracking-wider border-b border-gray-100">
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Settlement #</th>
                            <th class="py-2.5 px-3">Method</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Paid Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($settlements as $settlement)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap">
                                {{ format_app_datetime($settlement->settlement_date, 'd M, Y') }}
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900">
                                {{ $settlement->settlement_number }}
                            </td>
                            <td class="py-3 px-3 text-gray-600">
                                <span class="capitalize">{{ $settlement->payment_method ?: 'Cash' }}</span>
                                @if($settlement->reference_note)
                                    <span class="text-[10px] text-gray-400 block truncate max-w-xs">{{ $settlement->reference_note }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-circle-check text-[8px] mr-1"></i> Completed
                                </span>
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">
                                - PKR {{ number_format($settlement->paid_amount, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-gray-400 text-xs">
                                No settlement payout vouchers generated yet.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
