@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Financial Ledger</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-scale-balanced text-teal-600"></i>
                <span>Doctor Settlement Ledger</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Audit trail of consultation revenue credits and hospital settlement payouts for Dr. {{ $doctor->name }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-gray-100 text-gray-700 text-xs font-bold border border-gray-200">
                <i class="fa-solid fa-lock text-gray-500 mr-1.5"></i> Read-Only Audit Trail
            </span>
        </div>
    </div>

    <!-- SUMMARY METRIC CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase block mb-1">Filtered Revenue Credits</span>
            <span class="text-2xl font-black text-emerald-700 font-mono">+ PKR {{ number_format($totalCredit, 2) }}</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-xs">
            <span class="text-xs font-semibold text-gray-500 uppercase block mb-1">Filtered Payout Debits</span>
            <span class="text-2xl font-black text-slate-800 font-mono">- PKR {{ number_format($totalDebit, 2) }}</span>
        </div>

        <div class="bg-gradient-to-br from-emerald-600 to-teal-700 text-white rounded-xl p-4 shadow-md shadow-emerald-900/20">
            <span class="text-xs font-bold text-emerald-100 uppercase block mb-1">Net Current Payable</span>
            <span class="text-2xl font-black font-mono">PKR {{ number_format($currentPayable, 2) }}</span>
        </div>
    </div>

    <!-- FILTER BAR & LEDGER TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden space-y-4">
        
        <!-- Filter Form -->
        <div class="p-4 border-b border-gray-100 bg-gray-50/50">
            <form action="{{ route('doctor.ledger.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-xs">
                <div>
                    <label class="block font-semibold text-gray-600 mb-1">From Date</label>
                    <input type="date" name="start_date" value="{{ $startDate }}" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-3">
                </div>

                <div>
                    <label class="block font-semibold text-gray-600 mb-1">To Date</label>
                    <input type="date" name="end_date" value="{{ $endDate }}" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-3">
                </div>

                <div>
                    <label class="block font-semibold text-gray-600 mb-1">Transaction Type</label>
                    <select name="type" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-3">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Transactions</option>
                        <option value="credit" {{ $type === 'credit' ? 'selected' : '' }}>Credits (Earned Revenue)</option>
                        <option value="debit" {{ $type === 'debit' ? 'selected' : '' }}>Debits (Settlement Payouts)</option>
                    </select>
                </div>

                <div class="flex items-end space-x-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white font-bold text-xs rounded-xl shadow-xs transition">
                        Filter Ledger
                    </button>
                    @if($startDate || $endDate || $type !== 'all')
                        <a href="{{ route('doctor.ledger.index') }}" class="px-3 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-xs font-bold rounded-xl transition">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-gray-50/80 border-b border-gray-200 text-gray-500 uppercase font-semibold text-[10px] tracking-wider">
                        <th class="py-3 px-4">Entry Date</th>
                        <th class="py-3 px-4">Transaction Type</th>
                        <th class="py-3 px-4">Reference / Description</th>
                        <th class="py-3 px-4 text-right">Credit (Earned)</th>
                        <th class="py-3 px-4 text-right">Debit (Paid)</th>
                        <th class="py-3 px-4 text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-mono">
                    @forelse($ledgerEntries as $entry)
                    <tr class="hover:bg-slate-50/60 transition">
                        <!-- Date -->
                        <td class="py-3.5 px-4 text-gray-700 whitespace-nowrap">
                            {{ format_app_datetime($entry->entry_date, 'd M, Y') }}
                        </td>

                        <!-- Type -->
                        <td class="py-3.5 px-4 font-sans">
                            @if($entry->transaction_type === 'credit')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fa-solid fa-plus text-[8px] mr-1"></i> Credit (Share)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800 border border-slate-300">
                                    <i class="fa-solid fa-minus text-[8px] mr-1"></i> Debit (Payout)
                                </span>
                            @endif
                        </td>

                        <!-- Reference / Description -->
                        <td class="py-3.5 px-4 font-sans text-gray-800">
                            <span class="font-medium">{{ $entry->description ?: 'OPD Consultation Settlement' }}</span>
                            @if($entry->bill)
                                <span class="text-[11px] text-gray-400 block font-mono">Bill: {{ $entry->bill->bill_number }}</span>
                            @elseif($entry->settlement)
                                <span class="text-[11px] text-gray-400 block font-mono">Voucher: {{ $entry->settlement->settlement_number }}</span>
                            @endif
                        </td>

                        <!-- Credit -->
                        <td class="py-3.5 px-4 text-right text-emerald-700 font-bold">
                            @if($entry->transaction_type === 'credit')
                                + PKR {{ number_format($entry->amount, 2) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <!-- Debit -->
                        <td class="py-3.5 px-4 text-right text-slate-800 font-bold">
                            @if($entry->transaction_type === 'debit')
                                - PKR {{ number_format($entry->amount, 2) }}
                            @else
                                <span class="text-gray-300">—</span>
                            @endif
                        </td>

                        <!-- Balance After -->
                        <td class="py-3.5 px-4 text-right font-black text-gray-900">
                            PKR {{ number_format($entry->balance_after, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-12 text-center text-gray-400 text-xs font-sans">
                            <i class="fa-solid fa-scale-balanced text-4xl mb-2 text-gray-300 block"></i>
                            <p class="font-semibold">No ledger entries match this criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ledgerEntries->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
