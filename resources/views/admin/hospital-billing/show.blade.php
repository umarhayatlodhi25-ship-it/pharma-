@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/hospital-billing" class="hover:underline">Hospital Billing</a>
                <span>/</span>
                <span>Bill #{{ $bill->bill_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-receipt text-blue-600"></i>
                <span>Invoice Details: {{ $bill->bill_number }}</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Generated on {{ $bill->bill_date->format('d M Y') }} - Status: <span class="font-bold uppercase text-gray-700">{{ $bill->payment_status }}</span></p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('hospital-billing.receipt', $bill->id) }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                <i class="fa-solid fa-print mr-1.5"></i>
                <span>Print Official Receipt</span>
            </a>
            <a href="{{ route('hospital-billing.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition">
                <span>Back to Bills</span>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- LEFT 2 COLUMNS: BILL DETAILS & PAYMENT TIMELINE -->
        <div class="lg:col-span-2 space-y-6">

            <!-- MAIN INVOICE CARD -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-6">
                <!-- Patient & Doctor Info Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pb-6 border-b border-gray-100 text-xs">
                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Patient Details</span>
                        <h3 class="text-base font-bold text-gray-900">{{ $bill->patient->name ?? '—' }}</h3>
                        <p class="text-gray-500 mt-0.5">Patient ID: <span class="font-mono font-bold text-gray-700">{{ $bill->patient->patient_number ?? '—' }}</span></p>
                        <p class="text-gray-500 mt-0.5">Age/Gender: {{ $bill->patient->age ?? '—' }} Y / {{ $bill->patient->gender ?? '—' }}</p>
                        <p class="text-gray-500 mt-0.5">Phone: {{ $bill->patient->phone ?? '—' }}</p>
                    </div>

                    <div>
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Consultation / Service</span>
                        <h3 class="text-base font-bold text-indigo-700">{{ $bill->service->name ?? 'Hospital Service' }}</h3>
                        <p class="text-gray-600 mt-0.5">Attending Doctor: <strong>{{ $bill->doctor->name ?? 'Hospital Direct' }}</strong></p>
                        @if($bill->token)
                            <p class="text-blue-600 font-bold mt-1">
                                <i class="fa-solid fa-ticket-simple mr-1"></i> OPD Token #{{ $bill->token->formatted_token_number }}
                            </p>
                        @endif
                    </div>
                </div>

                <!-- Financial Breakdown Table -->
                <div class="space-y-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Financial Accounting</span>
                    <table class="w-full text-xs font-mono">
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="py-2 text-gray-600">Gross Service Fee</td>
                                <td class="py-2 text-right font-bold text-gray-900">PKR {{ number_format($bill->total_amount, 2) }}</td>
                            </tr>
                            @if($bill->discount_amount > 0)
                            <tr>
                                <td class="py-2 text-purple-600">Discount / Free Amount (Reason: {{ $bill->free_reason ?: 'Free Patient' }})</td>
                                <td class="py-2 text-right font-bold text-purple-600">- PKR {{ number_format($bill->discount_amount, 2) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td class="py-2 text-gray-600">Net Payable Amount</td>
                                <td class="py-2 text-right font-bold text-gray-900">PKR {{ number_format($bill->net_amount, 2) }}</td>
                            </tr>
                            <tr class="bg-emerald-50/50">
                                <td class="py-2 text-emerald-800 font-bold font-sans">Hospital Collected Payment</td>
                                <td class="py-2 text-right font-black text-emerald-700 text-sm">PKR {{ number_format($bill->paid_amount, 2) }}</td>
                            </tr>
                            @if($bill->due_amount > 0)
                            <tr class="bg-amber-50/50">
                                <td class="py-2 text-amber-800 font-bold font-sans">Remaining Outstanding Due</td>
                                <td class="py-2 text-right font-black text-amber-700 text-sm">PKR {{ number_format($bill->due_amount, 2) }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                @if($bill->notes)
                <div class="p-3 bg-gray-50 border border-gray-100 rounded-lg text-xs text-gray-600">
                    <strong>Remarks / Notes:</strong> {{ $bill->notes }}
                </div>
                @endif
            </div>

            <!-- PAYMENT TRANSACTIONS TIMELINE -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                    <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                    <span>Payment Receipts History</span>
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                                <th class="py-2.5 px-3">Receipt #</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Method</th>
                                <th class="py-2.5 px-3 text-right">Amount Received</th>
                                <th class="py-2.5 px-3 text-right">Doctor Share</th>
                                <th class="py-2.5 px-3 text-right">Hospital Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium text-gray-700 font-mono">
                            @forelse($bill->payments as $pmt)
                            <tr>
                                <td class="py-2.5 px-3 font-bold text-gray-900">{{ $pmt->payment_number }}</td>
                                <td class="py-2.5 px-3">{{ $pmt->payment_date->format('d M Y') }}</td>
                                <td class="py-2.5 px-3 font-sans capitalize">{{ $pmt->payment_method }}</td>
                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600">PKR {{ number_format($pmt->amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right text-blue-600">PKR {{ number_format($pmt->doctor_share, 2) }}</td>
                                <td class="py-2.5 px-3 text-right text-indigo-600">PKR {{ number_format($pmt->hospital_share, 2) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-gray-400 font-sans">
                                    No payments recorded yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- RIGHT 1 COLUMN: REVENUE SPLIT CARDS & RECEIVE DUE PAYMENT -->
        <div class="space-y-6">

            <!-- REVENUE SPLIT CARD -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-4">
                <h3 class="font-bold text-xs uppercase tracking-wider text-gray-400 flex items-center space-x-1.5 font-mono">
                    <i class="fa-solid fa-scale-balanced text-blue-600"></i>
                    <span>Recognized Revenue Split</span>
                </h3>

                <div class="space-y-3 font-mono">
                    <!-- Doctor Share -->
                    <div class="p-4 bg-blue-50/70 border border-blue-200 rounded-xl space-y-1">
                        <div class="flex items-center justify-between text-blue-900 font-sans">
                            <span class="font-bold">Doctor Share</span>
                            <span class="text-xs font-bold font-mono">{{ number_format($bill->doctor_share_percentage, 0) }}%</span>
                        </div>
                        <div class="text-2xl font-black text-blue-700">
                            PKR {{ number_format($bill->doctor_share, 2) }}
                        </div>
                        <div class="text-[10px] text-blue-600 font-sans">
                            Added to {{ $bill->doctor ? $bill->doctor->name : 'Doctor' }}'s Payable
                        </div>
                    </div>

                    <!-- Hospital Share -->
                    <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-xl space-y-1">
                        <div class="flex items-center justify-between text-emerald-900 font-sans">
                            <span class="font-bold">Hospital Net Share</span>
                            <span class="text-xs font-bold font-mono">{{ number_format($bill->hospital_share_percentage, 0) }}%</span>
                        </div>
                        <div class="text-2xl font-black text-emerald-700">
                            PKR {{ number_format($bill->hospital_share, 2) }}
                        </div>
                        <div class="text-[10px] text-emerald-600 font-sans">
                            Retained as Hospital Revenue
                        </div>
                    </div>
                </div>

                <div class="pt-2 text-[11px] text-gray-500 leading-relaxed">
                    <i class="fa-solid fa-info-circle text-blue-500 mr-1"></i>
                    Revenue split percentages were locked at the time of bill generation according to configured service rates.
                </div>
            </div>

            <!-- RECEIVE PAYMENT FORM IF REMAINING DUE > 0 -->
            @if($bill->due_amount > 0)
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-xs uppercase tracking-wider text-amber-900 flex items-center space-x-1.5">
                        <i class="fa-solid fa-hand-holding-dollar text-amber-600"></i>
                        <span>Collect Remaining Due</span>
                    </h3>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                        Partial
                    </span>
                </div>

                <form action="{{ route('hospital-billing.payment', $bill->id) }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Amount to Receive (PKR)</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0.01" 
                            max="{{ $bill->due_amount }}" 
                            name="amount" 
                            value="{{ $bill->due_amount }}" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-emerald-700 focus:bg-white focus:border-emerald-500"
                        >
                        <p class="text-[10px] text-gray-400 mt-1">Outstanding: PKR {{ number_format($bill->due_amount, 2) }}</p>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Payment Method</label>
                        <select name="payment_method" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                            <option value="cash">Cash Counter</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Remarks</label>
                        <input type="text" name="reference_note" placeholder="Optional remarks" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg font-bold shadow-sm transition"
                    >
                        Receive Payment & Settle
                    </button>
                </form>
            </div>
            @endif

        </div>

    </div>

</div>
@endsection
