@extends('layouts.admin')

@section('content')
<div class="space-y-6">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/dashboard" class="hover:underline">Dashboard</a>
                <span>/</span>
                <span>System</span>
                <span>/</span>
                <span class="text-gray-500">Settings</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-gear text-blue-600"></i>
                <span>Settings</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Manage organization identity, defaults, and system preferences</p>
        </div>

        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fa-solid fa-circle text-[8px] text-emerald-500 mr-2"></i> Active Organization Profile
            </span>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl space-y-1 shadow-xs">
            <div class="flex items-center space-x-2 font-bold mb-1">
                <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc pl-5 text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- SETTINGS TABS & CONTENT LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        
        <!-- LEFT COLUMN: NAVIGATION CARDS / TABS (4 COLS) -->
        <div class="lg:col-span-4 space-y-3">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 space-y-1">
                
                <!-- 1. Account / Organization Profile (Active) -->
                <a href="{{ route('settings.index', ['tab' => 'profile']) }}" 
                   class="flex items-center justify-between p-3 rounded-lg text-sm font-semibold transition {{ $activeTab === 'profile' ? 'bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs' : 'text-gray-700 hover:bg-gray-50' }}">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg {{ $activeTab === 'profile' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center text-sm">
                            <i class="fa-solid fa-hospital"></i>
                        </div>
                        <div>
                            <span class="block leading-tight font-bold">Account Profile</span>
                            <span class="text-[11px] text-gray-400 font-normal">Organization identity & logo</span>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-right text-xs {{ $activeTab === 'profile' ? 'text-blue-600' : 'text-gray-300' }}"></i>
                </a>

                <!-- 2. User Account -->
                <a href="{{ route('admin.settings.users.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-100 text-gray-500 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <span class="block leading-tight font-bold">User Accounts</span>
                            <span class="text-[11px] text-gray-400 font-normal">Manage system staff & roles</span>
                        </div>
                    </div>
                    <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-0.5 rounded border border-blue-100">
                        Manage <i class="fa-solid fa-arrow-up-right-from-square ml-0.5"></i>
                    </span>
                </a>

                <!-- 3. Print & Token Settings (Placeholder) -->
                <div class="flex items-center justify-between p-3 rounded-lg text-sm font-medium text-gray-400 bg-gray-50/60 border border-dashed border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-200/70 text-gray-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-print"></i>
                        </div>
                        <div>
                            <span class="block leading-tight font-bold text-gray-600">Print & Token Settings</span>
                            <span class="text-[11px] text-gray-400">Thermal receipt & slip layouts</span>
                        </div>
                    </div>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                        Phase 3.6
                    </span>
                </div>

                <!-- 4. Security (Placeholder) -->
                <div class="flex items-center justify-between p-3 rounded-lg text-sm font-medium text-gray-400 bg-gray-50/60 border border-dashed border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-200/70 text-gray-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <span class="block leading-tight font-bold text-gray-600">Security</span>
                            <span class="text-[11px] text-gray-400">2FA & session management</span>
                        </div>
                    </div>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                        Planned
                    </span>
                </div>

                <!-- 5. Notifications (Placeholder) -->
                <div class="flex items-center justify-between p-3 rounded-lg text-sm font-medium text-gray-400 bg-gray-50/60 border border-dashed border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 rounded-lg bg-gray-200/70 text-gray-400 flex items-center justify-center text-sm">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div>
                            <span class="block leading-tight font-bold text-gray-600">Notifications</span>
                            <span class="text-[11px] text-gray-400">SMS, WhatsApp & email</span>
                        </div>
                    </div>
                    <span class="text-[9px] uppercase tracking-wider font-extrabold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                        Planned
                    </span>
                </div>
            </div>

            <!-- QUICK INFO CARD -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50/40 p-4 rounded-xl border border-blue-100 text-xs text-blue-900 space-y-2">
                <div class="font-bold flex items-center space-x-1.5 text-blue-800">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Centralized Identity</span>
                </div>
                <p class="text-blue-700 leading-relaxed">
                    Changes saved here automatically reflect across <strong>OPD Tokens</strong>, <strong>Patient Slips</strong>, <strong>Pharmacy Invoices</strong>, and <strong>Financial Reports</strong> without modifying historical transactional data.
                </p>
            </div>
        </div>

        <!-- RIGHT COLUMN: ACCOUNT / ORGANIZATION PROFILE FORM (8 COLS) -->
        <div class="lg:col-span-8">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                
                <div class="p-5 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-gray-800 text-base flex items-center space-x-2">
                            <i class="fa-solid fa-id-card-clip text-blue-600"></i>
                            <span>Account / Organization Profile</span>
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Primary business name, address, branding, and default financial settings</p>
                    </div>
                    <span class="text-[11px] font-mono text-gray-400">ID: #{{ $profile->id }}</span>
                </div>

                <form action="{{ route('settings.profile.update') }}" method="POST" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- SECTION 1: LOGO UPLOAD & PREVIEW (Section 6) -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                            Organization Logo
                        </label>

                        <div class="flex flex-col sm:flex-row items-center gap-6 p-4 rounded-xl border border-gray-200 bg-gray-50/40">
                            <!-- Logo Preview Box -->
                            <div class="w-32 h-32 rounded-xl border-2 border-dashed border-gray-300 bg-white flex items-center justify-center p-2 overflow-hidden shadow-2xs relative group shrink-0">
                                @if($profile->hasLogo())
                                    <img src="{{ $profile->logo_url }}" alt="Logo" class="max-h-full max-w-full object-contain">
                                @else
                                    <div class="text-center p-2">
                                        <i class="fa-solid fa-hospital text-3xl text-gray-300 mb-1 block"></i>
                                        <span class="text-[10px] text-gray-400 font-semibold uppercase block">No Logo</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Upload Controls -->
                            <div class="space-y-3 flex-1 text-center sm:text-left">
                                <div>
                                    <input 
                                        type="file" 
                                        id="logo_input" 
                                        name="logo" 
                                        accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                    />
                                    <p class="text-[11px] text-gray-400 mt-1.5">
                                        Allowed formats: PNG, JPG, WEBP, SVG. Max file size: 2MB. Transparent PNG recommended.
                                    </p>
                                </div>

                                @if($profile->hasLogo())
                                    <div class="flex items-center space-x-4 pt-1">
                                        <label class="inline-flex items-center space-x-2 text-xs font-semibold text-rose-600 hover:text-rose-700 cursor-pointer">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-rose-600 focus:ring-rose-500">
                                            <span>Remove existing logo on save</span>
                                        </label>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: BASIC ORGANIZATION DETAILS -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        
                        <!-- Account / Hospital Name (Required) -->
                        <div class="sm:col-span-2">
                            <label for="account_name" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Account / Organization Name <span class="text-rose-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="account_name" 
                                name="account_name" 
                                value="{{ old('account_name', $profile->account_name) }}" 
                                required 
                                placeholder="e.g. ABC Medical & Diagnostic Center"
                                class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3.5 py-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            />
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Contact Phone
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-phone text-xs"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="phone" 
                                    name="phone" 
                                    value="{{ old('phone', $profile->phone) }}" 
                                    placeholder="e.g. 0300-1234567"
                                    class="w-full text-sm rounded-lg border border-gray-300 pl-9 pr-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-mono"
                                />
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Official Email
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-envelope text-xs"></i>
                                </span>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    value="{{ old('email', $profile->email) }}" 
                                    placeholder="e.g. info@example.com"
                                    class="w-full text-sm rounded-lg border border-gray-300 pl-9 pr-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                            </div>
                        </div>

                        <!-- Street Address -->
                        <div class="sm:col-span-2">
                            <label for="address" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Street Address
                            </label>
                            <input 
                                type="text" 
                                id="address" 
                                name="address" 
                                value="{{ old('address', $profile->address) }}" 
                                placeholder="e.g. Plot 12, Main Boulevard, Sector 4"
                                class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            />
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                City
                            </label>
                            <input 
                                type="text" 
                                id="city" 
                                name="city" 
                                value="{{ old('city', $profile->city) }}" 
                                placeholder="e.g. Karachi / Lahore / Islamabad"
                                class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            />
                        </div>

                        <!-- Website -->
                        <div>
                            <label for="website" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Website
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                                    <i class="fa-solid fa-globe text-xs"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="website" 
                                    name="website" 
                                    value="{{ old('website', $profile->website) }}" 
                                    placeholder="e.g. https://www.example.com"
                                    class="w-full text-sm rounded-lg border border-gray-300 pl-9 pr-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                                />
                            </div>
                        </div>

                        <!-- Registration / License Number -->
                        <div>
                            <label for="registration_number" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Registration / License No.
                            </label>
                            <input 
                                type="text" 
                                id="registration_number" 
                                name="registration_number" 
                                value="{{ old('registration_number', $profile->registration_number) }}" 
                                placeholder="e.g. PHC-12345 / Drug Lic #998"
                                class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs font-mono"
                            />
                        </div>

                        <!-- Currency (Default: PKR) -->
                        <div>
                            <label for="currency" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Currency <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                id="currency" 
                                name="currency" 
                                required
                                class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            >
                                @foreach($currencies as $code => $label)
                                    <option value="{{ $code }}" {{ old('currency', $profile->currency) === $code ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Timezone (Default: Asia/Karachi) -->
                        <div class="sm:col-span-2">
                            <label for="timezone" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                System Timezone <span class="text-rose-500">*</span>
                            </label>
                            <select 
                                id="timezone" 
                                name="timezone" 
                                required
                                class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            >
                                @foreach($timezones as $tz => $tzLabel)
                                    <option value="{{ $tz }}" {{ old('timezone', $profile->timezone) === $tz ? 'selected' : '' }}>
                                        {{ $tzLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Governs date and time timestamps across OPD token generation and printable slips.
                            </p>
                        </div>

                        <!-- Footer Text -->
                        <div class="sm:col-span-2">
                            <label for="footer_text" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Document Footer Note / Slogan
                            </label>
                            <textarea 
                                id="footer_text" 
                                name="footer_text" 
                                rows="2" 
                                placeholder="e.g. Please wait for your token to be called. Thank you for visiting us."
                                class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs"
                            >{{ old('footer_text', $profile->footer_text) }}</textarea>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Rendered at the bottom of printed token slips, bills, and OPD documents.
                            </p>
                        </div>
                    </div>

                    <!-- FORM ACTION BUTTONS -->
                    <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="text-xs text-gray-400">
                            Last updated: {{ $profile->updated_at ? $profile->updated_at->format('d M Y, h:i A') : 'Never' }}
                        </div>

                        <button 
                            type="submit" 
                            class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-md shadow-blue-500/20 hover:shadow-lg transition cursor-pointer"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Changes</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>

</div>
@endsection
