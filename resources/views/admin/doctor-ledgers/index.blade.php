@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ 
    settleModalOpen: false, 
    doctor: null,
    settleAmount: '',
    paymentMethod: 'cash',
    referenceNote: '',
    openSettle(stat) {
        this.doctor = stat.doctor;
        this.settleAmount = stat.current_payable;
        this.paymentMethod = 'cash';
        this.referenceNote = 'Doctor share settlement';
        this.settleModalOpen = true;
    }
}">

    <!-- HEADER & TOP ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/dashboard" class="hover:underline">Dashboard</a>
                <span>/</span>
                <span>Hospital</span>
                <span>/</span>
                <span>Doctor Ledger & Payables</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-book-medical text-blue-600"></i>
                <span>Doctor Ledgers & Settlements</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Track doctor revenue earnings, hospital payouts, and outstanding payable balances</p>
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

    <!-- SUMMARY KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Doctor Visits</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1 font-mono">{{ $totalVisitsAll }}</h3>
                <span class="text-[11px] text-gray-500">Across all doctors</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-hospital-user"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Earned (Gross Share)</p>
                <h3 class="text-2xl font-black text-blue-600 mt-1 font-mono">PKR {{ number_format($totalEarnedAll, 2) }}</h3>
                <span class="text-[11px] text-blue-600 font-medium">Earned from services</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Already Settled / Paid</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">PKR {{ number_format($totalPaidAll, 2) }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium">Disbursed to doctors</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Outstanding Payable</p>
                <h3 class="text-2xl font-black text-purple-600 mt-1 font-mono">PKR {{ number_format($totalPayableAll, 2) }}</h3>
                <span class="text-[11px] text-purple-600 font-medium">Current liability</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- SEARCH BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <form method="GET" action="{{ route('doctor-ledgers.index') }}" class="flex items-center space-x-3">
            <div class="relative flex-1 max-w-md">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search doctor by name, specialization..." 
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                >
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                    <i class="fa-solid fa-search"></i>
                </span>
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition">
                Search
            </button>
            @if($search)
                <a href="{{ route('doctor-ledgers.index') }}" class="p-2 text-gray-500 hover:text-gray-700 rounded-lg text-xs">
                    <i class="fa-solid fa-xmark"></i> Clear
                </a>
            @endif
        </form>
    </div>

    <!-- DOCTOR PAYABLES TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Doctor</th>
                        <th class="py-3 px-4 text-center">Total Visits</th>
                        <th class="py-3 px-4 text-right">Gross Collection</th>
                        <th class="py-3 px-4 text-right">Doctor Share (Earned)</th>
                        <th class="py-3 px-4 text-right">Already Paid</th>
                        <th class="py-3 px-4 text-right">Remaining Payable</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($doctorStats as $st)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900 text-sm">{{ $st['doctor']->name }}</div>
                            <div class="text-[11px] text-gray-500">
                                {{ $st['doctor']->specialization ?: 'General Practitioner' }}
                            </div>
                        </td>

                        <td class="py-3 px-4 text-center font-mono font-bold text-gray-800">
                            {{ $st['visits'] }}
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                            PKR {{ number_format($st['gross_billed'], 2) }}
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold text-blue-600">
                            PKR {{ number_format($st['earned'], 2) }}
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                            PKR {{ number_format($st['paid'], 2) }}
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-black text-sm {{ $st['current_payable'] > 0 ? 'text-purple-700' : 'text-gray-400' }}">
                            PKR {{ number_format($st['current_payable'], 2) }}
                        </td>

                        <td class="py-3 px-4 text-right space-x-2 whitespace-nowrap">
                            @if($st['current_payable'] > 0)
                                <button 
                                    type="button" 
                                    @click="openSettle({{ json_encode($st) }})"
                                    class="inline-flex items-center px-2.5 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-[11px] font-bold shadow-xs transition"
                                >
                                    <i class="fa-solid fa-money-bill-transfer mr-1"></i> Pay Doctor
                                </button>
                            @endif

                            <a 
                                href="{{ route('doctor-ledgers.show', $st['doctor']->id) }}" 
                                class="inline-flex items-center px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-[11px] font-bold transition"
                            >
                                <i class="fa-solid fa-list-check mr-1"></i> Statement
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-8 text-gray-400">
                            <i class="fa-solid fa-user-doctor text-3xl mb-2 text-gray-300"></i>
                            <p>No doctor records found.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- SETTLE DOCTOR MODAL -->
    <div 
        x-show="settleModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="settleModalOpen = false" 
            class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100 transform transition-all"
        >
            <template x-if="doctor">
                <form :action="'/doctors/' + doctor.id + '/settle'" method="POST">
                    @csrf
                    <div class="px-6 py-4 bg-purple-700 text-white flex items-center justify-between">
                        <h3 class="font-bold text-sm tracking-wide flex items-center space-x-2">
                            <i class="fa-solid fa-money-bill-transfer"></i>
                            <span>Doctor Payment Settlement</span>
                        </h3>
                        <button type="button" @click="settleModalOpen = false" class="text-purple-200 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 font-mono">
                            <div class="flex justify-between text-gray-600 font-sans">
                                <span>Doctor:</span>
                                <strong class="text-gray-900 font-bold" x-text="doctor.name"></strong>
                            </div>
                            <div class="flex justify-between text-purple-700 border-t border-slate-200 pt-1">
                                <span>Current Payable Balance:</span>
                                <strong class="text-sm font-black" x-text="'PKR ' + parseFloat(settleAmount).toFixed(2)"></strong>
                            </div>
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Disbursement Amount (PKR) <span class="text-red-500">*</span></label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                :max="settleAmount" 
                                name="amount" 
                                x-model.number="settleAmount" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-purple-700 text-sm focus:outline-hidden focus:bg-white focus:border-purple-500"
                            >
                            <p class="text-[10px] text-gray-500 mt-1">Payment cannot exceed current outstanding payable balance.</p>
                        </div>

                        <!-- Method -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select 
                                name="payment_method" 
                                x-model="paymentMethod" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-purple-500"
                            >
                                <option value="cash">Cash Payment</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <!-- Reference Note -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Reference / Note</label>
                            <input 
                                type="text" 
                                name="reference_note" 
                                x-model="referenceNote" 
                                placeholder="e.g. Weekly settlement, Cheque #, Bank ref..." 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-purple-500"
                            >
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            type="button" 
                            @click="settleModalOpen = false" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white rounded-lg text-xs font-bold shadow-sm transition"
                        >
                            Disburse Payment
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
