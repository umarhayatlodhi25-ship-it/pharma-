<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 h-[calc(100vh-6rem)]" x-data="{ showModal: false }">
    
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
                <div class="inline-flex rounded-xl bg-gray-100 p-1 text-xs font-bold border border-gray-200">
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('all')"
                        class="px-2.5 py-1 rounded-lg transition {{ $productTypeFilter === 'all' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('medicine')"
                        class="px-2.5 py-1 rounded-lg transition {{ $productTypeFilter === 'medicine' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        Medicines
                    </button>
                    <button
                        type="button"
                        wire:click="setProductTypeFilter('general')"
                        class="px-2.5 py-1 rounded-lg transition {{ $productTypeFilter === 'general' ? 'bg-blue-600 text-white shadow-xs' : 'text-gray-600 hover:text-gray-900' }}"
                    >
                        General Store
                    </button>
                </div>

                <div class="relative w-48 sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <i class="fa-solid fa-search text-xs"></i>
                    </span>
                    <input
                        type="text"
                        wire:model.live.debounce.250ms="search"
                        wire:keydown.enter="scanBarcode($event.target.value)"
                        placeholder="Search product or scan barcode..."
                        class="w-full pl-9 pr-3 py-1.5 bg-gray-50 border border-gray-200 rounded-xl text-xs focus:outline-none focus:border-blue-600 transition"
                    >
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto pr-2 grid grid-cols-2 md:grid-cols-3 gap-3 content-start">
            @forelse($medicines ?? [] as $medicine)
                <div 
                    wire:key="pos-product-{{ $medicine->id }}"
                    class="bg-gray-50 hover:bg-blue-50/80 border border-gray-200/80 hover:border-blue-500 p-3.5 rounded-xl transition flex flex-col justify-between space-y-2 group select-none w-full shadow-xs text-left"
                >
                    <div class="w-full">
                        <div class="flex justify-between items-start">
                            <h4 class="font-bold text-gray-800 text-sm group-hover:text-blue-600 transition line-clamp-1 cursor-pointer" wire:click="addToCart({{ $medicine->id }})" title="{{ $medicine->name }}">{{ $medicine->name }}</h4>
                            <button
                                type="button"
                                wire:click="addToCart({{ $medicine->id }})"
                                class="text-[10px] bg-blue-600 hover:bg-blue-700 text-white px-2.5 py-1 rounded-full font-bold shrink-0 ml-1 transition cursor-pointer"
                            >
                                <i class="fa-solid fa-plus text-[9px]"></i> Add
                            </button>
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

                        {{-- Available Packaging Units Buttons --}}
                        @if($medicine->packagings->count() > 0)
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($medicine->packagings->where('allow_sale', true) as $pkg)
                                    <button
                                        type="button"
                                        wire:click="addToCart({{ $medicine->id }}, {{ $pkg->id }})"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-white border border-blue-200 text-blue-800 hover:bg-blue-600 hover:text-white transition shadow-2xs cursor-pointer"
                                        title="Add 1 {{ $pkg->display_name ?: $pkg->unit?->name }} to cart"
                                    >
                                        {{ $pkg->unit?->name ?: $pkg->display_name }} (Rs.{{ number_format($pkg->sale_price ?: 0, 0) }})
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="w-full flex flex-col gap-1 pt-2 border-t border-gray-200/60 mt-auto">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-blue-600">PKR {{ number_format($medicine->selling_price ?? $medicine->unit_price ?? 0, 2) }}</span>
                            <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full font-semibold truncate max-w-[140px]" title="{{ $medicine->formatStockInUnits() }}">
                                {{ $medicine->formatStockInUnits() }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center text-gray-400 text-sm">
                    No products found matching your query.
                </div>
            @endforelse
        </div>
    </div>

    <!-- RIGHT SIDE: Current Cart -->
    <div class="lg:col-span-5 flex flex-col bg-white p-5 rounded-2xl shadow-sm border border-gray-100 justify-between h-full overflow-hidden">
        
        <div class="space-y-4">
            <div class="flex justify-between items-center border-b pb-3">
                <h2 class="text-lg font-bold text-gray-900">Current Cart</h2>
                <div class="flex items-center space-x-2">
                    <button wire:click="holdInvoice" class="bg-amber-50 text-amber-600 hover:bg-amber-600 hover:text-white border border-amber-200/80 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm cursor-pointer" title="Pause and hold current cart">
                        <i class="fa-solid fa-pause"></i>
                        <span>Hold Invoice</span>
                    </button>
                    <button wire:click="clearCart" class="bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-200/80 px-3 py-1.5 rounded-xl text-xs font-bold transition flex items-center space-x-1.5 shadow-sm cursor-pointer">
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
                        <select wire:model="customer_id" class="w-full pl-9 pr-8 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-600 appearance-none">
                            <option value="">Walk-in Customer</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                            @endforeach
                        </select>
                        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </span>
                    </div>
                    <button type="button" @click="showModal = true" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-xl text-xs font-bold transition flex items-center space-x-1 cursor-pointer">
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
                        <a href="{{ route('pos.receive-payment', $customer_id) }}" target="_blank" class="bg-rose-600 hover:bg-rose-700 text-white px-3 py-1.5 rounded-lg text-[10px] uppercase tracking-wider font-bold transition shadow-sm">
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
                        <tr wire:key="cart-item-{{ $id }}">
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
                                        class="text-[11px] bg-blue-50 border border-blue-200 rounded px-1.5 py-0.5 text-blue-800 font-bold cursor-pointer focus:outline-none"
                                        wire:change="updateCartPackaging('{{ $id }}', $event.target.value)"
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
                                        <button wire:click="decrementQty('{{ $id }}')" class="px-2.5 py-1 text-gray-600 hover:bg-gray-200 transition font-bold cursor-pointer">-</button>
                                        <span class="px-3 py-1 text-xs font-bold text-gray-800">{{ $item['qty'] ?? 1 }}</span>
                                        <button wire:click="incrementQty('{{ $id }}')" class="px-2.5 py-1 text-gray-600 hover:bg-gray-200 transition font-bold cursor-pointer">+</button>
                                    </div>
                                    @if(isset($item['total_base_available']))
                                    <span class="text-[9px] text-emerald-600 font-bold" title="Max available stock across all unexpired batches">
                                        Max: {{ floor($item['total_base_available'] / ($item['conversion_to_base'] ?: 1)) }} {{ $item['unit'] ?? '' }}s
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
                                <button wire:click="removeItem('{{ $id }}')" class="text-red-500 hover:text-red-700 p-1 transition cursor-pointer">
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
                    <input type="number" wire:model.live="discount" placeholder="0.00" class="w-full px-3 py-1 bg-white border border-gray-200 rounded-xl text-right text-sm font-medium focus:outline-none focus:border-blue-600">
                </div>
            </div>

            <!-- Payment Type Selection -->
            <div class="flex items-center space-x-3 mb-2">
                <label class="flex items-center space-x-1 cursor-pointer">
                    <input type="radio" wire:model.live="payment_type" value="full" class="text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-bold text-gray-700">Full</span>
                </label>
                <label class="flex items-center space-x-1 cursor-pointer" @if(!$customer_id) title="Requires Customer" @endif>
                    <input type="radio" wire:model.live="payment_type" value="partial" class="text-blue-600 focus:ring-blue-500" @if(!$customer_id) disabled @endif>
                    <span class="text-xs font-bold {{ !$customer_id ? 'text-gray-400' : 'text-gray-700' }}">Partial</span>
                </label>
                <label class="flex items-center space-x-1 cursor-pointer" @if(!$customer_id) title="Requires Customer" @endif>
                    <input type="radio" wire:model.live="payment_type" value="credit" class="text-blue-600 focus:ring-blue-500" @if(!$customer_id) disabled @endif>
                    <span class="text-xs font-bold {{ !$customer_id ? 'text-gray-400' : 'text-gray-700' }}">Credit</span>
                </label>
            </div>

            <!-- Customer Paid Amount Field -->
            <div class="flex justify-between items-center text-sm text-gray-600">
                <span>Paid Amount</span>
                <div class="w-32">
                    <input type="number" wire:model.live="paid_amount" placeholder="0.00" class="w-full px-3 py-1 bg-white border border-gray-200 rounded-xl text-right text-sm font-medium focus:outline-none focus:border-blue-600" {{ $payment_type === 'credit' ? 'disabled' : '' }}>
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

            <button wire:click="checkout" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-3 rounded-xl shadow-md transition text-sm flex items-center justify-center space-x-2 mt-1">
                <i class="fa-solid fa-cash-register"></i>
                <span>Proceed to Checkout</span>
            </button>
        </div>

    </div>

    <!-- Add Customer Modal -->
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto bg-black bg-opacity-50 flex items-center justify-center" style="display: none;">
        <div class="bg-white p-6 rounded-2xl shadow-xl w-full max-w-md space-y-4" @click.away="showModal = false">
            <div class="flex justify-between items-center border-b pb-3">
                <h3 class="text-lg font-bold text-gray-900">Add New Customer</h3>
                <button @click="showModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Customer Name *</label>
                    <input type="text" wire:model="new_customer_name" placeholder="Enter customer name" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-600">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1">Phone Number *</label>
                    <input type="text" wire:model="new_customer_phone" placeholder="0300-1234567" class="w-full p-2.5 border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-600">
                </div>
            </div>

            <div class="flex justify-end space-x-2 pt-3 border-t">
                <button type="button" @click="showModal = false" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-200">Cancel</button>
                <button type="button" wire:click="saveCustomer" @click="showModal = false" class="bg-blue-600 text-white px-5 py-2 rounded-xl text-xs font-bold hover:bg-blue-700 shadow">Save Customer</button>
            </div>
        </div>
    </div>

    <!-- Invoice Print Modal / Popup -->
    @if($showInvoiceModal && $completedSale)
    <div class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
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
                <button wire:click="closeInvoiceModal" class="text-emerald-100 hover:text-white transition p-1">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Receipt Content Preview -->
            <div class="p-5 font-mono text-xs text-gray-800 space-y-3.5 max-h-[65vh] overflow-y-auto bg-gray-50/70">
                <!-- Pharmacy Info -->
                <div class="text-center space-y-0.5">
                    <h2 class="text-base font-black tracking-wider text-gray-900 uppercase">PHARMACY MANAGEMENT</h2>
                    <p class="text-[11px] text-gray-500 font-sans">Official Sales Invoice / Receipt</p>
                    <p class="text-[10px] text-gray-400 font-sans">{{ $completedSale->created_at->format('d-M-Y h:i A') }}</p>
                </div>

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

                <div class="text-center text-[10px] text-gray-400 font-sans">
                    Thank you for your purchase! Get well soon.
                </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="p-4 bg-white border-t flex items-center justify-between gap-3">
                <button wire:click="closeInvoiceModal" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold py-2.5 rounded-xl text-xs transition">
                    <i class="fa-solid fa-plus text-[10px] mr-1"></i> New Sale
                </button>
                <a href="{{ url('/sales/' . $completedSale->id) }}" target="_blank" onclick="window.open(this.href, 'InvoicePrint', 'width=450,height=650'); return false;" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-xl text-xs transition text-center shadow flex items-center justify-center space-x-2">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Invoice</span>
                </a>
            </div>
        </div>
    </div>
    @endif
</div>