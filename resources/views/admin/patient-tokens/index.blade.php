@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Top Header & Actions -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100">
                    <i class="fa-solid fa-ticket-simple"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">OPD Token Queue</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Manage today's OPD patient queue &bull; 
                        <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($today)->format('l, d M, Y') }}</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Call Next Patient Form -->
            <form action="{{ route('patient-tokens.call-next') }}" method="POST">
                @csrf
                <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm hover:shadow transition flex items-center space-x-2">
                    <i class="fa-solid fa-bullhorn text-sm"></i>
                    <span>Call Next Patient</span>
                </button>
            </form>

            <!-- Generate Token Button -->
            <a href="{{ route('patient-tokens.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-sm hover:shadow transition flex items-center space-x-2">
                <i class="fa-solid fa-plus text-xs"></i>
                <span>+ Generate Token</span>
            </a>
        </div>
    </div>

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

    @if(session('info'))
        <div class="bg-blue-50 border border-blue-200/80 text-blue-800 px-4 py-3.5 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-circle-info text-blue-600 text-base"></i>
                <span>{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-blue-500 hover:text-blue-800 text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-200/80 text-amber-800 px-4 py-3.5 rounded-xl text-sm font-medium flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                <span>{{ session('warning') }}</span>
            </div>
            @if(session('existing_token_id'))
                <a href="{{ route('patient-tokens.show', session('existing_token_id')) }}" class="bg-amber-600 text-white px-3 py-1 rounded-lg text-xs font-bold hover:bg-amber-700 ml-3">
                    View Token
                </a>
            @endif
        </div>
    @endif

    <!-- PART 22: DAILY OPD FINANCIAL SUMMARY CARDS -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today's Total Tokens -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Today's Tokens</p>
                <h3 class="text-2xl font-black text-slate-800 mt-1">{{ $totalToday }}</h3>
                <span class="text-[11px] text-slate-400">Total visits registered</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-ticket"></i>
            </div>
        </div>

        <!-- Paid Patients -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Paid Patients</p>
                <h3 class="text-2xl font-black text-blue-700 mt-1">{{ $paidCount }}</h3>
                <span class="text-[11px] text-slate-400">Regular fee paying</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-money-bill-wave"></i>
            </div>
        </div>

        <!-- Free Patients -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Free Patients</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $freeCount }}</h3>
                <span class="text-[11px] text-slate-400">100% fee waived</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
        </div>

        <!-- Total Collected (SUM of charged_amount) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Collected</p>
                <h3 class="text-2xl font-black text-slate-900 mt-1">PKR {{ number_format($totalCollected, 0) }}</h3>
                <span class="text-[11px] text-emerald-600 font-semibold">Today's OPD revenue</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-900 text-amber-400 flex items-center justify-center text-lg">
                <i class="fa-solid fa-cash-register"></i>
            </div>
        </div>
    </div>

    <!-- Queue Status Strip -->
    <div class="bg-slate-100/70 p-3 rounded-xl border border-slate-200 flex flex-wrap items-center justify-between text-xs gap-3 font-medium">
        <div class="flex items-center space-x-4">
            <span class="text-slate-500 font-bold uppercase tracking-wider text-[11px]">Queue Status:</span>
            <span class="inline-flex items-center space-x-1.5 text-amber-800 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200">
                <i class="fa-solid fa-hourglass-half text-amber-600 text-[10px]"></i>
                <span>Waiting: <strong>{{ $waitingCount }}</strong></span>
            </span>
            <span class="inline-flex items-center space-x-1.5 text-blue-800 bg-blue-50 px-2.5 py-1 rounded-lg border border-blue-200">
                <i class="fa-solid fa-bullhorn text-blue-600 text-[10px]"></i>
                <span>Called: <strong>{{ $calledCount }}</strong></span>
            </span>
            <span class="inline-flex items-center space-x-1.5 text-emerald-800 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200">
                <i class="fa-solid fa-check text-emerald-600 text-[10px]"></i>
                <span>Completed: <strong>{{ $completedCount }}</strong></span>
            </span>
        </div>
        <div class="text-slate-500">
            Total Queue Length: <strong class="text-slate-800">{{ $tokens->total() }}</strong>
        </div>
    </div>

    <!-- NOW SERVING BANNER -->
    @if($nowServing)
        <div class="bg-gradient-to-r from-blue-900 via-indigo-900 to-slate-900 text-white p-6 sm:p-7 rounded-2xl shadow-md border border-blue-800/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 relative overflow-hidden">
            <div class="absolute -right-10 -bottom-10 opacity-10 text-9xl text-white pointer-events-none">
                <i class="fa-solid fa-bullhorn"></i>
            </div>

            <div class="flex items-center space-x-5 z-10">
                <div class="w-20 h-20 rounded-2xl bg-blue-600/30 border border-blue-400/40 flex flex-col items-center justify-center shrink-0">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-blue-300">TOKEN</span>
                    <span class="text-3xl font-black font-mono tracking-tight text-white">#{{ $nowServing->formatted_token_number }}</span>
                </div>
                <div>
                    <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-400/20 text-amber-300 border border-amber-400/30 mb-1">
                        <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
                        <span>NOW SERVING</span>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight text-white flex items-center space-x-2">
                        <span>{{ $nowServing->patient->name ?? 'Unknown Patient' }}</span>
                        <span class="text-xs font-mono font-medium px-2 py-0.5 rounded-md bg-white/10 text-slate-300 border border-white/10">
                            {{ $nowServing->patient->patient_number ?? '' }}
                        </span>
                    </h2>
                    <p class="text-xs text-blue-200/80 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                        @if($nowServing->doctor)
                            <span><i class="fa-solid fa-user-doctor mr-1 opacity-70"></i> {{ $nowServing->doctor->name }} ({{ $nowServing->doctor->specialization }})</span>
                        @endif
                        <span><i class="fa-solid fa-cake-candles mr-1 opacity-70"></i> {{ $nowServing->patient->age ?? '—' }} Yrs ({{ $nowServing->patient->gender ?? '—' }})</span>
                        @if($nowServing->patient && $nowServing->patient->phone)
                            <span><i class="fa-solid fa-phone mr-1 opacity-70"></i> {{ $nowServing->patient->phone }}</span>
                        @endif
                        <span><i class="fa-regular fa-clock mr-1 opacity-70"></i> Called at {{ $nowServing->called_at ? $nowServing->called_at->format('h:i A') : '—' }}</span>
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 z-10 w-full sm:w-auto">
                <!-- Complete Button -->
                <form action="{{ route('patient-tokens.complete', $nowServing->id) }}" method="POST" class="flex-1 sm:flex-none">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-check"></i>
                        <span>Complete</span>
                    </button>
                </form>

                <!-- Cancel Button -->
                <form action="{{ route('patient-tokens.cancel', $nowServing->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this called token?');" class="flex-1 sm:flex-none">
                    @csrf
                    <button type="submit" class="w-full sm:w-auto bg-white/10 hover:bg-rose-600/80 text-white px-4 py-2.5 rounded-xl font-semibold text-sm border border-white/20 transition flex items-center justify-center space-x-1.5">
                        <i class="fa-solid fa-ban text-xs"></i>
                        <span>Cancel</span>
                    </button>
                </form>

                <!-- Print Slip -->
                <a href="{{ route('patient-tokens.print', $nowServing->id) }}" target="_blank" class="bg-white/10 hover:bg-white/20 text-white p-2.5 rounded-xl font-semibold text-sm border border-white/20 transition" title="Print Token Slip">
                    <i class="fa-solid fa-print"></i>
                </a>

                <!-- View Token -->
                <a href="{{ route('patient-tokens.show', $nowServing->id) }}" class="bg-white/10 hover:bg-white/20 text-white p-2.5 rounded-xl font-semibold text-sm border border-white/20 transition" title="Token Details">
                    <i class="fa-solid fa-eye"></i>
                </a>
            </div>
        </div>
    @endif

    <!-- PART 19: QUEUE TABLE -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-base font-bold text-slate-900 tracking-tight flex items-center space-x-2">
                    <i class="fa-solid fa-list-ol text-blue-600 text-sm"></i>
                    <span>Today's OPD Queue</span>
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Chronological listing of tokens issued today</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-400">Total: <strong class="text-slate-700">{{ $tokens->total() }}</strong> tokens</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Token</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Doctor</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Payment</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Time</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($tokens as $t)
                        <tr class="hover:bg-slate-50/70 transition @if($t->status === 'called') bg-blue-50/40 @endif">
                            <!-- Token Number -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-mono font-black @if($t->status === 'called') bg-blue-600 text-white @elseif($t->status === 'waiting') bg-amber-100 text-amber-900 border border-amber-200 @elseif($t->status === 'completed') bg-emerald-50 text-emerald-700 border border-emerald-200 @else bg-slate-100 text-slate-600 border border-slate-200 @endif">
                                    {{ $t->formatted_token_number }}
                                </span>
                            </td>

                            <!-- Patient -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-slate-900">
                                    {{ $t->patient->name ?? 'Unknown' }}
                                </div>
                                <div class="text-xs font-mono text-slate-500">
                                    <a href="{{ route('patients.show', $t->patient_id) }}" class="text-blue-600 hover:underline">
                                        {{ $t->patient->patient_number ?? '—' }}
                                    </a>
                                    @if($t->patient && $t->patient->phone)
                                        &bull; {{ $t->patient->phone }}
                                    @endif
                                </div>
                            </td>

                            <!-- Doctor -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-medium text-slate-800 text-xs">
                                    {{ $t->doctor->name ?? '—' }}
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $t->doctor->specialization ?? '' }}
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($t->status === 'waiting')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        <i class="fa-solid fa-clock mr-1.5 text-[10px]"></i> Waiting
                                    </span>
                                @elseif($t->status === 'called')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse mr-1.5"></span> Called
                                    </span>
                                @elseif($t->status === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        <i class="fa-solid fa-check mr-1.5 text-[10px]"></i> Completed
                                    </span>
                                @elseif($t->status === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">
                                        <i class="fa-solid fa-ban mr-1.5 text-[10px]"></i> Cancelled
                                    </span>
                                @endif
                            </td>

                            <!-- Payment Type -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($t->payment_type === 'free')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        FREE
                                    </span>
                                    @if($t->free_reason)
                                        <span class="block text-[10px] text-slate-400 mt-0.5">{{ $t->free_reason }}</span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-100">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            <!-- Charged Amount -->
                            <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-800 text-xs">
                                PKR {{ number_format($t->charged_amount ?? 0, 0) }}
                            </td>

                            <!-- Issued Time -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-slate-500 text-xs">
                                {{ $t->created_at ? $t->created_at->format('h:i A') : '—' }}
                            </td>

                            <!-- Action Buttons -->
                            <td class="px-4 py-3.5 text-center whitespace-nowrap space-x-1">
                                <!-- Print Button (Part 18) -->
                                <a href="{{ route('patient-tokens.print', $t->id) }}" target="_blank" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-2.5 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 transition inline-flex items-center space-x-1" title="Print Slip">
                                    <i class="fa-solid fa-print text-[11px]"></i>
                                    <span>Print</span>
                                </a>

                                @if($t->status === 'waiting')
                                    <!-- Call -->
                                    <form action="{{ route('patient-tokens.call', $t->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-2xs transition inline-flex items-center space-x-1">
                                            <i class="fa-solid fa-bullhorn text-[10px]"></i>
                                            <span>Call</span>
                                        </button>
                                    </form>

                                    <!-- Cancel -->
                                    <form action="{{ route('patient-tokens.cancel', $t->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Cancel Token #{{ $t->formatted_token_number }}?');">
                                        @csrf
                                        <button type="submit" class="bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 px-2 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 transition">
                                            Cancel
                                        </button>
                                    </form>

                                @elseif($t->status === 'called')
                                    <!-- Complete -->
                                    <form action="{{ route('patient-tokens.complete', $t->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold shadow-2xs transition inline-flex items-center space-x-1">
                                            <i class="fa-solid fa-check text-[10px]"></i>
                                            <span>Complete</span>
                                        </button>
                                    </form>

                                    <!-- Cancel -->
                                    <form action="{{ route('patient-tokens.cancel', $t->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Cancel Token #{{ $t->formatted_token_number }}?');">
                                        @csrf
                                        <button type="submit" class="bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 px-2 py-1.5 rounded-lg text-xs font-semibold border border-slate-200 transition">
                                            Cancel
                                        </button>
                                    </form>
                                @endif

                                <!-- View Details -->
                                <a href="{{ route('patient-tokens.show', $t->id) }}" class="text-slate-400 hover:text-slate-700 p-1.5" title="Details">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-ticket-simple"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-700">No tokens issued today</p>
                                        <p class="text-xs text-slate-400 mt-0.5">Click "+ Generate Token" to issue the first token for today's OPD queue.</p>
                                    </div>
                                    <a href="{{ route('patient-tokens.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition">
                                        + Generate Token
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($tokens->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $tokens->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
