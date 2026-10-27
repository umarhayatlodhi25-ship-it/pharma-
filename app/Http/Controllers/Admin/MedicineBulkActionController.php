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
     * Preview affected products for bulk deletion.
     */
    public function preview(Request $request, ProductBulkDeletionService $deletionService): JsonResponse
    {
        $user = $request->user();
        if (!$user || (!$user->isAdmin() && $user->role !== 'admin')) {
            abort(403, 'Unauthorized: Administrator access required.');
        }

        $stats = $deletionService->getPreviewStats();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
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
