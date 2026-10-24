<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Show duty schedule and clinic availability settings for the authenticated doctor.
     */
    public function index()
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        $allDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $selectedDays = is_array($doctor->schedule_days) ? $doctor->schedule_days : ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        return view('doctor-portal.schedule.index', compact('doctor', 'allDays', 'selectedDays'));
    }

    /**
     * Update doctor's schedule and clinic timings.
     */
    public function update(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        $validated = $request->validate([
            'room_number'   => 'nullable|string|max:50',
            'start_time'    => 'nullable|string|max:20',
            'end_time'      => 'nullable|string|max:20',
            'schedule_days' => 'nullable|array',
            'schedule_days.*' => 'string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
        ]);

        $doctor->update([
            'room_number'   => $validated['room_number'] ?? null,
            'start_time'    => $validated['start_time'] ?? null,
            'end_time'      => $validated['end_time'] ?? null,
            'schedule_days' => $validated['schedule_days'] ?? [],
        ]);

        return redirect()->back()->with('success', 'Duty schedule and clinic details updated successfully.');
    }
}
