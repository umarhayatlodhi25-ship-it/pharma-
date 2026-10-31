<div class="min-h-screen bg-slate-50">
    <div class="max-w-[98%] mx-auto px-2 sm:px-4 lg:px-6 py-6">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/30">
                        <i class="fa-solid fa-layer-group text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Bulk Add Medicines
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Flexible packaging hierarchy, stock unit conversion, and batch inventory import
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                {{-- Download Template Dropdown / Buttons --}}
                <div class="relative inline-flex rounded-xl shadow-xs" x-data="{ open: false }">
                    <button type="button"
                            wire:click="downloadTemplate"
                            class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-emerald-300 text-emerald-700 hover:bg-emerald-50 text-xs sm:text-sm font-semibold rounded-l-xl transition">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Download Template (.xlsx)</span>
                    </button>
                    <button type="button"
                            wire:click="downloadCsvTemplate"
                            class="inline-flex items-center gap-1.5 px-3 py-2.5 bg-white border-y border-r border-emerald-300 text-emerald-700 hover:bg-emerald-50 text-xs sm:text-sm font-semibold rounded-r-xl transition"
                            title="Download CSV version">
                        <i class="fa-solid fa-file-csv text-emerald-600"></i>
                        <span>CSV</span>
                    </button>
                </div>

                {{-- Import CSV / Excel File --}}
                <div class="relative">
                    <input type="file" wire:model="importFile" id="importFile" class="hidden" accept=".csv,.txt,.xlsx,.xls,.ods">
                    <label for="importFile"
                           class="inline-flex cursor-pointer items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-xl shadow-md shadow-blue-600/25 transition"
                           wire:loading.class="opacity-50 cursor-wait pointer-events-none"
                           wire:target="importFile, importData">
                        <span wire:loading.remove wire:target="importFile, importData" class="flex items-center gap-2">
                            <i class="fa-solid fa-cloud-arrow-up text-sm"></i>
                            <span>Upload Excel / CSV</span>
                        </span>
                        <span wire:loading wire:target="importFile, importData" class="flex items-center gap-2">
                            <i class="fa-solid fa-circle-notch fa-spin text-sm"></i>
                            <span>Parsing File...</span>
                        </span>
                    </label>
                </div>

                <a href="/medicines"
                   class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs sm:text-sm font-semibold rounded-xl shadow-xs transition">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Medicines</span>
                </a>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- SYSTEM CAPABILITIES INFO BANNER --}}
        {{-- ========================================================= --}}
        <div class="mb-5 rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-slate-50 p-4 shadow-2xs">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-3 text-xs">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-600/10 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                        <i class="fa-solid fa-boxes-packing text-sm"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 text-sm block">Flexible Multi-Packaging & Stock Unit System</span>
                        <p class="text-slate-600 mt-0.5 leading-relaxed">
                            Each medicine configures its own packaging hierarchy independently. Import <strong>Packs</strong> (e.g. Panadol 50 Packs = 5,000 Tablets), <strong>Strips</strong> (e.g. Augmentin 20 Strips = 200 Tablets), <strong>Bottles</strong> (100 Syrups), or <strong>Vials</strong> (500 Injections) together in one file.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0 self-end md:self-center">
                    <span class="px-2.5 py-1 bg-white border border-blue-200 rounded-lg font-medium text-slate-700 text-[11px] shadow-2xs">
                        <i class="fa-solid fa-check-double text-emerald-600 mr-1"></i> Auto Base Unit Conversion
                    </span>
                    <span class="px-2.5 py-1 bg-white border border-blue-200 rounded-lg font-medium text-slate-700 text-[11px] shadow-2xs">
                        <i class="fa-solid fa-tag text-blue-600 mr-1"></i> Packaging-Aware Pricing
                    </span>
                </div>
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- FLASH & ERROR MESSAGES --}}
        {{-- ========================================================= --}}
        @if (session()->has('message'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                <div>{{ session('message') }}</div>
            </div>
        @endif

        @error('importFile')
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                <div>{{ $message }}</div>
            </div>
        @enderror

        @if ($errors->has('general'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                <div>{{ $errors->first('general') }}</div>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- IMPORT SUMMARY CARD --}}
        {{-- ========================================================= --}}
        @if ($importSummary)
            <div class="mb-5 rounded-2xl border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50/50 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center text-xs shadow-xs">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">File Import Preview & Validation Summary</h3>
                    </div>
                    <button type="button" wire:click="$set('importSummary', null)" class="text-xs font-semibold text-slate-400 hover:text-slate-600 transition inline-flex items-center gap-1">
                        <i class="fa-solid fa-xmark"></i> Dismiss
                    </button>
                </div>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="bg-white/90 rounded-xl p-3 border border-slate-200/60 shadow-2xs">
                        <span class="text-slate-500 block text-[11px] font-medium">Total Parsed</span>
                        <span class="text-lg font-bold text-slate-800">{{ $importSummary['parsed'] ?? 0 }}</span>
                    </div>
                    <div class="bg-white/90 rounded-xl p-3 border border-emerald-200/60 shadow-2xs">
                        <span class="text-emerald-600 block text-[11px] font-medium">Ready to Save</span>
                        <span class="text-lg font-bold text-emerald-700">{{ $importSummary['valid'] ?? 0 }}</span>
                    </div>
                    <div class="bg-white/90 rounded-xl p-3 border border-amber-200/60 shadow-2xs">
                        <span class="text-amber-600 block text-[11px] font-medium">Requires Attention</span>
                        <span class="text-lg font-bold text-amber-700">{{ $importSummary['invalid'] ?? 0 }}</span>
                    </div>
                    <div class="bg-white/90 rounded-xl p-3 border border-purple-200/60 shadow-2xs">
                        <span class="text-purple-600 block text-[11px] font-medium">Duplicates / Skipped</span>
                        <span class="text-lg font-bold text-purple-700">{{ $importSummary['duplicates'] ?? 0 }}</span>
                    </div>
                </div>

                @if (!empty($importSummary['errors']))
                    <div class="mt-4 pt-3 border-t border-slate-200/60">
                        <span class="font-bold text-xs text-amber-900 block mb-1.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-triangle-exclamation text-amber-600 text-xs"></i>
                            <span>Issues to review in preview table before saving ({{ count($importSummary['errors']) }}):</span>
                        </span>
                        <ul class="list-disc pl-5 space-y-1 text-xs text-amber-800 max-h-36 overflow-y-auto">
                            @foreach ($importSummary['errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MAIN BULK PREVIEW & EDIT CARD --}}
        {{-- ========================================================= --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
            
            {{-- Toolbar --}}
            <div class="px-4 sm:px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50/70">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $totalFailed > 0 ? 'bg-amber-100 text-amber-800 border border-amber-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200' }}">
                        <span class="w-2 h-2 rounded-full {{ $totalFailed > 0 ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                        {{ count($rows) }} {{ count($rows) === 1 ? 'Medicine' : 'Medicines' }} in Table
                    </span>

                    @if ($isImportedPreview)
                        <span class="text-xs bg-blue-100 text-blue-800 border border-blue-200 px-2.5 py-1 rounded-full font-medium flex items-center gap-1.5">
                            <i class="fa-solid fa-eye text-blue-600"></i> Import Preview Mode (Unsaved)
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 bg-white border border-slate-200 px-3 py-1.5 rounded-xl hover:bg-slate-50 transition">
                        <input type="checkbox" wire:model.live="updateExisting" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span>Update Existing Medicines</span>
                    </label>

                    <button type="button"
                            wire:click="addRow"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-xl transition shadow-2xs">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>+ Add Row</span>
                    </button>

                    <button type="button"
                            wire:click="clearAllRows"
                            onclick="return confirm('Clear all rows in the table?')"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white hover:bg-red-50 text-slate-600 hover:text-red-700 border border-slate-200 hover:border-red-200 text-xs font-medium rounded-xl transition shadow-2xs">
                        <i class="fa-solid fa-trash-can text-xs"></i>
                        <span>Clear All</span>
                    </button>
                </div>
            </div>

            {{-- FORM & PREVIEW TABLE --}}
            <form wire:submit.prevent="saveAll">
                <div class="overflow-x-auto max-h-[70vh]">
                    <table class="w-full text-left border-collapse min-w-[1700px]">
                        <thead class="sticky top-0 z-10 bg-slate-100 shadow-2xs">
                            <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <th class="py-3 px-2 w-14 text-center"># / Status</th>
                                <th class="py-3 px-2 min-w-[170px]">Medicine Name <span class="text-red-500">*</span></th>
                                <th class="py-3 px-2 min-w-[150px]">Category <span class="text-red-500">*</span></th>
                                <th class="py-3 px-2 min-w-[110px]">Base Unit <span class="text-red-500">*</span></th>
                                <th class="py-3 px-2 min-w-[240px]">Packaging Configuration</th>
                                <th class="py-3 px-2 min-w-[90px]">Import Qty</th>
                                <th class="py-3 px-2 min-w-[110px]">Stock Unit</th>
                                <th class="py-3 px-2 min-w-[150px]">Converted Base Stock</th>
                                <th class="py-3 px-2 min-w-[130px]">Purchase Price</th>
                                <th class="py-3 px-2 min-w-[130px]">Sale Price <span class="text-red-500">*</span></th>
                                <th class="py-3 px-2 min-w-[120px]">Batch Number</th>
                                <th class="py-3 px-2 min-w-[120px]">Expiry Date</th>
                                <th class="py-3 px-2 min-w-[130px]">Supplier</th>
                                <th class="py-3 px-2 min-w-[100px]">Barcode</th>
                                <th class="py-3 px-2 w-14 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach ($rows as $index => $row)
                                @php
                                    $hasError = !empty($row['validation_errors']) || $errors->has("rows.{$index}.*");
                                    $rowNum = $index + 1;
                                @endphp
                                <tr class="hover:bg-slate-50/80 transition align-top {{ $hasError ? 'bg-amber-50/30' : '' }}">
                                    
                                    {{-- 1. Row Number & Status Badge --}}
                                    <td class="py-3 px-2 text-center pt-3.5">
                                        <div class="flex flex-col items-center gap-1">
                                            <span class="text-slate-400 font-bold text-xs">{{ $rowNum }}</span>
                                            @if ($hasError)
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-100 text-red-600 text-[10px] font-bold" title="{{ implode(' | ', $row['validation_errors'] ?? []) }}">
                                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                                </span>
                                            @else
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 text-[10px] font-bold" title="Ready to import">
                                                    <i class="fa-solid fa-check"></i>
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 2. Medicine Name --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-product-name-suggestions"
                                               wire:model.live.debounce.300ms="rows.{{ $index }}.name"
                                               placeholder="e.g. Panadol Extra"
                                               autocomplete="off"
                                               class="w-full h-8 px-2 border rounded-lg text-xs transition focus:outline-none focus:ring-1 {{ $errors->has("rows.{$index}.name") ? 'border-red-400 bg-red-50/30 focus:ring-red-300' : 'border-slate-200 focus:ring-emerald-300 focus:border-emerald-500' }}">
                                        @error("rows.{$index}.name")
                                            <span class="text-[10px] text-red-500 font-medium block mt-0.5 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- 3. Category --}}
                                    <td class="py-3 px-2">
                                        <select wire:model.live="rows.{{ $index }}.category_id"
                                                class="w-full h-8 px-1.5 border rounded-lg text-xs bg-white transition focus:outline-none focus:ring-1 {{ $errors->has("rows.{$index}.category_id") ? 'border-red-400 bg-red-50/30 focus:ring-red-300' : 'border-slate-200 focus:ring-emerald-300 focus:border-emerald-500' }}">
                                            <option value="">Select Category</option>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @if (!empty($row['category_name_raw']) && empty($row['category_id']))
                                            <span class="text-[10px] text-amber-600 font-medium block mt-0.5 leading-tight">File: "{{ $row['category_name_raw'] }}"</span>
                                        @endif
                                        @error("rows.{$index}.category_id")
                                            <span class="text-[10px] text-red-500 font-medium block mt-0.5 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- 4. Base Unit --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-dosage-unit-suggestions"
                                               wire:model.live.debounce.400ms="rows.{{ $index }}.base_unit"
                                               placeholder="Tablet"
                                               class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-1 focus:ring-emerald-300 focus:border-emerald-500">
                                    </td>

                                    {{-- 5. Packaging Configuration --}}
                                    <td class="py-3 px-2">
                                        <div class="space-y-1.5">
                                            {{-- Strip configuration --}}
                                            <div class="flex items-center gap-1.5">
                                                <label class="inline-flex items-center gap-1 cursor-pointer shrink-0 text-[11px] text-slate-700">
                                                    <input type="checkbox"
                                                           wire:model.live="rows.{{ $index }}.strip_enabled"
                                                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 text-xs">
                                                    <span>Strip:</span>
                                                </label>
                                                @if (!empty($row['strip_enabled']))
                                                    <input type="number"
                                                           min="1"
                                                           step="1"
                                                           wire:model.live.debounce.300ms="rows.{{ $index }}.units_per_strip"
                                                           placeholder="Units"
                                                           class="w-14 h-7 px-1.5 border border-slate-200 rounded text-center text-xs focus:ring-1 focus:ring-emerald-300"
                                                           title="Units per strip">
                                                    <span class="text-[10px] text-slate-500">{{ $row['base_unit'] ?: 'Units' }}</span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 italic">None</span>
                                                @endif
                                            </div>

                                            {{-- Pack configuration --}}
                                            <div class="flex items-center gap-1.5">
                                                <label class="inline-flex items-center gap-1 cursor-pointer shrink-0 text-[11px] text-slate-700">
                                                    <input type="checkbox"
                                                           wire:model.live="rows.{{ $index }}.pack_enabled"
                                                           class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 text-xs">
                                                    <span>Pack:</span>
                                                </label>
                                                @if (!empty($row['pack_enabled']))
                                                    <input type="number"
                                                           min="1"
                                                           step="1"
                                                           wire:model.live.debounce.300ms="rows.{{ $index }}.strips_per_pack"
                                                           placeholder="Strips"
                                                           class="w-14 h-7 px-1.5 border border-slate-200 rounded text-center text-xs focus:ring-1 focus:ring-emerald-300"
                                                           title="Strips/Units per pack">
                                                    <span class="text-[10px] text-slate-500">{{ !empty($row['strip_enabled']) ? 'Strips' : ($row['base_unit'] ?: 'Units') }}</span>
                                                @else
                                                    <span class="text-[10px] text-slate-400 italic">None</span>
                                                @endif
                                            </div>

                                            {{-- Calculated Packaging Display Badge --}}
                                            <div class="text-[10px] text-blue-700 bg-blue-50/70 border border-blue-200/60 rounded px-1.5 py-0.5 leading-snug">
                                                <i class="fa-solid fa-link text-[9px] mr-1"></i>{{ $row['packaging_display'] ?? 'Base only' }}
                                            </div>
                                        </div>
                                    </td>

                                    {{-- 6. Import Quantity --}}
                                    <td class="py-3 px-2">
                                        <input type="number"
                                               min="0"
                                               step="any"
                                               wire:model.live.debounce.300ms="rows.{{ $index }}.stock_quantity"
                                               placeholder="0"
                                               class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs text-right font-semibold focus:ring-1 focus:ring-emerald-300">
                                    </td>

                                    {{-- 7. Selected Stock Unit --}}
                                    <td class="py-3 px-2">
                                        <select wire:model.live="rows.{{ $index }}.stock_entry_unit"
                                                class="w-full h-8 px-1.5 border border-slate-200 rounded-lg text-xs bg-white focus:ring-1 focus:ring-emerald-300 font-medium">
                                            @foreach ($row['allowed_units'] ?? [$row['base_unit']] as $u)
                                                <option value="{{ $u }}">{{ $u }}</option>
                                            @endforeach
                                        </select>
                                    </td>

                                    {{-- 8. Converted Base Stock (Live Preview) --}}
                                    <td class="py-3 px-2">
                                        <div class="h-8 flex items-center px-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold text-xs">
                                            <i class="fa-solid fa-boxes-stacked text-[11px] mr-1.5 text-emerald-600"></i>
                                            <span>{{ number_format($row['converted_base_stock'] ?? 0) }} {{ Str::plural($row['base_unit'] ?? 'Unit', (float)($row['converted_base_stock'] ?? 0)) }}</span>
                                        </div>
                                    </td>

                                    {{-- 9. Purchase Price & Unit --}}
                                    <td class="py-3 px-2">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1">
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       wire:model.live.debounce.300ms="rows.{{ $index }}.purchase_price"
                                                       placeholder="0.00"
                                                       class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs text-right">
                                                <select wire:model.live="rows.{{ $index }}.purchase_price_unit"
                                                        class="h-8 px-1 border border-slate-200 rounded-lg text-[11px] bg-slate-50 shrink-0">
                                                    @foreach ($row['allowed_units'] ?? [$row['base_unit']] as $u)
                                                        <option value="{{ $u }}">/ {{ $u }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if (!empty($row['base_unit_purchase_price']) && !empty($row['purchase_price_unit']) && $row['purchase_price_unit'] !== $row['base_unit'])
                                                <span class="text-[10px] text-slate-500 block text-right font-medium">
                                                    ≈ Rs. {{ number_format($row['base_unit_purchase_price'], 2) }} / {{ $row['base_unit'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- 10. Sale Price & Unit --}}
                                    <td class="py-3 px-2">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-1">
                                                <input type="number"
                                                       step="0.01"
                                                       min="0"
                                                       wire:model.live.debounce.300ms="rows.{{ $index }}.unit_price"
                                                       placeholder="0.00"
                                                       class="w-full h-8 px-2 border rounded-lg text-xs text-right font-semibold {{ $errors->has("rows.{$index}.unit_price") ? 'border-red-400 bg-red-50/30' : 'border-slate-200' }}">
                                                <select wire:model.live="rows.{{ $index }}.sale_price_unit"
                                                        class="h-8 px-1 border border-slate-200 rounded-lg text-[11px] bg-slate-50 shrink-0 font-medium">
                                                    @foreach ($row['allowed_units'] ?? [$row['base_unit']] as $u)
                                                        <option value="{{ $u }}">/ {{ $u }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            @if (!empty($row['base_unit_selling_price']) && !empty($row['sale_price_unit']) && $row['sale_price_unit'] !== $row['base_unit'])
                                                <span class="text-[10px] text-emerald-700 block text-right font-medium">
                                                    ≈ Rs. {{ number_format($row['base_unit_selling_price'], 2) }} / {{ $row['base_unit'] }}
                                                </span>
                                            @endif
                                            @error("rows.{$index}.unit_price")
                                                <span class="text-[10px] text-red-500 font-medium block leading-tight">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </td>

                                    {{-- 11. Batch Number --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               wire:model.live.debounce.300ms="rows.{{ $index }}.batch_number"
                                               placeholder="Auto-generated"
                                               class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs font-mono">
                                    </td>

                                    {{-- 12. Expiry Date --}}
                                    <td class="py-3 px-2">
                                        <input type="date"
                                               wire:model.live="rows.{{ $index }}.expiry_date"
                                               class="w-full h-8 px-1.5 border border-slate-200 rounded-lg text-xs">
                                        @error("rows.{$index}.expiry_date")
                                            <span class="text-[10px] text-red-500 font-medium block leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- 13. Supplier --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-supplier-suggestions"
                                               wire:model.live.debounce.300ms="rows.{{ $index }}.supplier"
                                               placeholder="Supplier"
                                               class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs">
                                    </td>

                                    {{-- 14. Barcode --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               wire:model.live.debounce.300ms="rows.{{ $index }}.barcode"
                                               placeholder="Optional"
                                               class="w-full h-8 px-2 border border-slate-200 rounded-lg text-xs font-mono">
                                    </td>

                                    {{-- 15. Action --}}
                                    <td class="py-3 px-2 text-center pt-3.5">
                                        <button type="button"
                                                wire:click="removeRow({{ $index }})"
                                                class="w-7 h-7 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition inline-flex items-center justify-center"
                                                title="Remove Row">
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Card Footer --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <button type="button"
                            wire:click="addRow"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold rounded-xl transition shadow-xs">
                        <i class="fa-solid fa-plus text-xs text-emerald-600"></i>
                        <span>+ Add Row</span>
                    </button>

                    <div class="flex items-center gap-3 w-full sm:w-auto">
                        <a href="/medicines"
                           class="w-1/2 sm:w-auto text-center px-4 py-2.5 text-slate-600 hover:text-slate-800 text-xs font-semibold rounded-xl transition">
                            Cancel
                        </a>

                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="w-1/2 sm:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-md shadow-emerald-600/30 transition disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="saveAll" class="flex items-center gap-2">
                                <i class="fa-solid fa-check text-xs"></i>
                                <span>Save All Medicines</span>
                            </span>
                            <span wire:loading wire:target="saveAll" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Importing Medicines...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>

    {{-- ============================================================= --}}
    {{-- SUGGESTION DATALISTS --}}
    {{-- ============================================================= --}}
    <datalist id="bulk-product-name-suggestions">
        @foreach($suggestedProductNames as $p)
            <option value="{{ $p }}">
        @endforeach
    </datalist>

    <datalist id="bulk-brand-suggestions">
        @foreach($suggestedBrands as $b)
            <option value="{{ $b }}">
        @endforeach
    </datalist>

    <datalist id="bulk-generic-suggestions">
        @foreach($suggestedGenerics as $g)
            <option value="{{ $g }}">
        @endforeach
    </datalist>

    <datalist id="bulk-manufacturer-suggestions">
        @foreach($suggestedManufacturers as $m)
            <option value="{{ $m }}">
        @endforeach
    </datalist>

    <datalist id="bulk-dosage-unit-suggestions">
        @foreach($suggestedDosageUnits as $u)
            <option value="{{ $u }}">
        @endforeach
    </datalist>

    <datalist id="bulk-supplier-suggestions">
        @foreach($existingSuppliers as $s)
            <option value="{{ $s }}">
        @endforeach
    </datalist>
</div>
