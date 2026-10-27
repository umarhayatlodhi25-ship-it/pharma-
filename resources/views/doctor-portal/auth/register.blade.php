<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Doctor Portal Registration - {{ config('app.name', 'Pharmacy Management System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-3 sm:p-6 lg:p-10" x-data="{ termsModalOpen: false }">

    <!-- Main Container Card -->
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[700px] border border-slate-200/60">

        <!-- Left Hero Side (Teal/Emerald Theme Banner) -->
        <div class="md:col-span-5 bg-gradient-to-b from-[#e6f4f1] via-[#d4eee8] to-[#bce2d9] p-8 md:p-10 flex flex-col justify-between items-center text-center relative overflow-hidden">
            
            <!-- Background Decorative Ambient Glows -->
            <div class="absolute -top-24 -left-24 w-60 h-60 bg-[#008080]/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-[#0d9488]/20 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Branding Content -->
            <div class="relative z-10 w-full pt-4 flex flex-col items-center">
                <!-- Circular Icon Badge -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg shadow-[#008080]/20 mb-5 border-4 border-white/90">
                    <div class="w-12 h-12 rounded-full bg-teal-500/10 flex items-center justify-center text-[#008080]">
                        <i class="fa-solid fa-user-doctor text-2xl"></i>
                    </div>
                </div>

                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-teal-600/10 border border-teal-600/20 text-[#008080] text-xs font-bold uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Doctor Self-Service</span>
                </div>

                <!-- Main Titles -->
                <h1 class="text-2xl font-black text-[#004d40] tracking-wide uppercase">DOCTOR PORTAL</h1>
                <p class="text-xs font-bold text-[#0d9488] tracking-[0.2em] uppercase mt-0.5">ACCOUNT REGISTRATION</p>

                <!-- Divider -->
                <div class="flex items-center justify-center gap-2 my-4 w-full">
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                    <span class="text-[#008080] text-xs font-bold"><i class="fa-solid fa-stethoscope"></i></span>
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                </div>

                <p class="text-xs text-slate-700 font-medium leading-relaxed max-w-xs">
                    Connect your existing hospital doctor profile to access your OPD Queue, electronic prescriptions, patient consultation records, and practice earnings.
                </p>
            </div>

            <!-- Information Card -->
            <div class="relative z-10 w-full mt-6 p-4 rounded-2xl bg-white/80 backdrop-blur-sm border border-teal-200 shadow-sm text-left text-xs text-slate-700 space-y-2.5">
                <div class="flex items-center space-x-2 text-[#008080] font-bold">
                    <i class="fa-solid fa-shield-halved text-sm"></i>
                    <span>Hospital Verification Policy</span>
                </div>
                <ul class="space-y-1.5 text-[11px] text-slate-600">
                    <li class="flex items-start space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                        <span>Only doctors already registered in hospital records may request portal access.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                        <span>Account requests are securely verified and approved by the hospital administrator.</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-0.5 shrink-0"></i>
                        <span>Zero data disclosure: Existing patient records remain strictly protected.</span>
                    </li>
                </ul>
            </div>

            <!-- Footer Login Link -->
            <div class="relative z-10 w-full pt-4 text-center">
                <p class="text-xs text-slate-600 font-medium">
                    Already have an approved account?
                    <a href="{{ route('doctor.login') }}" class="font-bold text-[#008080] hover:text-[#004d40] underline ml-1">
                        Log In here
                    </a>
                </p>
            </div>
        </div>

        <!-- Right Side: Registration Form -->
        <div class="md:col-span-7 p-6 sm:p-10 flex flex-col justify-center bg-white overflow-y-auto">
            
            <div class="max-w-md w-full mx-auto space-y-5">
                
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Request Portal Account</h2>
                    <p class="text-xs text-slate-500 mt-1">Please provide your existing doctor details and credentials to verify identity.</p>
                </div>

                <!-- Session Flash Messages -->
                @if (session('success'))
                    <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start space-x-2.5 text-xs text-emerald-800">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm mt-0.5 shrink-0"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-800 space-y-1">
                        <div class="flex items-center space-x-2 font-bold text-rose-900">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            <span>Please correct the errors below:</span>
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 pl-1 text-[11px]">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Registration Form -->
                <form action="{{ route('doctor.register.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Section 1: Doctor Profile Lookup -->
                    <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                            <span class="flex items-center space-x-1.5 text-teal-800">
                                <i class="fa-solid fa-id-card-clip text-teal-600"></i>
                                <span>1. Hospital Doctor Identification</span>
                            </span>
                            <span class="text-[10px] text-slate-500 font-normal">Registered details</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="doctor_identifier" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Doctor ID / Staff Code <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-hashtag text-xs"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="doctor_identifier" 
                                        id="doctor_identifier" 
                                        value="{{ old('doctor_identifier') }}"
                                        placeholder="e.g. 1 or DOC-101"
                                        required 
                                        class="w-full pl-8 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="name" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Doctor Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-user-doctor text-xs"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="name" 
                                        id="name" 
                                        value="{{ old('name') }}"
                                        placeholder="Dr. Sarah Khan"
                                        required 
                                        class="w-full pl-8 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="license_number" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                PMDC / Medical License / CNIC <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                    <i class="fa-solid fa-certificate text-xs"></i>
                                </span>
                                <input 
                                    type="text" 
                                    name="license_number" 
                                    id="license_number" 
                                    value="{{ old('license_number') }}"
                                    placeholder="e.g. 12345-P or 35201-XXXXXXX-X"
                                    required 
                                    class="w-full pl-8 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Contact Details -->
                    <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                            <span class="flex items-center space-x-1.5 text-teal-800">
                                <i class="fa-solid fa-address-book text-teal-600"></i>
                                <span>2. Registered Contact Details</span>
                            </span>
                            <span class="text-[10px] text-slate-500 font-normal">Used for verification</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="email" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Email Address <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-envelope text-xs"></i>
                                    </span>
                                    <input 
                                        type="email" 
                                        name="email" 
                                        id="email" 
                                        value="{{ old('email') }}"
                                        placeholder="doctor@hospital.com"
                                        required 
                                        class="w-full pl-8 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                    >
                                </div>
                            </div>

                            <div>
                                <label for="phone" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Phone Number <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                        <i class="fa-solid fa-phone text-xs"></i>
                                    </span>
                                    <input 
                                        type="text" 
                                        name="phone" 
                                        id="phone" 
                                        value="{{ old('phone') }}"
                                        placeholder="03001234567"
                                        required 
                                        class="w-full pl-8 pr-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Credentials -->
                    <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 space-y-3">
                        <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                            <span class="flex items-center space-x-1.5 text-teal-800">
                                <i class="fa-solid fa-lock text-teal-600"></i>
                                <span>3. Set Portal Password</span>
                            </span>
                            <span class="text-[10px] text-slate-500 font-normal">Min 6 characters</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="password" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Password <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="password" 
                                    id="password" 
                                    placeholder="••••••••"
                                    required 
                                    class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                >
                            </div>

                            <div>
                                <label for="password_confirmation" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Confirm Password <span class="text-rose-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    name="password_confirmation" 
                                    id="password_confirmation" 
                                    placeholder="••••••••"
                                    required 
                                    class="w-full px-3 py-2 text-xs bg-white border border-slate-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Terms & Privacy Acknowledgement -->
                    <div class="pt-1">
                        <label class="flex items-start space-x-2.5 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="terms" 
                                value="1" 
                                {{ old('terms') ? 'checked' : '' }}
                                required
                                class="mt-0.5 rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                            >
                            <span class="text-xs text-slate-600 leading-snug">
                                I confirm that I am an authorized medical doctor registered with this hospital, and I agree to the 
                                <button type="button" @click="termsModalOpen = true" class="text-teal-700 underline font-semibold hover:text-teal-900">
                                    Terms of Clinical Service & Privacy Policy
                                </button>.
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-700 hover:from-teal-700 hover:to-emerald-800 shadow-lg shadow-teal-700/25 transition duration-200 flex items-center justify-center space-x-2 cursor-pointer"
                        >
                            <i class="fa-solid fa-paper-plane text-xs"></i>
                            <span>Submit Registration Request</span>
                        </button>
                    </div>
                </form>

                <div class="pt-2 text-center text-xs text-slate-500">
                    <span>Having trouble? Contact Hospital Administrator or IT Desk.</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms Modal -->
    <div 
        x-show="termsModalOpen" 
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="termsModalOpen = false"
            class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4 border border-slate-200"
        >
            <div class="flex items-center justify-between border-b pb-3 border-slate-100">
                <h3 class="text-base font-bold text-slate-900 flex items-center space-x-2">
                    <i class="fa-solid fa-shield-halved text-teal-600"></i>
                    <span>Terms & Privacy Acknowledgement</span>
                </h3>
                <button @click="termsModalOpen = false" class="text-slate-400 hover:text-slate-700">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            
            <div class="text-xs text-slate-600 space-y-3 max-h-72 overflow-y-auto pr-2 leading-relaxed">
                <p class="font-bold text-slate-800">1. Medical Record Confidentiality</p>
                <p>All clinical consultation data, patient history, diagnostic findings, and prescriptions accessed through this portal are strictly confidential and governed by patient privacy standards. Doctors must not disclose confidential patient records.</p>
                
                <p class="font-bold text-slate-800">2. Identity & Profile Linking</p>
                <p>Registration requests are matched with existing hospital doctor profiles. Creating false requests or attempting to claim another practitioner's identity is strictly prohibited and subject to immediate administrative review.</p>

                <p class="font-bold text-slate-800">3. Administrative Approval</p>
                <p>Submitting this form submits a request for portal credentials. Active access is granted only after verification and explicit approval by the hospital administration.</p>
            </div>

            <div class="pt-3 border-t border-slate-100 flex justify-end">
                <button 
                    type="button" 
                    @click="termsModalOpen = false"
                    class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition"
                >
                    I Understand
                </button>
            </div>
        </div>
    </div>

</body>
</html>
