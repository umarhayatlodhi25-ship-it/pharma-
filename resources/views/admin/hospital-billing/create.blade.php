@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{
    patientMode: 'existing',
    selectedPatientId: '{{ $selectedPatient->id ?? '' }}',
    patientSearch: '',
    patients: [],
    isSearchingPatients: false,

    doctorId: '{{ $selectedDoctor->id ?? '' }}',
    serviceId: '{{ $selectedService->id ?? '' }}',
    servicesList: {{ json_encode($allServices) }},
    doctorsList: {{ json_encode($allDoctors) }},

    totalFee: {{ $selectedService ? $selectedService->default_fee : ($selectedDoctor ? $selectedDoctor->consultation_fee : 500) }},
    doctorSharePct: {{ $selectedService ? $selectedService->doctor_share_percentage : 70 }},
    hospitalSharePct: {{ $selectedService ? $selectedService->hospital_share_percentage : 30 }},

    paymentType: 'paid', // paid, partial, free, pending
    paidAmount: {{ $selectedService ? $selectedService->default_fee : ($selectedDoctor ? $selectedDoctor->consultation_fee : 500) }},
    freeReason: '',
    paymentMethod: 'cash',
    notes: '',

    init() {
        if (this.serviceId) {
            this.onServiceChange();
        } else if (this.doctorId) {
            this.onDoctorChange();
        }
    },

    onServiceChange() {
        let srv = this.servicesList.find(s => s.id == this.serviceId);
        if (srv) {
            this.totalFee = parseFloat(srv.default_fee) || 0;
            this.doctorSharePct = parseFloat(srv.doctor_share_percentage) || 0;
            this.hospitalSharePct = parseFloat(srv.hospital_share_percentage) || 0;
            this.recalculate();
        }
    },

    onDoctorChange() {
        let doc = this.doctorsList.find(d => d.id == this.doctorId);
        if (doc && (!this.serviceId || this.serviceId == '1')) {
            this.totalFee = parseFloat(doc.consultation_fee) || 500;
            this.recalculate();
        }
    },

    recalculate() {
        if (this.paymentType === 'paid') {
            this.paidAmount = this.totalFee;
        } else if (this.paymentType === 'free' || this.paymentType === 'pending') {
            this.paidAmount = 0;
        } else if (this.paymentType === 'partial') {
            if (this.paidAmount > this.totalFee) {
                this.paidAmount = this.totalFee;
            }
        }
    },

    get doctorShare() {
        if (this.paymentType === 'free') return 0;
        let base = (this.paymentType === 'partial') ? (parseFloat(this.paidAmount) || 0) : (parseFloat(this.totalFee) || 0);
        return Math.round((base * (this.doctorSharePct / 100)) * 100) / 100;
    },

    get hospitalShare() {
        if (this.paymentType === 'free') return 0;
        let base = (this.paymentType === 'partial') ? (parseFloat(this.paidAmount) || 0) : (parseFloat(this.totalFee) || 0);
        return Math.round((base - this.doctorShare) * 100) / 100;
    },

    get remainingDue() {
        if (this.paymentType === 'free') return 0;
        let fee = parseFloat(this.totalFee) || 0;
        let paid = parseFloat(this.paidAmount) || 0;
        return Math.max(0, Math.round((fee - paid) * 100) / 100);
    }
}">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/hospital-billing" class="hover:underline">Hospital Billing</a>
                <span>/</span>
                <span>New Bill</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-file-invoice-dollar text-blue-600"></i>
                <span>Generate Hospital Bill & Revenue Split</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Receive patient payments, calculate doctor/hospital revenue shares and issue official receipt</p>
        </div>

        <div>
            <a href="{{ route('hospital-billing.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
                <i class="fa-solid fa-arrow-left mr-1.5"></i>
                <span>Back to Invoices</span>
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

    <form action="{{ route('hospital-billing.store') }}" method="POST">
        @csrf
        <input type="hidden" name="patient_mode" :value="patientMode">
        <input type="hidden" name="doctor_share_percentage" :value="doctorSharePct">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT 2 COLUMNS: PATIENT & SERVICE SELECTION -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. PATIENT SELECTION CARD -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs flex items-center justify-center font-black">1</span>
                            <span>Patient Information</span>
                        </h3>

                        <!-- Patient Mode Toggle -->
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
                                @click="patientMode = 'new'" 
                                :class="patientMode === 'new' ? 'bg-white text-blue-600 shadow-xs' : 'text-gray-500 hover:text-gray-900'"
                                class="px-3 py-1 rounded-md transition"
                            >
                                + New Patient
                            </button>
                        </div>
                    </div>

                    <!-- EXISTING PATIENT SELECT -->
                    <div x-show="patientMode === 'existing'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Search & Select Patient <span class="text-red-500">*</span></label>
                            <select 
                                name="patient_id" 
                                x-model="selectedPatientId" 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                            >
                                <option value="">-- Choose Existing Patient --</option>
                                @foreach(\App\Models\Patient::orderBy('name')->get() as $p)
                                    <option value="{{ $p->id }}" {{ (old('patient_id') == $p->id || (isset($selectedPatient) && $selectedPatient->id == $p->id)) ? 'selected' : '' }}>
                                        {{ $p->name }} ({{ $p->patient_number }}) - {{ $p->phone ?: 'No phone' }} - Age: {{ $p->age }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- NEW PATIENT REGISTRATION FORM -->
                    <div x-show="patientMode === 'new'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs" style="display: none;">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-gray-700 mb-1">Patient Full Name <span class="text-red-500">*</span></label>
                            <input type="text" name="patient_name" value="{{ old('patient_name') }}" placeholder="Full Name" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Father / Husband Name</label>
                            <input type="text" name="father_husband_name" value="{{ old('father_husband_name') }}" placeholder="Father / Husband Name" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Phone Number</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" placeholder="03XXXXXXXXX" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono focus:bg-white focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Age <span class="text-red-500">*</span></label>
                            <input type="number" name="age" value="{{ old('age', 25) }}" min="0" max="150" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono focus:bg-white focus:border-blue-500">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Gender <span class="text-red-500">*</span></label>
                            <select name="gender" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-gray-700 mb-1">Address</label>
                            <input type="text" name="address" value="{{ old('address') }}" placeholder="Residential address / City" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- 2. SERVICE & DOCTOR SELECTION CARD -->
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm space-y-4">
                    <div class="border-b border-gray-100 pb-3">
                        <h3 class="font-bold text-sm text-gray-800 flex items-center space-x-2">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 text-xs flex items-center justify-center font-black">2</span>
                            <span>Hospital Service & Doctor</span>
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- Service Selection -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Select Service <span class="text-red-500">*</span></label>
                            <select 
                                name="hospital_service_id" 
                                x-model="serviceId" 
                                @change="onServiceChange()" 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500"
                            >
                                <option value="">-- Choose Hospital Service --</option>
                                @foreach($allServices as $srv)
                                    <option value="{{ $srv->id }}">
                                        {{ $srv->name }} (Fee: PKR {{ number_format($srv->default_fee, 0) }}) - Doc: {{ number_format($srv->doctor_share_percentage, 0) }}% / Hosp: {{ number_format($srv->hospital_share_percentage, 0) }}%
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Doctor Selection -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Attending Doctor (Optional for lab/procedure)</label>
                            <select 
                                name="doctor_id" 
                                x-model="doctorId" 
                                @change="onDoctorChange()" 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500"
                            >
                                <option value="">-- Hospital Direct / No Doctor --</option>
                                @foreach($allDoctors as $doc)
                                    <option value="{{ $doc->id }}">
                                        {{ $doc->name }} (Fee: PKR {{ number_format($doc->consultation_fee, 0) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Editable Total Fee -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Total Service Fee (PKR) <span class="text-red-500">*</span></label>
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                name="total_amount" 
                                x-model.number="totalFee" 
                                @input="recalculate()" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-gray-900 focus:bg-white focus:border-blue-500"
                            >
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select 
                                name="payment_method" 
                                x-model="paymentMethod" 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:bg-white focus:border-blue-500"
                            >
                                <option value="cash">Cash Counter</option>
                                <option value="card">Debit / Credit Card</option>
                                <option value="bank_transfer">Bank Transfer / Online</option>
                                <option value="other">Other / Cheque</option>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Status Selectors: Paid / Partial / Free / Pending -->
                    <div class="pt-2">
                        <label class="block font-bold text-gray-700 mb-2 text-xs">Payment Collection Status <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <label 
                                :class="paymentType === 'paid' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 ring-2 ring-emerald-500' : 'border-gray-200 bg-gray-50 hover:bg-white text-gray-700'"
                                class="p-3 border rounded-xl cursor-pointer transition flex flex-col items-center justify-center text-center space-y-1"
                            >
                                <input type="radio" name="payment_type" value="paid" x-model="paymentType" @change="recalculate()" class="sr-only">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                                <span class="font-bold">Full Paid</span>
                                <span class="text-[10px] text-gray-500">100% Collected</span>
                            </label>

                            <label 
                                :class="paymentType === 'partial' ? 'border-amber-500 bg-amber-50/50 text-amber-900 ring-2 ring-amber-500' : 'border-gray-200 bg-gray-50 hover:bg-white text-gray-700'"
                                class="p-3 border rounded-xl cursor-pointer transition flex flex-col items-center justify-center text-center space-y-1"
                            >
                                <input type="radio" name="payment_type" value="partial" x-model="paymentType" @change="recalculate()" class="sr-only">
                                <i class="fa-solid fa-clock-rotate-left text-amber-600 text-base"></i>
                                <span class="font-bold">Partial Paid</span>
                                <span class="text-[10px] text-gray-500">Part received, rest due</span>
                            </label>

                            <label 
                                :class="paymentType === 'free' ? 'border-purple-500 bg-purple-50/50 text-purple-900 ring-2 ring-purple-500' : 'border-gray-200 bg-gray-50 hover:bg-white text-gray-700'"
                                class="p-3 border rounded-xl cursor-pointer transition flex flex-col items-center justify-center text-center space-y-1"
                            >
                                <input type="radio" name="payment_type" value="free" x-model="paymentType" @change="recalculate()" class="sr-only">
                                <i class="fa-solid fa-hand-holding-heart text-purple-600 text-base"></i>
                                <span class="font-bold">Free Patient</span>
                                <span class="text-[10px] text-gray-500">100% Discounted</span>
                            </label>

                            <label 
                                :class="paymentType === 'pending' ? 'border-gray-500 bg-gray-100 text-gray-900 ring-2 ring-gray-500' : 'border-gray-200 bg-gray-50 hover:bg-white text-gray-700'"
                                class="p-3 border rounded-xl cursor-pointer transition flex flex-col items-center justify-center text-center space-y-1"
                            >
                                <input type="radio" name="payment_type" value="pending" x-model="paymentType" @change="recalculate()" class="sr-only">
                                <i class="fa-solid fa-hourglass-half text-gray-600 text-base"></i>
                                <span class="font-bold">Pending Due</span>
                                <span class="text-[10px] text-gray-500">Unpaid Bill</span>
                            </label>
                        </div>
                    </div>

                    <!-- Partial Payment Input -->
                    <div x-show="paymentType === 'partial'" class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl space-y-2 text-xs" style="display: none;">
                        <label class="block font-bold text-amber-900">Paid Amount Now (PKR) <span class="text-red-500">*</span></label>
                        <div class="relative max-w-xs">
                            <input 
                                type="number" 
                                step="0.01" 
                                min="0.01" 
                                :max="totalFee" 
                                name="paid_amount" 
                                x-model.number="paidAmount" 
                                @input="recalculate()" 
                                class="w-full p-2.5 bg-white border border-amber-300 rounded-lg font-mono font-bold text-amber-900 focus:outline-hidden focus:border-amber-500"
                            >
                        </div>
                        <p class="text-[11px] text-amber-800">
                            Remaining Due to be paid later: <strong class="font-mono font-bold" x-text="'PKR ' + remainingDue.toFixed(2)"></strong>
                        </p>
                    </div>

                    <!-- Free Patient Reason -->
                    <div x-show="paymentType === 'free'" class="p-4 bg-purple-50/70 border border-purple-200 rounded-xl space-y-2 text-xs" style="display: none;">
                        <label class="block font-bold text-purple-900">Reason for Free Billing <span class="text-red-500">*</span></label>
                        <select name="free_reason" x-model="freeReason" class="w-full p-2.5 bg-white border border-purple-300 rounded-lg font-medium focus:border-purple-500">
                            <option value="Poor Patient">Poor / Needy Patient</option>
                            <option value="Doctor Reference">Doctor Personal Reference</option>
                            <option value="Hospital Staff">Hospital Employee / Staff Relative</option>
                            <option value="Emergency Welfare">Emergency / Welfare Case</option>
                            <option value="Other">Other Reason</option>
                        </select>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block font-bold text-gray-700 mb-1 text-xs">Remarks / Billing Notes</label>
                        <input type="text" name="notes" placeholder="Optional notes for cashier record..." class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:bg-white focus:border-blue-500">
                    </div>
                </div>

            </div>

            <!-- RIGHT 1 COLUMN: REVENUE SPLIT PREVIEW & ACTIONS -->
            <div class="space-y-6">

                <!-- LIVE REVENUE SPLIT PREVIEW CARD -->
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between">
                        <h3 class="font-bold text-xs uppercase tracking-wider flex items-center space-x-2">
                            <i class="fa-solid fa-calculator text-blue-400"></i>
                            <span>Revenue Split Summary</span>
                        </h3>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-slate-800 font-mono text-slate-300">Live Calculation</span>
                    </div>

                    <div class="p-5 space-y-4 text-xs font-mono">
                        <!-- Gross & Discount -->
                        <div class="space-y-1.5 pb-3 border-b border-gray-100">
                            <div class="flex justify-between text-gray-600">
                                <span>Gross Service Fee:</span>
                                <strong class="text-gray-900" x-text="'PKR ' + parseFloat(totalFee).toFixed(2)"></strong>
                            </div>

                            <template x-if="paymentType === 'free'">
                                <div class="flex justify-between text-purple-600">
                                    <span>Free Waiver / Discount:</span>
                                    <strong x-text="'- PKR ' + parseFloat(totalFee).toFixed(2)"></strong>
                                </div>
                            </template>

                            <div class="flex justify-between text-gray-600">
                                <span>Hospital Collected:</span>
                                <strong class="text-emerald-600 text-sm font-black" x-text="'PKR ' + (paymentType === 'free' ? '0.00' : (paymentType === 'partial' ? parseFloat(paidAmount).toFixed(2) : parseFloat(totalFee).toFixed(2)))"></strong>
                            </div>

                            <template x-if="paymentType === 'partial'">
                                <div class="flex justify-between text-amber-600 font-bold">
                                    <span>Remaining Due:</span>
                                    <span x-text="'PKR ' + remainingDue.toFixed(2)"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Revenue Sharing Split -->
                        <div class="space-y-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider font-sans">Recognized Revenue Share</p>
                            
                            <!-- Doctor Share -->
                            <div class="p-3 bg-blue-50/70 border border-blue-100 rounded-xl flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-blue-900 font-sans">Doctor Payable</div>
                                    <div class="text-[10px] text-blue-600">Share: <span x-text="doctorSharePct"></span>%</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-blue-700" x-text="'PKR ' + doctorShare.toFixed(2)"></div>
                                    <div class="text-[9px] text-blue-500">Credited to Doctor</div>
                                </div>
                            </div>

                            <!-- Hospital Share -->
                            <div class="p-3 bg-emerald-50/70 border border-emerald-100 rounded-xl flex items-center justify-between">
                                <div>
                                    <div class="font-bold text-emerald-900 font-sans">Hospital Share</div>
                                    <div class="text-[10px] text-emerald-600">Share: <span x-text="hospitalSharePct"></span>%</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-black text-emerald-700" x-text="'PKR ' + hospitalShare.toFixed(2)"></div>
                                    <div class="text-[9px] text-emerald-500">Hospital Revenue</div>
                                </div>
                            </div>
                        </div>

                        <!-- Disclaimer notice -->
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-[10px] text-slate-500 font-sans leading-relaxed">
                            <i class="fa-solid fa-shield-halved text-blue-600 mr-1"></i>
                            The hospital receives ALL patient payments directly. Doctor shares are recorded in the doctor's ledger balance and can be settled anytime by hospital administration.
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <div class="pt-2">
                            <button 
                                type="submit" 
                                class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-xl font-bold text-xs shadow-md hover:shadow-lg transition flex items-center justify-center space-x-2 font-sans"
                            >
                                <i class="fa-solid fa-receipt"></i>
                                <span>Generate Bill & Record Payment</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </form>

</div>
@endsection
