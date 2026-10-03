<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-5 border-b border-gray-100 bg-gray-50/60 flex items-center justify-between">
        <div>
            <h2 class="font-bold text-gray-800 text-base flex items-center space-x-2">
                <i class="fa-solid fa-print text-blue-600"></i>
                <span>Print & Token Settings</span>
            </h2>
            <p class="text-xs text-gray-500 mt-0.5">Control OPD token generation and thermal receipt printing</p>
        </div>
        <button type="button" onclick="window.open('{{ route('patient-tokens.print', ['token' => 'preview']) }}', '_blank', 'width=400,height=600')" class="inline-flex items-center space-x-2 px-3 py-1.5 bg-gray-800 hover:bg-gray-900 text-white text-xs font-bold rounded-lg transition">
            <i class="fa-solid fa-eye"></i>
            <span>Preview Receipt</span>
        </button>
    </div>

    <form action="{{ route('settings.print-token.update') }}" method="POST" class="p-5 sm:p-6 space-y-8">
        @csrf
        @method('PUT')

        <!-- TOKEN SETTINGS -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-2">
                <i class="fa-solid fa-ticket text-gray-400 mr-2"></i>Token Settings
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="token_prefix" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Token Prefix
                    </label>
                    <input type="text" id="token_prefix" name="token_prefix" value="{{ old('token_prefix', $profile->token_prefix) }}" placeholder="e.g. T" class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs" />
                </div>
                <div>
                    <label for="token_start_number" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Starting Token Number
                    </label>
                    <input type="number" id="token_start_number" name="token_start_number" value="{{ old('token_start_number', $profile->token_start_number) }}" min="1" required class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs" />
                </div>
                <div>
                    <label for="default_token_status" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Default Token Status
                    </label>
                    <select id="default_token_status" name="default_token_status" required class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="Waiting" {{ old('default_token_status', $profile->default_token_status) === 'Waiting' ? 'selected' : '' }}>Waiting</option>
                        <option value="Called" {{ old('default_token_status', $profile->default_token_status) === 'Called' ? 'selected' : '' }}>Called</option>
                        <option value="Completed" {{ old('default_token_status', $profile->default_token_status) === 'Completed' ? 'selected' : '' }}>Completed</option>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition">
                    <div class="relative">
                        <input type="checkbox" name="daily_token_reset" value="1" class="sr-only peer" {{ old('daily_token_reset', $profile->daily_token_reset) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Daily Token Reset</span>
                        <span class="block text-[11px] text-gray-500">Reset number to start daily</span>
                    </div>
                </label>

                <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition">
                    <div class="relative">
                        <input type="checkbox" name="auto_print_token" value="1" class="sr-only peer" {{ old('auto_print_token', $profile->auto_print_token) ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    </div>
                    <div>
                        <span class="block text-sm font-bold text-gray-800">Auto Print Token</span>
                        <span class="block text-[11px] text-gray-500">Print receipt upon generation</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- THERMAL PRINTER SETTINGS -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-2">
                <i class="fa-solid fa-print text-gray-400 mr-2"></i>Thermal Printer Settings
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="thermal_paper_size" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Paper Size
                    </label>
                    <select id="thermal_paper_size" name="thermal_paper_size" required class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="58" {{ old('thermal_paper_size', $profile->thermal_paper_size) == 58 ? 'selected' : '' }}>58mm</option>
                        <option value="80" {{ old('thermal_paper_size', $profile->thermal_paper_size) == 80 ? 'selected' : '' }}>80mm</option>
                    </select>
                </div>
                <div>
                    <label for="print_copies" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                        Print Copies
                    </label>
                    <select id="print_copies" name="print_copies" required class="w-full text-sm font-semibold rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs">
                        <option value="1" {{ old('print_copies', $profile->print_copies) == 1 ? 'selected' : '' }}>1 Copy</option>
                        <option value="2" {{ old('print_copies', $profile->print_copies) == 2 ? 'selected' : '' }}>2 Copies</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- RECEIPT DISPLAY SETTINGS -->
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wider border-b border-gray-200 pb-2">
                <i class="fa-solid fa-list-check text-gray-400 mr-2"></i>Receipt Display Settings
            </h3>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach([
                    'show_logo' => 'Show Organization Logo',
                    'show_organization_name' => 'Show Organization Name',
                    'show_patient_id' => 'Show Patient ID',
                    'show_doctor_name' => 'Show Doctor Name',
                    'show_date' => 'Show Date',
                    'show_time' => 'Show Time',
                    'show_consultation_fee' => 'Show Consultation Fee',
                    'show_token_status' => 'Show Token Status'
                ] as $field => $label)
                <label class="flex items-center space-x-3 cursor-pointer p-3 rounded-lg border border-gray-200 bg-gray-50 hover:bg-gray-100 transition">
                    <input type="checkbox" name="{{ $field }}" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" {{ old($field, $profile->$field) ? 'checked' : '' }}>
                    <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
                </label>
                @endforeach
            </div>

            <!-- Footer Text (Already managed in Profile but user requested to edit here as well if needed, it uses the same footer_text field) -->
            <div class="pt-2">
                <label for="print_footer_text" class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Receipt Footer Note
                </label>
                <textarea id="print_footer_text" name="footer_text" rows="2" class="w-full text-sm rounded-lg border border-gray-300 px-3.5 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 shadow-2xs">{{ old('footer_text', $profile->footer_text) }}</textarea>
            </div>
        </div>

        <!-- FORM ACTION BUTTONS -->
        <div class="pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <div class="text-xs text-gray-400">
                Last updated: {{ $profile->updated_at ? $profile->updated_at->format('d M Y, h:i A') : 'Never' }}
            </div>

            <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center space-x-2 px-6 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-bold text-sm rounded-xl shadow-md shadow-blue-500/20 hover:shadow-lg transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Save Settings</span>
            </button>
        </div>
    </form>
</div>
