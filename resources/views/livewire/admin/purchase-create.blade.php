<div class="space-y-6">

    {{-- ============================================================
         PAGE HEADER
    ============================================================= --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">

        <div>
            <div class="flex items-center gap-3">

                <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-200
                            flex items-center justify-center text-blue-600">

                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-6 h-6"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor"
                         stroke-width="2">

                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2 4h13m-13 0a2 2 0 100 4 2 2 0 000-4zm11 0a2 2 0 100 4 2 2 0 000-4z"/>

                    </svg>

                </div>

                <div>

                    <h1 class="text-2xl font-bold text-gray-900">
                        New Purchase Entry
                    </h1>

                    <p class="text-sm text-gray-500 mt-0.5">
                        Create a new medicine purchase
                    </p>

                </div>

            </div>
        </div>


        <a href="{{ url('/purchases') }}"
           class="inline-flex items-center justify-center gap-2
                  px-4 py-2.5 rounded-lg
                  border border-gray-300 bg-white
                  text-gray-700 text-sm font-semibold
                  hover:bg-gray-50 transition">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-4 h-4"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M15 19l-7-7 7-7"/>

            </svg>

            Back to Purchases

        </a>

    </div>


    {{-- ============================================================
         FLASH MESSAGE
    ============================================================= --}}
    @if (session()->has('message'))

        <div class="flex items-center gap-3 px-4 py-3 rounded-xl
                    bg-green-50 border border-green-200 text-green-700">

            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5 flex-shrink-0"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor"
                 stroke-width="2">

                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M5 13l4 4L19 7"/>

            </svg>

            <span class="text-sm font-medium">
                {{ session('message') }}
            </span>

        </div>

    @endif


    {{-- ============================================================
         PURCHASE INFORMATION
    ============================================================= --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-visible">

        <div class="px-5 py-4 bg-blue-50 border-b border-blue-100">

            <h2 class="font-bold text-gray-900">
                Purchase Information
            </h2>

            <p class="text-xs text-gray-500">
                Select supplier and purchase date
            </p>

        </div>


        <div class="p-5">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                {{-- PURCHASE DATE --}}
                <div>

                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Purchase Date
                    </label>

                    <input
                        type="date"
                        wire:model="purchase_date"
                        class="w-full h-11 px-3 rounded-lg
                               border border-gray-300 bg-white
                               text-sm text-gray-700
                               focus:border-blue-500
                               focus:ring-2 focus:ring-blue-100
                               outline-none">

                    @error('purchase_date')

                        <p class="mt-1 text-xs text-red-500">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- SUPPLIER --}}
                <div>

                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Supplier Name
                    </label>

                    <select
                        wire:model="supplier_id"
                        class="w-full h-11 px-3 rounded-lg
                               border border-gray-300 bg-white
                               text-sm text-gray-700
                               focus:border-blue-500
                               focus:ring-2 focus:ring-blue-100
                               outline-none">

                        <option value="">
                            Select Supplier
                        </option>

                        @foreach($suppliers as $supplier)

                            <option value="{{ $supplier->id }}">
                                {{ $supplier->name }}
                            </option>

                        @endforeach

                    </select>

                    @error('supplier_id')

                        <p class="mt-1 text-xs text-red-500">
                            {{ $message }}
                        </p>

                    @enderror

                </div>


                {{-- INVOICE NUMBER --}}
                <div>

                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Invoice Number
                    </label>

                    <input
                        type="text"
                        wire:model="invoice_number"
                        class="w-full h-11 px-3 rounded-lg
                               border border-gray-300 bg-gray-50
                               text-sm text-gray-700
                               focus:border-blue-500
                               focus:ring-2 focus:ring-blue-100
                               outline-none">

                    @error('invoice_number')

                        <p class="mt-1 text-xs text-red-500">
                            {{ $message }}
                        </p>

                    @enderror

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         MEDICINE / PRODUCT ITEMS
    ============================================================= --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-visible">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 px-5 py-4 bg-blue-50 border-b border-blue-100">
            <div>
                <h2 class="text-base font-bold text-gray-900">
                    Purchase Items
                </h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    Search product/medicine name and click Add to add items to invoice
                </p>
            </div>

            <div class="text-sm font-semibold text-blue-700">
                {{ count($cart) }} item(s) in cart
            </div>
        </div>

        {{-- CART ERROR --}}
        @error('cart')
            <div class="px-5 py-3 bg-red-50 border-b border-red-200 text-sm text-red-600">
                {{ $message }}
            </div>
        @enderror

        {{-- ============================================================
             ELEVATED ADD PRODUCT BAR (Z-50 DROPDOWN DOES NOT CLIP)
        ============================================================= --}}
        <div class="p-5 bg-slate-50/90 border-b border-gray-200 relative z-30">
            @php
                $selectedMedicine = $this->selectedMedicine;
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 mr-2">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        Add Item:
                    </span>
                    <button
                        type="button"
                        wire:click="$set('product_type', 'all')"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition {{ $product_type === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        All Items
                    </button>
                    <button
                        type="button"
                        wire:click="$set('product_type', 'medicine')"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ $product_type === 'medicine' ? 'bg-blue-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        <span>💊</span> Medicine
                    </button>
                    <button
                        type="button"
                        wire:click="$set('product_type', 'general')"
                        class="px-2.5 py-1 rounded-lg text-xs font-bold transition flex items-center gap-1 {{ $product_type === 'general' ? 'bg-amber-600 text-white shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                    >
                        <span>🛒</span> General Store
                    </button>
                </div>

                @if($medicine_id)
                    <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Selected: {{ $selectedMedicine?->name }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

                {{-- 1. MEDICINE SEARCH (Col 4) --}}
                <div class="md:col-span-4 relative" x-data="{ open: false }" @click.outside="open = false">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">
                        Search Product / Medicine <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.35-5.65a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/>
                            </svg>
                        </div>

                        <input
                            type="text"
                            wire:model.live.debounce.300ms="medicineSearch"
                            @focus="open = true"
                            @input="open = true"
                            placeholder="Type product name to search database..."
                            autocomplete="off"
                            class="w-full h-11 pl-9 pr-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-800 font-medium placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                        />
                    </div>

                    {{-- SEARCH DROPDOWN POPUP --}}
                    <div
                        x-show="open"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-cloak
                        class="absolute z-50 left-0 mt-1.5 w-full sm:w-[460px] bg-white border border-blue-200 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-black/5"
                    >
                        <div class="px-3 py-2 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <span class="text-xs font-semibold text-gray-600">Products from Database</span>
                            <span class="text-[10px] text-gray-400">{{ $medicines->count() }} result(s)</span>
                        </div>

                        <div class="max-h-64 overflow-y-auto divide-y divide-gray-100">
                            @forelse($medicines as $medicine)
                                <button
                                    type="button"
                                    wire:key="med-search-{{ $medicine->id }}"
                                    wire:click="selectMedicine({{ $medicine->id }})"
                                    @click="open = false"
                                    class="w-full px-3.5 py-2.5 text-left hover:bg-blue-50 transition"
                                >
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-800 truncate">{{ $medicine->name }}</span>
                                                @if($medicine->is_general)
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-amber-100 text-amber-800">General</span>
                                                @else
                                                    <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-blue-100 text-blue-800">Medicine</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-500 mt-0.5">
                                                {{ $medicine->category?->name ?? 'General' }}
                                                @if($medicine->generic_name)
                                                    <span class="mx-1">•</span>{{ $medicine->generic_name }}
                                                @endif
                                                @if($medicine->brand)
                                                    <span class="mx-1">•</span>{{ $medicine->brand }}
                                                @endif
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            @if($medicine->purchase_price !== null && (float)$medicine->purchase_price > 0)
                                                <div class="text-xs font-semibold text-emerald-600">Buy: PKR {{ number_format((float)$medicine->purchase_price, 2) }}</div>
                                            @endif
                                            @if($medicine->unit_price !== null)
                                                <div class="text-[10px] text-gray-500">Sell: PKR {{ number_format((float)$medicine->unit_price, 2) }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            @empty
                                @if(empty($masterMedicines) || $masterMedicines->isEmpty())
                                    <div class="px-4 py-6 text-center text-xs text-gray-500">No matching product found.</div>
                                @endif
                            @endforelse

                            {{-- MASTER DRUG FORMULARY ITEMS --}}
                            @if(isset($masterMedicines) && $masterMedicines->isNotEmpty())
                                <div class="px-3 py-1.5 bg-purple-50 border-t border-b border-purple-100 flex items-center justify-between">
                                    <span class="text-xs font-bold text-purple-800 flex items-center gap-1">
                                        <i class="fa-solid fa-cloud-arrow-down text-[10px]"></i> Master Drug Formulary
                                    </span>
                                    <span class="text-[10px] text-purple-600 font-medium">{{ $masterMedicines->count() }} new item(s)</span>
                                </div>

                                @foreach($masterMedicines as $master)
                                    <div
                                        wire:key="master-search-{{ $master->id }}"
                                        wire:click="promoteAndAddToPurchase({{ $master->id }})"
                                        @click="open = false"
                                        class="w-full px-3.5 py-2.5 text-left hover:bg-purple-50/80 transition border-b border-purple-50 flex items-center justify-between gap-3 cursor-pointer"
                                    >
                                        <div 
                                            class="min-w-0 flex-1"
                                            title="Click to import and add to purchase"
                                        >
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <span class="text-sm font-semibold text-gray-800 truncate">{{ $master->medicine_name }}</span>
                                                <span class="text-[9px] px-1.5 py-0.5 rounded font-bold bg-purple-100 text-purple-800 border border-purple-200">Master</span>
                                            </div>
                                            <div class="text-[11px] text-gray-500 mt-0.5">
                                                {{ $master->dosage_form ?: 'Tablet' }}
                                                @if($master->generic_name)
                                                    <span class="mx-1">•</span>{{ $master->generic_name }}
                                                @endif
                                                @if($master->manufacturer)
                                                    <span class="mx-1">•</span>{{ $master->manufacturer }}
                                                @endif
                                            </div>
                                        </div>

                                        <div class="text-right shrink-0 flex items-center gap-2">
                                            <button
                                                type="button"
                                                wire:click.stop="promoteAndAddToPurchase({{ $master->id }})"
                                                @click="open = false"
                                                class="text-[10px] bg-purple-600 hover:bg-purple-700 text-white px-2.5 py-1.5 rounded-xl font-bold transition shadow-xs flex items-center gap-1 cursor-pointer"
                                                title="Import and add directly to purchase items"
                                            >
                                                <i class="fa-solid fa-plus text-[9px]"></i> Add
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    @error('medicine_id')
                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. CATEGORY (Col 2 - Both Medicine & General Categories) --}}
                <div class="md:col-span-2">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-700">Category</label>
                        <span class="text-[10px] text-blue-600 font-medium">Both Types</span>
                    </div>
                    <select
                        wire:model.live="category_id"
                        class="w-full h-11 px-2.5 rounded-lg border border-gray-300 bg-white text-xs font-semibold text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none truncate"
                    >
                        <option value="">All Categories (Auto)</option>
                        <optgroup label="💊 Medicine Categories">
                            @foreach($medicineCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="🛒 General Store Categories">
                            @foreach($generalCategories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                {{-- 3. QTY & PACKAGING UNIT (Col 3 - Medicine & General Store Packaging Units) --}}
                <div class="md:col-span-3">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-semibold text-gray-700">
                            Qty & Unit <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[10px] text-blue-600 font-medium">Packaging Unit</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <input
                            type="number"
                            min="0.01"
                            step="any"
                            wire:model.live="quantity"
                            placeholder="1"
                            class="w-16 sm:w-20 h-11 px-2 border border-gray-300 rounded-lg text-center text-sm font-bold text-gray-800 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none shrink-0"
                        />
                        <select
                            wire:model.live="packaging_id"
                            class="h-11 px-2 border border-gray-300 rounded-lg text-xs bg-white font-semibold text-blue-900 focus:ring-2 focus:ring-blue-100 outline-none w-full truncate"
                        >
                            @if($selectedMedicine)
                                <optgroup label="📦 Configured Packagings ({{ $selectedMedicine->is_general ? 'General' : 'Medicine' }})">
                                    @forelse($selectedMedicine->packagings as $pkg)
                                        <option value="{{ $pkg->id }}">
                                            {{ $pkg->display_name ?: ($pkg->unit?->name . ' (' . (int)$pkg->conversion_to_base . 'x Base)') }}
                                        </option>
                                    @empty
                                        <option value="">{{ $selectedMedicine->base_unit ?: ($selectedMedicine->is_general ? 'Piece' : 'Tablet') }} (1x Base)</option>
                                    @endforelse
                                </optgroup>
                            @endif

                            <optgroup label="✨ All Packaging Units (General & Medicine)">
                                @foreach($availableUnits as $unit)
                                    <option value="{{ $unit->name }}">
                                        {{ $unit->name }} ({{ $unit->symbol ?: $unit->unit_id }})
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    @error('quantity')
                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>


                {{-- 4. PURCHASE PRICE (Col 2) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Purchase Price (PKR) <span class="text-red-500">*</span></label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model="purchase_price"
                        placeholder="0.00"
                        class="w-full h-11 px-3 border border-gray-300 rounded-lg text-right text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                    />
                    @error('purchase_price')
                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 5. SALE PRICE (Col 1) --}}
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Sale Price</label>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        wire:model="selling_price"
                        placeholder="0.00"
                        class="w-full h-11 px-2 border border-gray-300 rounded-lg text-right text-sm font-semibold focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                    />
                </div>

                {{-- 6. EXPIRY DATE (Col 2) --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Expiry Date</label>
                    <input
                        type="date"
                        wire:model="expiry_date"
                        class="w-full h-11 px-2 border border-gray-300 rounded-lg text-xs font-medium text-gray-700 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none"
                    />
                    @error('expiry_date')
                        <p class="mt-1 text-xs text-red-500 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 7. ADD BUTTON (Col 1) --}}
                <div class="md:col-span-1">
                    <button
                        type="button"
                        wire:click="addItem"
                        wire:loading.attr="disabled"
                        wire:target="addItem"
                        class="w-full h-11 inline-flex items-center justify-center gap-1 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm shadow-md transition disabled:opacity-50"
                        title="Add item to purchase cart"
                    >
                        <span wire:loading.remove wire:target="addItem">+ Add</span>
                        <span wire:loading wire:target="addItem">...</span>
                    </button>
                </div>

            </div>
        </div>

        {{-- ============================================================
             CLEAN CART TABLE
        ============================================================= --}}
        <div class="overflow-x-auto min-h-[220px]">
            <table class="w-full min-w-[1000px] text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-700 text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left w-12">#</th>
                        <th class="px-4 py-3 text-left">Medicine / Product Name</th>
                        <th class="px-4 py-3 text-left w-40">Category</th>
                        <th class="px-4 py-3 text-center w-40">Qty & Unit</th>
                        <th class="px-4 py-3 text-right w-36">Sale Price</th>
                        <th class="px-4 py-3 text-right w-36">Purchase Price</th>
                        <th class="px-4 py-3 text-left w-36">Expiry Date</th>
                        <th class="px-4 py-3 text-right w-36">Total</th>
                        <th class="px-4 py-3 text-center w-20">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100">
                    @forelse($cart as $index => $item)
                        <tr wire:key="cart-item-{{ $index }}" class="hover:bg-blue-50/30 transition">
                            <td class="px-4 py-3.5 text-gray-500 font-medium">{{ $index + 1 }}</td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-gray-800">{{ $item['medicine_name'] }}</div>
                                @if(!empty($item['batch_number']))
                                    <div class="text-[11px] text-gray-400 mt-0.5">Batch: {{ $item['batch_number'] }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-gray-600 font-medium">{{ $item['category_name'] ?? 'General' }}</td>
                            <td class="px-4 py-3.5 text-center font-bold">
                                <div>
                                    {{ number_format((float) $item['quantity'], 0) }} <span class="text-xs font-bold text-blue-600">{{ $item['unit'] ?? '' }}</span>
                                </div>
                                @if(!empty($item['unit']) && $item['unit'] !== 'base')
                                    <div class="text-[10px] text-gray-400 font-normal">
                                        = {{ number_format((float) ($item['base_quantity'] ?? $item['quantity']), 0) }} Base Units
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-medium text-gray-700">PKR {{ number_format((float) $item['selling_price'], 2) }}</td>
                            <td class="px-4 py-3.5 text-right font-medium text-gray-700">PKR {{ number_format((float) $item['purchase_price'], 2) }}</td>
                            <td class="px-4 py-3.5 text-gray-700 font-medium">
                                @if(!empty($item['expiry_date']))
                                    {{ \Carbon\Carbon::parse($item['expiry_date'])->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-gray-900">PKR {{ number_format((float) $item['total'], 2) }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <button
                                    type="button"
                                    wire:click="removeItem({{ $index }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 text-red-500 hover:bg-red-50 hover:text-red-700 transition"
                                    title="Remove item"
                                >
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 01-1-1h-4a1 1 0 01-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-3 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0v10l-8 4-8-4V7m16 0l-8 4m0 0L4 7m8 4v10"/>
                                    </svg>
                                    <span class="font-bold text-gray-600 text-base">No items in purchase invoice</span>
                                    <span class="text-xs text-gray-400 mt-1">Use the search panel above to select medicines and click <strong>+ Add</strong></span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if(count($cart) > 0)
                    <tfoot>
                        <tr class="bg-gray-50 border-t-2 border-gray-200 text-sm">
                            <td colspan="3" class="px-4 py-4 text-right font-extrabold text-gray-700">TOTAL</td>
                            <td class="px-4 py-4 text-center font-extrabold text-blue-700">{{ number_format($this->totalQuantity, 0) }}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td class="px-4 py-4 text-right font-extrabold text-blue-700">PKR {{ number_format($this->grandTotal, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- ============================================================
             PURCHASE FOOTER
        ============================================================= --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 px-5 py-4 bg-gray-50 border-t border-gray-200">
            <div class="text-sm text-gray-500">
                <span class="font-bold text-gray-800">{{ count($cart) }}</span> medicine item(s) •
                <span class="font-bold text-gray-800">{{ number_format($this->totalQuantity, 0) }}</span> units
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right">
                    <p class="text-xs text-gray-500 font-medium">Amount Payable</p>
                    <p class="text-xl font-black text-blue-700">PKR {{ number_format($this->grandTotal, 2) }}</p>
                </div>

                <button
                    type="button"
                    wire:click="saveInvoice"
                    wire:loading.attr="disabled"
                    wire:target="saveInvoice"
                    @disabled(count($cart) === 0)
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-lg bg-green-600 text-white font-bold text-sm hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md transition"
                >
                    <span wire:loading.remove wire:target="saveInvoice">Save Purchase</span>
                    <span wire:loading wire:target="saveInvoice">Saving...</span>
                </button>
            </div>
        </div>

    </div>





    


    

</div>