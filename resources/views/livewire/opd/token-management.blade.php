<div class="p-4 sm:p-6 lg:p-8 space-y-6 max-w-7xl mx-auto">

    <!-- CSS FOR PRINTING TOKEN ONLY -->
    <style>
        @media print {
            /* Hide the entire application layout, sidebar, headers, tables, etc. */
            body * {
                visibility: hidden !important;
            }
            /* Show only the printable token card container */
            #printable-token-area, #printable-token-area * {
                visibility: visible !important;
            }
            #printable-token-area {
                position: fixed !important;
                left: 50% !important;
                top: 20px !important;
                transform: translateX(-50%) !important;
                width: 320px !important;
                margin: 0 !important;
                padding: 24px !important;
                background: #ffffff !important;
                color: #000000 !important;
                border: 2px dashed #1e293b !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                display: block !important;
                z-index: 99999 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    <!-- HEADER & TOP ACTIONS -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20">
                    <i class="fa-solid fa-ticket text-lg"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">OPD Token Management</h1>
                    <p class="text-sm text-slate-500">OPD queue, patient registration & token generation</p>
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200 shadow-xs">
                <i class="fa-regular fa-calendar-check mr-2 text-blue-600"></i>
                {{ now()->format('d M Y') }}
            </span>

            <button 
                wire:click="startNewToken" 
                type="button"
                class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-all focus:outline-hidden focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <i class="fa-solid fa-plus mr-2 text-xs"></i>
                New Token
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session()->has('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 flex items-start space-x-3 text-emerald-900 shadow-xs animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-600 text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-sm">{{ session('success') }}</p>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark text-sm"></i>
            </button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 flex items-start space-x-3 text-rose-900 shadow-xs">
            <i class="fa-solid fa-circle-exclamation text-rose-600 text-lg mt-0.5"></i>
            <div class="flex-1">
                <p class="font-semibold text-sm">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    @if(session()->has('queue_success'))
        <div class="p-3 rounded-lg bg-blue-50 border border-blue-200 flex items-center space-x-2 text-blue-900 text-sm">
            <i class="fa-solid fa-info-circle text-blue-600"></i>
            <span>{{ session('queue_success') }}</span>
        </div>
    @endif

    <!-- MAIN TWO-COLUMN WORKFLOW -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ========================================== -->
        <!-- LEFT COLUMN: TOKEN GENERATION FORM (7 COLS) -->
        <!-- ========================================== -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200 shadow-xs p-5 sm:p-6 space-y-6">

            <!-- STEP 1: SELECT DOCTOR -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-bold text-slate-800">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">1</span>
                        Select Doctor <span class="text-rose-500">*</span>
                    </label>
                    @if($selectedDoctor)
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            Fee: PKR {{ number_format($selectedDoctor->consultation_fee, 0) }}
                        </span>
                    @endif
                </div>

                <div class="relative">
                    <select 
                        wire:model.live="doctor_id" 
                        id="doctor_id"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 shadow-xs transition"
                    >
                        <option value="">-- Choose Doctor --</option>
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}">
                                {{ $doc->name }} {{ $doc->specialization ? '— ' . $doc->specialization : '' }} (Fee: PKR {{ number_format($doc->consultation_fee, 0) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @error('doctor_id')
                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                @enderror

                @if($selectedDoctor)
                    <div class="mt-2.5 p-3 rounded-xl bg-blue-50/70 border border-blue-100 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2">
                            <i class="fa-solid fa-user-doctor text-blue-600 text-sm"></i>
                            <span class="font-bold text-slate-800">{{ $selectedDoctor->name }}</span>
                            @if($selectedDoctor->specialization)
                                <span class="text-slate-500">({{ $selectedDoctor->specialization }})</span>
                            @endif
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500">Standard Consultation:</span>
                            <span class="font-bold text-blue-700 ml-1">PKR {{ number_format($selectedDoctor->consultation_fee, 0) }}</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- STEP 2: SEARCH / SELECT PATIENT -->
            <div class="pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-sm font-bold text-slate-800">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">2</span>
                        Patient Information <span class="text-rose-500">*</span>
                    </label>

                    <!-- Toggle Mode: Existing vs New -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                        <button 
                            type="button" 
                            wire:click="setPatientMode('existing')"
                            class="px-3 py-1.5 rounded-lg transition-all {{ $patient_mode === 'existing' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <i class="fa-solid fa-user-check mr-1.5"></i> Existing Patient
                        </button>
                        <button 
                            type="button" 
                            wire:click="setPatientMode('new')"
                            class="px-3 py-1.5 rounded-lg transition-all {{ $patient_mode === 'new' ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <i class="fa-solid fa-user-plus mr-1.5"></i> Register New Patient
                        </button>
                    </div>
                </div>

                <!-- OPTION A: EXISTING PATIENT SEARCH -->
                @if($patient_mode === 'existing')
                    @if(!$selectedPatient)
                        <!-- Search Input -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </div>
                            <input 
                                type="text" 
                                wire:model.live.debounce.250ms="patient_search" 
                                placeholder="Search by Name, Phone, CNIC or Patient ID (e.g. PT-00001)..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:outline-hidden focus:ring-2 focus:ring-blue-500/20 shadow-xs transition"
                            />
                            @if(!empty($patient_search))
                                <button 
                                    type="button" 
                                    wire:click="$set('patient_search', '')" 
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
                                >
                                    <i class="fa-solid fa-circle-xmark"></i>
                                </button>
                            @endif
                        </div>
                        @error('selected_patient_id')
                            <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                        @enderror

                        <!-- Search Dropdown Results -->
                        @if(!empty($patientSearchResults))
                            <div class="mt-2 bg-white rounded-xl border border-slate-200 shadow-lg divide-y divide-slate-100 max-h-60 overflow-y-auto z-20">
                                @forelse($patientSearchResults as $p)
                                    <div 
                                        wire:click="selectPatient({{ $p->id }})"
                                        class="p-3 hover:bg-blue-50/80 cursor-pointer transition flex items-center justify-between group"
                                    >
                                        <div class="space-y-0.5">
                                            <div class="flex items-center space-x-2">
                                                <span class="font-bold text-slate-900 group-hover:text-blue-700 text-sm">{{ $p->name }}</span>
                                                <span class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                                    {{ $p->patient_number }}
                                                </span>
                                                <span class="text-xs text-slate-500">({{ $p->age }} yrs, {{ $p->gender }})</span>
                                            </div>
                                            <div class="text-xs text-slate-500 flex items-center space-x-3">
                                                @if($p->phone)
                                                    <span><i class="fa-solid fa-phone text-slate-400 mr-1 text-[10px]"></i>{{ $p->phone }}</span>
                                                @endif
                                                @if($p->cnic)
                                                    <span><i class="fa-solid fa-id-card text-slate-400 mr-1 text-[10px]"></i>{{ $p->cnic }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="text-xs font-semibold text-blue-600 group-hover:translate-x-1 transition-transform">
                                            Select <i class="fa-solid fa-chevron-right ml-1 text-[10px]"></i>
                                        </span>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-sm text-slate-500">
                                        No patients found matching "{{ $patient_search }}".
                                        <button 
                                            type="button" 
                                            wire:click="setPatientMode('new')"
                                            class="text-blue-600 font-bold hover:underline ml-1"
                                        >
                                            Register as New Patient
                                        </button>
                                    </div>
                                @endforelse
                            </div>
                        @endif

                    @else
                        <!-- SELECTED PATIENT READONLY AUTO-FILLED CARD -->
                        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-4 relative space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-2.5">
                                    <div class="w-9 h-9 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold text-sm">
                                        <i class="fa-solid fa-user-check"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h4 class="font-bold text-slate-900 text-base">{{ $selectedPatient->name }}</h4>
                                            <span class="px-2 py-0.5 rounded text-xs font-mono font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                                {{ $selectedPatient->patient_number }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500">
                                            Existing Patient • Auto-filled details
                                        </p>
                                    </div>
                                </div>

                                <button 
                                    type="button" 
                                    wire:click="clearSelectedPatient" 
                                    class="inline-flex items-center text-xs font-semibold text-slate-500 hover:text-rose-600 bg-white hover:bg-rose-50 px-2.5 py-1.5 rounded-lg border border-slate-200 transition"
                                >
                                    <i class="fa-solid fa-rotate-left mr-1.5"></i> Change
                                </button>
                            </div>

                            <!-- Readonly Auto-filled Fields Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 pt-2 border-t border-blue-100 text-xs">
                                <div>
                                    <span class="text-slate-500 block">Father/Husband</span>
                                    <span class="font-semibold text-slate-800">{{ $selectedPatient->father_husband_name ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Age & Gender</span>
                                    <span class="font-semibold text-slate-800">{{ $selectedPatient->age }} yrs • {{ $selectedPatient->gender }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">Phone</span>
                                    <span class="font-semibold text-slate-800 font-mono">{{ $selectedPatient->phone ?: '—' }}</span>
                                </div>
                                <div>
                                    <span class="text-slate-500 block">CNIC</span>
                                    <span class="font-semibold text-slate-800 font-mono">{{ $selectedPatient->cnic ?: '—' }}</span>
                                </div>
                                <div class="col-span-2">
                                    <span class="text-slate-500 block">Address</span>
                                    <span class="font-semibold text-slate-800 truncate block">{{ $selectedPatient->address ?: '—' }}</span>
                                </div>
                            </div>

                            <!-- Active Token Warning if already present today -->
                            @if($has_active_token_warning)
                                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 flex items-start space-x-2 text-xs">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600 text-sm mt-0.5"></i>
                                    <div>
                                        <p class="font-bold">Active Token Found Today:</p>
                                        <p>Patient already has Token <strong>#{{ $active_token_number }}</strong> with status <strong>{{ $active_token_status }}</strong>.</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                <!-- OPTION B: REGISTER NEW PATIENT FORM -->
                @else
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                            <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                                <i class="fa-solid fa-user-plus text-blue-600 mr-1.5"></i> New Patient Details
                            </span>
                            <span class="text-[11px] text-slate-500">Will auto-save and continue to token</span>
                        </div>

                        <!-- Duplicate Warning Box -->
                        @if($duplicate_patient_warning)
                            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                                    <span>
                                        Possible match found: <strong>{{ $duplicate_patient_warning['name'] }}</strong> ({{ $duplicate_patient_warning['patient_number'] }})
                                    </span>
                                </div>
                                <button 
                                    type="button" 
                                    wire:click="useDuplicatePatient({{ $duplicate_patient_warning['id'] }})"
                                    class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white rounded font-bold text-[11px] transition shadow-2xs"
                                >
                                    Use This Patient
                                </button>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                            <!-- Patient Name -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Patient Name <span class="text-rose-500">*</span></label>
                                <input 
                                    type="text" 
                                    wire:model="new_name" 
                                    placeholder="Full Name"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                                @error('new_name') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Father / Husband Name -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Father / Husband Name</label>
                                <input 
                                    type="text" 
                                    wire:model="new_father_husband_name" 
                                    placeholder="Father or Husband Name"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                                @error('new_father_husband_name') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Age -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Age (Years) <span class="text-rose-500">*</span></label>
                                <input 
                                    type="number" 
                                    wire:model="new_age" 
                                    placeholder="e.g. 30"
                                    min="0" max="150"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                                @error('new_age') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Gender -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Gender <span class="text-rose-500">*</span></label>
                                <select 
                                    wire:model="new_gender"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                >
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                @error('new_gender') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Phone -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">Phone Number</label>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.400ms="new_phone" 
                                    placeholder="0300-1234567"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-mono"
                                />
                                @error('new_phone') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- CNIC -->
                            <div>
                                <label class="block font-semibold text-slate-700 mb-1">CNIC (Identity No.)</label>
                                <input 
                                    type="text" 
                                    wire:model.live.debounce.400ms="new_cnic" 
                                    placeholder="35201-1234567-1"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-mono"
                                />
                                @error('new_cnic') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>

                            <!-- Address -->
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-slate-700 mb-1">Address</label>
                                <input 
                                    type="text" 
                                    wire:model="new_address" 
                                    placeholder="Street, City, Colony"
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm bg-white text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                                @error('new_address') <span class="text-rose-500 text-[11px] font-medium">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- STEP 3: DOCTOR FEE & FREE PATIENT OPTIONS -->
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <div class="flex items-center justify-between">
                    <label class="block text-sm font-bold text-slate-800">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-xs font-bold mr-2">3</span>
                        Consultation Fee & Type <span class="text-rose-500">*</span>
                    </label>

                    <!-- Fee Type Radio Buttons -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                        <button 
                            type="button" 
                            wire:click="$set('fee_type', 'paid')" 
                            class="px-3.5 py-1.5 rounded-lg transition-all {{ $fee_type === 'paid' ? 'bg-emerald-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <i class="fa-solid fa-money-bill-wave mr-1.5"></i> Paid
                        </button>
                        <button 
                            type="button" 
                            wire:click="$set('fee_type', 'free')" 
                            class="px-3.5 py-1.5 rounded-lg transition-all {{ $fee_type === 'free' ? 'bg-purple-600 text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                        >
                            <i class="fa-solid fa-hand-holding-heart mr-1.5"></i> Free Visit
                        </button>
                    </div>
                </div>

                <!-- Fee Display & Calculation Cards -->
                @if($fee_type === 'paid')
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/70 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-xs text-slate-500 block">Doctor Consultation Fee</span>
                            <span class="font-bold text-slate-800 text-lg">
                                PKR {{ number_format($selectedDoctor ? $selectedDoctor->consultation_fee : 0, 0) }}
                            </span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-slate-500 block">Amount Payable</span>
                            <span class="font-black text-emerald-700 text-xl">
                                PKR {{ number_format($selectedDoctor ? $selectedDoctor->consultation_fee : 0, 0) }}
                            </span>
                        </div>
                    </div>
                @else
                    <!-- Free Visit Options -->
                    <div class="p-4 rounded-xl border border-purple-200 bg-purple-50/40 space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-xs text-purple-700 block">Doctor's Original Fee (Recorded)</span>
                                <span class="font-semibold text-slate-500 line-through text-sm">
                                    PKR {{ number_format($selectedDoctor ? $selectedDoctor->consultation_fee : 0, 0) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-xs text-purple-700 block">Final Fee</span>
                                <span class="font-black text-purple-700 text-xl tracking-wide">
                                    FREE
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1">
                                Reason for Free Visit <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                wire:model.live="free_reason" 
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 shadow-2xs"
                            >
                                <option value="">-- Select Free Reason --</option>
                                <option value="Poor Patient">Poor Patient</option>
                                <option value="Emergency">Emergency</option>
                                <option value="Staff">Staff</option>
                                <option value="Follow-up">Follow-up</option>
                                <option value="Hospital Policy">Hospital Policy</option>
                                <option value="Other">Other</option>
                            </select>
                            @error('free_reason')
                                <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        @if($free_reason === 'Other')
                            <div>
                                <label class="block text-xs font-bold text-slate-800 mb-1">
                                    Specify Other Reason <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    wire:model="other_reason" 
                                    placeholder="Enter reason for complimentary visit..."
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 shadow-2xs"
                                />
                                @error('other_reason')
                                    <p class="text-xs text-rose-500 font-medium mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Clinical Notes (Optional) -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Visit Notes / Symptoms (Optional)</label>
                    <input 
                        type="text" 
                        wire:model="notes" 
                        placeholder="e.g. Fever checkup, follow-up, general consultation"
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                </div>
            </div>

            <!-- STEP 4: GENERATE TOKEN BUTTON -->
            <div class="pt-4 border-t border-slate-100">
                <button 
                    type="button" 
                    wire:click="generateToken" 
                    wire:loading.attr="disabled"
                    @disabled($isSubmitting)
                    class="w-full py-3.5 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-base shadow-md shadow-blue-500/20 hover:shadow-lg transition-all flex items-center justify-center space-x-2 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <span wire:loading.remove wire:target="generateToken">
                        <i class="fa-solid fa-ticket mr-2"></i> Generate Token
                    </span>
                    <span wire:loading wire:target="generateToken" class="inline-flex items-center">
                        <i class="fa-solid fa-circle-notch fa-spin mr-2"></i> Generating Token...
                    </span>
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- RIGHT COLUMN: TOKEN CARD / PREVIEW (5 COLS) -->
        <!-- ========================================== -->
        <div class="lg:col-span-5 space-y-6">

            @if($generatedToken)
                <!-- ========================================== -->
                <!-- GENERATED TOKEN DISPLAY CARD (Section 6)   -->
                <!-- ========================================== -->
                <div class="bg-white rounded-2xl border-2 border-blue-600 shadow-xl overflow-hidden animate-scale-up">
                    <!-- Ticket Header Banner -->
                    <div class="bg-blue-600 text-white px-6 py-4 text-center relative">
                        <span class="text-[11px] font-extrabold uppercase tracking-widest text-blue-200">
                            {{ config('app.name', 'Pharma System') }}
                        </span>
                        <h3 class="text-xl font-black tracking-tight mt-0.5">OPD TOKEN</h3>
                        <div class="text-[10px] text-blue-100 mt-1">Official OPD Consultation Ticket</div>
                    </div>

                    <!-- Ticket Body -->
                    <div class="p-6 space-y-5 bg-white text-center">
                        
                        <!-- Token Number Highlight -->
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 inline-block w-full">
                            <span class="text-xs uppercase tracking-wider text-slate-500 font-bold block">Token No:</span>
                            <span class="text-5xl font-black text-slate-900 tracking-tight font-mono">
                                #{{ $generatedToken->formatted_token_number }}
                            </span>
                        </div>

                        <!-- Details Grid -->
                        <div class="space-y-3 text-left border-y border-dashed border-slate-300 py-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Patient:</span>
                                <span class="font-bold text-slate-900">{{ $generatedToken->patient ? $generatedToken->patient->name : 'Walk-in' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Doctor:</span>
                                <span class="font-bold text-slate-900">{{ $generatedToken->doctor ? $generatedToken->doctor->name : '—' }}</span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Date:</span>
                                <span class="font-semibold text-slate-800">
                                    {{ $generatedToken->created_at ? $generatedToken->created_at->format('d M Y') : now()->format('d M Y') }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Time:</span>
                                <span class="font-semibold text-slate-800 font-mono">
                                    {{ $generatedToken->created_at ? $generatedToken->created_at->format('h:i A') : now()->format('h:i A') }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between pt-1">
                                <span class="text-slate-500 font-medium">Fee:</span>
                                @if(strtolower($generatedToken->payment_type) === 'free' || floatval($generatedToken->charged_amount) == 0)
                                    <span class="font-black text-purple-700 text-base tracking-wide bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                        FREE
                                    </span>
                                @else
                                    <span class="font-black text-emerald-700 text-base">
                                        PKR {{ number_format($generatedToken->charged_amount, 0) }}
                                    </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500 font-medium">Status:</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ strtoupper($generatedToken->status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-3 pt-1 no-print">
                            <button 
                                type="button" 
                                wire:click="printToken({{ $generatedToken->id }})" 
                                class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm shadow-xs transition flex items-center justify-center space-x-2"
                            >
                                <i class="fa-solid fa-print"></i>
                                <span>Print Token</span>
                            </button>

                            <button 
                                type="button" 
                                wire:click="startNewToken" 
                                class="w-full py-2.5 px-4 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-sm border border-blue-200 transition flex items-center justify-center space-x-2"
                            >
                                <i class="fa-solid fa-plus"></i>
                                <span>New Token</span>
                            </button>
                        </div>
                    </div>
                </div>

            @else
                <!-- LIVE PREVIEW BEFORE GENERATING -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-6 space-y-5">
                    <div class="flex items-center space-x-2 text-slate-700 pb-3 border-b border-slate-100">
                        <i class="fa-solid fa-eye text-blue-600"></i>
                        <h3 class="font-bold text-sm">Live Token Preview</h3>
                    </div>

                    <div class="p-6 rounded-xl border border-dashed border-slate-300 bg-slate-50/50 text-center space-y-4">
                        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mx-auto text-xl">
                            <i class="fa-solid fa-ticket"></i>
                        </div>

                        <div>
                            <span class="text-xs uppercase tracking-wider text-slate-400 font-bold block">Next Sequential Token</span>
                            <span class="text-3xl font-black text-slate-800 font-mono">
                                #{{ str_pad((string) ((int) \App\Models\PatientToken::where('token_date', now()->toDateString())->max('token_number') + 1), 3, '0', STR_PAD_LEFT) }}
                            </span>
                        </div>

                        <div class="text-xs space-y-2 text-left pt-3 border-t border-slate-200">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Doctor:</span>
                                <span class="font-bold text-slate-800">{{ $selectedDoctor ? $selectedDoctor->name : 'Not selected' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Patient:</span>
                                <span class="font-bold text-slate-800">
                                    @if($patient_mode === 'existing')
                                        {{ $selectedPatient ? $selectedPatient->name : 'Search & select patient' }}
                                    @else
                                        {{ !empty($new_name) ? $new_name : 'Enter new patient details' }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Fee Type:</span>
                                <span class="font-bold {{ $fee_type === 'free' ? 'text-purple-700' : 'text-slate-800' }}">
                                    {{ ucfirst($fee_type) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Payable Fee:</span>
                                @if($fee_type === 'free')
                                    <span class="font-black text-purple-700">FREE</span>
                                @else
                                    <span class="font-bold text-emerald-700">
                                        PKR {{ number_format($selectedDoctor ? $selectedDoctor->consultation_fee : 0, 0) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-slate-400 text-center">
                        Select doctor and enter patient info, then click <strong class="text-slate-600">Generate Token</strong>.
                    </p>
                </div>
            @endif

            <!-- QUICK QUEUE SUMMARY STATS -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-xs p-5">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Today's Token Stats</h4>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 block text-[11px]">Total</span>
                        <span class="text-base font-bold text-slate-800 font-mono">{{ $totalQueueCount }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50 border border-amber-100">
                        <span class="text-amber-600 block text-[11px] font-semibold">Waiting</span>
                        <span class="text-base font-bold text-amber-800 font-mono">{{ $waitingCount }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-emerald-50 border border-emerald-100">
                        <span class="text-emerald-600 block text-[11px] font-semibold">Completed</span>
                        <span class="text-base font-bold text-emerald-800 font-mono">{{ $completedCount }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- SECTION 7: TODAY'S OPD QUEUE               -->
    <!-- ========================================== -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden space-y-4 p-5 sm:p-6">
        
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100">
            <div>
                <h2 class="text-lg font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-list-ol text-blue-600 text-base"></i>
                    <span>Today's OPD Queue</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200">
                        {{ $totalQueueCount }} Today
                    </span>
                </h2>
                <p class="text-xs text-slate-500">Live patient consultations and token queue</p>
            </div>

            <!-- Queue Search & Filter -->
            <div class="flex flex-wrap items-center gap-2">
                <!-- Search in queue -->
                <div class="relative">
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="queue_search" 
                        placeholder="Search queue..." 
                        class="pl-8 pr-3 py-1.5 rounded-lg border border-slate-300 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-500 focus:outline-hidden"
                    />
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                </div>

                <!-- Status Filter Pills -->
                <select 
                    wire:model.live="queue_filter_status" 
                    class="rounded-lg border border-slate-300 text-xs py-1.5 px-2.5 bg-white text-slate-700 focus:border-blue-500 focus:outline-hidden"
                >
                    <option value="all">All Statuses</option>
                    <option value="waiting">Waiting ({{ $waitingCount }})</option>
                    <option value="in_consultation">In Consultation ({{ $inConsultationCount }})</option>
                    <option value="completed">Completed ({{ $completedCount }})</option>
                    <option value="cancelled">Cancelled ({{ $cancelledCount }})</option>
                </select>
            </div>
        </div>

        <!-- QUEUE TABLE -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Token</th>
                        <th class="py-3 px-3">Patient</th>
                        <th class="py-3 px-3">Doctor</th>
                        <th class="py-3 px-3">Fee</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3">Time</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 text-xs">
                    @forelse($queueTokens as $t)
                        <tr class="hover:bg-slate-50/80 transition {{ $t->status === 'in_consultation' || $t->status === 'called' ? 'bg-blue-50/30' : '' }}">
                            <!-- Token Number -->
                            <td class="py-3 px-3 font-mono font-black text-slate-900 text-sm">
                                #{{ $t->formatted_token_number }}
                            </td>

                            <!-- Patient -->
                            <td class="py-3 px-3">
                                <div class="font-bold text-slate-900">{{ $t->patient ? $t->patient->name : 'Unknown' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    {{ $t->patient ? $t->patient->patient_number : '' }}
                                    @if($t->patient && $t->patient->phone)
                                        • {{ $t->patient->phone }}
                                    @endif
                                </div>
                            </td>

                            <!-- Doctor -->
                            <td class="py-3 px-3">
                                <div class="font-semibold text-slate-800">{{ $t->doctor ? $t->doctor->name : '—' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $t->doctor ? $t->doctor->specialization : '' }}</div>
                            </td>

                            <!-- Fee -->
                            <td class="py-3 px-3 font-semibold">
                                @if(strtolower($t->payment_type) === 'free' || floatval($t->charged_amount) == 0)
                                    <span class="text-purple-700 font-bold">FREE</span>
                                @else
                                    <span class="text-slate-800">PKR {{ number_format($t->charged_amount, 0) }}</span>
                                @endif
                            </td>

                            <!-- Type -->
                            <td class="py-3 px-3">
                                @if(strtolower($t->payment_type) === 'free')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                        Free
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-100 text-blue-800 border border-blue-200">
                                        Paid
                                    </span>
                                @endif
                            </td>

                            <!-- Time -->
                            <td class="py-3 px-3 font-mono text-slate-600">
                                {{ $t->created_at ? $t->created_at->format('h:i A') : '—' }}
                            </td>

                            <!-- Status -->
                            <td class="py-3 px-3">
                                @php
                                    $st = strtolower($t->status);
                                @endphp
                                @if($st === 'waiting')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        <i class="fa-solid fa-clock mr-1 text-[9px]"></i> Waiting
                                    </span>
                                @elseif($st === 'in_consultation' || $st === 'called')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200 animate-pulse">
                                        <i class="fa-solid fa-stethoscope mr-1 text-[9px]"></i> In Consultation
                                    </span>
                                @elseif($st === 'completed')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <i class="fa-solid fa-check mr-1 text-[9px]"></i> Completed
                                    </span>
                                @elseif($st === 'cancelled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <i class="fa-solid fa-ban mr-1 text-[9px]"></i> Cancelled
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <!-- Print Slip Icon Button -->
                                    <button 
                                        type="button" 
                                        wire:click="printToken({{ $t->id }})" 
                                        title="Print Token Slip"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                    </button>

                                    <!-- Status Action: Start -->
                                    @if($st === 'waiting')
                                        <button 
                                            type="button" 
                                            wire:click="startConsultation({{ $t->id }})" 
                                            class="px-2.5 py-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-[11px] shadow-2xs transition"
                                        >
                                            <i class="fa-solid fa-play mr-1 text-[9px]"></i> Start
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="cancelToken({{ $t->id }})" 
                                            wire:confirm="Are you sure you want to cancel Token #{{ $t->formatted_token_number }}?"
                                            class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold text-[11px] transition"
                                        >
                                            Cancel
                                        </button>

                                    <!-- Status Action: Complete -->
                                    @elseif($st === 'in_consultation' || $st === 'called')
                                        <button 
                                            type="button" 
                                            wire:click="completeConsultation({{ $t->id }})" 
                                            class="px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] shadow-2xs transition"
                                        >
                                            <i class="fa-solid fa-check mr-1 text-[9px]"></i> Complete
                                        </button>
                                        <button 
                                            type="button" 
                                            wire:click="cancelToken({{ $t->id }})" 
                                            wire:confirm="Are you sure you want to cancel Token #{{ $t->formatted_token_number }}?"
                                            class="px-2 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold text-[11px] transition"
                                        >
                                            Cancel
                                        </button>
                                    @else
                                        <span class="text-[11px] text-slate-400 italic px-2 py-1">—</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400 text-xs">
                                <i class="fa-regular fa-folder-open text-2xl block mb-2 text-slate-300"></i>
                                No tokens recorded for today's queue yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($queueTokens->hasPages())
            <div class="pt-3 border-t border-slate-100">
                {{ $queueTokens->links() }}
            </div>
        @endif
    </div>

    <!-- ========================================== -->
    <!-- SECTION 8: HIDDEN CLEAN PRINTABLE TOKEN    -->
    <!-- Visible ONLY during browser print          -->
    <!-- ========================================== -->
    @if($printToken)
        <div id="printable-token-area" style="display: none;">
            <div style="text-align: center; margin-bottom: 12px; border-bottom: 1px solid #000; padding-bottom: 8px;">
                @if(account_profile()->hasLogo())
                    <div style="margin-bottom: 6px;">
                        <img src="{{ account_profile()->logo_url }}" alt="Logo" style="max-height: 48px; max-width: 140px; margin: 0 auto; display: block; object-fit: contain;">
                    </div>
                @endif
                <div style="font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; line-height: 1.2;">
                    {{ account_profile()->account_name }}
                </div>
                @if(account_profile()->formatted_address)
                    <div style="font-size: 10px; color: #333; margin-top: 2px;">
                        {{ account_profile()->formatted_address }}
                    </div>
                @endif
                @if(account_profile()->phone)
                    <div style="font-size: 10px; color: #333;">
                        Phone: {{ account_profile()->phone }}
                    </div>
                @endif
                <div style="font-size: 16px; font-weight: 900; margin-top: 6px; letter-spacing: 1px;">
                    OPD TOKEN
                </div>
            </div>

            <div style="text-align: center; margin: 14px 0; padding: 10px; border: 2px solid #000; border-radius: 6px;">
                <div style="font-size: 12px; font-weight: bold; text-transform: uppercase;">Token No:</div>
                <div style="font-size: 36px; font-weight: 900; letter-spacing: -1px; font-family: monospace;">
                    #{{ $printToken->formatted_token_number }}
                </div>
            </div>

            <table style="width: 100%; font-size: 12px; line-height: 1.6; margin-bottom: 14px;">
                <tr>
                    <td style="font-weight: bold; width: 35%;">Patient:</td>
                    <td>{{ $printToken->patient ? $printToken->patient->name : 'Walk-in' }}</td>
                </tr>
                @if($printToken->patient && $printToken->patient->patient_number)
                <tr>
                    <td style="font-weight: bold;">Patient ID:</td>
                    <td style="font-family: monospace;">{{ $printToken->patient->patient_number }}</td>
                </tr>
                @endif
                <tr>
                    <td style="font-weight: bold;">Doctor:</td>
                    <td>{{ $printToken->doctor ? $printToken->doctor->name : '—' }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Date:</td>
                    <td>{{ $printToken->created_at ? $printToken->created_at->format('d M Y') : now()->format('d M Y') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Time:</td>
                    <td>{{ $printToken->created_at ? $printToken->created_at->format('h:i A') : now()->format('h:i A') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Fee:</td>
                    <td style="font-weight: bold;">
                        @if(strtolower($printToken->payment_type) === 'free' || floatval($printToken->charged_amount) == 0)
                            FREE
                        @else
                            PKR {{ number_format($printToken->charged_amount, 0) }}
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: bold;">Status:</td>
                    <td style="text-transform: uppercase;">{{ $printToken->status }}</td>
                </tr>
            </table>

            <div style="text-align: center; border-top: 1px dashed #000; padding-top: 8px; font-size: 11px; font-style: italic;">
                {{ account_profile()->footer_text ?: 'Please wait for your token to be called.' }}
            </div>
        </div>
    @endif

    <!-- JAVASCRIPT FOR BROWSER PRINT TRIGGER -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('trigger-print', () => {
                setTimeout(() => {
                    window.print();
                }, 150);
            });
        });
    </script>
</div>
