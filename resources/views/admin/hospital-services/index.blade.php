@extends('layouts.admin')

@section('content')
<div class="space-y-6" x-data="{ 
    modalOpen: false, 
    isEdit: false, 
    formAction: '{{ route('hospital-services.store') }}',
    service: {
        id: '',
        name: '',
        code: '',
        service_type: 'consultation',
        default_fee: 500,
        doctor_share_percentage: 70,
        hospital_share_percentage: 30,
        is_active: true,
        description: ''
    },
    openCreate() {
        this.isEdit = false;
        this.formAction = '{{ route('hospital-services.store') }}';
        this.service = {
            id: '',
            name: '',
            code: '',
            service_type: 'consultation',
            default_fee: 500,
            doctor_share_percentage: 70,
            hospital_share_percentage: 30,
            is_active: true,
            description: ''
        };
        this.modalOpen = true;
    },
    openEdit(item) {
        this.isEdit = true;
        this.formAction = '/hospital-services/' + item.id;
        this.service = {
            id: item.id,
            name: item.name,
            code: item.code || '',
            service_type: item.service_type,
            default_fee: item.default_fee,
            doctor_share_percentage: parseFloat(item.doctor_share_percentage),
            hospital_share_percentage: parseFloat(item.hospital_share_percentage),
            is_active: Boolean(item.is_active),
            description: item.description || ''
        };
        this.modalOpen = true;
    },
    updateSplitFromDoctor() {
        let doc = parseFloat(this.service.doctor_share_percentage) || 0;
        doc = Math.max(0, Math.min(100, doc));
        this.service.doctor_share_percentage = doc;
        this.service.hospital_share_percentage = Math.round((100 - doc) * 100) / 100;
    },
    updateSplitFromHospital() {
        let hosp = parseFloat(this.service.hospital_share_percentage) || 0;
        hosp = Math.max(0, Math.min(100, hosp));
        this.service.hospital_share_percentage = hosp;
        this.service.doctor_share_percentage = Math.round((100 - hosp) * 100) / 100;
    }
}">

    <!-- HEADER & TOP ACTION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-blue-600 font-semibold mb-1">
                <a href="/dashboard" class="hover:underline">Dashboard</a>
                <span>/</span>
                <span>Hospital</span>
                <span>/</span>
                <span>Services & Revenue Split</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-2">
                <i class="fa-solid fa-hand-holding-medical text-blue-600"></i>
                <span>Hospital Services</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Configure clinical services, pricing fees and automatic doctor/hospital revenue shares</p>
        </div>

        <div>
            <button @click="openCreate()" type="button" class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-lg shadow-sm transition">
                <i class="fa-solid fa-plus mr-1.5 text-xs"></i>
                <span>+ Add Service</span>
            </button>
        </div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span class="font-semibold">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    @if(isset($errors) && $errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-xs space-y-1">
            <div class="font-bold flex items-center space-x-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                <span>Validation Errors:</span>
            </div>
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- SUMMARY KPI CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Total Services</p>
                <h3 class="text-2xl font-black text-gray-800 mt-1 font-mono">{{ $services->total() }}</h3>
                <span class="text-[11px] text-gray-500">Configured in system</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-stethoscope"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Active Services</p>
                <h3 class="text-2xl font-black text-emerald-600 mt-1 font-mono">{{ \App\Models\HospitalService::where('is_active', true)->count() }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium">Ready for billing & OPD</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Consultations</p>
                <h3 class="text-2xl font-black text-indigo-600 mt-1 font-mono">{{ \App\Models\HospitalService::where('service_type', 'consultation')->count() }}</h3>
                <span class="text-[11px] text-indigo-600 font-medium">Doctor visit types</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-doctor"></i>
            </div>
        </div>

        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Procedures & Labs</p>
                <h3 class="text-2xl font-black text-amber-600 mt-1 font-mono">{{ \App\Models\HospitalService::whereIn('service_type', ['procedure', 'diagnostic', 'nursing'])->count() }}</h3>
                <span class="text-[11px] text-amber-600 font-medium">Drips, injections, sugar</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-syringe"></i>
            </div>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <form method="GET" action="{{ route('hospital-services.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="relative sm:col-span-2">
                <input 
                    type="text" 
                    name="search" 
                    value="{{ $search }}" 
                    placeholder="Search by service name, code, description..." 
                    class="w-full pl-9 pr-4 py-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                >
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 pointer-events-none">
                    <i class="fa-solid fa-search"></i>
                </span>
            </div>

            <div>
                <select name="service_type" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:bg-white focus:border-blue-500">
                    <option value="">All Categories</option>
                    @foreach($serviceTypes as $val => $lbl)
                        <option value="{{ $val }}" {{ $type == $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <select name="status" onchange="this.form.submit()" class="w-full p-2 bg-gray-50 border border-gray-200 rounded-lg text-xs font-medium focus:outline-hidden focus:bg-white focus:border-blue-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                @if($search || $type || $status)
                    <a href="{{ route('hospital-services.index') }}" class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs transition" title="Clear Filters">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- SERVICES TABLE -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-gray-200 text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4">Service Details</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4 text-right">Default Fee</th>
                        <th class="py-3 px-4 text-center">Doctor Share</th>
                        <th class="py-3 px-4 text-center">Hospital Share</th>
                        <th class="py-3 px-4">Split Example (on Default Fee)</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 font-medium text-gray-700">
                    @forelse($services as $srv)
                    @php
                        $split = $srv->calculateSplit($srv->default_fee);
                    @endphp
                    <tr class="hover:bg-slate-50/75 transition">
                        <td class="py-3 px-4">
                            <div class="font-bold text-gray-900 text-sm">{{ $srv->name }}</div>
                            <div class="text-[11px] text-gray-500 mt-0.5 flex items-center space-x-2">
                                @if($srv->code)
                                    <span class="font-mono bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">{{ $srv->code }}</span>
                                @endif
                                <span>{{ $srv->description ?: 'No description provided' }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @if($srv->service_type === 'consultation')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <i class="fa-solid fa-user-doctor mr-1"></i> Consultation
                                </span>
                            @elseif($srv->service_type === 'procedure')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="fa-solid fa-bandage mr-1"></i> Procedure
                                </span>
                            @elseif($srv->service_type === 'diagnostic')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                    <i class="fa-solid fa-heart-pulse mr-1"></i> Diagnostic
                                </span>
                            @elseif($srv->service_type === 'nursing')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                    <i class="fa-solid fa-droplet mr-1"></i> Nursing
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                    Other
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-gray-900 text-sm">
                            PKR {{ number_format($srv->default_fee, 2) }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded font-mono font-bold text-xs {{ $srv->doctor_share_percentage > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-gray-100 text-gray-400' }}">
                                {{ number_format($srv->doctor_share_percentage, 0) }}%
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="inline-block px-2.5 py-0.5 rounded font-mono font-bold text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ number_format($srv->hospital_share_percentage, 0) }}%
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="text-[11px] font-mono leading-tight space-y-0.5">
                                <div class="text-blue-600 font-semibold">Doctor: PKR {{ number_format($split['doctor_share'], 2) }}</div>
                                <div class="text-emerald-600 font-semibold">Hospital: PKR {{ number_format($split['hospital_share'], 2) }}</div>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-center">
                            <form action="{{ route('hospital-services.status', $srv->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold transition {{ $srv->is_active ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-red-100 text-red-800 hover:bg-red-200' }}" title="Click to toggle status">
                                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $srv->is_active ? 'bg-emerald-600' : 'bg-red-600' }}"></span>
                                    {{ $srv->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </form>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button 
                                type="button" 
                                @click="openEdit({{ json_encode($srv) }})" 
                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" 
                                title="Edit Service"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-400">
                            <i class="fa-solid fa-hand-holding-medical text-3xl mb-2 text-gray-300"></i>
                            <p>No hospital services found matching your criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($services->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $services->links() }}
            </div>
        @endif
    </div>

    <!-- CREATE / EDIT MODAL -->
    <div 
        x-show="modalOpen" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div 
            @click.away="modalOpen = false" 
            class="bg-white rounded-2xl shadow-xl max-w-lg w-full overflow-hidden border border-gray-100 transform transition-all"
        >
            <form :action="formAction" method="POST">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between">
                    <h3 class="font-bold text-sm tracking-wide flex items-center space-x-2">
                        <i class="fa-solid fa-hand-holding-medical text-blue-400"></i>
                        <span x-text="isEdit ? 'Edit Hospital Service' : 'Add New Hospital Service'"></span>
                    </h3>
                    <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <!-- Service Name -->
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Service Name <span class="text-red-500">*</span></label>
                        <input 
                            type="text" 
                            name="name" 
                            x-model="service.name" 
                            required 
                            placeholder="e.g. Doctor Consultation, Sugar Check, Drip..." 
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                        >
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <!-- Service Code -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Service Code</label>
                            <input 
                                type="text" 
                                name="code" 
                                x-model="service.code" 
                                placeholder="e.g. SRV-CONSULT" 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono focus:outline-hidden focus:bg-white focus:border-blue-500"
                            >
                        </div>

                        <!-- Service Category -->
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Service Category <span class="text-red-500">*</span></label>
                            <select 
                                name="service_type" 
                                x-model="service.service_type" 
                                required 
                                class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                            >
                                <option value="consultation">Doctor Consultation</option>
                                <option value="diagnostic">Diagnostic / Sugar / BP</option>
                                <option value="procedure">Procedure / Dressing / Nebulizer</option>
                                <option value="nursing">Nursing / Drip / IV</option>
                                <option value="other">Other Hospital Service</option>
                            </select>
                        </div>
                    </div>

                    <!-- Default Fee -->
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Default Fee (PKR) <span class="text-red-500">*</span></label>
                        <input 
                            type="number" 
                            step="0.01" 
                            min="0" 
                            name="default_fee" 
                            x-model.number="service.default_fee" 
                            required 
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-mono font-bold text-gray-900 focus:outline-hidden focus:bg-white focus:border-blue-500"
                        >
                    </div>

                    <!-- Revenue Share Balancer Card -->
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800 text-[11px] uppercase tracking-wider flex items-center">
                                <i class="fa-solid fa-scale-balanced mr-1.5 text-blue-600"></i> Revenue Split Percentages
                            </span>
                            <span class="text-[10px] text-slate-500">Must total 100%</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block font-semibold text-blue-700 mb-1">Doctor Share %</label>
                                <div class="relative">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="100" 
                                        name="doctor_share_percentage" 
                                        x-model.number="service.doctor_share_percentage" 
                                        @input="updateSplitFromDoctor()" 
                                        required 
                                        class="w-full p-2 bg-white border border-blue-200 rounded-lg font-mono font-bold text-blue-800 focus:outline-hidden focus:border-blue-500"
                                    >
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-gray-400 pointer-events-none">%</span>
                                </div>
                            </div>

                            <div>
                                <label class="block font-semibold text-emerald-700 mb-1">Hospital Share %</label>
                                <div class="relative">
                                    <input 
                                        type="number" 
                                        step="0.01" 
                                        min="0" 
                                        max="100" 
                                        name="hospital_share_percentage" 
                                        x-model.number="service.hospital_share_percentage" 
                                        @input="updateSplitFromHospital()" 
                                        required 
                                        class="w-full p-2 bg-white border border-emerald-200 rounded-lg font-mono font-bold text-emerald-800 focus:outline-hidden focus:border-emerald-500"
                                    >
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-3 font-bold text-gray-400 pointer-events-none">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- Real-time split preview -->
                        <div class="p-2.5 bg-white border border-slate-200 rounded-lg font-mono text-[11px] flex justify-between items-center">
                            <div>
                                <span class="text-gray-400">Doctor Receives:</span>
                                <span class="font-bold text-blue-600 ml-1" x-text="'PKR ' + ((service.default_fee * (service.doctor_share_percentage / 100)) || 0).toFixed(2)"></span>
                            </div>
                            <div class="text-right">
                                <span class="text-gray-400">Hospital Receives:</span>
                                <span class="font-bold text-emerald-600 ml-1" x-text="'PKR ' + ((service.default_fee * (service.hospital_share_percentage / 100)) || 0).toFixed(2)"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Description / Notes</label>
                        <textarea 
                            name="description" 
                            x-model="service.description" 
                            rows="2" 
                            placeholder="Optional notes regarding this procedure or billing rules..." 
                            class="w-full p-2.5 bg-gray-50 border border-gray-200 rounded-lg font-medium focus:outline-hidden focus:bg-white focus:border-blue-500"
                        ></textarea>
                    </div>

                    <!-- Active checkbox -->
                    <div class="flex items-center space-x-2 pt-1">
                        <input 
                            type="checkbox" 
                            name="is_active" 
                            id="modal_is_active" 
                            value="1" 
                            x-model="service.is_active" 
                            class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500"
                        >
                        <label for="modal_is_active" class="font-bold text-gray-700">Active (available for OPD Tokens & Cashier Billing)</label>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end space-x-3">
                    <button 
                        type="button" 
                        @click="modalOpen = false" 
                        class="px-4 py-2 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-100 transition"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit" 
                        class="px-5 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-xs font-bold shadow-sm transition"
                    >
                        <span x-text="isEdit ? 'Update Service' : 'Save Service'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
