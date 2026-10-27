<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Doctor Portal - {{ config('app.name', 'Pharmacy Management System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans antialiased text-gray-800" x-data="{ mobileMenuOpen: false }">

    <div class="flex h-screen overflow-hidden">
        
        <!-- MOBILE BACKDROP OVERLAY -->
        <div 
            x-show="mobileMenuOpen" 
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="mobileMenuOpen = false" 
            class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-xs md:hidden"
            x-cloak
        ></div>

        <!-- DOCTOR SIDEBAR -->
        <aside 
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col shadow-2xl transition-transform duration-300 ease-in-out md:static md:translate-x-0 shrink-0"
        >
            <!-- Brand Header -->
            <div class="p-4 sm:p-5 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="bg-gradient-to-tr from-teal-500 to-emerald-600 p-2.5 rounded-xl text-white shadow-md shadow-emerald-900/30">
                        <i class="fa-solid fa-user-doctor text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center space-x-1.5">
                            <h1 class="font-bold text-white text-base tracking-wide">DOCTOR</h1>
                            <span class="text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">Portal</span>
                        </div>
                        <p class="text-[10px] text-slate-400 tracking-wider">Clinical Workspace</p>
                    </div>
                </div>
                <button @click="mobileMenuOpen = false" class="md:hidden text-slate-400 hover:text-white p-2 focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            @php
                $currentDoctor = auth()->user()->doctor ?? null;
            @endphp

            <!-- Doctor Summary Mini Card -->
            @if($currentDoctor)
            <div class="mx-3 mt-4 p-3 rounded-xl bg-slate-800/80 border border-slate-700/80">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-lg bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold text-sm shrink-0 border border-teal-500/30">
                        Dr
                    </div>
                    <div class="truncate">
                        <p class="text-xs font-bold text-white truncate">{{ $currentDoctor->name }}</p>
                        <p class="text-[11px] text-teal-400 truncate">{{ $currentDoctor->specialization ?: 'Consultant' }}</p>
                    </div>
                </div>
                @if($currentDoctor->room_number)
                <div class="mt-2 pt-2 border-t border-slate-700/60 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Clinic Room:</span>
                    <span class="font-bold text-slate-200">{{ $currentDoctor->room_number }}</span>
                </div>
                @endif
            </div>
            @endif

            <!-- Navigation Links -->
            <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5 text-sm">
                <!-- CLINICAL WORKFLOW -->
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Clinical Care</p>
                    <div class="space-y-1">
                        <a href="{{ route('doctor.dashboard') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.dashboard') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-gauge w-5 text-center"></i>
                            <span>Dashboard</span>
                        </a>

                        <a href="{{ route('doctor.queue.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.queue.*') || request()->routeIs('doctor.consultations.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <div class="flex items-center space-x-3">
                                <i class="fa-solid fa-stethoscope w-5 text-center"></i>
                                <span>OPD Queue</span>
                            </div>
                            @if(isset($waitingQueueCount) && $waitingQueueCount > 0)
                                <span class="bg-amber-500 text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full shadow-xs">
                                    {{ $waitingQueueCount }}
                                </span>
                            @endif
                        </a>

                        <a href="{{ route('doctor.patients.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.patients.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-hospital-user w-5 text-center"></i>
                            <span>My Patients</span>
                        </a>

                        <a href="{{ route('doctor.prescriptions.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.prescriptions.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-prescription w-5 text-center"></i>
                            <span>Prescriptions</span>
                        </a>
                    </div>
                </div>

                <!-- FINANCIALS & SCHEDULE -->
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Finance & Practice</p>
                    <div class="space-y-1">
                        <a href="{{ route('doctor.earnings.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.earnings.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-chart-line w-5 text-center"></i>
                            <span>Earnings Overview</span>
                        </a>

                        <a href="{{ route('doctor.ledger.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.ledger.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-scale-balanced w-5 text-center"></i>
                            <span>Settlement Ledger</span>
                        </a>

                        <a href="{{ route('doctor.schedule.index') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.schedule.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-regular fa-calendar-check w-5 text-center"></i>
                            <span>Duty Schedule</span>
                        </a>

                        <a href="{{ route('doctor.profile.show') }}" @click="mobileMenuOpen = false" 
                           class="flex items-center space-x-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('doctor.profile.*') ? 'bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold shadow-md shadow-emerald-900/30' : 'hover:bg-slate-800 hover:text-white text-slate-300' }}">
                            <i class="fa-solid fa-id-card w-5 text-center"></i>
                            <span>My Profile & Password</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer User Section -->
            <div class="p-3 border-t border-slate-800 bg-slate-950/40">
                <form action="{{ route('doctor.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2 rounded-lg text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 text-xs font-semibold transition">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Sign Out of Portal</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- TOP APP BAR -->
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6 z-10 shrink-0">
                <div class="flex items-center space-x-3">
                    <button @click="mobileMenuOpen = true" class="md:hidden text-gray-500 hover:text-gray-700 p-2 focus:outline-none">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>
                    <div>
                        <span class="text-xs font-bold text-teal-700 bg-teal-50 px-2.5 py-1 rounded-full border border-teal-200 inline-flex items-center">
                            <i class="fa-solid fa-hospital text-teal-600 mr-1.5"></i> Doctor Portal
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <!-- Quick OPD Queue Link -->
                    <a href="{{ route('doctor.queue.index') }}" class="hidden sm:inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs transition">
                        <i class="fa-solid fa-play text-[10px]"></i>
                        <span>Start OPD Queue</span>
                    </a>

                    <!-- Current Time Display (Asia/Karachi) -->
                    <div class="hidden md:flex items-center space-x-2 text-xs text-gray-500 font-medium bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-200">
                        <i class="fa-regular fa-clock text-teal-600"></i>
                        <span>{{ format_app_datetime(now(), 'd M Y, h:i A') }}</span>
                    </div>

                    <!-- User Pill -->
                    <div class="flex items-center space-x-2 pl-2">
                        <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-teal-600 to-emerald-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <div class="hidden sm:block text-left">
                            <p class="text-xs font-bold text-gray-800 leading-tight">{{ auth()->user()->name }}</p>
                            <p class="text-[10px] text-teal-600 font-semibold leading-tight">Doctor Account</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- SCROLLABLE PAGE BODY -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50">
                <!-- SYSTEM NOTICES / FLASH ALERTS -->
                @if(session('success'))
                    <div class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                            <span class="font-semibold">{{ session('success') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs flex items-center justify-between shadow-xs">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-circle-exclamation text-rose-600 text-base"></i>
                            <span class="font-semibold">{{ session('error') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-5 p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-center justify-between shadow-xs">
                        <div class="flex items-center space-x-2.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                            <span class="font-semibold">{{ session('warning') }}</span>
                        </div>
                        <button type="button" onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-xs shadow-xs">
                        <p class="font-bold flex items-center space-x-2 mb-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                            <span>Please address the following errors:</span>
                        </p>
                        <ul class="list-disc list-inside space-y-0.5">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
