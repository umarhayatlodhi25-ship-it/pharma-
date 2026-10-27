<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login - Pharmacy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Firebase SDK Loaded Synchronously in Head -->
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-auth-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.23.0/firebase-database-compat.js"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-100 flex items-center justify-center min-h-screen p-3 sm:p-6 lg:p-10">

    <!-- Main Container Card -->
    <div class="w-full max-w-5xl bg-white rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-12 min-h-[640px] border border-slate-200/60">

        <!-- Left Hero Side (Teal Theme Banner) -->
        <div class="md:col-span-5 bg-gradient-to-b from-[#e6f4f1] via-[#d4eee8] to-[#bce2d9] p-8 md:p-10 flex flex-col justify-between items-center text-center relative overflow-hidden">
            
            <!-- Background Decorative Ambient Glows -->
            <div class="absolute -top-24 -left-24 w-60 h-60 bg-[#008080]/10 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-[#0d9488]/15 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Top Branding Content -->
            <div class="relative z-10 w-full pt-4 flex flex-col items-center">
                <!-- Circular Icon Badge -->
                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg shadow-[#008080]/15 mb-6 border-4 border-white/80">
                    <svg class="w-11 h-11 text-[#008080]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <!-- Medical Cross & Capsule Pill Circle -->
                        <circle cx="12" cy="12" r="9" class="stroke-[#008080]" stroke-width="1.8" />
                        <path d="M12 7v10M7 12h10" stroke-width="2.5" class="stroke-[#008080]" />
                        <path d="M15 9l2.5-2.5a2.121 2.121 0 0 1 3 3L18 12" stroke-width="1.8" class="stroke-[#0d9488]" />
                    </svg>
                </div>

                <!-- Main Titles -->
                <h1 class="text-3xl font-extrabold text-[#004d40] tracking-wider uppercase">PHARMACY</h1>
                <p class="text-xs font-bold text-[#0d9488] tracking-[0.25em] uppercase mt-1">MANAGEMENT SYSTEM</p>

                <!-- Small Divider -->
                <div class="flex items-center justify-center gap-2 my-4 w-full">
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                    <span class="text-[#008080] text-xs font-bold">+</span>
                    <div class="h-[1px] bg-[#008080]/20 w-12"></div>
                </div>

                <!-- Tagline -->
                <p class="text-sm text-slate-600 font-medium max-w-xs">Smart Pharmacy, Better Care</p>
            </div>

            <!-- Bottom Pharmacy Illustration Graphics -->
            <div class="relative z-10 w-full mt-6 flex justify-center items-end">
                <svg viewBox="0 0 280 200" class="max-h-56 w-auto drop-shadow-md" xmlns="http://www.w3.org/2000/svg">
                    <!-- Shelf background -->
                    <rect x="20" y="140" width="240" height="12" rx="4" fill="#0d9488" opacity="0.3"/>
                    <rect x="10" y="152" width="260" height="6" rx="3" fill="#008080" opacity="0.25"/>
                    <!-- Medicine bottles -->
                    <rect x="40" y="90" width="28" height="50" rx="6" fill="#0d9488" opacity="0.8"/>
                    <rect x="44" y="82" width="20" height="12" rx="3" fill="#0d9488"/>
                    <rect x="46" y="100" width="16" height="8" rx="2" fill="white" opacity="0.4"/>
                    <text x="54" y="125" font-size="8" fill="white" text-anchor="middle" font-weight="bold">Rx</text>
                    <!-- Pill bottle -->
                    <rect x="82" y="95" width="32" height="45" rx="8" fill="#14b8a6" opacity="0.85"/>
                    <rect x="84" y="86" width="28" height="13" rx="4" fill="#0f766e"/>
                    <circle cx="98" cy="115" r="7" fill="white" opacity="0.35"/>
                    <!-- Small bottle -->
                    <rect x="128" y="105" width="22" height="35" rx="5" fill="#2dd4bf" opacity="0.7"/>
                    <rect x="130" y="98" width="18" height="10" rx="3" fill="#0d9488"/>
                    <!-- Capsule pills scattered -->
                    <ellipse cx="170" cy="120" rx="18" ry="8" fill="#f59e0b" opacity="0.8" transform="rotate(-20 170 120)"/>
                    <ellipse cx="178" cy="116" rx="9" ry="8" fill="#fbbf24" opacity="0.9" transform="rotate(-20 178 116)"/>
                    <!-- Cross symbol -->
                    <rect x="200" y="75" width="48" height="48" rx="10" fill="white" opacity="0.9"/>
                    <rect x="218" y="83" width="12" height="32" rx="3" fill="#008080"/>
                    <rect x="208" y="93" width="32" height="12" rx="3" fill="#008080"/>
                    <!-- Small pills on shelf -->
                    <circle cx="50" cy="148" r="5" fill="#f59e0b" opacity="0.7"/>
                    <circle cx="65" cy="147" r="4" fill="#14b8a6" opacity="0.7"/>
                    <circle cx="78" cy="148" r="5" fill="#ef4444" opacity="0.6"/>
                    <!-- Stethoscope hint -->
                    <path d="M210 140 Q225 130 230 145 Q235 155 225 158 Q215 160 213 152" stroke="#0d9488" stroke-width="3" fill="none" opacity="0.6" stroke-linecap="round"/>
                    <circle cx="211" cy="151" r="4" fill="#0d9488" opacity="0.6"/>
                </svg>
            </div>
        </div>

        <!-- Right Form Side -->
        <div class="md:col-span-7 p-8 md:p-12 lg:p-14 flex flex-col justify-center bg-white">
            
            <div class="max-w-md w-full mx-auto">
                
                <!-- Form Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-slate-900 tracking-tight">Welcome Back!</h2>
                    <p class="text-slate-500 text-sm mt-1.5">Sign in to continue to your account</p>
                </div>

                <!-- Error Messages Alert -->
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-2xl text-sm mb-6 flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Login Form -->
                <form action="/login" method="POST" class="space-y-5">
                    @csrf

                    <!-- Email / Username Field -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Username / Email</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </span>
                            <input type="email" 
                                   name="email" 
                                   class="w-full pl-10 pr-4 py-3 bg-slate-50/50 rounded-xl border border-slate-200 focus:bg-white focus:border-[#008080] focus:ring-4 focus:ring-[#008080]/15 text-slate-800 placeholder-slate-400 text-sm outline-none transition" 
                                   required 
                                   placeholder="Enter your username or email"
                                   value="{{ old('email') }}">
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Password</label>
                        <div class="relative flex items-center">
                            <span class="absolute left-3.5 text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input type="password" 
                                   id="password-input"
                                   name="password" 
                                   class="w-full pl-10 pr-10 py-3 bg-slate-50/50 rounded-xl border border-slate-200 focus:bg-white focus:border-[#008080] focus:ring-4 focus:ring-[#008080]/15 text-slate-800 placeholder-slate-400 text-sm outline-none transition" 
                                   required 
                                   placeholder="Enter your password">
                            
                            <!-- Toggle Eye Button -->
                            <button type="button" 
                                    onclick="togglePasswordVisibility()" 
                                    class="absolute right-3.5 text-slate-400 hover:text-slate-600 focus:outline-none">
                                <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember me & Forgot Password row -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-[#008080] focus:ring-[#008080] accent-[#008080] cursor-pointer" checked>
                            <span class="text-sm font-medium text-slate-600">Remember me</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[#008080] hover:text-[#006666] hover:underline transition">Forgot Password?</a>
                    </div>

                    <!-- Main Sign In Button -->
                    <button type="submit" class="w-full bg-[#008080] hover:bg-[#006666] text-white py-3.5 rounded-xl font-semibold shadow-lg shadow-[#008080]/20 transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer text-base active:scale-[0.99]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        <span>Sign In</span>
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative flex items-center justify-center my-6">
                    <div class="border-t border-slate-200 w-full"></div>
                    <span class="bg-white px-4 text-xs font-semibold text-slate-400 absolute uppercase tracking-wider">or sign in with</span>
                </div>

                <!-- Google Firebase Sign In Button -->
                <button id="firebase-google-btn" 
                        onclick="window.loginWithFirebaseGoogle()" 
                        type="button" 
                        class="w-full flex items-center justify-center gap-3 bg-white border border-slate-300 text-slate-700 py-3.5 px-4 rounded-xl font-bold hover:bg-slate-50 hover:border-slate-400 transition shadow-sm cursor-pointer text-sm active:scale-[0.99]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                    </svg>
                    <span>Sign in with Google</span>
                </button>

                <!-- Dedicated Doctor Portal Access Link -->
                <div class="mt-5 pt-4 border-t border-slate-100 text-center">
                    <p class="text-xs text-slate-500 mb-1.5">Hospital Medical Doctor?</p>
                    <a href="{{ route('doctor.login') }}" class="inline-flex items-center gap-2 text-xs font-bold text-teal-800 bg-teal-50 hover:bg-teal-100 border border-teal-200/80 px-4 py-2 rounded-xl transition">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>Doctor Portal Login & Registration</span>
                        <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>

                <!-- Security Footer -->
                <div class="mt-6 text-center">
                    <div class="inline-flex items-center gap-1.5 text-[#008080] font-semibold text-xs bg-[#e6f4f1] px-3 py-1 rounded-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                        <span>Real Account Verification & Security</span>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <!-- Password Toggle Script -->
    <script>
        function togglePasswordVisibility() {
            const pwdInput = document.getElementById('password-input');
            const eyeIcon = document.getElementById('eye-icon');
            
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a9.957 9.957 0 014.122-.963c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m-6.165-4.152a3 3 0 10-4.243-4.243"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3l18 18"></path>
                `;
            } else {
                pwdInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }
    </script>

    @include('partials.firebase')
</body>
</html>