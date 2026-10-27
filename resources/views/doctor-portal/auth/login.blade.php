<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Doctor Portal Login - {{ config('app.name', 'Pharmacy Management System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-3 sm:p-6 lg:p-10">

    <!-- Main Container Card -->
    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[580px] border border-slate-200/60">

        <!-- Left Hero Side (Teal/Emerald Theme Banner) -->
        <div class="md:col-span-5 bg-gradient-to-b from-[#e6f4f1] via-[#d4eee8] to-[#bce2d9] p-8 md:p-10 flex flex-col justify-between items-center text-center relative overflow-hidden">
            
            <!-- Background Decorative Ambient Glows -->
            <div class="absolute -top-24 -left-24 w-60 h-60 bg-[#008080]/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-[#0d9488]/20 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Branding Content -->
            <div class="relative z-10 w-full pt-4 flex flex-col items-center">
                <!-- Circular Icon Badge -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg shadow-[#008080]/20 mb-6 border-4 border-white/90">
                    <div class="w-12 h-12 rounded-full bg-teal-500/10 flex items-center justify-center text-[#008080]">
                        <i class="fa-solid fa-user-doctor text-2xl"></i>
                    </div>
                </div>

                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-teal-600/10 border border-teal-600/20 text-[#008080] text-xs font-bold uppercase tracking-wider mb-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Doctor Portal</span>
                </div>

                <!-- Main Titles -->
                <h1 class="text-2xl font-black text-[#004d40] tracking-wide uppercase">CLINICAL WORKSPACE</h1>
                <p class="text-xs font-bold text-[#0d9488] tracking-[0.2em] uppercase mt-0.5">DOCTOR ACCESS</p>

                <!-- Divider -->
                <div class="flex items-center justify-center gap-2 my-4 w-full">
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                    <span class="text-[#008080] text-xs font-bold"><i class="fa-solid fa-stethoscope"></i></span>
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                </div>

                <p class="text-xs text-slate-700 font-medium leading-relaxed max-w-xs">
                    Sign in to manage your active OPD patient queue, create electronic prescriptions, examine consultations, and view financial earnings.
                </p>
            </div>

            <!-- Footer Switch to General Portal -->
            <div class="relative z-10 w-full pt-6 text-center border-t border-teal-200/60">
                <p class="text-xs text-slate-600 font-medium">
                    Pharmacy Cashier or Administrator?
                    <a href="/login" class="font-bold text-[#008080] hover:text-[#004d40] underline block sm:inline mt-1 sm:mt-0">
                        Hospital Login
                    </a>
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form -->
        <div class="md:col-span-7 p-6 sm:p-12 flex flex-col justify-center bg-white">
            
            <div class="max-w-sm w-full mx-auto space-y-6">
                
                <div>
                    <h2 class="text-2xl font-black text-slate-900 tracking-tight">Doctor Sign In</h2>
                    <p class="text-xs text-slate-500 mt-1">Enter your registered email and portal password to proceed.</p>
                </div>

                <!-- Session Flash Messages -->
                @if (session('success'))
                    <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-start space-x-2.5 text-xs text-emerald-800">
                        <i class="fa-solid fa-circle-check text-emerald-600 text-sm mt-0.5 shrink-0"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('info'))
                    <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl flex items-start space-x-2.5 text-xs text-amber-800">
                        <i class="fa-solid fa-circle-info text-amber-600 text-sm mt-0.5 shrink-0"></i>
                        <div>{{ session('info') }}</div>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl flex items-start space-x-2.5 text-xs text-rose-800">
                        <i class="fa-solid fa-circle-exclamation text-rose-600 text-sm mt-0.5 shrink-0"></i>
                        <div>{{ $errors->first() }}</div>
                    </div>
                @endif

                <!-- Login Form -->
                <form action="{{ route('doctor.login.submit') }}" method="POST" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Registered Email Address
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-envelope text-sm"></i>
                            </span>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                value="{{ old('email') }}"
                                placeholder="doctor@hospital.com"
                                required 
                                autofocus
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                            >
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Password
                            </label>
                            <a href="{{ route('password.request') }}" class="text-[11px] font-semibold text-teal-700 hover:text-teal-900">
                                Forgot password?
                            </a>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                                <i class="fa-solid fa-lock text-sm"></i>
                            </span>
                            <input 
                                type="password" 
                                name="password" 
                                id="password" 
                                placeholder="••••••••"
                                required 
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:bg-white focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none transition"
                            >
                        </div>
                    </div>

                    <div class="flex items-center">
                        <label class="flex items-center space-x-2 cursor-pointer">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                value="1" 
                                {{ old('remember') ? 'checked' : '' }}
                                class="rounded text-teal-600 focus:ring-teal-500 border-slate-300"
                            >
                            <span class="text-xs text-slate-600 font-medium">Remember this device</span>
                        </label>
                    </div>

                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full py-3 px-4 rounded-xl font-bold text-sm text-white bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-700 hover:from-teal-700 hover:to-emerald-800 shadow-lg shadow-teal-700/25 transition duration-200 flex items-center justify-center space-x-2 cursor-pointer"
                        >
                            <i class="fa-solid fa-right-to-bracket text-xs"></i>
                            <span>Sign In to Doctor Portal</span>
                        </button>
                    </div>
                </form>

                <!-- New Doctor Registration Link -->
                <div class="pt-4 border-t border-slate-100 text-center space-y-2">
                    <p class="text-xs text-slate-600 font-medium">
                        Are you a doctor registered at this hospital?
                    </p>
                    <a 
                        href="{{ route('doctor.register') }}" 
                        class="inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200 transition"
                    >
                        <i class="fa-solid fa-user-plus text-xs"></i>
                        <span>Request Doctor Portal Access</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
