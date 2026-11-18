<?php

namespace App\Services;

use App\Models\PatientToken;
use App\Models\SyncLog;
use App\Models\SyncOutbox;
use App\Models\TokenCallLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TokenSyncService
{
    protected string $targetConnection = 'pgsql_prod';

    /**
     * Check if the cloud destination connection is reachable.
     */
    public function isCloudReachable(): bool
    {
        try {
            DB::connection($this->targetConnection)->select('SELECT 1');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Process a batch of pending synchronization records.
     *
     * @param int $batchSize
     * @return array{processed: int, synced: int, failed: int, errors: array}
     */
    public function syncPendingOutbox(int $batchSize = 100): array
    {
        $processed = 0;
        $synced = 0;
        $failed = 0;
        $errors = [];

        // If cloud is unreachable, return immediately without blocking local operations
        if (!$this->isCloudReachable()) {
            return [
                'processed' => 0,
                'synced'    => 0,
                'failed'    => 0,
                'errors'    => ['Cloud connection is unreachable or offline.'],
            ];
        }

        $items = SyncOutbox::where('status', 'pending')
            ->orderBy('id', 'asc')
            ->take($batchSize)
            ->get();

        foreach ($items as $item) {
            $processed++;
            $startTime = microtime(true);

            try {
                $this->syncOutboxItem($item);

                $execTimeMs = (int) round((microtime(true) - $startTime) * 1000);

                $item->status = 'synced';
                $item->synced_at = now();
                $item->save();

                SyncLog::create([
                    'sync_outbox_id'    => $item->id,
                    'event_type'        => $item->event_type,
                    'status'            => 'success',
                    'response_code'     => 200,
                    'response_body'     => 'Synced successfully to cloud PostgreSQL.',
                    'execution_time_ms' => $execTimeMs,
                    'created_at'        => now(),
                ]);

                $synced++;
            } catch (Throwable $e) {
                $execTimeMs = (int) round((microtime(true) - $startTime) * 1000);
                $failed++;
                $errorMsg = $e->getMessage();
                $errors[] = "Outbox #{$item->id}: {$errorMsg}";

                $item->attempts += 1;
                $item->last_error = $errorMsg;
                if ($item->attempts >= 5) {
                    $item->status = 'failed';
                }
                $item->save();

                SyncLog::create([
                    'sync_outbox_id'    => $item->id,
                    'event_type'        => $item->event_type,
                    'status'            => 'failed',
                    'response_code'     => 500,
                    'response_body'     => $errorMsg,
                    'execution_time_ms' => $execTimeMs,
                    'created_at'        => now(),
                ]);

                Log::error("Token sync outbox #{$item->id} error: {$errorMsg}");
            }
        }

        return [
            'processed' => $processed,
            'synced'    => $synced,
            'failed'    => $failed,
            'errors'    => $errors,
        ];
    }

    /**
     * Synchronize a single outbox item into the cloud database.
     */
    protected function syncOutboxItem(SyncOutbox $item): void
    {
        $payload = $item->payload;
        if (!is_array($payload)) {
            $payload = json_decode((string) $item->payload, true) ?: [];
        }

        $cloudDb = DB::connection($this->targetConnection);

        if (in_array($item->event_type, ['token_created', 'token_status_changed'])) {
            $tokenId = $payload['token_id'] ?? null;
            $uuid = $payload['uuid'] ?? $item->aggregate_id;

            $localToken = $tokenId ? PatientToken::find($tokenId) : PatientToken::where('uuid', $uuid)->first();
            if (!$localToken) {
                return;
            }

            // Upsert into Neon PostgreSQL patient_tokens
            $tokenAttributes = [
                'uuid'                => $localToken->uuid,
                'patient_id'          => $localToken->patient_id,
                'doctor_id'           => $localToken->doctor_id,
                'token_number'        => $localToken->token_number,
                'queue_sequence'      => $localToken->queue_sequence,
                'token_prefix'        => $localToken->token_prefix,
                'room_number'         => $localToken->room_number,
                'token_date'          => $localToken->token_date,
                'status'              => $localToken->status,
                'call_count'          => $localToken->call_count,
                'called_at'           => $localToken->called_at,
                'completed_at'        => $localToken->completed_at,
                'started_at'          => $localToken->started_at,
                'held_at'             => $localToken->held_at,
                'skipped_at'          => $localToken->skipped_at,
                'cancelled_at'        => $localToken->cancelled_at,
                'cancellation_reason' => $localToken->cancellation_reason,
                'payment_type'        => $localToken->payment_type,
                'consultation_fee'    => $localToken->consultation_fee,
                'charged_amount'      => $localToken->charged_amount,
                'notes'               => $localToken->notes,
                'updated_at'          => now(),
            ];

            // Check if exists in destination
            $existing = $cloudDb->table('patient_tokens')->where('uuid', $localToken->uuid)->first();
            if ($existing) {
                $cloudDb->table('patient_tokens')->where('uuid', $localToken->uuid)->update($tokenAttributes);
            } else {
                $tokenAttributes['created_at'] = $localToken->created_at ?? now();
                $cloudDb->table('patient_tokens')->insert($tokenAttributes);
            }

            // Sync call logs for this token
            $callLogs = TokenCallLog::where('patient_token_id', $localToken->id)->get();
            foreach ($callLogs as $log) {
                $existingLog = $cloudDb->table('token_call_logs')
                    ->where('patient_token_id', $log->patient_token_id)
                    ->where('called_at', $log->called_at)
                    ->where('action', $log->action)
                    ->first();

                if (!$existingLog) {
                    $cloudDb->table('token_call_logs')->insert([
                        'patient_token_id' => $log->patient_token_id,
                        'action'           => $log->action,
                        'from_status'      => $log->from_status,
                        'to_status'        => $log->to_status,
                        'doctor_id'        => $log->doctor_id,
                        'room_number'      => $log->room_number,
                        'user_id'          => $log->user_id,
                        'notes'            => $log->notes,
                        'called_at'        => $log->called_at,
                        'created_at'       => $log->created_at ?? now(),
                        'updated_at'       => $log->updated_at ?? now(),
                    ]);
                }
            }
        }
    }

    /**
     * Overall sync statistics.
     */
    public function getSyncStats(): array
    {
        return [
            'pending_count' => SyncOutbox::where('status', 'pending')->count(),
            'synced_count'  => SyncOutbox::where('status', 'synced')->count(),
            'failed_count'  => SyncOutbox::where('status', 'failed')->count(),
            'last_synced_at'=> SyncOutbox::where('status', 'synced')->latest('synced_at')->value('synced_at'),
            'cloud_online'  => $this->isCloudReachable(),
        ];
    }
}

