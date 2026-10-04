<div class="min-h-screen bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- HEADER --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/30">
                        <i class="fa-solid fa-pen-to-square text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Bulk Edit Medicines
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Edit multiple medicines quickly in a grid format
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="/medicines"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl shadow-xs transition">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Medicines</span>
                </a>
            </div>
        </div>

        {{-- FLASH & ERROR MESSAGES --}}
        @if (session()->has('message'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @if ($errors->has('general'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                <div>{{ $errors->first('general') }}</div>
            </div>
        @endif

        {{-- MAIN CARD --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2 w-full md:w-auto">
                    <div class="relative w-full md:w-64">
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search medicines..." class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-sm">
                        <i class="fa-solid fa-search absolute left-3 top-2.5 text-slate-400"></i>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button wire:click="saveAll" type="button" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-xs transition">
                        <i class="fa-solid fa-save text-xs"></i>
                        <span>Save All Changes</span>
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1200px]">
                    <thead>
                        <tr class="bg-slate-100/80 text-[11px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                            <th class="py-3 px-3 w-12 text-center">ID</th>
                            <th class="py-3 px-3 min-w-[180px]">Medicine Name <span class="text-red-500">*</span></th>
                            <th class="py-3 px-3 min-w-[150px]">Generic Name</th>
                            <th class="py-3 px-3 min-w-[130px]">Brand</th>
                            <th class="py-3 px-3 min-w-[140px]">Manufacturer</th>
                            <th class="py-3 px-3 min-w-[110px]">Dosage Unit</th>
                            <th class="py-3 px-3 min-w-[120px]">Sale Price <span class="text-red-500">*</span></th>
                            <th class="py-3 px-3 min-w-[120px]">Purchase Price</th>
                            <th class="py-3 px-3 min-w-[90px]">Alert Qty</th>
                            <th class="py-3 px-3 min-w-[130px]">Barcode</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 align-top">
                        @forelse($rows as $id => $row)
                            <tr class="hover:bg-slate-50 transition-colors {{ $errors->has("rows.{$id}.*") ? 'bg-red-50/50' : '' }}">
                                <td class="py-2.5 px-3 text-center text-xs text-slate-500 font-medium">
                                    {{ $id }}
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.name"
                                           class="w-full border {{ $errors->has("rows.{$id}.name") ? 'border-red-300 ring-1 ring-red-300' : 'border-slate-200' }} rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="Name">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.generic_name"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="Generic">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.brand"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="Brand">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.manufacturer"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="Manufacturer">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.dosage_unit"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="e.g. Tablet">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" step="0.01" wire:model="rows.{{ $id }}.unit_price"
                                           class="w-full border {{ $errors->has("rows.{$id}.unit_price") ? 'border-red-300' : 'border-slate-200' }} rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition text-right"
                                           placeholder="0.00">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" step="0.01" wire:model="rows.{{ $id }}.purchase_price"
                                           class="w-full border {{ $errors->has("rows.{$id}.purchase_price") ? 'border-red-300' : 'border-slate-200' }} rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition text-right"
                                           placeholder="0.00">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="number" wire:model="rows.{{ $id }}.alert_quantity"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition text-right"
                                           placeholder="10">
                                </td>
                                <td class="py-2.5 px-3">
                                    <input type="text" wire:model="rows.{{ $id }}.barcode"
                                           class="w-full border border-slate-200 rounded text-sm px-2.5 py-1.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition"
                                           placeholder="Barcode">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-500 text-sm">
                                    No medicines found matching your search.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex flex-col md:flex-row items-center justify-between gap-4">
                <div class="w-full md:w-auto overflow-x-auto">
                    {{ $medicines->links() }}
                </div>
                <button wire:click="saveAll" type="button" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl shadow-xs transition whitespace-nowrap">
                    <i class="fa-solid fa-save text-xs"></i>
                    <span>Save All Changes</span>
                </button>
            </div>
        </div>
    </div>
</div>
