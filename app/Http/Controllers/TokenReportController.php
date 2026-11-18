<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\PatientToken;
use App\Models\SyncOutbox;
use App\Services\TokenSyncService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TokenReportController extends Controller
{
    /**
     * Display OPD Token and Queue Management Analytical Reports.
     */
    public function index(Request $request, TokenSyncService $syncService)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $doctorId = $request->input('doctor_id');

        $query = PatientToken::with(['doctor', 'patient'])
            ->whereBetween('token_date', [$startDate, $endDate]);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        $allTokens = $query->get();

        $totalIssued = $allTokens->count();
        $waitingCount = $allTokens->where('status', 'waiting')->count();
        $calledCount = $allTokens->whereIn('status', ['called', 'in_consultation'])->count();
        $completedCount = $allTokens->where('status', 'completed')->count();
        $cancelledCount = $allTokens->where('status', 'cancelled')->count();
        $holdCount = $allTokens->where('status', 'hold')->count();
        $skippedCount = $allTokens->where('status', 'skipped')->count();

        // Calculate Average Wait Time (minutes): from created_at to called_at
        $calledTokens = $allTokens->filter(fn($t) => $t->called_at && $t->created_at);
        $totalWaitMinutes = 0;
        foreach ($calledTokens as $ct) {
            $diff = Carbon::parse($ct->created_at)->diffInMinutes(Carbon::parse($ct->called_at));
            $totalWaitMinutes += max(0, $diff);
        }
        $avgWaitMinutes = $calledTokens->count() > 0 ? round($totalWaitMinutes / $calledTokens->count(), 1) : 0;

        // Calculate Average Consultation Time (minutes): from called_at/started_at to completed_at
        $completedTokens = $allTokens->filter(fn($t) => $t->status === 'completed' && $t->completed_at && ($t->started_at ?? $t->called_at));
        $totalConsultMinutes = 0;
        foreach ($completedTokens as $cpt) {
            $start = $cpt->started_at ?? $cpt->called_at;
            $diff = Carbon::parse($start)->diffInMinutes(Carbon::parse($cpt->completed_at));
            $totalConsultMinutes += max(0, $diff);
        }
        $avgConsultMinutes = $completedTokens->count() > 0 ? round($totalConsultMinutes / $completedTokens->count(), 1) : 0;

        // Doctor-wise breakdown
        $doctors = Doctor::active()->orderBy('name')->get();
        $doctorStats = [];
        foreach ($doctors as $doc) {
            $docTokens = $allTokens->where('doctor_id', $doc->id);
            if ($docTokens->count() === 0 && $doctorId && $doctorId != $doc->id) {
                continue;
            }

            $docCalled = $docTokens->filter(fn($t) => $t->called_at && $t->created_at);
            $docTotalWait = 0;
            foreach ($docCalled as $dt) {
                $docTotalWait += max(0, Carbon::parse($dt->created_at)->diffInMinutes(Carbon::parse($dt->called_at)));
            }

            $docCompleted = $docTokens->filter(fn($t) => $t->status === 'completed' && $t->completed_at && ($t->started_at ?? $t->called_at));
            $docTotalConsult = 0;
            foreach ($docCompleted as $dt) {
                $start = $dt->started_at ?? $dt->called_at;
                $docTotalConsult += max(0, Carbon::parse($start)->diffInMinutes(Carbon::parse($dt->completed_at)));
            }

            $doctorStats[] = [
                'doctor'         => $doc,
                'total'          => $docTokens->count(),
                'completed'      => $docTokens->where('status', 'completed')->count(),
                'waiting'        => $docTokens->where('status', 'waiting')->count(),
                'cancelled'      => $docTokens->where('status', 'cancelled')->count(),
                'avg_wait_min'   => $docCalled->count() > 0 ? round($docTotalWait / $docCalled->count(), 1) : 0,
                'avg_consult_min'=> $docCompleted->count() > 0 ? round($docTotalConsult / $docCompleted->count(), 1) : 0,
            ];
        }

        // Sync Statistics
        $syncStats = $syncService->getSyncStats();

        return view('admin.reports.tokens', compact(
            'startDate',
            'endDate',
            'doctorId',
            'doctors',
            'totalIssued',
            'waitingCount',
            'calledCount',
            'completedCount',
            'cancelledCount',
            'holdCount',
            'skippedCount',
            'avgWaitMinutes',
            'avgConsultMinutes',
            'doctorStats',
            'syncStats'
        ));
    }

    /**
     * Trigger background synchronization manually from the web UI.
     */
    public function triggerSync(TokenSyncService $syncService)
    {
        $result = $syncService->syncPendingOutbox(150);

        if ($result['synced'] > 0) {
            return redirect()->back()->with('success', "Cloud synchronization completed: {$result['synced']} record(s) synced successfully.");
        } elseif (!empty($result['errors'])) {
            return redirect()->back()->with('warning', "Synchronization status: " . implode(' | ', $result['errors']));
        } else {
            return redirect()->back()->with('info', "All records are already up to date with cloud database.");
        }
    }
}

