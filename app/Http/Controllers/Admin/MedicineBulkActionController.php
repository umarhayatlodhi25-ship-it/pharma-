<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductBulkDeletionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MedicineBulkActionController extends Controller
{
    /**
     * Preview affected products for bulk deletion (supports all products or specific selected IDs).
     */
    public function preview(Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        if ($request->has('medicine_ids') && is_array($request->input('medicine_ids'))) {
            $stats = $deletionService->getPreviewStatsForIds($request->input('medicine_ids'));
        } else {
            $stats = $deletionService->getPreviewStats();
        }

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Initialize a tracked batch bulk deletion job (for selected products or all products).
     */
    public function initJob(Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $type = $request->input('type', 'selected');

        if ($type === 'all') {
            $request->validate([
                'confirmation' => [
                    'required',
                    'string',
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (trim((string) $value) !== 'DELETE ALL PRODUCTS') {
                            $fail('You must type DELETE ALL PRODUCTS exactly to confirm this bulk operation.');
                        }
                    },
                ],
            ]);
            $target = 'all';
        } else {
            $request->validate([
                'medicine_ids' => 'required|array|min:1',
            ]);
            $target = $request->input('medicine_ids');
        }

        $force = (bool) $request->input('force', false);
        $job = $deletionService->initJob($user, $target, $type, $request->ip(), $request->userAgent(), $force);

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Process next safe batch in isolated database transaction.
     */
    public function processBatch(Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $request->validate([
            'job_id' => 'required|string',
        ]);

        $batchSize = $request->input('batch_size') ? (int) $request->input('batch_size') : null;
        $job = $deletionService->processNextBatch($request->input('job_id'), $batchSize);

        return response()->json([
            'success' => in_array($job['status'] ?? '', ['processing', 'completed', 'partial']),
            'data' => $job,
        ]);
    }

    /**
     * Query live job status and telemetry.
     */
    public function jobStatus(string $jobId, Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $job = $deletionService->getJobStatus($jobId);
        if (!$job) {
            return response()->json(['success' => false, 'message' => 'Job not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $job,
        ]);
    }

    /**
     * Retry failed products only.
     */
    public function retryJob(string $jobId, Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        try {
            $job = $deletionService->retryFailedJob($jobId, $user);
            return response()->json(['success' => true, 'data' => $job]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Cancel an active job safely.
     */
    public function cancelJob(string $jobId, Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $job = $deletionService->cancelJob($jobId, $user);
        return response()->json(['success' => true, 'data' => $job]);
    }

    /**
     * Dedicated POST/DELETE endpoint to delete or archive all products.
     */
    public function deleteAll(Request $request, ProductBulkDeletionService $deletionService): JsonResponse|RedirectResponse
    {
        // 1. Strict Server-Side Authorization Check
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Only authorized administrators are permitted to execute bulk product deletion.');
        }

        // 2. Server-side Validation of Confirmation Phrase
        $request->validate([
            'confirmation' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (trim((string) $value) !== 'DELETE ALL PRODUCTS') {
                        $fail('You must type DELETE ALL PRODUCTS exactly to confirm this bulk operation.');
                    }
                },
            ],
        ], [
            'confirmation.required' => 'Please type DELETE ALL PRODUCTS to confirm.',
        ]);

        // 3. Execute Service within Atomic Transaction and Row-Locking
        $result = $deletionService->execute(
            $user,
            $request->ip(),
            $request->userAgent()
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => $result['status'] === 'success',
                'data' => $result,
            ]);
        }

        $flashKey = ($result['status'] === 'success') ? 'message' : 'warning';
        return redirect('/medicines')->with($flashKey, $result['message']);
    }
}
