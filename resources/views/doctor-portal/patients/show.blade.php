@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <a href="{{ route('doctor.patients.index') }}" class="hover:underline">My Patients</a>
                <span>/</span>
                <span>{{ $patient->name }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-hospital-user text-teal-600"></i>
                <span>{{ $patient->name }}</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-teal-100 text-teal-800 border border-teal-200">
                    {{ $patient->patient_number }}
                </span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Authorized medical record under Dr. {{ $doctor->name }}'s clinical care.
            </p>
        </div>

        <div class="flex items-center space-x-2">
            @if($activeTokenToday)
                <a href="{{ route('doctor.consultations.show', $activeTokenToday->id) }}" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-md shadow-indigo-900/20">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>Consult Token #{{ $activeTokenToday->token_number }} Today</span>
                </a>
            @endif

            <a href="{{ route('doctor.prescriptions.create', ['patient_id' => $patient->id]) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-prescription"></i>
                <span>Write Prescription</span>
            </a>

            <a href="{{ route('doctor.patients.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- PATIENT MASTER PROFILE CARD -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-4">
        <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
            <i class="fa-solid fa-id-card text-teal-600"></i>
            <span>Patient Demographic Details</span>
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-gray-400 block font-medium">Full Name</span>
                <span class="font-bold text-gray-900 text-sm">{{ $patient->name }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Age & Gender</span>
                <span class="font-bold text-gray-800 text-sm">
                    {{ $patient->gender ?: '—' }}, {{ $patient->age ? $patient->age.' yrs' : '—' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Primary Contact</span>
                <span class="font-mono font-bold text-gray-800 text-sm">{{ $patient->phone ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">CNIC / ID</span>
                <span class="font-mono font-semibold text-gray-800">{{ $patient->cnic ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Father / Husband Name</span>
                <span class="font-semibold text-gray-800">{{ $patient->father_husband_name ?: '—' }}</span>
            </div>
            <div class="sm:col-span-2">
                <span class="text-gray-400 block font-medium">Residential Address</span>
                <span class="font-semibold text-gray-800">{{ $patient->address ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Total Visits with Dr. {{ $doctor->name }}</span>
                <span class="font-mono font-black text-teal-700 text-sm">{{ $doctorTokens->count() }} visits</span>
            </div>
        </div>
    </div>

    <!-- CONSULTATION HISTORY & CLINICAL PROGRESS NOTES -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-notes-medical text-teal-600"></i>
                <span>OPD Consultation History with Dr. {{ $doctor->name }}</span>
            </h3>
            <span class="text-xs text-gray-400">{{ $doctorTokens->count() }} consultations recorded</span>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse($doctorTokens as $visit)
                <div class="p-5 hover:bg-slate-50/50 transition space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-sm bg-slate-900 text-white px-2.5 py-0.5 rounded-lg">
                                Token #{{ $visit->token_number }}
                            </span>
                            <span class="text-xs font-semibold text-gray-700">
                                {{ format_app_datetime($visit->token_date, 'd M, Y') }}
                            </span>
                            @if($visit->created_at)
                                <span class="text-[11px] text-gray-400">({{ format_app_datetime($visit->created_at, 'h:i A') }})</span>
                            @endif
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $visit->status === 'completed' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                                {{ ucfirst($visit->status) }}
                            </span>
                        </div>

                        <div class="flex items-center space-x-2">
                            @if($visit->prescription)
                                <a href="{{ route('doctor.prescriptions.show', $visit->prescription->id) }}" class="inline-flex items-center space-x-1 px-3 py-1 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 rounded-lg text-xs font-bold transition">
                                    <i class="fa-solid fa-prescription mr-1"></i> {{ $visit->prescription->prescription_number }}
                                </a>
                            @endif
                            <a href="{{ route('doctor.consultations.show', $visit->id) }}" class="inline-flex items-center space-x-1 px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-lg text-xs font-bold transition">
                                <i class="fa-solid fa-eye mr-1"></i> Open Workspace
                            </a>
                        </div>
                    </div>

                    <!-- Vitals strip if recorded -->
                    @if($visit->blood_pressure || $visit->pulse_rate || $visit->temperature || $visit->weight)
                    <div class="flex flex-wrap items-center gap-3 bg-gray-50 p-2.5 rounded-lg text-xs font-mono text-gray-600 border border-gray-100">
                        @if($visit->blood_pressure) <span><strong class="text-gray-800">BP:</strong> {{ $visit->blood_pressure }} mmHg</span> @endif
                        @if($visit->pulse_rate) <span><strong class="text-gray-800">Pulse:</strong> {{ $visit->pulse_rate }} bpm</span> @endif
                        @if($visit->temperature) <span><strong class="text-gray-800">Temp:</strong> {{ $visit->temperature }} °F</span> @endif
                        @if($visit->weight) <span><strong class="text-gray-800">Weight:</strong> {{ $visit->weight }} kg</span> @endif
                    </div>
                    @endif

                    <!-- Clinical Findings -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                        @if($visit->diagnosis)
                        <div class="bg-teal-50/50 p-3 rounded-lg border border-teal-100/70">
                            <span class="text-[10px] font-bold text-teal-700 uppercase block mb-1">Diagnosis</span>
                            <p class="font-bold text-gray-900">{{ $visit->diagnosis }}</p>
                        </div>
                        @endif

                        @if($visit->symptoms)
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Reported Symptoms</span>
                            <p class="text-gray-700 leading-relaxed">{{ $visit->symptoms }}</p>
                        </div>
                        @endif

                        @if($visit->clinical_notes)
                        <div class="bg-gray-50 p-3 rounded-lg border border-gray-100">
                            <span class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Clinical Notes & Advice</span>
                            <p class="text-gray-700 leading-relaxed">{{ $visit->clinical_notes }}</p>
                        </div>
                        @endif
                    </div>

                    @if($visit->follow_up_date || $visit->follow_up_instructions)
                    <div class="text-xs text-amber-800 bg-amber-50/70 p-2.5 rounded-lg border border-amber-200/60 flex items-center space-x-2">
                        <i class="fa-regular fa-calendar-check text-amber-600"></i>
                        <span>
                            <strong>Follow-up:</strong> {{ $visit->follow_up_date ? format_app_datetime($visit->follow_up_date, 'd M, Y') : 'As advised' }}
                            @if($visit->follow_up_instructions) • {{ $visit->follow_up_instructions }} @endif
                        </span>
                    </div>
                    @endif
                </div>
            @empty
                <div class="py-8 text-center text-gray-400 text-xs">
                    <i class="fa-solid fa-notes-medical text-3xl mb-2 text-gray-300 block"></i>
                    No consultation notes recorded yet for this patient.
                </div>
            @endforelse
        </div>
    </div>

    <!-- PRESCRIPTIONS WRITTEN BY THIS DOCTOR -->
    @if($doctorPrescriptions->count() > 0)
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-sm font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-prescription text-teal-600"></i>
                <span>Prescriptions Issued by Dr. {{ $doctor->name }}</span>
            </h3>
            <span class="text-xs text-gray-400">{{ $doctorPrescriptions->count() }} issued</span>
        </div>

        <div class="divide-y divide-gray-100">
            @foreach($doctorPrescriptions as $rx)
                <div class="p-4 hover:bg-slate-50/50 transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-xs">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-teal-700">{{ $rx->prescription_number }}</span>
                            <span class="text-gray-400">•</span>
                            <span class="text-gray-600">{{ format_app_datetime($rx->created_at, 'd M, Y h:i A') }}</span>
                            @if($rx->diagnosis)
                                <span class="text-gray-400">•</span>
                                <span class="font-semibold text-gray-800">{{ $rx->diagnosis }}</span>
                            @endif
                        </div>
                        <p class="text-[11px] text-gray-500 mt-1">
                            {{ $rx->items->count() }} medicines: {{ $rx->items->pluck('medicine_name')->join(', ') }}
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <a href="{{ route('doctor.prescriptions.show', $rx->id) }}" class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs transition">
                            View Details
                        </a>
                        <a href="{{ route('doctor.prescriptions.print', $rx->id) }}" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-bold text-xs transition">
                            <i class="fa-solid fa-print"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
@endsection
