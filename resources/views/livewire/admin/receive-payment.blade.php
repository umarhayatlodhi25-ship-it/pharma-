<div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 max-w-4xl mx-auto my-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Receive Customer Payment</h2>
            <p class="text-sm text-gray-500">Settle outstanding balances and previous dues.</p>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 bg-emerald-50 border-l-4 border-emerald-500 p-4 text-emerald-700 text-sm font-semibold rounded-xl flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 text-red-700 text-sm font-semibold rounded-xl flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg"></i>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Left Side: Form -->
        <div class="space-y-5">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Select Customer *</label>
                <select wire:model.live="customer_id" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-600">
                    <option value="">-- Choose Customer --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->phone }})</option>
                    @endforeach
                </select>
                @error('customer_id') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
            </div>

            @if($customer_id)
                <div class="bg-rose-50 border border-rose-100 rounded-xl p-4 flex justify-between items-center">
                    <span class="text-rose-700 font-semibold text-sm">Total Outstanding Balance:</span>
                    <span class="text-rose-700 font-bold text-lg">Rs. {{ number_format($outstanding_balance, 2) }}</span>
                </div>

                @if($outstanding_balance > 0)
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Payment Amount (Rs) *</label>
                        <input type="number" wire:model="payment_amount" step="0.01" max="{{ $outstanding_balance }}" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-blue-700 focus:outline-none focus:border-blue-600" placeholder="0.00">
                        @error('payment_amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Payment Method *</label>
                        <select wire:model="payment_method" class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-medium focus:outline-none focus:border-blue-600">
                            <option value="cash">Cash</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="easypaisa">Easypaisa/JazzCash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        @error('payment_method') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-600 mb-1.5">Remarks (Optional)</label>
                        <textarea wire:model="remarks" rows="2" class="w-full p-2.5 bg-white border border-gray-200 rounded-xl text-sm focus:outline-none focus:border-blue-600" placeholder="E.g., Cleared dues for January"></textarea>
                    </div>

                    <button wire:click="savePayment" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl text-sm font-bold shadow-md transition flex items-center justify-center space-x-2">
                        <i class="fa-solid fa-money-bill-wave"></i>
                        <span>Receive Payment</span>
                    </button>
                @else
                    <div class="text-emerald-600 text-sm font-bold bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-center">
                        This customer has no outstanding dues.
                    </div>
                @endif
            @endif
        </div>

        <!-- Right Side: Unpaid Invoices -->
        <div>
            @if($customer_id)
                <h3 class="text-sm font-bold text-gray-800 mb-3 border-b pb-2">Pending Invoices for Allocation</h3>
                @if(count($unpaid_invoices) > 0)
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                        @foreach($unpaid_invoices as $invoice)
                            <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex justify-between items-center">
                                <div>
                                    <p class="font-bold text-xs text-gray-900">{{ $invoice->invoice_number }}</p>
                                    <p class="text-[10px] text-gray-500">{{ $invoice->created_at->format('d M Y') }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] text-gray-500">Bill: Rs. {{ number_format($invoice->total_amount, 2) }}</p>
                                    <p class="text-xs font-bold text-rose-600">Due: Rs. {{ number_format($invoice->total_amount - $invoice->paid_amount, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-gray-400 mt-3 text-center italic">Payments will be automatically allocated to the oldest invoices first.</p>
                @else
                    <div class="text-gray-400 text-xs text-center py-8">
                        No pending invoices found.
                    </div>
                @endif
            @else
                <div class="h-full flex flex-col items-center justify-center text-gray-400 bg-gray-50 rounded-2xl border border-dashed border-gray-200 py-12">
                    <i class="fa-solid fa-file-invoice-dollar text-4xl mb-3 text-gray-300"></i>
                    <p class="text-sm font-medium">Select a customer to view pending invoices</p>
                </div>
            @endif
        </div>

    </div>
</div>
