<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorLedger;
use App\Models\DoctorSettlement;
use App\Services\HospitalBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorLedgerController extends Controller
{
    protected HospitalBillingService $billingService;

    public function __construct(HospitalBillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Overview of all doctors' collections, earnings, settlements, and payable balances.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $doctorsQuery = Doctor::query();
        if (!empty($search)) {
            $doctorsQuery->where('name', 'like', "%{$search}%")
                ->orWhere('specialization', 'like', "%{$search}%");
        }

        $doctors = $doctorsQuery->orderBy('name')->get();

        // Calculate statistics for each doctor
        $doctorStats = [];
        $totalEarnedAll = 0.0;
        $totalPaidAll = 0.0;
        $totalPayableAll = 0.0;
        $totalVisitsAll = 0;

        foreach ($doctors as $doc) {
            $earned = (float) $doc->ledgers()->where('transaction_type', 'credit')->sum('amount');
            $paid = (float) $doc->ledgers()->where('transaction_type', 'debit')->sum('amount');
            $payable = round(max(0.00, $earned - $paid), 2);
            $visits = $doc->tokens()->count();
            $grossBilled = (float) $doc->hospitalBills()->sum('total_amount');

            $doctorStats[] = [
                'doctor'          => $doc,
                'visits'          => $visits,
                'gross_billed'    => $grossBilled,
                'earned'          => $earned,
                'paid'            => $paid,
                'current_payable' => $payable,
            ];

            $totalEarnedAll += $earned;
            $totalPaidAll += $paid;
            $totalPayableAll += $payable;
            $totalVisitsAll += $visits;
        }

        return view('admin.doctor-ledgers.index', compact(
            'doctorStats',
            'search',
            'totalEarnedAll',
            'totalPaidAll',
            'totalPayableAll',
            'totalVisitsAll'
        ));
    }

    /**
     * Show individual doctor ledger statement and settlement history.
     */
    public function show(Request $request, Doctor $doctor)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DoctorLedger::with(['bill.patient', 'bill.service', 'payment', 'settlement'])
            ->where('doctor_id', $doctor->id);

        if (!empty($startDate) && !empty($endDate)) {
            $query->whereBetween('entry_date', [$startDate, $endDate]);
        }

        $ledgers = $query->orderBy('entry_date', 'asc')->orderBy('id', 'asc')->get();

        $settlements = DoctorSettlement::where('doctor_id', $doctor->id)
            ->orderBy('settlement_date', 'desc')
            ->orderBy('id', 'desc')
            ->take(20)
            ->get();

        $totalEarned = (float) $doctor->ledgers()->where('transaction_type', 'credit')->sum('amount');
        $totalPaid = (float) $doctor->ledgers()->where('transaction_type', 'debit')->sum('amount');
        $currentPayable = round(max(0.00, $totalEarned - $totalPaid), 2);

        return view('admin.doctor-ledgers.show', compact(
            'doctor',
            'ledgers',
            'settlements',
            'totalEarned',
            'totalPaid',
            'currentPayable',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Settle payment to doctor.
     */
    public function settle(Request $request, Doctor $doctor)
    {
        $currentPayable = (float) $doctor->current_payable;

        $validated = $request->validate([
            'amount'         => 'required|numeric|min:0.01|max:' . max(0.01, $currentPayable),
            'payment_method' => 'required|in:cash,bank_transfer,cheque,other',
            'reference_note' => 'nullable|string|max:255',
        ], [
            'amount.max' => "Payment amount cannot exceed doctor's outstanding payable balance (PKR " . number_format($currentPayable, 2) . ").",
        ]);

        try {
            $settlement = $this->billingService->settleDoctor(
                $doctor,
                floatval($validated['amount']),
                $validated['payment_method'],
                $validated['reference_note'] ?? 'Settlement payout',
                auth()->id()
            );

            return redirect()->back()
                ->with('success', "Settlement voucher #{$settlement->settlement_number} recorded successfully. Paid: PKR " . number_format($settlement->paid_amount, 2) . ". Remaining: PKR " . number_format($settlement->remaining_payable, 2));
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Settlement failed: ' . $e->getMessage());
        }
    }

    /**
     * Print settlement payout voucher.
     */
    public function printVoucher(DoctorSettlement $settlement)
    {
        $settlement->load(['doctor', 'createdBy']);
        return view('admin.doctor-ledgers.voucher', compact('settlement'));
    }
}
