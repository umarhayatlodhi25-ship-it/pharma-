@extends('layouts.doctor')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">

    <!-- PAGE HEADER -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-gray-200">
        <div>
            <div class="flex items-center space-x-2 text-xs text-teal-600 font-semibold mb-1">
                <span>Doctor Portal</span>
                <span>/</span>
                <span>Practice Schedule</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center space-x-2">
                <i class="fa-regular fa-calendar-check text-teal-600"></i>
                <span>Clinic Schedule & Duty Timings</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Configure your active duty days and consulting hours for hospital OPD reception.
            </p>
        </div>
    </div>

    <!-- SCHEDULE FORM CARD -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 space-y-6">
        <form action="{{ route('doctor.schedule.update') }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- ROOM & HOURS -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                    <i class="fa-solid fa-door-open text-teal-600"></i>
                    <span>Clinic Facility & Hours</span>
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Clinic Room #</label>
                        <input type="text" name="room_number" value="{{ old('room_number', $doctor->room_number) }}" placeholder="e.g. Room 104, 1st Floor" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Duty Start Time</label>
                        <input type="text" name="start_time" value="{{ old('start_time', $doctor->start_time ?: '09:00 AM') }}" placeholder="09:00 AM" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                    </div>

                    <div>
                        <label class="block font-bold text-gray-700 mb-1">Duty End Time</label>
                        <input type="text" name="end_time" value="{{ old('end_time', $doctor->end_time ?: '05:00 PM') }}" placeholder="05:00 PM" class="w-full text-xs rounded-xl border-gray-300 focus:ring-teal-500 focus:border-teal-500 py-2 px-3 font-mono">
                    </div>
                </div>
            </div>

            <!-- DUTY DAYS CHECKBOXES -->
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center space-x-2 border-b border-gray-100 pb-3">
                    <i class="fa-solid fa-calendar-days text-teal-600"></i>
                    <span>Consultation Days in Week</span>
                </h3>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    @foreach($allDays as $day)
                    <label class="flex items-center space-x-2.5 p-3 rounded-xl border border-gray-200 hover:bg-slate-50 cursor-pointer transition">
                        <input type="checkbox" name="schedule_days[]" value="{{ $day }}" 
                               {{ in_array($day, $selectedDays) ? 'checked' : '' }}
                               class="rounded text-teal-600 focus:ring-teal-500 w-4 h-4">
                        <span class="font-semibold text-gray-800">{{ $day }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <!-- SUBMIT BUTTON -->
            <div class="pt-4 border-t border-gray-100 flex justify-end">
                <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl font-bold text-xs shadow-md shadow-teal-900/20 transition">
                    <i class="fa-solid fa-floppy-disk mr-2"></i> Save Schedule Settings
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
