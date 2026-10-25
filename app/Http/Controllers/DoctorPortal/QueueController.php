<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\PatientToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    /**
     * Display OPD token queue for the authenticated doctor.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = $request->get('doctor') ?? auth()->user()->doctor;

        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Karachi'));
        $todayDate = now($tz)->toDateString();
        $targetDate = $request->input('date', $todayDate);
        $statusFilter = $request->input('status', 'all');
        $search = trim($request->input('search', ''));

        $query = $doctor->tokens()->with(['patient', 'prescription']);

        if ($targetDate !== 'all' && !empty($targetDate)) {
            $query->where('token_date', $targetDate);
        }

        if ($statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('token_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('patient_number', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Order: in_consultation first, then waiting/called, then completed/cancelled
        $tokens = $query->orderByRaw("
            CASE 
                WHEN status = 'in_consultation' THEN 1
                WHEN status = 'called' THEN 2
                WHEN status = 'waiting' THEN 3
                WHEN status = 'completed' THEN 4
                ELSE 5
            END ASC
        ")->orderBy('token_date', 'desc')->orderBy('token_number', 'asc')->paginate(20)->withQueryString();

        // Queue counts for active date scope
        $baseCountQuery = ($targetDate === 'all')
            ? $doctor->tokens()
            : $doctor->tokens()->where('token_date', $targetDate);

        $counts = [
            'total'           => (clone $baseCountQuery)->count(),
            'waiting'         => (clone $baseCountQuery)->where('status', 'waiting')->count(),
            'in_consultation' => (clone $baseCountQuery)->where('status', 'in_consultation')->count(),
            'completed'       => (clone $baseCountQuery)->where('status', 'completed')->count(),
        ];

        $todayTokensCount = $doctor->tokens()->where('token_date', $todayDate)->count();
        $totalLifetimeTokens = $doctor->tokens()->count();

        return view('doctor-portal.queue.index', compact(
            'doctor',
            'tokens',
            'counts',
            'targetDate',
            'todayDate',
            'todayTokensCount',
            'totalLifetimeTokens',
            'statusFilter',
            'search'
        ));
    }

    /**
     * Call the token into active consultation.
     */
    public function call(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        DB::transaction(function () use ($token) {
            $token->status = 'in_consultation';
            if (!$token->called_at) {
                $token->called_at = now();
            }
            $token->save();
        });

        return redirect()->route('doctor.consultations.show', $token->id)
            ->with('success', "Token #{$token->token_number} ({$token->patient->name}) is now in consultation.");
    }

    /**
     * Mark the token consultation as completed.
     */
    public function complete(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        DB::transaction(function () use ($token) {
            $token->status = 'completed';
            $token->completed_at = now();
            $token->save();
        });

        return redirect()->route('doctor.queue.index')
            ->with('success', "Token #{$token->token_number} ({$token->patient->name}) marked as completed.");
    }

    /**
     * Cancel an assigned token with notes.
     */
    public function cancel(Request $request, PatientToken $token)
    {
        $doctor = auth()->user()->doctor;

        // Server-side Ownership Verification (IDOR protection)
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($token, $validated) {
            $token->status = 'cancelled';
            if (!empty($validated['cancel_reason'])) {
                $token->notes = trim(($token->notes ? $token->notes . ' | ' : '') . 'Cancelled by Doctor: ' . $validated['cancel_reason']);
            }
            $token->save();
        });

        return redirect()->route('doctor.queue.index')
            ->with('warning', "Token #{$token->token_number} cancelled.");
    }
}
