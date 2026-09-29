@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- HEADER & BREADCRUMB -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="{{ route('doctors.index') }}" class="hover:underline">Doctors</a>
                <span>/</span>
                <a href="{{ route('doctors.show', $doctor->id) }}" class="hover:underline">{{ $doctor->name }}</a>
                <span>/</span>
                <span>Edit</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-user-pen text-blue-600"></i>
                <span>Edit Doctor: {{ $doctor->name }}</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Update doctor credentials, specialization, and consultation fee</p>
        </div>

        <a href="{{ route('doctors.show', $doctor->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-lg transition">
            <i class="fa-solid fa-arrow-left mr-1.5"></i>
            Back to Profile
        </a>
    </div>

    <!-- FEE UPDATE POLICY NOTICE (Section 7) -->
    <div class="p-4 rounded-xl bg-blue-50 border border-blue-200 text-blue-900 text-xs flex items-start space-x-3 shadow-xs">
        <i class="fa-solid fa-circle-info text-blue-600 text-base mt-0.5"></i>
        <div>
            <span class="font-bold block">OPD Fee Update Policy:</span>
            <span>If you modify the Consultation Fee here, only newly generated OPD tokens will use the updated amount. All historical OPD tokens and financial records safely preserve their original consultation fee snapshot.</span>
        </div>
    </div>

    <!-- EDIT FORM -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('doctors.update', $doctor->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-xs">

                <!-- 1. Doctor Name -->
                <div class="sm:col-span-2">
                    <label class="block font-bold text-gray-700 mb-1.5">
                        Doctor Name <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ old('name', $doctor->name) }}" 
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
                        value="{{ old('specialization', $doctor->specialization) }}" 
                        list="specialization_options" 
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
                        value="{{ old('qualification', $doctor->qualification) }}" 
                        placeholder="e.g. MBBS, FCPS" 
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    />
                    @error('qualification')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 4. Consultation Fee -->
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
                            name="consultation_fee" 
                            value="{{ old('consultation_fee', $doctor->consultation_fee) }}" 
                            min="0" 
                            step="10" 
                            required
                            class="w-full pl-12 pr-4 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 font-mono font-bold focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                        />
                    </div>
                    @error('consultation_fee')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
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
                        <option value="active" {{ old('status', $doctor->status) === 'active' ? 'selected' : '' }}>Active (Available in OPD)</option>
                        <option value="inactive" {{ old('status', $doctor->status) === 'inactive' ? 'selected' : '' }}>Inactive (Hidden from OPD)</option>
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
                        value="{{ old('phone', $doctor->phone) }}" 
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
                        value="{{ old('email', $doctor->email) }}" 
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
                        <option value="Male" {{ old('gender', $doctor->gender) === 'Male' ? 'selected' : '' }}>Male</option>
                        <option value="Female" {{ old('gender', $doctor->gender) === 'Female' ? 'selected' : '' }}>Female</option>
                        <option value="Other" {{ old('gender', $doctor->gender) === 'Other' ? 'selected' : '' }}>Other</option>
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
                        value="{{ old('address', $doctor->address) }}" 
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
                        class="w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 focus:outline-hidden focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                    >{{ old('notes', $doctor->notes) }}</textarea>
                    @error('notes')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                <a href="{{ route('doctors.show', $doctor->id) }}" class="px-5 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-sm transition flex items-center space-x-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Update Doctor</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
