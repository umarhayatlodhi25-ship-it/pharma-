@extends('layouts.doctor')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <a href="{{ route('doctor.prescriptions.index') }}" class="hover:underline">Prescriptions</a>
                <span>/</span>
                <span>{{ $prescription->prescription_number }}</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-file-prescription text-teal-600"></i>
                <span>Prescription Slip</span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-teal-100 text-teal-800 border border-teal-200">
                    {{ $prescription->prescription_number }}
                </span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Issued on {{ format_app_datetime($prescription->created_at, 'd M, Y h:i A') }}
            </p>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('doctor.prescriptions.print', $prescription->id) }}" target="_blank" class="inline-flex items-center space-x-1.5 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                <i class="fa-solid fa-print"></i>
                <span>Print Prescription</span>
            </a>

            @if($prescription->patient_token_id)
                <a href="{{ route('doctor.consultations.show', $prescription->patient_token_id) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>Consultation Workspace</span>
                </a>
            @endif

            <a href="{{ route('doctor.prescriptions.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>
    </div>

    <!-- PRESCRIPTION DOCUMENT CARD -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-md p-6 sm:p-8 space-y-6">
        
        <!-- CLINICAL HEADER -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between border-b border-gray-200 pb-5 gap-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900 tracking-tight">Dr. {{ $prescription->doctor->name }}</h2>
                <p class="text-xs text-teal-700 font-semibold">{{ $prescription->doctor->specialization ?: 'Specialist Consultant' }}</p>
                <p class="text-xs text-gray-500">{{ $prescription->doctor->qualification ?: '' }}</p>
                @if($prescription->doctor->room_number)
                    <p class="text-xs text-gray-400 mt-0.5">Clinic Room: {{ $prescription->doctor->room_number }}</p>
                @endif
            </div>

            <div class="sm:text-right space-y-0.5 text-xs text-gray-500">
                <div class="font-mono text-base font-black text-gray-900">{{ $prescription->prescription_number }}</div>
                <div>Date: <span class="font-semibold text-gray-800">{{ format_app_datetime($prescription->created_at, 'd M, Y') }}</span></div>
                @if($prescription->token)
                    <div>Token #: <span class="font-mono font-bold text-gray-900">#{{ $prescription->token->token_number }}</span></div>
                @endif
            </div>
        </div>

        <!-- PATIENT DEMOGRAPHICS STRIP -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-gray-400 block font-medium">Patient Name</span>
                <span class="font-bold text-gray-900 text-sm">{{ $prescription->patient->name }}</span>
                <span class="text-[11px] text-teal-700 font-mono">{{ $prescription->patient->patient_number }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Age & Gender</span>
                <span class="font-bold text-gray-800 text-sm">
                    {{ $prescription->patient->gender ?: '—' }}, {{ $prescription->patient->age ? $prescription->patient->age.' yrs' : '—' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Contact Phone</span>
                <span class="font-mono font-semibold text-gray-800">{{ $prescription->patient->phone ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium">Diagnosis</span>
                <span class="font-bold text-teal-800 text-sm">{{ $prescription->diagnosis ?: 'General Consultation' }}</span>
            </div>
        </div>

        <!-- MEDICINES LIST (RX) -->
        <div class="space-y-3">
            <div class="flex items-center space-x-2 border-b border-gray-100 pb-2">
                <span class="text-2xl font-serif font-black text-teal-700 italic">Rx</span>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Prescribed Medications</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-gray-50 text-gray-500 uppercase font-semibold text-[10px] tracking-wider border-b border-gray-200">
                            <th class="py-2.5 px-3 w-8">#</th>
                            <th class="py-2.5 px-3">Medicine & Dosage</th>
                            <th class="py-2.5 px-3">Frequency</th>
                            <th class="py-2.5 px-3">Duration</th>
                            <th class="py-2.5 px-3">Special Instructions</th>
                            <th class="py-2.5 px-3 text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 font-mono">
                        @foreach($prescription->items as $index => $item)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-3 text-gray-400 font-bold">{{ $index + 1 }}</td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-gray-900 text-sm block font-sans">{{ $item->medicine_name }}</span>
                                @if($item->dosage)
                                    <span class="text-[11px] text-gray-500">{{ $item->dosage }}</span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-semibold text-teal-800 font-sans">
                                {{ $item->frequency ?: '—' }}
                            </td>
                            <td class="py-3 px-3 text-gray-700 font-sans">
                                {{ $item->duration ?: '—' }}
                            </td>
                            <td class="py-3 px-3 text-gray-600 font-sans italic">
                                {{ $item->instructions ?: '—' }}
                            </td>
                            <td class="py-3 px-3 text-center font-bold text-gray-900">
                                {{ $item->quantity ?: 1 }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CLINICAL NOTES & FOLLOW-UP -->
        @if($prescription->clinical_notes || $prescription->follow_up_date || $prescription->follow_up_instructions)
        <div class="pt-4 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            @if($prescription->clinical_notes)
            <div class="bg-gray-50 p-4 rounded-xl border border-gray-100">
                <span class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Doctor's Advice & Precautions</span>
                <p class="text-gray-800 leading-relaxed">{{ $prescription->clinical_notes }}</p>
            </div>
            @endif

            @if($prescription->follow_up_date || $prescription->follow_up_instructions)
            <div class="bg-amber-50/70 p-4 rounded-xl border border-amber-200/60">
                <span class="text-[10px] font-bold text-amber-800 uppercase block mb-1">Follow-Up Instructions</span>
                @if($prescription->follow_up_date)
                    <p class="font-bold text-amber-900">Next Visit: {{ format_app_datetime($prescription->follow_up_date, 'd M, Y') }}</p>
                @endif
                @if($prescription->follow_up_instructions)
                    <p class="text-amber-800 mt-1">{{ $prescription->follow_up_instructions }}</p>
                @endif
            </div>
            @endif
        </div>
        @endif

        <!-- SIGNATURE AREA -->
        <div class="pt-8 border-t border-gray-200 flex items-center justify-between text-xs text-gray-500">
            <div>
                <span class="text-[10px] text-gray-400 block font-mono">Issued by: {{ $prescription->createdBy ? $prescription->createdBy->name : 'Doctor Portal' }}</span>
            </div>
            <div class="text-center">
                <div class="w-48 border-b border-gray-400 mb-1"></div>
                <span class="font-bold text-gray-800">Dr. {{ $prescription->doctor->name }}</span>
                <span class="text-[10px] text-gray-400 block">Consultant Signature</span>
            </div>
        </div>

    </div>

</div>
@endsection
