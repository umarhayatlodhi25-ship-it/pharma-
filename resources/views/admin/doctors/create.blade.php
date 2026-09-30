@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- HEADER & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('doctors.index') }}" class="hover:underline">Doctors</a>
                <span>/</span>
                <span>Register Doctor</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-user-plus text-blue-600"></i>
                <span>Register New Doctor</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Add a medical practitioner and set standard OPD consultation fee</p>
        </div>

        <a href="{{ route('doctors.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i>
            Back to Doctors
        </a>
    </div>

    <!-- DUPLICATE WARNING MODAL / NOTICE (Section 14) -->
    @if(session('warning'))
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-2 shadow-xs">
            <div class="flex items-center space-x-2 font-bold text-sm">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Duplicate Doctor Warning</span>
            </div>
            <p>{{ session('warning') }}</p>
            @if(session('duplicate_doctor_id'))
                <div class="pt-1 flex items-center space-x-3">
                    <a href="{{ route('doctors.show', session('duplicate_doctor_id')) }}" class="font-bold underline text-amber-800 hover:text-amber-950">
                        View Existing Doctor Profile ({{ session('duplicate_doctor_name') }})
                    </a>
                    <span class="text-amber-600">•</span>
                    <span class="text-amber-700">Check the box below if you still wish to register this doctor.</span>
                </div>
            @endif
        </div>
    @endif

    <!-- REGISTRATION FORM -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('doctors.store') }}" class="space-y-6">
            @csrf

            @if(session('warning'))
                <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 flex items-center space-x-2 text-xs">
                    <input type="checkbox" name="confirm_duplicate" id="confirm_duplicate" value="1" class="rounded text-amber-600 focus:ring-amber-500">
                    <label for="confirm_duplicate" class="font-bold text-amber-900 cursor-pointer">
                        Confirm: Register as a separate doctor with identical name and specialization
                    </label>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">

                <!-- 1. Doctor Name -->
                <div class="sm:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1.5">
                        Doctor Name <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ old('name') }}" 
                        placeholder="e.g. Dr. Ahmed Khan" 
                        required
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('name')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 2. Specialization -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">
                        Specialization <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="specialization" 
                        value="{{ old('specialization') }}" 
                        list="specialization_options" 
                        placeholder="e.g. General Physician" 
                        required
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    <datalist id="specialization_options">
                        <option value="General Physician">
                        <option value="Pediatrician">
                        <option value="Cardiologist">
                        <option value="Dermatologist">
                        <option value="Gynecologist">
                        <option value="ENT Specialist">
                        <option value="Orthopedic">
                        <option value="Dentist">
                        <option value="Ophthalmologist">
                        <option value="Neurologist">
                        <option value="Urologist">
                        <option value="Psychiatrist">
                    </datalist>
                    @error('specialization')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 3. Qualification -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">
                        Qualification
                    </label>
                    <input 
                        type="text" 
                        name="qualification" 
                        value="{{ old('qualification') }}" 
                        placeholder="e.g. MBBS, FCPS" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('qualification')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 4. Consultation & Revenue Share Section (Configured ONCE) -->
                <div class="sm:col-span-2 bg-gradient-to-r from-blue-50/60 to-indigo-50/50 p-5 rounded-2xl border border-blue-100 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-blue-100/80 pb-3">
                        <div class="flex items-center space-x-2.5">
                            <div class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                <i class="fa-solid fa-scale-balanced"></i>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900">Consultation & Revenue Share</h3>
                                <p class="text-[11px] text-gray-500">Configured once in Doctor Profile. Automatically applied during token generation.</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 self-start sm:self-auto">
                            Automatic OPD Split
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Consultation Fee -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1.5">
                                Consultation Fee (PKR) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 font-bold text-xs">
                                    PKR
                                </div>
                                <input 
                                    type="number" 
                                    id="docConsultationFee"
                                    name="consultation_fee" 
                                    value="{{ old('consultation_fee', '500') }}" 
                                    min="0" 
                                    step="10" 
                                    required
                                    oninput="recalculateRevenueSplit()"
                                    class="w-full pl-12 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 font-mono font-bold focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs bg-white"
                                />
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Base consultation fee for this doctor.</p>
                            @error('consultation_fee')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Doctor Share % -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1.5">
                                Doctor Share (%) <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="number" 
                                    id="docSharePercentage"
                                    name="doctor_share_percentage" 
                                    value="{{ old('doctor_share_percentage', '70') }}" 
                                    min="0" 
                                    max="100" 
                                    step="0.5" 
                                    required
                                    oninput="recalculateRevenueSplit()"
                                    class="w-full pr-10 pl-3.5 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 font-mono font-bold focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs bg-white"
                                />
                                <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center pointer-events-none text-gray-400 font-bold text-xs">
                                    %
                                </div>
                            </div>
                            <p class="text-[11px] text-gray-400 mt-1">Doctor's percentage share (0% - 100%).</p>
                            @error('doctor_share_percentage')
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Live Revenue Split Preview -->
                    <div class="bg-white p-4 rounded-xl border border-blue-100 shadow-2xs grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                        <div class="p-2.5 bg-blue-50/70 rounded-lg border border-blue-100">
                            <span class="text-[10px] font-bold text-blue-600 uppercase block tracking-wider">Doctor Share</span>
                            <span id="previewDocPct" class="text-base font-black font-mono text-blue-900">70%</span>
                        </div>

                        <div class="p-2.5 bg-indigo-50/70 rounded-lg border border-indigo-100">
                            <span class="text-[10px] font-bold text-indigo-600 uppercase block tracking-wider">Hospital Share</span>
                            <span id="previewHospPct" class="text-base font-black font-mono text-indigo-900">30% (Automatic)</span>
                        </div>

                        <div class="p-2.5 bg-emerald-50/70 rounded-lg border border-emerald-100">
                            <span class="text-[10px] font-bold text-emerald-600 uppercase block tracking-wider">Doctor Amount</span>
                            <span id="previewDocAmount" class="text-base font-black font-mono text-emerald-800">PKR 350</span>
                        </div>

                        <div class="p-2.5 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="text-[10px] font-bold text-slate-600 uppercase block tracking-wider">Hospital Amount</span>
                            <span id="previewHospAmount" class="text-base font-black font-mono text-slate-800">PKR 150</span>
                        </div>
                    </div>
                </div>

                <!-- 5. Status -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">
                        Status <span class="text-rose-500">*</span>
                    </label>
                    <select 
                        name="status" 
                        required
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    >
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Available in OPD)</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Hidden from OPD)</option>
                    </select>
                    @error('status')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 6. Phone -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Phone Number</label>
                    <input 
                        type="text" 
                        name="phone" 
                        value="{{ old('phone') }}" 
                        placeholder="0300-1234567" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 font-mono focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('phone')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 7. Email -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}" 
                        placeholder="doctor@example.com" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('email')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 8. Gender -->
                <div>
                    <label class="block font-bold text-gray-700 mb-1.5">Gender</label>
                    <select 
                        name="gender" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    >
                        <option value="">-- Select Gender --</option>
                        <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('gender')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 9. Address -->
                <div class="sm:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1.5">Clinic / Residential Address</label>
                    <input 
                        type="text" 
                        name="address" 
                        value="{{ old('address') }}" 
                        placeholder="Room / Clinic #, Street, City" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('address')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 10. Notes -->
                <div class="sm:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1.5">Internal Notes / OPD Schedule</label>
                    <textarea 
                        name="notes" 
                        rows="2" 
                        placeholder="Available Mon-Fri 10:00 AM - 02:00 PM, special instructions..."
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    >{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <a href="{{ route('doctors.index') }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Register Doctor</span>
                </button>
            </div>
        </form>
</div>

<script>
    function recalculateRevenueSplit() {
        const feeInput = document.getElementById('docConsultationFee');
        const pctInput = document.getElementById('docSharePercentage');

        const fee = parseFloat(feeInput?.value) || 0;
        let docPct = parseFloat(pctInput?.value);

        if (isNaN(docPct)) docPct = 70;
        if (docPct < 0) docPct = 0;
        if (docPct > 100) docPct = 100;

        const hospPct = Math.round((100 - docPct) * 100) / 100;
        const docAmount = Math.round(fee * (docPct / 100) * 100) / 100;
        const hospAmount = Math.round((fee - docAmount) * 100) / 100;

        document.getElementById('previewDocPct').textContent = docPct + '%';
        document.getElementById('previewHospPct').textContent = hospPct + '% (Automatic)';
        document.getElementById('previewDocAmount').textContent = 'PKR ' + docAmount.toLocaleString('en-US');
        document.getElementById('previewHospAmount').textContent = 'PKR ' + hospAmount.toLocaleString('en-US');
    }

    document.addEventListener('DOMContentLoaded', function () {
        recalculateRevenueSplit();
    });
</script>
@endsection
