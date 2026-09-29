@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Header Banner -->
    <div class="bg-white p-6 rounded-2xl shadow-xs border border-slate-200/80 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-lg border border-blue-100">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Register New Patient</h1>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Enter patient demographics and contact details</p>
            </div>
        </div>
        <div>
            <a href="{{ route('patients.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-sm transition flex items-center space-x-2">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back to Patients</span>
            </a>
        </div>
    </div>

    <!-- Error Alert Box -->
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 p-4 rounded-2xl text-sm shadow-xs">
            <div class="flex items-center space-x-2 font-bold mb-2">
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

    <!-- Registration Form Card -->
    <div class="bg-white p-6 sm:p-8 rounded-2xl shadow-xs border border-slate-200/80">
        <form action="{{ route('patients.store') }}" method="POST" class="space-y-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Patient Name -->
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Patient Name <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="{{ old('name') }}" 
                        placeholder="e.g. Muhammad Ali" 
                        class="w-full p-3 border @error('name') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition" 
                        required
                    >
                    @error('name')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Father / Husband Name -->
                <div>
                    <label for="father_husband_name" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Father / Husband Name
                    </label>
                    <input 
                        type="text" 
                        id="father_husband_name" 
                        name="father_husband_name" 
                        value="{{ old('father_husband_name') }}" 
                        placeholder="e.g. Tariq Mehmood" 
                        class="w-full p-3 border @error('father_husband_name') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition"
                    >
                    @error('father_husband_name')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Age -->
                <div>
                    <label for="age" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Age (Years) <span class="text-rose-500">*</span>
                    </label>
                    <input 
                        type="number" 
                        id="age" 
                        name="age" 
                        min="0" 
                        max="150" 
                        value="{{ old('age') }}" 
                        placeholder="e.g. 35" 
                        class="w-full p-3 border @error('age') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition" 
                        required
                    >
                    @error('age')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Gender -->
                <div>
                    <label for="gender" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Gender <span class="text-rose-500">*</span>
                    </label>
                    <div class="relative">
                        <select 
                            id="gender" 
                            name="gender" 
                            class="w-full p-3 border @error('gender') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 transition appearance-none cursor-pointer" 
                            required
                        >
                            <option value="" disabled {{ old('gender') ? '' : 'selected' }}>Select Gender</option>
                            <option value="Male" {{ old('gender') === 'Male' ? 'selected' : '' }}>Male</option>
                            <option value="Female" {{ old('gender') === 'Female' ? 'selected' : '' }}>Female</option>
                            <option value="Other" {{ old('gender') === 'Other' ? 'selected' : '' }}>Other</option>
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </div>
                    @error('gender')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Phone Number
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-solid fa-phone text-xs"></i>
                        </span>
                        <input 
                            type="text" 
                            id="phone" 
                            name="phone" 
                            value="{{ old('phone') }}" 
                            placeholder="e.g. 0300-1234567" 
                            class="w-full pl-10 pr-4 py-3 border @error('phone') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition"
                        >
                    </div>
                    @error('phone')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- CNIC -->
                <div>
                    <label for="cnic" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        CNIC Number
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400">
                            <i class="fa-regular fa-id-card text-xs"></i>
                        </span>
                        <input 
                            type="text" 
                            id="cnic" 
                            name="cnic" 
                            value="{{ old('cnic') }}" 
                            placeholder="e.g. 35201-1234567-1" 
                            class="w-full pl-10 pr-4 py-3 border @error('cnic') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 font-mono transition"
                        >
                    </div>
                    @error('cnic')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

                <!-- Address -->
                <div class="md:col-span-2">
                    <label for="address" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Address
                    </label>
                    <textarea 
                        id="address" 
                        name="address" 
                        rows="3" 
                        placeholder="Enter full residential address..." 
                        class="w-full p-3 border @error('address') border-rose-400 bg-rose-50/30 @else border-slate-200 bg-slate-50/50 @enderror rounded-xl text-sm focus:outline-none focus:bg-white focus:border-blue-500 text-slate-800 placeholder-slate-400 transition"
                    >{{ old('address') }}</textarea>
                    @error('address')
                        <p class="text-xs text-rose-600 mt-1.5 flex items-center space-x-1">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </p>
                    @enderror
                </div>

            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                <a href="{{ route('patients.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-2.5 rounded-xl font-bold text-sm transition">
                    Cancel
                </a>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-7 py-2.5 rounded-xl font-bold text-sm shadow-sm hover:shadow transition flex items-center space-x-2">
                    <i class="fa-solid fa-check"></i>
                    <span>Register Patient</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
