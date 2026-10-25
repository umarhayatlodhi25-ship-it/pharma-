<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class EarningsController extends Controller
{
    /**
     * Display earnings overview for the authenticated doctor based strictly on ledger entries.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = $request->get('doctor') ?? auth()->user()->doctor;

        $tz = config('app.display_timezone', config('app.timezone', 'Asia/Karachi'));
        $today = now($tz)->toDateString();
        $startOfMonth = now($tz)->startOfMonth()->toDateString();
        $endOfMonth = now($tz)->endOfMonth()->toDateString();

        // 1. Overall Financial Totals (computed strictly from doctor ledgers)
        $grossCollection = $doctor->gross_collection;
        $totalEarned = (float) $doctor->total_earned;
        $totalPaid = (float) $doctor->total_paid;
        $currentPayable = (float) $doctor->current_payable;

        // 2. Period earnings
        $todayEarned = (float) $doctor->ledgers()
            ->where('entry_date', $today)
            ->where('transaction_type', 'credit')
            ->sum('amount');

        $monthEarned = (float) $doctor->ledgers()
            ->whereBetween('entry_date', [$startOfMonth, $endOfMonth])
            ->where('transaction_type', 'credit')
            ->sum('amount');

        // 3. Consultation Volume Breakdown (Authoritative matching Admin Doctor Ledger)
        $totalTokens = $doctor->tokens()->count();
        $totalLifetimeVisits = $doctor->tokens()->where('status', '!=', 'cancelled')->count();
        $completedVisits = $doctor->tokens()->where('status', 'completed')->count();
        $paidTokens = $doctor->tokens()->where('payment_type', 'paid')->count();
        $freeTokens = $doctor->tokens()->where('payment_type', 'free')->count();

        // 4. Recent Settlement Payouts (Reading directly from doctor_settlements)
        $settlements = $doctor->settlements()
            ->latest('settlement_date')
            ->latest('id')
            ->take(15)
            ->get();

        // 5. Recent Consultation Revenue Credits
        $recentCredits = $doctor->ledgers()
            ->where('transaction_type', 'credit')
            ->with(['bill.patient', 'bill.token'])
            ->latest('entry_date')
            ->latest('id')
            ->take(15)
            ->get();

        return view('doctor-portal.earnings.index', compact(
            'doctor',
            'grossCollection',
            'totalEarned',
            'totalPaid',
            'currentPayable',
            'todayEarned',
            'monthEarned',
            'totalTokens',
            'totalLifetimeVisits',
            'completedVisits',
            'paidTokens',
            'freeTokens',
            'settlements',
            'recentCredits'
        ));
    }
}
