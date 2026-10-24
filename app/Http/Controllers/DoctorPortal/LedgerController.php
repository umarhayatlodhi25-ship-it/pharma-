<?php

namespace App\Http\Controllers\DoctorPortal;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    /**
     * Display the read-only financial ledger entries for the authenticated doctor.
     */
    public function index(Request $request)
    {
        /** @var Doctor $doctor */
        $doctor = auth()->user()->doctor;

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $type = $request->input('type', 'all');

        $query = $doctor->ledgers()->with(['bill.patient', 'settlement']);

        if (!empty($startDate)) {
            $query->whereDate('entry_date', '>=', $startDate);
        }

        if (!empty($endDate)) {
            $query->whereDate('entry_date', '<=', $endDate);
        }

        if ($type === 'credit') {
            $query->where('transaction_type', 'credit');
        } elseif ($type === 'debit') {
            $query->where('transaction_type', 'debit');
        }

        $ledgerEntries = $query->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Totals
        $totalCredit = (float) (clone $query)->where('transaction_type', 'credit')->sum('amount');
        $totalDebit = (float) (clone $query)->where('transaction_type', 'debit')->sum('amount');
        $currentPayable = (float) $doctor->current_payable;

        return view('doctor-portal.ledger.index', compact(
            'doctor',
            'ledgerEntries',
            'startDate',
            'endDate',
            'type',
            'totalCredit',
            'totalDebit',
            'currentPayable'
        ));
    }
}
