@extends('layouts.app')

@section('content')
<div class="space-y-6">

    <!-- Header & Action Card -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100">
                    <i class="fa-solid fa-hospital-user"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Patient Management</h1>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Filter patients by registration, today/monthly visits, and doctor consultations</p>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('patients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm shadow-sm hover:shadow transition flex items-center space-x-2">
                <i class="fa-solid fa-user-plus"></i>
                <span>+ Register Patient</span>
            </a>
        </div>
    </div>

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

    <!-- 1. KPI Stats Summary Cards (Today, Monthly, Consultations) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Patients -->
        <a href="{{ route('patients.index') }}" class="bg-white p-5 rounded-2xl border {{ $period === 'all' && empty($doctorId) && ($status === 'all' || empty($status)) ? 'border-blue-500 ring-2 ring-blue-500/20 shadow-md' : 'border-slate-200/80 shadow-xs' }} hover:border-blue-400 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Total Patients</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_patients']) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 flex items-center space-x-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                        <span>All registered patients</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl group-hover:scale-110 transition shadow-2xs">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </a>

        <!-- Today's Patients -->
        <a href="{{ route('patients.index', ['period' => 'today']) }}" class="bg-white p-5 rounded-2xl border {{ $period === 'today' ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md bg-emerald-50/20' : 'border-slate-200/80 shadow-xs' }} hover:border-emerald-400 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-1.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Today's Patients</p>
                        @if($period === 'today')
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Filtered</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['today_patients']) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 flex items-center space-x-1">
                        <span class="font-bold text-emerald-600">{{ $stats['today_visits'] }}</span>
                        <span>visits today ({{ $stats['today_completed_visits'] }} seen)</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition shadow-2xs">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
        </a>

        <!-- Monthly Patients -->
        <a href="{{ route('patients.index', ['period' => 'this_month']) }}" class="bg-white p-5 rounded-2xl border {{ $period === 'this_month' ? 'border-indigo-500 ring-2 ring-indigo-500/20 shadow-md bg-indigo-50/20' : 'border-slate-200/80 shadow-xs' }} hover:border-indigo-400 hover:shadow-md transition group block">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-1.5">
                        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Monthly Patients</p>
                        @if($period === 'this_month')
                            <span class="px-1.5 py-0.2 rounded text-[10px] font-bold bg-indigo-100 text-indigo-800">Filtered</span>
                        @endif
                    </div>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['month_patients']) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 flex items-center space-x-1">
                        <span class="font-bold text-indigo-600">{{ $stats['month_visits'] }}</span>
                        <span>visits in {{ now(config('app.display_timezone', 'Asia/Karachi'))->format('F Y') }}</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl group-hover:scale-110 transition shadow-2xs">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>
        </a>

        <!-- Doctor Consultations / Examined -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-purple-600">Doctor Consultations</p>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_visits']) }}</h3>
                    <p class="text-xs text-slate-500 mt-1 flex items-center space-x-1">
                        <span class="font-bold text-purple-700">{{ $stats['total_completed_visits'] }}</span>
                        <span>examined & completed</span>
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl shadow-2xs">
                    <i class="fa-solid fa-user-doctor"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Doctor Activity Summary ("Kis Doctor Ne Kitne Mareez Dekhe") -->
    @php
        $doctorsWithVisits = $doctorStats->where('total_tokens_count', '>', 0);
    @endphp
    @if($doctorsWithVisits->count() > 0)
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 space-y-3">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
            <div class="flex items-center space-x-2.5">
                <div class="w-7 h-7 rounded-lg bg-teal-50 text-teal-600 flex items-center justify-center text-xs border border-teal-100">
                    <i class="fa-solid fa-stethoscope"></i>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-slate-800">Doctor-wise Consultations (Kis Doctor Ne Kitne Mareez Dekhe)</h2>
                    <p class="text-[11px] text-slate-500">Click any doctor card to instantly filter their patients</p>
                </div>
            </div>
            @if(!empty($doctorId))
                <a href="{{ route('patients.index', request()->except(['page', 'doctor_id'])) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center space-x-1">
                    <i class="fa-solid fa-xmark text-[10px]"></i>
                    <span>Show All Doctors</span>
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 pt-1">
            @foreach($doctorsWithVisits as $doc)
                @php
                    $isDocActive = (string)$doctorId === (string)$doc->id;
                @endphp
                <a href="{{ route('patients.index', array_merge(request()->except(['page', 'doctor_id']), $isDocActive ? [] : ['doctor_id' => $doc->id])) }}" 
                   class="p-3.5 rounded-xl border transition flex items-start space-x-3 group {{ $isDocActive ? 'bg-blue-50/80 border-blue-500 ring-2 ring-blue-500/20 shadow-xs' : 'bg-slate-50/60 hover:bg-white hover:border-blue-300 hover:shadow-xs border-slate-200/80' }}">
                    <div class="w-10 h-10 rounded-xl {{ $isDocActive ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-slate-200 group-hover:border-blue-400' }} flex items-center justify-center shrink-0 font-bold text-sm shadow-2xs">
                        <i class="fa-solid fa-user-doctor"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1">
                            <h4 class="text-xs font-bold text-slate-900 truncate group-hover:text-blue-600 transition">{{ $doc->name }}</h4>
                            @if($isDocActive)
                                <span class="text-blue-600 text-xs shrink-0"><i class="fa-solid fa-circle-check"></i></span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-500 truncate mt-0.5">{{ $doc->specialization ?: 'Doctor / Consultant' }}</p>
                        
                        <div class="mt-2.5 flex items-center justify-between text-[11px] pt-2 border-t border-slate-200/60">
                            <span class="font-bold text-slate-800">
                                <i class="fa-solid fa-users text-slate-400 mr-1 text-[10px]"></i>{{ $doc->total_patients_count }} Patients
                            </span>
                            <span class="text-slate-500">
                                {{ $doc->total_tokens_count }} Visits
                            </span>
                        </div>
                        
                        <div class="mt-1.5 flex items-center justify-between text-[10px]">
                            <span class="text-emerald-700 font-medium">
                                <i class="fa-solid fa-check text-[9px] mr-0.5"></i>{{ $doc->completed_tokens_count }} Examined
                            </span>
                            @if($doc->today_tokens_count > 0)
                                <span class="px-1.5 py-0.2 rounded font-bold bg-emerald-100 text-emerald-800">{{ $doc->today_tokens_count }} today</span>
                            @elseif($doc->month_tokens_count > 0)
                                <span class="px-1.5 py-0.2 rounded font-semibold bg-indigo-50 text-indigo-700">{{ $doc->month_tokens_count }} this mo</span>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
    @endif

    <!-- 3. Comprehensive Filter Form -->
    <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 space-y-4">
        <form method="GET" action="{{ route('patients.index') }}" id="patientFilterForm" class="space-y-4">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                
                <!-- 1. Search Box -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-magnifying-glass text-slate-400 mr-1"></i>Search Patient
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            name="search" 
                            value="{{ $search ?? '' }}" 
                            placeholder="ID (PT-0001), Name, Phone, CNIC..." 
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                    </div>
                </div>

                <!-- 2. Date Period Presets -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-calendar text-slate-400 mr-1"></i>Visit / Reg Period
                    </label>
                    <select name="period" id="periodSelect" onchange="handlePeriodChange(this.value)" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 font-medium transition cursor-pointer">
                        <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="today" {{ $period === 'today' ? 'selected' : '' }}>📅 Today ({{ now(config('app.display_timezone', 'Asia/Karachi'))->format('d M') }})</option>
                        <option value="yesterday" {{ $period === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                        <option value="this_week" {{ $period === 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ $period === 'this_month' ? 'selected' : '' }}>🗓️ This Month ({{ now(config('app.display_timezone', 'Asia/Karachi'))->format('F Y') }})</option>
                        <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Date Range...</option>
                    </select>
                </div>

                <!-- 3. Doctor Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-stethoscope text-slate-400 mr-1"></i>Doctor (Examined By)
                    </label>
                    <select name="doctor_id" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 font-medium transition cursor-pointer">
                        <option value="">All Doctors</option>
                        @foreach($allDoctors as $doc)
                            <option value="{{ $doc->id }}" {{ (string)$doctorId === (string)$doc->id ? 'selected' : '' }}>
                                {{ $doc->name }} {{ $doc->specialization ? '('.$doc->specialization.')' : '' }} [{{ $doc->total_tokens_count }} visits]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- 4. Consultation Status -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        <i class="fa-solid fa-clipboard-check text-slate-400 mr-1"></i>Examination Status
                    </label>
                    <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 font-medium transition cursor-pointer">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>✅ Completed / Examined</option>
                        <option value="in_consultation" {{ $status === 'in_consultation' ? 'selected' : '' }}>🩺 In Consultation</option>
                        <option value="waiting" {{ $status === 'waiting' ? 'selected' : '' }}>⏳ Waiting</option>
                        <option value="has_visits" {{ $status === 'has_visits' ? 'selected' : '' }}>👥 With Doctor Visits</option>
                        <option value="no_visits" {{ $status === 'no_visits' ? 'selected' : '' }}>🆕 No Visits Yet</option>
                    </select>
                </div>

            </div>

            <!-- Custom Date Range Sub-row -->
            <div id="customDateRow" class="{{ $period === 'custom' || (!empty($fromDate) && $period === 'custom') ? 'grid' : 'hidden' }} grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $fromDate ?? '' }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $toDate ?? '' }}" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800">
                </div>
            </div>

            <!-- Filter Action Controls -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-2">
                <div class="flex items-center gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold text-xs shadow-xs hover:shadow transition flex items-center space-x-2 cursor-pointer">
                        <i class="fa-solid fa-filter text-xs"></i>
                        <span>Apply Filter</span>
                    </button>
                    
                    @if($period !== 'all' || !empty($doctorId) || ($status && $status !== 'all') || !empty($search) || !empty($fromDate) || !empty($toDate))
                        <a href="{{ route('patients.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center space-x-1.5" title="Clear all filters">
                            <i class="fa-solid fa-rotate-left text-xs"></i>
                            <span>Reset Filters</span>
                        </a>
                    @endif
                </div>

                <div class="flex items-center space-x-2 text-xs text-slate-500">
                    <span>Showing <strong class="text-slate-900 font-bold">{{ $patients->total() }}</strong> patients</span>
                    @if($selectedDoctor)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                            Dr: {{ $selectedDoctor->name }}
                        </span>
                    @endif
                    @if($period === 'today')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Today
                        </span>
                    @elseif($period === 'this_month')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            This Month
                        </span>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Active Filter Tags / Indicator -->
    @if($period !== 'all' || !empty($doctorId) || ($status && $status !== 'all') || !empty($search))
        <div class="bg-blue-50/60 border border-blue-100 p-3 rounded-xl flex flex-wrap items-center gap-2 text-xs text-slate-700">
            <span class="font-bold text-blue-900 flex items-center space-x-1">
                <i class="fa-solid fa-sliders text-blue-600"></i>
                <span>Active Filters:</span>
            </span>

            @if($period !== 'all')
                <span class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-800 px-2.5 py-1 rounded-lg font-semibold text-xs shadow-2xs">
                    <span>Period: {{ ucfirst(str_replace('_', ' ', $period)) }}</span>
                    <a href="{{ route('patients.index', request()->except(['period', 'from_date', 'to_date', 'page'])) }}" class="text-blue-500 hover:text-blue-800"><i class="fa-solid fa-xmark"></i></a>
                </span>
            @endif

            @if($selectedDoctor)
                <span class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-800 px-2.5 py-1 rounded-lg font-semibold text-xs shadow-2xs">
                    <span>Doctor: {{ $selectedDoctor->name }}</span>
                    <a href="{{ route('patients.index', request()->except(['doctor_id', 'page'])) }}" class="text-blue-500 hover:text-blue-800"><i class="fa-solid fa-xmark"></i></a>
                </span>
            @endif

            @if(!empty($status) && $status !== 'all')
                <span class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-800 px-2.5 py-1 rounded-lg font-semibold text-xs shadow-2xs">
                    <span>Status: {{ ucfirst(str_replace('_', ' ', $status)) }}</span>
                    <a href="{{ route('patients.index', request()->except(['status', 'page'])) }}" class="text-blue-500 hover:text-blue-800"><i class="fa-solid fa-xmark"></i></a>
                </span>
            @endif

            @if(!empty($search))
                <span class="inline-flex items-center gap-1.5 bg-white border border-blue-200 text-blue-800 px-2.5 py-1 rounded-lg font-semibold text-xs shadow-2xs">
                    <span>Keyword: "{{ $search }}"</span>
                    <a href="{{ route('patients.index', request()->except(['search', 'page'])) }}" class="text-blue-500 hover:text-blue-800"><i class="fa-solid fa-xmark"></i></a>
                </span>
            @endif

            <a href="{{ route('patients.index') }}" class="text-xs font-bold text-rose-600 hover:text-rose-800 ml-auto">
                Clear All
            </a>
        </div>
    @endif

    <!-- 4. Patients Data Table -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">#</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient ID</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Patient Name</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Age & Gender</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Contact / Phone</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Doctor & Consultation</th>
                        <th class="px-4 py-3.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500">Registration Date</th>
                        <th class="px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wider text-slate-500">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($patients as $patient)
                        @php
                            // Determine relevant token/consultation to display:
                            // If filtered by doctor, pick token for this doctor. Otherwise pick latest token.
                            $displayToken = $patient->tokens->first() ?: $patient->latestToken;
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3.5 text-slate-500 text-xs font-medium">
                                {{ $loop->iteration + ($patients->currentPage() - 1) * $patients->perPage() }}
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $patient->patient_number }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $patient->name }}</div>
                                @if($patient->father_husband_name)
                                    <div class="text-[11px] text-slate-500 font-normal">S/O, D/O: {{ $patient->father_husband_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-bold text-slate-800 text-xs">{{ $patient->age }}</span>
                                    <span class="text-[11px] text-slate-400">Yrs</span>
                                    <span class="text-slate-300">•</span>
                                    @if($patient->gender === 'Male')
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[11px] font-medium bg-blue-100 text-blue-800">
                                            <i class="fa-solid fa-mars mr-1 text-[9px]"></i> Male
                                        </span>
                                    @elseif($patient->gender === 'Female')
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[11px] font-medium bg-pink-100 text-pink-800">
                                            <i class="fa-solid fa-venus mr-1 text-[9px]"></i> Female
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.2 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700">
                                            {{ $patient->gender }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 text-xs whitespace-nowrap">
                                @if($patient->phone)
                                    <div class="text-slate-800 font-semibold flex items-center space-x-1">
                                        <i class="fa-solid fa-phone text-slate-400 text-[10px]"></i>
                                        <span>{{ $patient->phone }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                                @if($patient->cnic)
                                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $patient->cnic }}</div>
                                @endif
                            </td>

                            <!-- Doctor & Consultation Column -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($displayToken)
                                    <div class="space-y-1">
                                        <div class="flex items-center space-x-1.5">
                                            <span class="w-5 h-5 rounded-md bg-teal-50 text-teal-700 flex items-center justify-center text-[10px] border border-teal-200 shrink-0">
                                                <i class="fa-solid fa-user-md"></i>
                                            </span>
                                            <span class="text-xs font-bold text-slate-900">
                                                {{ $displayToken->doctor?->name ?? 'OPD Doctor' }}
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-1.5 text-[11px]">
                                            <span class="font-mono text-slate-500 font-semibold">Token #{{ $displayToken->token_number }}</span>
                                            <span class="text-slate-300">•</span>
                                            <span class="text-slate-500">{{ $displayToken->token_date ? $displayToken->token_date->format('d M, Y') : '' }} <span class="text-slate-400 font-mono text-[10px]">{{ format_app_datetime($displayToken->created_at, 'h:i A') }}</span></span>
                                        </div>
                                        <div class="flex items-center space-x-1.5">
                                            @if($displayToken->status === 'completed')
                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                                    <i class="fa-solid fa-circle-check mr-1 text-[8px]"></i> Examined
                                                </span>
                                            @elseif($displayToken->status === 'in_consultation')
                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold bg-blue-100 text-blue-800">
                                                    <i class="fa-solid fa-stethoscope mr-1 text-[8px]"></i> In Consultation
                                                </span>
                                            @elseif($displayToken->status === 'waiting')
                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                                    <i class="fa-regular fa-clock mr-1 text-[8px]"></i> Waiting
                                                </span>
                                            @elseif($displayToken->status === 'cancelled')
                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold bg-rose-100 text-rose-800">
                                                    Cancelled
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                                    {{ ucfirst($displayToken->status) }}
                                                </span>
                                            @endif

                                            @if($patient->tokens_count > 1)
                                                <span class="text-[10px] text-slate-400 font-semibold" title="Total visits by this patient">
                                                    ({{ $patient->tokens_count }} visits)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center text-slate-400 text-xs italic">
                                        <i class="fa-regular fa-clock mr-1 text-[10px]"></i> No OPD visits yet
                                    </span>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 text-slate-500 text-xs whitespace-nowrap">
                                {{ $patient->formatRegisteredAt('d M, Y h:i A') }}
                            </td>

                            <td class="px-4 py-3.5 text-center whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1.5">
                                    <a href="{{ route('patients.show', $patient->id) }}" class="inline-flex items-center space-x-1.5 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-blue-200 hover:border-blue-600 transition shadow-2xs">
                                        <i class="fa-solid fa-eye text-[11px]"></i>
                                        <span>View</span>
                                    </a>

                                    <form action="{{ route('patients.destroy', $patient->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Are you sure you want to delete patient \'{{ addslashes($patient->name) }}\' ({{ $patient->patient_number }})? All associated tokens and billing records will also be removed.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center space-x-1.5 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white px-2.5 py-1.5 rounded-lg text-xs font-bold border border-rose-200 hover:border-rose-600 transition shadow-2xs cursor-pointer" title="Delete Patient">
                                            <i class="fa-solid fa-trash-can text-[11px]"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-xl">
                                        <i class="fa-solid fa-hospital-user"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-slate-700">No patients found</p>
                                        <p class="text-xs text-slate-400 mt-0.5">
                                            @if(!empty($search) || $period !== 'all' || !empty($doctorId) || ($status && $status !== 'all'))
                                                No patients match your current filter criteria. Try adjusting or resetting filters.
                                            @else
                                                Start by registering the first patient in the system.
                                            @endif
                                        </p>
                                    </div>
                                    @if(!empty($search) || $period !== 'all' || !empty($doctorId) || ($status && $status !== 'all'))
                                        <a href="{{ route('patients.index') }}" class="bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition">
                                            <i class="fa-solid fa-rotate-left mr-1"></i> Reset Filters
                                        </a>
                                    @else
                                        <a href="{{ route('patients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold shadow-sm transition">
                                            + Register Patient
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($patients->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                {{ $patients->links() }}
            </div>
        @endif
    </div>

</div>

<script>
function handlePeriodChange(val) {
    const row = document.getElementById('customDateRow');
    if (val === 'custom') {
        row.classList.remove('hidden');
        row.classList.add('grid');
    } else {
        row.classList.add('hidden');
        row.classList.remove('grid');
    }
}
</script>
@endsection
