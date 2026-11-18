<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\PatientToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConsultationController extends Controller
{
    /**
     * Display consultation queue / history.
     */
    public function index()
    {
        return redirect()->route('doctor.queue.index');
    }

    /**
     * Show consultation workspace for a specific token.
     */
    public function show(PatientToken $token)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation.');
        }

        $token->load(['patient', 'prescription.items', 'doctor']);

        // Historical visits of this patient conducted by THIS doctor
        $pastDoctorVisits = PatientToken::where('patient_id', $token->patient_id)
            ->where('doctor_id', $doctor->id)
            ->where('id', '!=', $token->id)
            ->orderBy('token_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(5)
            ->get();

        return view('doctor-portal.consultations.show', compact('doctor', 'token', 'pastDoctorVisits'));
    }

    /**
     * Update clinical examination notes and status for the token.
     */
    public function update(Request $request, PatientToken $token)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation.');
        }

        $validated = $request->validate([
            'symptoms'               => 'nullable|string|max:2000',
            'diagnosis'              => 'nullable|string|max:1000',
            'clinical_notes'         => 'nullable|string|max:3000',
            'blood_pressure'         => 'nullable|string|max:50',
            'pulse_rate'             => 'nullable|string|max:50',
            'temperature'            => 'nullable|string|max:50',
            'weight'                 => 'nullable|string|max:50',
            'follow_up_date'         => 'nullable|date',
            'follow_up_instructions' => 'nullable|string|max:1000',
            'action'                 => 'nullable|in:save,save_and_complete',
        ]);

        $nextToken = null;
        DB::transaction(function () use ($token, $validated, $doctor, &$nextToken) {
            $token->fill([
                'symptoms'               => $validated['symptoms'] ?? null,
                'diagnosis'              => $validated['diagnosis'] ?? null,
                'clinical_notes'         => $validated['clinical_notes'] ?? null,
                'blood_pressure'         => $validated['blood_pressure'] ?? null,
                'pulse_rate'             => $validated['pulse_rate'] ?? null,
                'temperature'            => $validated['temperature'] ?? null,
                'weight'                 => $validated['weight'] ?? null,
                'follow_up_date'         => $validated['follow_up_date'] ?? null,
                'follow_up_instructions' => $validated['follow_up_instructions'] ?? null,
            ]);

            // If requested to complete consultation
            if (($validated['action'] ?? '') === 'save_and_complete') {
                $token->save();
                app(\App\Services\TokenQueueService::class)->completeConsultation($token, auth()->id());
                // Auto-call next waiting token for this doctor
                $nextToken = app(\App\Services\TokenQueueService::class)->callNext($doctor->id, $doctor->room_number, auth()->id());
            } elseif ($token->status === 'waiting' || $token->status === 'called') {
                $token->status = 'in_consultation';
                if (!$token->called_at) {
                    $token->called_at = now();
                }
                $token->save();
            } else {
                $token->save();
            }
        });

        if (($validated['action'] ?? '') === 'save_and_complete') {
            $msg = "Consultation for Token #{$token->formatted_token_number} saved and completed.";
            if ($nextToken) {
                $msg .= " Next Token #{$nextToken->formatted_token_number} ({$nextToken->patient->name}) called automatically to Room {$nextToken->room_number}.";
            }
            return redirect()->route('doctor.queue.index')
                ->with('success', $msg);
        }

        return redirect()->back()
            ->with('success', "Clinical examination details updated successfully.");
    }
}
