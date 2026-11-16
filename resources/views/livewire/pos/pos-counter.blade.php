<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-6rem)]" x-data="posCounterData()" wire:poll.5s>
    
    <!-- LEFT SIDE: Medicines Grid -->
    <div class="lg:col-span-7 flex flex-col bg-white p-5 rounded-2xl shadow-sm border border-gray-100 space-y-4 h-full overflow-hidden">
         
        @if(session()->has('message'))
            <div class="bg-emerald-50 border-l-4 border-emerald-500 p-3 text-emerald-700 text-xs font-semibold rounded-xl flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('message') }}</span>
                </div>
            </div>
        @endif

        @if(session()->has('error'))
            <div class="bg-red-50 border-l-4 border-red-500 p-3 text-red-700 text-xs font-semibold rounded-xl flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Select Products</h2>
                <p class="text-xs text-gray-500">Medicines & General Store Items</p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Product Type Filter Buttons --}}
                <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs font-bold border border-gray-200" role="tablist" aria-label="Product category filter">
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('all')"
                        class="px-2.5 py-1 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $productTypeFilter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                        aria-label="Show all products"
                        tabindex="0"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('medicine')"
                        class="px-2.5 py-1 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $productTypeFilter === 'medicine' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                        aria-label="Show medicines"
                        tabindex="0"
                    >
                        Medicines
                    </button>
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('general')"
                        class="px-2.5 py-1 rounded-lg transition focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $productTypeFilter === 'general' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                        aria-label="Show general store items"
                        tabindex="0"
                    >
                        General Store
                    </button>
                </div>

                <div class="relative w-48 sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-search text-xs"></i>
                    </span>
                    <input
                        id="pos-product-search"
                        type="text"
                        wire:model.live.debounce.250ms="search"
                        placeholder="Search product or scan barcode... (F2)"
                        aria-label="Product search"
                        autocomplete="off"
                        class="w-full pl-9 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 transition"
                    >
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/80 to-cyan-50/90 p-3 shadow-sm">
            <div class="flex items-center justify-between gap-2 mb-2">
                <h3 class="text-[11px] font-black uppercase tracking-[0.18em] text-blue-700">Keyboard Shortcuts</h3>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="showDebug = !showDebug" class="text-[9px] font-bold uppercase tracking-wider text-slate-500 hover:text-blue-700 bg-white/80 hover:bg-white px-2 py-0.5 rounded-full border border-slate-200 transition cursor-pointer">
                        <span x-text="showDebug ? 'Hide Debug' : 'Show Debug'"></span>
                    </button>
                    <span class="text-[9px] font-bold uppercase tracking-wider text-blue-600 bg-white/80 px-2 py-0.5 rounded-full border border-blue-200">POS</span>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2 text-[10px] text-gray-700">
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F2</kbd><span>Medicine Search</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F3</kbd><span>Customer Search</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F4</kbd><span>Checkout</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F5</kbd><span>Cash Payment</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F6</kbd><span>Partial Payment</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F7</kbd><span>Credit</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">F8</kbd><span>Print Receipt</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">Enter</kbd><span>Select / Confirm</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">Esc</kbd><span>Close Modal</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">Del</kbd><span>Remove Item</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">Ctrl+Q</kbd><span>Edit Qty</span></div>
                <div class="flex items-center gap-1.5"><kbd class="min-w-8 text-center px-1.5 py-0.5 rounded-md border border-blue-200 bg-white font-bold text-blue-700 shadow-xs">↑↓</kbd><span>Navigate</span></div>
            </div>
        </div>

        <div x-show="showDebug" class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3 text-[10px] text-slate-700" style="display: none;">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-600">Keyboard Debug</h4>
                <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 font-bold" :class="actionStatus === 'ok' ? 'text-emerald-700' : (actionStatus === 'fail' ? 'text-rose-700' : 'text-slate-600')" x-text="actionStatus.toUpperCase()"></span>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
                <div><span class="block text-slate-500">Last Key</span><span class="font-bold text-slate-800" x-text="lastKey || '—'"></span></div>
                <div><span class="block text-slate-500">event.key</span><span class="font-bold text-slate-800" x-text="lastKey || '—'"></span></div>
                <div><span class="block text-slate-500">event.code</span><span class="font-bold text-slate-800" x-text="lastCode || '—'"></span></div>
                <div><span class="block text-slate-500">ctrl</span><span class="font-bold text-slate-800" x-text="ctrlKey ? 'true' : 'false'"></span></div>
                <div><span class="block text-slate-500">alt</span><span class="font-bold text-slate-800" x-text="altKey ? 'true' : 'false'"></span></div>
                <div><span class="block text-slate-500">shift</span><span class="font-bold text-slate-800" x-text="shiftKey ? 'true' : 'false'"></span></div>
                <div><span class="block text-slate-500">repeat</span><span class="font-bold text-slate-800" x-text="repeatKey ? 'true' : 'false'"></span></div>
                <div><span class="block text-slate-500">shortcut</span><span class="font-bold text-slate-800" x-text="shortcutMatched || '—'"></span></div>
                <div class="col-span-2 sm:col-span-3 lg:col-span-3"><span class="block text-slate-500">action</span><span class="font-bold text-slate-800" x-text="actionLabel || '—'"></span></div>
            </div>
            <div class="mt-2 rounded-lg border border-slate-200 bg-white px-2 py-1 text-slate-700 font-mono" x-text="actionMessage || 'Waiting for keypress...'"></div>
        </div>

        <div class="flex-1 overflow-y-auto pr-2 grid grid-cols-2 md:grid-cols-3 gap-3 content-start">
            @forelse($medicines ?? [] as $medicine)
                @php
                    $physicalStock = (float) $medicine->getPhysicalStock();
                    $validStock = (float) $medicine->getAvailableStock();
                    $isZeroStock = $physicalStock <= 0;
                    $isExpiredStock = !$isZeroStock && $validStock <= 0;
                    $isDisabled = $isZeroStock || $isExpiredStock;
                    $expiredDate = $isExpiredStock ? $medicine->getLatestExpiredDate() : null;
                @endphp
                <div 
                    data-product-card
                    data-medicine-id="{{ $medicine->id }}"
                    wire:key="pos-product-{{ $medicine->id }}"
                    class="{{ $isDisabled ? 'bg-gray-100/90 border-gray-200 opacity-60 cursor-not-allowed select-none' : 'bg-gray-50 hover:bg-blue-50/80 border-gray-200/80 hover:border-blue-500 shadow-xs' }} border p-3.5 rounded-xl transition flex flex-col justify-between space-y-2 group select-none w-full text-left relative focus-within:ring-2 focus-within:ring-blue-300 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    tabindex="{{ $isDisabled ? -1 : 0 }}"
                    @if(!$isDisabled) wire:click="addToCart({{ $medicine->id }})" @endif
                >
                    <div class="w-full">
                        <div class="flex justify-between items-start gap-1">
                            <h4 
                                class="font-bold text-sm line-clamp-1 {{ $isDisabled ? 'text-gray-500 cursor-not-allowed' : 'text-gray-800 group-hover:text-blue-600 cursor-pointer' }} transition"
                                @if(!$isDisabled) wire:click="addToCart({{ $medicine->id }})" @endif
                                title="{{ $medicine->name }}"
                            >
                                {{ $medicine->name }}
                            </h4>

                            @if($isDisabled)
                                <button
                                    type="button"
                                    disabled
                                    class="text-[10px] bg-gray-300 text-gray-500 px-2.5 py-1 rounded-full font-bold shrink-0 ml-1 cursor-not-allowed opacity-75"
                                    title="{{ $isExpiredStock ? 'All batches are expired - Cannot add to cart' : 'Out of stock - Cannot add to cart' }}"
                                >
                                    <i class="fa-solid fa-ban text-[9px]"></i> Add
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="addToCart({{ $medicine->id }})"
                                    class="text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-full font-bold shrink-0 ml-1 transition cursor-pointer shadow-xs focus:outline-none focus:ring-2 focus:ring-blue-300"
                                    title="Add to cart"
                                >
                                    <i class="fa-solid fa-plus text-[9px]"></i> Add
                                </button>
                            @endif
                        </div>

                        <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                            @if($medicine->is_general)
                                <span class="text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded-md">General Store</span>
                            @else
                                <span class="text-[9px] bg-blue-100 text-blue-800 font-bold px-1.5 py-0.5 rounded-md">Medicine</span>
                            @endif
                            <span class="text-[11px] text-gray-500 line-clamp-1">{{ $medicine->category->name ?? 'General' }}</span>
                        </div>

                        @if($medicine->generic_name)
                            <p class="text-[10px] text-gray-400 line-clamp-1 italic mt-0.5">{{ $medicine->generic_name }}</p>
                        @endif

                        @if($medicine->packagings->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($medicine->packagings->where('allow_sale', true) as $pkg)
                                    @php
                                        $pkgConv = (float)($pkg->conversion_to_base ?: 1.0);
                                        $pkgDisabled = $isDisabled || ($pkgConv > $validStock);
                                    @endphp
                                    @if($pkgDisabled)
                                        <button
                                            type="button"
                                            disabled
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-gray-200/70 border border-gray-300 text-gray-400 cursor-not-allowed opacity-60"
                                            title="Insufficient valid stock for {{ $pkg->display_name ?: $pkg->unit?->name }}"
                                        >
                                            {{ $pkg->unit?->name ?: $pkg->display_name }} (Rs.{{ number_format($pkg->sale_price ?: 0, 0) }})
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            wire:click="addToCart({{ $medicine->id }}, {{ $pkg->id }})"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-white border border-blue-200 text-blue-800 hover:bg-blue-600 hover:text-white transition shadow-2xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300"
                                            title="Add 1 {{ $pkg->display_name ?: $pkg->unit?->name }} to cart"
                                        >
                                            {{ $pkg->unit?->name ?: $pkg->display_name }} (Rs.{{ number_format($pkg->sale_price ?: 0, 0) }})
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="w-full flex flex-col gap-1 pt-2 border-t border-gray-200/60 mt-auto">
                        <div class="flex justify-between items-center gap-1">
                            <span class="text-xs font-bold {{ $isDisabled ? 'text-gray-400' : 'text-blue-600' }}">
                                PKR {{ number_format($medicine->selling_price ?? $medicine->unit_price ?? 0, 2) }}
                            </span>

                            @if($isZeroStock)
                                <span class="text-[10px] bg-red-100 text-red-700 border border-red-200 px-2 py-0.5 rounded-full font-bold shrink-0 tracking-wide">
                                    <i class="fa-solid fa-ban text-[9px] mr-0.5"></i> OUT OF STOCK
                                </span>
                            @elseif($isExpiredStock)
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full font-bold shrink-0 tracking-wide">
                                        <i class="fa-solid fa-triangle-exclamation text-[9px] mr-0.5"></i> EXPIRED STOCK
                                    </span>
                                    <span class="text-[9px] text-amber-700 font-semibold mt-0.5" title="Physical stock: {{ $medicine->formatStockInUnits($physicalStock) }} (Expired)">
                                        {{ $medicine->formatStockInUnits($physicalStock) }} (Expired{{ $expiredDate ? ': ' . $expiredDate : '' }})
                                    </span>
                                </div>
                            @else
                                <div class="flex flex-col items-end">
                                    <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold truncate max-w-[160px]" title="Valid unexpired stock: {{ $medicine->formatAvailableStock() }}">
                                        AVAILABLE ({{ $medicine->formatAvailableStock() }})
                                    </span>
                                    @if($physicalStock > $validStock)
                                        <span class="text-[9px] text-amber-600 font-medium" title="Physical: {{ $medicine->formatStockInUnits($physicalStock) }}, Expired: {{ (int)($physicalStock - $validStock) }} {{ $medicine->base_unit }}s">
                                            ({{ (int)($physicalStock - $validStock) }} expired excluded)
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                @if(empty($masterMedicines) || $masterMedicines->isEmpty())
                    <div class="col-span-full py-12 text-center text-gray-400 text-sm">
                        No products found matching your query.
                    </div>
                @endif
            @endforelse

            {{-- MASTER CATALOG RESULTS SECTION --}}
            @if(isset($masterMedicines) && $masterMedicines->isNotEmpty())
                @foreach($masterMedicines as $master)
                    <div 
                        data-product-card
                        data-master-id="{{ $master->id }}"
                        wire:key="pos-master-{{ $master->id }}"
                        wire:click="promoteAndAddToCart({{ $master->id }})"
                        tabindex="0"
                        class="bg-purple-50/50 hover:bg-purple-50 border-purple-200/90 hover:border-purple-400 border p-3.5 rounded-xl transition flex flex-col justify-between space-y-2 group select-none w-full text-left relative shadow-xs cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-300"
                    >
                        <div class="w-full">
                            <div class="flex justify-between items-start gap-1">
                                <h4 
                                    class="font-bold text-sm line-clamp-1 text-gray-800 group-hover:text-purple-700 transition"
                                    title="Click to import and add: {{ $master->medicine_name }}"
                                >
                                    {{ $master->medicine_name }}
                                </h4>

                                <button
                                    type="button"
                                    wire:click.stop="promoteAndAddToCart({{ $master->id }})"
                                    class="text-[10px] bg-purple-600 hover:bg-purple-700 text-white px-2.5 py-1 rounded-full font-bold shrink-0 ml-1 transition cursor-pointer shadow-xs flex items-center gap-1 focus:outline-none focus:ring-2 focus:ring-purple-300"
                                    title="Import from Master & add to cart"
                                >
                                    <i class="fa-solid fa-plus text-[9px]"></i> Add
                                </button>
                            </div>

                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-[9px] bg-purple-100 text-purple-800 border border-purple-200 font-bold px-1.5 py-0.5 rounded-md flex items-center gap-1">
                                    <i class="fa-solid fa-cloud-arrow-down text-[8px]"></i> Master
                                </span>
                                <span class="text-[11px] text-gray-500 line-clamp-1">{{ $master->category ?: ($master->dosage_form ?: 'Medicine') }}</span>
                            </div>

                            @if($master->generic_name)
                                <p class="text-[10px] text-gray-400 line-clamp-1 italic mt-0.5">{{ $master->generic_name }}</p>
                            @endif

                            @if($master->manufacturer)
                                <p class="text-[10px] text-gray-400 line-clamp-1 mt-0.5">By: {{ $master->manufacturer }}</p>
                            @endif
                        </div>

                        <div class="w-full flex flex-col gap-1 pt-2 border-t border-purple-200/50 mt-auto">
                            <div class="flex justify-between items-center gap-1">
                                <span class="text-[10px] text-purple-700 font-medium">
                                    Form: {{ $master->dosage_form ?: 'Tablet' }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="promoteAndAddToCart({{ $master->id }})"
                                    class="text-[10px] text-purple-700 hover:text-purple-900 font-bold hover:underline cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-300"
                                >
                                    Click to Import →
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    </div>

    <!-- RIGHT SIDE: Current Cart -->
    <div class="lg:col-span-5 flex flex-col bg-white p-5 rounded-2xl shadow-sm border border-gray-100 justify-between h-full overflow-hidden">
        <div class="space-y-4">
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-lg font-bold text-gray-900">Current Cart</h2>
                <div class="flex items-center space-x-2">
                    <button wire:click="holdInvoice" class="bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white border border-amber-200/80 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-amber-300" title="Pause and hold current cart">
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold Invoice</span>
                    </button>
                    <button wire:click="clearCart" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200/80 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-300">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear Cart</span>
                    </button>
                </div>
            </div>

            <!-- Customer Section -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500">Customer</label>
                <div class="flex space-x-2">
                    <div class="relative flex-1">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <select id="pos-customer-select" wire:model.live="customer_id" class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200 appearance-none" aria-label="Customer selection">
                            <option value="">Walk-in Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" data-customer-option>{{ $customer->name }} ({{ $customer->phone }})</option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </div>
                    <button type="button" @click="showModal = true" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-300">
                        <i class="fa-solid fa-plus text-[10px]"></i>
                        <span>Add</span>
                    </button>
                </div>
                @if($customer_outstanding > 0)
                    <div class="mt-2 p-2 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-xs font-bold flex justify-between items-center">
                        <div>
                            <span>Previous Dues:</span>
                            <span class="text-lg">Rs. {{ number_format($customer_outstanding, 2) }}</span>
                        </div>
                        <a href="{{ route('pos.receive-payment', $customer_id) }}" target="_blank" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold transition shadow-sm focus:outline-none focus:ring-2 focus:ring-rose-300">
                            Receive
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Cart Items Table List -->
        <div class="flex-1 overflow-y-auto my-4 pr-1">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b text-xs text-gray-500 uppercase">
                        <th class="py-2 font-semibold">Item & Packaging Unit</th>
                        <th class="py-2 text-center font-semibold w-28">Qty</th>
                        <th class="py-2 text-right font-semibold">Price</th>
                        <th class="py-2 text-right font-semibold">Total</th>
                        <th class="py-2 text-center font-semibold w-10">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($cart ?? [] as $id => $item)
                        @php
                            $med = \App\Models\Medicine::with('packagings.unit')->find($item['medicine_id']);
                        @endphp
                        <tr wire:key="cart-item-{{ $id }}" tabindex="0" class="focus:outline-none focus:ring-2 focus:ring-blue-400 focus:bg-blue-50/50 rounded-lg transition" data-cart-row data-cart-key="{{ $id }}" data-qty="{{ $item['qty'] ?? 1 }}" data-name="{{ $item['name'] ?? 'Item' }}">
                            <td class="py-3">
                                <p class="font-bold text-gray-900 text-xs">{{ $item['name'] ?? '' }}</p>
                                <div class="flex flex-wrap gap-1 mt-1 mb-1">
                                    <span class="text-[9px] bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded border border-gray-200">
                                        Batch: <span class="font-bold">{{ $item['batch_number'] ?? 'N/A' }}</span>
                                    </span>
                                    @if(isset($item['expiry_date']) && $item['expiry_date'] !== 'N/A')
                                    <span class="text-[9px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded border border-red-100">
                                        Exp: <span class="font-bold">{{ $item['expiry_date'] }}</span>
                                    </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1 mt-1">
                                    <select
                                        class="text-[11px] bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5 text-blue-800 font-bold cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-300"
                                        wire:change="updateCartPackaging('{{ $id }}', $event.target.value)"
                                        aria-label="Select packaging for {{ $item['name'] ?? 'item' }}"
                                    >
                                        @if($med && $med->packagings->count() > 0)
                                            @foreach($med->packagings->where('allow_sale', true) as $pkg)
                                                <option value="{{ $pkg->id }}" {{ ($item['packaging_id'] ?? null) == $pkg->id ? 'selected' : '' }}>
                                                    {{ $pkg->display_name ?: $pkg->unit?->name }} (Rs.{{ number_format($pkg->sale_price ?: 0, 0) }})
                                                </option>
                                            @endforeach
                                        @else
                                            <option value="">{{ $item['unit'] }}</option>
                                        @endif
                                    </select>
                                </div>
                                <p class="text-[10px] text-gray-400 font-normal mt-0.5">
                                    = {{ number_format($item['base_qty'] ?? $item['qty']) }} {{ $item['base_unit'] ?? 'Base Units' }}
                                </p>
                            </td>
                            <td class="py-3 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <div class="inline-flex items-center border border-gray-200 rounded-xl bg-gray-50 overflow-hidden">
                                        <button wire:click="decrementQty('{{ $id }}')" class="px-2.5 py-1 text-gray-600 hover:bg-gray-200 transition font-bold cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-300">-</button>
                                        <span class="px-3 py-1 text-xs font-bold text-gray-800">{{ $item['qty'] ?? 1 }}</span>
                                        <button wire:click="incrementQty('{{ $id }}')" class="px-2.5 py-1 text-gray-600 hover:bg-gray-200 transition font-bold cursor-pointer focus:outline-none focus:ring-2 focus:ring-gray-300">+</button>
                                    </div>
                                    @php
                                        $currentValidStock = $med ? (float)$med->getAvailableStock() : 0;
                                        $currentPhysicalStock = $med ? (float)$med->getPhysicalStock() : 0;
                                        $itemBaseQty = (float)($item['base_qty'] ?? $item['qty']);
                                    @endphp
                                    @if($currentPhysicalStock <= 0)
                                        <span class="text-[9px] bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-bold" title="Product has no physical stock">
                                            Out of Stock
                                        </span>
                                    @elseif($currentValidStock <= 0)
                                        <span class="text-[9px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-bold" title="All batches are expired">
                                            Expired Stock
                                        </span>
                                    @elseif($currentValidStock < $itemBaseQty)
                                        <span class="text-[9px] bg-amber-100 text-amber-800 px-1.5 py-0.5 rounded font-bold" title="Available unexpired stock is lower than cart quantity">
                                            Only {{ floor($currentValidStock / ($item['conversion_to_base'] ?: 1)) }} left
                                        </span>
                                    @else
                                        <span class="text-[9px] text-emerald-600 font-bold" title="Max valid stock across all unexpired batches">
                                            Max: {{ floor($currentValidStock / ($item['conversion_to_base'] ?: 1)) }} {{ $item['unit'] ?? '' }}s
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 text-right text-xs font-medium text-gray-600">
                                {{ number_format($item['price'] ?? 0, 2) }}
                            </td>
                            <td class="py-3 text-right text-xs font-bold text-gray-900">
                                {{ number_format(($item['price'] ?? 0) * ($item['qty'] ?? 1), 2) }}
                            </td>
                            <td class="py-3 text-center">
                                <button wire:click="removeItem('{{ $id }}')" class="text-red-500 hover:text-red-700 p-1 transition cursor-pointer focus:outline-none focus:ring-2 focus:ring-red-200">
                                    <i class="fa-regular fa-trash-can"></i>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400 text-xs">
                                Cart is empty. Select items to start sale.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>


        <!-- Totals Summary & Checkout Footer -->
        <div class="border-t pt-3 space-y-2.5 bg-gray-50/50 p-4 rounded-2xl border border-gray-100">
            <div class="flex justify-between text-sm text-gray-600">
                <span>Subtotal ({{ collect($cart ?? [])->sum('qty') }} Items)</span>
                <span class="font-bold text-gray-800">{{ number_format($subtotal ?? 0, 2) }}</span>
            </div>
            
            <div class="flex justify-between items-center text-sm text-gray-600">
                <span>Discount</span>
                <div class="w-32">
                    <input type="number" wire:model.live="discount" placeholder="0.00" class="w-full px-3 py-1 bg-white border border-gray-200 rounded-xl text-right text-sm font-medium focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="flex items-center space-x-3 mb-2">
                <label class="flex items-center space-x-1 cursor-pointer">
                    <input type="radio" name="payment-type" wire:model.live="payment_type" value="full" class="text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-bold text-gray-700">Full</span>
                </label>
                <label class="flex items-center space-x-1 cursor-pointer" @if(!$customer_id) title="Requires Customer" @endif>
                    <input type="radio" name="payment-type" wire:model.live="payment_type" value="partial" class="text-blue-600 focus:ring-blue-500" @if(!$customer_id) disabled @endif>
                    <span class="text-xs font-bold {{ !$customer_id ? 'text-gray-400' : 'text-gray-700' }}">Partial</span>
                </label>
                <label class="flex items-center space-x-1 cursor-pointer" @if(!$customer_id) title="Requires Customer" @endif>
                    <input type="radio" name="payment-type" wire:model.live="payment_type" value="credit" class="text-blue-600 focus:ring-blue-500" @if(!$customer_id) disabled @endif>
                    <span class="text-xs font-bold {{ !$customer_id ? 'text-gray-400' : 'text-gray-700' }}">Credit</span>
                </label>
            </div>

            <div class="flex justify-between items-center text-sm text-gray-600">
                <span>Paid Amount</span>
                <div class="w-32">
                    <input id="pos-paid-amount" type="number" wire:model.live="paid_amount" placeholder="0.00" class="w-full px-3 py-1 bg-white border border-gray-200 rounded-xl text-right text-sm font-medium focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200" {{ $payment_type === 'credit' ? 'disabled' : '' }}>
                </div>
            </div>

            <div class="flex justify-between text-sm text-gray-600">
                <span>Change Return</span>
                <span class="font-bold text-blue-600">Rs. {{ number_format($changeAmount ?? 0, 2) }}</span>
            </div>
            
            @if($customer_id && $payment_type !== 'full')
                <div class="flex justify-between text-sm text-rose-600 mt-1">
                    <span>New Sale Dues</span>
                    <span class="font-bold">Rs. {{ number_format(max(0, $totalAmount - (is_numeric($paid_amount) ? (float)$paid_amount : 0)), 2) }}</span>
                </div>
            @endif
            @if($customer_outstanding > 0)
                <div class="flex justify-between text-sm text-rose-700 font-bold mt-1 pt-1 border-t border-rose-100">
                    <span>Total Customer Dues</span>
                    <span>Rs. {{ number_format($customer_outstanding + max(0, $totalAmount - (is_numeric($paid_amount) ? (float)$paid_amount : 0)), 2) }}</span>
                </div>
            @endif
            
            <div class="flex justify-between items-center pt-2 border-t border-gray-200">
                <span class="text-base font-extrabold text-gray-900">Total Amount</span>
                <span class="text-xl font-black text-emerald-700">Rs. {{ number_format($totalAmount ?? 0, 2) }}</span>
            </div>

            <button id="pos-checkout-button" wire:click="checkout" wire:loading.attr="disabled" class="w-full bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl shadow-md transition text-sm flex items-center justify-center space-x-2 mt-1 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                <i class="fa-solid fa-cash-register"></i>
                <span wire:loading.remove wire:target="checkout">Proceed to Checkout (F4)</span>
                <span wire:loading wire:target="checkout">Processing Sale...</span>
            </button>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
        <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md space-y-4" @click.away="showModal = false">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-gray-900">Add New Customer</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Customer Name *</label>
                    <input type="text" wire:model="new_customer_name" placeholder="Enter customer name" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Phone Number *</label>
                    <input type="text" wire:model="new_customer_phone" placeholder="0300-1234567" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-600 focus:ring-2 focus:ring-blue-200">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button type="button" @click="showModal = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300">Cancel</button>
                <button type="button" wire:click="saveCustomer" @click="showModal = false" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 shadow focus:outline-none focus:ring-2 focus:ring-blue-300">Save Customer</button>
            </div>
        </div>
    </div>

    <!-- Invoice Print Modal / Popup -->
    @if($showInvoiceModal && $completedSale)
    <div class="pos-invoice-modal fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden flex flex-col transform transition-all">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-700 text-white p-4 flex justify-between items-center shadow-md">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center text-white">
                        <i class="fa-solid fa-check text-lg"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm tracking-wide">Sale Completed Successfully</h3>
                        <p class="text-[11px] text-emerald-100 font-mono">{{ $completedSale->invoice_number }}</p>
                    </div>
                </div>
                <button wire:click="closeInvoiceModal" class="close-btn text-emerald-100 hover:text-white transition p-1 focus:outline-none focus:ring-2 focus:ring-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Receipt Content Preview -->
            <div class="p-5 font-mono text-xs text-gray-800 space-y-3.5 max-h-[65vh] overflow-y-auto bg-gray-50/70">
                <!-- Pharmacy Info -->
                <x-receipt-header 
                    subtitle="Official Sales Invoice / Receipt" 
                    :date="$completedSale->created_at->format('d-M-Y h:i A')" 
                />

                <div class="border-b border-dashed border-gray-300"></div>

                <!-- Invoice Meta -->
                <div class="grid grid-cols-2 text-[11px] gap-2">
                    <div>
                        <span class="text-gray-500 block text-[10px]">INVOICE NO</span>
                        <span class="font-bold text-gray-900">{{ $completedSale->invoice_number }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-gray-500 block text-[10px]">CUSTOMER</span>
                        <span class="font-bold text-gray-900">{{ $completedSale->customer?->name ?? 'Walk-in Customer' }}</span>
                    </div>
                </div>

                <div class="border-b border-dashed border-gray-300"></div>

                <!-- Items Table -->
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-gray-300 text-gray-500 text-[10px] uppercase">
                            <th class="py-1">Item</th>
                            <th class="py-1 text-center">Qty</th>
                            <th class="py-1 text-right">Price</th>
                            <th class="py-1 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-dashed divide-gray-200">
                        @foreach($completedSale->items as $item)
                            <tr>
                                <td class="py-1.5 font-bold text-gray-800">
                                    {{ $item->medicine?->name ?? 'Medicine' }}
                                </td>
                                <td class="py-1.5 text-center font-medium">
                                    {{ $item->quantity }} <span class="text-[10px] text-blue-600 font-bold">{{ $item->unit }}</span>
                                </td>
                                <td class="py-1.5 text-right font-medium">
                                    {{ number_format($item->unit_price, 2) }}
                                </td>
                                <td class="py-1.5 text-right font-bold">
                                    {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="border-b border-dashed border-gray-300"></div>

                <!-- Totals -->
                <div class="space-y-1 text-xs">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal:</span>
                        <span class="font-semibold">PKR {{ number_format($completedSale->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-emerald-700 font-extrabold text-sm border-t pt-1">
                        <span>Total Amount:</span>
                        <span>PKR {{ number_format($completedSale->total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Paid Amount:</span>
                        <span>PKR {{ number_format($completedSale->paid_amount, 2) }}</span>
                    </div>
                    
                    @if($completedSale->payment_status === 'partial' || $completedSale->payment_status === 'unpaid')
                        <div class="flex justify-between text-rose-600 font-bold border-t border-dashed border-rose-200 pt-1">
                            <span>Outstanding:</span>
                            <span>PKR {{ number_format($completedSale->total_amount - $completedSale->paid_amount, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-blue-600 font-bold">
                        <span>Change Return:</span>
                        <span>PKR {{ number_format($completedSale->change_amount, 2) }}</span>
                    </div>
                </div>

                <div class="border-b border-dashed border-gray-300"></div>

                <div class="text-center text-[10px] text-gray-400 font-sans space-y-0.5">
                    <p>Thank you for your purchase! Get well soon.</p>
                    @if(!empty($accountProfile?->footer_text))
                        <p class="text-[9px] text-gray-500">{{ $accountProfile->footer_text }}</p>
                    @endif
                </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="p-4 bg-white border-t flex items-center justify-between gap-3">
                <button id="pos-new-sale-btn" wire:click="closeInvoiceModal" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-xs transition focus:outline-none focus:ring-2 focus:ring-gray-300">
                    <i class="fa-solid fa-plus text-[10px] mr-1"></i> New Sale (Esc)
                </button>
                <a id="pos-print-receipt-btn" href="{{ url('/sales/' . $completedSale->id) }}" target="_blank" onclick="window.open(this.href, 'InvoicePrint', 'width=450,height=650'); return false;" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-xs transition text-center shadow flex items-center justify-center space-x-2 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Invoice (F8)</span>
                </a>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
(() => {
    function createPosCounterData() {
        return {
            showModal: false,
            showDebug: false,
            selectedProductIndex: -1,
            selectedCustomerIndex: -1,
            activeCartIndex: -1,
            productSearchId: 'pos-product-search',
            lastKey: '',
            lastCode: '',
            ctrlKey: false,
            altKey: false,
            shiftKey: false,
            repeatKey: false,
            shortcutMatched: '',
            actionLabel: '',
            actionStatus: 'idle',
            actionMessage: 'Ready. Press shortcut keys...',
            isProcessingCheckout: false,

            get productButtons() {
                return Array.from(document.querySelectorAll('[data-product-card]:not([disabled])'));
            },
            get customerButtons() {
                return Array.from(document.querySelectorAll('[data-customer-option]'));
            },
            get cartRows() {
                return Array.from(document.querySelectorAll('tr[data-cart-row]'));
            },

            setDebug(event, shortcut, action, message, status = 'ok') {
                this.lastKey = event?.key || '';
                this.lastCode = event?.code || '';
                this.ctrlKey = !!event?.ctrlKey;
                this.altKey = !!event?.altKey;
                this.shiftKey = !!event?.shiftKey;
                this.repeatKey = !!event?.repeat;
                this.shortcutMatched = shortcut;
                this.actionLabel = action;
                this.actionStatus = status;
                this.actionMessage = message;

                if (window.console && window.console.log) {
                    console.log(`[POS Shortcut] ${shortcut} => ${action}: ${message} (${status})`);
                }
            },

            focusProductSearch(selectText = true) {
                const el = document.getElementById(this.productSearchId);
                if (el) {
                    el.focus();
                    if (selectText && el.select) {
                        el.select();
                    }
                    this.selectedProductIndex = -1;
                }
            },

            focusCustomerSearch() {
                const el = document.getElementById('pos-customer-select');
                if (el) {
                    el.focus();
                }
            },

            focusPaidAmount() {
                const el = document.getElementById('pos-paid-amount');
                if (el && !el.disabled) {
                    el.focus();
                    el.select?.();
                }
            },

            focusCheckoutButton() {
                const el = document.getElementById('pos-checkout-button');
                if (el && !el.disabled) {
                    el.focus();
                }
            },

            focusSearchResult(index = 0) {
                const buttons = this.productButtons;
                if (!buttons.length) return;
                this.selectedProductIndex = Math.min(Math.max(index, 0), buttons.length - 1);
                const target = buttons[this.selectedProductIndex];
                if (target) {
                    target.focus();
                    target.scrollIntoView({ block: 'nearest' });
                }
            },

            focusCartRow(index = 0) {
                const rows = this.cartRows;
                if (!rows.length) return;
                this.activeCartIndex = Math.min(Math.max(index, 0), rows.length - 1);
                const target = rows[this.activeCartIndex];
                if (target) {
                    target.focus();
                    target.scrollIntoView({ block: 'nearest' });
                }
            },

            handleKeyDown(event) {
                const key = event.key || '';
                const code = event.code || '';
                const ctrl = event.ctrlKey;
                const alt = event.altKey;
                const shift = event.shiftKey;
                const target = event.target;
                const isFormElement = target && ['input', 'textarea', 'select'].includes(target.tagName.toLowerCase());
                const isSearchInput = target && target.id === this.productSearchId;
                const isPaidAmountInput = target && target.id === 'pos-paid-amount';
                const isGeneralTextInput = isFormElement && !isSearchInput && !isPaidAmountInput;

                // 1. F2 or Ctrl+Alt+M: Focus Medicine Search
                if (key === 'F2' || (ctrl && alt && (key.toLowerCase() === 'm' || code === 'KeyM'))) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.focusProductSearch(true);
                    this.setDebug(event, 'F2', 'Medicine Search', 'Medicine search input focused and text selected.', 'ok');
                    return;
                }

                // 2. F3 or Ctrl+Alt+C: Focus Customer Search
                if (key === 'F3' || (ctrl && alt && (key.toLowerCase() === 'c' || code === 'KeyC'))) {
                    event.preventDefault();
                    event.stopPropagation();
                    this.focusCustomerSearch();
                    this.setDebug(event, 'F3', 'Customer Search', 'Customer select focused.', 'ok');
                    return;
                }

                // 3. F4 or Ctrl+Alt+K: Proceed to Checkout
                if (key === 'F4' || (ctrl && alt && (key.toLowerCase() === 'k' || code === 'KeyK'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    const cartItemsCount = this.cartRows.length;
                    if (cartItemsCount === 0) {
                        this.setDebug(event, 'F4', 'Checkout', 'Cart is empty! Add medicines before checking out.', 'fail');
                        return;
                    }

                    const paidInput = document.getElementById('pos-paid-amount');
                    const paymentTypeRadios = document.querySelectorAll('input[name="payment-type"]');
                    let activePaymentType = 'full';
                    paymentTypeRadios.forEach(r => { if (r.checked) activePaymentType = r.value; });

                    // If not already in payment section, focus paid amount or checkout button
                    if (document.activeElement !== paidInput && activePaymentType !== 'credit') {
                        this.focusPaidAmount();
                        this.setDebug(event, 'F4', 'Checkout Workflow', 'Focused paid amount field for payment verification.', 'ok');
                        return;
                    }

                    // If already focused or credit payment, execute checkout
                    if ($wire && typeof $wire.checkout === 'function') {
                        this.setDebug(event, 'F4', 'Execute Checkout', 'Checkout action triggered.', 'ok');
                        $wire.checkout();
                    } else {
                        const checkoutBtn = document.getElementById('pos-checkout-button');
                        if (checkoutBtn) {
                            checkoutBtn.click();
                            this.setDebug(event, 'F4', 'Execute Checkout', 'Checkout button clicked.', 'ok');
                        }
                    }
                    return;
                }

                // 4. F5 or Ctrl+Alt+P: Cash / Full Payment
                if (key === 'F5' || (ctrl && alt && (key.toLowerCase() === 'p' || code === 'KeyP'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    if ($wire && typeof $wire.setPaymentType === 'function') {
                        $wire.setPaymentType('full');
                    }
                    const fullRadio = document.querySelector('input[name="payment-type"][value="full"]');
                    if (fullRadio) {
                        fullRadio.checked = true;
                    }
                    this.focusPaidAmount();
                    this.setDebug(event, 'F5', 'Cash Payment', 'Cash / Full payment selected and paid amount focused.', 'ok');
                    return;
                }

                // 5. F6 or Ctrl+Alt+J: Partial Payment
                if (key === 'F6' || (ctrl && alt && (key.toLowerCase() === 'j' || code === 'KeyJ' || key === '6'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    const customerSelect = document.getElementById('pos-customer-select');
                    if (!customerSelect || !customerSelect.value) {
                        this.focusCustomerSearch();
                        this.setDebug(event, 'F6', 'Partial Payment', 'Partial payment requires a registered customer. Customer select focused.', 'fail');
                        return;
                    }

                    if ($wire && typeof $wire.setPaymentType === 'function') {
                        $wire.setPaymentType('partial');
                    }
                    const partialRadio = document.querySelector('input[name="payment-type"][value="partial"]');
                    if (partialRadio) {
                        partialRadio.checked = true;
                    }
                    this.focusPaidAmount();
                    this.setDebug(event, 'F6', 'Partial Payment', 'Partial payment selected and paid amount focused.', 'ok');
                    return;
                }

                // 6. F7 or Ctrl+Alt+L: Credit Payment
                if (key === 'F7' || (ctrl && alt && (key.toLowerCase() === 'l' || code === 'KeyL' || key === '7'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    const customerSelect = document.getElementById('pos-customer-select');
                    if (!customerSelect || !customerSelect.value) {
                        this.focusCustomerSearch();
                        this.setDebug(event, 'F7', 'Credit Payment', 'Credit payment requires a registered customer. Customer select focused.', 'fail');
                        return;
                    }

                    if ($wire && typeof $wire.setPaymentType === 'function') {
                        $wire.setPaymentType('credit');
                    }
                    const creditRadio = document.querySelector('input[name="payment-type"][value="credit"]');
                    if (creditRadio) {
                        creditRadio.checked = true;
                    }
                    this.focusCheckoutButton();
                    this.setDebug(event, 'F7', 'Credit Payment', 'Credit payment selected. Proceed button focused.', 'ok');
                    return;
                }

                // 7. F8 or Ctrl+Alt+R: Print Receipt
                if (key === 'F8' || (ctrl && alt && (key.toLowerCase() === 'r' || code === 'KeyR'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    const invoiceModal = document.querySelector('.pos-invoice-modal');
                    if (invoiceModal) {
                        const printLink = invoiceModal.querySelector('a[href*="/sales/"], #pos-print-receipt-btn');
                        if (printLink) {
                            printLink.click();
                            this.setDebug(event, 'F8', 'Print Receipt', 'Receipt printing window triggered.', 'ok');
                            return;
                        }
                    }
                    this.setDebug(event, 'F8', 'Print Receipt', 'No completed sale invoice modal open to print.', 'fail');
                    return;
                }

                // 8. Ctrl+Q or Ctrl+Alt+Q: Edit Quantity
                if ((ctrl && (key.toLowerCase() === 'q' || code === 'KeyQ')) || (ctrl && alt && (key.toLowerCase() === 'q' || code === 'KeyQ'))) {
                    event.preventDefault();
                    event.stopPropagation();

                    let activeRow = document.querySelector('tr[data-cart-row]:focus') || (document.activeElement ? document.activeElement.closest('tr[data-cart-row]') : null);
                    if (!activeRow && this.activeCartIndex >= 0 && this.cartRows[this.activeCartIndex]) {
                        activeRow = this.cartRows[this.activeCartIndex];
                    }
                    if (!activeRow && this.cartRows.length > 0) {
                        activeRow = this.cartRows[0];
                    }

                    if (activeRow) {
                        const cartKey = activeRow.getAttribute('data-cart-key');
                        const currentQty = activeRow.getAttribute('data-qty') || '1';
                        const itemName = activeRow.getAttribute('data-name') || 'cart item';
                        const input = window.prompt(`Enter new quantity for ${itemName}:`, currentQty);
                        if (input !== null && input.trim() !== '') {
                            const newQty = parseFloat(input);
                            if (!isNaN(newQty) && newQty > 0) {
                                if ($wire && typeof $wire.updateQuantity === 'function') {
                                    $wire.updateQuantity(cartKey, newQty);
                                }
                                this.setDebug(event, 'Ctrl+Q', 'Edit Quantity', `Quantity updated to ${newQty} for ${itemName}.`, 'ok');
                                return;
                            }
                        }
                        this.setDebug(event, 'Ctrl+Q', 'Edit Quantity', 'Quantity edit cancelled or unchanged.', 'fail');
                        return;
                    }

                    this.setDebug(event, 'Ctrl+Q', 'Edit Quantity', 'No cart item selected for quantity edit.', 'fail');
                    return;
                }

                // 9. Delete: Remove Selected Cart Item
                if (key === 'Delete') {
                    // Do not remove cart item if user is typing in a text field
                    if (isGeneralTextInput || isSearchInput || isPaidAmountInput) {
                        return;
                    }

                    let activeRow = document.querySelector('tr[data-cart-row]:focus') || (document.activeElement ? document.activeElement.closest('tr[data-cart-row]') : null);
                    if (!activeRow && this.activeCartIndex >= 0 && this.cartRows[this.activeCartIndex]) {
                        activeRow = this.cartRows[this.activeCartIndex];
                    }

                    if (activeRow) {
                        event.preventDefault();
                        event.stopPropagation();
                        const cartKey = activeRow.getAttribute('data-cart-key');
                        const itemName = activeRow.getAttribute('data-name') || 'Item';
                        if ($wire && typeof $wire.removeItem === 'function') {
                            $wire.removeItem(cartKey);
                        } else {
                            const removeBtn = activeRow.querySelector('button[wire\\:click*="removeItem"]');
                            if (removeBtn) removeBtn.click();
                        }
                        this.activeCartIndex = Math.max(0, this.activeCartIndex - 1);
                        this.setDebug(event, 'Delete', 'Remove Item', `Removed ${itemName} from cart.`, 'ok');
                        return;
                    }

                    this.setDebug(event, 'Delete', 'Remove Item', 'No cart item selected to remove.', 'fail');
                    return;
                }

                // 10. Escape: Close Active Modal
                if (key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();

                    // Invoice modal
                    const invoiceModal = document.querySelector('.pos-invoice-modal');
                    if (invoiceModal) {
                        if ($wire && typeof $wire.closeInvoiceModal === 'function') {
                            $wire.closeInvoiceModal();
                        } else {
                            const closeBtn = invoiceModal.querySelector('.close-btn, #pos-new-sale-btn');
                            if (closeBtn) closeBtn.click();
                        }
                        this.focusProductSearch();
                        this.setDebug(event, 'Esc', 'Close Modal', 'Invoice modal closed. Focus restored to search.', 'ok');
                        return;
                    }

                    // Customer modal
                    if (this.showModal) {
                        this.showModal = false;
                        if ($wire && typeof $wire.closeCustomerModal === 'function') {
                            $wire.closeCustomerModal();
                        }
                        this.focusCustomerSearch();
                        this.setDebug(event, 'Esc', 'Close Modal', 'Add Customer modal closed.', 'ok');
                        return;
                    }

                    // If text in search input, clear it
                    const searchInput = document.getElementById(this.productSearchId);
                    if (searchInput && searchInput.value) {
                        if ($wire) {
                            $wire.set('search', '');
                        } else {
                            searchInput.value = '';
                        }
                        this.focusProductSearch();
                        this.setDebug(event, 'Esc', 'Clear Search', 'Search query cleared.', 'ok');
                        return;
                    }

                    this.setDebug(event, 'Esc', 'Escape', 'No active modal or query to close.', 'ok');
                    return;
                }

                // 11. Enter: Context-Sensitive Select / Confirm
                if (key === 'Enter') {
                    // Invoice Modal
                    const invoiceModal = document.querySelector('.pos-invoice-modal');
                    if (invoiceModal) {
                        event.preventDefault();
                        event.stopPropagation();
                        const printLink = invoiceModal.querySelector('#pos-print-receipt-btn, a[href*="/sales/"]');
                        if (printLink) {
                            printLink.click();
                            this.setDebug(event, 'Enter', 'Print Invoice', 'Receipt print triggered from modal.', 'ok');
                        }
                        return;
                    }

                    // Customer Modal
                    if (this.showModal) {
                        event.preventDefault();
                        event.stopPropagation();
                        if ($wire && typeof $wire.saveCustomer === 'function') {
                            $wire.saveCustomer();
                            this.setDebug(event, 'Enter', 'Save Customer', 'Customer save submitted.', 'ok');
                        }
                        return;
                    }

                    // Paid Amount Input
                    if (isPaidAmountInput) {
                        event.preventDefault();
                        event.stopPropagation();
                        if ($wire && typeof $wire.checkout === 'function') {
                            this.setDebug(event, 'Enter', 'Confirm Checkout', 'Checkout submitted from paid amount field.', 'ok');
                            $wire.checkout();
                        }
                        return;
                    }

                    // Checkout Button
                    if (target && target.id === 'pos-checkout-button') {
                        event.preventDefault();
                        event.stopPropagation();
                        if ($wire && typeof $wire.checkout === 'function') {
                            this.setDebug(event, 'Enter', 'Confirm Checkout', 'Checkout triggered from button.', 'ok');
                            $wire.checkout();
                        }
                        return;
                    }

                    // Product Card focused
                    const focusedCard = document.querySelector('[data-product-card]:focus') || (target ? target.closest('[data-product-card]') : null);
                    if (focusedCard) {
                        event.preventDefault();
                        event.stopPropagation();
                        focusedCard.click();
                        this.focusProductSearch();
                        this.setDebug(event, 'Enter', 'Add Medicine', 'Highlighted medicine added to cart.', 'ok');
                        return;
                    }

                    // Product Search Input
                    if (isSearchInput) {
                        event.preventDefault();
                        event.stopPropagation();
                        const val = (target.value || '').trim();

                        // If user used arrow keys to highlight a card
                        if (this.selectedProductIndex >= 0 && this.productButtons[this.selectedProductIndex]) {
                            this.productButtons[this.selectedProductIndex].click();
                            this.focusProductSearch();
                            this.setDebug(event, 'Enter', 'Add Medicine', 'Selected search result added to cart.', 'ok');
                            return;
                        }

                        // If results exist, add the first in-stock product
                        if (this.productButtons.length > 0) {
                            this.productButtons[0].click();
                            this.focusProductSearch();
                            this.setDebug(event, 'Enter', 'Add Medicine', 'First search result added to cart.', 'ok');
                            return;
                        }

                        // If no visible results but text is present, attempt barcode scan
                        if (val && $wire && typeof $wire.scanBarcode === 'function') {
                            $wire.scanBarcode(val);
                            this.setDebug(event, 'Enter', 'Scan Barcode', `Barcode '${val}' scanned.`, 'ok');
                            return;
                        }

                        this.setDebug(event, 'Enter', 'Search Enter', 'Enter pressed in search with no matches.', 'ok');
                        return;
                    }

                    // Cart row focused
                    const focusedRow = document.querySelector('tr[data-cart-row]:focus') || (target ? target.closest('tr[data-cart-row]') : null);
                    if (focusedRow) {
                        event.preventDefault();
                        event.stopPropagation();
                        const cartKey = focusedRow.getAttribute('data-cart-key');
                        const currentQty = focusedRow.getAttribute('data-qty') || '1';
                        const itemName = focusedRow.getAttribute('data-name') || 'item';
                        const input = window.prompt(`Enter new quantity for ${itemName}:`, currentQty);
                        if (input !== null && input.trim() !== '') {
                            const newQty = parseFloat(input);
                            if (!isNaN(newQty) && newQty > 0 && $wire) {
                                $wire.updateQuantity(cartKey, newQty);
                                this.setDebug(event, 'Enter', 'Edit Quantity', `Updated quantity to ${newQty}.`, 'ok');
                            }
                        }
                        return;
                    }
                }

                // 12. Arrow Keys: Navigation
                if (key === 'ArrowDown') {
                    if (isGeneralTextInput) return; // allow normal text navigation

                    if (isSearchInput || this.selectedProductIndex >= 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.focusSearchResult(this.selectedProductIndex + 1);
                        this.setDebug(event, '↓', 'Navigate', `Moved to product result ${this.selectedProductIndex + 1}.`, 'ok');
                        return;
                    }

                    if (document.activeElement === document.getElementById('pos-customer-select')) {
                        return;
                    }

                    // Cart navigation
                    if (this.cartRows.length > 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.focusCartRow(this.activeCartIndex + 1);
                        this.setDebug(event, '↓', 'Navigate Cart', `Focused cart row ${this.activeCartIndex + 1}.`, 'ok');
                        return;
                    }
                }

                if (key === 'ArrowUp') {
                    if (isGeneralTextInput) return;

                    if (this.selectedProductIndex > 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.focusSearchResult(this.selectedProductIndex - 1);
                        this.setDebug(event, '↑', 'Navigate', `Moved to product result ${this.selectedProductIndex + 1}.`, 'ok');
                        return;
                    } else if (this.selectedProductIndex === 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.focusProductSearch();
                        this.setDebug(event, '↑', 'Navigate', 'Returned focus to search input.', 'ok');
                        return;
                    }

                    if (document.activeElement === document.getElementById('pos-customer-select')) {
                        return;
                    }

                    if (this.cartRows.length > 0 && this.activeCartIndex > 0) {
                        event.preventDefault();
                        event.stopPropagation();
                        this.focusCartRow(this.activeCartIndex - 1);
                        this.setDebug(event, '↑', 'Navigate Cart', `Focused cart row ${this.activeCartIndex + 1}.`, 'ok');
                        return;
                    }
                }
            },

            init() {
                // Ensure single document listener
                if (window.__posKeydownHandler) {
                    document.removeEventListener('keydown', window.__posKeydownHandler, true);
                    window.__posKeydownHandler = null;
                }

                const handler = (e) => this.handleKeyDown(e);
                window.__posKeydownHandler = handler;
                document.addEventListener('keydown', handler, true);

                // Initial focus on medicine search
                setTimeout(() => {
                    this.focusProductSearch(false);
                }, 100);

                // Wire event listeners from Livewire component
                if (typeof $wire !== 'undefined') {
                    $wire.on('focus-product-search', () => {
                        setTimeout(() => this.focusProductSearch(true), 50);
                    });
                    $wire.on('focus-paid-amount', () => {
                        setTimeout(() => this.focusPaidAmount(), 50);
                    });
                    $wire.on('stockUpdated', () => {
                        this.selectedProductIndex = -1;
                    });
                }

                this.setDebug({ key: '', code: '' }, 'INIT', 'System Ready', 'POS Keyboard Controller activated.', 'ok');
            },

            destroy() {
                if (window.__posKeydownHandler) {
                    document.removeEventListener('keydown', window.__posKeydownHandler, true);
                    window.__posKeydownHandler = null;
                }
            }
        };
    }

    // Expose globally so Alpine evaluates posCounterData() reliably
    window.posCounterData = createPosCounterData;

    // Register with Alpine
    if (window.Alpine) {
        window.Alpine.data('posCounterData', createPosCounterData);
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('posCounterData', createPosCounterData);
        });
    }
})();
</script>
@endscript