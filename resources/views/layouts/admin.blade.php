<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Pharmacy Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @livewireStyles
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-800" x-data="{ mobileMenuOpen: false }">

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

        <!-- SIDEBAR (DESKTOP + MOBILE DRAWER) -->
        <aside 
            :class="mobileMenuOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 flex flex-col shadow-2xl transition-transform duration-300 ease-in-out md:static md:translate-x-0 shrink-0"
        >
            <!-- Brand Logo & Mobile Close Button -->
            <div class="p-4 sm:p-5 flex items-center justify-between border-b border-slate-800">
                <div class="flex items-center space-x-3">
                    <div class="bg-blue-600 p-2 rounded-lg text-white">
                        <i class="fa-solid fa-pills text-lg"></i>
                    </div>
                    <div>
                        <h1 class="font-bold text-white text-base tracking-wide">PHARMACY</h1>
                        <p class="text-[10px] text-slate-400 tracking-wider uppercase">Management System</p>
                    </div>
                </div>
                <button @click="mobileMenuOpen = false" class="md:hidden text-slate-400 hover:text-white p-2 focus:outline-none">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Sidebar Links -->
            <div class="flex-1 overflow-y-auto px-4 py-6 space-y-6 text-sm">
                @php
                    $user = auth()->user();
                @endphp
                
                <!-- Main Dashboard -->
                @if(!$user || $user->hasPermission('view_dashboard'))
                <div>
                    <a href="/dashboard" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('dashboard') || request()->is('/') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                        <i class="fa-solid fa-house w-5"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                @endif

                <!-- MANAGE SECTION -->
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Manage</p>
                    <div class="space-y-1">
                        @if(!$user || $user->hasPermission('manage_medicines'))
                        <a href="/medicines" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('medicines*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-capsules w-5"></i>
                            <span>Products & Inventory</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_purchases'))
                        <a href="/purchases" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('purchases*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-cart-plus w-5"></i>
                            <span>Purchases</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_suppliers'))
                        <a href="/suppliers" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('suppliers*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-truck-field w-5"></i>
                            <span>Suppliers</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_returns'))
                        <a href="/returns" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('returns*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-arrow-rotate-left w-5"></i>
                            <span>Returns</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('process_pos'))
                        <a href="/pos" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('pos*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-cash-register w-5"></i>
                            <span>POS Counter</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_hold_invoices'))
                        <a href="/hold-invoices" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('hold-invoices*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-circle-pause w-5"></i>
                            <span>Hold Invoices</span>
                            @php
                                $heldCount = \App\Models\HoldInvoice::where('status', 'held')->count();
                            @endphp
                            @if($heldCount > 0)
                                <span class="ml-auto bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">{{ $heldCount }}</span>
                            @endif
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('view_sales'))
                        <a href="/sales" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('sales*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-receipt w-5"></i>
                            <span>Sales History</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('view_expiry'))
                        <a href="/expiry-alerts" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('expiry-alerts*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-triangle-exclamation w-5"></i>
                            <span>Expiry Alerts</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- HOSPITAL SECTION -->
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Hospital</p>
                    <div class="space-y-1">
                        @if(!$user || $user->hasPermission('manage_patients'))
                        <a href="/patients" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('patients*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-hospital-user w-5"></i>
                            <span>Patients</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_doctors'))
                        <a href="/doctors" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('doctors*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-user-doctor w-5"></i>
                            <span>Doctors</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_opd_tokens'))
                        <a href="/opd/tokens" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ (request()->is('opd/tokens*') || request()->is('patient-tokens*')) ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-ticket-simple w-5"></i>
                            <span>OPD Tokens</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_hospital_billing'))
                        <a href="/hospital-billing" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('hospital-billing*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-file-invoice-dollar w-5"></i>
                            <span>Hospital Billing</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_doctor_ledgers'))
                        <a href="/doctor-ledgers" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ (request()->is('doctor-ledgers*') || request()->is('doctors/*/ledger*') || request()->is('doctor-settlements*')) ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-scale-balanced w-5"></i>
                            <span>Doctor Ledgers</span>
                        </a>
                        @endif

                        @if(!$user || $user->hasPermission('manage_hospital_services'))
                        <a href="/hospital-services" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('hospital-services*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-hand-holding-medical w-5"></i>
                            <span>Hospital Services</span>
                        </a>
                        @endif
                    </div>
                </div>

                <!-- REPORTS SECTION -->
                @if(!$user || $user->hasPermission('view_reports'))
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">Reports</p>
                    <div class="space-y-1">
                        <a href="/reports" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('reports*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-chart-pie w-5"></i>
                            <span>Reports Hub</span>
                        </a>
                    </div>
                </div>
                @endif

                <!-- SYSTEM SECTION -->
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 px-3 mb-2">System</p>
                    <div class="space-y-1">
                        @if(!$user || $user->isAdmin())
                        <a href="{{ route('settings.index') }}" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('settings') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-gear w-5"></i>
                            <span>Settings</span>
                        </a>

                        <a href="{{ route('admin.settings.users.index') }}" @click="mobileMenuOpen = false" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg {{ request()->is('admin/settings/users*') || request()->is('settings/users*') ? 'bg-blue-600 text-white font-semibold' : 'hover:bg-slate-800 hover:text-white transition' }}">
                            <i class="fa-solid fa-users w-5"></i>
                            <span>User Management</span>
                        </a>
                        @endif
                        
                        <form action="/logout" method="POST">
                            @csrf
                            <button type="submit" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg hover:bg-red-600 hover:text-white transition text-left text-red-400">
                                <i class="fa-solid fa-right-from-bracket w-5"></i>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </aside>

        <!-- MAIN CONTENT WRAPPER -->
        <div class="flex-1 flex flex-col overflow-hidden min-w-0">
            
            <!-- TOPBAR -->
            <header class="bg-white shadow-xs border-b border-gray-200 h-16 flex items-center justify-between px-3 sm:px-6 z-10 shrink-0">
                <div class="flex items-center space-x-3 flex-1 max-w-md">
                    <!-- Hamburger Mobile Menu Toggle Button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-slate-600 hover:text-slate-900 transition p-2 rounded-lg hover:bg-slate-100 focus:outline-none" title="Toggle Navigation Menu">
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div class="relative w-full hidden sm:block">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fa-solid fa-search text-gray-400"></i>
                        </span>
                        <input type="text" placeholder="Search medicines, invoices..." class="w-full pl-10 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:bg-white focus:border-blue-500">
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    @if(!$user || $user->isAdmin())
                    <a href="{{ route('settings.index') }}" class="p-2 text-slate-500 hover:text-slate-700 transition hidden sm:block" title="System Settings">
                        <i class="fa-solid fa-gear text-lg"></i>
                    </a>
                    @endif

                    <div class="flex items-center space-x-3 border-l pl-3 sm:pl-4 border-gray-200">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold text-sm">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                        <div class="hidden md:block text-left">
                            <h4 class="text-xs font-bold text-gray-800">{{ auth()->user()->name ?? 'Guest User' }}</h4>
                            <p class="text-[10px] text-gray-500 uppercase">{{ auth()->user()->role ?? 'User' }}</p>
                        </div>
                    </div>
                </div>
            </header>

            <!-- PAGE CONTENT AREA -->
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-3 sm:p-6">
                {{ $slot ?? '' }}
                @yield('content')
            </main>

        </div>

    </div>

    @livewireScripts
    @include('partials.firebase')
</body>
</html>