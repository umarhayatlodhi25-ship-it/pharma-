@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/doctor-ledgers" class="hover:underline">Doctor Ledgers</a>
                <span>/</span>
                <span>{{ $doctor->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-user-doctor text-blue-600"></i>
                <span>Doctor Ledger: {{ $doctor->name }}</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">{{ $doctor->specialization ?: 'General Practitioner' }} - Consultation Fee: PKR {{ number_format($doctor->consultation_fee, 2) }}</p>
        </div>

        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow-sm transition">
                <i class="fa-solid fa-print mr-1.5"></i>
                <span>Print Statement</span>
            </button>
            <a href="{{ route('doctor-ledgers.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition">
                <span>All Doctors</span>
            </a>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- FINANCIAL SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Total Earned -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Revenue Earned</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1 font-mono">PKR {{ number_format($totalEarned, 2) }}</h3>
                <span class="text-[11px] text-blue-600 font-medium">Credits from consultations</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-down-left"></i>
            </div>
        </div>

        <!-- Total Paid Out -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Disbursed / Paid</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">PKR {{ number_format($totalPaid, 2) }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium">Debits via settlements</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-arrow-up-right"></i>
            </div>
        </div>

        <!-- Current Outstanding Payable -->
        <div class="bg-white p-5 rounded-xl border border-purple-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-purple-700 uppercase tracking-wider">Current Outstanding Payable</p>
                <h3 class="text-2xl font-black text-purple-700 mt-1 font-mono">PKR {{ number_format($currentPayable, 2) }}</h3>
                <span class="text-[11px] text-purple-600 font-medium">Hospital liability to doctor</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT 2 COLUMNS: DETAILED RUNNING LEDGER TABLE -->
        <div class="lg:col-span-2 space-y-6">

            <!-- FILTER BY DATE -->
            <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm no-print">
                <form method="GET" action="{{ route('doctor-ledgers.show', $doctor->id) }}" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center space-x-2 text-xs">
                        <span class="font-bold text-gray-600">Filter Range:</span>
                        <input type="date" name="start_date" value="{{ $startDate }}" class="p-2 bg-gray-50 border border-gray-200 rounded-lg">
                        <span>to</span>
                        <input type="date" name="end_date" value="{{ $endDate }}" class="p-2 bg-gray-50 border border-gray-200 rounded-lg">
                    </div>
                    <div class="flex items-center space-x-2">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">
                            Filter
                        </button>
                        @if($startDate || $endDate)
                            <a href="{{ route('doctor-ledgers.show', $doctor->id) }}" class="p-2 text-gray-500 hover:text-gray-700 rounded-lg text-xs">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- LEDGER TRANSACTIONS -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                        <i class="fa-solid fa-table-list text-blue-600"></i>
                        <span>Statement of Account (Running Ledger)</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Description / Reference</th>
                                <th class="py-2.5 px-3 text-right">Earned (Credit)</th>
                                <th class="py-2.5 px-3 text-right">Paid (Debit)</th>
                                <th class="py-2.5 px-3 text-right">Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                            @php
                                $running = 0.0;
                            @endphp
                            @forelse($ledgers as $entry)
                            @php
                                if ($entry->transaction_type === 'credit') {
                                    $running += (float)$entry->amount;
                                } else {
                                    $running -= (float)$entry->amount;
                                }
                            @endphp
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="py-2.5 px-3 text-gray-600 whitespace-nowrap">
                                    {{ $entry->entry_date->format('d M Y') }}
                                </td>

                                <td class="py-2.5 px-3 font-sans">
                                    <div class="text-gray-900 font-semibold">{{ $entry->description }}</div>
                                    @if($entry->bill)
                                        <div class="text-[10px] text-gray-500 font-mono">
                                            Bill: <a href="{{ route('hospital-billing.show', $entry->bill->id) }}" class="text-blue-600 hover:underline">{{ $entry->bill->bill_number }}</a>
                                            | Patient: {{ $entry->bill->patient->name ?? '—' }}
                                        </div>
                                    @elseif($entry->settlement)
                                        <div class="text-[10px] text-purple-600 font-mono">
                                            Voucher: <a href="{{ route('doctor-settlements.voucher', $entry->settlement->id) }}" target="_blank" class="hover:underline font-bold">{{ $entry->settlement->settlement_number }}</a>
                                        </div>
                                    @endif
                                </td>

                                <td class="py-2.5 px-3 text-right font-bold text-blue-600">
                                    {{ $entry->transaction_type === 'credit' ? '+ PKR ' . number_format($entry->amount, 2) : '—' }}
                                </td>

                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">
                                    {{ $entry->transaction_type === 'debit' ? '- PKR ' . number_format($entry->amount, 2) : '—' }}
                                </td>

                                <td class="py-2.5 px-3 text-right font-black text-gray-900">
                                    PKR {{ number_format($running, 2) }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400 font-sans">
                                    No ledger entries recorded for this doctor.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if(count($ledgers) > 0)
                        <tfoot class="bg-slate-50 font-bold border-t-2 border-slate-300 font-mono">
                            <tr>
                                <td colspan="2" class="py-3 px-3 font-sans text-gray-900">Closing Balance:</td>
                                <td class="py-3 px-3 text-right text-blue-700">+ PKR {{ number_format($totalEarned, 2) }}</td>
                                <td class="py-3 px-3 text-right text-emerald-700">- PKR {{ number_format($totalPaid, 2) }}</td>
                                <td class="py-3 px-3 text-right text-purple-700 text-sm">PKR {{ number_format($currentPayable, 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- SETTLEMENT HISTORY -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                        <i class="fa-solid fa-money-check-dollar text-emerald-600"></i>
                        <span>Recent Settlement Payout Vouchers</span>
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs font-mono">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3">Voucher #</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3 text-right">Previous Payable</th>
                                <th class="py-2.5 px-3 text-right">Amount Paid</th>
                                <th class="py-2.5 px-3 text-right">Remaining</th>
                                <th class="py-2.5 px-3">Method</th>
                                <th class="py-2.5 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                            @forelse($settlements as $set)
                            <tr class="hover:bg-slate-50/75 transition">
                                <td class="py-2.5 px-3 font-bold text-purple-700">{{ $set->settlement_number }}</td>
                                <td class="py-2.5 px-3 text-gray-600">{{ $set->settlement_date->format('d M Y') }}</td>
                                <td class="py-2.5 px-3 text-right">PKR {{ number_format($set->previous_payable, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">PKR {{ number_format($set->paid_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right">PKR {{ number_format($set->remaining_payable, 2) }}</td>
                                <td class="py-2.5 px-3 font-sans capitalize">{{ $set->payment_method }}</td>
                                <td class="py-2.5 px-3 text-right">
                                    <a 
                                        href="{{ route('doctor-settlements.voucher', $set->id) }}" 
                                        target="_blank" 
                                        class="p-1 text-slate-600 hover:text-blue-600 transition" 
                                        title="Print Voucher"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-gray-400 font-sans">
                                    No settlement payouts recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT 1 COLUMN: DISBURSE PAYMENT TO DOCTOR FORM -->
        <div class="space-y-6">

            <div class="bg-white rounded-2xl border border-purple-200 shadow-sm p-6 space-y-4">
                <div class="border-b border-gray-100 pb-3">
                    <h3 class="font-bold text-sm text-purple-900 flex items-center space-x-2">
                        <i class="fa-solid fa-money-bill-transfer text-purple-600"></i>
                        <span>Disburse Payment to Doctor</span>
                    </h3>
                    <p class="text-xs text-gray-500 mt-1">Record payment to reduce outstanding payable balance</p>
                </div>

                @if($currentPayable > 0)
                <form action="{{ route('doctor-ledgers.settle', $doctor->id) }}" method="POST" class="space-y-4 text-xs">
                    @csrf

                    <div class="p-3 bg-purple-50 rounded-xl font-mono text-xs space-y-1">
                        <div class="flex justify-between text-purple-900">
                            <span>Outstanding Balance:</span>
                            <strong class="text-sm font-black">PKR {{ number_format($currentPayable, 2) }}</strong>
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Payment Amount (PKR) <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0.01" 
                            max="{{ $currentPayable }}" 
                            name="amount" 
                            value="{{ $currentPayable }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-purple-700 focus:bg-white focus:border-purple-500"
                        >
                        <p class="text-[10px] text-gray-400 mt-1">Maximum allowed: PKR {{ number_format($currentPayable, 2) }}</p>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-purple-500">
                            <option value="cash">Cash Payment</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Reference / Note</label>
                        <input type="text" name="reference_note" placeholder="e.g. Weekly settlement cheque #..." class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-purple-500">
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-2.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-lg font-bold shadow-sm transition"
                    >
                        Issue Settlement Payment
                    </button>
                </form>
                @else
                <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 text-center space-y-1">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-2xl"></i>
                    <p class="font-bold">All Accounts Settled</p>
                    <p class="text-[11px] text-emerald-600">No outstanding payable balance currently owed to this doctor.</p>
                </div>
                @endif
            </div>

        </div>

    </div>

</div>
@endsection
