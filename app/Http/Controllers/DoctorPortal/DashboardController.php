<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the doctor portal dashboard with real clinical and financial statistics.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;
        $today = now()->toDateString();

        // OPD Queue metrics for today
        $todayTokensCount = $doctor->tokens()->where('token_date', $today)->count();
        $waitingTokensCount = $doctor->tokens()->where('token_date', $today)->where('status', 'waiting')->count();
        $inConsultationCount = $doctor->tokens()->where('token_date', $today)->where('status', 'in_consultation')->count();
        $completedTokensCount = $doctor->tokens()->where('token_date', $today)->where('status', 'completed')->count();

        // Authorized Patients count (distinct patients examined by this doctor)
        $authorizedPatientsCount = $doctor->tokens()->distinct('patient_id')->count('patient_id');

        // Total prescriptions written
        $prescriptionsCount = $doctor->prescriptions()->count();

        // Recent OPD consultations for this doctor
        $recentTokens = $doctor->tokens()
            ->with(['patient', 'prescription'])
            ->orderBy('token_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(8)
            ->get();

        // Financial Overview (strictly computed from doctor ledgers)
        $totalEarned = (float) $doctor->total_earned;
        $totalPaid = (float) $doctor->total_paid;
        $currentPayable = (float) $doctor->current_payable;

        $todayEarned = (float) $doctor->ledgers()
            ->where('entry_date', $today)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        return view('doctor-portal.dashboard', compact(
            'doctor',
            'today',
            'todayTokensCount',
            'waitingTokensCount',
            'inConsultationCount',
            'completedTokensCount',
            'authorizedPatientsCount',
            'prescriptionsCount',
            'recentTokens',
            'totalEarned',
            'totalPaid',
            'currentPayable',
            'todayEarned'
        ));
    }
}
