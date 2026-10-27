<div class="min-h-screen bg-slate-50">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">

            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    Products & Inventory
                </h1>

                <p class="text-sm text-slate-500 mt-1">
                    Manage medicine and general store inventory items
                </p>
            </div>

            <div class="flex items-center gap-3 text-sm">
                <a
                    href="{{ route('medicines.bulk-add') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
                >
                    <i class="fa-solid fa-plus-square"></i>
                    <span>+ Bulk Add</span>
                </a>

                <a
                    href="{{ route('medicines.bulk-edit') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
                >
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Bulk Edit</span>
                </a>

                <a
                    href="/dashboard"
                    class="text-emerald-600 font-medium hover:text-emerald-700"
                >
                    Dashboard
                </a>

                <span class="text-slate-400">›</span>

                <span class="text-slate-500">
                    Products & Inventory
                </span>
            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- FLASH MESSAGE --}}
        {{-- ========================================================= --}}

        @if (session()->has('message'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-3 text-sm font-medium text-emerald-700">
                {{ session('message') }}
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- VALIDATION --}}
        {{-- ========================================================= --}}

        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm text-red-700">
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ========================================================= --}}
        {{-- ADD PRODUCT FORM --}}
        {{-- ========================================================= --}}

        <section class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">

            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-600"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14l-8 4m0 10L4 17V7m8 14V11m0 0L4 7m8 4l8-4"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Add Inventory Product
                        </h2>

                        <p class="text-xs text-slate-500">
                            Add a new medicine or general store item to inventory
                        </p>
                    </div>
                </div>

                <a
                    href="{{ route('medicines.bulk-add') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition"
                >
                    <i class="fa-solid fa-layer-group"></i>
                    <span>+ Bulk Add Medicines</span>
                </a>

            </div>

            {{-- Product Type Selector Tabs --}}
            <div class="px-5 pt-4 pb-2 border-b border-slate-100 bg-slate-50/50">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                    Select Product Type
                </label>
                <div class="flex flex-wrap items-center gap-3">
                    <button
                        type="button"
                        wire:click="setProductType('medicine')"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 {{ $product_type === 'medicine' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100' }}"
                    >
                        <i class="fa-solid fa-capsules text-base"></i>
                        <span>Medicine</span>
                    </button>

                    <button
                        type="button"
                        wire:click="setProductType('general')"
                        class="px-4 py-2 rounded-lg text-sm font-bold transition flex items-center gap-2 {{ $product_type === 'general' ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100' }}"
                    >
                        <i class="fa-solid fa-store text-base"></i>
                        <span>General Store Item</span>
                    </button>
                </div>
            </div>


            <form wire:submit="save" class="p-5">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">

                    {{-- Product Name (with Auto-Suggestions) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                                <span>Product Name <span class="text-red-500">*</span></span>
                                @if($verified_product_match)
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-800 text-[9px] font-bold px-2 py-0.5 rounded border border-emerald-200">
                                        <i class="fa-solid fa-circle-check"></i> Verified DB Product
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-800 text-[9px] font-bold px-2 py-0.5 rounded border border-amber-200">
                                        <i class="fa-solid fa-triangle-exclamation"></i> New / Unverified Product
                                    </span>
                                @endif
                            </label>
                            <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-wand-magic-sparkles text-[9px] text-emerald-500"></i> Auto-suggest
                            </span>
                        </div>

                        <div class="relative" @click.outside="$wire.showProductSuggestions = false">
                            <input
                                type="text"
                                wire:model.live.debounce.1000ms="product_search"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="{{ $product_type === 'general' ? 'e.g. Lux Soap 100g / Dettol Soap / Colgate' : 'e.g. Panadol 500mg / Augmentin 625mg' }}"
                                autocomplete="off"
                            >
                            
                            @if($showProductSuggestions && count($this->suggestedProducts) > 0)
                                <div class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto divide-y divide-slate-100 py-1">
                                    <div class="px-3 py-1.5 bg-emerald-50 border-b border-emerald-100 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-emerald-600">
                                        <span><i class="fa-solid fa-check-circle mr-1"></i> Verified DB Products</span>
                                        <span>{{ count($this->suggestedProducts) }} match(es)</span>
                                    </div>
                                    @foreach($this->suggestedProducts as $prod)
                                        <button
                                            type="button"
                                            wire:click="selectProduct({{ $prod->id }}, '{{ addslashes($prod->name) }}')"
                                            class="w-full text-left px-3.5 py-2.5 hover:bg-emerald-50/80 flex flex-col justify-center text-sm transition group text-slate-700"
                                        >
                                            <div class="flex items-center justify-between w-full">
                                                <div class="flex items-center gap-2 font-bold">
                                                    <i class="fa-solid fa-pills text-xs text-emerald-600 group-hover:scale-110 transition-transform"></i>
                                                    <span>{{ $prod->name }}</span>
                                                    @if($prod->strength)
                                                        <span class="text-xs text-slate-500 font-medium"> {{ $prod->strength }}</span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-1">
                                                    @if($prod->dosage_form || $prod->dosage_unit)
                                                        <span class="text-[10px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded border border-slate-200 font-bold uppercase tracking-wider">
                                                            {{ $prod->dosage_form ?: $prod->dosage_unit }}
                                                        </span>
                                                    @endif
                                                    <span class="text-[9px] bg-emerald-100 text-emerald-700 px-1.5 py-0.5 rounded border border-emerald-200 font-bold uppercase ml-1">
                                                        <i class="fa-solid fa-check"></i> Verified
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-1 pl-5 flex items-center gap-2">
                                                <span>{{ $prod->manufacturer ?: ($prod->brand ?: 'Unknown Manufacturer') }}</span>
                                                @if($prod->barcode)
                                                    <span>&bull; Barcode: {{ $prod->barcode }}</span>
                                                @endif
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif($showProductSuggestions && $ai_suggestion)
                                <div class="absolute z-50 left-0 right-0 mt-1 bg-white border border-indigo-200 rounded-xl shadow-xl max-h-60 overflow-y-auto divide-y divide-indigo-100 py-1">
                                    <div class="px-3 py-1.5 bg-indigo-50 border-b border-indigo-100 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-indigo-600">
                                        <span><i class="fa-solid fa-wand-magic-sparkles mr-1"></i> AI Suggestion (Not Verified)</span>
                                        <span>Confidence: {{ $ai_suggestion['confidence'] ?? 0 }}%</span>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="applyAiSuggestion"
                                        class="w-full text-left px-3.5 py-3 hover:bg-indigo-50/50 flex flex-col justify-center text-sm transition group text-slate-700"
                                    >
                                        <div class="flex items-center justify-between w-full">
                                            <div class="flex items-center gap-2 font-bold text-indigo-900">
                                                <i class="fa-solid fa-robot text-xs text-indigo-500 group-hover:scale-110 transition-transform"></i>
                                                <span>{{ $ai_suggestion['normalized_name'] ?? 'Unknown' }}</span>
                                                @if(!empty($ai_suggestion['strength']))
                                                    <span class="text-xs text-indigo-400 font-medium"> {{ $ai_suggestion['strength'] }}</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-1">
                                                @if(!empty($ai_suggestion['dosage_form']))
                                                    <span class="text-[10px] bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded border border-indigo-200 font-bold uppercase tracking-wider">
                                                        {{ $ai_suggestion['dosage_form'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-[10px] text-slate-500 mt-1.5 pl-5 flex items-center gap-2">
                                            <span>Mfr: {{ $ai_suggestion['manufacturer'] ?? ($ai_suggestion['brand'] ?? 'Unknown') }}</span>
                                        </div>
                                        <div class="mt-2 text-[10px] text-center text-slate-400 bg-slate-50 py-1 rounded">
                                            Click to auto-fill these fields (requires manual review)
                                        </div>
                                    </button>
                                    <div class="px-3 py-2 bg-slate-50 border-t border-indigo-100 text-center">
                                        <button type="button" wire:click="enableManualEntry" class="w-full text-center text-xs font-bold text-slate-500 hover:text-slate-700 transition">
                                            <i class="fa-solid fa-xmark mr-1"></i> Skip & Enter Manually
                                        </button>
                                    </div>
                                </div>
                            @elseif($showProductSuggestions && count($this->suggestedProducts) === 0 && strlen($product_search) >= 2)
                                <div class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-3 text-center text-sm text-slate-500">
                                    No existing product found in database.
                                    <div class="mt-2 space-y-2">
                                        <button type="button" wire:click="askAiForProduct" class="bg-blue-100 text-blue-700 hover:bg-blue-200 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center w-full gap-2">
                                            <i class="fa-solid fa-robot"></i> Ask AI to Auto-Fill Details
                                        </button>
                                        <button type="button" wire:click="enableManualEntry" class="bg-slate-100 text-slate-700 hover:bg-slate-200 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center justify-center w-full gap-2">
                                            <i class="fa-solid fa-pen"></i> Enter Manually
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <div wire:loading wire:target="product_search" class="absolute right-3 top-2.5">
                                <i class="fa-solid fa-spinner fa-spin text-slate-400 text-sm"></i>
                            </div>
                            <div wire:loading wire:target="askAiForProduct" class="absolute right-3 top-2.5">
                                <i class="fa-solid fa-spinner fa-spin text-blue-500 text-sm"></i>
                            </div>
                        </div>
                        
                        @if (session()->has('ai_error'))
                            <div class="mt-1 text-xs text-red-500 font-medium">
                                <i class="fa-solid fa-circle-exclamation"></i> {{ session('ai_error') }}
                            </div>
                        @endif
                        @if (session()->has('ai_success'))
                            <div class="mt-1 text-xs text-blue-600 font-medium">
                                <i class="fa-solid fa-check-circle"></i> {{ session('ai_success') }}
                            </div>
                        @endif
                    </div>

                    {{-- Category (Smart Searchable Auto-Suggest Dropdown & Quick Create) --}}
                    <div 
                        wire:key="category-picker-{{ $product_type }}-{{ $category_id }}"
                        x-data="{
                            open: false,
                            search: @js((string)$category_search),
                            selectedId: @js((string)$category_id),
                            categories: {{ Js::from($formCategories->map(fn($c) => ['id' => (string)$c->id, 'name' => $c->name, 'type' => $c->product_type ?? 'both'])) }},
                            get filtered() {
                                if (!this.search || typeof this.search !== 'string' || this.search.trim() === '') return this.categories;
                                const q = this.search.toLowerCase().trim();
                                return this.categories.filter(c => c.name.toLowerCase().includes(q));
                            },
                            get exactMatch() {
                                if (!this.search || typeof this.search !== 'string') return null;
                                const q = this.search.toLowerCase().trim();
                                return this.categories.find(c => c.name.toLowerCase() === q);
                            },
                            select(cat) {
                                this.selectedId = cat.id;
                                this.search = cat.name;
                                $wire.selectCategory(cat.id, cat.name);
                                this.open = false;
                            },
                            clear() {
                                this.selectedId = '';
                                this.search = '';
                                $wire.clearCategory();
                                this.open = false;
                            },
                            createCategory() {
                                if (this.search && typeof this.search === 'string' && this.search.trim() !== '') {
                                    $wire.quickCreateCategory(this.search.trim());
                                    this.open = false;
                                }
                            }
                        }"
                        class="relative"
                        @click.outside="open = false"
                    >
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-slate-700">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[11px] font-semibold text-emerald-600 flex items-center gap-1">
                                <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i> Auto-Suggest
                            </span>
                        </div>

                        <div class="relative">
                            <input
                                type="text"
                                x-model="search"
                                @focus="open = true"
                                @input="open = true"
                                @keydown.escape="open = false"
                                placeholder="Type or select category..."
                                class="w-full h-10 pl-3 pr-16 border rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400 {{ $errors->has('category_id') ? 'border-red-300 ring-1 ring-red-300' : 'border-slate-200' }}"
                                autocomplete="off"
                            >

                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                <template x-if="search && search.length > 0">
                                    <button 
                                        type="button" 
                                        @click="clear()" 
                                        class="p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-slate-100"
                                        title="Clear Category"
                                    >
                                        <i class="fa-solid fa-xmark text-xs"></i>
                                    </button>
                                </template>
                                <button 
                                    type="button" 
                                    @click="open = !open" 
                                    class="p-1 text-slate-400 hover:text-slate-600"
                                    title="Toggle Dropdown"
                                >
                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Hidden Input to retain exact Livewire Category binding --}}
                        <input type="hidden" wire:model="category_id" value="{{ $category_id }}">

                        {{-- Dynamic Suggestion Dropdown Panel --}}
                        <div 
                            x-show="open" 
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto divide-y divide-slate-100 py-1"
                            style="display: none;"
                        >
                            {{-- Header Indicator --}}
                            <div class="px-3 py-1.5 bg-slate-50 flex items-center justify-between text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <span>Suggested Categories</span>
                                <span x-text="filtered.length + ' available'"></span>
                            </div>

                            {{-- Filtered Suggestions List --}}
                            <template x-for="cat in filtered" :key="cat.id">
                                <button
                                    type="button"
                                    @click="select(cat)"
                                    class="w-full text-left px-3.5 py-2.5 hover:bg-emerald-50/80 flex items-center justify-between text-sm transition group"
                                    :class="String(selectedId) === String(cat.id) ? 'bg-emerald-50 font-bold text-emerald-900' : 'text-slate-700'"
                                >
                                    <div class="flex items-center gap-2">
                                        <i class="fa-solid fa-tag text-xs text-emerald-600 group-hover:scale-110 transition-transform"></i>
                                        <span x-text="cat.name"></span>
                                    </div>
                                    <template x-if="String(selectedId) === String(cat.id)">
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-600 bg-emerald-100 px-2 py-0.5 rounded-full">
                                            <i class="fa-solid fa-check text-[10px]"></i> Selected
                                        </span>
                                    </template>
                                </button>
                            </template>

                            {{-- No Match / Quick Add New Category Option --}}
                            <template x-if="filtered.length === 0 && search && search.trim() !== ''">
                                <div class="p-3 text-center">
                                    <p class="text-xs text-slate-500 mb-2">
                                        No matching category found for "<span class="font-bold text-slate-700" x-text="search"></span>"
                                    </p>
                                    <button
                                        type="button"
                                        @click="createCategory()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-xs transition"
                                    >
                                        <i class="fa-solid fa-plus-circle"></i>
                                        <span>Add "<span x-text="search"></span>" as New Category</span>
                                    </button>
                                </div>
                            </template>

                            {{-- Quick Add Option if typed name doesn't exactly match any existing --}}
                            <template x-if="filtered.length > 0 && search && search.trim() !== '' && !exactMatch">
                                <div class="p-2 bg-emerald-50/50 border-t border-emerald-100">
                                    <button
                                        type="button"
                                        @click="createCategory()"
                                        class="w-full text-left px-3 py-1.5 bg-white hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold text-emerald-800 flex items-center justify-between transition"
                                    >
                                        <span class="flex items-center gap-1.5">
                                            <i class="fa-solid fa-plus text-emerald-600"></i>
                                            <span>Create & Select: "<span x-text="search"></span>"</span>
                                        </span>
                                        <span class="text-[10px] bg-emerald-600 text-white px-1.5 py-0.5 rounded">New Category</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Brand / Company (with Auto-Suggestions) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-slate-700">
                                Brand / Company
                            </label>
                            <span class="text-[10px] text-slate-400 font-medium">Auto-suggest</span>
                        </div>

                        <input
                            type="text"
                            list="brand-suggestions"
                            wire:model="brand"
                            class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                            placeholder="{{ $product_type === 'general' ? 'e.g. Unilever / Lux / Nestle' : 'e.g. GSK / Abbott / Getz' }}"
                            autocomplete="off"
                        >
                    </div>

                    @if($product_type === 'medicine')
                        {{-- Generic Name (with Auto-Suggestions) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-slate-700">
                                    Generic Name
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Auto-suggest</span>
                            </div>

                            <input
                                type="text"
                                list="generic-suggestions"
                                wire:model="generic_name"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. Paracetamol / Ibuprofen / Amoxicillin"
                                autocomplete="off"
                            >
                        </div>

                        {{-- Manufacturer (with Auto-Suggestions) --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-slate-700">
                                    Manufacturer
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Auto-suggest</span>
                            </div>

                            <input
                                type="text"
                                list="manufacturer-suggestions"
                                wire:model="manufacturer"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. GlaxoSmithKline / Abbott / Getz Pharma"
                                autocomplete="off"
                            >
                        </div>

                        {{-- Strength & Dosage Form --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-slate-700">
                                    Strength
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">e.g. 500mg</span>
                            </div>

                            <input
                                type="text"
                                list="strength-suggestions"
                                wire:model="strength"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. 500mg, 250mg, 10mg"
                                autocomplete="off"
                            >
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-sm font-medium text-slate-700">
                                    Dosage Form
                                </label>
                                <span class="text-[10px] text-slate-400 font-medium">Auto-suggests Unit</span>
                            </div>

                            <input
                                type="text"
                                list="dosage-form-suggestions"
                                wire:model.live.debounce.200ms="dosage_form"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. Tablet, Capsule, Syrup, Injection"
                                autocomplete="off"
                            >
                        </div>
                    @endif

                    {{-- Initial Quantity & Unit Selection (with Auto-Suggestions & Quick Presets) --}}
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-slate-700">
                                Initial Stock Quantity <span class="text-red-500">*</span>
                            </label>
                            <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                <i class="fa-solid fa-wand-magic-sparkles text-[9px] text-emerald-500"></i> Auto-suggest
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <input
                                type="number"
                                min="0"
                                step="any"
                                list="stock-qty-suggestions"
                                wire:model.live="quantity"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. 10"
                                autocomplete="off"
                            >

                            <select
                                wire:model.live="initial_stock_unit"
                                class="w-full h-10 px-2 border border-slate-200 rounded-lg bg-white text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                            >
                                <optgroup label="📦 Active Hierarchy Units">
                                    <option value="base">{{ $base_unit ?: 'Base Unit' }} (1x Base)</option>
                                    @if(!empty($secondary_unit))
                                        <option value="secondary">{{ $secondary_unit }} ({{ $this->calculatedSecondaryConversion }}x Base)</option>
                                    @endif
                                    @if(!empty($primary_unit))
                                        <option value="primary">{{ $primary_unit }} ({{ $this->calculatedPrimaryConversion }}x Base)</option>
                                    @endif
                                </optgroup>

                                <optgroup label="✨ Select / Switch Unit">
                                    @foreach($availableUnits as $unit)
                                        @if($unit->name !== $base_unit && $unit->name !== $secondary_unit && $unit->name !== $primary_unit)
                                            <option value="{{ $unit->name }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>

                        {{-- Quick Suggestion Pills --}}
                        <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-0.5">Quick:</span>
                            @foreach(['5', '10', '20', '50', '100', '200', '500'] as $qtyPreset)
                                <button 
                                    type="button" 
                                    wire:click="$set('quantity', '{{ $qtyPreset }}')" 
                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border transition {{ (string)$quantity === (string)$qtyPreset ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs' : 'bg-slate-100 border-slate-200 text-slate-600 hover:bg-emerald-50 hover:text-emerald-700 hover:border-emerald-200' }}"
                                >
                                    {{ $qtyPreset }}
                                </button>
                            @endforeach
                        </div>

                        @if((float)$quantity > 0)
                            @php
                                $cMultiplier = 1;
                                if ($initial_stock_unit === 'primary') $cMultiplier = $this->calculatedPrimaryConversion;
                                elseif ($initial_stock_unit === 'secondary') $cMultiplier = $this->calculatedSecondaryConversion;
                                $calcBaseQty = ((float)$quantity) * $cMultiplier;
                            @endphp
                            <p class="text-[11px] text-emerald-700 font-medium mt-1">
                                = {{ number_format($calcBaseQty) }} {{ $base_unit ?: 'Tablet' }}s stored in Inventory
                            </p>
                        @endif
                    </div>

                    {{-- Purchase Price (Base Unit default) --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Base Unit Purchase Price (PKR) <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live="purchase_price"
                            class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                            placeholder="e.g. 8"
                        >
                    </div>

                    {{-- Selling Price (Base Unit default) --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Base Unit Selling Price (PKR) <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            wire:model.live="selling_price"
                            class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                            placeholder="e.g. 12"
                        >
                    </div>

                    {{-- Alert Quantity --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Low Stock Alert Qty (Base Units)
                        </label>

                        <input
                            type="number"
                            min="0"
                            wire:model="alert_quantity"
                            class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                            placeholder="10"
                        >
                    </div>

                    {{-- Supplier --}}
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Supplier
                        </label>

                        <select
                            wire:model="supplier_id"
                            class="w-full h-10 px-3 border border-slate-200 rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                        >
                            <option value="">
                                Select Supplier
                            </option>

                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Expiry & Batch Options for General Items --}}
                    @if($product_type === 'general')
                        <div class="col-span-full grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-3 rounded-lg border border-slate-200 my-1">
                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700">
                                <input type="checkbox" wire:model.live="has_expiry" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                                <span>Track Expiry Date for this product</span>
                            </label>

                            <label class="flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700">
                                <input type="checkbox" wire:model.live="track_batches" class="rounded text-blue-600 focus:ring-blue-500 h-4 w-4">
                                <span>Track Batch Numbers for this product</span>
                            </label>
                        </div>
                    @endif

                    {{-- Expiry Date --}}
                    @if($product_type === 'medicine' || $has_expiry)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Expiry Date {{ ($product_type === 'medicine' || $has_expiry) ? '*' : '' }}
                            </label>

                            <input
                                type="date"
                                wire:model="expiry_date"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                            >
                        </div>
                    @endif

                    {{-- Batch Number --}}
                    @if($product_type === 'medicine' || $track_batches)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Batch Number {{ $product_type === 'medicine' ? '*' : '(Optional)' }}
                            </label>

                            <input
                                type="text"
                                wire:model="batch_number"
                                class="w-full h-10 px-3 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400"
                                placeholder="e.g. BATCH-001"
                            >
                        </div>
                    @endif

                </div>

                {{-- ========================================================= --}}
                {{-- MEDICINE UNITS & PACKAGING HIERARCHY (FOR MEDICINE) --}}
                {{-- ========================================================= --}}
                @if($product_type === 'medicine')
                    <div class="mt-6 pt-5 border-t border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                                    <i class="fa-solid fa-boxes-stacked text-emerald-600"></i>
                                    Medicine Packaging & Unit Hierarchy
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Configure multi-level packaging units. Inventory is always stored in Base Units.
                                </p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-xs font-semibold">
                                <i class="fa-solid fa-shield-check"></i> Base Unit Stock Architecture
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            {{-- 1. BASE UNIT --}}
                            <div class="bg-emerald-50/50 p-4 rounded-xl border-2 border-emerald-300 relative shadow-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                                        <i class="fa-solid fa-circle-dot text-emerald-600 text-[10px]"></i> Base Unit (Level 1)
                                    </span>
                                    <span class="px-2 py-0.5 bg-emerald-600 text-white text-[10px] font-bold rounded-md">1 Base Unit</span>
                                </div>

                                <div class="space-y-3">
                                    {{-- Base Unit Searchable Suggestion Dropdown --}}
                                    <div 
                                        x-data="{
                                            open: false,
                                            query: '',
                                            unitValue: @entangle('base_unit').live,
                                            standardUnits: [
                                                { name: 'Tablet', symbol: 'Tab', type: 'Solid Dose' },
                                                { name: 'Capsule', symbol: 'Cap', type: 'Solid Dose' },
                                                { name: 'Bottle', symbol: 'Btl', type: 'Liquid' },
                                                { name: 'Vial', symbol: 'Vial', type: 'Injectable' },
                                                { name: 'Ampoule', symbol: 'Amp', type: 'Injectable' },
                                                { name: 'Tube', symbol: 'Tb', type: 'Cream / Gel' },
                                                { name: 'Sachet', symbol: 'Sach', type: 'Powder' },
                                                { name: 'Piece', symbol: 'Pc', type: 'Item' },
                                                { name: 'Strip', symbol: 'Str', type: 'Packaging' },
                                                { name: 'Pack', symbol: 'Pk', type: 'Packaging' },
                                                { name: 'Box', symbol: 'Bx', type: 'Packaging' },
                                                { name: 'Drop', symbol: 'Drp', type: 'Liquid' },
                                                { name: 'Suppository', symbol: 'Sup', type: 'Solid' },
                                                { name: 'Inhaler', symbol: 'Inh', type: 'Inhaler' },
                                                { name: 'Spray', symbol: 'Spry', type: 'Spray' },
                                                { name: 'Milliliter', symbol: 'ml', type: 'Volume' },
                                                { name: 'Liter', symbol: 'L', type: 'Volume' },
                                                { name: 'Gram', symbol: 'g', type: 'Weight' },
                                                { name: 'Milligram', symbol: 'mg', type: 'Weight' }
                                            ],
                                            get allUnits() {
                                                const dbUnits = {{ Js::from($availableUnits->map(fn($u) => ['name' => $u->name, 'symbol' => $u->symbol ?? ''])) }};
                                                const map = new Map();
                                                this.standardUnits.forEach(u => map.set(u.name.toLowerCase(), u));
                                                dbUnits.forEach(u => {
                                                    if (!map.has(u.name.toLowerCase())) {
                                                        map.set(u.name.toLowerCase(), { name: u.name, symbol: u.symbol || '', type: 'Unit' });
                                                    }
                                                });
                                                return Array.from(map.values());
                                            },
                                            get filteredUnits() {
                                                if (!this.query || this.query.trim() === '') {
                                                    return this.allUnits;
                                                }
                                                const q = this.query.toLowerCase().trim();
                                                return this.allUnits.filter(u => 
                                                    u.name.toLowerCase().includes(q) || 
                                                    (u.symbol && u.symbol.toLowerCase().includes(q)) ||
                                                    (u.type && u.type.toLowerCase().includes(q))
                                                );
                                            },
                                            selectUnit(name) {
                                                this.unitValue = name;
                                                $wire.set('base_unit', name);
                                                this.query = '';
                                                this.open = false;
                                            },
                                            clear() {
                                                this.unitValue = '';
                                                this.query = '';
                                                $wire.set('base_unit', '');
                                                this.open = true;
                                            }
                                        }"
                                        class="relative"
                                        @click.outside="open = false"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700">
                                                Base Unit Name <span class="text-red-500">*</span>
                                            </label>
                                            <span class="text-[10px] text-emerald-700 font-semibold flex items-center gap-1">
                                                <i class="fa-solid fa-wand-magic-sparkles text-[9px] text-emerald-600"></i> Auto-Suggest
                                            </span>
                                        </div>

                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                :value="open ? (query !== '' ? query : (unitValue || '')) : (unitValue || '')"
                                                @focus="open = true; query = '';" 
                                                @input="open = true; query = $event.target.value; unitValue = $event.target.value; $wire.set('base_unit', $event.target.value);"
                                                @keydown.escape="open = false"
                                                class="w-full h-9 pl-3 pr-16 border border-emerald-300 rounded-lg text-sm bg-white font-medium focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500 shadow-2xs cursor-pointer" 
                                                placeholder="Select or type unit (e.g. Tablet, Capsule)"
                                                autocomplete="off"
                                            >
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                                                <template x-if="unitValue && unitValue.length > 0">
                                                    <button 
                                                        type="button" 
                                                        @click="clear()" 
                                                        class="p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-emerald-100/50"
                                                        title="Clear"
                                                    >
                                                        <i class="fa-solid fa-xmark text-xs"></i>
                                                    </button>
                                                </template>
                                                <button 
                                                    type="button" 
                                                    @click="open = !open; if(open) query = '';" 
                                                    class="p-1 text-emerald-700 hover:text-emerald-900 rounded-md hover:bg-emerald-100 transition"
                                                    title="Toggle Suggestions"
                                                >
                                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Floating Dropdown Menu --}}
                                        <div 
                                            x-show="open" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-emerald-200 rounded-xl shadow-xl max-h-64 overflow-hidden divide-y divide-slate-100 py-1"
                                            style="display: none;"
                                        >
                                            <div class="px-3 py-1.5 bg-emerald-50/80 flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-emerald-800 border-b border-emerald-100">
                                                <span>Suggested Units</span>
                                                <span x-text="filteredUnits.length + ' Available'"></span>
                                            </div>

                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50">
                                                <template x-for="u in filteredUnits" :key="u.name">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(u.name)"
                                                        class="w-full text-left px-3 py-2 hover:bg-emerald-50 flex items-center justify-between text-xs transition group"
                                                        :class="unitValue && unitValue.toLowerCase() === u.name.toLowerCase() ? 'bg-emerald-50 font-bold text-emerald-900' : 'text-slate-700'"
                                                    >
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-5 h-5 rounded-md bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">
                                                                <i class="fa-solid fa-cube text-[9px]"></i>
                                                            </span>
                                                            <div>
                                                                <span x-text="u.name" class="font-medium"></span>
                                                                <template x-if="u.type">
                                                                    <span class="text-[10px] text-slate-400 ml-1.5 font-normal" x-text="'(' + u.type + ')'"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <template x-if="u.symbol">
                                                                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono" x-text="u.symbol"></span>
                                                            </template>
                                                            <template x-if="unitValue && unitValue.toLowerCase() === u.name.toLowerCase()">
                                                                <i class="fa-solid fa-check text-emerald-600 text-xs"></i>
                                                            </template>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>

                                            <template x-if="filteredUnits.length === 0 && query && query.trim() !== ''">
                                                <div class="p-2.5 text-center">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(query.trim())"
                                                        class="w-full text-left px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-xs font-bold text-emerald-800 flex items-center justify-between transition"
                                                    >
                                                        <span class="flex items-center gap-1.5">
                                                            <i class="fa-solid fa-plus text-emerald-600"></i>
                                                            <span>Use custom unit: "<span x-text="query"></span>"</span>
                                                        </span>
                                                        <span class="text-[10px] bg-emerald-600 text-white px-1.5 py-0.5 rounded">Custom</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Quick Suggested Pills --}}
                                        <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-0.5">Quick:</span>
                                            @foreach(['Tablet', 'Capsule', 'Bottle', 'Vial', 'Ampoule', 'Tube', 'Sachet', 'Piece'] as $unitPill)
                                                <button 
                                                    type="button" 
                                                    @click="selectUnit('{{ $unitPill }}')" 
                                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border transition {{ $base_unit === $unitPill ? 'bg-emerald-600 text-white border-emerald-600 shadow-2xs' : 'bg-white border-emerald-200 text-emerald-800 hover:bg-emerald-100' }}"
                                                >
                                                    {{ $unitPill }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Purchase Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model.live="base_unit_purchase_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="{{ $purchase_price ?: '8' }}">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Sale Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model.live="base_unit_selling_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white font-bold text-emerald-700" placeholder="{{ $selling_price ?: '12' }}">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Base Barcode (Optional)</label>
                                        <input type="text" wire:model="base_unit_barcode" class="w-full h-8 px-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="Scan or enter barcode">
                                    </div>
                                </div>
                            </div>

                            {{-- 2. SECONDARY UNIT --}}
                            <div class="bg-blue-50/40 p-4 rounded-xl border border-blue-200 relative shadow-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-blue-800 flex items-center gap-1.5">
                                        <i class="fa-solid fa-arrow-up text-blue-500 text-[10px]"></i> Secondary Unit (Level 2)
                                    </span>
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-800 text-[10px] font-bold rounded-md">
                                        = {{ $this->calculatedSecondaryConversion }} {{ $base_unit ?: 'Tablets' }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    {{-- Secondary Unit Searchable Suggestion Dropdown --}}
                                    <div 
                                        x-data="{
                                            open: false,
                                            query: '',
                                            unitValue: @entangle('secondary_unit').live,
                                            standardUnits: [
                                                { name: 'Strip', symbol: 'Str', type: 'Intermediate Pack' },
                                                { name: 'Blister', symbol: 'Bls', type: 'Blister Pack' },
                                                { name: 'Pack', symbol: 'Pk', type: 'Multi-dose Pack' },
                                                { name: 'Box', symbol: 'Bx', type: 'Intermediate Box' },
                                                { name: 'Bottle', symbol: 'Btl', type: 'Liquid / Syrup' },
                                                { name: 'Ampoule', symbol: 'Amp', type: 'Injectable' },
                                                { name: 'Tube', symbol: 'Tb', type: 'Cream / Ointment' },
                                                { name: 'Vial', symbol: 'Vial', type: 'Injectable' },
                                                { name: 'Sachet', symbol: 'Sach', type: 'Powder Pack' },
                                                { name: 'Carton', symbol: 'Ctn', type: 'Outer Pack' },
                                                { name: 'Piece', symbol: 'Pc', type: 'Single Piece' }
                                            ],
                                            get allUnits() {
                                                const dbUnits = {{ Js::from($availableUnits->map(fn($u) => ['name' => $u->name, 'symbol' => $u->symbol ?? ''])) }};
                                                const map = new Map();
                                                this.standardUnits.forEach(u => map.set(u.name.toLowerCase(), u));
                                                dbUnits.forEach(u => {
                                                    if (!map.has(u.name.toLowerCase())) {
                                                        map.set(u.name.toLowerCase(), { name: u.name, symbol: u.symbol || '', type: 'Packaging' });
                                                    }
                                                });
                                                return Array.from(map.values());
                                            },
                                            get filteredUnits() {
                                                if (!this.query || this.query.trim() === '') {
                                                    return this.allUnits;
                                                }
                                                const q = this.query.toLowerCase().trim();
                                                return this.allUnits.filter(u => 
                                                    u.name.toLowerCase().includes(q) || 
                                                    (u.symbol && u.symbol.toLowerCase().includes(q)) ||
                                                    (u.type && u.type.toLowerCase().includes(q))
                                                );
                                            },
                                            selectUnit(name) {
                                                this.unitValue = name;
                                                $wire.set('secondary_unit', name);
                                                this.query = '';
                                                this.open = false;
                                            },
                                            clear() {
                                                this.unitValue = '';
                                                this.query = '';
                                                $wire.set('secondary_unit', '');
                                                this.open = false;
                                            }
                                        }"
                                        class="relative"
                                        @click.outside="open = false"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700">Secondary Unit (e.g. Strip)</label>
                                            <span class="text-[10px] text-blue-700 font-semibold flex items-center gap-1">
                                                <i class="fa-solid fa-wand-magic-sparkles text-[9px] text-blue-600"></i> Auto-Suggest
                                            </span>
                                        </div>

                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                :value="open ? (query !== '' ? query : (unitValue || '')) : (unitValue || '')"
                                                @focus="open = true; query = '';" 
                                                @input="open = true; query = $event.target.value; unitValue = $event.target.value; $wire.set('secondary_unit', $event.target.value);"
                                                @keydown.escape="open = false"
                                                class="w-full h-9 pl-3 pr-16 border border-blue-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-blue-500 shadow-2xs cursor-pointer" 
                                                placeholder="e.g. Strip, Blister, Box"
                                                autocomplete="off"
                                            >
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                                                <template x-if="unitValue && unitValue.length > 0">
                                                    <button 
                                                        type="button" 
                                                        @click="clear()" 
                                                        class="p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-blue-100/50"
                                                        title="Clear"
                                                    >
                                                        <i class="fa-solid fa-xmark text-xs"></i>
                                                    </button>
                                                </template>
                                                <button 
                                                    type="button" 
                                                    @click="open = !open; if(open) query = '';" 
                                                    class="p-1 text-blue-700 hover:text-blue-900 rounded-md hover:bg-blue-100 transition"
                                                    title="Toggle Suggestions"
                                                >
                                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Floating Dropdown Menu --}}
                                        <div 
                                            x-show="open" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-blue-200 rounded-xl shadow-xl max-h-64 overflow-hidden divide-y divide-slate-100 py-1"
                                            style="display: none;"
                                        >
                                            <div class="px-3 py-1.5 bg-blue-50/80 flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-blue-800 border-b border-blue-100">
                                                <span>Suggested Units</span>
                                                <span x-text="filteredUnits.length + ' Available'"></span>
                                            </div>

                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50">
                                                <template x-for="u in filteredUnits" :key="u.name">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(u.name)"
                                                        class="w-full text-left px-3 py-2 hover:bg-blue-50 flex items-center justify-between text-xs transition group"
                                                        :class="unitValue && unitValue.toLowerCase() === u.name.toLowerCase() ? 'bg-blue-50 font-bold text-blue-900' : 'text-slate-700'"
                                                    >
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-5 h-5 rounded-md bg-blue-100 text-blue-700 flex items-center justify-center text-[10px] font-bold">
                                                                <i class="fa-solid fa-layer-group text-[9px]"></i>
                                                            </span>
                                                            <div>
                                                                <span x-text="u.name" class="font-medium"></span>
                                                                <template x-if="u.type">
                                                                    <span class="text-[10px] text-slate-400 ml-1.5 font-normal" x-text="'(' + u.type + ')'"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <template x-if="u.symbol">
                                                                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono" x-text="u.symbol"></span>
                                                            </template>
                                                            <template x-if="unitValue && unitValue.toLowerCase() === u.name.toLowerCase()">
                                                                <i class="fa-solid fa-check text-blue-600 text-xs"></i>
                                                            </template>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>

                                            <template x-if="filteredUnits.length === 0 && query && query.trim() !== ''">
                                                <div class="p-2.5 text-center">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(query.trim())"
                                                        class="w-full text-left px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-bold text-blue-800 flex items-center justify-between transition"
                                                    >
                                                        <span class="flex items-center gap-1.5">
                                                            <i class="fa-solid fa-plus text-blue-600"></i>
                                                            <span>Use custom unit: "<span x-text="query"></span>"</span>
                                                        </span>
                                                        <span class="text-[10px] bg-blue-600 text-white px-1.5 py-0.5 rounded">Custom</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Quick Suggested Pills --}}
                                        <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-0.5">Quick:</span>
                                            @foreach(['Strip', 'Blister', 'Pack', 'Box', 'Bottle', 'Ampoule'] as $secPill)
                                                <button 
                                                    type="button" 
                                                    @click="selectUnit('{{ $secPill }}')" 
                                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border transition {{ $secondary_unit === $secPill ? 'bg-blue-600 text-white border-blue-600 shadow-2xs' : 'bg-white border-blue-200 text-blue-800 hover:bg-blue-100' }}"
                                                >
                                                    {{ $secPill }}
                                                </button>
                                            @endforeach
                                            @if(!empty($secondary_unit))
                                                <button 
                                                    type="button" 
                                                    @click="clear()" 
                                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border bg-red-50 text-red-600 border-red-200 hover:bg-red-100"
                                                    title="Clear Secondary Unit"
                                                >
                                                    ✕ None
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] text-slate-600 font-medium mb-0.5">
                                            1 {{ $secondary_unit ?: 'Strip' }} contains
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="1" wire:model.live="secondary_unit_to_base" class="w-24 h-8 px-2.5 border border-blue-300 rounded-md text-sm bg-white font-bold text-blue-900">
                                            <span class="text-xs font-semibold text-slate-700">{{ $base_unit ?: 'Tablets' }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Purchase Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model="secondary_unit_purchase_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="e.g. 80">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Sale Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model="secondary_unit_selling_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white font-bold text-blue-700" placeholder="e.g. 110">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Strip Barcode (Optional)</label>
                                        <input type="text" wire:model="secondary_unit_barcode" class="w-full h-8 px-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="Scan Strip barcode">
                                    </div>
                                </div>
                            </div>

                            {{-- 3. PRIMARY UNIT --}}
                            <div class="bg-indigo-50/40 p-4 rounded-xl border border-indigo-200 relative shadow-xs">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-800 flex items-center gap-1.5">
                                        <i class="fa-solid fa-layer-group text-indigo-500 text-[10px]"></i> Primary Unit (Level 3)
                                    </span>
                                    <span class="px-2 py-0.5 bg-indigo-100 text-indigo-800 text-[10px] font-bold rounded-md">
                                        = {{ $this->calculatedPrimaryConversion }} {{ $base_unit ?: 'Tablets' }}
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    {{-- Primary Unit Searchable Suggestion Dropdown --}}
                                    <div 
                                        x-data="{
                                            open: false,
                                            query: '',
                                            unitValue: @entangle('primary_unit').live,
                                            standardUnits: [
                                                { name: 'Pack', symbol: 'Pk', type: 'Outer Pack' },
                                                { name: 'Box', symbol: 'Bx', type: 'Outer Box' },
                                                { name: 'Carton', symbol: 'Ctn', type: 'Master Carton' },
                                                { name: 'Crate', symbol: 'Crt', type: 'Crate' },
                                                { name: 'Container', symbol: 'Cnt', type: 'Container' },
                                                { name: 'Dozen', symbol: 'Dzn', type: '12-Pack' },
                                                { name: 'Strip', symbol: 'Str', type: 'Packaging' },
                                                { name: 'Bottle', symbol: 'Btl', type: 'Bulk Bottle' }
                                            ],
                                            get allUnits() {
                                                const dbUnits = {{ Js::from($availableUnits->map(fn($u) => ['name' => $u->name, 'symbol' => $u->symbol ?? ''])) }};
                                                const map = new Map();
                                                this.standardUnits.forEach(u => map.set(u.name.toLowerCase(), u));
                                                dbUnits.forEach(u => {
                                                    if (!map.has(u.name.toLowerCase())) {
                                                        map.set(u.name.toLowerCase(), { name: u.name, symbol: u.symbol || '', type: 'Outer Unit' });
                                                    }
                                                });
                                                return Array.from(map.values());
                                            },
                                            get filteredUnits() {
                                                if (!this.query || this.query.trim() === '') {
                                                    return this.allUnits;
                                                }
                                                const q = this.query.toLowerCase().trim();
                                                return this.allUnits.filter(u => 
                                                    u.name.toLowerCase().includes(q) || 
                                                    (u.symbol && u.symbol.toLowerCase().includes(q)) ||
                                                    (u.type && u.type.toLowerCase().includes(q))
                                                );
                                            },
                                            selectUnit(name) {
                                                this.unitValue = name;
                                                $wire.set('primary_unit', name);
                                                this.query = '';
                                                this.open = false;
                                            },
                                            clear() {
                                                this.unitValue = '';
                                                this.query = '';
                                                $wire.set('primary_unit', '');
                                                this.open = false;
                                            }
                                        }"
                                        class="relative"
                                        @click.outside="open = false"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="block text-xs font-semibold text-slate-700">Primary Unit (e.g. Pack / Box)</label>
                                            <span class="text-[10px] text-indigo-700 font-semibold flex items-center gap-1">
                                                <i class="fa-solid fa-wand-magic-sparkles text-[9px] text-indigo-600"></i> Auto-Suggest
                                            </span>
                                        </div>

                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                :value="open ? (query !== '' ? query : (unitValue || '')) : (unitValue || '')"
                                                @focus="open = true; query = '';" 
                                                @input="open = true; query = $event.target.value; unitValue = $event.target.value; $wire.set('primary_unit', $event.target.value);"
                                                @keydown.escape="open = false"
                                                class="w-full h-9 pl-3 pr-16 border border-indigo-200 rounded-lg text-sm bg-white font-medium focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-500 shadow-2xs cursor-pointer" 
                                                placeholder="e.g. Pack, Box, Carton"
                                                autocomplete="off"
                                            >
                                            <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                                                <template x-if="unitValue && unitValue.length > 0">
                                                    <button 
                                                        type="button" 
                                                        @click="clear()" 
                                                        class="p-1 text-slate-400 hover:text-slate-600 rounded-full hover:bg-indigo-100/50"
                                                        title="Clear"
                                                    >
                                                        <i class="fa-solid fa-xmark text-xs"></i>
                                                    </button>
                                                </template>
                                                <button 
                                                    type="button" 
                                                    @click="open = !open; if(open) query = '';" 
                                                    class="p-1 text-indigo-700 hover:text-indigo-900 rounded-md hover:bg-indigo-100 transition"
                                                    title="Toggle Suggestions"
                                                >
                                                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open ? 'rotate-180' : ''"></i>
                                                </button>
                                            </div>
                                        </div>

                                        {{-- Floating Dropdown Menu --}}
                                        <div 
                                            x-show="open" 
                                            x-transition:enter="transition ease-out duration-150"
                                            x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                                            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave="transition ease-in duration-100"
                                            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                                            x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                                            class="absolute z-50 left-0 right-0 mt-1 bg-white border border-indigo-200 rounded-xl shadow-xl max-h-64 overflow-hidden divide-y divide-slate-100 py-1"
                                            style="display: none;"
                                        >
                                            <div class="px-3 py-1.5 bg-indigo-50/80 flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-indigo-800 border-b border-indigo-100">
                                                <span>Suggested Units</span>
                                                <span x-text="filteredUnits.length + ' Available'"></span>
                                            </div>

                                            <div class="max-h-52 overflow-y-auto divide-y divide-slate-50">
                                                <template x-for="u in filteredUnits" :key="u.name">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(u.name)"
                                                        class="w-full text-left px-3 py-2 hover:bg-indigo-50 flex items-center justify-between text-xs transition group"
                                                        :class="unitValue && unitValue.toLowerCase() === u.name.toLowerCase() ? 'bg-indigo-50 font-bold text-indigo-900' : 'text-slate-700'"
                                                    >
                                                        <div class="flex items-center gap-2">
                                                            <span class="w-5 h-5 rounded-md bg-indigo-100 text-indigo-700 flex items-center justify-center text-[10px] font-bold">
                                                                <i class="fa-solid fa-boxes-stacked text-[9px]"></i>
                                                            </span>
                                                            <div>
                                                                <span x-text="u.name" class="font-medium"></span>
                                                                <template x-if="u.type">
                                                                    <span class="text-[10px] text-slate-400 ml-1.5 font-normal" x-text="'(' + u.type + ')'"></span>
                                                                </template>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <template x-if="u.symbol">
                                                                <span class="text-[10px] bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded font-mono" x-text="u.symbol"></span>
                                                            </template>
                                                            <template x-if="unitValue && unitValue.toLowerCase() === u.name.toLowerCase()">
                                                                <i class="fa-solid fa-check text-indigo-600 text-xs"></i>
                                                            </template>
                                                        </div>
                                                    </button>
                                                </template>
                                            </div>

                                            <template x-if="filteredUnits.length === 0 && query && query.trim() !== ''">
                                                <div class="p-2.5 text-center">
                                                    <button
                                                        type="button"
                                                        @click="selectUnit(query.trim())"
                                                        class="w-full text-left px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg text-xs font-bold text-indigo-800 flex items-center justify-between transition"
                                                    >
                                                        <span class="flex items-center gap-1.5">
                                                            <i class="fa-solid fa-plus text-indigo-600"></i>
                                                            <span>Use custom unit: "<span x-text="query"></span>"</span>
                                                        </span>
                                                        <span class="text-[10px] bg-indigo-600 text-white px-1.5 py-0.5 rounded">Custom</span>
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Quick Suggested Pills --}}
                                        <div class="flex items-center gap-1 mt-1.5 flex-wrap">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mr-0.5">Quick:</span>
                                            @foreach(['Pack', 'Box', 'Carton', 'Crate', 'Container'] as $primPill)
                                                <button 
                                                    type="button" 
                                                    @click="selectUnit('{{ $primPill }}')" 
                                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border transition {{ $primary_unit === $primPill ? 'bg-indigo-600 text-white border-indigo-600 shadow-2xs' : 'bg-white border-indigo-200 text-indigo-800 hover:bg-indigo-100' }}"
                                                >
                                                    {{ $primPill }}
                                                </button>
                                            @endforeach
                                            @if(!empty($primary_unit))
                                                <button 
                                                    type="button" 
                                                    @click="clear()" 
                                                    class="px-1.5 py-0.5 text-[10px] font-bold rounded-md border bg-red-50 text-red-600 border-red-200 hover:bg-red-100"
                                                    title="Clear Primary Unit"
                                                >
                                                    ✕ None
                                                </button>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] text-slate-600 font-medium mb-0.5">
                                            1 {{ $primary_unit ?: 'Pack' }} contains
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input type="number" min="1" wire:model.live="primary_unit_to_secondary" class="w-24 h-8 px-2.5 border border-indigo-300 rounded-md text-sm bg-white font-bold text-indigo-900">
                                            <span class="text-xs font-semibold text-slate-700">{{ $secondary_unit ?: ($base_unit ?: 'Units') }}</span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Purchase Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model="primary_unit_purchase_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="e.g. 800">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Sale Price</label>
                                            <div class="relative">
                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-xs text-slate-400">Rs.</span>
                                                <input type="number" min="0" step="0.01" wire:model="primary_unit_selling_price" class="w-full h-8 pl-8 pr-2 border border-slate-300 rounded-md text-xs bg-white font-bold text-indigo-700" placeholder="e.g. 1000">
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-[11px] text-slate-600 font-medium mb-0.5">Pack Barcode (Optional)</label>
                                        <input type="text" wire:model="primary_unit_barcode" class="w-full h-8 px-2 border border-slate-300 rounded-md text-xs bg-white" placeholder="Scan Pack barcode">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- PACKAGING PREVIEW COMPONENT --}}
                        <div class="mt-4 p-4 bg-linear-to-r from-emerald-50 via-teal-50 to-blue-50 border border-emerald-200 rounded-xl shadow-xs">
                            <div class="flex items-center justify-between flex-wrap gap-2 mb-3 border-b border-emerald-200/60 pb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-900 flex items-center gap-2">
                                    <i class="fa-solid fa-diagram-project text-emerald-600"></i>
                                    PACKAGING PREVIEW & AUTOMATIC CONVERSION
                                </span>
                                <span class="text-[11px] font-semibold text-emerald-700 bg-white px-2.5 py-0.5 rounded-full border border-emerald-200 shadow-2xs">
                                    Hierarchy Auto-Calculation Active
                                </span>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-center">
                                {{-- Visual Cascade Flow --}}
                                <div class="flex items-center justify-center gap-3 py-2 bg-white/80 rounded-lg border border-emerald-100 px-4">
                                    @if(!empty($primary_unit))
                                        <div class="text-center">
                                            <span class="block text-xs font-bold text-indigo-900 bg-indigo-100 px-3 py-1 rounded-md border border-indigo-200">
                                                1 {{ $primary_unit }}
                                            </span>
                                        </div>
                                        <i class="fa-solid fa-arrow-right text-emerald-500 text-sm"></i>
                                    @endif

                                    @if(!empty($secondary_unit))
                                        <div class="text-center">
                                            <span class="block text-xs font-bold text-blue-900 bg-blue-100 px-3 py-1 rounded-md border border-blue-200">
                                                {{ max(1, (int)$primary_unit_to_secondary) }} {{ Str::plural($secondary_unit, (int)$primary_unit_to_secondary) }}
                                            </span>
                                        </div>
                                        <i class="fa-solid fa-arrow-right text-emerald-500 text-sm"></i>
                                    @endif

                                    <div class="text-center">
                                        <span class="block text-xs font-extrabold text-emerald-900 bg-emerald-100 px-3 py-1 rounded-md border border-emerald-300">
                                            {{ $this->calculatedPrimaryConversion }} {{ Str::plural($base_unit ?: 'Tablet', $this->calculatedPrimaryConversion) }}
                                        </span>
                                    </div>
                                </div>

                                {{-- Formula Summary --}}
                                <div class="text-xs space-y-1 bg-white/80 p-3 rounded-lg border border-emerald-100">
                                    @if(!empty($primary_unit))
                                        <div class="flex items-center justify-between text-slate-700 font-medium">
                                            <span>1 {{ $primary_unit }} =</span>
                                            <span class="font-bold text-indigo-800">{{ $this->calculatedPrimaryConversion }} {{ Str::plural($base_unit ?: 'Tablet', $this->calculatedPrimaryConversion) }}</span>
                                        </div>
                                    @endif
                                    @if(!empty($secondary_unit))
                                        <div class="flex items-center justify-between text-slate-700 font-medium">
                                            <span>1 {{ $secondary_unit }} =</span>
                                            <span class="font-bold text-blue-800">{{ $this->calculatedSecondaryConversion }} {{ Str::plural($base_unit ?: 'Tablet', $this->calculatedSecondaryConversion) }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between text-slate-700 font-medium">
                                        <span>1 {{ $base_unit ?: 'Tablet' }} =</span>
                                        <span class="font-bold text-emerald-800">1 Base Unit</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ========================================================= --}}
                {{-- PRODUCT UNITS & PACKAGING (FOR GENERAL STORE ITEMS) --}}
                {{-- ========================================================= --}}
                @if($product_type === 'general')
                    <div class="mt-6 pt-5 border-t border-slate-200">
                        <h3 class="text-base font-bold text-slate-900 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-box text-blue-600"></i>
                            General Item Packaging & Units
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Outer / Purchase Packaging (Optional, e.g. Carton, Box)
                                </label>
                                <input type="text" list="gen-prim-units" wire:model.live="primary_unit" class="w-full h-9 px-3 border border-slate-200 rounded-lg text-sm bg-white" placeholder="Carton / Box (Leave empty if sold individually)">
                                <datalist id="gen-prim-units">
                                    <option value="Carton">
                                    <option value="Box">
                                    <option value="Pack">
                                    <option value="Dozen">
                                </datalist>

                                <div class="mt-2">
                                    <label class="block text-[11px] text-slate-500 mb-0.5">1 {{ $primary_unit ?: 'Outer Unit' }} contains</label>
                                    <div class="flex items-center gap-1.5">
                                        <input type="number" min="1" wire:model.live="primary_unit_to_secondary" class="w-24 h-8 px-2 border border-slate-200 rounded-md text-sm bg-white">
                                        <span class="text-xs text-slate-600 font-medium">{{ $base_unit ?: 'Pieces' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Base Sale Unit (e.g. Piece, Bottle, Liter, Kg) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" list="gen-base-units" wire:model.live="base_unit" class="w-full h-9 px-3 border border-slate-200 rounded-lg text-sm bg-white" placeholder="Piece">
                                <datalist id="gen-base-units">
                                    <option value="Piece">
                                    <option value="Bottle">
                                    <option value="Can">
                                    <option value="Kg">
                                    <option value="Liter">
                                    <option value="Packet">
                                </datalist>
                                <p class="text-[11px] text-slate-500 mt-1">Single item sale unit.</p>
                            </div>
                        </div>
                    </div>
                @endif



                {{-- Buttons --}}
                <div class="flex justify-end gap-3 mt-5">

                    <button
                        type="button"
                        wire:click="$refresh"
                        class="h-10 px-5 rounded-lg bg-slate-100 border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-200"
                    >
                        Cancel
                    </button>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        class="h-10 px-6 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700 disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="save">
                            @if($product_type === 'general')
                                Save General Store Item
                            @else
                                Save Medicine
                            @endif
                        </span>

                        <span wire:loading wire:target="save">
                            Saving...
                        </span>
                    </button>

                </div>

            </form>

        </section>


        {{-- ========================================================= --}}
        {{-- PRODUCTS & INVENTORY LIST (FULL-FEATURED) --}}
        {{-- ========================================================= --}}

        <section class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden" id="inventory-list-section">

            {{-- Title & Top Toolbar --}}
            <div class="px-6 py-5 border-b border-slate-100 bg-gradient-to-r from-slate-50/80 via-white to-slate-50/80 flex flex-col md:flex-row md:items-center justify-between gap-4">

                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-500 flex items-center justify-center shadow-sm text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14l-8 4m0 10L4 17V7m8 14V11m0 0L4 7m8 4l8-4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">
                                Products & Inventory List
                            </h2>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                {{ number_format($totalMedicines) }} Total
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Manage stock, active batches, multi-unit conversions, barcode generation, and product lifecycle
                        </p>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        wire:click="exportCsv"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export CSV</span>
                    </button>

                    <button
                        type="button"
                        onclick="window.print()"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-sm transition active:scale-95"
                    >
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        <span>Print Sheet</span>
                    </button>

                    <button
                        type="button"
                        wire:click="$refresh"
                        class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition hover:rotate-180 duration-300"
                        title="Refresh list"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>

                    {{-- Admin Only: Secure Delete All Products --}}
                    @if(auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->isAdmin()))
                        <button
                            type="button"
                            wire:click="openDeleteAllModal"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-red-200 bg-red-50 hover:bg-red-100 text-red-700 hover:text-red-800 text-xs font-bold shadow-sm transition active:scale-95"
                            title="Delete all products (Admin only)"
                            id="btn-delete-all-products"
                        >
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span>Delete All</span>
                        </button>
                    @endif
                </div>

            </div>

            {{-- Flash Alert Messages --}}
            @if(session()->has('message'))
                <div class="mx-6 mt-4 p-3.5 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ session('message') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">✕</button>
                </div>
            @endif

            @if(session()->has('warning'))
                <div class="mx-6 mt-4 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span class="font-medium">{{ session('warning') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-amber-500 hover:text-amber-700">✕</button>
                </div>
            @endif

            @if(session()->has('error'))
                <div class="mx-6 mt-4 p-3.5 rounded-xl bg-red-50 border border-red-200 text-red-800 text-sm flex items-center justify-between shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="font-medium">{{ session('error') }}</span>
                    </div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700">✕</button>
                </div>
            @endif

            {{-- Interactive Statistics Cards --}}
            <div class="p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Total Products --}}
                <div 
                    wire:click="$set('productTypeFilter', 'all'); $set('stockFilter', ''); $set('expiryFilter', '');"
                    class="cursor-pointer group rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50/60 to-emerald-100/30 p-4.5 hover:shadow-md transition duration-200 active:scale-[0.99]"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold text-emerald-700">Total Products</p>
                            <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalMedicines) }}</p>
                            <div class="flex items-center gap-2 mt-1.5 text-[11px] font-medium text-slate-500">
                                <span class="text-blue-700 font-semibold">{{ $totalMedicineProducts }} Medicines</span>
                                <span>•</span>
                                <span class="text-amber-700 font-semibold">{{ $totalGeneralProducts }} General</span>
                            </div>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Total Stock Units & Value --}}
                <div 
                    wire:click="$set('stockFilter', 'in_stock'); $set('expiryFilter', '');"
                    class="cursor-pointer group rounded-2xl border border-blue-100 bg-gradient-to-br from-blue-50/60 to-blue-100/30 p-4.5 hover:shadow-md transition duration-200 active:scale-[0.99]"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold text-blue-700">Total Stock (Units)</p>
                            <p class="text-2xl font-black text-slate-900 mt-1">{{ number_format($totalStock) }}</p>
                            <p class="text-[11px] font-medium text-blue-600 mt-1.5">
                                Est. Value: PKR {{ number_format($totalStockValue, 0) }}
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-blue-500/10 text-blue-600 flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0v10l-8 4m8-14l-8 4m0 10L4 17V7m8 14V11m0 0L4 7m8 4l8-4"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Low Stock Items --}}
                <div 
                    wire:click="$set('stockFilter', 'low_stock'); $set('expiryFilter', '');"
                    class="cursor-pointer group rounded-2xl border border-amber-100 bg-gradient-to-br from-amber-50/60 to-amber-100/30 p-4.5 hover:shadow-md transition duration-200 active:scale-[0.99]"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold text-amber-700">Low Stock Alert</p>
                            <p class="text-2xl font-black text-amber-600 mt-1">{{ number_format($lowStock) }}</p>
                            <p class="text-[11px] font-medium text-amber-600 mt-1.5">
                                Needs reordering soon
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-600 flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                {{-- Expired & Near Expiry --}}
                <div 
                    wire:click="$set('stockFilter', 'expired'); $set('expiryFilter', '');"
                    class="cursor-pointer group rounded-2xl border border-red-100 bg-gradient-to-br from-red-50/60 to-red-100/30 p-4.5 hover:shadow-md transition duration-200 active:scale-[0.99]"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wider font-semibold text-red-700">Expired Items</p>
                            <p class="text-2xl font-black text-red-600 mt-1">{{ number_format($expired) }}</p>
                            <p class="text-[11px] font-medium text-slate-500 mt-1.5">
                                +{{ $nearExpiry }} expiring in 90 days
                            </p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-red-500/10 text-red-600 flex items-center justify-center group-hover:scale-110 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

            </div>

            {{-- Filters & Search Toolbar --}}
            <div class="px-6 pb-4 space-y-3">

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">

                    {{-- Search Input --}}
                    <div class="relative lg:col-span-2">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m2.35-5.65a8 8 0 11-16 0 8 8 0 0116 0z"/>
                            </svg>
                        </div>
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="search"
                            placeholder="Search name, generic, barcode, batch..."
                            class="w-full h-10 pl-9 pr-8 border border-slate-200 rounded-xl text-sm bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:border-emerald-500 transition"
                        >
                        @if($search)
                            <button
                                wire:click="$set('search', '')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                            >
                                ✕
                            </button>
                        @endif
                    </div>

                    {{-- Product Type --}}
                    <div>
                        <select
                            wire:model.live="productTypeFilter"
                            class="w-full h-10 px-3 border border-slate-200 rounded-xl bg-white text-sm text-slate-700 font-medium focus:ring-2 focus:ring-emerald-300 outline-none"
                        >
                            <option value="all">📦 All Types</option>
                            <option value="medicine">💊 Medicines Only</option>
                            <option value="general">🏪 General Store Only</option>
                        </select>
                    </div>

                    {{-- Category Filter --}}
                    <div>
                        <select
                            wire:model.live="categoryFilter"
                            class="w-full h-10 px-3 border border-slate-200 rounded-xl bg-white text-sm text-slate-700 focus:ring-2 focus:ring-emerald-300 outline-none"
                        >
                            <option value="">📁 All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Supplier Filter --}}
                    <div>
                        <select
                            wire:model.live="supplierFilter"
                            class="w-full h-10 px-3 border border-slate-200 rounded-xl bg-white text-sm text-slate-700 focus:ring-2 focus:ring-emerald-300 outline-none"
                        >
                            <option value="">🚚 All Suppliers</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Stock Status Filter --}}
                    <div>
                        <select
                            wire:model.live="stockFilter"
                            class="w-full h-10 px-3 border border-slate-200 rounded-xl bg-white text-sm text-slate-700 focus:ring-2 focus:ring-emerald-300 outline-none"
                        >
                            <option value="">⚡ All Stock Status</option>
                            <option value="in_stock">🟢 In Stock</option>
                            <option value="low_stock">🟡 Low Stock</option>
                            <option value="out_of_stock">🔴 Out of Stock</option>
                            <option value="expired">⚠️ Expired</option>
                        </select>
                    </div>

                </div>

                {{-- Secondary Filters Row --}}
                <div class="flex items-center justify-between gap-3 flex-wrap pt-1 text-xs">

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Expiry Filter --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-400 font-medium">Expiry:</span>
                            <select
                                wire:model.live="expiryFilter"
                                class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 focus:ring-1 focus:ring-emerald-300 outline-none"
                            >
                                <option value="">All Expiry</option>
                                <option value="30_days">Expiring in 30 Days</option>
                                <option value="60_days">Expiring in 60 Days</option>
                                <option value="90_days">Expiring in 90 Days</option>
                            </select>
                        </div>

                        {{-- Per Page --}}
                        <div class="flex items-center gap-1.5">
                            <span class="text-slate-400 font-medium">Show:</span>
                            <select
                                wire:model.live="perPage"
                                class="h-8 px-2.5 rounded-lg border border-slate-200 bg-white text-xs text-slate-700 focus:ring-1 focus:ring-emerald-300 outline-none"
                            >
                                <option value="10">10 / page</option>
                                <option value="15">15 / page</option>
                                <option value="25">25 / page</option>
                                <option value="50">50 / page</option>
                                <option value="100">100 / page</option>
                            </select>
                        </div>

                        @if($search || $productTypeFilter !== 'all' || $categoryFilter || $supplierFilter || $stockFilter || $expiryFilter)
                            <button
                                type="button"
                                wire:click="$set('search', ''); $set('productTypeFilter', 'all'); $set('categoryFilter', ''); $set('supplierFilter', ''); $set('stockFilter', ''); $set('expiryFilter', '');"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 text-xs font-semibold transition"
                            >
                                <span>Reset All Filters</span>
                                <span>✕</span>
                            </button>
                        @endif
                    </div>

                    {{-- Results Indicator --}}
                    <div class="text-slate-500 font-medium" wire:loading.remove wire:target="search, productTypeFilter, categoryFilter, supplierFilter, stockFilter, expiryFilter">
                        Found <strong class="text-slate-800">{{ $medicines->total() }}</strong> products
                    </div>
                    <div class="text-emerald-600 font-semibold flex items-center gap-1" wire:loading wire:target="search, productTypeFilter, categoryFilter, supplierFilter, stockFilter, expiryFilter">
                        <svg class="animate-spin h-3.5 w-3.5" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Updating results...</span>
                    </div>

                </div>

            </div>

            {{-- Bulk Actions Floating Bar --}}
            @if(count($selectedMedicines) > 0)
                <div class="mx-6 mb-3 px-4 py-3 bg-indigo-900 text-white rounded-xl flex items-center justify-between shadow-lg animate-fadeIn flex-wrap gap-3">
                    <div class="flex items-center gap-2 text-sm font-semibold">
                        <span class="w-6 h-6 rounded-full bg-indigo-500 flex items-center justify-center text-xs font-black">
                            {{ count($selectedMedicines) }}
                        </span>
                        <span>Products Selected</span>
                    </div>

                    <div class="flex items-center gap-2 flex-wrap">
                        {{-- Bulk Status --}}
                        <div class="inline-flex rounded-lg shadow-sm">
                            <button
                                type="button"
                                wire:click="bulkUpdateStatus('active')"
                                class="px-3 py-1.5 rounded-l-lg bg-indigo-800 hover:bg-indigo-700 text-xs font-semibold text-white border-r border-indigo-700"
                            >
                                Set Active
                            </button>
                            <button
                                type="button"
                                wire:click="bulkUpdateStatus('inactive')"
                                class="px-3 py-1.5 rounded-r-lg bg-indigo-800 hover:bg-indigo-700 text-xs font-semibold text-white"
                            >
                                Set Inactive
                            </button>
                        </div>

                        {{-- Bulk Delete Button --}}
                        <button
                            type="button"
                            wire:click="confirmBulkDelete"
                            class="px-3.5 py-1.5 rounded-lg bg-red-600 hover:bg-red-700 text-xs font-bold text-white flex items-center gap-1.5 shadow-sm transition active:scale-95"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Delete Selected</span>
                        </button>

                        {{-- Deselect --}}
                        <button
                            type="button"
                            wire:click="$set('selectedMedicines', []); $set('selectAll', false);"
                            class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-600 text-xs font-semibold text-slate-200"
                        >
                            Deselect All
                        </button>
                    </div>
                </div>
            @endif

            {{-- ===================================================== --}}
            {{-- DATA TABLE --}}
            {{-- ===================================================== --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-y border-slate-200/80 text-xs font-bold uppercase tracking-wider text-slate-600">

                            {{-- Select All Checkbox --}}
                            <th class="px-4 py-3.5 w-10">
                                <input
                                    type="checkbox"
                                    wire:model.live="selectAll"
                                    class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                >
                            </th>

                            <th class="px-3 py-3.5 w-12 text-slate-400">#</th>

                            <th class="px-3 py-3.5">Type</th>

                            {{-- Product Name (Sortable) --}}
                            <th 
                                wire:click="sortBy('name')" 
                                class="px-4 py-3.5 cursor-pointer hover:bg-slate-100/80 transition select-none group"
                            >
                                <div class="flex items-center gap-1">
                                    <span>Product Name & Details</span>
                                    <span class="text-slate-400 group-hover:text-slate-700">
                                        @if($sortField === 'name')
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        @else
                                            ↕
                                        @endif
                                    </span>
                                </div>
                            </th>

                            {{-- Category (Sortable) --}}
                            <th 
                                wire:click="sortBy('category')" 
                                class="px-4 py-3.5 cursor-pointer hover:bg-slate-100/80 transition select-none group"
                            >
                                <div class="flex items-center gap-1">
                                    <span>Category</span>
                                    <span class="text-slate-400 group-hover:text-slate-700">
                                        @if($sortField === 'category')
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        @else
                                            ↕
                                        @endif
                                    </span>
                                </div>
                            </th>

                            <th class="px-4 py-3.5">Batches</th>

                            <th class="px-4 py-3.5">Total Stock</th>

                            {{-- Selling Price (Sortable) --}}
                            <th 
                                wire:click="sortBy('unit_price')" 
                                class="px-4 py-3.5 cursor-pointer hover:bg-slate-100/80 transition select-none group"
                            >
                                <div class="flex items-center gap-1">
                                    <span>Selling Price</span>
                                    <span class="text-slate-400 group-hover:text-slate-700">
                                        @if($sortField === 'unit_price')
                                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                                        @else
                                            ↕
                                        @endif
                                    </span>
                                </div>
                            </th>

                            <th class="px-4 py-3.5">Expiry / Batch Date</th>

                            <th class="px-4 py-3.5">Status</th>

                            <th class="px-4 py-3.5 text-center font-bold text-slate-700">Actions</th>

                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($medicines as $index => $medicine)
                            @php
                                $stock = $medicine->batches->sum('quantity');
                                $alert = $medicine->reorder_level ?? $medicine->alert_quantity ?? 10;
                                $batchesCount = $medicine->batches->count();
                                $isExpanded = in_array($medicine->id, $expandedRows);
                                $isSelected = in_array((string)$medicine->id, $selectedMedicines);

                                $activeExpiredBatch = $medicine->batches
                                    ->where('quantity', '>', 0)
                                    ->first(function ($batch) {
                                        return $batch->expiry_date && $batch->expiry_date->isPast();
                                    });

                                $nearestBatch = $medicine->batches
                                    ->where('quantity', '>', 0)
                                    ->sortBy('expiry_date')
                                    ->first();

                                $displayBatch = $activeExpiredBatch ?: ($nearestBatch ?: $medicine->batches->first());

                                // Status logic
                                $status = 'In Stock';
                                $statusClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';

                                if ($medicine->status === 'inactive') {
                                    $status = 'Inactive';
                                    $statusClass = 'bg-slate-100 text-slate-600 border-slate-200';
                                } elseif ($activeExpiredBatch) {
                                    $status = 'Expired';
                                    $statusClass = 'bg-red-50 text-red-700 border-red-200';
                                } elseif ($stock <= 0) {
                                    $status = 'Out of Stock';
                                    $statusClass = 'bg-red-50 text-red-700 border-red-200';
                                } elseif ($stock <= $alert) {
                                    $status = 'Low Stock';
                                    $statusClass = 'bg-amber-50 text-amber-700 border-amber-200';
                                }
                            @endphp

                            <tr wire:key="medicine-row-{{ $medicine->id }}" class="hover:bg-slate-50/80 transition group {{ $isSelected ? 'bg-indigo-50/40' : '' }}">

                                {{-- Selection Checkbox --}}
                                <td class="px-4 py-4">
                                    <input
                                        type="checkbox"
                                        value="{{ $medicine->id }}"
                                        wire:model.live="selectedMedicines"
                                        class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500 cursor-pointer"
                                    >
                                </td>

                                {{-- Index --}}
                                <td class="px-3 py-4 text-xs font-mono text-slate-400">
                                    {{ $medicines->firstItem() + $index }}
                                </td>

                                {{-- Product Type --}}
                                <td class="px-3 py-4">
                                    @if($medicine->is_general)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                            <span>🏪</span> General
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-800 border border-blue-200/80 shadow-2xs">
                                            <span>💊</span> Medicine
                                        </span>
                                    @endif
                                </td>

                                {{-- Product Name & Generic Details --}}
                                <td class="px-4 py-4">
                                    <div class="flex items-start gap-2">
                                        <div>
                                            <div class="font-bold text-slate-900 group-hover:text-emerald-700 transition">
                                                {{ $medicine->name }}
                                            </div>

                                            @if($medicine->generic_name)
                                                <div class="text-xs font-medium text-slate-500 mt-0.5">
                                                    {{ $medicine->generic_name }}
                                                </div>
                                            @endif

                                            <div class="flex items-center gap-2 mt-1 text-[11px] text-slate-400 flex-wrap">
                                                @if($medicine->brand)
                                                    <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-medium">
                                                        🏷️ {{ $medicine->brand }}
                                                    </span>
                                                @endif
                                                @if($medicine->strength)
                                                    <span class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded font-medium">
                                                        {{ $medicine->strength }}
                                                    </span>
                                                @endif
                                                @if($medicine->dosage_form)
                                                    <span class="text-slate-500 font-medium">
                                                        {{ $medicine->dosage_form }}
                                                    </span>
                                                @endif
                                                @if($medicine->barcode)
                                                    <span class="font-mono text-slate-400 text-[10px] bg-slate-50 px-1 rounded border border-slate-200">
                                                        {{ $medicine->barcode }}
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Packaging Hierarchy Chips --}}
                                            @if($medicine->packagings->count() > 1)
                                                <div class="flex flex-wrap gap-1 mt-1.5">
                                                    @foreach($medicine->packagings as $pkg)
                                                        <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200">
                                                            {{ $pkg->display_name ?: $pkg->unit?->name }} ({{ (int)$pkg->conversion_to_base }}x)
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Category --}}
                                <td class="px-4 py-4 text-slate-700 font-medium text-xs">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200/80">
                                        {{ $medicine->category?->name ?? '—' }}
                                    </span>
                                </td>

                                {{-- Batches Details & Expand Toggle --}}
                                <td class="px-4 py-4">
                                    <button
                                        type="button"
                                        wire:click="toggleExpandRow({{ $medicine->id }})"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $isExpanded ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
                                        title="Click to view/hide batch list"
                                    >
                                        <span>{{ $batchesCount }} {{ Str::plural('Batch', $batchesCount) }}</span>
                                        <span>{{ $isExpanded ? '▲' : '▼' }}</span>
                                    </button>

                                    @if($displayBatch && !$isExpanded)
                                        <div class="text-[11px] font-mono text-slate-500 mt-1">
                                            Latest: <strong>{{ $displayBatch->batch_number }}</strong>
                                        </div>
                                    @endif
                                </td>

                                {{-- Total Stock & Breakdown --}}
                                <td class="px-4 py-4">
                                    <div @class([
                                        'font-extrabold text-sm',
                                        'text-emerald-600' => $stock > $alert,
                                        'text-amber-600' => $stock > 0 && $stock <= $alert,
                                        'text-red-600' => $stock <= 0,
                                    ])>
                                        {{ number_format($stock) }} <span class="text-xs font-normal text-slate-500">{{ $medicine->base_unit ?: 'Base Units' }}</span>
                                    </div>

                                    @if($stock > 0 && $medicine->packagings->count() > 1)
                                        <div class="text-[11px] font-semibold text-emerald-700 mt-0.5">
                                            {{ $medicine->formatStockInUnits($stock) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Selling Price --}}
                                <td class="px-4 py-4">
                                    <div class="font-black text-sm text-slate-900">
                                        PKR {{ number_format($medicine->selling_price, 2) }}
                                    </div>
                                    @if($medicine->purchase_price > 0)
                                        <div class="text-[11px] text-slate-400">
                                            Cost: PKR {{ number_format($medicine->purchase_price, 2) }}
                                        </div>
                                    @endif
                                </td>

                                {{-- Expiry Date --}}
                                <td class="px-4 py-4 text-xs">
                                    @if($displayBatch?->expiry_date)
                                        <div class="font-medium text-slate-700">
                                            {{ $displayBatch->expiry_date->format('Y-m-d') }}
                                        </div>
                                        @php
                                            $daysLeft = now()->diffInDays($displayBatch->expiry_date, false);
                                        @endphp
                                        @if($daysLeft < 0)
                                            <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-200">
                                                Expired {{ abs((int)$daysLeft) }}d ago
                                            </span>
                                        @elseif($daysLeft <= 90)
                                            <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                In {{ (int)$daysLeft }} days
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">N/A</span>
                                    @endif
                                </td>

                                {{-- Status Pill --}}
                                <td class="px-4 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full border text-xs font-bold {{ $statusClass }}">
                                        {{ $status }}
                                    </span>
                                </td>

                                {{-- Actions Group --}}
                                <td class="px-4 py-4 text-center">
                                    <div class="inline-flex items-center justify-center gap-1">

                                        {{-- Quick View --}}
                                        <button
                                            type="button"
                                            wire:click="openViewModal({{ $medicine->id }})"
                                            class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition"
                                            title="View Details"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>

                                        {{-- Edit Product --}}
                                        <button
                                            type="button"
                                            wire:click="openEditModal({{ $medicine->id }})"
                                            class="w-8 h-8 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 flex items-center justify-center transition"
                                            title="Edit Product"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>

                                        {{-- Add Batch / Restock --}}
                                        <button
                                            type="button"
                                            wire:click="openAddBatchModal({{ $medicine->id }})"
                                            class="w-8 h-8 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 flex items-center justify-center transition"
                                            title="Add New Batch / Restock"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                            </svg>
                                        </button>

                                        {{-- Barcode Label --}}
                                        <button
                                            type="button"
                                            wire:click="openBarcodeModal({{ $medicine->id }})"
                                            class="w-8 h-8 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-700 flex items-center justify-center transition"
                                            title="Generate & Print Barcode Label"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                            </svg>
                                        </button>

                                        {{-- Delete Product --}}
                                        <button
                                            type="button"
                                            wire:click="confirmDelete({{ $medicine->id }})"
                                            class="w-8 h-8 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition"
                                            title="Delete Product"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>

                                    </div>
                                </td>

                            </tr>

                            {{-- Inline Expandable Batch Table --}}
                            @if($isExpanded)
                                <tr class="bg-slate-50/90 border-y border-slate-200">
                                    <td colspan="11" class="p-4 pl-12">
                                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 space-y-3">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="text-sm font-bold text-slate-800">
                                                        Active Batches for {{ $medicine->name }}
                                                    </span>
                                                    <span class="text-xs text-slate-500">
                                                        ({{ $medicine->batches->count() }} batches recorded)
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    wire:click="openAddBatchModal({{ $medicine->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition"
                                                >
                                                    + Add Batch to {{ $medicine->name }}
                                                </button>
                                            </div>

                                            <div class="overflow-x-auto">
                                                <table class="w-full text-xs text-left">
                                                    <thead>
                                                        <tr class="bg-slate-100 text-slate-600 font-semibold uppercase tracking-wider">
                                                            <th class="px-3 py-2">Batch #</th>
                                                            <th class="px-3 py-2">Supplier</th>
                                                            <th class="px-3 py-2">Expiry Date</th>
                                                            <th class="px-3 py-2">Quantity</th>
                                                            <th class="px-3 py-2">Purchase Price</th>
                                                            <th class="px-3 py-2">Selling Price</th>
                                                            <th class="px-3 py-2">Status</th>
                                                            <th class="px-3 py-2 text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        @forelse($medicine->batches as $b)
                                                            <tr class="hover:bg-slate-50">
                                                                <td class="px-3 py-2 font-mono font-bold text-slate-800">
                                                                    {{ $b->batch_number }}
                                                                </td>
                                                                <td class="px-3 py-2 text-slate-600">
                                                                    {{ $b->supplier?->name ?? '—' }}
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    @if($b->expiry_date)
                                                                        <span class="{{ $b->expiry_date->isPast() ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                                                            {{ $b->expiry_date->format('Y-m-d') }}
                                                                        </span>
                                                                    @else
                                                                        —
                                                                    @endif
                                                                </td>
                                                                <td class="px-3 py-2 font-bold {{ $b->quantity <= 0 ? 'text-red-600' : 'text-slate-900' }}">
                                                                    {{ number_format($b->quantity) }} {{ $medicine->base_unit }}
                                                                </td>
                                                                <td class="px-3 py-2 text-slate-600">
                                                                    PKR {{ number_format($b->purchase_price, 2) }}
                                                                </td>
                                                                <td class="px-3 py-2 font-semibold text-slate-800">
                                                                    PKR {{ number_format($b->selling_price, 2) }}
                                                                </td>
                                                                <td class="px-3 py-2">
                                                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $b->quantity > 0 ? ($b->expiry_date && $b->expiry_date->isPast() ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700') : 'bg-slate-100 text-slate-500' }}">
                                                                        {{ $b->quantity > 0 ? ($b->expiry_date && $b->expiry_date->isPast() ? 'Expired' : 'Active') : 'Depleted' }}
                                                                    </span>
                                                                </td>
                                                                <td class="px-3 py-2 text-right">
                                                                    <div class="inline-flex items-center gap-1">
                                                                        <button
                                                                            type="button"
                                                                            wire:click="openAdjustStockModal({{ $b->id }})"
                                                                            class="px-2 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-semibold"
                                                                            title="Adjust Batch Stock"
                                                                        >
                                                                            ⚖️ Adjust
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            wire:click="deleteBatch({{ $b->id }})"
                                                                            wire:confirm="Are you sure you want to delete this batch ({{ $b->batch_number }})?"
                                                                            class="px-2 py-1 rounded bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-semibold"
                                                                            title="Delete Batch"
                                                                        >
                                                                            🗑️ Delete
                                                                        </button>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="8" class="px-3 py-4 text-center text-slate-400">
                                                                    No batches found for this product.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif

                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-16 text-center">
                                    <div class="max-w-md mx-auto space-y-3">
                                        <div class="w-16 h-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-2xl">
                                            🔍
                                        </div>
                                        <h3 class="text-base font-bold text-slate-800">No products found</h3>
                                        <p class="text-xs text-slate-500">
                                            No products matched your active filters or search term. Try resetting your search or adjusting filters above.
                                        </p>
                                        <button
                                            type="button"
                                            wire:click="$set('search', ''); $set('productTypeFilter', 'all'); $set('categoryFilter', ''); $set('supplierFilter', ''); $set('stockFilter', ''); $set('expiryFilter', '');"
                                            class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition"
                                        >
                                            Reset All Filters
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination & Footer --}}
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-xs text-slate-500">
                    Showing <span class="font-bold text-slate-700">{{ $medicines->firstItem() ?? 0 }}</span>
                    to <span class="font-bold text-slate-700">{{ $medicines->lastItem() ?? 0 }}</span>
                    of <span class="font-bold text-slate-700">{{ $medicines->total() }}</span> products
                </div>

                <div>
                    {{ $medicines->links() }}
                </div>
            </div>

        </section>

        {{-- ========================================================= --}}
        {{-- MODAL 1: QUICK VIEW PRODUCT DETAILS --}}
        {{-- ========================================================= --}}
        @if($showViewModal && $viewMedicine)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto border border-slate-200">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
                                💊
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">{{ $viewMedicine->name }}</h3>
                                <p class="text-xs text-slate-500">{{ $viewMedicine->generic_name ?: 'General Inventory Item' }} • {{ $viewMedicine->category?->name ?? 'Uncategorized' }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeViewModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center">✕</button>
                    </div>

                    {{-- Modal Body --}}
                    <div class="p-6 space-y-6">

                        {{-- Details Grid --}}
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Product Type</span>
                                <div class="text-sm font-bold text-slate-800 capitalize">{{ $viewMedicine->product_type }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Base Unit</span>
                                <div class="text-sm font-bold text-slate-800">{{ $viewMedicine->base_unit }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Total Stock</span>
                                <div class="text-sm font-bold text-emerald-700">{{ number_format($viewMedicine->batches->sum('quantity')) }} {{ $viewMedicine->base_unit }}</div>
                            </div>
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Barcode</span>
                                <div class="text-sm font-mono font-bold text-slate-800">{{ $viewMedicine->barcode ?: '—' }}</div>
                            </div>
                        </div>

                        {{-- Packaging Breakdown --}}
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Packaging & Multi-Unit Hierarchy</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                @forelse($viewMedicine->packagings as $pkg)
                                    <div class="p-3 bg-emerald-50/50 rounded-xl border border-emerald-100">
                                        <div class="font-bold text-xs text-emerald-900">{{ $pkg->display_name ?: $pkg->unit?->name }}</div>
                                        <div class="text-[11px] text-slate-600 mt-0.5">1x = {{ (int)$pkg->conversion_to_base }} {{ $viewMedicine->base_unit }}s</div>
                                        <div class="text-xs font-semibold text-emerald-700 mt-1">Sale: PKR {{ number_format($pkg->sale_price, 2) }}</div>
                                    </div>
                                @empty
                                    <div class="text-xs text-slate-400">Single Base Unit ({{ $viewMedicine->base_unit }})</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Active Batches --}}
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Active Batches</h4>
                            <div class="border border-slate-200 rounded-xl overflow-hidden">
                                <table class="w-full text-xs text-left">
                                    <thead class="bg-slate-50 font-semibold text-slate-600">
                                        <tr>
                                            <th class="px-3 py-2">Batch #</th>
                                            <th class="px-3 py-2">Supplier</th>
                                            <th class="px-3 py-2">Expiry</th>
                                            <th class="px-3 py-2">Stock</th>
                                            <th class="px-3 py-2">Selling Price</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @forelse($viewMedicine->batches as $b)
                                            <tr>
                                                <td class="px-3 py-2 font-mono font-bold">{{ $b->batch_number }}</td>
                                                <td class="px-3 py-2 text-slate-600">{{ $b->supplier?->name ?? '—' }}</td>
                                                <td class="px-3 py-2">{{ $b->expiry_date?->format('Y-m-d') ?? '—' }}</td>
                                                <td class="px-3 py-2 font-bold">{{ number_format($b->quantity) }}</td>
                                                <td class="px-3 py-2 font-semibold">PKR {{ number_format($b->selling_price, 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="px-3 py-4 text-center text-slate-400">No active batches</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>

                    <div class="px-6 py-3 border-t border-slate-100 bg-slate-50 flex justify-end">
                        <button type="button" wire:click="closeViewModal" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold transition">Close</button>
                    </div>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 2: EDIT MEDICINE MODAL --}}
        {{-- ========================================================= --}}
        @if($showEditModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto border border-slate-200">
                    
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-blue-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg">
                                ✏️
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Edit Product Information</h3>
                                <p class="text-xs text-slate-500">Update details, pricing, and reorder levels</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeEditModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center">✕</button>
                    </div>

                    <form wire:submit="updateMedicine" class="p-6 space-y-4">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Product Name *</label>
                                <input type="text" wire:model="edit_name" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none" required>
                                @error('edit_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            @if($edit_product_type === 'medicine')
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Generic Formula</label>
                                    <input type="text" wire:model="edit_generic_name" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                                </div>
                            @else
                                <div>
                                    <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Brand / Company</label>
                                    <input type="text" wire:model="edit_brand" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Category *</label>
                                <select wire:model="edit_category_id" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none" required>
                                    <option value="">Select Category</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('edit_category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Selling Price (Base Unit) *</label>
                                <input type="number" step="0.01" wire:model="edit_unit_price" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none" required>
                                @error('edit_unit_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Purchase Cost</label>
                                <input type="number" step="0.01" wire:model="edit_purchase_price" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alert Quantity</label>
                                <input type="number" wire:model="edit_alert_quantity" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Status</label>
                                <select wire:model="edit_status" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-300 outline-none">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Barcode</label>
                                <input type="text" wire:model="edit_barcode" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-blue-300 outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">SKU</label>
                                <input type="text" wire:model="edit_sku" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-blue-300 outline-none">
                            </div>
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" wire:click="closeEditModal" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-sm transition">Update Product</button>
                        </div>

                    </form>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 3: ADD NEW BATCH / RESTOCK --}}
        {{-- ========================================================= --}}
        @if($showAddBatchModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-slate-200">
                    
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-emerald-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg">
                                ➕
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Add New Batch / Restock</h3>
                                <p class="text-xs text-slate-500">Adding stock for <strong>{{ $batchMedicineName }}</strong></p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeAddBatchModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center">✕</button>
                    </div>

                    <form wire:submit="saveNewBatch" class="p-6 space-y-4">

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Batch Number *</label>
                                <input type="text" wire:model="new_batch_number" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm font-mono focus:ring-2 focus:ring-emerald-300 outline-none" required>
                                @error('new_batch_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Supplier</label>
                                <select wire:model="new_batch_supplier_id" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none">
                                    <option value="">Select Supplier</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ $sup->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Quantity *</label>
                                <input type="number" step="0.01" wire:model="new_batch_quantity" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none" placeholder="e.g. 50" required>
                                @error('new_batch_quantity') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Unit Type</label>
                                <select wire:model="new_batch_unit" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none">
                                    <option value="base">Base Unit</option>
                                    <option value="secondary">Secondary Unit (Strip/Pack)</option>
                                    <option value="primary">Primary Unit (Box/Carton)</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Purchase Cost (PKR)</label>
                                <input type="number" step="0.01" wire:model="new_batch_purchase_price" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none" placeholder="e.g. 35.00">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Selling Price (PKR) *</label>
                                <input type="number" step="0.01" wire:model="new_batch_selling_price" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none" placeholder="e.g. 50.00" required>
                                @error('new_batch_selling_price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Expiry Date</label>
                            <input type="date" wire:model="new_batch_expiry_date" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-emerald-300 outline-none">
                            @error('new_batch_expiry_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" wire:click="closeAddBatchModal" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition">Save Batch</button>
                        </div>

                    </form>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 4: STOCK ADJUSTMENT MODAL --}}
        {{-- ========================================================= --}}
        @if($showAdjustStockModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-amber-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                                ⚖️
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Adjust Stock</h3>
                                <p class="text-xs text-slate-500">Batch <strong>{{ $adjustBatchNumber }}</strong> ({{ $adjustMedicineName }})</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeAdjustStockModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center">✕</button>
                    </div>

                    <form wire:submit="saveStockAdjustment" class="p-6 space-y-4">

                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                            Current Stock in this Batch: <strong class="text-slate-900 font-bold text-sm">{{ number_format($adjustCurrentQty) }}</strong>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Adjustment Type *</label>
                            <select wire:model="adjustType" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-300 outline-none">
                                <option value="ADJUSTMENT_IN">➕ Stock Increase (Found Extra / Audit Correction)</option>
                                <option value="ADJUSTMENT_OUT">➖ Stock Decrease (Correction / Recount)</option>
                                <option value="DAMAGE">💔 Damaged Stock (Disposal)</option>
                                <option value="EXPIRED">⚠️ Expired Stock (Disposal)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Quantity to Adjust *</label>
                            <input type="number" step="0.01" wire:model="adjustQuantity" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-300 outline-none" placeholder="e.g. 5" required>
                            @error('adjustQuantity') <span class="text-red-500 text-xs font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Reason / Notes</label>
                            <input type="text" wire:model="adjustNotes" class="w-full h-10 px-3 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-amber-300 outline-none" placeholder="Reason for inventory change...">
                        </div>

                        <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                            <button type="button" wire:click="closeAdjustStockModal" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition">Apply Adjustment</button>
                        </div>

                    </form>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 5: DELETE MEDICINE CONFIRMATION --}}
        {{-- ========================================================= --}}
        @if($showDeleteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    
                    <div class="p-6 text-center space-y-4">
                        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                            🗑️
                        </div>

                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Delete Product</h3>
                            <p class="text-sm font-semibold text-red-600 mt-1">"{{ $deleteMedicineName }}"</p>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs text-slate-600 text-left space-y-1.5">
                            <div class="flex justify-between">
                                <span>Active Batches:</span>
                                <strong class="text-slate-900">{{ $deleteMedicineBatchesCount }}</strong>
                            </div>
                            <div class="flex justify-between">
                                <span>Current Total Stock:</span>
                                <strong class="text-slate-900">{{ number_format($deleteMedicineStock) }} units</strong>
                            </div>
                            @if($deleteHasSales)
                                <div class="pt-2 border-t border-slate-200 text-amber-800 text-[11px] font-semibold">
                                    ⚠️ This product has historical sales/purchase invoices. Deleting it will safely <strong>deactivate and zero its stock</strong> to keep your sales and tax reports accurate.
                                </div>
                            @else
                                <div class="pt-2 border-t border-slate-200 text-slate-500 text-[11px]">
                                    ✓ This product has no sales history. It will be completely removed from the system.
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="closeDeleteModal"
                                class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="deleteMedicine"
                                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm transition active:scale-95"
                            >
                                Confirm Delete
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 6: BULK DELETE CONFIRMATION --}}
        {{-- ========================================================= --}}
        @if($showBulkDeleteModal)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    
                    <div class="p-6 text-center space-y-4">
                        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                            ⚠️
                        </div>

                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Delete Multiple Products</h3>
                            <p class="text-sm font-semibold text-slate-600 mt-1">
                                Are you sure you want to delete <span class="text-red-600 font-bold">{{ count($selectedMedicines) }}</span> selected products?
                            </p>
                        </div>

                        <p class="text-xs text-slate-500">
                            Products with no past invoices will be permanently deleted. Products with past sales records will be archived/deactivated to preserve accounting balance.
                        </p>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="closeBulkDeleteModal"
                                class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="bulkDelete"
                                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-sm transition active:scale-95"
                            >
                                Delete All Selected
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 6B: DELETE ALL PRODUCTS CONFIRMATION (ADMIN ONLY) --}}
        {{-- ========================================================= --}}
        @if($showDeleteAllModal && auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->isAdmin()))
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/70 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn" id="delete-all-products-modal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full border border-red-200 overflow-hidden transform transition-all">
                    
                    {{-- Modal Header --}}
                    <div class="px-6 py-4.5 bg-gradient-to-r from-red-600 via-rose-600 to-red-700 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-xl shadow-inner text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold tracking-tight">Bulk Operation: Delete All Products</h3>
                                <p class="text-xs text-rose-100 font-medium">Administrator Authorization Required</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="closeDeleteAllModal"
                            class="text-white/80 hover:text-white rounded-lg p-1 hover:bg-white/10 transition"
                            title="Close modal"
                        >
                            ✕
                        </button>
                    </div>

                    {{-- Modal Content --}}
                    <div class="p-6 space-y-4">

                        {{-- Preview Stats Banner --}}
                        <div class="rounded-xl bg-gradient-to-br from-red-50 to-rose-50/60 border border-red-200/80 p-4">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold uppercase tracking-wider text-red-800">Affected Scope Preview</span>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-600 text-white shadow-xs">
                                    {{ number_format($deleteAllPreview['total_products'] ?? 0) }} Active Products
                                </span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                                <div class="bg-white/90 rounded-lg p-2.5 border border-red-100 shadow-2xs">
                                    <div class="text-lg font-black text-slate-800">{{ number_format($deleteAllPreview['total_products'] ?? 0) }}</div>
                                    <div class="text-[10px] uppercase font-semibold text-slate-500">Total Products</div>
                                </div>
                                <div class="bg-white/90 rounded-lg p-2.5 border border-amber-100 shadow-2xs">
                                    <div class="text-lg font-black text-amber-700">{{ number_format($deleteAllPreview['with_history_count'] ?? 0) }}</div>
                                    <div class="text-[10px] uppercase font-semibold text-slate-500">With History (Archived)</div>
                                </div>
                                <div class="bg-white/90 rounded-lg p-2.5 border border-emerald-100 shadow-2xs">
                                    <div class="text-lg font-black text-emerald-700">{{ number_format($deleteAllPreview['without_history_count'] ?? 0) }}</div>
                                    <div class="text-[10px] uppercase font-semibold text-slate-500">Unreferenced (Deleted)</div>
                                </div>
                                <div class="bg-white/90 rounded-lg p-2.5 border border-blue-100 shadow-2xs">
                                    <div class="text-lg font-black text-blue-700">{{ number_format($deleteAllPreview['batches_count'] ?? 0) }}</div>
                                    <div class="text-[10px] uppercase font-semibold text-slate-500">Batches Affected</div>
                                </div>
                            </div>
                        </div>

                        {{-- Data Integrity & Financial Explanation --}}
                        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 space-y-2 text-xs text-slate-600">
                            <div class="flex items-center gap-1.5 font-bold text-slate-900 text-xs">
                                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                <span>Regulatory & Financial Data Integrity Safeguards:</span>
                            </div>
                            <ul class="list-disc list-inside space-y-1 pl-1 text-[11px] leading-relaxed text-slate-600">
                                <li><strong>Preserved Financial Records:</strong> Invoices, sales items, purchase records, returns, and stock movement logs are strictly preserved.</li>
                                <li><strong>Soft Deletion & Archiving:</strong> Products with existing sales or ledger dependencies will be safely archived and hidden from active inventory, maintaining receipt links without cascade deletions.</li>
                                <li><strong>Safe Permanent Deletion:</strong> Products with zero transaction dependencies will be permanently deleted safely.</li>
                                <li><strong>Audit Logging:</strong> This bulk operation is logged with your administrator identity, IP address, and timestamp.</li>
                            </ul>
                        </div>

                        {{-- Confirmation Input Requirement --}}
                        <div class="pt-2">
                            <label for="delete-all-confirmation-input" class="block text-xs font-bold text-slate-800 mb-1.5">
                                Type <span class="font-mono bg-red-100 text-red-800 px-1.5 py-0.5 rounded border border-red-200 select-all">DELETE ALL PRODUCTS</span> below to confirm:
                            </label>
                            <input
                                type="text"
                                id="delete-all-confirmation-input"
                                wire:model.live="deleteAllConfirmationInput"
                                placeholder="DELETE ALL PRODUCTS"
                                autocomplete="off"
                                spellcheck="false"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 focus:border-red-500 focus:ring-2 focus:ring-red-200 text-sm font-semibold tracking-wide text-slate-900 transition placeholder:text-slate-400 uppercase"
                            />
                            @if(!empty($deleteAllConfirmationInput) && trim($deleteAllConfirmationInput) !== 'DELETE ALL PRODUCTS')
                                <p class="text-[11px] text-red-600 mt-1 font-semibold flex items-center gap-1">
                                    <span>✕</span> Phrase does not match exactly.
                                </p>
                            @elseif(trim($deleteAllConfirmationInput) === 'DELETE ALL PRODUCTS')
                                <p class="text-[11px] text-emerald-600 mt-1 font-semibold flex items-center gap-1">
                                    <span>✓</span> Phrase confirmed. You may proceed.
                                </p>
                            @endif
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                wire:click="closeDeleteAllModal"
                                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition"
                            >
                                Cancel
                            </button>

                            <button
                                type="button"
                                wire:click="executeDeleteAll"
                                wire:loading.attr="disabled"
                                id="btn-confirm-delete-all"
                                @if(trim($deleteAllConfirmationInput) !== 'DELETE ALL PRODUCTS') disabled @endif
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-extrabold shadow-sm transition active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed disabled:pointer-events-none @if(trim($deleteAllConfirmationInput) === 'DELETE ALL PRODUCTS') bg-red-600 hover:bg-red-700 text-white shadow-red-200 @else bg-slate-200 text-slate-400 @endif"
                            >
                                <span wire:loading.remove wire:target="executeDeleteAll">
                                    Confirm Bulk Delete
                                </span>
                                <span wire:loading wire:target="executeDeleteAll" class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Processing Bulk Deletion...
                                </span>
                            </button>
                        </div>

                    </div>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 7: BARCODE GENERATOR & THERMAL PRINT --}}
        {{-- ========================================================= --}}
        @if($showBarcodeModal && $barcodeMedicine)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-amber-50 to-white">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg">
                                🏷️
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Barcode Label Generator</h3>
                                <p class="text-xs text-slate-500">{{ $barcodeMedicine->name }}</p>
                            </div>
                        </div>
                        <button type="button" wire:click="closeBarcodeModal" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center">✕</button>
                    </div>

                    <div class="p-6 space-y-4">

                        {{-- Barcode Label Preview Card --}}
                        <div id="printable-barcode-tag" class="p-4 bg-white border-2 border-dashed border-slate-300 rounded-xl text-center space-y-2">
                            <div class="font-extrabold text-sm text-slate-900 leading-tight">
                                {{ $barcodeMedicine->name }}
                            </div>

                            @if($barcodeShowGeneric && $barcodeMedicine->generic_name)
                                <div class="text-[10px] text-slate-500">
                                    {{ $barcodeMedicine->generic_name }}
                                </div>
                            @endif

                            {{-- Visual Barcode Simulator --}}
                            <div class="py-2 flex flex-col items-center justify-center">
                                <div class="font-mono text-xl tracking-[0.25em] font-black text-slate-800 border-y-2 border-slate-800 px-4 py-1">
                                    ||| | |||| | ||| |||| |
                                </div>
                                <div class="font-mono text-xs text-slate-600 mt-1 font-bold">
                                    {{ $barcodeMedicine->barcode ?: ($barcodeMedicine->sku ?: 'MED-' . str_pad($barcodeMedicine->id, 6, '0', STR_PAD_LEFT)) }}
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs font-bold text-slate-800 pt-1 border-t border-slate-100 px-2">
                                @if($barcodeShowPrice)
                                    <span class="text-emerald-700">PKR {{ number_format($barcodeMedicine->selling_price, 2) }}</span>
                                @endif
                                @if($barcodeShowExpiry && $barcodeMedicine->batches->first()?->expiry_date)
                                    <span class="text-slate-500 text-[10px]">Exp: {{ $barcodeMedicine->batches->first()->expiry_date->format('m/Y') }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Label Options --}}
                        <div class="space-y-2 text-xs text-slate-700 font-medium">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="barcodeShowPrice" class="w-4 h-4 text-emerald-600 rounded">
                                <span>Include Retail Selling Price</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="barcodeShowExpiry" class="w-4 h-4 text-emerald-600 rounded">
                                <span>Include Batch Expiry Date</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" wire:model.live="barcodeShowGeneric" class="w-4 h-4 text-emerald-600 rounded">
                                <span>Include Generic Formula</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button
                                type="button"
                                wire:click="closeBarcodeModal"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition"
                            >
                                Close
                            </button>
                            <button
                                type="button"
                                onclick="window.print()"
                                class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-sm transition flex items-center gap-1.5"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                <span>Print Thermal Label</span>
                            </button>
                        </div>

                    </div>

                </div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MODAL 6: NEW PRODUCT CONFIRMATION --}}
        {{-- ========================================================= --}}
        @if($showNewProductConfirmation)
            <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 animate-fadeIn">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full border border-slate-200">
                    <div class="p-6 text-center space-y-4">
                        <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto text-2xl shadow-inner">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-900">Confirm New Product</h3>
                            <p class="text-sm font-semibold text-amber-600 mt-1">"{{ $name }}"</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-xs text-slate-600 text-left space-y-1.5">
                            Are you sure you want to manually create this new product instead of selecting an existing one or using AI auto-fill?
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button
                                type="button"
                                wire:click="cancelSaveNewProduct"
                                class="flex-1 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-bold transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                wire:click="confirmSaveNewProduct"
                                class="flex-1 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-sm transition active:scale-95"
                            >
                                Confirm & Save
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    {{-- ============================================================= --}}
    {{-- AUTO-SUGGESTION DATALISTS --}}
    {{-- ============================================================= --}}
    <datalist id="product-name-suggestions">
        @foreach($suggestedProductNames as $pn)
            <option value="{{ $pn }}">
        @endforeach
    </datalist>

    <datalist id="brand-suggestions">
        @foreach($suggestedBrands as $b)
            <option value="{{ $b }}">
        @endforeach
    </datalist>

    <datalist id="generic-suggestions">
        @foreach($suggestedGenerics as $g)
            <option value="{{ $g }}">
        @endforeach
    </datalist>

    <datalist id="manufacturer-suggestions">
        @foreach($suggestedManufacturers as $m)
            <option value="{{ $m }}">
        @endforeach
    </datalist>

    <datalist id="dosage-form-suggestions">
        @foreach($suggestedDosageForms as $df)
            <option value="{{ $df }}">
        @endforeach
    </datalist>

    <datalist id="strength-suggestions">
        @foreach($suggestedStrengths as $st)
            <option value="{{ $st }}">
        @endforeach
    </datalist>

    <datalist id="stock-qty-suggestions">
        <option value="5">
        <option value="10">
        <option value="20">
        <option value="25">
        <option value="50">
        <option value="100">
        <option value="200">
        <option value="250">
        <option value="500">
        <option value="1000">
    </datalist>

    {{-- ============================================================= --}}
    {{-- LOCAL MEDICINE SEARCH API --}}
    {{-- ============================================================= --}}
    @script
    <script>
        (() => {
            const input = document.getElementById('medicine-search');
            const box = document.getElementById('medicine-suggestions');

            if (!input || !box) {
                return;
            }

            let timer = null;

            input.addEventListener('input', function () {
                clearTimeout(timer);
                const query = this.value.trim();

                if (query.length < 1) {
                    box.innerHTML = '';
                    box.classList.add('hidden');
                    return;
                }

                timer = setTimeout(async () => {
                    try {
                        const response = await fetch(
                            `/api/medicines/search?q=${encodeURIComponent(query)}`,
                            {
                                headers: {
                                    'Accept': 'application/json'
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error('API request failed');
                        }

                        const medicines = await response.json();
                        box.innerHTML = '';

                        if (!medicines.length) {
                            box.innerHTML = `
                                <div class="px-4 py-3 text-sm text-slate-500">
                                    No medicine found
                                </div>
                            `;
                            box.classList.remove('hidden');
                            return;
                        }

                        medicines.forEach(medicine => {
                            const button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'w-full text-left px-4 py-3 hover:bg-emerald-50 border-b border-slate-100 last:border-0';
                            button.innerHTML = `
                                <div class="font-medium text-slate-800">
                                    ${escapeHtml(medicine.name)}
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    ${escapeHtml(medicine.generic_name ?? '')}
                                    ${medicine.brand ? ' • ' + escapeHtml(medicine.brand) : ''}
                                </div>
                            `;

                            button.addEventListener('click', () => {
                                input.value = medicine.name;
                                if (window.Livewire) {
                                    const component = input.closest('[wire\\:id]');
                                    if (component) {
                                        Livewire.find(component.getAttribute('wire:id')).set('name', medicine.name);
                                    }
                                }
                                box.innerHTML = '';
                                box.classList.add('hidden');
                            });

                            box.appendChild(button);
                        });

                        box.classList.remove('hidden');
                    } catch (error) {
                        console.error('Medicine API error:', error);
                        box.innerHTML = `
                            <div class="px-4 py-3 text-sm text-red-500">
                                Unable to search medicines
                            </div>
                        `;
                        box.classList.remove('hidden');
                    }
                }, 250);
            });

            document.addEventListener('click', function (event) {
                if (!input.contains(event.target) && !box.contains(event.target)) {
                    box.classList.add('hidden');
                }
            });

            function escapeHtml(value) {
                const div = document.createElement('div');
                div.textContent = value ?? '';
                return div.innerHTML;
            }
        })();
    </script>
    @endscript

</div>