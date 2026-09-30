@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ 
    paymentModalOpen: false, 
    selectedBill: null,
    paymentAmount: '',
    paymentMethod: 'cash',
    referenceNote: '',
    openPayment(bill) {
        this.selectedBill = bill;
        this.paymentAmount = bill.due_amount;
        this.paymentMethod = 'cash';
        this.referenceNote = 'Balance settlement';
        this.paymentModalOpen = true;
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
                <span>Billing & Payments</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                <span>Hospital Billing & Revenue</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Cashier collection counter, split breakdown and payment settlement</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('hospital-billing.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-lg shadow-sm transition">
                <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
                <span>+ New Bill</span>
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

    <!-- SUMMARY KPI STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
        <!-- 1. Total Gross Billed -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Gross Billed</p>
                <h3 class="text-xl font-black text-gray-800 mt-1 font-mono">PKR {{ number_format($totalGross, 2) }}</h3>
                <span class="text-[10px] text-gray-500">{{ $totalBillsCount }} invoices</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>

        <!-- 2. Collected by Hospital -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Collected Cash</p>
                <h3 class="text-xl font-black text-emerald-600 mt-1 font-mono">PKR {{ number_format($totalCollected, 2) }}</h3>
                <span class="text-[10px] text-emerald-600 font-medium">Received by Hospital</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>

        <!-- 3. Doctor Share (Payable) -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Doctor Share</p>
                <h3 class="text-xl font-black text-blue-600 mt-1 font-mono">PKR {{ number_format($totalDoctorShare, 2) }}</h3>
                <span class="text-[10px] text-blue-600 font-medium">Earned Doctor Payable</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
        </div>

        <!-- 4. Hospital Share (Net Revenue) -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Hospital Revenue</p>
                <h3 class="text-xl font-black text-indigo-600 mt-1 font-mono">PKR {{ number_format($totalHospitalShare, 2) }}</h3>
                <span class="text-[10px] text-indigo-600 font-medium">Net Hospital Share</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-building-columns"></i>
            </div>
        </div>

        <!-- 5. Outstanding Due -->
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Outstanding Due</p>
                <h3 class="text-xl font-black text-amber-600 mt-1 font-mono">PKR {{ number_format($totalOutstandingDue, 2) }}</h3>
                <span class="text-[10px] text-amber-600 font-medium">Partial Uncollected</span>
            </div>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <form method="GET" action="{{ route('hospital-billing.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-6 gap-3 items-end">
            <!-- Period Preset -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Time Period</label>
                <select name="filter" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="today" {{ $filter == 'today' ? 'selected' : '' }}>Today</option>
                    <option value="yesterday" {{ $filter == 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                    <option value="weekly" {{ $filter == 'weekly' ? 'selected' : '' }}>This Week</option>
                    <option value="monthly" {{ $filter == 'monthly' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $filter == 'custom' ? 'selected' : '' }}>Custom Range</option>
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
                        <option value="{{ $doc->id }}" {{ $doctorId == $doc->id ? 'selected' : '' }}>{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Service Filter -->
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Service</label>
                <select name="hospital_service_id" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                    <option value="">All Services</option>
                    @foreach($allServices as $srv)
                        <option value="{{ $srv->id }}" {{ $serviceId == $srv->id ? 'selected' : '' }}>{{ $srv->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Payment Status -->
            <div class="flex items-center space-x-2">
                <div class="flex-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="payment_status" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:border-blue-500">
                        <option value="">All Statuses</option>
                        <option value="paid" {{ $paymentStatus == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ $paymentStatus == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="free" {{ $paymentStatus == 'free' ? 'selected' : '' }}>Free</option>
                        <option value="pending" {{ $paymentStatus == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <button type="submit" class="bg-blue-600 text-white p-2 rounded-lg text-xs hover:bg-blue-700 transition" title="Apply Filter">
                    <i class="fa-solid fa-filter"></i>
                </button>
            </div>
        </form>
    </div>

    <!-- BILLS LISTING TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Bill # / Date</th>
                        <th class="py-3 px-4">Patient</th>
                        <th class="py-3 px-4">Doctor & Service</th>
                        <th class="py-3 px-4 text-right">Gross Fee</th>
                        <th class="py-3 px-4 text-right">Collected</th>
                        <th class="py-3 px-4 text-right">Remaining Due</th>
                        <th class="py-3 px-4 text-center">Revenue Split</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($bills as $b)
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4">
                            <div class="font-bold font-mono text-gray-900">{{ $b->bill_number }}</div>
                            <div class="text-[11px] text-gray-500 flex items-center space-x-1 mt-0.5">
                                <i class="fa-regular fa-calendar text-[10px]"></i>
                                <span>{{ $b->bill_date->format('d M Y') }}</span>
                                @if($b->token)
                                    <span class="ml-1 px-1.5 py-0.2 bg-blue-100 text-blue-700 font-bold rounded">Token #{{ $b->token->formatted_token_number }}</span>
                                @endif
                            </div>
                        </td>

                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900">{{ $b->patient->name ?? '—' }}</div>
                            <div class="text-[11px] text-gray-500 font-mono">{{ $b->patient->patient_number ?? '—' }}</div>
                        </td>

                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-800">{{ $b->doctor->name ?? 'Hospital Direct' }}</div>
                            <div class="text-[11px] text-indigo-600 font-medium">
                                {{ $b->service->name ?? 'General Service' }}
                            </div>
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold text-gray-900">
                            PKR {{ number_format($b->total_amount, 2) }}
                            @if($b->discount_amount > 0)
                                <div class="text-[10px] text-red-500 font-normal">Discount: -{{ number_format($b->discount_amount, 2) }}</div>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600">
                            PKR {{ number_format($b->paid_amount, 2) }}
                            <div class="text-[10px] text-gray-400 capitalize font-sans">{{ $b->payment_method }}</div>
                        </td>

                        <td class="py-3 px-4 text-right font-mono font-bold {{ $b->due_amount > 0 ? 'text-amber-600' : 'text-gray-400' }}">
                            PKR {{ number_format($b->due_amount, 2) }}
                        </td>

                        <td class="py-3 px-4">
                            <div class="text-[11px] font-mono leading-tight space-y-0.5">
                                <div class="text-blue-600 font-semibold flex items-center justify-between">
                                    <span>Doc:</span>
                                    <span>PKR {{ number_format($b->doctor_share, 2) }}</span>
                                </div>
                                <div class="text-emerald-600 font-semibold flex items-center justify-between">
                                    <span>Hosp:</span>
                                    <span>PKR {{ number_format($b->hospital_share, 2) }}</span>
                                </div>
                            </div>
                        </td>

                        <td class="py-3 px-4 text-center">
                            @if($b->payment_status === 'paid')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1 bg-emerald-600"></span> Paid
                                </span>
                            @elseif($b->payment_status === 'partial')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1 bg-amber-600"></span> Partial
                                </span>
                            @elseif($b->payment_status === 'free')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1 bg-purple-600"></span> Free
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-800">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1 bg-gray-600"></span> Pending
                                </span>
                            @endif
                        </td>

                        <td class="py-3 px-4 text-right space-x-1 whitespace-nowrap">
                            @if($b->due_amount > 0)
                                <button 
                                    type="button" 
                                    @click="openPayment({{ json_encode($b) }})" 
                                    class="inline-flex items-center px-2 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded text-[11px] font-bold shadow-xs transition"
                                    title="Receive Payment"
                                >
                                    <i class="fa-solid fa-hand-holding-dollar mr-1"></i> Pay
                                </button>
                            @endif

                            <a 
                                href="{{ route('hospital-billing.receipt', $b->id) }}" 
                                target="_blank"
                                class="inline-flex items-center p-1.5 text-slate-600 hover:text-blue-600 hover:bg-slate-100 rounded-lg transition" 
                                title="Print Receipt"
                            >
                                <i class="fa-solid fa-print"></i>
                            </a>

                            <a 
                                href="{{ route('hospital-billing.show', $b->id) }}" 
                                class="inline-flex items-center p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                title="View Bill"
                            >
                                <i class="fa-solid fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-10 text-gray-400">
                            <i class="fa-solid fa-file-invoice text-3xl mb-2 text-gray-300"></i>
                            <p>No hospital billing records found for the selected period.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($bills->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $bills->links() }}
            </div>
        @endif
    </div>

    <!-- RECEIVE PAYMENT MODAL (FOR PARTIAL / DUE BILLS) -->
    <div 
        x-show="paymentModalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="paymentModalOpen = false" 
            class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden border border-gray-100 transform transition-all"
        >
            <template x-if="selectedBill">
                <form :action="'/hospital-billing/' + selectedBill.id + '/payment'" method="POST">
                    @csrf
                    <div class="px-6 py-4 bg-emerald-600 text-white flex items-center justify-between">
                        <h3 class="font-bold text-sm tracking-wide flex items-center space-x-2">
                            <i class="fa-solid fa-hand-holding-dollar"></i>
                            <span>Receive Bill Payment</span>
                        </h3>
                        <button type="button" @click="paymentModalOpen = false" class="text-emerald-100 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 space-y-4 text-xs">
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 font-mono">
                            <div class="flex justify-between text-gray-600">
                                <span>Bill Number:</span>
                                <strong class="text-gray-900" x-text="selectedBill.bill_number"></strong>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Patient:</span>
                                <strong class="text-gray-900" x-text="selectedBill.patient ? selectedBill.patient.name : '—'"></strong>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Total Fee:</span>
                                <span class="font-bold" x-text="'PKR ' + parseFloat(selectedBill.total_amount).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-emerald-600">
                                <span>Already Paid:</span>
                                <span class="font-bold" x-text="'PKR ' + parseFloat(selectedBill.paid_amount).toFixed(2)"></span>
                            </div>
                            <div class="flex justify-between text-amber-600 border-t border-slate-200 pt-1">
                                <span>Outstanding Due:</span>
                                <strong class="text-sm font-black" x-text="'PKR ' + parseFloat(selectedBill.due_amount).toFixed(2)"></strong>
                            </div>
                        </div>

                        <!-- Amount to receive -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Amount to Receive (PKR) <span class="text-red-500">*</span></label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                :max="selectedBill.due_amount" 
                                name="amount" 
                                x-model.number="paymentAmount" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-emerald-700 text-sm focus:outline-hidden focus:bg-white focus:border-emerald-500"
                            >
                            <p class="text-[10px] text-gray-500 mt-1">Cannot exceed remaining balance of PKR <span x-text="parseFloat(selectedBill.due_amount).toFixed(2)"></span></p>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select 
                                name="payment_method" 
                                x-model="paymentMethod" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-emerald-500"
                            >
                                <option value="cash">Cash Counter</option>
                                <option value="card">Debit / Credit Card</option>
                                <option value="bank_transfer">Bank Transfer / Online</option>
                                <option value="other">Other / Cheque</option>
                            </select>
                        </div>

                        <!-- Reference Note -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Payment Note / Receipt Remarks</label>
                            <input 
                                type="text" 
                                name="reference_note" 
                                x-model="referenceNote" 
                                placeholder="e.g. Counter cash receipt, transaction reference..." 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-emerald-500"
                            >
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                        <button 
                            type="button" 
                            @click="paymentModalOpen = false" 
                            class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-xs font-bold shadow-sm transition"
                        >
                            Receive Payment
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </div>

</div>
@endsection
