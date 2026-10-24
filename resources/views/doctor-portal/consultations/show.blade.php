@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <a href="{{ route('doctor.queue.index') }}" class="hover:underline">OPD Queue</a>
                <span>/</span>
                <span>Token #{{ $token->token_number }} Consultation</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-notes-medical text-teal-600"></i>
                <span>Clinical Consultation Workspace</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-slate-900 text-white">
                    #{{ $token->token_number }}
                </span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Patient: <span class="font-bold text-gray-800">{{ $token->patient->name }}</span> ({{ $token->patient->patient_number }}) • Date: {{ format_app_datetime($token->token_date, 'd M, Y') }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            @if($token->prescription)
                <a href="{{ route('doctor.prescriptions.show', $token->prescription->id) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-prescription"></i>
                    <span>View Prescription ({{ $token->prescription->prescription_number }})</span>
                </a>
            @else
                <a href="{{ route('doctor.prescriptions.create', ['token_id' => $token->id]) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <i class="fa-solid fa-prescription"></i>
                    <span>Write Prescription</span>
                </a>
            @endif

            <a href="{{ route('doctor.queue.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Queue List</span>
            </a>
        </div>
    </div>

    <!-- PATIENT SUMMARY BANNER -->
    <div class="bg-white rounded-2xl border border-teal-100 shadow-sm p-5 bg-gradient-to-r from-teal-50/40 via-white to-white">
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 text-xs">
            <div>
                <span class="text-gray-400 block font-medium">Patient Name</span>
                <span class="font-bold text-gray-900 text-sm block">{{ $token->patient->name }}</span>
                <span class="text-[11px] text-teal-700 font-mono">{{ $token->patient->patient_number }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Age & Gender</span>
                <span class="font-bold text-gray-800 text-sm">
                    {{ $token->patient->gender ?: '—' }}, {{ $token->patient->age ? $token->patient->age.' yrs' : '—' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Contact Phone</span>
                <span class="font-mono font-semibold text-gray-800 text-sm">{{ $token->patient->phone ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Father / Husband</span>
                <span class="font-semibold text-gray-800 text-sm">{{ $token->patient->father_husband_name ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Visit Status</span>
                @if($token->status === 'in_consultation')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                        In Consultation
                    </span>
                @elseif($token->status === 'completed')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                        Completed
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        {{ ucfirst($token->status) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- MAIN TWO COLUMN WORKSPACE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- CLINICAL EXAMINATION & NOTES FORM (2 COLS) -->
        <div class="lg:col-span-2 space-y-6">
            <form action="{{ route('doctor.consultations.update', $token->id) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <!-- PATIENT VITALS -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <i class="fa-solid fa-heart-pulse text-rose-500"></i>
                        <span>Patient Vital Signs</span>
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <label class="block font-semibold text-gray-600 mb-1">Blood Pressure</label>
                            <input type="text" name="blood_pressure" value="{{ old('blood_pressure', $token->blood_pressure) }}" placeholder="e.g. 120/80" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                            <span class="text-[10px] text-gray-400">mmHg</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 mb-1">Pulse Rate</label>
                            <input type="text" name="pulse_rate" value="{{ old('pulse_rate', $token->pulse_rate) }}" placeholder="e.g. 72" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                            <span class="text-[10px] text-gray-400">bpm</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 mb-1">Temperature</label>
                            <input type="text" name="temperature" value="{{ old('temperature', $token->temperature) }}" placeholder="e.g. 98.6" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                            <span class="text-[10px] text-gray-400">°F</span>
                        </div>
                        <div>
                            <label class="block font-semibold text-gray-600 mb-1">Weight</label>
                            <input type="text" name="weight" value="{{ old('weight', $token->weight) }}" placeholder="e.g. 65" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                            <span class="text-[10px] text-gray-400">kg</span>
                        </div>
                    </div>
                </div>

                <!-- CLINICAL NOTES & DIAGNOSIS -->
                <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                        <i class="fa-solid fa-clipboard-check text-teal-600"></i>
                        <span>Clinical Findings & Diagnosis</span>
                    </h3>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Chief Complaints & Symptoms</label>
                            <textarea name="symptoms" rows="3" placeholder="Describe symptoms reported by patient (e.g. persistent fever, cough for 3 days)..." class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 p-3 leading-relaxed">{{ old('symptoms', $token->symptoms) }}</textarea>
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Provisional / Confirmed Diagnosis</label>
                            <input type="text" name="diagnosis" value="{{ old('diagnosis', $token->diagnosis) }}" placeholder="e.g. Acute Bronchitis, Type 2 Diabetes, Hypertension..." class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-semibold">
                        </div>

                        <div>
                            <label class="block font-bold text-gray-700 mb-1">Doctor's Clinical Notes & Treatment Plan</label>
                            <textarea name="clinical_notes" rows="4" placeholder="Detailed clinical examination notes, test findings, dietary advice, or precautions..." class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 p-3 leading-relaxed">{{ old('clinical_notes', $token->clinical_notes) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Next Follow-Up Date</label>
                                <input type="date" name="follow_up_date" value="{{ old('follow_up_date', $token->follow_up_date ? $token->follow_up_date->format('Y-m-d') : '') }}" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700 mb-1">Follow-Up Instructions</label>
                                <input type="text" name="follow_up_instructions" value="{{ old('follow_up_instructions', $token->follow_up_instructions) }}" placeholder="e.g. Return after 5 days with CBC & Sugar report" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <button type="submit" name="action" value="save" class="inline-flex items-center px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            <i class="fa-solid fa-floppy-disk mr-2"></i> Save Consultation Details
                        </button>

                        <button type="submit" name="action" value="save_and_complete" class="inline-flex items-center px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-emerald-900/20">
                            <i class="fa-solid fa-circle-check mr-2"></i> Save & Complete Visit
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- SIDEBAR: PRESCRIPTION & HISTORICAL VISITS (1 COL) -->
        <div class="space-y-6">

            <!-- PRESCRIPTION CARD -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2">
                        <i class="fa-solid fa-prescription text-teal-600"></i>
                        <span>Prescription Slip</span>
                    </h3>
                    @if($token->prescription)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-teal-50 text-teal-700 font-mono">
                            {{ $token->prescription->prescription_number }}
                        </span>
                    @endif
                </div>

                @if($token->prescription)
                    <div class="space-y-3">
                        <p class="text-xs text-gray-600">
                            Prescription issued with <span class="font-bold text-gray-900">{{ $token->prescription->items->count() }} medicines</span>.
                        </p>
                        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs space-y-1.5 font-mono">
                            @foreach($token->prescription->items as $item)
                                <div class="flex items-center justify-between text-gray-800">
                                    <span class="font-semibold">{{ $item->medicine_name }}</span>
                                    <span class="text-gray-500">{{ $item->dosage ?: $item->frequency }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="pt-2 flex items-center space-x-2">
                            <a href="{{ route('doctor.prescriptions.show', $token->prescription->id) }}" class="flex-1 text-center py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs transition">
                                View Details
                            </a>
                            <a href="{{ route('doctor.prescriptions.print', $token->prescription->id) }}" target="_blank" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold text-xs transition">
                                <i class="fa-solid fa-print"></i>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="text-center py-5 space-y-3">
                        <i class="fa-solid fa-file-prescription text-3xl text-gray-300 block"></i>
                        <p class="text-xs text-gray-500">No prescription generated yet for this visit.</p>
                        <a href="{{ route('doctor.prescriptions.create', ['token_id' => $token->id]) }}" class="inline-flex items-center px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                            <i class="fa-solid fa-plus mr-1.5"></i> Add Prescription
                        </a>
                    </div>
                @endif
            </div>

            <!-- PAST VISITS WITH THIS DOCTOR -->
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                    <i class="fa-solid fa-history text-teal-600"></i>
                    <span>Patient History (With Dr. {{ $doctor->name }})</span>
                </h3>

                <div class="space-y-3">
                    @forelse($pastDoctorVisits as $pastToken)
                        <div class="p-3 rounded-lg bg-gray-50/70 border border-gray-100 text-xs space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-800">Token #{{ $pastToken->token_number }}</span>
                                <span class="text-[11px] text-gray-400 font-mono">{{ format_app_datetime($pastToken->token_date, 'd M, Y') }}</span>
                            </div>
                            @if($pastToken->diagnosis)
                                <p class="text-teal-700 font-semibold">{{ $pastToken->diagnosis }}</p>
                            @endif
                            @if($pastToken->symptoms)
                                <p class="text-gray-500 text-[11px] truncate">{{ $pastToken->symptoms }}</p>
                            @endif
                            <div class="pt-1 flex items-center justify-between">
                                <span class="text-[10px] font-bold text-gray-500 uppercase">{{ $pastToken->status }}</span>
                                <a href="{{ route('doctor.consultations.show', $pastToken->id) }}" class="text-[11px] font-bold text-teal-600 hover:underline">
                                    View Note →
                                </a>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 py-3 text-center">First recorded consultation with Dr. {{ $doctor->name }}.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
