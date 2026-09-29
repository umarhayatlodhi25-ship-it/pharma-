@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Flash Alerts -->
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

    <!-- PART 16 & 17: SUCCESS SCREEN / RECEIPT HERO BANNER (When newly generated) -->
    @if(session('token_generated') || request('generated'))
        <div class="bg-gradient-to-r from-emerald-800 via-teal-900 to-slate-900 text-white p-6 sm:p-8 rounded-2xl shadow-lg border border-emerald-700/50 space-y-6 relative overflow-hidden">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 relative z-10 border-b border-emerald-700/40 pb-5">
                <div class="flex items-center space-x-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500 text-white flex items-center justify-center font-bold text-2xl shadow-md">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <div>
                        <span class="text-[11px] font-bold tracking-widest uppercase text-emerald-300">OPD REGISTRATION COMPLETED</span>
                        <h2 class="text-2xl font-black text-white">TOKEN GENERATED SUCCESSFULLY</h2>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <a href="{{ route('patient-tokens.print', $token->id) }}" target="_blank" class="bg-white hover:bg-emerald-50 text-slate-900 px-5 py-2.5 rounded-xl font-bold text-sm shadow-md transition flex items-center space-x-2">
                        <i class="fa-solid fa-print text-sm text-emerald-700"></i>
                        <span>Print Token</span>
                    </a>
                    <a href="{{ route('patient-tokens.index') }}" class="bg-white/10 hover:bg-white/20 text-white px-4 py-2.5 rounded-xl font-semibold text-sm border border-white/20 transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-list-ol text-xs"></i>
                        <span>Back to Token Queue</span>
                    </a>
                </div>
            </div>

            <!-- Quick Summary Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs relative z-10">
                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-xs">
                    <span class="text-emerald-200 block text-[10px] uppercase font-semibold">Token Number</span>
                    <span class="text-2xl font-black font-mono text-amber-300">#{{ $token->formatted_token_number }}</span>
                </div>

                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-xs">
                    <span class="text-emerald-200 block text-[10px] uppercase font-semibold">Patient</span>
                    <span class="text-sm font-bold text-white block">{{ $token->patient->name ?? '—' }}</span>
                    <span class="text-[11px] font-mono text-emerald-200">{{ $token->patient->patient_number ?? '—' }}</span>
                </div>

                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-xs">
                    <span class="text-emerald-200 block text-[10px] uppercase font-semibold">Doctor</span>
                    <span class="text-sm font-bold text-white block">{{ $token->doctor->name ?? '—' }}</span>
                    <span class="text-[11px] text-emerald-200">{{ $token->doctor->specialization ?? '' }}</span>
                </div>

                <div class="p-3 bg-white/10 rounded-xl backdrop-blur-xs">
                    <span class="text-emerald-200 block text-[10px] uppercase font-semibold">Payment / Charged</span>
                    @if($token->payment_type === 'free')
                        <span class="text-sm font-black text-amber-300 block">FREE (PKR 0)</span>
                        <span class="text-[11px] text-emerald-200">{{ $token->free_reason }}</span>
                    @else
                        <span class="text-sm font-black text-white block">PAID (PKR {{ number_format($token->charged_amount, 0) }})</span>
                        <span class="text-[11px] text-emerald-200">Fee: PKR {{ number_format($token->consultation_fee, 0) }}</span>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Header & Navigation -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 text-blue-700 border border-blue-200 flex flex-col items-center justify-center shrink-0">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-500">TOKEN</span>
                <span class="text-2xl font-black font-mono leading-none">#{{ $token->formatted_token_number }}</span>
            </div>
            <div>
                <div class="flex items-center space-x-2.5">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Token #{{ $token->formatted_token_number }}</h1>
                    <!-- Status Badge -->
                    @if($token->status === 'waiting')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                            <i class="fa-solid fa-clock mr-1 text-[10px]"></i> Waiting
                        </span>
                    @elseif($token->status === 'called')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse mr-1"></span> Called / Serving
                        </span>
                    @elseif($token->status === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            <i class="fa-solid fa-check mr-1 text-[10px]"></i> Completed
                        </span>
                    @elseif($token->status === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                            <i class="fa-solid fa-ban mr-1 text-[10px]"></i> Cancelled
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Date: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($token->token_date)->format('d-M-Y') }}</strong>
                    &bull; Time: <span class="text-slate-700">{{ $token->created_at ? $token->created_at->format('h:i A') : '—' }}</span>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Print Slip -->
            <a href="{{ route('patient-tokens.print', $token->id) }}" target="_blank" class="bg-slate-900 hover:bg-slate-800 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-xs transition flex items-center space-x-1.5">
                <i class="fa-solid fa-print text-xs"></i>
                <span>Print Slip</span>
            </a>

            <a href="{{ route('patient-tokens.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Queue</span>
            </a>
            <a href="{{ route('patient-tokens.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-xs transition flex items-center space-x-1.5">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>New Token</span>
            </a>
        </div>
    </div>

    <!-- PART 20: TOKEN & PATIENT & DOCTOR & FINANCIAL DETAILS CARD -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                <i class="fa-solid fa-id-card-clip text-blue-600"></i>
                <span>OPD Token Details</span>
            </h2>
            <span class="text-xs text-slate-400 font-mono">Token Reference ID: #{{ $token->id }}</span>
        </div>

        <div class="p-6 sm:p-8 space-y-8">

            <!-- Section 1: Patient Information (Part 20) -->
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-user text-slate-400"></i>
                    <span>Patient Information</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-y-5 gap-x-6">
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Patient Number</p>
                        <p class="text-sm font-mono font-bold text-blue-700 mt-0.5">
                            <a href="{{ route('patients.show', $token->patient_id) }}" class="hover:underline flex items-center space-x-1.5">
                                <span>{{ $token->patient->patient_number ?? '—' }}</span>
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
                            </a>
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Patient Name</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $token->patient->name ?? 'Unknown' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Father / Husband Name</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">{{ $token->patient->father_husband_name ?: '—' }}</p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Age & Gender</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            {{ $token->patient->age ?? '—' }} Years &bull; {{ $token->patient->gender ?? '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Phone Number</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            {{ $token->patient->phone ?: '—' }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">CNIC</p>
                        <p class="text-sm font-mono font-semibold text-slate-800 mt-0.5">
                            {{ $token->patient->cnic ?: '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Section 2: Doctor Information (Part 20) -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-user-doctor text-slate-400"></i>
                    <span>Consulting Doctor</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-y-4 gap-x-6 p-4 bg-slate-50 rounded-xl border border-slate-200/60">
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Doctor Name</p>
                        <p class="text-sm font-bold text-slate-900 mt-0.5">{{ $token->doctor->name ?? 'Not Assigned' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Specialization</p>
                        <p class="text-sm font-semibold text-blue-700 mt-0.5">{{ $token->doctor->specialization ?? 'General' }}</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Financial & Payment Details (Part 20) -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-receipt text-slate-400"></i>
                    <span>Financial Details</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Consultation Fee</span>
                        <p class="text-lg font-mono font-bold text-slate-800 mt-1">
                            PKR {{ number_format($token->consultation_fee ?? 0, 0) }}
                        </p>
                        <span class="text-[10px] text-slate-400">Doctor's regular fee</span>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Payment Type</span>
                        <div class="mt-1">
                            @if($token->payment_type === 'free')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                    FREE CONSULTATION
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    PAID CONSULTATION
                                </span>
                            @endif
                        </div>
                        <span class="text-[10px] text-slate-400">
                            {{ $token->payment_type === 'free' ? 'Fee 100% Waived' : 'Full Payment' }}
                        </span>
                    </div>

                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/60">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Charged Amount</span>
                        <p class="text-xl font-mono font-black {{ $token->charged_amount == 0 ? 'text-emerald-700' : 'text-blue-900' }} mt-1">
                            PKR {{ number_format($token->charged_amount ?? 0, 0) }}
                        </p>
                        <span class="text-[10px] text-slate-400">Amount collected at reception</span>
                    </div>
                </div>

                <!-- If Free: Show Free Reason & Other Reason -->
                @if($token->payment_type === 'free')
                    <div class="mt-4 p-4 bg-emerald-50/70 border border-emerald-200/80 rounded-xl text-xs space-y-1">
                        <div class="flex items-center space-x-2 font-bold text-emerald-900">
                            <i class="fa-solid fa-hand-holding-heart text-emerald-600"></i>
                            <span>Free Consultation Reason: <u>{{ $token->free_reason }}</u></span>
                        </div>
                        @if($token->other_reason)
                            <p class="text-emerald-800 pt-1">
                                <strong>Reason Details:</strong> {{ $token->other_reason }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Section 4: Token Lifecycle Timeline -->
            <div class="pt-6 border-t border-slate-100">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 flex items-center space-x-2">
                    <i class="fa-solid fa-stopwatch text-slate-400"></i>
                    <span>Token Lifecycle Timeline</span>
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">1. Issued At</p>
                        <p class="text-sm font-bold text-slate-800 mt-1">
                            {{ $token->created_at ? $token->created_at->format('h:i A') : '—' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $token->created_at ? $token->created_at->format('d-M-Y') : '' }}</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">2. Called At</p>
                        <p class="text-sm font-bold @if($token->called_at) text-blue-700 @else text-slate-400 font-normal @endif mt-1">
                            {{ $token->called_at ? $token->called_at->format('h:i A') : 'Not yet called' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $token->called_at ? $token->called_at->format('d-M-Y') : '' }}</p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/60">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">3. Completed At</p>
                        <p class="text-sm font-bold @if($token->completed_at) text-emerald-700 @else text-slate-400 font-normal @endif mt-1">
                            {{ $token->completed_at ? $token->completed_at->format('h:i A') : 'Pending completion' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">{{ $token->completed_at ? $token->completed_at->format('d-M-Y') : '' }}</p>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            @if($token->notes)
                <div class="pt-6 border-t border-slate-100">
                    <p class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Notes / Purpose</p>
                    <p class="text-sm text-slate-700 mt-1 p-3 bg-slate-50 rounded-xl border border-slate-200/60">
                        {{ $token->notes }}
                    </p>
                </div>
            @endif

        </div>

        <!-- Footer Action Toolbar -->
        <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex flex-wrap justify-between items-center gap-3">
            <a href="{{ route('patient-tokens.index') }}" class="text-slate-600 hover:text-slate-900 font-semibold text-sm transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Token Queue</span>
            </a>

            <div class="flex items-center space-x-2">
                <!-- Print Slip -->
                <a href="{{ route('patient-tokens.print', $token->id) }}" target="_blank" class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-4 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Print Token Slip</span>
                </a>

                @if($token->status === 'waiting')
                    <form action="{{ route('patient-tokens.call', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-bullhorn text-xs"></i>
                            <span>Call This Patient</span>
                        </button>
                    </form>

                    <form action="{{ route('patient-tokens.cancel', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this token?');">
                        @csrf
                        <button type="submit" class="bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 transition">
                            Cancel Token
                        </button>
                    </form>

                @elseif($token->status === 'called')
                    <form action="{{ route('patient-tokens.complete', $token->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                            <i class="fa-solid fa-check text-xs"></i>
                            <span>Mark Completed</span>
                        </button>
                    </form>

                    <form action="{{ route('patient-tokens.cancel', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this token?');">
                        @csrf
                        <button type="submit" class="bg-white hover:bg-rose-50 text-slate-600 hover:text-rose-700 px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 transition">
                            Cancel Token
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection
