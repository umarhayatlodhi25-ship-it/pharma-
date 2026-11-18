<?php

namespace App\Http\Controllers;

use App\Models\AccountProfile;
use App\Models\PatientToken;
use App\Models\TokenCallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TokenDisplayController extends Controller
{
    /**
     * Display the Central Hospital LCD Token Display screen.
     */
    public function index()
    {
        $profile = AccountProfile::current();
        $today = now()->toDateString();

        $activeTokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->whereIn('status', ['called', 'in_consultation'])
            ->orderBy('called_at', 'desc')
            ->take(4)
            ->get();

        $waitingTokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->take(6)
            ->get();

        return view('display.token-display', compact('profile', 'activeTokens', 'waitingTokens', 'today'));
    }

    /**
     * Polling JSON API endpoint for real-time LCD synchronization over local LAN.
     */
    public function liveData(Request $request): JsonResponse
    {
        $profile = AccountProfile::current();
        $today = now()->toDateString();

        // Active tokens currently called or consulting
        $activeTokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->whereIn('status', ['called', 'in_consultation'])
            ->orderBy('called_at', 'desc')
            ->take(4)
            ->get()
            ->map(function ($token) {
                // Get latest call log id for this token to enable audio deduplication
                $latestLog = TokenCallLog::where('patient_token_id', $token->id)
                    ->whereIn('action', ['call', 'recall'])
                    ->latest('id')
                    ->first();

                return [
                    'id'                     => $token->id,
                    'uuid'                   => $token->uuid,
                    'token_number'           => $token->token_number,
                    'queue_sequence'         => $token->queue_sequence ?? $token->token_number,
                    'formatted_token_number' => $token->formatted_token_number,
                    'prefix'                 => $token->token_prefix,
                    'status'                 => $token->status,
                    'doctor_name'            => $token->doctor ? 'Dr. ' . $token->doctor->name : 'Attending Doctor',
                    'department'             => $token->doctor?->specialization ?? 'OPD Clinic',
                    'room_number'            => $token->room_number ?? $token->doctor?->room_number ?? 'OPD Room',
                    'call_count'             => $token->call_count ?? 1,
                    'called_at'              => $token->called_at?->format('h:i A'),
                    'called_at_timestamp'    => $token->called_at?->timestamp,
                    'latest_call_log_id'     => $latestLog?->id ?? ($token->id . '_' . ($token->called_at?->timestamp ?? 0)),
                ];
            });

        // Next waiting tokens in sequence
        $waitingTokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->take(6)
            ->get()
            ->map(function ($token) {
                return [
                    'id'                     => $token->id,
                    'token_number'           => $token->token_number,
                    'queue_sequence'         => $token->queue_sequence ?? $token->token_number,
                    'formatted_token_number' => $token->formatted_token_number,
                    'doctor_name'            => $token->doctor ? 'Dr. ' . $token->doctor->name : 'Attending Doctor',
                    'department'             => $token->doctor?->specialization ?? 'General OPD',
                    'room_number'            => $token->room_number ?? $token->doctor?->room_number ?? 'OPD Room',
                    'issued_at'              => format_app_datetime($token->created_at, 'h:i A'),
                ];
            });

        // Latest announcement event if any
        $latestAnnouncement = TokenCallLog::with('patientToken.doctor')
            ->whereIn('action', ['call', 'recall'])
            ->whereDate('called_at', $today)
            ->latest('id')
            ->first();

        $announcementPayload = null;
        if ($latestAnnouncement && $latestAnnouncement->patientToken) {
            $t = $latestAnnouncement->patientToken;
            $announcementPayload = [
                'log_id'                 => $latestAnnouncement->id,
                'token_id'               => $t->id,
                'token_number'           => $t->token_number,
                'queue_sequence'         => $t->queue_sequence ?? $t->token_number,
                'formatted_token_number' => $t->formatted_token_number,
                'doctor_name'            => $t->doctor ? 'Dr. ' . $t->doctor->name : 'Attending Doctor',
                'room_number'            => $latestAnnouncement->room_number ?? $t->room_number ?? $t->doctor?->room_number ?? 'Consultation Room',
                'action'                 => $latestAnnouncement->action,
                'timestamp'              => $latestAnnouncement->called_at?->timestamp,
            ];
        }

        return response()->json([
            'status'              => 'success',
            'server_time'         => now()->format('h:i:s A'),
            'server_date'         => now()->format('l, d M Y'),
            'active_tokens'       => $activeTokens,
            'waiting_tokens'      => $waitingTokens,
            'latest_announcement' => $announcementPayload,
            'total_waiting'       => PatientToken::where('token_date', $today)->where('status', 'waiting')->count(),
            'total_completed'     => PatientToken::where('token_date', $today)->where('status', 'completed')->count(),
            'settings'            => [
                'voice_announcement_enabled' => (bool) ($profile->voice_announcement_enabled ?? true),
                'voice_announcement_language'=> $profile->voice_announcement_language ?? 'both', // 'en', 'ur', 'both'
                'voice_repeat_count'         => (int) ($profile->voice_repeat_count ?? 1),
                'voice_volume'               => (int) ($profile->voice_volume ?? 100),
                'lcd_refresh_interval_sec'   => (int) ($profile->lcd_refresh_interval_sec ?? 3),
                'ticker_message'             => $profile->lcd_message_ticker ?: 'Welcome to Healthcare OPD Clinic • Please observe queue discipline and wait for your token number announcement.',
            ],
        ]);
    }
}

