@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    billingMode: '{{ $initialMode }}', // 'opd' or 'direct'
    patientMode: '{{ $selectedPatient ? 'existing' : 'existing' }}', // 'existing' or 'new'
    
    // Search state
    searchQuery: '',
    searchResults: [],
    isSearching: false,
    searchTimeout: null,
    
    // Selected entities
    selectedPatient: {{ $selectedPatient ? json_encode([
        'id'                           => $selectedPatient->id,
        'name'                         => $selectedPatient->name,
        'patient_number'               => $selectedPatient->patient_number,
        'phone'                        => $selectedPatient->phone,
        'age'                          => $selectedPatient->age,
        'gender'                       => $selectedPatient->gender,
        'address'                      => $selectedPatient->address,
        'cnic'                         => $selectedPatient->cnic,
        'father_husband_name'          => $selectedPatient->father_husband_name,
    ]) : 'null' }},

    selectedToken: {{ $selectedToken ? json_encode([
        'id'                     => $selectedToken->id,
        'token_number'           => $selectedToken->token_number,
        'formatted_token_number' => $selectedToken->formatted_token_number,
        'token_date'             => $selectedToken->token_date->format('d-M-Y'),
        'doctor_id'              => $selectedToken->doctor_id,
        'doctor_name'            => $selectedToken->doctor ? $selectedToken->doctor->name : 'N/A',
        'consultation_fee'       => (float)$selectedToken->consultation_fee,
        'consultation_paid'      => $selectedToken->hospitalBill ? (float)$selectedToken->hospitalBill->paid_amount : (float)$selectedToken->charged_amount,
        'consultation_balance'   => $selectedToken->hospitalBill ? (float)$selectedToken->hospitalBill->due_amount : (float)$selectedToken->balance_due,
    ]) : 'null' }},

    consultationOutstanding: {{ $selectedPatient ? $selectedPatient->consultation_outstanding : 0.00 }},
    hospitalServiceOutstanding: {{ $selectedPatient ? $selectedPatient->hospital_service_outstanding : 0.00 }},
    totalOutstanding: {{ $selectedPatient ? $selectedPatient->pending_balance : 0.00 }},
    existingBills: [],

    // Service catalog & items
    servicesCatalog: {{ json_encode($allServices) }},

    // Doctors catalog & selected doctor
    doctorsCatalog: {{ json_encode($allDoctors->map(fn($d) => [
        'id'               => $d->id,
        'name'             => $d->name,
        'specialization'   => $d->specialization,
        'consultation_fee' => (float)$d->consultation_fee,
    ])) }},
    selectedDoctorId: '{{ $selectedDoctor ? $selectedDoctor->id : ($selectedToken ? $selectedToken->doctor_id : '') }}',
    filterByDoctor: true,
    doctorMismatchError: '',
    items: [
        @if($selectedService)
        {
            hospital_service_id: '{{ $selectedService->id }}',
            service_name: '{{ addslashes($selectedService->name) }}',
            service_code: '{{ addslashes($selectedService->code) }}',
            quantity: 1,
            unit_price: {{ $selectedService->default_fee }},
            discount_amount: 0,
            notes: ''
        }
        @else
        {
            hospital_service_id: '',
            service_name: '',
            service_code: '',
            quantity: 1,
            unit_price: 0,
            discount_amount: 0,
            notes: ''
        }
        @endif
    ],

    // Calculation states
    includePreviousBalance: false,
    discountAmount: 0,
    paymentType: 'paid', // 'paid', 'partial', 'pending', 'free'
    paymentMethod: 'cash',
    paidAmount: 0,
    freeReason: '',
    notes: '',
    submitting: false,

    get selectedDoctorName() {
        if (!this.selectedDoctorId) return '';
        let doc = this.doctorsCatalog.find(d => String(d.id) === String(this.selectedDoctorId));
        return doc ? doc.name : '';
    },

    get isDoctorTokenMismatch() {
        if (this.billingMode === 'opd' && this.selectedToken && this.selectedToken.doctor_id && this.selectedDoctorId) {
            return parseInt(this.selectedDoctorId) !== parseInt(this.selectedToken.doctor_id);
        }
        return false;
    },

    onDoctorChange() {
        if (this.billingMode === 'opd' && this.selectedToken && this.selectedToken.doctor_id) {
            if (this.isDoctorTokenMismatch) {
                this.doctorMismatchError = `Selected OPD Token #${this.selectedToken.token_number} is assigned to Dr. ${this.selectedToken.doctor_name}. Mismatched doctor-token billing is not permitted.`;
            } else {
                this.doctorMismatchError = '';
            }
        } else {
            this.doctorMismatchError = '';
        }

        if (this.billingMode === 'opd' && this.searchQuery.trim().length > 0) {
            this.performSearch();
        }
    },

    resetToTokenDoctor() {
        if (this.selectedToken && this.selectedToken.doctor_id) {
            this.selectedDoctorId = String(this.selectedToken.doctor_id);
            this.doctorMismatchError = '';
        }
    },

    get canSubmit() {
        if (this.submitting) return false;
        if (this.isDoctorTokenMismatch) return false;

        if (this.billingMode === 'opd') {
            if (!this.selectedDoctorId || !this.selectedToken || !this.selectedPatient) {
                return false;
            }
        } else {
            if (this.patientMode === 'existing' && !this.selectedPatient) {
                return false;
            }
        }

        if (this.newServicesTotal <= 0 && this.paymentType !== 'free') {
            return false;
        }

        if (this.paymentType === 'partial') {
            let paid = parseFloat(this.paidAmount) || 0;
            if (paid <= 0 || paid > this.totalDue) {
                return false;
            }
        }

        return true;
    },

    init() {
        if (this.selectedPatient) {
            this.fetchPatientContext(this.selectedPatient.id, this.selectedToken ? this.selectedToken.id : null);
        }
        this.recalculateTotals();
    },

    addItem() {
        this.items.push({
            hospital_service_id: '',
            service_name: '',
            service_code: '',
            quantity: 1,
            unit_price: 0,
            discount_amount: 0,
            notes: ''
        });
        this.recalculateTotals();
    },

    removeItem(index) {
        if (this.items.length > 1) {
            this.items.splice(index, 1);
        } else {
            this.items[0] = {
                hospital_service_id: '',
                service_name: '',
                service_code: '',
                quantity: 1,
                unit_price: 0,
                discount_amount: 0,
                notes: ''
            };
        }
        this.recalculateTotals();
    },

    onServiceSelect(index) {
        let sId = this.items[index].hospital_service_id;
        if (sId) {
            let srv = this.servicesCatalog.find(s => s.id == sId);
            if (srv) {
                this.items[index].service_name = srv.name;
                this.items[index].service_code = srv.code;
                this.items[index].unit_price = parseFloat(srv.default_fee) || 0;
            }
        }
        this.recalculateTotals();
    },

    lineTotal(item) {
        let qty = parseFloat(item.quantity) || 0;
        let price = parseFloat(item.unit_price) || 0;
        let disc = parseFloat(item.discount_amount) || 0;
        let sub = qty * price;
        return Math.max(0, Math.round((sub - disc) * 100) / 100);
    },

    get itemsGrossTotal() {
        return Math.round(this.items.reduce((sum, itm) => {
            let qty = parseFloat(itm.quantity) || 0;
            let price = parseFloat(itm.unit_price) || 0;
            return sum + (qty * price);
        }, 0) * 100) / 100;
    },

    get itemsDiscountSum() {
        return Math.round(this.items.reduce((sum, itm) => {
            return sum + (parseFloat(itm.discount_amount) || 0);
        }, 0) * 100) / 100;
    },

    get newServicesTotal() {
        return Math.round(this.items.reduce((sum, itm) => sum + this.lineTotal(itm), 0) * 100) / 100;
    },

    get eligiblePreviousDue() {
        if (!this.includePreviousBalance) return 0;
        return Math.round((parseFloat(this.totalOutstanding) || 0) * 100) / 100;
    },

    get totalDue() {
        if (this.paymentType === 'free') {
            return this.includePreviousBalance ? this.eligiblePreviousDue : 0;
        }
        return Math.round((this.newServicesTotal + this.eligiblePreviousDue) * 100) / 100;
    },

    get remainingBalance() {
        if (this.paymentType === 'free') return 0;
        let due = this.totalDue;
        let paid = parseFloat(this.paidAmount) || 0;
        return Math.max(0, Math.round((due - paid) * 100) / 100);
    },

    recalculateTotals() {
        if (this.paymentType === 'paid') {
            this.paidAmount = this.totalDue;
        } else if (this.paymentType === 'pending' || this.paymentType === 'free') {
            this.paidAmount = 0;
        } else if (this.paymentType === 'partial') {
            if (this.paidAmount > this.totalDue) {
                this.paidAmount = this.totalDue;
            }
        }
    },

    onPaymentTypeChange() {
        this.recalculateTotals();
    },

    toggleIncludePrevious() {
        this.recalculateTotals();
    },

    // Debounced Search Handler
    onSearchInput() {
        clearTimeout(this.searchTimeout);
        if (this.searchQuery.trim().length < 1) {
            this.searchResults = [];
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.performSearch();
        }, 300);
    },

    performSearch() {
        this.isSearching = true;
        let endpoint = this.billingMode === 'opd' 
            ? '{{ route('hospital-billing.search-tokens') }}' 
            : '{{ route('hospital-billing.search-patients') }}';

        let params = new URLSearchParams();
        params.append('query', this.searchQuery);
        if (this.billingMode === 'opd') {
            if (this.selectedDoctorId) {
                params.append('doctor_id', this.selectedDoctorId);
            }
            if (this.filterByDoctor) {
                params.append('filter_doctor', '1');
            }
        }

        fetch(`${endpoint}?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                this.searchResults = data;
                this.isSearching = false;
            })
            .catch(() => {
                this.searchResults = [];
                this.isSearching = false;
            });
    },

    selectSearchResult(item) {
        if (this.billingMode === 'opd') {
            this.selectedToken = {
                id: item.token_id,
                token_number: item.token_number,
                formatted_token_number: item.formatted_token_number,
                token_date: item.token_date,
                doctor_id: item.doctor_id,
                doctor_name: item.doctor_name,
                consultation_fee: item.consultation_fee,
                consultation_paid: item.consultation_paid,
                consultation_balance: item.consultation_balance,
            };
            if (item.doctor_id) {
                this.selectedDoctorId = String(item.doctor_id);
                this.doctorMismatchError = '';
            }
            this.selectedPatient = {
                id: item.patient_id,
                name: item.patient_name,
                patient_number: item.patient_number,
                phone: item.patient_phone,
                age: item.patient_age,
                gender: item.patient_gender,
            };
            this.consultationOutstanding = item.consultation_outstanding;
            this.hospitalServiceOutstanding = item.hospital_service_outstanding;
            this.totalOutstanding = item.total_outstanding;
            this.fetchPatientContext(item.patient_id, item.token_id);
        } else {
            this.selectedPatient = item;
            this.selectedToken = null;
            this.consultationOutstanding = item.consultation_outstanding;
            this.hospitalServiceOutstanding = item.hospital_service_outstanding;
            this.totalOutstanding = item.total_outstanding;
            this.fetchPatientContext(item.id, null);
        }
        this.searchResults = [];
        this.searchQuery = '';
        this.recalculateTotals();
    },

    fetchPatientContext(patientId, tokenId) {
        let url = `/hospital-billing/patient-context/${patientId}`;
        if (tokenId) url += `?token_id=${tokenId}`;

        fetch(url)
            .then(res => res.json())
            .then(data => {
                this.consultationOutstanding = data.consultation_outstanding || 0;
                this.hospitalServiceOutstanding = data.hospital_service_outstanding || 0;
                this.totalOutstanding = data.total_outstanding || 0;
                this.existingBills = data.existing_bills || [];
                if (data.token && !this.selectedToken) {
                    this.selectedToken = data.token;
                }
                this.recalculateTotals();
            });
    },

    clearSelection() {
        this.selectedPatient = null;
        this.selectedToken = null;
        this.doctorMismatchError = '';
        this.consultationOutstanding = 0;
        this.hospitalServiceOutstanding = 0;
        this.totalOutstanding = 0;
        this.existingBills = [];
        this.recalculateTotals();
    },

    switchBillingMode(mode) {
        this.billingMode = mode;
        this.searchResults = [];
        this.searchQuery = '';
        this.doctorMismatchError = '';
        if (mode === 'direct') {
            this.selectedToken = null;
            this.selectedDoctorId = '';
        } else if (mode === 'opd') {
            if (this.patientMode === 'new') {
                this.patientMode = 'existing';
                this.selectedPatient = null;
            }
            if (this.selectedToken && this.selectedToken.doctor_id) {
                this.selectedDoctorId = String(this.selectedToken.doctor_id);
            }
        }
        this.recalculateTotals();
    }
}">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('hospital-billing.index') }}" class="hover:underline">Hospital Billing</a>
                <span>/</span>
                <span>Generate Bill</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                <span>Hospital Billing & Service Cashier</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Process OPD prescribed procedures, direct hospital walk-in services, and partial collections</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('hospital-billing.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                <span>Billing Invoices</span>
            </a>
        </div>
    </div>

    <!-- ERROR BANNER -->
    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-xs space-y-1">
            <div class="font-bold flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                <span>Please fix the following validation errors:</span>
            </div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- MAIN FORM -->
    <form action="{{ route('hospital-billing.store') }}" method="POST" @submit="submitting = true">
        @csrf
        <input type="hidden" name="billing_mode" :value="billingMode">
        <input type="hidden" name="patient_mode" :value="patientMode">
        <input type="hidden" name="patient_id" :value="selectedPatient ? selectedPatient.id : ''">
        <input type="hidden" name="patient_token_id" :value="billingMode === 'opd' && selectedToken ? selectedToken.id : ''">
        <input type="hidden" name="doctor_id" :value="selectedDoctorId || ''">
        <input type="hidden" name="total_amount" :value="newServicesTotal">
        <input type="hidden" name="items" :value="JSON.stringify(items)">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT 2 COLUMNS: MODE, PATIENT, SERVICES -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. BILLING MODE SELECTOR CARD -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-gray-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs flex items-center justify-center font-black">1</span>
                            <h3 class="font-bold text-sm text-gray-800">Select Hospital Billing Mode</h3>
                        </div>

                        <!-- Mode Toggle Tabs -->
                        <div class="inline-flex bg-gray-100 p-1 rounded-xl text-xs font-semibold">
                            <button 
                                type="button" 
                                @click="switchBillingMode('opd')" 
                                :class="billingMode === 'opd' ? 'bg-white text-blue-600 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900'"
                                class="px-4 py-1.5 rounded-lg transition flex items-center space-x-1.5"
                            >
                                <i class="fa-solid fa-hospital-user"></i>
                                <span>Mode A: OPD Patient Billing</span>
                            </button>
                            <button 
                                type="button" 
                                @click="switchBillingMode('direct')" 
                                :class="billingMode === 'direct' ? 'bg-white text-emerald-600 shadow-xs font-bold' : 'text-gray-500 hover:text-gray-900'"
                                class="px-4 py-1.5 rounded-lg transition flex items-center space-x-1.5"
                            >
                                <i class="fa-solid fa-hand-holding-medical"></i>
                                <span>Mode B: Direct Service Billing</span>
                            </button>
                        </div>
                    </div>

                    <div class="text-xs text-gray-500">
                        <template x-if="billingMode === 'opd'">
                            <p class="flex items-center space-x-1.5 text-blue-700 bg-blue-50/70 p-2.5 rounded-lg border border-blue-100">
                                <i class="fa-solid fa-info-circle"></i>
                                <span><strong>Mode A (OPD Patient):</strong> For patients who already have an OPD token. Preserves consultation fee history and attaches procedure charges to the patient without charging consultation again.</span>
                            </p>
                        </template>
                        <template x-if="billingMode === 'direct'">
                            <p class="flex items-center space-x-1.5 text-emerald-700 bg-emerald-50/70 p-2.5 rounded-lg border border-emerald-100">
                                <i class="fa-solid fa-info-circle"></i>
                                <span><strong>Mode B (Direct Service):</strong> For direct walk-in/private patients (Drip, BP check, Sugar test, Injections, Dressing). No doctor or OPD token required. 100% hospital service revenue.</span>
                            </p>
                        </template>
                    </div>
                </div>

                <!-- 2. PATIENT / TOKEN LOOKUP CARD -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs flex items-center justify-center font-black">2</span>
                            <h3 class="font-bold text-sm text-gray-800" x-text="billingMode === 'opd' ? 'Search OPD Token & Patient' : 'Select or Register Patient'"></h3>
                        </div>

                        <!-- In Direct Mode: Toggle Existing / New Patient -->
                        <template x-if="billingMode === 'direct'">
                            <div class="inline-flex bg-gray-100 p-0.5 rounded-lg text-xs font-semibold">
                                <button 
                                    type="button" 
                                    @click="patientMode = 'existing'" 
                                    :class="patientMode === 'existing' ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                    class="px-3 py-1 rounded-md transition"
                                >
                                    Existing Patient
                                </button>
                                <button 
                                    type="button" 
                                    @click="patientMode = 'new'; clearSelection()" 
                                    :class="patientMode === 'new' ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                    class="px-3 py-1 rounded-md transition"
                                >
                                    + New Patient
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- MODE A: REQUIRED DOCTOR SELECTION CARD -->
                    <template x-if="billingMode === 'opd'">
                        <div class="space-y-2 p-3.5 bg-blue-50/50 rounded-xl border border-blue-100">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1.5">
                                <label class="block text-xs font-bold text-gray-800">
                                    <i class="fa-solid fa-user-doctor text-blue-600 mr-1"></i>
                                    <span>Attending Doctor</span>
                                    <span class="text-red-500">*</span>
                                    <span class="text-[10px] font-normal text-gray-500 ml-1">(Required for OPD procedure billing)</span>
                                </label>
                                <label class="inline-flex items-center space-x-1.5 text-[11px] text-blue-700 font-semibold cursor-pointer select-none">
                                    <input 
                                        type="checkbox" 
                                        x-model="filterByDoctor" 
                                        @change="if (searchQuery.trim().length > 0) performSearch()"
                                        class="rounded text-blue-600 focus:ring-blue-500 w-3.5 h-3.5"
                                    >
                                    <span>Filter tokens to selected doctor</span>
                                </label>
                            </div>

                            <div class="relative">
                                <select 
                                    x-model="selectedDoctorId" 
                                    @change="onDoctorChange()" 
                                    class="w-full pl-9 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500 transition"
                                    :class="{'border-red-400 ring-1 ring-red-300': isDoctorTokenMismatch || (!selectedDoctorId && selectedToken)}"
                                >
                                    <option value="">-- Choose Attending Doctor --</option>
                                    <template x-for="doc in doctorsCatalog" :key="doc.id">
                                        <option :value="doc.id" x-text="'Dr. ' + doc.name + (doc.specialization ? ' — ' + doc.specialization : '')"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-user-doctor absolute left-3 top-3 text-blue-600 text-xs"></i>
                            </div>

                            <!-- Doctor Token Mismatch Banner -->
                            <template x-if="isDoctorTokenMismatch">
                                <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 shadow-xs">
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-base"></i>
                                        <div>
                                            <span class="font-bold">Doctor Mismatch:</span>
                                            <span>OPD Token #<span x-text="selectedToken.token_number"></span> belongs to Dr. <strong x-text="selectedToken.doctor_name"></strong>, which does not match selected Dr. <strong x-text="selectedDoctorName"></strong>. Mismatched doctor-token billing is prohibited.</span>
                                        </div>
                                    </div>
                                    <button 
                                        type="button" 
                                        @click="resetToTokenDoctor()" 
                                        class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-bold text-[11px] rounded-lg transition whitespace-nowrap self-end sm:self-auto shadow-xs"
                                    >
                                        Reset to Token Doctor
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- MODE B: OPTIONAL REFERRING / PERFORMING DOCTOR -->
                    <template x-if="billingMode === 'direct'">
                        <div class="space-y-1.5 p-3.5 bg-emerald-50/40 rounded-xl border border-emerald-100">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-gray-800">
                                    <i class="fa-solid fa-stethoscope text-emerald-600 mr-1"></i>
                                    <span>Referring / Performing Doctor</span>
                                    <span class="text-[10px] font-normal text-gray-500 ml-1">(Optional for direct walk-in billing)</span>
                                </label>
                                <span class="text-[10px] text-emerald-800 font-bold bg-emerald-100/70 px-2 py-0.5 rounded-full" x-text="selectedDoctorId ? 'Doctor share applied per service rules' : '100% Hospital Revenue'"></span>
                            </div>

                            <div class="relative">
                                <select 
                                    x-model="selectedDoctorId" 
                                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-emerald-500 transition"
                                >
                                    <option value="">-- No Doctor Assigned (100% Hospital Service Revenue) --</option>
                                    <template x-for="doc in doctorsCatalog" :key="doc.id">
                                        <option :value="doc.id" x-text="'Dr. ' + doc.name + (doc.specialization ? ' — ' + doc.specialization : '')"></option>
                                    </template>
                                </select>
                                <i class="fa-solid fa-stethoscope absolute left-3 top-2.5 text-emerald-600 text-xs"></i>
                            </div>
                        </div>
                    </template>

                    <!-- SEARCH BAR (When using existing patient or token) -->
                    <template x-if="patientMode === 'existing' && !selectedPatient">
                        <div class="relative space-y-2">
                            <label class="block text-xs font-bold text-gray-700">
                                <span x-text="billingMode === 'opd' ? 'Search by Token Number, Patient Name, ID, or Phone' : 'Search Patient by Name, Patient ID, CNIC or Phone'"></span>
                                <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-gray-400 text-xs"></i>
                                <input 
                                    type="text" 
                                    x-model="searchQuery" 
                                    @input="onSearchInput"
                                    :placeholder="billingMode === 'opd' ? 'Type token number (e.g. 12), patient name, or phone...' : 'Type patient name, phone number, or ID...'" 
                                    class="w-full pl-9 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:ring-2 focus:ring-blue-500 focus:bg-white transition"
                                    autocomplete="off"
                                >
                                <template x-if="isSearching">
                                    <i class="fa-solid fa-circle-notch fa-spin absolute right-3 top-3 text-blue-600 text-xs"></i>
                                </template>
                            </div>

                            <!-- Live Search Results Dropdown -->
                            <div 
                                x-show="searchResults.length > 0" 
                                class="absolute z-20 left-0 right-0 mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto divide-y divide-gray-100"
                            >
                                <template x-for="result in searchResults" :key="result.token_id || result.id">
                                    <div 
                                        @click="selectSearchResult(result)" 
                                        class="p-3 hover:bg-blue-50 cursor-pointer flex items-center justify-between text-xs transition"
                                    >
                                        <div class="space-y-0.5">
                                            <div class="font-bold text-gray-800 flex items-center space-x-2">
                                                <span x-text="result.patient_name || result.name"></span>
                                                <span class="text-[10px] text-gray-400" x-text="result.patient_number || ''"></span>
                                                <template x-if="result.token_number">
                                                    <span class="px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 font-black text-[10px]" x-text="'Token #' + result.token_number"></span>
                                                </template>
                                                <template x-if="result.doctor_matches">
                                                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[9px] flex items-center space-x-1">
                                                        <i class="fa-solid fa-check"></i>
                                                        <span>Matches Doctor</span>
                                                    </span>
                                                </template>
                                            </div>
                                            <div class="text-[11px] text-gray-500 flex items-center space-x-3">
                                                <span><i class="fa-solid fa-phone text-[10px] mr-1"></i><span x-text="result.patient_phone || result.phone || 'No phone'"></span></span>
                                                <template x-if="result.doctor_name">
                                                    <span class="font-medium text-gray-700"><i class="fa-solid fa-user-doctor text-[10px] mr-1 text-blue-600"></i><span x-text="result.doctor_name"></span></span>
                                                </template>
                                                <template x-if="result.token_date">
                                                    <span class="text-gray-400" x-text="result.token_date"></span>
                                                </template>
                                            </div>
                                        </div>

                                        <div class="text-right">
                                            <template x-if="result.total_outstanding > 0">
                                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full" x-text="'Due: PKR ' + result.total_outstanding"></span>
                                            </template>
                                            <div class="text-[10px] text-blue-600 font-semibold mt-1">Select &rarr;</div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- SELECTED PATIENT & TOKEN SUMMARY CARD -->
                    <template x-if="selectedPatient">
                        <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-100 space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-sm shadow-xs">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-800" x-text="selectedPatient.name"></h4>
                                        <p class="text-[11px] text-gray-500 flex items-center space-x-2">
                                            <span x-text="selectedPatient.patient_number || 'PT-XXXXX'"></span>
                                            <span>&bull;</span>
                                            <span x-text="(selectedPatient.gender || '') + (selectedPatient.age ? ', ' + selectedPatient.age + ' yrs' : '')"></span>
                                            <span>&bull;</span>
                                            <span x-text="selectedPatient.phone || 'No Phone'"></span>
                                        </p>
                                    </div>
                                </div>

                                <button type="button" @click="clearSelection()" class="text-xs text-red-600 hover:text-red-800 font-semibold flex items-center space-x-1">
                                    <i class="fa-solid fa-xmark"></i>
                                    <span>Change</span>
                                </button>
                            </div>

                            <!-- OPD TOKEN DETAILS (If Mode A) -->
                            <template x-if="selectedToken">
                                <div class="p-3 bg-white rounded-lg border border-blue-100 grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                                    <div>
                                        <span class="text-[10px] text-gray-400 block font-semibold">OPD Token</span>
                                        <span class="font-bold text-blue-600" x-text="'#' + selectedToken.token_number + ' (' + selectedToken.token_date + ')'"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 block font-semibold">Assigned Doctor</span>
                                        <span class="font-bold text-gray-800" x-text="selectedToken.doctor_name"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 block font-semibold">Consultation Paid</span>
                                        <span class="font-bold text-emerald-600" x-text="'PKR ' + selectedToken.consultation_paid"></span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] text-gray-400 block font-semibold">Consultation Balance</span>
                                        <span class="font-bold" :class="selectedToken.consultation_balance > 0 ? 'text-amber-600' : 'text-gray-500'" x-text="'PKR ' + selectedToken.consultation_balance"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- DIRECT SERVICE DOCTOR BADGE (If Mode B with Doctor) -->
                            <template x-if="billingMode === 'direct' && selectedDoctorId">
                                <div class="p-2.5 bg-emerald-50 rounded-lg border border-emerald-100 flex items-center justify-between text-xs">
                                    <div class="flex items-center space-x-2">
                                        <i class="fa-solid fa-stethoscope text-emerald-600"></i>
                                        <span><strong>Referring / Performing Doctor:</strong> Dr. <span x-text="selectedDoctorName"></span></span>
                                    </div>
                                    <span class="text-[10px] text-emerald-800 bg-white px-2 py-0.5 rounded border border-emerald-200 font-semibold">Service Revenue Split</span>
                                </div>
                            </template>

                            <!-- SEPARATE DEBT BREAKDOWN -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-1 text-xs">
                                <div class="p-2.5 rounded-lg bg-white border border-gray-100">
                                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Consultation Debt</span>
                                    <span class="text-xs font-bold" :class="consultationOutstanding > 0 ? 'text-amber-600' : 'text-gray-700'" x-text="'PKR ' + consultationOutstanding"></span>
                                    <p class="text-[9px] text-gray-400 mt-0.5">Doctor OPD visits</p>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white border border-gray-100">
                                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Hospital Service Debt</span>
                                    <span class="text-xs font-bold" :class="hospitalServiceOutstanding > 0 ? 'text-red-600' : 'text-gray-700'" x-text="'PKR ' + hospitalServiceOutstanding"></span>
                                    <p class="text-[9px] text-gray-400 mt-0.5">Procedures & tests</p>
                                </div>
                                <div class="p-2.5 rounded-lg bg-white border border-gray-100">
                                    <span class="text-[10px] text-gray-400 block uppercase font-bold">Total Patient Due</span>
                                    <span class="text-xs font-bold" :class="totalOutstanding > 0 ? 'text-red-700' : 'text-emerald-700'" x-text="'PKR ' + totalOutstanding"></span>
                                    <p class="text-[9px] text-gray-400 mt-0.5">All outstanding balances</p>
                                </div>
                            </div>

                            <!-- Carry-Forward Checkbox -->
                            <template x-if="totalOutstanding > 0">
                                <div class="pt-2 border-t border-blue-100 flex items-center justify-between">
                                    <label class="flex items-center space-x-2 cursor-pointer text-xs font-bold text-gray-700">
                                        <input 
                                            type="checkbox" 
                                            name="include_previous_balance" 
                                            value="1" 
                                            x-model="includePreviousBalance" 
                                            @change="toggleIncludePrevious"
                                            class="rounded text-blue-600 focus:ring-blue-500 w-4 h-4"
                                        >
                                        <span>Include Eligible Previous Outstanding Balance (PKR <span x-text="totalOutstanding"></span>) in this Bill</span>
                                    </label>
                                    <span class="text-[10px] text-gray-500">FIFO auto-allocation</span>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- NEW PATIENT FORM (In Direct Service Mode) -->
                    <template x-if="patientMode === 'new' && billingMode === 'direct'">
                        <div class="space-y-4 p-4 rounded-xl bg-gray-50 border border-gray-200 text-xs">
                            <h4 class="font-bold text-gray-800 flex items-center space-x-2">
                                <i class="fa-solid fa-user-plus text-blue-600"></i>
                                <span>Register New Direct Patient</span>
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="patient_name" placeholder="Patient Name" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs" :required="patientMode === 'new'">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Father / Husband Name</label>
                                    <input type="text" name="father_husband_name" placeholder="Father/Husband Name" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Phone Number</label>
                                    <input type="text" name="phone" placeholder="0300-1234567" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Age (Years) <span class="text-red-500">*</span></label>
                                    <input type="number" name="age" min="0" max="150" placeholder="e.g. 30" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs" :required="patientMode === 'new'">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                                    <select name="gender" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs" :required="patientMode === 'new'">
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-gray-700 mb-1">CNIC (Optional)</label>
                                    <input type="text" name="cnic" placeholder="xxxxx-xxxxxxx-x" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Address</label>
                                <input type="text" name="address" placeholder="Residential address / city" class="w-full px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs">
                            </div>
                        </div>
                    </template>
                </div>

                <!-- 3. MULTI-SERVICE ENTRY TABLE CARD -->
                <div class="bg-white p-5 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div class="flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs flex items-center justify-center font-black">3</span>
                            <h3 class="font-bold text-sm text-gray-800">Hospital Services & Procedures</h3>
                        </div>

                        <button 
                            type="button" 
                            @click="addItem()" 
                            class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold transition shadow-xs"
                        >
                            <i class="fa-solid fa-plus mr-1.5"></i>
                            <span>Add Service</span>
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 text-gray-600 font-bold border-b border-gray-200">
                                    <th class="py-2.5 px-3">Hospital Service</th>
                                    <th class="py-2.5 px-3 w-20 text-center">Qty</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Unit Price (PKR)</th>
                                    <th class="py-2.5 px-3 w-24 text-right">Discount</th>
                                    <th class="py-2.5 px-3 w-28 text-right">Line Total</th>
                                    <th class="py-2.5 px-2 w-10 text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <!-- Service Dropdown -->
                                        <td class="py-2 px-3">
                                            <select 
                                                x-model="item.hospital_service_id" 
                                                @change="onServiceSelect(index)" 
                                                class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-xs focus:ring-1 focus:ring-blue-500"
                                            >
                                                <option value="">-- Choose Hospital Service --</option>
                                                <template x-for="srv in servicesCatalog" :key="srv.id">
                                                    <option :value="srv.id" x-text="srv.name + ' (PKR ' + srv.default_fee + ')'"></option>
                                                </template>
                                            </select>
                                            <input 
                                                type="text" 
                                                x-model="item.notes" 
                                                placeholder="Optional item remarks (e.g. Right arm drip, 5ml dose)" 
                                                class="w-full mt-1 px-2 py-1 bg-transparent text-[10px] text-gray-500 border-none focus:ring-0 placeholder:text-gray-300"
                                            >
                                        </td>

                                        <!-- Quantity -->
                                        <td class="py-2 px-3 text-center">
                                            <input 
                                                type="number" 
                                                min="1" 
                                                step="1" 
                                                x-model="item.quantity" 
                                                @input="recalculateTotals()" 
                                                class="w-16 px-2 py-1.5 text-center bg-white border border-gray-200 rounded-lg text-xs font-semibold"
                                            >
                                        </td>

                                        <!-- Unit Price -->
                                        <td class="py-2 px-3 text-right">
                                            <input 
                                                type="number" 
                                                min="0" 
                                                step="0.01" 
                                                x-model="item.unit_price" 
                                                @input="recalculateTotals()" 
                                                class="w-24 px-2 py-1.5 text-right bg-white border border-gray-200 rounded-lg text-xs font-semibold"
                                            >
                                        </td>

                                        <!-- Discount -->
                                        <td class="py-2 px-3 text-right">
                                            <input 
                                                type="number" 
                                                min="0" 
                                                step="0.01" 
                                                x-model="item.discount_amount" 
                                                @input="recalculateTotals()" 
                                                class="w-20 px-2 py-1.5 text-right bg-white border border-gray-200 rounded-lg text-xs"
                                                placeholder="0.00"
                                            >
                                        </td>

                                        <!-- Line Total -->
                                        <td class="py-2 px-3 text-right font-bold text-gray-800">
                                            <span x-text="'PKR ' + lineTotal(item).toFixed(2)"></span>
                                        </td>

                                        <!-- Delete Action -->
                                        <td class="py-2 px-2 text-center">
                                            <button 
                                                type="button" 
                                                @click="removeItem(index)" 
                                                class="text-gray-400 hover:text-red-600 transition p-1"
                                                title="Remove service"
                                            >
                                                <i class="fa-solid fa-trash-can text-xs"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Items Quick Total Bar -->
                    <div class="flex justify-between items-center text-xs text-gray-600 pt-2 border-t border-gray-100 font-semibold">
                        <span>Items Subtotal: PKR <strong x-text="itemsGrossTotal.toFixed(2)"></strong></span>
                        <template x-if="itemsDiscountSum > 0">
                            <span class="text-amber-600">Item Discounts: - PKR <strong x-text="itemsDiscountSum.toFixed(2)"></strong></span>
                        </template>
                        <span class="text-blue-700 font-bold">New Services Total: PKR <strong x-text="newServicesTotal.toFixed(2)"></strong></span>
                    </div>
                </div>

            </div>

            <!-- RIGHT 1 COLUMN: BILLING & PAYMENT SUMMARY CARD -->
            <div class="space-y-6">

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-5 sticky top-4">
                    <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                        <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                            <i class="fa-solid fa-calculator text-blue-600"></i>
                            <span>Financial Summary</span>
                        </h3>
                        <span 
                            class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                            :class="{
                                'bg-emerald-100 text-emerald-800': paymentType === 'paid',
                                'bg-amber-100 text-amber-800': paymentType === 'partial',
                                'bg-red-100 text-red-800': paymentType === 'pending',
                                'bg-purple-100 text-purple-800': paymentType === 'free'
                            }"
                            x-text="paymentType"
                        ></span>
                    </div>

                    <!-- Financial Line Items -->
                    <div class="space-y-2.5 text-xs">
                        <div class="flex justify-between items-center text-gray-600">
                            <span>Services Subtotal:</span>
                            <span class="font-semibold text-gray-800" x-text="'PKR ' + itemsGrossTotal.toFixed(2)"></span>
                        </div>

                        <template x-if="itemsDiscountSum > 0">
                            <div class="flex justify-between items-center text-amber-600">
                                <span>Total Discounts:</span>
                                <span class="font-semibold" x-text="'- PKR ' + itemsDiscountSum.toFixed(2)"></span>
                            </div>
                        </template>

                        <div class="flex justify-between items-center font-bold text-gray-800 pt-1 border-t border-gray-50">
                            <span>New Service Total:</span>
                            <span class="text-blue-600" x-text="'PKR ' + newServicesTotal.toFixed(2)"></span>
                        </div>

                        <template x-if="includePreviousBalance && eligiblePreviousDue > 0">
                            <div class="flex justify-between items-center text-red-600 font-semibold bg-red-50/50 p-2 rounded-lg border border-red-100">
                                <div>
                                    <span>Eligible Previous Due:</span>
                                    <span class="text-[10px] text-gray-500 block">Consultation & Services</span>
                                </div>
                                <span x-text="'+ PKR ' + eligiblePreviousDue.toFixed(2)"></span>
                            </div>
                        </template>

                        <div class="flex justify-between items-center font-black text-sm text-gray-900 pt-2 border-t-2 border-gray-100">
                            <span>Total Due:</span>
                            <span class="text-lg text-blue-700" x-text="'PKR ' + totalDue.toFixed(2)"></span>
                        </div>
                    </div>

                    <!-- PAYMENT SECTION -->
                    <div class="space-y-3 pt-3 border-t border-gray-100">
                        <!-- Payment Type -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Payment Status <span class="text-red-500">*</span></label>
                            <select 
                                name="payment_type" 
                                x-model="paymentType" 
                                @change="onPaymentTypeChange()" 
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:bg-white focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="paid">Paid in Full</option>
                                <option value="partial">Partial Payment</option>
                                <option value="pending">Unpaid / Credit (Pending)</option>
                                <option value="free">Free / Complimentary</option>
                            </select>
                        </div>

                        <!-- Amount Received Now -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                <span>Amount Received Now</span>
                                <span class="text-red-500" x-show="paymentType !== 'pending' && paymentType !== 'free'">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs font-bold text-gray-400">PKR</span>
                                <input 
                                    type="number" 
                                    name="paid_amount" 
                                    min="0" 
                                    step="0.01" 
                                    :max="totalDue"
                                    x-model="paidAmount" 
                                    :readonly="paymentType === 'paid' || paymentType === 'pending' || paymentType === 'free'"
                                    :class="{'bg-gray-100': paymentType === 'paid' || paymentType === 'pending' || paymentType === 'free', 'bg-white': paymentType === 'partial'}"
                                    class="w-full pl-12 pr-3 py-2 border border-gray-200 rounded-xl text-xs font-bold text-gray-800 focus:ring-2 focus:ring-blue-500"
                                >
                            </div>
                        </div>

                        <!-- Remaining Due Balance -->
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 flex justify-between items-center text-xs">
                            <span class="font-bold text-gray-700">Remaining Balance:</span>
                            <span class="font-black text-sm" :class="remainingBalance > 0 ? 'text-red-600' : 'text-emerald-600'" x-text="'PKR ' + remainingBalance.toFixed(2)"></span>
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select 
                                name="payment_method" 
                                x-model="paymentMethod" 
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold text-gray-800 focus:bg-white focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="cash">Cash Counter</option>
                                <option value="card">Debit / Credit Card</option>
                                <option value="bank_transfer">Online Bank Transfer</option>
                                <option value="other">Other / Cheque</option>
                            </select>
                        </div>

                        <!-- Free Reason (if complimentary) -->
                        <div x-show="paymentType === 'free'" class="space-y-1">
                            <label class="block text-xs font-bold text-purple-800">Reason for Free Service <span class="text-red-500">*</span></label>
                            <select 
                                name="free_reason" 
                                x-model="freeReason" 
                                class="w-full px-3 py-2 bg-purple-50 border border-purple-200 rounded-xl text-xs font-semibold text-purple-900"
                                :required="paymentType === 'free'"
                            >
                                <option value="">-- Choose Free Reason --</option>
                                <option value="Staff/Employee Courtesy">Staff / Employee Courtesy</option>
                                <option value="Needy/Charity Patient">Needy / Charity Patient</option>
                                <option value="Hospital Director Approval">Hospital Director Approval</option>
                                <option value="Follow-up within valid period">Follow-up within valid period</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <!-- Notes / Remarks -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Bill Remarks / Notes</label>
                            <textarea 
                                name="notes" 
                                rows="2" 
                                placeholder="Any internal notes or references..." 
                                class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-500"
                            ></textarea>
                        </div>
                    </div>

                    <!-- SUBMIT ACTIONS -->
                    <div class="space-y-2 pt-2">
                        <button 
                            type="submit" 
                            name="action" 
                            value="save_bill" 
                            :disabled="!canSubmit"
                            class="w-full py-3 bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs shadow-md transition flex items-center justify-center space-x-2"
                        >
                            <template x-if="submitting">
                                <i class="fa-solid fa-circle-notch fa-spin"></i>
                            </template>
                            <template x-if="!submitting">
                                <i class="fa-solid fa-check"></i>
                            </template>
                            <span>Save Bill & Post Ledger</span>
                        </button>

                        <button 
                            type="submit" 
                            name="action" 
                            value="print_receipt" 
                            :disabled="!canSubmit"
                            class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs shadow-sm transition flex items-center justify-center space-x-2"
                        >
                            <i class="fa-solid fa-print"></i>
                            <span>Save & Print Thermal Receipt</span>
                        </button>

                        <!-- Clear Validation Message Status Helper -->
                        <div x-show="!canSubmit && !submitting" class="text-[11px] text-gray-600 bg-gray-50 p-2.5 rounded-xl border border-gray-200 space-y-1">
                            <template x-if="isDoctorTokenMismatch">
                                <div class="text-red-600 font-bold flex items-center space-x-1.5">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    <span>Doctor and Token do not match. Reset to token doctor or change selection.</span>
                                </div>
                            </template>
                            <template x-if="!isDoctorTokenMismatch && billingMode === 'opd' && !selectedDoctorId">
                                <div class="text-amber-700 font-semibold flex items-center space-x-1.5">
                                    <i class="fa-solid fa-user-doctor"></i>
                                    <span>Please select an attending doctor.</span>
                                </div>
                            </template>
                            <template x-if="!isDoctorTokenMismatch && billingMode === 'opd' && selectedDoctorId && !selectedToken">
                                <div class="text-amber-700 font-semibold flex items-center space-x-1.5">
                                    <i class="fa-solid fa-ticket"></i>
                                    <span>Please search and select an OPD token.</span>
                                </div>
                            </template>
                            <template x-if="!isDoctorTokenMismatch && billingMode === 'direct' && patientMode === 'existing' && !selectedPatient">
                                <div class="text-amber-700 font-semibold flex items-center space-x-1.5">
                                    <i class="fa-solid fa-user"></i>
                                    <span>Please search and select a patient.</span>
                                </div>
                            </template>
                            <template x-if="!isDoctorTokenMismatch && newServicesTotal <= 0 && paymentType !== 'free'">
                                <div class="text-amber-700 font-semibold flex items-center space-x-1.5">
                                    <i class="fa-solid fa-list-check"></i>
                                    <span>Please add at least one hospital service.</span>
                                </div>
                            </template>
                        </div>

                        <a 
                            href="{{ route('hospital-billing.index') }}" 
                            class="w-full py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs text-center block transition"
                        >
                            Cancel
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
@endsection
