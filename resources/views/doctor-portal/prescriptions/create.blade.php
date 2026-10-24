@extends('layouts.doctor')

@section('content')
<div class="space-y-6">

    <!-- BREADCRUMB & HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <a href="{{ route('doctor.prescriptions.index') }}" class="hover:underline">Prescriptions</a>
                <span>/</span>
                <span>New Prescription</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-solid fa-file-prescription text-teal-600"></i>
                <span>Write Medical Prescription</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Dr. {{ $doctor->name }} • {{ $doctor->specialization ?: 'Specialist Consultant' }}
            </p>
        </div>

        <div>
            @if($token)
                <a href="{{ route('doctor.consultations.show', $token->id) }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back to Consultation</span>
                </a>
            @else
                <a href="{{ route('doctor.prescriptions.index') }}" class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Prescriptions List</span>
                </a>
            @endif
        </div>
    </div>

    <form action="{{ route('doctor.prescriptions.store') }}" method="POST" 
          x-data="{
              medicinesList: {{ Js::from($medicines) }},
              items: [
                  { medicine_id: '', medicine_name: '', dosage: '', frequency: '1-0-1', duration: '5 Days', instructions: 'After meal', quantity: 1 }
              ],
              addItem() {
                  this.items.push({ medicine_id: '', medicine_name: '', dosage: '', frequency: '1-0-1', duration: '5 Days', instructions: 'After meal', quantity: 1 });
              },
              removeItem(index) {
                  if (this.items.length > 1) {
                      this.items.splice(index, 1);
                  }
              },
              onMedicineSelected(index, event) {
                  const selectedId = event.target.value;
                  const med = this.medicinesList.find(m => m.id == selectedId);
                  if (med) {
                      this.items[index].medicine_name = med.name + (med.strength ? ' ' + med.strength : '') + (med.dosage_form ? ' (' + med.dosage_form + ')' : '');
                      if (!this.items[index].dosage && med.strength) {
                          this.items[index].dosage = med.strength;
                      }
                  }
              }
          }" 
          class="space-y-6">
        @csrf

        @if($token)
            <input type="hidden" name="patient_token_id" value="{{ $token->id }}">
        @endif

        <!-- PATIENT SELECTION CARD -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                <i class="fa-solid fa-user-check text-teal-600"></i>
                <span>Patient Information</span>
            </h3>

            @if($patient)
                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div>
                        <span class="text-gray-400 block font-medium">Patient Name</span>
                        <span class="font-bold text-gray-900 text-sm">{{ $patient->name }}</span>
                        <span class="text-[11px] text-teal-700 font-mono">{{ $patient->patient_number }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-medium">Age & Gender</span>
                        <span class="font-bold text-gray-800 text-sm">
                            {{ $patient->gender ?: '—' }}, {{ $patient->age ? $patient->age.' yrs' : '—' }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-medium">Contact Phone</span>
                        <span class="font-mono font-semibold text-gray-800 text-sm">{{ $patient->phone ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-gray-400 block font-medium">Consultation Token</span>
                        @if($token)
                            <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-bold bg-slate-900 text-white text-xs">
                                Token #{{ $token->token_number }}
                            </span>
                        @else
                            <span class="text-gray-400 italic">Direct visit</span>
                        @endif
                    </div>
                </div>
            @else
                <div class="text-xs">
                    <label class="block font-bold text-gray-700 mb-1">Select Patient (Authorized Under Your Care)</label>
                    <select name="patient_id" required class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2.5 px-3">
                        <option value="">-- Choose Patient --</option>
                        @foreach($authorizedPatients as $p)
                            <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->patient_number }}) • {{ $p->phone ?: 'No phone' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Provisional / Confirmed Diagnosis</label>
                    <input type="text" name="diagnosis" value="{{ old('diagnosis', $token ? $token->diagnosis : '') }}" placeholder="e.g. Acute Upper Respiratory Tract Infection" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-semibold">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">General Clinical Advice / Dietary Restrictions</label>
                    <input type="text" name="clinical_notes" value="{{ old('clinical_notes', $token ? $token->clinical_notes : '') }}" placeholder="e.g. Rest, take plenty of fluids, avoid cold drinks" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>
            </div>
        </div>

        <!-- RX MEDICINES TABLE CARD -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div>
                    <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2">
                        <i class="fa-solid fa-pills text-teal-600"></i>
                        <span>Prescribed Medicines (Rx)</span>
                    </h3>
                    <p class="text-[11px] text-gray-400">Choose from pharmacy catalogue or type custom medicine names.</p>
                </div>
                <button type="button" @click="addItem()" class="inline-flex items-center space-x-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg font-bold text-xs shadow-2xs transition">
                    <i class="fa-solid fa-plus mr-1"></i> Add Medicine Row
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, index) in items" :key="index">
                    <div class="p-4 rounded-xl bg-gray-50/80 border border-gray-200/80 space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-200/60 pb-2">
                            <span class="text-xs font-bold text-teal-800 flex items-center space-x-1.5 font-mono">
                                <span class="w-5 h-5 rounded-full bg-teal-600 text-white flex items-center justify-center text-[10px]" x-text="index + 1"></span>
                                <span>Medicine Entry</span>
                            </span>
                            <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">
                                <i class="fa-solid fa-trash-can mr-1"></i> Remove
                            </button>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                            <!-- Select from catalogue -->
                            <div class="sm:col-span-5">
                                <label class="block font-semibold text-gray-600 mb-1">Pick from Catalogue</label>
                                <select :name="`items[${index}][medicine_id]`" @change="onMedicineSelected(index, $event)" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5">
                                    <option value="">-- Or type manually below --</option>
                                    <template x-for="m in medicinesList" :key="m.id">
                                        <option :value="m.id" x-text="`${m.name} ${m.strength || ''} ${m.dosage_form ? '('+m.dosage_form+')' : ''}`"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Manual or filled medicine name -->
                            <div class="sm:col-span-7">
                                <label class="block font-semibold text-gray-700 mb-1">Medicine Name & Formulation *</label>
                                <input type="text" :name="`items[${index}][medicine_name]`" x-model="item.medicine_name" required placeholder="e.g. Tab. Panadol 500mg" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5 font-bold text-gray-900">
                            </div>

                            <!-- Dosage -->
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-600 mb-1">Dosage</label>
                                <input type="text" :name="`items[${index}][dosage]`" x-model="item.dosage" placeholder="e.g. 500mg / 1 tab" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5">
                            </div>

                            <!-- Frequency -->
                            <div class="sm:col-span-3">
                                <label class="block font-semibold text-gray-600 mb-1">Frequency</label>
                                <select :name="`items[${index}][frequency]`" x-model="item.frequency" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5 font-medium">
                                    <option value="1-0-1">1-0-1 (Twice Daily - Morning & Night)</option>
                                    <option value="1-1-1">1-1-1 (Thrice Daily - Morning, Noon & Night)</option>
                                    <option value="1-0-0">1-0-0 (Once Daily - Morning)</option>
                                    <option value="0-0-1">0-0-1 (Once Daily - Night / Bedtime)</option>
                                    <option value="OD">OD (Once a day)</option>
                                    <option value="BD">BD (Twice a day)</option>
                                    <option value="TDS">TDS (Three times a day)</option>
                                    <option value="QID">QID (Four times a day)</option>
                                    <option value="SOS">SOS (As Needed / In Emergency)</option>
                                </select>
                            </div>

                            <!-- Duration -->
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-600 mb-1">Duration</label>
                                <input type="text" :name="`items[${index}][duration]`" x-model="item.duration" placeholder="e.g. 5 Days" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5">
                            </div>

                            <!-- Instructions -->
                            <div class="sm:col-span-3">
                                <label class="block font-semibold text-gray-600 mb-1">Instructions</label>
                                <select :name="`items[${index}][instructions]`" x-model="item.instructions" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5">
                                    <option value="After meal">After meal (کھانے کے بعد)</option>
                                    <option value="Before meal">Before meal (کھانے سے پہلے)</option>
                                    <option value="With water">With water (پانی کے ساتھ)</option>
                                    <option value="With milk">With milk (دودھ کے ساتھ)</option>
                                    <option value="At bedtime">At bedtime (سوتے وقت)</option>
                                    <option value="Empty stomach">Empty stomach (نہار منہ)</option>
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div class="sm:col-span-2">
                                <label class="block font-semibold text-gray-600 mb-1">Qty / Units</label>
                                <input type="number" :name="`items[${index}][quantity]`" x-model="item.quantity" min="1" placeholder="10" class="w-full text-xs rounded-lg border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-1.5 px-2.5 font-mono">
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- FOLLOW UP & SUBMIT CARD -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
            <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                <i class="fa-regular fa-calendar-check text-teal-600"></i>
                <span>Follow-Up Appointment & Finalization</span>
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Next Follow-Up Date</label>
                    <input type="date" name="follow_up_date" value="{{ old('follow_up_date', $token && $token->follow_up_date ? $token->follow_up_date->format('Y-m-d') : '') }}" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>
                <div>
                    <label class="block font-bold text-gray-700 mb-1">Follow-Up Instructions</label>
                    <input type="text" name="follow_up_instructions" value="{{ old('follow_up_instructions', $token ? $token->follow_up_instructions : '') }}" placeholder="e.g. Revisit for blood pressure checkup" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-xs text-gray-400">
                    <i class="fa-solid fa-lock mr-1"></i> Prescriptions are digitally signed and finalized upon submission.
                </span>

                <button type="submit" class="inline-flex items-center px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs shadow-md shadow-teal-900/20 transition">
                    <i class="fa-solid fa-file-signature mr-2"></i> Issue & Finalize Prescription
                </button>
            </div>
        </div>

    </form>

</div>
@endsection
