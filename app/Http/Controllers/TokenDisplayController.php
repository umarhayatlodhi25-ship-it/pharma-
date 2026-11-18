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

        // 1. Current Active Token
        $activeToken = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->whereIn('status', ['called', 'in_consultation'])
            ->orderByDesc('called_at')
            ->first();

        // 2. Upcoming Waiting Tokens (Next 5)
        $waitingTokens = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->take(5)
            ->get();

        // 3. Previously Called Tokens (Latest 8, excluding active)
        $previousQuery = PatientToken::with(['doctor'])
            ->where('token_date', $today)
            ->where(function ($q) {
                $q->whereNotNull('called_at')
                  ->orWhereIn('status', ['completed', 'skipped', 'hold']);
            });

        if ($activeToken) {
            $previousQuery->where('id', '!=', $activeToken->id);
        }

        $previousTokens = $previousQuery
            ->orderByRaw("COALESCE(completed_at, called_at, updated_at) DESC")
            ->take(8)
            ->get();

        return view('display.token-display', compact(
            'profile',
            'activeToken',
            'waitingTokens',
            'previousTokens',
            'today'
        ));
    }

    /**
     * Polling JSON API endpoint for real-time LCD synchronization over local LAN.
     */
    public function liveData(Request $request): JsonResponse
    {
        $profile = AccountProfile::current();
        $today = now()->toDateString();

        // 1. Current Active Token
        $activeToken = PatientToken::with(['patient', 'doctor'])
            ->where('token_date', $today)
            ->whereIn('status', ['called', 'in_consultation'])
            ->orderByDesc('called_at')
            ->first();

        // Helper to format doctor name cleanly without duplicate "Dr. Dr."
        $formatDoctorName = function ($doctor, $fallback = 'OPD Doctor') {
            if (!$doctor || empty($doctor->name)) {
                return $fallback;
            }
            $name = trim($doctor->name);
            return preg_match('/^dr\.?\s+/i', $name) ? $name : 'Dr. ' . $name;
        };

        $activeTokenData = null;
        if ($activeToken) {
            $latestLog = TokenCallLog::where('patient_token_id', $activeToken->id)
                ->whereIn('action', ['call', 'recall', 'start_consultation'])
                ->latest('id')
                ->first();

            $activeTokenData = [
                'id'                     => $activeToken->id,
                'token_number'           => $activeToken->token_number,
                'queue_sequence'         => $activeToken->queue_sequence ?? $activeToken->token_number,
                'formatted_token_number' => $activeToken->formatted_token_number,
                'prefix'                 => $activeToken->token_prefix,
                'status'                 => $activeToken->status,
                'doctor_name'            => $formatDoctorName($activeToken->doctor, 'Attending Doctor'),
                'department'             => $activeToken->doctor?->specialization ?? 'General OPD',
                'room_number'            => $activeToken->room_number ?? $activeToken->doctor?->room_number ?? 'Consultation Desk',
                'call_count'             => (int) ($activeToken->call_count ?? 1),
                'called_at'              => $activeToken->called_at?->format('h:i A'),
                'called_at_timestamp'    => $activeToken->called_at?->timestamp,
                'is_recall'              => ($latestLog && $latestLog->action === 'recall') || ((int) $activeToken->call_count > 1),
                'latest_log_id'          => $latestLog?->id,
            ];
        }

        // 2. Upcoming Waiting Tokens (Next 5)
        $waitingTokens = PatientToken::with(['doctor'])
            ->where('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number', 'asc')
            ->take(5)
            ->get()
            ->map(function ($token) use ($formatDoctorName) {
                return [
                    'id'                     => $token->id,
                    'token_number'           => $token->token_number,
                    'queue_sequence'         => $token->queue_sequence ?? $token->token_number,
                    'formatted_token_number' => $token->formatted_token_number,
                    'doctor_name'            => $formatDoctorName($token->doctor, 'OPD Doctor'),
                    'department'             => $token->doctor?->specialization ?? 'General OPD',
                    'room_number'            => $token->room_number ?? $token->doctor?->room_number ?? 'OPD Room',
                    'status'                 => 'waiting',
                    'issued_at'              => format_app_datetime($token->created_at, 'h:i A'),
                ];
            });

        // 3. Previously Called Tokens (Latest 8, excluding active)
        $previousQuery = PatientToken::with(['doctor'])
            ->where('token_date', $today)
            ->where(function ($q) {
                $q->whereNotNull('called_at')
                  ->orWhereIn('status', ['completed', 'skipped', 'hold']);
            });

        if ($activeToken) {
            $previousQuery->where('id', '!=', $activeToken->id);
        }

        $previousTokens = $previousQuery
            ->orderByRaw("COALESCE(completed_at, called_at, updated_at) DESC")
            ->take(8)
            ->get()
            ->map(function ($token) use ($formatDoctorName) {
                $time = $token->completed_at ?? $token->called_at ?? $token->updated_at;
                return [
                    'id'                     => $token->id,
                    'token_number'           => $token->token_number,
                    'queue_sequence'         => $token->queue_sequence ?? $token->token_number,
                    'formatted_token_number' => $token->formatted_token_number,
                    'doctor_name'            => $formatDoctorName($token->doctor, 'OPD Doctor'),
                    'department'             => $token->doctor?->specialization ?? 'General OPD',
                    'room_number'            => $token->room_number ?? $token->doctor?->room_number ?? 'OPD Room',
                    'status'                 => $token->status,
                    'time_formatted'         => $time ? format_app_datetime($time, 'h:i A') : '—',
                    'call_count'             => (int) ($token->call_count ?? 1),
                ];
            });

        // 4. Latest Call/Recall Event for Voice Announcement Trigger
        $latestCallLog = TokenCallLog::with('patientToken.doctor')
            ->whereIn('action', ['call', 'recall'])
            ->whereDate('called_at', $today)
            ->latest('id')
            ->first();

        $announcementEvent = null;
        if ($latestCallLog && $latestCallLog->patientToken) {
            $pt = $latestCallLog->patientToken;
            if ($pt->status !== 'cancelled') {
                $announcementEvent = [
                    'log_id'                 => $latestCallLog->id,
                    'token_id'               => $pt->id,
                    'token_number'           => $pt->token_number,
                    'queue_sequence'         => $pt->queue_sequence ?? $pt->token_number,
                    'formatted_token_number' => $pt->formatted_token_number,
                    'prefix'                 => $pt->token_prefix,
                    'doctor_name'            => $formatDoctorName($pt->doctor, 'Doctor'),
                    'room_number'            => $latestCallLog->room_number ?? $pt->room_number ?? $pt->doctor?->room_number ?? 'Consultation Room',
                    'action'                 => $latestCallLog->action,
                    'is_recall'              => ($latestCallLog->action === 'recall') || ((int) ($pt->call_count ?? 1) > 1),
                    'call_count'             => (int) ($pt->call_count ?? 1),
                    'timestamp'              => $latestCallLog->called_at?->timestamp,
                ];
            }
        }

        return response()->json([
            'status'              => 'success',
            'server_time'         => now()->format('h:i:s A'),
            'server_date'         => now()->format('l, d M Y'),
            'active_token'        => $activeTokenData,
            'active_tokens'       => $activeTokenData ? [$activeTokenData] : [],
            'waiting_tokens'      => $waitingTokens,
            'previous_tokens'     => $previousTokens,
            'latest_announcement' => $announcementEvent,
            'total_waiting'       => PatientToken::where('token_date', $today)->where('status', 'waiting')->count(),
            'total_completed'     => PatientToken::where('token_date', $today)->where('status', 'completed')->count(),
            'settings'            => [
                'voice_announcement_enabled' => (bool) ($profile->voice_announcement_enabled ?? true),
                'voice_announcement_language'=> $profile->voice_announcement_language ?? 'both',
                'voice_repeat_count'         => (int) ($profile->voice_repeat_count ?? 1),
                'voice_volume'               => (int) ($profile->voice_volume ?? 100),
                'lcd_refresh_interval_sec'   => (int) ($profile->lcd_refresh_interval_sec ?? 3),
                'ticker_message'             => $profile->lcd_message_ticker ?: 'Please wait for your token and proceed to your designated doctor room when announced. • مہربانی فرما کر اپنے ٹوکن کا انتظار کریں اور اعلان پر متعلقہ ڈاکٹر کے کمرے میں تشریف لے جائیں۔',
            ],
        ]);
    }
}
