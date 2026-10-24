@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Flash Alert -->
    @if(session('success') || session('message'))
        <div class="bg-emerald-50 border border-emerald-200/80 text-emerald-800 px-4 py-3.5 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') ?? session('message') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200/80 text-rose-800 px-4 py-3.5 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                <span>{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- Header & Navigation -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl border border-blue-100 shadow-xs">
                <i class="fa-solid fa-id-badge"></i>
            </div>
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $patient->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $patient->patient_number }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Registered on {{ $patient->formatRegisteredAt('F d, Y \a\t h:i A', 'N/A') }}
                </p>
            </div>
        </div>
        @php
            $activeTokenToday = $patient->activeTokenToday();
        @endphp

        <div class="flex flex-wrap items-center gap-2">
            @if($activeTokenToday)
                <a href="{{ route('patient-tokens.show', $activeTokenToday->id) }}" class="inline-flex items-center space-x-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 px-3.5 py-2.5 rounded-xl font-bold text-xs transition">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Today's Token: #{{ $activeTokenToday->formatted_token_number }} ({{ ucfirst($activeTokenToday->status) }})</span>
                </a>
            @else
                <a href="{{ route('patient-tokens.create', ['patient_id' => $patient->id]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-bold text-xs shadow-xs transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-ticket-simple text-xs"></i>
                    <span>Generate Today's Token</span>
                </a>
            @endif

            <a href="{{ route('patients.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3.5 py-2.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Patients</span>
            </a>

            <form action="{{ route('patients.destroy', $patient->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete patient \'{{ addslashes($patient->name) }}\' ({{ $patient->patient_number }})? All related tokens and billing records will also be removed.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200 hover:border-rose-600 px-3.5 py-2.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5 cursor-pointer shadow-xs">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                    <span>Delete Patient</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Patient Details Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-address-card text-blue-600"></i>
                <span>Patient Profile Details</span>
            </h2>
        </div>

        <div class="p-6 sm:p-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-y-6 gap-x-8">

                <!-- Patient Number -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-hashtag"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Patient Number</p>
                        <p class="text-sm font-bold font-mono text-blue-700 mt-0.5">{{ $patient->patient_number }}</p>
                    </div>
                </div>

                <!-- Patient Name -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Patient Name</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $patient->name }}</p>
                    </div>
                </div>

                <!-- Father / Husband Name -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-people-roof"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Father / Husband Name</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            {{ $patient->father_husband_name ?: '—' }}
                        </p>
                    </div>
                </div>

                <!-- Age -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-cake-candles"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Age</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $patient->age }} Years</p>
                    </div>
                </div>

                <!-- Gender -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-venus-mars"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Gender</p>
                        <div class="mt-1">
                            @if($patient->gender === 'Male')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="fa-solid fa-mars mr-1.5 text-[10px]"></i> Male
                                </span>
                            @elseif($patient->gender === 'Female')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                    <i class="fa-solid fa-venus mr-1.5 text-[10px]"></i> Female
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    <i class="fa-solid fa-genderless mr-1.5 text-[10px]"></i> {{ $patient->gender }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Phone -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Phone Number</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            @if($patient->phone)
                                <a href="tel:{{ $patient->phone }}" class="text-blue-600 hover:underline">
                                    {{ $patient->phone }}
                                </a>
                            @else
                                <span class="text-slate-400 font-normal">—</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- CNIC -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-regular fa-id-card"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">CNIC Number</p>
                        <p class="text-sm font-semibold font-mono text-slate-800 mt-0.5">
                            {{ $patient->cnic ?: '—' }}
                        </p>
                    </div>
                </div>

                <!-- Registration Date -->
                <div class="flex items-start space-x-3.5">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Registration Date</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            {{ $patient->formatRegisteredAt('d M, Y - h:i A') }}
                        </p>
                    </div>
                </div>

                <!-- Address -->
                <div class="flex items-start space-x-3.5 md:col-span-2 pt-2 border-t border-slate-100">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center shrink-0 text-sm">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Address</p>
                        <p class="text-sm font-medium text-slate-700 mt-0.5 leading-relaxed">
                            {{ $patient->address ?: 'No address specified' }}
                        </p>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer Actions -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between items-center">
            <a href="{{ route('patients.index') }}" class="inline-flex items-center space-x-2 text-slate-600 hover:text-slate-900 font-semibold text-sm transition">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Patients</span>
            </a>
            <span class="text-xs text-slate-400">Patient Profile</span>
        </div>
    </div>

    <!-- Patient Account & Outstanding History Section -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div class="flex items-center space-x-2.5">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm border border-emerald-100">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-800">
                        Patient Account &amp; Outstanding Balance History
                    </h2>
                    <p class="text-xs text-slate-500">Track all consultation and hospital bills, partial payments and outstanding dues</p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if($patient->pending_balance > 0)
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-rose-50 text-rose-700 border border-rose-200 font-mono">
                        <i class="fa-solid fa-triangle-exclamation mr-1.5 text-rose-500"></i>
                        Outstanding: PKR {{ number_format($patient->pending_balance, 2) }}
                    </span>
                @else
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono">
                        <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i>
                        All Settled (PKR 0.00)
                    </span>
                @endif
            </div>
        </div>

        <!-- Quick Financial Metrics Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 p-5 bg-slate-50/50 border-b border-slate-100 text-xs">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Total Bills</span>
                <span class="text-lg font-bold font-mono text-slate-800 mt-0.5 block">{{ $patient->bills->count() }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Total Invoiced</span>
                <span class="text-lg font-bold font-mono text-slate-800 mt-0.5 block">PKR {{ number_format($patient->bills->sum('net_amount'), 2) }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Total Paid</span>
                <span class="text-lg font-bold font-mono text-emerald-700 mt-0.5 block">PKR {{ number_format($patient->bills->sum('paid_amount'), 2) }}</span>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/70">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Outstanding Balance</span>
                <span class="text-lg font-black font-mono {{ $patient->pending_balance > 0 ? 'text-rose-600' : 'text-emerald-700' }} mt-0.5 block">
                    PKR {{ number_format($patient->pending_balance, 2) }}
                </span>
            </div>
        </div>

        <!-- Bills & Payment History Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Bill & Token #</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Doctor / Service</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Original Amount</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Paid Amount</th>
                        <th class="px-5 py-3 text-right text-xs font-bold uppercase tracking-wider text-slate-500">Outstanding</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($patient->bills as $b)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-mono font-bold text-xs text-blue-700">
                                    {{ $b->bill_number }}
                                </div>
                                @if($b->token)
                                    <div class="text-[11px] font-mono text-slate-500">
                                        Token #{{ $b->token->formatted_token_number }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 text-xs whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($b->bill_date)->format('d-M-Y') }}
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-700 whitespace-nowrap">
                                <span class="font-medium text-slate-900 block">{{ $b->doctor->name ?? '—' }}</span>
                                <span class="text-[11px] text-slate-400">{{ $b->service->service_name ?? 'OPD Consultation' }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-semibold text-slate-800 text-xs whitespace-nowrap">
                                PKR {{ number_format($b->net_amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-emerald-700 text-xs whitespace-nowrap">
                                PKR {{ number_format($b->paid_amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-black text-xs whitespace-nowrap">
                                @if($b->due_amount > 0)
                                    <span class="text-rose-600">PKR {{ number_format($b->due_amount, 2) }}</span>
                                @else
                                    <span class="text-slate-400">PKR 0.00</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap">
                                @if($b->payment_status === 'paid')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        Paid
                                    </span>
                                @elseif($b->payment_status === 'partial')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        Partial
                                    </span>
                                @elseif($b->payment_status === 'free')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-purple-800">
                                        Free
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap space-x-1.5">
                                @if($b->due_amount > 0)
                                    <button 
                                        type="button"
                                        onclick="openPaymentModal('{{ $b->id }}', '{{ $b->bill_number }}', '{{ $b->due_amount }}', '{{ $b->net_amount }}', '{{ $b->paid_amount }}')"
                                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-2.5 py-1 rounded-lg text-xs font-bold transition inline-flex items-center space-x-1"
                                    >
                                        <i class="fa-solid fa-hand-holding-dollar text-[10px]"></i>
                                        <span>Collect Due</span>
                                    </button>
                                @endif
                                <a href="{{ route('hospital-billing.receipt', $b->id) }}" target="_blank" class="text-slate-500 hover:text-slate-800 text-xs p-1" title="Print Receipt">
                                    <i class="fa-solid fa-receipt"></i>
                                </a>
                                @if($b->payments->count() > 0)
                                    <button 
                                        type="button" 
                                        onclick="togglePaymentHistory('payments-{{ $b->id }}')" 
                                        class="text-blue-600 hover:text-blue-800 text-xs font-medium underline"
                                    >
                                        History ({{ $b->payments->count() }})
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @if($b->payments->count() > 0)
                            <tr id="payments-{{ $b->id }}" class="hidden bg-slate-50/60 border-y border-dashed border-slate-200">
                                <td colspan="8" class="px-6 py-3">
                                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                        Payment Transactions for Bill #{{ $b->bill_number }}
                                    </div>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                                        @foreach($b->payments as $pmt)
                                            <div class="bg-white p-2.5 rounded-lg border border-slate-200 text-xs space-y-0.5 text-left">
                                                <div class="flex justify-between font-bold text-slate-800">
                                                    <span class="text-emerald-700 font-mono">PKR {{ number_format($pmt->amount, 2) }}</span>
                                                    <span class="text-[10px] uppercase font-semibold text-slate-500">{{ $pmt->payment_method }}</span>
                                                </div>
                                                <div class="text-[11px] text-slate-500 flex justify-between">
                                                    <span>{{ format_app_datetime($pmt->payment_date ?: $pmt->created_at, 'd M Y h:i A') }}</span>
                                                    <span class="italic text-slate-400">By {{ $pmt->receivedBy->name ?? 'User' }}</span>
                                                </div>
                                                @if($pmt->reference_note)
                                                    <div class="text-[10px] text-slate-400">{{ $pmt->reference_note }}</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400 text-xs">
                                No billing records found for this patient.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Token History Section -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                <span>Token History</span>
            </h2>
            <div class="flex items-center space-x-2">
                @if(!$activeTokenToday)
                    <a href="{{ route('patient-tokens.create', ['patient_id' => $patient->id]) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-3.5 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Generate Today's Token</span>
                    </a>
                @endif
            </div>
        </div>

        @php
            $tokens = $patient->tokens()->with('doctor')->latest('token_date')->latest('token_number')->take(10)->get();
        @endphp

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Token</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Doctor</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Payment Type</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Charged Amount</th>
                        <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-5 py-3 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($tokens as $tok)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 text-slate-700 text-xs font-medium whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($tok->token_date)->format('d-M-Y') }}
                                @if(\Carbon\Carbon::parse($tok->token_date)->isToday())
                                    <span class="ml-1 text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">Today</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800">
                                #{{ $tok->formatted_token_number }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap text-xs text-slate-700">
                                {{ $tok->doctor->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($tok->payment_type === 'free')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        Free
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                        Paid
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800 text-xs">
                                PKR {{ number_format($tok->charged_amount ?? 0, 0) }}
                            </td>
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                @if($tok->status === 'waiting')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-clock mr-1 text-[10px]"></i> Waiting
                                    </span>
                                @elseif($tok->status === 'called')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse mr-1"></span> Called
                                    </span>
                                @elseif($tok->status === 'completed')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check mr-1 text-[10px]"></i> Completed
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                        <i class="fa-solid fa-ban mr-1 text-[10px]"></i> Cancelled
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center whitespace-nowrap space-x-1">
                                <a href="{{ route('patient-tokens.print', $tok->id) }}" target="_blank" class="text-slate-500 hover:text-slate-800 text-xs p-1" title="Print Slip">
                                    <i class="fa-solid fa-print"></i>
                                </a>
                                <a href="{{ route('patient-tokens.show', $tok->id) }}" class="inline-flex items-center space-x-1 text-blue-600 hover:text-blue-800 text-xs font-bold">
                                    <span>View</span>
                                    <i class="fa-solid fa-chevron-right text-[10px]"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-xs">
                                No previous tokens recorded for this patient.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div id="paymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-xs hidden p-4">
        <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-md w-full overflow-hidden animate-scale-up">
            <div class="bg-slate-900 text-white p-5 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-hand-holding-dollar"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-base leading-tight">Record Outstanding Payment</h3>
                        <span id="modalBillNumber" class="text-xs text-slate-300 font-mono">Bill #</span>
                    </div>
                </div>
                <button type="button" onclick="closePaymentModal()" class="text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="recordPaymentForm" method="POST" action="">
                @csrf
                <div class="p-6 space-y-4">
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex justify-between items-center text-xs">
                        <span class="font-semibold text-amber-800">Current Outstanding Due:</span>
                        <span id="modalDueAmount" class="font-mono font-black text-rose-700 text-sm">PKR 0.00</span>
                    </div>

                    <div>
                        <label for="modalPaymentAmount" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Amount to Pay (PKR) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0.01" 
                            name="amount" 
                            id="modalPaymentAmount" 
                            required 
                            class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-base font-mono font-bold text-slate-900 focus:outline-none focus:border-blue-500"
                        >
                    </div>

                    <div>
                        <label for="modalPaymentMethod" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Payment Method <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            name="payment_method" 
                            id="modalPaymentMethod" 
                            required 
                            class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-semibold text-slate-800 focus:outline-none focus:border-blue-500"
                        >
                            <option value="cash">Cash</option>
                            <option value="card">Debit / Credit Card</option>
                            <option value="bank_transfer">Bank Transfer / Online</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="modalReferenceNote" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Reference Note (Optional)
                        </label>
                        <input 
                            type="text" 
                            name="reference_note" 
                            id="modalReferenceNote" 
                            placeholder="e.g. Counter settlement, follow-up visit"
                            class="w-full p-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm text-slate-800 focus:outline-none focus:border-blue-500"
                        >
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end space-x-2">
                    <button type="button" onclick="closePaymentModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:text-slate-900 transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition shadow-xs">
                        Confirm & Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePaymentHistory(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('hidden');
            }
        }

        function openPaymentModal(billId, billNumber, dueAmount, netAmount, paidAmount) {
            const form = document.getElementById('recordPaymentForm');
            form.action = `/hospital-billing/${billId}/payment`;
            document.getElementById('modalBillNumber').textContent = `Bill #${billNumber}`;
            document.getElementById('modalDueAmount').textContent = `PKR ${parseFloat(dueAmount).toFixed(2)}`;
            const amtInput = document.getElementById('modalPaymentAmount');
            amtInput.max = dueAmount;
            amtInput.value = dueAmount;
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closePaymentModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }
    </script>
</div>
@endsection
