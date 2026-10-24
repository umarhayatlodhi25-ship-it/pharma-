<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Slip - {{ $prescription->prescription_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .print-container { box-shadow: none !important; border: none !important; max-width: 100% !important; margin: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 p-4 sm:p-8 font-sans text-gray-800">

    <!-- FLOATING ACTION TOOLBAR -->
    <div class="no-print max-w-3xl mx-auto mb-4 flex items-center justify-between bg-slate-900 text-white p-3 rounded-xl shadow-lg">
        <div class="flex items-center space-x-2 text-xs">
            <i class="fa-solid fa-file-prescription text-teal-400"></i>
            <span class="font-bold">Prescription Slip: {{ $prescription->prescription_number }}</span>
        </div>
        <div class="flex items-center space-x-2">
            <button onclick="window.print()" class="px-4 py-1.5 bg-teal-500 hover:bg-teal-600 text-white font-bold text-xs rounded-lg shadow-xs transition">
                <i class="fa-solid fa-print mr-1.5"></i> Print Prescription
            </button>
            <button onclick="window.close()" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-gray-300 font-bold text-xs rounded-lg transition">
                Close
            </button>
        </div>
    </div>

    <!-- PRINTABLE SLIP CONTAINER -->
    <div class="print-container max-w-3xl mx-auto bg-white p-8 sm:p-10 rounded-2xl shadow-xl border border-gray-200 space-y-6">

        <!-- CLINICAL HEADER -->
        <div class="flex items-start justify-between border-b-2 border-teal-600 pb-5">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="w-8 h-8 rounded-lg bg-teal-600 text-white flex items-center justify-center font-bold text-sm">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </span>
                    <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Dr. {{ $prescription->doctor->name }}</h1>
                </div>
                <p class="text-xs font-bold text-teal-700">{{ $prescription->doctor->specialization ?: 'Specialist Consultant' }}</p>
                <p class="text-[11px] text-gray-500">{{ $prescription->doctor->qualification ?: '' }}</p>
                @if($prescription->doctor->room_number)
                    <p class="text-[11px] text-gray-500">Clinic Room: {{ $prescription->doctor->room_number }}</p>
                @endif
                @if($prescription->doctor->phone)
                    <p class="text-[11px] text-gray-500 font-mono">Clinic Contact: {{ $prescription->doctor->phone }}</p>
                @endif
            </div>

            <div class="text-right space-y-1">
                <span class="inline-block px-3 py-1 bg-teal-50 border border-teal-200 text-teal-900 font-mono font-bold text-sm rounded-lg">
                    {{ $prescription->prescription_number }}
                </span>
                <p class="text-xs text-gray-600">Date: <span class="font-bold">{{ format_app_datetime($prescription->created_at, 'd M, Y') }}</span></p>
                @if($prescription->token)
                    <p class="text-xs text-gray-500">Token: <span class="font-mono font-bold text-gray-800">#{{ $prescription->token->token_number }}</span></p>
                @endif
            </div>
        </div>

        <!-- PATIENT DETAILS BAR -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div>
                <span class="text-gray-400 block font-medium text-[10px] uppercase">Patient Name</span>
                <span class="font-bold text-gray-900 text-sm">{{ $prescription->patient->name }}</span>
                <span class="text-[11px] text-teal-700 font-mono">{{ $prescription->patient->patient_number }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium text-[10px] uppercase">Age / Gender</span>
                <span class="font-semibold text-gray-800">
                    {{ $prescription->patient->gender ?: '—' }}, {{ $prescription->patient->age ? $prescription->patient->age.' yrs' : '—' }}
                </span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium text-[10px] uppercase">Phone</span>
                <span class="font-mono font-semibold text-gray-800">{{ $prescription->patient->phone ?: '—' }}</span>
            </div>
            <div>
                <span class="text-gray-400 block font-medium text-[10px] uppercase">Diagnosis</span>
                <span class="font-bold text-gray-900">{{ $prescription->diagnosis ?: 'General Consultation' }}</span>
            </div>
        </div>

        <!-- VITALS (IF RECORDED ON TOKEN) -->
        @if($prescription->token && ($prescription->token->blood_pressure || $prescription->token->pulse_rate || $prescription->token->temperature || $prescription->token->weight))
        <div class="flex items-center space-x-4 bg-teal-50/50 p-2.5 rounded-lg text-xs font-mono text-gray-700 border border-teal-100">
            <span class="font-bold uppercase tracking-wider text-[10px] text-teal-800 font-sans">Vitals:</span>
            @if($prescription->token->blood_pressure) <span>BP: {{ $prescription->token->blood_pressure }} mmHg</span> @endif
            @if($prescription->token->pulse_rate) <span>Pulse: {{ $prescription->token->pulse_rate }} bpm</span> @endif
            @if($prescription->token->temperature) <span>Temp: {{ $prescription->token->temperature }} °F</span> @endif
            @if($prescription->token->weight) <span>Weight: {{ $prescription->token->weight }} kg</span> @endif
        </div>
        @endif

        <!-- PRESCRIPTION MEDICINES -->
        <div class="space-y-3 pt-2">
            <div class="flex items-center space-x-2">
                <span class="text-3xl font-serif font-black text-teal-800 italic">Rx</span>
                <span class="text-xs font-bold uppercase tracking-wider text-gray-400">Medications</span>
            </div>

            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b-2 border-gray-300 text-gray-600 uppercase font-bold text-[10px] tracking-wider">
                        <th class="py-2 px-2 w-8">#</th>
                        <th class="py-2 px-2">Medicine Formulation</th>
                        <th class="py-2 px-2">Dosage</th>
                        <th class="py-2 px-2">Frequency</th>
                        <th class="py-2 px-2">Duration</th>
                        <th class="py-2 px-2">Instructions</th>
                        <th class="py-2 px-2 text-center">Qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($prescription->items as $idx => $item)
                    <tr>
                        <td class="py-3 px-2 font-mono text-gray-400 font-bold">{{ $idx + 1 }}</td>
                        <td class="py-3 px-2">
                            <span class="font-bold text-gray-900 text-sm block">{{ $item->medicine_name }}</span>
                        </td>
                        <td class="py-3 px-2 font-mono text-gray-700">{{ $item->dosage ?: '—' }}</td>
                        <td class="py-3 px-2 font-semibold text-gray-800">{{ $item->frequency ?: '—' }}</td>
                        <td class="py-3 px-2 text-gray-700">{{ $item->duration ?: '—' }}</td>
                        <td class="py-3 px-2 text-gray-600 italic">{{ $item->instructions ?: '—' }}</td>
                        <td class="py-3 px-2 text-center font-mono font-bold text-gray-900">{{ $item->quantity ?: 1 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- CLINICAL ADVICE & FOLLOW-UP -->
        @if($prescription->clinical_notes || $prescription->follow_up_date || $prescription->follow_up_instructions)
        <div class="pt-4 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            @if($prescription->clinical_notes)
            <div>
                <span class="text-[10px] font-bold text-gray-500 uppercase block mb-1">Advice & Instructions</span>
                <p class="text-gray-800 leading-relaxed">{{ $prescription->clinical_notes }}</p>
            </div>
            @endif

            @if($prescription->follow_up_date || $prescription->follow_up_instructions)
            <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                <span class="text-[10px] font-bold text-gray-700 uppercase block mb-1">Follow-Up Appointment</span>
                @if($prescription->follow_up_date)
                    <p class="font-bold text-gray-900">Next Visit: {{ format_app_datetime($prescription->follow_up_date, 'd M, Y') }}</p>
                @endif
                @if($prescription->follow_up_instructions)
                    <p class="text-gray-600 mt-0.5">{{ $prescription->follow_up_instructions }}</p>
                @endif
            </div>
            @endif
        </div>
        @endif

        <!-- SIGNATURE FOOTER -->
        <div class="pt-12 flex items-end justify-between text-xs text-gray-500">
            <div>
                <p class="text-[10px] text-gray-400">Generated via Clinical Doctor Portal • {{ config('app.name') }}</p>
                <p class="text-[10px] text-gray-400">Dispense only with verified pharmacist check.</p>
            </div>
            <div class="text-center">
                <div class="w-52 border-b border-gray-500 mb-1"></div>
                <p class="font-bold text-gray-900">Dr. {{ $prescription->doctor->name }}</p>
                <p class="text-[10px] text-gray-500">Authorized Medical Practitioner Signature</p>
            </div>
        </div>

    </div>

</body>
</html>
