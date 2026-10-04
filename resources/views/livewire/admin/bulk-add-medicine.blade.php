<div class="min-h-screen bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-600/30">
                        <i class="fa-solid fa-layer-group text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                            Bulk Add Medicines
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                            Add multiple medicines quickly to your inventory
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button type="button" wire:click="downloadTemplate" class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-emerald-200 text-emerald-700 hover:bg-emerald-50 text-sm font-semibold rounded-xl shadow-xs transition">
                    <i class="fa-solid fa-download text-xs"></i>
                    <span>Template</span>
                </button>
                
                <div class="relative" x-data="{
                    isImporting: false,
                    async handleFile(e) {
                        const file = e.target.files[0];
                        if (!file) return;
                        
                        this.isImporting = true;
                        
                        const reader = new FileReader();
                        reader.onload = async (evt) => {
                            try {
                                const data = new Uint8Array(evt.target.result);
                                const workbook = XLSX.read(data, {type: 'array'});
                                const firstSheetName = workbook.SheetNames[0];
                                const worksheet = workbook.Sheets[firstSheetName];
                                const json = XLSX.utils.sheet_to_json(worksheet, {header: 1});
                                
                                // Remove header row if it exists
                                if (json.length > 0) json.shift();
                                
                                const chunkSize = 150;
                                for (let i = 0; i < json.length; i += chunkSize) {
                                    const chunk = json.slice(i, i + chunkSize);
                                    await @this.call('importJson', chunk);
                                }
                            } catch (err) {
                                console.error(err);
                                alert('An error occurred during import.');
                            } finally {
                                this.isImporting = false;
                                document.getElementById('importFile').value = '';
                            }
                        };
                        reader.readAsArrayBuffer(file);
                    }
                }">
                    <input type="file" id="importFile" class="hidden" accept=".csv,.txt,.xlsx,.xls" x-on:change="handleFile">
                    <label for="importFile" class="inline-flex cursor-pointer items-center gap-2 px-4 py-2.5 bg-blue-50 border border-blue-200 text-blue-700 hover:bg-blue-100 text-sm font-semibold rounded-xl shadow-xs transition" x-bind:class="{'opacity-50 cursor-wait': isImporting}" wire:loading.class="opacity-50 cursor-wait" wire:target="importJson">
                        <i x-bind:class="isImporting ? 'fa-solid fa-circle-notch fa-spin' : 'fa-solid fa-file-import'" class="text-xs"></i>
                        <span x-text="isImporting ? 'Importing...' : 'Import CSV/Excel'">Import CSV/Excel</span>
                    </label>
                </div>

                <a href="/medicines"
                   class="inline-flex items-center gap-2 px-4 py-2.5 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold rounded-xl shadow-xs transition">
                    <i class="fa-solid fa-arrow-left text-xs"></i>
                    <span>Back to Medicines</span>
                </a>
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

        @if ($errors->has('general'))
            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm font-medium text-red-800 flex items-center gap-3 shadow-xs">
                <i class="fa-solid fa-circle-exclamation text-red-600 text-lg"></i>
                <div>{{ $errors->first('general') }}</div>
            </div>
        @endif

        @if ($errors->any() && !$errors->has('general'))
            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 shadow-xs">
                <div class="font-bold flex items-center gap-2 mb-1">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                    <span>Validation errors found in table:</span>
                </div>
                <ul class="list-disc pl-5 space-y-1 text-xs text-amber-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ========================================================= --}}
        {{-- MAIN BULK ADD CARD --}}
        {{-- ========================================================= --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-8">
            
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h2 class="text-base font-bold text-slate-800">
                        Medicine List ({{ count($rows) }} {{ count($rows) === 1 ? 'row' : 'rows' }})
                    </h2>
                </div>
                <button type="button"
                        wire:click="addRow"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold rounded-xl transition shadow-2xs">
                    <i class="fa-solid fa-plus text-xs"></i>
                    <span>+ Add Row</span>
                </button>
            </div>

            <form wire:submit.prevent="saveAll">
                {{-- Table container with horizontal scroll --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1200px]">
                        <thead>
                            <tr class="bg-slate-100/80 text-[11px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <th class="py-3 px-3 w-12 text-center">#</th>
                                <th class="py-3 px-3 min-w-[180px]">Medicine Name <span class="text-red-500">*</span></th>
                                <th class="py-3 px-3 min-w-[160px]">Category <span class="text-red-500">*</span></th>
                                <th class="py-3 px-3 min-w-[150px]">Generic Name</th>
                                <th class="py-3 px-3 min-w-[130px]">Brand</th>
                                <th class="py-3 px-3 min-w-[140px]">Manufacturer</th>
                                <th class="py-3 px-3 min-w-[110px]">Dosage Unit</th>
                                <th class="py-3 px-3 min-w-[120px]">Sale Price <span class="text-red-500">*</span></th>
                                <th class="py-3 px-3 min-w-[120px]">Purchase Price</th>
                                <th class="py-3 px-3 min-w-[90px]">Alert Qty</th>
                                <th class="py-3 px-3 min-w-[130px]">Barcode</th>
                                <th class="py-3 px-3 w-16 text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs">
                            @foreach ($rows as $index => $row)
                                <tr class="hover:bg-slate-50/70 transition align-top">
                                    {{-- Row Number --}}
                                    <td class="py-3 px-3 text-center text-slate-400 font-semibold pt-4">
                                        {{ $index + 1 }}
                                    </td>

                                    {{-- Medicine Name --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-product-name-suggestions"
                                               wire:model="rows.{{ $index }}.name"
                                               placeholder="e.g. Panadol 500mg"
                                               autocomplete="off"
                                               class="w-full h-9 px-2.5 border rounded-lg text-xs transition focus:outline-none focus:ring-2 {{ $errors->has("rows.{$index}.name") ? 'border-red-400 focus:ring-red-200 bg-red-50/30' : 'border-slate-200 focus:ring-emerald-200 focus:border-emerald-400' }}">
                                        @error("rows.{$index}.name")
                                            <span class="text-[10px] text-red-500 font-medium block mt-1 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- Category --}}
                                    <td class="py-3 px-2">
                                        <select wire:model="rows.{{ $index }}.category_id"
                                                class="w-full h-9 px-2 border rounded-lg text-xs bg-white transition focus:outline-none focus:ring-2 {{ $errors->has("rows.{$index}.category_id") ? 'border-red-400 focus:ring-red-200 bg-red-50/30' : 'border-slate-200 focus:ring-emerald-200 focus:border-emerald-400' }}">
                                            <option value="">Select Category</option>
                                            @foreach ($categories as $cat)
                                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                        @error("rows.{$index}.category_id")
                                            <span class="text-[10px] text-red-500 font-medium block mt-1 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- Generic Name --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-generic-suggestions"
                                               wire:model="rows.{{ $index }}.generic_name"
                                               placeholder="e.g. Paracetamol"
                                               autocomplete="off"
                                               class="w-full h-9 px-2.5 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                    </td>

                                    {{-- Brand --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-brand-suggestions"
                                               wire:model="rows.{{ $index }}.brand"
                                               placeholder="e.g. GSK"
                                               autocomplete="off"
                                               class="w-full h-9 px-2.5 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                    </td>

                                    {{-- Manufacturer --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-manufacturer-suggestions"
                                               wire:model="rows.{{ $index }}.manufacturer"
                                               placeholder="e.g. GSK Pharma"
                                               autocomplete="off"
                                               class="w-full h-9 px-2.5 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                    </td>

                                    {{-- Dosage Unit --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               list="bulk-dosage-unit-suggestions"
                                               wire:model="rows.{{ $index }}.dosage_unit"
                                               placeholder="Tablet"
                                               autocomplete="off"
                                               class="w-full h-9 px-2.5 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                    </td>

                                    {{-- Sale Price --}}
                                    <td class="py-3 px-2">
                                        <input type="number"
                                               step="0.01"
                                               wire:model="rows.{{ $index }}.unit_price"
                                               placeholder="0.00"
                                               class="w-full h-9 px-2.5 border rounded-lg text-xs transition focus:outline-none focus:ring-2 {{ $errors->has("rows.{$index}.unit_price") ? 'border-red-400 focus:ring-red-200 bg-red-50/30' : 'border-slate-200 focus:ring-emerald-200 focus:border-emerald-400' }}">
                                        @error("rows.{$index}.unit_price")
                                            <span class="text-[10px] text-red-500 font-medium block mt-1 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- Purchase Price --}}
                                    <td class="py-3 px-2">
                                        <input type="number"
                                               step="0.01"
                                               wire:model="rows.{{ $index }}.purchase_price"
                                               placeholder="0.00"
                                               class="w-full h-9 px-2.5 border rounded-lg text-xs transition focus:outline-none focus:ring-2 {{ $errors->has("rows.{$index}.purchase_price") ? 'border-red-400 focus:ring-red-200 bg-red-50/30' : 'border-slate-200 focus:ring-emerald-200 focus:border-emerald-400' }}">
                                        @error("rows.{$index}.purchase_price")
                                            <span class="text-[10px] text-red-500 font-medium block mt-1 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- Alert Quantity --}}
                                    <td class="py-3 px-2">
                                        <input type="number"
                                               wire:model="rows.{{ $index }}.alert_quantity"
                                               placeholder="10"
                                               class="w-full h-9 px-2.5 border border-slate-200 rounded-lg text-xs transition focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-400">
                                    </td>

                                    {{-- Barcode --}}
                                    <td class="py-3 px-2">
                                        <input type="text"
                                               wire:model="rows.{{ $index }}.barcode"
                                               placeholder="Optional"
                                               class="w-full h-9 px-2.5 border rounded-lg text-xs transition focus:outline-none focus:ring-2 {{ $errors->has("rows.{$index}.barcode") ? 'border-red-400 focus:ring-red-200 bg-red-50/30' : 'border-slate-200 focus:ring-emerald-200 focus:border-emerald-400' }}">
                                        @error("rows.{$index}.barcode")
                                            <span class="text-[10px] text-red-500 font-medium block mt-1 leading-tight">{{ $message }}</span>
                                        @enderror
                                    </td>

                                    {{-- Action --}}
                                    <td class="py-3 px-2 text-center pt-3.5">
                                        <button type="button"
                                                wire:click="removeRow({{ $index }})"
                                                class="w-8 h-8 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition inline-flex items-center justify-center"
                                                title="Remove Row">
                                            <i class="fa-solid fa-trash-can text-sm"></i>
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
                                <span>Saving Medicines...</span>
                            </span>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>


    {{-- ============================================================= --}}
    {{-- BULK ADD SUGGESTION DATALISTS --}}
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

    <script src="https://cdn.sheetjs.com/xlsx-latest/package/dist/xlsx.full.min.js"></script>
</div>
