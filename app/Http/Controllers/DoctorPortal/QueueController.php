<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\PatientToken;
use App\Services\TokenQueueService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    protected TokenQueueService $tokenQueueService;

    public function __construct(TokenQueueService $tokenQueueService)
    {
        $this->tokenQueueService = $tokenQueueService;
    }

    /**
     * Display OPD token queue for the authenticated doctor.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = $request->get('doctor') ?? auth()->user()->doctor;
        if (!$doctor) {
            abort(403, 'Doctor profile not associated with this account.');
        }

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
            if ($statusFilter === 'in_consultation') {
                $query->whereIn('status', ['called', 'in_consultation']);
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('token_number', 'like', "%{$search}%")
                  ->orWhere('token_prefix', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('patient_number', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Order: in_consultation/called first, then waiting, then hold/skipped, then completed/cancelled
        $tokens = $query->orderByRaw("
            CASE 
                WHEN status = 'in_consultation' THEN 1
                WHEN status = 'called' THEN 2
                WHEN status = 'waiting' THEN 3
                WHEN status = 'hold' THEN 4
                WHEN status = 'skipped' THEN 5
                WHEN status = 'completed' THEN 6
                ELSE 7
            END ASC
        ")->orderBy('token_number', 'asc')->paginate(20)->withQueryString();

        // Queue counts for active date scope
        $baseCountQuery = ($targetDate === 'all')
            ? $doctor->tokens()
            : $doctor->tokens()->where('token_date', $targetDate);

        $counts = [
            'total'           => (clone $baseCountQuery)->count(),
            'waiting'         => (clone $baseCountQuery)->where('status', 'waiting')->count(),
            'called'          => (clone $baseCountQuery)->where('status', 'called')->count(),
            'in_consultation' => (clone $baseCountQuery)->whereIn('status', ['called', 'in_consultation'])->count(),
            'hold'            => (clone $baseCountQuery)->where('status', 'hold')->count(),
            'skipped'         => (clone $baseCountQuery)->where('status', 'skipped')->count(),
            'completed'       => (clone $baseCountQuery)->where('status', 'completed')->count(),
            'cancelled'       => (clone $baseCountQuery)->where('status', 'cancelled')->count(),
        ];

        // Currently active patient in consultation or called
        $nowServing = $doctor->tokens()->with(['patient', 'prescription'])
            ->where('token_date', $todayDate)
            ->whereIn('status', ['called', 'in_consultation'])
            ->latest('called_at')
            ->first();

        // Next 5 waiting tokens for this doctor
        $nextTokens = $doctor->tokens()->with('patient')
            ->where('token_date', $todayDate)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->take(5)
            ->get();

        $todayTokensCount = $doctor->tokens()->where('token_date', $todayDate)->count();
        $totalLifetimeTokens = $doctor->tokens()->count();

        return view('doctor-portal.queue.index', compact(
            'doctor',
            'tokens',
            'counts',
            'nowServing',
            'nextTokens',
            'targetDate',
            'todayDate',
            'todayTokensCount',
            'totalLifetimeTokens',
            'statusFilter',
            'search'
        ));
    }

    /**
     * Atomically call the next waiting token.
     */
    public function callNext(Request $request)
    {
        $doctor = auth()->user()->doctor;
        if (!$doctor) {
            abort(403, 'Doctor profile required.');
        }

        $token = $this->tokenQueueService->callNext($doctor->id, $doctor->room_number, auth()->id());

        if (!$token) {
            return redirect()->route('doctor.queue.index')
                ->with('info', 'There are no waiting patients in today\'s queue.');
        }

        return redirect()->route('doctor.queue.index')
            ->with('success', "Calling Token #{$token->formatted_token_number} ({$token->patient->name}) to Room {$token->room_number}.");
    }

    /**
     * Call a specific token into active consultation.
     */
    public function call(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $this->tokenQueueService->startConsultation($token, auth()->id());

        return redirect()->route('doctor.consultations.show', $token->id)
            ->with('success', "Token #{$token->formatted_token_number} ({$token->patient->name}) is now in consultation.");
    }

    /**
     * Re-announce / recall the token.
     */
    public function recall(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $this->tokenQueueService->callToken($token, $doctor->room_number, auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('success', "Re-announced Token #{$token->formatted_token_number} ({$token->patient->name}).");
    }

    /**
     * Start consultation (moves to consultation room screen).
     */
    public function startConsultation(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $this->tokenQueueService->startConsultation($token, auth()->id());

        return redirect()->route('doctor.consultations.show', $token->id)
            ->with('success', "Token #{$token->formatted_token_number} ({$token->patient->name}) is now in consultation.");
    }

    /**
     * Mark the token consultation as completed.
     */
    public function complete(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $this->tokenQueueService->completeConsultation($token, auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('success', "Token #{$token->formatted_token_number} ({$token->patient->name}) marked as completed.");
    }

    /**
     * Skip the token (absent / patient stepped away).
     */
    public function skip(Request $request, PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $reason = $request->input('reason', 'Patient not present when called');
        $this->tokenQueueService->skipToken($token, $reason, auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('warning', "Token #{$token->formatted_token_number} marked as skipped.");
    }

    /**
     * Put token on hold (e.g. waiting for lab / X-ray).
     */
    public function hold(Request $request, PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $reason = $request->input('reason', 'Awaiting test results or vital signs');
        $this->tokenQueueService->holdToken($token, $reason, auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('info', "Token #{$token->formatted_token_number} placed on hold.");
    }

    /**
     * Resume a hold or skipped token back to waiting queue.
     */
    public function resume(PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $this->tokenQueueService->resumeToken($token, auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('success', "Token #{$token->formatted_token_number} resumed into waiting queue.");
    }

    /**
     * Cancel an assigned token with mandatory reason.
     */
    public function cancel(Request $request, PatientToken $token)
    {
        $doctor = auth()->user()->doctor;
        if ($token->doctor_id !== $doctor->id) {
            abort(403, 'Unauthorized Access: You are not assigned to this patient consultation token.');
        }

        $validated = $request->validate([
            'cancel_reason' => 'required|string|max:255',
        ]);

        $this->tokenQueueService->cancelToken($token, $validated['cancel_reason'], auth()->id());

        return redirect()->route('doctor.queue.index')
            ->with('warning', "Token #{$token->formatted_token_number} cancelled.");
    }
}
