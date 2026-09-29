@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    <!-- Header Banner -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xl border border-blue-100">
                <i class="fa-solid fa-ticket-simple"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Token Generator</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                    Reception OPD Token Generator &bull; 
                    <span class="font-semibold text-slate-700">{{ \Carbon\Carbon::parse($today)->format('d-M-Y') }}</span>
                </p>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('patient-tokens.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Token Queue</span>
            </a>
        </div>
    </div>

    <!-- Flash Alerts -->
    @if(session('warning'))
        <div class="bg-amber-50 border border-amber-200 text-amber-900 p-4 rounded-2xl text-sm font-medium flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-center space-x-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg shrink-0"></i>
                <div>
                    <span>{{ session('warning') }}</span>
                    @if(session('likely_duplicate_id'))
                        <div class="text-xs text-amber-700 mt-1">
                            Would you like to issue a token for the existing patient instead?
                        </div>
                    @endif
                </div>
            </div>
            <div class="flex items-center space-x-2 shrink-0">
                @if(session('existing_token_id'))
                    <a href="{{ route('patient-tokens.show', session('existing_token_id')) }}" class="bg-amber-600 hover:bg-amber-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-eye text-xs"></i>
                        <span>View Existing Token</span>
                    </a>
                @endif
                @if(session('likely_duplicate_id'))
                    <a href="{{ route('patient-tokens.create', ['patient_id' => session('likely_duplicate_id')]) }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3.5 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5">
                        <i class="fa-solid fa-check text-xs"></i>
                        <span>Select Existing Patient</span>
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-sm shadow-xs">
            <div class="flex items-center space-x-2 font-bold mb-1.5">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-1 text-xs text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Token Form -->
    <form id="tokenForm" action="{{ route('patient-tokens.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- PART 2 & PART 3: SELECT DOCTOR & CURRENT DATE -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/80">
            <div class="border-b border-slate-100 pb-4 mb-5 flex justify-between items-center">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                    <i class="fa-solid fa-user-doctor text-blue-600"></i>
                    <span>1. Select Doctor & Date</span>
                </h2>
                <span class="text-xs text-slate-400 font-semibold">Active OPD Doctors Only</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Doctor Selection Dropdown -->
                <div>
                    <label for="doctor_id" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Select Doctor <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        id="doctor_id" 
                        name="doctor_id" 
                        required 
                        class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:outline-none focus:bg-white focus:border-blue-500 transition cursor-pointer"
                        onchange="handleDoctorChange()"
                    >
                        <option value="">-- Choose Active Doctor --</option>
                        @foreach($doctors as $doc)
                            <option 
                                value="{{ $doc->id }}"
                                data-name="{{ $doc->name }}"
                                data-specialization="{{ $doc->specialization }}"
                                data-fee="{{ (float) $doc->consultation_fee }}"
                                {{ (old('doctor_id', $selectedDoctor?->id) == $doc->id) ? 'selected' : '' }}
                            >
                                {{ $doc->name }} — {{ $doc->specialization }} (PKR {{ number_format($doc->consultation_fee, 0) }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Doctor Quick Info Display -->
                    <div id="doctorInfoCard" class="mt-3 p-4 bg-blue-50/70 border border-blue-100 rounded-xl text-xs space-y-1.5 transition">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Doctor Name:</span>
                            <span id="docDispName" class="font-bold text-slate-800 text-sm">Please select doctor</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">Specialization:</span>
                            <span id="docDispSpec" class="font-semibold text-blue-700">—</span>
                        </div>
                        <div class="flex justify-between items-center pt-1.5 border-t border-blue-200/50">
                            <span class="text-slate-600 font-medium">Consultation Fee:</span>
                            <span id="docDispFee" class="font-mono font-black text-blue-900 text-sm">PKR 0</span>
                        </div>
                    </div>
                </div>

                <!-- Current Date & Reception Metadata -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Current Date
                        </label>
                        <div class="p-3 bg-slate-100/80 border border-slate-200 rounded-xl text-sm font-mono font-bold text-slate-800 flex items-center justify-between">
                            <span class="flex items-center space-x-2">
                                <i class="fa-regular fa-calendar-check text-blue-600 text-base"></i>
                                <span>{{ \Carbon\Carbon::parse($today)->format('d-M-Y') }}</span>
                            </span>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-100 text-blue-800">Today</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">Tokens are automatically generated for today's OPD date.</p>
                    </div>

                    <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/70 flex items-center space-x-3 text-xs text-slate-600">
                        <i class="fa-solid fa-shield-halved text-blue-500 text-lg"></i>
                        <span>Doctor consultation fee is fixed by hospital records and cannot be arbitrarily modified.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- PART 4, 5, 6, 7: PATIENT MODE & PATIENT DETAILS -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/80 space-y-6">
            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
                <div>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                        <i class="fa-solid fa-hospital-user text-blue-600"></i>
                        <span>2. Patient Information</span>
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Select existing registered patient or register a new patient</p>
                </div>

                <!-- Patient Mode Tabs (Part 4) -->
                <div class="inline-flex p-1 bg-slate-100 rounded-xl border border-slate-200" role="group">
                    <button 
                        type="button" 
                        id="btnModeExisting"
                        onclick="setPatientMode('existing')"
                        class="px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 bg-white text-blue-700 shadow-xs"
                    >
                        <i class="fa-solid fa-user-check text-[11px]"></i>
                        <span>Existing Patient</span>
                    </button>
                    <button 
                        type="button" 
                        id="btnModeNew"
                        onclick="setPatientMode('new')"
                        class="px-4 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center space-x-1.5"
                    >
                        <i class="fa-solid fa-user-plus text-[11px]"></i>
                        <span>New Patient</span>
                    </button>
                </div>
            </div>

            <!-- Hidden input for patient mode -->
            <input type="hidden" id="patient_mode" name="patient_mode" value="{{ old('patient_mode', $selectedPatient ? 'existing' : 'existing') }}">
            <input type="hidden" id="confirm_duplicate" name="confirm_duplicate" value="0">

            <!-- ========================================== -->
            <!-- MODE A: EXISTING PATIENT SEARCH & AUTO-FILL -->
            <!-- ========================================== -->
            <div id="sectionExistingPatient" class="space-y-5">
                <!-- Search Box -->
                <div class="relative">
                    <label for="patientSearchInput" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Search Patient <span class="text-xs font-normal text-slate-400 normal-case">(Type Patient Number, Name, Phone, or CNIC)</span>
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </span>
                        <input 
                            type="text" 
                            id="patientSearchInput" 
                            placeholder="Search by name, PT-00001, 0300-..., or CNIC..." 
                            class="w-full pl-10 pr-10 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition"
                            autocomplete="off"
                            oninput="debouncedPatientSearch(this.value)"
                        >
                        <div id="searchSpinner" class="absolute inset-y-0 right-0 flex items-center pr-3.5 hidden">
                            <i class="fa-solid fa-circle-notch fa-spin text-blue-600 text-sm"></i>
                        </div>
                    </div>

                    <!-- Search Autocomplete Dropdown List -->
                    <div id="patientSearchResults" class="absolute z-30 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-64 overflow-y-auto hidden">
                        <!-- Populated via JS -->
                    </div>
                </div>

                <!-- Hidden input for existing patient ID -->
                <input type="hidden" id="patient_id" name="patient_id" value="{{ old('patient_id', $selectedPatient?->id) }}">

                <!-- Selected Patient Read-Only Demographic Card -->
                <div id="selectedPatientCard" class="p-5 bg-slate-50 rounded-2xl border border-slate-200/90 space-y-4 {{ $selectedPatient ? '' : 'hidden' }}">
                    <div class="flex justify-between items-center pb-3 border-b border-slate-200/80">
                        <div class="flex items-center space-x-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-emerald-800">Selected Patient Details</span>
                        </div>
                        <button type="button" onclick="clearSelectedPatient()" class="text-xs text-rose-600 hover:text-rose-800 font-semibold flex items-center space-x-1">
                            <i class="fa-solid fa-xmark"></i>
                            <span>Change Patient</span>
                        </button>
                    </div>

                    <!-- Auto-filled Read-Only Fields Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Patient Number</span>
                            <span id="dispPatientNumber" class="font-mono font-black text-blue-700 text-sm">{{ $selectedPatient?->patient_number ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Patient Name</span>
                            <span id="dispPatientName" class="font-bold text-slate-800 text-sm">{{ $selectedPatient?->name ?? '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Father / Husband</span>
                            <span id="dispPatientFather" class="font-medium text-slate-700 text-sm">{{ $selectedPatient?->father_husband_name ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Age & Gender</span>
                            <span id="dispPatientAgeGender" class="font-semibold text-slate-800 text-sm">
                                {{ $selectedPatient ? $selectedPatient->age . ' Yrs / ' . $selectedPatient->gender : '—' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Phone Number</span>
                            <span id="dispPatientPhone" class="font-medium text-slate-700">{{ $selectedPatient?->phone ?: '—' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">CNIC Number</span>
                            <span id="dispPatientCnic" class="font-mono font-medium text-slate-700">{{ $selectedPatient?->cnic ?: '—' }}</span>
                        </div>
                        <div class="sm:col-span-2">
                            <span class="text-slate-400 uppercase font-semibold block text-[10px]">Address</span>
                            <span id="dispPatientAddress" class="font-medium text-slate-700">{{ $selectedPatient?->address ?: '—' }}</span>
                        </div>
                    </div>

                    <!-- Active Token Notice if selected patient already has one -->
                    <div id="patientActiveTokenWarning" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-semibold flex items-center justify-between hidden">
                        <span id="patientActiveTokenMsg">This patient already has an active token today.</span>
                        <a id="patientActiveTokenLink" href="#" class="bg-amber-600 hover:bg-amber-700 text-white px-2.5 py-1 rounded-lg text-xs font-bold transition">
                            View Token
                        </a>
                    </div>
                </div>

                <div id="noPatientPrompt" class="p-4 bg-slate-50 rounded-xl border border-dashed border-slate-200 text-center text-xs text-slate-500 {{ $selectedPatient ? 'hidden' : '' }}">
                    Search and pick a registered patient from the search box above, or switch to <strong>New Patient</strong> mode to register.
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODE B: NEW PATIENT MANUAL REGISTRATION   -->
            <!-- ========================================== -->
            <div id="sectionNewPatient" class="space-y-5 hidden">
                <div class="p-3.5 bg-blue-50/70 border border-blue-100 rounded-xl text-xs text-blue-800 flex items-center space-x-2">
                    <i class="fa-solid fa-circle-info text-blue-600 text-sm"></i>
                    <span>Patient Number will be automatically generated (e.g. <strong>PT-XXXXX</strong>). No manual numbering required.</span>
                </div>

                <!-- Duplicate Live Warning Banner -->
                <div id="duplicateWarningCard" class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-xl text-xs space-y-2 hidden">
                    <div class="flex items-center space-x-2 font-bold">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm"></i>
                        <span>Patient may already be registered!</span>
                    </div>
                    <p id="duplicateWarningText" class="text-amber-800">
                        A patient with this CNIC or Phone number was found in the database.
                    </p>
                    <div class="flex items-center space-x-3 pt-1">
                        <button type="button" id="btnUseExistingDuplicate" class="bg-emerald-600 hover:bg-emerald-700 text-white px-3 py-1.5 rounded-lg font-bold text-xs transition">
                            Select Existing Patient Instead
                        </button>
                        <button type="button" onclick="dismissDuplicateWarning()" class="text-amber-700 hover:underline text-xs">
                            Continue as New Patient
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                    <!-- Patient Name -->
                    <div class="sm:col-span-2">
                        <label for="patient_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Patient Name <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="patient_name" 
                            name="patient_name" 
                            value="{{ old('patient_name') }}"
                            placeholder="Full name of patient"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                    </div>

                    <!-- Father / Husband Name -->
                    <div>
                        <label for="father_husband_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Father / Husband Name
                        </label>
                        <input 
                            type="text" 
                            id="father_husband_name" 
                            name="father_husband_name" 
                            value="{{ old('father_husband_name') }}"
                            placeholder="S/D/W of"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                    </div>

                    <!-- Age -->
                    <div>
                        <label for="age" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Age (Years) <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="age" 
                            name="age" 
                            value="{{ old('age') }}"
                            min="0" 
                            max="150"
                            placeholder="e.g. 35"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                    </div>

                    <!-- Gender -->
                    <div>
                        <label for="gender" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Gender <span class="text-rose-500">*</span>
                        </label>
                        <select 
                            id="gender" 
                            name="gender" 
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                            <option value="">-- Select Gender --</option>
                            <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Phone Number
                        </label>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            value="{{ old('phone') }}"
                            placeholder="0300-1234567"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                            onblur="checkPatientDuplicate()"
                        >
                    </div>

                    <!-- CNIC -->
                    <div>
                        <label for="cnic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            CNIC Number
                        </label>
                        <input 
                            type="text" 
                            id="cnic" 
                            name="cnic" 
                            value="{{ old('cnic') }}"
                            placeholder="35201-xxxxxxx-x"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition font-mono"
                            onblur="checkPatientDuplicate()"
                        >
                    </div>

                    <!-- Address -->
                    <div class="sm:col-span-2">
                        <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1.5">
                            Address
                        </label>
                        <input 
                            type="text" 
                            id="address" 
                            name="address" 
                            value="{{ old('address') }}"
                            placeholder="Residential area, street, city..."
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
                        >
                    </div>
                </div>
            </div>
        </div>

        <!-- PART 8, 9, 10, 11: PAYMENT TYPE & CHARGED AMOUNT -->
        <div class="bg-white p-6 sm:p-7 rounded-2xl shadow-xs border border-slate-200/80 space-y-6">
            <div class="border-b border-slate-100 pb-4 flex justify-between items-center">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 flex items-center space-x-2">
                    <i class="fa-solid fa-coins text-blue-600"></i>
                    <span>3. Payment Type & Consultation Fee</span>
                </h2>
                <span class="text-xs text-slate-400 font-semibold">Hospital Billing</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Payment Mode Switcher (Paid vs Free) -->
                <div class="space-y-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                        Payment Type <span class="text-rose-500">*</span>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <!-- Paid Option -->
                        <label id="labelPaid" class="cursor-pointer p-4 rounded-xl border-2 border-blue-600 bg-blue-50/50 flex items-center space-x-3 transition">
                            <input 
                                type="radio" 
                                name="payment_type" 
                                value="paid" 
                                {{ old('payment_type', 'paid') === 'paid' ? 'checked' : '' }}
                                class="text-blue-600 focus:ring-blue-500 h-4 w-4"
                                onchange="handlePaymentChange('paid')"
                            >
                            <div>
                                <span class="font-bold text-sm text-slate-900 block">Paid Consultation</span>
                                <span class="text-xs text-slate-500">Normal Doctor Fee</span>
                            </div>
                        </label>

                        <!-- Free Option -->
                        <label id="labelFree" class="cursor-pointer p-4 rounded-xl border-2 border-slate-200 bg-white hover:bg-slate-50 flex items-center space-x-3 transition">
                            <input 
                                type="radio" 
                                name="payment_type" 
                                value="free" 
                                {{ old('payment_type') === 'free' ? 'checked' : '' }}
                                class="text-emerald-600 focus:ring-emerald-500 h-4 w-4"
                                onchange="handlePaymentChange('free')"
                            >
                            <div>
                                <span class="font-bold text-sm text-slate-900 block">Free Consultation</span>
                                <span class="text-xs text-emerald-600 font-semibold">100% Fee Waiver (PKR 0)</span>
                            </div>
                        </label>
                    </div>

                    <!-- Free Consultation Reason Dropdown (Part 11) -->
                    <div id="sectionFreeReason" class="p-4 bg-amber-50/60 border border-amber-200/80 rounded-xl space-y-3 {{ old('payment_type') === 'free' ? '' : 'hidden' }}">
                        <div>
                            <label for="free_reason" class="block text-xs font-bold uppercase tracking-wider text-amber-900 mb-1.5">
                                Free Reason <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                id="free_reason" 
                                name="free_reason" 
                                class="w-full p-2.5 bg-white border border-amber-300 rounded-xl text-sm focus:outline-none focus:border-amber-500 text-slate-800 transition"
                                onchange="handleFreeReasonChange(this.value)"
                            >
                                <option value="">-- Select Reason --</option>
                                <option value="General Free" {{ old('free_reason') === 'General Free' ? 'selected' : '' }}>General Free</option>
                                <option value="Poor Patient" {{ old('free_reason') === 'Poor Patient' ? 'selected' : '' }}>Poor Patient</option>
                                <option value="Staff" {{ old('free_reason') === 'Staff' ? 'selected' : '' }}>Staff</option>
                                <option value="Referral" {{ old('free_reason') === 'Referral' ? 'selected' : '' }}>Referral</option>
                                <option value="Other" {{ old('free_reason') === 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <!-- Other Reason text input -->
                        <div id="sectionOtherReason" class="{{ old('free_reason') === 'Other' ? '' : 'hidden' }}">
                            <label for="other_reason" class="block text-xs font-bold uppercase tracking-wider text-amber-900 mb-1.5">
                                Specify Other Reason <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="other_reason" 
                                name="other_reason" 
                                value="{{ old('other_reason') }}"
                                placeholder="Enter specific free reason details..."
                                class="w-full p-2.5 bg-white border border-amber-300 rounded-xl text-sm focus:outline-none focus:border-amber-500 text-slate-800 transition"
                            >
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation Display Box (Parts 10 & 11) -->
                <div class="bg-slate-50 p-5 rounded-2xl border border-slate-200/90 flex flex-col justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Billing Breakdown</p>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">Consultation Fee:</span>
                                <span id="billConsultationFee" class="font-mono font-bold text-slate-800">PKR 0</span>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-slate-600">Payment Status:</span>
                                <span id="billPaymentStatus" class="font-semibold text-blue-700 bg-blue-100 px-2 py-0.5 rounded text-xs">
                                    PAID
                                </span>
                            </div>

                            <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                                <div>
                                    <span class="text-sm font-bold text-slate-900 block">Charged Amount:</span>
                                    <span class="text-[11px] text-slate-400">Total payable by patient</span>
                                </div>
                                <span id="billChargedAmount" class="font-mono font-black text-2xl text-blue-900">
                                    PKR 0
                                </span>
                            </div>
                        </div>
                    </div>

                    <p class="text-[11px] text-slate-400 mt-4 pt-3 border-t border-slate-200/60 flex items-center space-x-1.5">
                        <i class="fa-solid fa-lock text-slate-400 text-[10px]"></i>
                        <span>Doctor's base consultation fee remains unchanged in doctor records.</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- OPTIONAL NOTES -->
        <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80">
            <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                Visit Notes / Chief Complaint (Optional)
            </label>
            <input 
                type="text" 
                id="notes" 
                name="notes" 
                value="{{ old('notes') }}"
                placeholder="e.g. Follow-up, Fever & Cough, Routine Checkup..." 
                class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition"
            >
        </div>

        <!-- GENERATE TOKEN ACTION BAR -->
        <div class="bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-center gap-4">
            <a href="{{ route('patient-tokens.index') }}" class="w-full sm:w-auto text-center px-6 py-3 rounded-xl font-bold text-sm text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 transition">
                Cancel
            </a>

            <button 
                type="submit" 
                id="btnGenerateToken"
                class="w-full sm:w-auto bg-blue-600 hover:bg-blue-700 text-white px-8 py-3.5 rounded-xl font-black text-base shadow-md hover:shadow-lg transition flex items-center justify-center space-x-2"
            >
                <i class="fa-solid fa-ticket-simple text-lg"></i>
                <span>Generate OPD Token</span>
            </button>
        </div>
    </form>

</div>

<!-- Vanilla JS for Interactive Reception Counter Logic -->
<script>
    let currentDoctorFee = 0;
    let searchDebounceTimer = null;

    document.addEventListener('DOMContentLoaded', function () {
        handleDoctorChange();
        
        // Initial payment state
        const initialPayment = document.querySelector('input[name="payment_type"]:checked')?.value || 'paid';
        handlePaymentChange(initialPayment);

        // Initial patient mode
        const initialMode = document.getElementById('patient_mode').value || 'existing';
        setPatientMode(initialMode);
    });

    // 1. DOCTOR SELECTION LOGIC (Part 2)
    function handleDoctorChange() {
        const doctorSelect = document.getElementById('doctor_id');
        const selectedOption = doctorSelect.options[doctorSelect.selectedIndex];

        if (selectedOption && selectedOption.value) {
            const name = selectedOption.getAttribute('data-name');
            const spec = selectedOption.getAttribute('data-specialization');
            const fee = parseFloat(selectedOption.getAttribute('data-fee')) || 0;

            currentDoctorFee = fee;
            document.getElementById('docDispName').textContent = name;
            document.getElementById('docDispSpec').textContent = spec;
            document.getElementById('docDispFee').textContent = 'PKR ' + fee.toLocaleString('en-US', { minimumFractionDigits: 0 });
        } else {
            currentDoctorFee = 0;
            document.getElementById('docDispName').textContent = 'Please select doctor';
            document.getElementById('docDispSpec').textContent = '—';
            document.getElementById('docDispFee').textContent = 'PKR 0';
        }

        updateBillingBreakdown();
    }

    // 2. PATIENT MODE SWITCHER (Part 4)
    function setPatientMode(mode) {
        document.getElementById('patient_mode').value = mode;

        const btnExisting = document.getElementById('btnModeExisting');
        const btnNew = document.getElementById('btnModeNew');
        const secExisting = document.getElementById('sectionExistingPatient');
        const secNew = document.getElementById('sectionNewPatient');

        if (mode === 'existing') {
            btnExisting.className = 'px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 bg-white text-blue-700 shadow-xs';
            btnNew.className = 'px-4 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center space-x-1.5';
            secExisting.classList.remove('hidden');
            secNew.classList.add('hidden');
        } else {
            btnNew.className = 'px-4 py-1.5 rounded-lg text-xs font-bold transition flex items-center space-x-1.5 bg-white text-blue-700 shadow-xs';
            btnExisting.className = 'px-4 py-1.5 rounded-lg text-xs font-semibold text-slate-600 hover:text-slate-900 transition flex items-center space-x-1.5';
            secNew.classList.remove('hidden');
            secExisting.classList.add('hidden');
        }
    }

    // 3. EXISTING PATIENT SERVER-SIDE SEARCH (Part 5)
    function debouncedPatientSearch(query) {
        clearTimeout(searchDebounceTimer);
        const resultsBox = document.getElementById('patientSearchResults');
        const spinner = document.getElementById('searchSpinner');

        if (!query || query.trim().length < 1) {
            resultsBox.classList.add('hidden');
            resultsBox.innerHTML = '';
            return;
        }

        spinner.classList.remove('hidden');

        searchDebounceTimer = setTimeout(() => {
            fetch(`/patients/search?q=${encodeURIComponent(query.trim())}`)
                .then(res => res.json())
                .then(data => {
                    spinner.classList.add('hidden');
                    renderSearchResults(data);
                })
                .catch(err => {
                    spinner.classList.add('hidden');
                    console.error('Search error:', err);
                });
        }, 250);
    }

    function renderSearchResults(patients) {
        const resultsBox = document.getElementById('patientSearchResults');
        resultsBox.innerHTML = '';

        if (!patients || patients.length === 0) {
            resultsBox.innerHTML = `
                <div class="p-4 text-center text-xs text-slate-500">
                    <p class="font-semibold">No registered patients found.</p>
                    <p class="mt-0.5 text-slate-400">Switch to "New Patient" mode to register manually.</p>
                </div>
            `;
            resultsBox.classList.remove('hidden');
            return;
        }

        patients.forEach(p => {
            const item = document.createElement('div');
            item.className = 'p-3 hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer flex justify-between items-center transition';
            
            const activeBadge = p.has_active_token ? `
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-900 border border-amber-200">
                    Active Token #${p.active_token_number} (${p.active_token_status})
                </span>
            ` : '';

            item.innerHTML = `
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-xs font-bold text-blue-700 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-200">${p.patient_number}</span>
                        <strong class="text-sm text-slate-800">${escapeHtml(p.name)}</strong>
                        <span class="text-xs text-slate-500">(${p.age} Yrs, ${p.gender})</span>
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5 space-x-3">
                        ${p.phone ? `<span><i class="fa-solid fa-phone mr-1"></i>${escapeHtml(p.phone)}</span>` : ''}
                        ${p.cnic ? `<span><i class="fa-regular fa-id-card mr-1"></i>${escapeHtml(p.cnic)}</span>` : ''}
                    </div>
                </div>
                <div>${activeBadge}</div>
            `;

            item.onclick = function () {
                selectExistingPatient(p);
                resultsBox.classList.add('hidden');
            };

            resultsBox.appendChild(item);
        });

        resultsBox.classList.remove('hidden');
    }

    function selectExistingPatient(patient) {
        document.getElementById('patient_id').value = patient.id;
        document.getElementById('dispPatientNumber').textContent = patient.patient_number;
        document.getElementById('dispPatientName').textContent = patient.name;
        document.getElementById('dispPatientFather').textContent = patient.father_husband_name || '—';
        document.getElementById('dispPatientAgeGender').textContent = `${patient.age} Yrs / ${patient.gender}`;
        document.getElementById('dispPatientPhone').textContent = patient.phone || '—';
        document.getElementById('dispPatientCnic').textContent = patient.cnic || '—';
        document.getElementById('dispPatientAddress').textContent = patient.address || '—';

        // Check active token
        const activeWarning = document.getElementById('patientActiveTokenWarning');
        const activeLink = document.getElementById('patientActiveTokenLink');
        const activeMsg = document.getElementById('patientActiveTokenMsg');

        if (patient.has_active_token) {
            activeMsg.textContent = `This patient already has an active token today (Token #${patient.active_token_number} - ${patient.active_token_status}).`;
            activeLink.href = `/patient-tokens/${patient.active_token_id}`;
            activeWarning.classList.remove('hidden');
        } else {
            activeWarning.classList.add('hidden');
        }

        document.getElementById('selectedPatientCard').classList.remove('hidden');
        document.getElementById('noPatientPrompt').classList.add('hidden');
        document.getElementById('patientSearchInput').value = '';
    }

    function clearSelectedPatient() {
        document.getElementById('patient_id').value = '';
        document.getElementById('selectedPatientCard').classList.add('hidden');
        document.getElementById('noPatientPrompt').classList.remove('hidden');
        document.getElementById('patientSearchInput').focus();
    }

    // 4. DUPLICATE CHECK FOR NEW PATIENT (Part 6)
    function checkPatientDuplicate() {
        const cnic = document.getElementById('cnic').value.trim();
        const phone = document.getElementById('phone').value.trim();

        if (!cnic && !phone) return;

        fetch(`/patients/check-duplicate?cnic=${encodeURIComponent(cnic)}&phone=${encodeURIComponent(phone)}`)
            .then(res => res.json())
            .then(data => {
                const warnCard = document.getElementById('duplicateWarningCard');
                const warnText = document.getElementById('duplicateWarningText');
                const btnUse = document.getElementById('btnUseExistingDuplicate');

                if (data.found && data.patient) {
                    warnText.innerHTML = `Found matching patient: <strong>${escapeHtml(data.patient.name)}</strong> (ID: <code>${data.patient.patient_number}</code>, Phone: ${data.patient.phone || 'N/A'}, CNIC: ${data.patient.cnic || 'N/A'}).`;
                    btnUse.onclick = function () {
                        setPatientMode('existing');
                        selectExistingPatient(data.patient);
                        warnCard.classList.add('hidden');
                    };
                    warnCard.classList.remove('hidden');
                } else {
                    warnCard.classList.add('hidden');
                }
            })
            .catch(err => console.error('Duplicate check error:', err));
    }

    function dismissDuplicateWarning() {
        document.getElementById('duplicateWarningCard').classList.add('hidden');
        document.getElementById('confirm_duplicate').value = '1';
    }

    // 5. PAYMENT TYPE & BILLING CALCULATION (Parts 9, 10, 11)
    function handlePaymentChange(type) {
        const labelPaid = document.getElementById('labelPaid');
        const labelFree = document.getElementById('labelFree');
        const freeSec = document.getElementById('sectionFreeReason');

        if (type === 'paid') {
            labelPaid.className = 'cursor-pointer p-4 rounded-xl border-2 border-blue-600 bg-blue-50/50 flex items-center space-x-3 transition';
            labelFree.className = 'cursor-pointer p-4 rounded-xl border-2 border-slate-200 bg-white hover:bg-slate-50 flex items-center space-x-3 transition';
            freeSec.classList.add('hidden');
        } else {
            labelFree.className = 'cursor-pointer p-4 rounded-xl border-2 border-emerald-600 bg-emerald-50/50 flex items-center space-x-3 transition';
            labelPaid.className = 'cursor-pointer p-4 rounded-xl border-2 border-slate-200 bg-white hover:bg-slate-50 flex items-center space-x-3 transition';
            freeSec.classList.remove('hidden');
        }

        updateBillingBreakdown();
    }

    function handleFreeReasonChange(reason) {
        const otherSec = document.getElementById('sectionOtherReason');
        if (reason === 'Other') {
            otherSec.classList.remove('hidden');
            document.getElementById('other_reason').required = true;
        } else {
            otherSec.classList.add('hidden');
            document.getElementById('other_reason').required = false;
        }
    }

    function updateBillingBreakdown() {
        const paymentType = document.querySelector('input[name="payment_type"]:checked')?.value || 'paid';
        const feeDisp = document.getElementById('billConsultationFee');
        const statusDisp = document.getElementById('billPaymentStatus');
        const chargedDisp = document.getElementById('billChargedAmount');

        feeDisp.textContent = 'PKR ' + currentDoctorFee.toLocaleString('en-US', { minimumFractionDigits: 0 });

        if (paymentType === 'free') {
            statusDisp.textContent = 'FREE / WAIVED';
            statusDisp.className = 'font-semibold text-emerald-800 bg-emerald-100 px-2.5 py-0.5 rounded text-xs';
            chargedDisp.textContent = 'PKR 0';
            chargedDisp.className = 'font-mono font-black text-2xl text-emerald-700';
        } else {
            statusDisp.textContent = 'PAID';
            statusDisp.className = 'font-semibold text-blue-700 bg-blue-100 px-2.5 py-0.5 rounded text-xs';
            chargedDisp.textContent = 'PKR ' + currentDoctorFee.toLocaleString('en-US', { minimumFractionDigits: 0 });
            chargedDisp.className = 'font-mono font-black text-2xl text-blue-900';
        }
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // Close autocomplete on click outside
    document.addEventListener('click', function (e) {
        const resultsBox = document.getElementById('patientSearchResults');
        const searchInput = document.getElementById('patientSearchInput');
        if (resultsBox && !resultsBox.contains(e.target) && e.target !== searchInput) {
            resultsBox.classList.add('hidden');
        }
    });
</script>
@endsection
