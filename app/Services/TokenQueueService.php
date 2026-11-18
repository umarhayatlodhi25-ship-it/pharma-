<?php

namespace App\Services;

use App\Models\AccountProfile;
use App\Models\Doctor;
use App\Models\PatientToken;
use App\Models\SyncOutbox;
use App\Models\TokenCallLog;
use App\Models\TokenCounter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TokenQueueService
{
    /**
     * Get or initialize today's atomic sequence counter for a queue scope.
     * Supports:
     * - 'doctor_wise': each doctor has separate 1..N daily sequence
     * - 'department_wise': each department/specialization has separate 1..N daily sequence
     * - 'hospital_wide': single daily 1..N sequence across the hospital
     *
     * @param string $tokenDate
     * @param int|null $doctorId
     * @param int|null $departmentId
     * @param string|null $prefix
     * @return array{queue_key: string, queue_sequence: int, token_number: int, prefix: string}
     */
    public function allocateTokenNumbers(
        string $tokenDate,
        ?int $doctorId = null,
        ?int $departmentId = null,
        ?string $prefix = null
    ): array {
        $profile = AccountProfile::current();
        $scope = strtolower($profile->queue_scope ?? 'doctor_wise');
        $defaultPrefix = $profile->token_prefix !== null ? trim($profile->token_prefix) : '';
        $effectivePrefix = $prefix !== null ? trim($prefix) : $defaultPrefix;

        // Determine queue key based on scope
        if ($scope === 'doctor_wise' && $doctorId) {
            $queueKey = "doc_{$doctorId}_{$tokenDate}";
        } elseif ($scope === 'department_wise' && $departmentId) {
            $queueKey = "dept_{$departmentId}_{$tokenDate}";
        } elseif ($scope === 'department_wise' && $doctorId) {
            $doc = Doctor::find($doctorId);
            $specKey = $doc && !empty($doc->specialization) ? Str::slug($doc->specialization) : 'general';
            $queueKey = "spec_{$specKey}_{$tokenDate}";
        } else {
            $queueKey = "hosp_global_{$tokenDate}";
        }

        // Lock & increment sequence in token_counters
        $counter = TokenCounter::where('queue_key', $queueKey)
            ->where('token_date', $tokenDate)
            ->lockForUpdate()
            ->first();

        $startNumber = (int) $profile->token_start_number > 0 ? (int) $profile->token_start_number : 1;

        if (!$counter) {
            $counter = TokenCounter::create([
                'queue_key'      => $queueKey,
                'token_date'     => $tokenDate,
                'current_number' => $startNumber,
            ]);
            $queueSequence = $startNumber;
        } else {
            $counter->current_number += 1;
            $counter->save();
            $queueSequence = $counter->current_number;
        }

        // Global sequence for backwards compatibility with existing patient_tokens.token_number
        $globalCounter = TokenCounter::where('queue_key', "global_{$tokenDate}")
            ->where('token_date', $tokenDate)
            ->lockForUpdate()
            ->first();

        if (!$globalCounter) {
            $latestTokenNumber = PatientToken::where('token_date', $tokenDate)->max('token_number');
            $currentGlobal = $latestTokenNumber ? (intval($latestTokenNumber) + 1) : $startNumber;
            $globalCounter = TokenCounter::create([
                'queue_key'      => "global_{$tokenDate}",
                'token_date'     => $tokenDate,
                'current_number' => $currentGlobal,
            ]);
            $globalTokenNumber = $currentGlobal;
        } else {
            $globalCounter->current_number += 1;
            $globalCounter->save();
            $globalTokenNumber = $globalCounter->current_number;
        }

        return [
            'queue_key'      => $queueKey,
            'queue_sequence' => $queueSequence,
            'token_number'   => $globalTokenNumber,
            'prefix'         => $effectivePrefix,
        ];
    }

    /**
     * Call next waiting token atomically for the given doctor.
     */
    public function callNext(int $doctorId, ?string $room = null, ?int $userId = null): ?PatientToken
    {
        return DB::transaction(function () use ($doctorId, $room, $userId) {
            $doctor = Doctor::findOrFail($doctorId);
            $today = now()->toDateString();
            $effectiveRoom = $room ?? $doctor->room_number;

            $token = PatientToken::where('doctor_id', $doctorId)
                ->where('token_date', $today)
                ->where('status', 'waiting')
                ->orderBy('token_number', 'asc')
                ->lockForUpdate()
                ->first();

            if (!$token) {
                return null;
            }

            $fromStatus = $token->status;
            $token->status = 'called';
            $token->called_at = now();
            $token->room_number = $effectiveRoom;
            $token->call_count = (int) $token->call_count + 1;
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'call',
                fromStatus: $fromStatus,
                toStatus: 'called',
                doctorId: $doctorId,
                roomNumber: $effectiveRoom,
                userId: $userId ?? auth()->id(),
                notes: "Called Token #{$token->formatted_token_number} into active queue."
            );

            return $token;
        });
    }

    /**
     * Call a specific token by ID or recall an existing token.
     */
    public function callToken(PatientToken $token, ?string $room = null, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $room, $userId) {
            $fromStatus = $token->status;
            $isRecall = ($fromStatus === 'called');
            $action = $isRecall ? 'recall' : 'call';

            $token->status = 'called';
            $token->called_at = now();
            if ($room !== null) {
                $token->room_number = $room;
            } elseif (empty($token->room_number) && $token->doctor) {
                $token->room_number = $token->doctor->room_number;
            }
            $token->call_count = (int) $token->call_count + 1;
            $token->save();

            $this->logCallAction(
                token: $token,
                action: $action,
                fromStatus: $fromStatus,
                toStatus: 'called',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: $isRecall
                    ? "Recalled Token #{$token->formatted_token_number} (call count: {$token->call_count})."
                    : "Called Token #{$token->formatted_token_number}."
            );

            return $token;
        });
    }

    /**
     * Start consultation for the token (serving status).
     */
    public function startConsultation(PatientToken $token, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $userId) {
            $fromStatus = $token->status;
            // Existing app uses 'in_consultation' or 'called' interchangeably
            $token->status = 'in_consultation';
            if (!$token->started_at) {
                $token->started_at = now();
            }
            if (!$token->called_at) {
                $token->called_at = now();
            }
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'start_consultation',
                fromStatus: $fromStatus,
                toStatus: 'in_consultation',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Consultation started for Token #{$token->formatted_token_number}."
            );

            return $token;
        });
    }

    /**
     * Complete consultation for the token.
     */
    public function completeConsultation(PatientToken $token, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $userId) {
            $fromStatus = $token->status;
            $token->status = 'completed';
            $token->completed_at = now();
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'complete',
                fromStatus: $fromStatus,
                toStatus: 'completed',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Completed consultation for Token #{$token->formatted_token_number}."
            );

            return $token;
        });
    }

    /**
     * Skip token and place in skipped state.
     */
    public function skipToken(PatientToken $token, ?string $reason = null, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $reason, $userId) {
            $fromStatus = $token->status;
            $token->status = 'skipped';
            $token->skipped_at = now();
            if ($reason) {
                $token->notes = trim(($token->notes ? $token->notes . ' | ' : '') . 'Skipped: ' . $reason);
            }
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'skip',
                fromStatus: $fromStatus,
                toStatus: 'skipped',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Skipped Token #{$token->formatted_token_number}: " . ($reason ?? 'No reason provided')
            );

            return $token;
        });
    }

    /**
     * Put token on hold.
     */
    public function holdToken(PatientToken $token, ?string $reason = null, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $reason, $userId) {
            $fromStatus = $token->status;
            $token->status = 'hold';
            $token->held_at = now();
            if ($reason) {
                $token->notes = trim(($token->notes ? $token->notes . ' | ' : '') . 'On Hold: ' . $reason);
            }
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'hold',
                fromStatus: $fromStatus,
                toStatus: 'hold',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Put Token #{$token->formatted_token_number} on Hold: " . ($reason ?? 'Pending investigation/tests')
            );

            return $token;
        });
    }

    /**
     * Resume a skipped or held token back into waiting or active queue.
     */
    public function resumeToken(PatientToken $token, ?int $userId = null): PatientToken
    {
        return DB::transaction(function () use ($token, $userId) {
            $fromStatus = $token->status;
            $token->status = 'waiting';
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'resume',
                fromStatus: $fromStatus,
                toStatus: 'waiting',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Resumed Token #{$token->formatted_token_number} back into waiting queue."
            );

            return $token;
        });
    }

    /**
     * Cancel token with mandatory audit reason.
     */
    public function cancelToken(PatientToken $token, string $reason, ?int $userId = null): PatientToken
    {
        if (empty(trim($reason))) {
            throw new InvalidArgumentException('A cancellation reason is required.');
        }

        return DB::transaction(function () use ($token, $reason, $userId) {
            $fromStatus = $token->status;
            $token->status = 'cancelled';
            $token->cancelled_at = now();
            $token->cancellation_reason = trim($reason);
            $token->notes = trim(($token->notes ? $token->notes . ' | ' : '') . 'Cancelled: ' . $reason);
            $token->save();

            $this->logCallAction(
                token: $token,
                action: 'cancel',
                fromStatus: $fromStatus,
                toStatus: 'cancelled',
                doctorId: $token->doctor_id,
                roomNumber: $token->room_number,
                userId: $userId ?? auth()->id(),
                notes: "Cancelled Token #{$token->formatted_token_number}: {$reason}"
            );

            return $token;
        });
    }

    /**
     * Audit log helper and outbox sync event dispatcher.
     */
    protected function logCallAction(
        PatientToken $token,
        string $action,
        ?string $fromStatus,
        string $toStatus,
        ?int $doctorId,
        ?string $roomNumber,
        ?int $userId,
        ?string $notes = null
    ): TokenCallLog {
        $log = TokenCallLog::create([
            'patient_token_id' => $token->id,
            'action'           => $action,
            'from_status'      => $fromStatus,
            'to_status'        => $toStatus,
            'doctor_id'        => $doctorId,
            'room_number'      => $roomNumber,
            'user_id'          => $userId,
            'notes'            => $notes,
            'called_at'        => now(),
        ]);

        // Non-blocking Outbox queue for offline-first sync
        try {
            SyncOutbox::create([
                'uuid'           => (string) Str::uuid(),
                'event_type'     => 'token_status_changed',
                'aggregate_type' => 'PatientToken',
                'aggregate_id'   => (string) ($token->uuid ?? $token->id),
                'payload'        => [
                    'token_id'         => $token->id,
                    'uuid'             => $token->uuid,
                    'token_number'     => $token->token_number,
                    'formatted_number' => $token->formatted_token_number,
                    'status'           => $toStatus,
                    'action'           => $action,
                    'called_at'        => $token->called_at?->toIso8601String(),
                    'completed_at'     => $token->completed_at?->toIso8601String(),
                    'room_number'      => $roomNumber,
                    'doctor_id'        => $doctorId,
                    'call_count'       => $token->call_count,
                    'timestamp'        => now()->toIso8601String(),
                ],
                'status'         => 'pending',
                'attempts'       => 0,
            ]);
        } catch (\Throwable $e) {
            // Outbox error must never abort local operation
            \Illuminate\Support\Facades\Log::warning('Outbox queue failed: ' . $e->getMessage());
        }

        return $log;
    }
}
