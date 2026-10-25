<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the doctor portal dashboard with real clinical and financial statistics
     * matching the authoritative Admin Doctor Ledger.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = $request->get('doctor') ?? auth()->user()->doctor;

        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Karachi'));
        $today = now($tz)->toDateString();

        // 1. Today's OPD Queue Metrics (Scoped strictly to configured application timezone)
        $todayTokensCount = $doctor->tokens()->where('token_date', $today)->count();
        $waitingTokensCount = $doctor->tokens()->where('token_date', $today)->where('status', 'waiting')->count();
        $inConsultationCount = $doctor->tokens()->where('token_date', $today)->where('status', 'in_consultation')->count();
        $completedTokensCount = $doctor->tokens()->where('token_date', $today)->where('status', 'completed')->count();

        // 2. Lifetime Volume & Visit Reconciliation (matching Admin Doctor Ledger)
        $totalLifetimeTokens = $doctor->tokens()->count();
        $totalLifetimeVisits = $doctor->tokens()->where('status', '!=', 'cancelled')->count();
        $totalCompletedVisits = $doctor->tokens()->where('status', 'completed')->count();

        // 3. Clinical Patients & Prescriptions
        $authorizedPatientsCount = $doctor->tokens()->distinct('patient_id')->count('patient_id');
        $prescriptionsCount = $doctor->prescriptions()->count();

        // 4. Financial Practice Summary (Authoritative Single Source of Truth)
        $grossCollection = $doctor->gross_collection;
        $totalEarned = (float) $doctor->total_earned;
        $totalPaid = (float) $doctor->total_paid;
        $currentPayable = (float) $doctor->current_payable;

        $todayEarned = (float) $doctor->ledgers()
            ->where('entry_date', $today)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        // 5. Recent OPD Consultations (strictly scoped to this authenticated doctor)
        $recentTokens = $doctor->tokens()
            ->with(['patient', 'prescription'])
            ->orderBy('token_date', 'desc')
            ->orderBy('token_number', 'desc')
            ->orderBy('id', 'desc')
            ->take(10)
            ->get();

        return view('doctor-portal.dashboard', compact(
            'doctor',
            'today',
            'todayTokensCount',
            'waitingTokensCount',
            'inConsultationCount',
            'completedTokensCount',
            'totalLifetimeTokens',
            'totalLifetimeVisits',
            'totalCompletedVisits',
            'authorizedPatientsCount',
            'prescriptionsCount',
            'grossCollection',
            'totalEarned',
            'totalPaid',
            'currentPayable',
            'todayEarned',
            'recentTokens'
        ));
    }
}
