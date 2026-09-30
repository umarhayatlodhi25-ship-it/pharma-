<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Purchase;
use App\Models\PurchaseInvoice;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Supplier;
use App\Models\Customer;
use App\Models\Category;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientToken;
use App\Models\HospitalBill;
use App\Models\HospitalBillPayment;
use App\Models\HospitalService;
use App\Models\DoctorSettlement;
use App\Models\DoctorLedger;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Reports Hub / Overview
     */
    public function index()
    {
        $todaySales = Sale::whereDate('created_at', Carbon::today())->sum('total_amount');
        $monthlySales = Sale::whereMonth('created_at', Carbon::now()->month)
                            ->whereYear('created_at', Carbon::now()->year)
                            ->sum('total_amount');
        $totalMedicines = Medicine::count();
        $totalStockUnits = MedicineBatch::sum('quantity');
        
        $expiredCount = MedicineBatch::whereDate('expiry_date', '<', Carbon::today())->count();
        $expiring30DaysCount = MedicineBatch::whereDate('expiry_date', '>=', Carbon::today())
                                            ->whereDate('expiry_date', '<=', Carbon::now()->addDays(30))
                                            ->count();

        $lowStockCount = DB::table('medicines')
            ->join('medicine_batches', 'medicines.id', '=', 'medicine_batches.medicine_id')
            ->select('medicines.id', 'medicines.alert_quantity', DB::raw('SUM(medicine_batches.quantity) as total_qty'))
            ->groupBy('medicines.id', 'medicines.alert_quantity')
            ->havingRaw('SUM(medicine_batches.quantity) <= medicines.alert_quantity')
            ->get()
            ->count();

        return view('admin.reports.index', compact(
            'todaySales',
            'monthlySales',
            'totalMedicines',
            'totalStockUnits',
            'expiredCount',
            'expiring30DaysCount',
            'lowStockCount'
        ));
    }

    /**
     * 1. Sales Report
     */
    public function sales(Request $request)
    {
        $filter = $request->get('filter', 'today'); // today, weekly, monthly, custom
        $paymentMethod = $request->get('payment_method');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = Sale::with(['user', 'items.medicine']);

        if ($filter == 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter == 'weekly') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter == 'monthly') {
            $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        $sales = $query->latest()->get();

        $totalRevenue = $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $totalDiscount = $sales->sum('discount');
        $avgSaleValue = $totalTransactions > 0 ? $totalRevenue / $totalTransactions : 0;

        $paymentBreakdown = [
            'cash' => $sales->where('payment_method', 'cash')->sum('total_amount'),
            'card' => $sales->where('payment_method', 'card')->sum('total_amount'),
            'easypaisa' => $sales->where('payment_method', 'easypaisa')->sum('total_amount'),
            'bank_transfer' => $sales->where('payment_method', 'bank_transfer')->sum('total_amount'),
        ];

        return view('admin.reports.sales', compact(
            'sales', 'filter', 'paymentMethod', 'startDate', 'endDate',
            'totalRevenue', 'totalTransactions', 'totalDiscount', 'avgSaleValue', 'paymentBreakdown'
        ));
    }

    /**
     * 2. Purchase Report
     */
    public function purchases(Request $request)
    {
        $filter = $request->get('filter', 'monthly');
        $supplierId = $request->get('supplier_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $suppliers = Supplier::all();

        // Check if using Purchase model or PurchaseInvoice
        $query = Purchase::with('supplier');

        if ($filter == 'today') {
            $query->whereDate('purchase_date', Carbon::today());
        } elseif ($filter == 'weekly') {
            $query->whereBetween('purchase_date', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter == 'monthly') {
            $query->whereMonth('purchase_date', Carbon::now()->month)->whereYear('purchase_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $query->whereBetween('purchase_date', [Carbon::parse($startDate), Carbon::parse($endDate)]);
        }

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $purchases = $query->latest('purchase_date')->get();

        $totalPurchasesAmount = $purchases->sum('total_amount');
        $totalPaidAmount = $purchases->sum('paid_amount');
        $totalDueAmount = $purchases->sum('due_amount');

        return view('admin.reports.purchases', compact(
            'purchases', 'suppliers', 'filter', 'supplierId', 'startDate', 'endDate',
            'totalPurchasesAmount', 'totalPaidAmount', 'totalDueAmount'
        ));
    }

    /**
     * 3. Stock Report
     */
    public function stock(Request $request)
    {
        $categoryId = $request->get('category_id');
        $productType = $request->get('product_type', 'all');
        $categories = Category::all();

        $query = Medicine::with(['category', 'batches']);

        if ($productType && $productType !== 'all') {
            $query->productType($productType);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        $medicines = $query->get()->map(function ($medicine) {
            $totalQty = $medicine->batches->sum('quantity');
            $costVal = $medicine->batches->sum(function ($b) {
                return $b->quantity * $b->purchase_price;
            });
            $retailVal = $medicine->batches->sum(function ($b) {
                return $b->quantity * $b->selling_price;
            });

            $medicine->total_qty = $totalQty;
            $medicine->cost_valuation = $costVal;
            $medicine->retail_valuation = $retailVal;
            $medicine->potential_profit = $retailVal - $costVal;
            return $medicine;
        });

        $totalInventoryQty = $medicines->sum('total_qty');
        $totalCostValuation = $medicines->sum('cost_valuation');
        $totalRetailValuation = $medicines->sum('retail_valuation');
        $totalPotentialProfit = $totalRetailValuation - $totalCostValuation;

        return view('admin.reports.stock', compact(
            'medicines', 'categories', 'categoryId', 'productType',
            'totalInventoryQty', 'totalCostValuation', 'totalRetailValuation', 'totalPotentialProfit'
        ));
    }

    /**
     * 4. Expiry Report
     */
    public function expiry(Request $request)
    {
        $status = $request->get('status', 'all'); // expired, 30days, 60days, 90days, all

        $query = MedicineBatch::with('medicine')
            ->whereHas('medicine', function($q) {
                $q->where('has_expiry', true);
            });

        $today = Carbon::today();

        if ($status == 'expired') {
            $query->whereDate('expiry_date', '<', $today);
        } elseif ($status == '30days') {
            $query->whereDate('expiry_date', '>=', $today)
                  ->whereDate('expiry_date', '<=', $today->copy()->addDays(30));
        } elseif ($status == '60days') {
            $query->whereDate('expiry_date', '>=', $today)
                  ->whereDate('expiry_date', '<=', $today->copy()->addDays(60));
        } elseif ($status == '90days') {
            $query->whereDate('expiry_date', '>=', $today)
                  ->whereDate('expiry_date', '<=', $today->copy()->addDays(90));
        } else {
            $query->whereDate('expiry_date', '<=', $today->copy()->addDays(90));
        }

        $batches = $query->orderBy('expiry_date', 'asc')->get();

        $expiredBatches = MedicineBatch::whereDate('expiry_date', '<', $today)
            ->whereHas('medicine', fn($q) => $q->where('has_expiry', true))
            ->get();
        $expiredValue = $expiredBatches->sum(function ($b) {
            return $b->quantity * $b->purchase_price;
        });

        $expiring30DaysBatches = MedicineBatch::whereDate('expiry_date', '>=', $today)
            ->whereDate('expiry_date', '<=', $today->copy()->addDays(30))
            ->whereHas('medicine', fn($q) => $q->where('has_expiry', true))
            ->get();
        $atRiskValue = $expiring30DaysBatches->sum(function ($b) {
            return $b->quantity * $b->purchase_price;
        });

        return view('admin.reports.expiry', compact(
            'batches', 'status', 'expiredValue', 'atRiskValue'
        ));
    }

    /**
     * 5. Profit & Loss Report
     */
    public function profitLoss(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $salesQuery = Sale::whereBetween('created_at', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);

        $totalSalesRevenue = $salesQuery->sum('total_amount');
        $totalDiscountsGiven = $salesQuery->sum('discount');
        $totalTaxCollected = $salesQuery->sum('tax');

        // Cost of Goods Sold (COGS) calculation from SaleItems
        $salesIds = $salesQuery->pluck('id');
        $saleItems = SaleItem::whereIn('sale_id', $salesIds)->get();
        
        $totalCogs = 0;
        foreach ($saleItems as $item) {
            $batch = MedicineBatch::find($item->batch_id);
            $unitCost = $batch ? $batch->purchase_price : ($item->medicine ? $item->medicine->purchase_price : 0);
            $totalCogs += ($unitCost * $item->quantity);
        }

        // Purchases incurred during period
        $purchasesIncurred = Purchase::whereBetween('purchase_date', [
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        ])->sum('total_amount');

        $grossProfit = $totalSalesRevenue - $totalCogs;
        $netProfit = $grossProfit - $totalDiscountsGiven;
        $profitMargin = $totalSalesRevenue > 0 ? ($netProfit / $totalSalesRevenue) * 100 : 0;

        return view('admin.reports.profit_loss', compact(
            'startDate', 'endDate',
            'totalSalesRevenue', 'totalDiscountsGiven', 'totalCogs', 'purchasesIncurred',
            'grossProfit', 'netProfit', 'profitMargin'
        ));
    }

    /**
     * 6. Customer Report
     */
    public function customers(Request $request)
    {
        $customers = Customer::all();

        $customerSales = Sale::select('customer_id', 
            DB::raw('COUNT(id) as total_orders'),
            DB::raw('SUM(total_amount) as total_spent'),
            DB::raw('MAX(created_at) as last_order_date')
        )
        ->whereNotNull('customer_id')
        ->groupBy('customer_id')
        ->orderByDesc('total_spent')
        ->get();

        $totalCustomers = Customer::count();
        $totalCustomerRevenue = $customerSales->sum('total_spent');

        return view('admin.reports.customers', compact(
            'customers', 'customerSales', 'totalCustomers', 'totalCustomerRevenue'
        ));
    }

    /**
     * 7. Supplier Report
     */
    public function suppliers(Request $request)
    {
        $suppliers = Supplier::all();

        $supplierStats = Supplier::all()->map(function ($supplier) {
            $purchases = Purchase::where('supplier_id', $supplier->id)->get();
            $supplier->total_purchases_count = $purchases->count();
            $supplier->total_purchased_amount = $purchases->sum('total_amount');
            $supplier->total_paid_amount = $purchases->sum('paid_amount');
            $supplier->pending_due_amount = $purchases->sum('due_amount') + ($supplier->opening_balance ?? 0);
            return $supplier;
        });

        $totalSuppliersCount = $suppliers->count();
        $grandPurchasesValue = $supplierStats->sum('total_purchased_amount');
        $grandTotalPayableDue = $supplierStats->sum('pending_due_amount');

        return view('admin.reports.suppliers', compact(
            'supplierStats', 'totalSuppliersCount', 'grandPurchasesValue', 'grandTotalPayableDue'
        ));
    }

    /**
     * 8. Best Selling Report
     */
    public function bestSelling(Request $request)
    {
        $limit = $request->get('limit', 15);
        $period = $request->get('period', 'all'); // today, month, all

        $query = SaleItem::select('medicine_id',
            DB::raw('SUM(quantity) as total_qty_sold'),
            DB::raw('SUM(subtotal) as total_revenue_generated')
        )
        ->with('medicine');

        if ($period == 'today') {
            $query->whereHas('sale', function ($q) {
                $q->whereDate('created_at', Carbon::today());
            });
        } elseif ($period == 'month') {
            $query->whereHas('sale', function ($q) {
                $q->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
            });
        }

        $bestSellers = $query->groupBy('medicine_id')
                            ->orderByDesc('total_qty_sold')
                            ->limit($limit)
                            ->get();

        $overallQuantitySold = $bestSellers->sum('total_qty_sold');
        $overallRevenueGenerated = $bestSellers->sum('total_revenue_generated');

        return view('admin.reports.best_selling', compact(
            'bestSellers', 'limit', 'period', 'overallQuantitySold', 'overallRevenueGenerated'
        ));
    }

    /**
     * 9. Low Stock Report
     */
    public function lowStock(Request $request)
    {
        $productType = $request->get('product_type', 'all');
        $query = Medicine::with(['category', 'batches']);

        if ($productType && $productType !== 'all') {
            $query->productType($productType);
        }

        $lowStockMedicines = $query->get()->map(function ($medicine) {
            $currentQty = $medicine->batches->sum('quantity');
            $medicine->current_stock = $currentQty;
            $medicine->stock_deficit = max(0, $medicine->alert_quantity - $currentQty);
            return $medicine;
        })->filter(function ($medicine) {
            return $medicine->current_stock <= $medicine->alert_quantity;
        })->values();

        $outOfStockCount = $lowStockMedicines->where('current_stock', 0)->count();
        $lowStockCount = $lowStockMedicines->where('current_stock', '>', 0)->count();

        return view('admin.reports.low_stock', compact(
            'lowStockMedicines', 'outOfStockCount', 'lowStockCount', 'productType'
        ));
    }

    /**
     * 10. Discount Analysis Report
     */
    public function discounts(Request $request)
    {
        $filter = $request->get('filter', 'monthly'); // today, weekly, monthly, custom
        $userId = $request->get('user_id');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $users = User::all();

        $query = Sale::with(['user', 'customer'])->where('discount', '>', 0);

        if ($filter == 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter == 'weekly') {
            $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($filter == 'monthly') {
            $query->whereMonth('created_at', Carbon::now()->month)->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $query->whereBetween('created_at', [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()]);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $discountSales = $query->latest()->get();

        $totalDiscountGiven = $discountSales->sum('discount');
        $discountedInvoicesCount = $discountSales->count();
        $totalOriginalSubtotal = $discountSales->sum(function($sale) {
            return $sale->subtotal ?: ($sale->total_amount + $sale->discount);
        });
        $totalFinalAmountPaid = $discountSales->sum('total_amount');
        $avgDiscountPerInvoice = $discountedInvoicesCount > 0 ? $totalDiscountGiven / $discountedInvoicesCount : 0;
        $discountPercentageOfSales = $totalOriginalSubtotal > 0 ? ($totalDiscountGiven / $totalOriginalSubtotal) * 100 : 0;

        return view('admin.reports.discounts', compact(
            'discountSales', 'users', 'filter', 'userId', 'startDate', 'endDate',
            'totalDiscountGiven', 'discountedInvoicesCount', 'totalOriginalSubtotal',
            'totalFinalAmountPaid', 'avgDiscountPerInvoice', 'discountPercentageOfSales'
        ));
    }

    /**
     * 11. OPD Reports & All Doctors Collection Report
     */
    public function opd(Request $request)
    {
        $filter = $request->get('filter', 'today'); // today, yesterday, weekly, monthly, custom
        $doctorId = $request->get('doctor_id');
        $feeType = $request->get('fee_type');
        $status = $request->get('status');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Resolve dates based on preset filter
        if ($filter == 'today') {
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        } elseif ($filter == 'yesterday') {
            $startDate = Carbon::yesterday()->toDateString();
            $endDate = Carbon::yesterday()->toDateString();
        } elseif ($filter == 'weekly') {
            $startDate = Carbon::now()->startOfWeek()->toDateString();
            $endDate = Carbon::now()->endOfWeek()->toDateString();
        } elseif ($filter == 'monthly') {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        } elseif ($filter == 'custom') {
            $startDate = $startDate ?: Carbon::today()->toDateString();
            $endDate = $endDate ?: Carbon::today()->toDateString();
        } else {
            $filter = 'today';
            $startDate = Carbon::today()->toDateString();
            $endDate = Carbon::today()->toDateString();
        }

        $allDoctors = Doctor::active()->orderBy('name')->get();

        // 1. Detailed OPD Visits Query
        $visitsQuery = PatientToken::with(['patient', 'doctor'])
            ->whereBetween('token_date', [$startDate, $endDate]);

        if (!empty($doctorId)) {
            $visitsQuery->where('doctor_id', $doctorId);
        }

        if (!empty($feeType)) {
            $visitsQuery->where('payment_type', $feeType);
        }

        if (!empty($status)) {
            if ($status === 'in_consultation') {
                $visitsQuery->whereIn('status', ['called', 'in_consultation']);
            } else {
                $visitsQuery->where('status', $status);
            }
        }

        $tokens = $visitsQuery->orderBy('token_date', 'desc')->orderBy('token_number', 'asc')->get();

        // 2. Summary KPI Metrics (Section 3)
        $totalTokens = $tokens->count();
        $totalPatients = $tokens->pluck('patient_id')->unique()->count();
        $paidPatients = $tokens->where('payment_type', 'paid')->count();
        $freePatients = $tokens->where('payment_type', 'free')->count();

        // Financials: strictly exclude cancelled visits from collection and fee totals (Section 12)
        $validTokens = $tokens->where('status', '!=', 'cancelled');
        $totalDoctorFees = (float) $validTokens->sum('consultation_fee');
        $totalCollection = (float) $validTokens->sum('charged_amount');
        $totalFreeAmount = max(0, $totalDoctorFees - $totalCollection);

        // 3. Doctor-wise Collection Report (Section 5, 6, 7, 8)
        // If specific doctor selected, show that doctor; otherwise show All Doctors
        $doctorsToReport = !empty($doctorId) ? Doctor::where('id', $doctorId)->get() : $allDoctors;

        $doctorStatsQuery = DB::table('patient_tokens')
            ->whereBetween('token_date', [$startDate, $endDate])
            ->whereNotNull('doctor_id');

        if (!empty($doctorId)) {
            $doctorStatsQuery->where('doctor_id', $doctorId);
        }
        if (!empty($feeType)) {
            $doctorStatsQuery->where('payment_type', $feeType);
        }
        if (!empty($status)) {
            if ($status === 'in_consultation') {
                $doctorStatsQuery->whereIn('status', ['called', 'in_consultation']);
            } else {
                $doctorStatsQuery->where('status', $status);
            }
        }

        $doctorStats = $doctorStatsQuery
            ->select([
                'doctor_id',
                DB::raw('COUNT(id) as total_tokens'),
                DB::raw('COUNT(DISTINCT patient_id) as total_patients'),
                DB::raw("COUNT(CASE WHEN payment_type = 'paid' THEN 1 END) as paid_count"),
                DB::raw("COUNT(CASE WHEN payment_type = 'free' THEN 1 END) as free_count"),
                DB::raw("SUM(CASE WHEN status != 'cancelled' THEN consultation_fee ELSE 0 END) as doctor_fees"),
                DB::raw("SUM(CASE WHEN status != 'cancelled' THEN charged_amount ELSE 0 END) as collected_amount"),
                DB::raw("SUM(CASE WHEN status != 'cancelled' THEN doctor_share_amount ELSE 0 END) as doctor_share_amount"),
                DB::raw("SUM(CASE WHEN status != 'cancelled' THEN hospital_share_amount ELSE 0 END) as hospital_share_amount"),
            ])
            ->groupBy('doctor_id')
            ->get()
            ->keyBy('doctor_id');

        $doctorReports = [];
        $grandTotal = [
            'total_patients' => 0,
            'paid_patients'  => 0,
            'free_patients'  => 0,
            'doctor_fees'    => 0.0,
            'collected'      => 0.0,
            'doctor_share'   => 0.0,
            'hospital_share' => 0.0,
            'free_amount'    => 0.0,
            'collection_pct' => 0.0,
        ];

        foreach ($doctorsToReport as $doc) {
            $stat = $doctorStats->get($doc->id);
            $pCount = $stat ? intval($stat->total_patients) : 0;
            $pPaid = $stat ? intval($stat->paid_count) : 0;
            $pFree = $stat ? intval($stat->free_count) : 0;
            $dFees = $stat ? floatval($stat->doctor_fees) : 0.0;
            $dCollected = $stat ? floatval($stat->collected_amount) : 0.0;
            $dDocShare = $stat ? floatval($stat->doctor_share_amount) : 0.0;
            $dHospShare = $stat ? floatval($stat->hospital_share_amount) : 0.0;
            $dFreeAmount = max(0, $dFees - $dCollected);
            $dPct = $dFees > 0 ? round(($dCollected / $dFees) * 100, 1) : 0.0;

            $doctorReports[] = [
                'doctor'         => $doc,
                'total_patients' => $pCount,
                'paid_patients'  => $pPaid,
                'free_patients'  => $pFree,
                'doctor_fees'    => $dFees,
                'collected'      => $dCollected,
                'doctor_share'   => $dDocShare,
                'hospital_share' => $dHospShare,
                'free_amount'    => $dFreeAmount,
                'collection_pct' => $dPct,
            ];

            $grandTotal['total_patients'] += $pCount;
            $grandTotal['paid_patients']  += $pPaid;
            $grandTotal['free_patients']  += $pFree;
            $grandTotal['doctor_fees']    += $dFees;
            $grandTotal['collected']      += $dCollected;
            $grandTotal['doctor_share']   += $dDocShare;
            $grandTotal['hospital_share'] += $dHospShare;
            $grandTotal['free_amount']    += $dFreeAmount;
        }

        $grandTotal['collection_pct'] = $grandTotal['doctor_fees'] > 0
            ? round(($grandTotal['collected'] / $grandTotal['doctor_fees']) * 100, 1)
            : 0.0;

        return view('admin.reports.opd', compact(
            'tokens',
            'allDoctors',
            'filter',
            'doctorId',
            'feeType',
            'status',
            'startDate',
            'endDate',
            'totalTokens',
            'totalPatients',
            'paidPatients',
            'freePatients',
            'totalDoctorFees',
            'totalCollection',
            'totalFreeAmount',
            'doctorReports',
            'grandTotal'
        ));
    }

    /**
     * 12. Doctor Collection Report (Detailed)
     */
    public function doctorCollection(Request $request)
    {
        $doctors = Doctor::orderBy('name')->get();
        $doctorId = $request->get('doctor_id');
        $filter = $request->get('filter', 'today'); // today, yesterday, weekly, monthly, custom, all
        $paymentStatus = $request->get('payment_status', 'all');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $query = HospitalBill::with(['doctor', 'patient', 'service', 'token']);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($filter == 'today') {
            $query->whereDate('bill_date', Carbon::today());
        } elseif ($filter == 'yesterday') {
            $query->whereDate('bill_date', Carbon::yesterday());
        } elseif ($filter == 'weekly') {
            $query->whereBetween('bill_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
        } elseif ($filter == 'monthly') {
            $query->whereMonth('bill_date', Carbon::now()->month)->whereYear('bill_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $query->whereBetween('bill_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
        }

        if ($paymentStatus && $paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }

        $bills = $query->latest('id')->get();

        $metrics = [
            'total_patients'      => $bills->pluck('patient_id')->unique()->count(),
            'total_bills'         => $bills->count(),
            'total_billed'        => (float) $bills->sum('total_amount'),
            'total_collected'     => (float) $bills->sum('paid_amount'),
            'doctor_total_share'  => (float) $bills->sum('doctor_share'),
            'hospital_total_share'=> (float) $bills->sum('hospital_share'),
            'remaining_unpaid'    => (float) $bills->sum('remaining_amount'),
        ];

        $selectedDoctor = $doctorId ? Doctor::find($doctorId) : null;

        return view('admin.reports.doctor_collection', compact(
            'bills',
            'doctors',
            'selectedDoctor',
            'doctorId',
            'filter',
            'paymentStatus',
            'startDate',
            'endDate',
            'metrics'
        ));
    }

    /**
     * 13. All Doctors Collection Report (Summary & Comparison)
     */
    public function allDoctorsCollection(Request $request)
    {
        $filter = $request->get('filter', 'today'); // today, yesterday, weekly, monthly, custom, all
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $billQuery = HospitalBill::query();

        if ($filter == 'today') {
            $billQuery->whereDate('bill_date', Carbon::today());
        } elseif ($filter == 'yesterday') {
            $billQuery->whereDate('bill_date', Carbon::yesterday());
        } elseif ($filter == 'weekly') {
            $billQuery->whereBetween('bill_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
        } elseif ($filter == 'monthly') {
            $billQuery->whereMonth('bill_date', Carbon::now()->month)->whereYear('bill_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $billQuery->whereBetween('bill_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
        }

        $bills = $billQuery->get();
        $doctors = Doctor::orderBy('name')->get();

        $doctorSummaries = [];
        $hospitalTotals = [
            'total_patients' => 0,
            'total_billed' => 0,
            'total_collected' => 0,
            'total_doctor_share' => 0,
            'total_hospital_share' => 0,
            'total_settled' => 0,
            'balance_payable' => 0,
        ];

        foreach ($doctors as $doc) {
            $docBills = $bills->where('doctor_id', $doc->id);
            $pCount = $docBills->pluck('patient_id')->unique()->count();
            $billed = (float) $docBills->sum('total_amount');
            $collected = (float) $docBills->sum('paid_amount');
            $docShare = (float) $docBills->sum('doctor_share');
            $hospShare = (float) $docBills->sum('hospital_share');
            
            $totalSettled = (float) $doc->settlements()->sum('amount');
            $currentPayable = (float) $doc->current_payable;

            $doctorSummaries[] = [
                'doctor' => $doc,
                'patients_count' => $pCount,
                'total_billed' => $billed,
                'total_collected' => $collected,
                'doctor_share' => $docShare,
                'hospital_share' => $hospShare,
                'total_settled' => $totalSettled,
                'current_payable' => $currentPayable,
            ];

            $hospitalTotals['total_patients'] += $pCount;
            $hospitalTotals['total_billed'] += $billed;
            $hospitalTotals['total_collected'] += $collected;
            $hospitalTotals['total_doctor_share'] += $docShare;
            $hospitalTotals['total_hospital_share'] += $hospShare;
            $hospitalTotals['total_settled'] += $totalSettled;
            $hospitalTotals['balance_payable'] += $currentPayable;
        }

        return view('admin.reports.all_doctors_collection', compact(
            'doctorSummaries',
            'hospitalTotals',
            'filter',
            'startDate',
            'endDate'
        ));
    }

    /**
     * 14. Hospital Overall Collection Report (Overall Revenue)
     */
    public function hospitalCollection(Request $request)
    {
        $filter = $request->get('filter', 'today'); // today, yesterday, weekly, monthly, custom, all
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $billQuery = HospitalBill::query();
        $settlementQuery = DoctorSettlement::query();

        if ($filter == 'today') {
            $billQuery->whereDate('bill_date', Carbon::today());
            $settlementQuery->whereDate('settlement_date', Carbon::today());
        } elseif ($filter == 'yesterday') {
            $billQuery->whereDate('bill_date', Carbon::yesterday());
            $settlementQuery->whereDate('settlement_date', Carbon::yesterday());
        } elseif ($filter == 'weekly') {
            $billQuery->whereBetween('bill_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
            $settlementQuery->whereBetween('settlement_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
        } elseif ($filter == 'monthly') {
            $billQuery->whereMonth('bill_date', Carbon::now()->month)->whereYear('bill_date', Carbon::now()->year);
            $settlementQuery->whereMonth('settlement_date', Carbon::now()->month)->whereYear('settlement_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $billQuery->whereBetween('bill_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
            $settlementQuery->whereBetween('settlement_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
        }

        $bills = $billQuery->with(['doctor', 'service'])->get();
        $settlementsSum = (float) $settlementQuery->sum('amount');

        $metrics = [
            'total_billed'       => (float) $bills->sum('total_amount'),
            'total_collected'    => (float) $bills->sum('paid_amount'),
            'doctor_share'       => (float) $bills->sum('doctor_share'),
            'hospital_share'     => (float) $bills->sum('hospital_share'),
            'total_unpaid'       => (float) $bills->sum('remaining_amount'),
            'total_settled'      => $settlementsSum,
            'cash_in_hand'       => (float) ($bills->sum('paid_amount') - $settlementsSum),
        ];

        // Breakdown by Service
        $serviceBreakdown = $bills->groupBy('hospital_service_id')->map(function ($group) {
            $service = $group->first()->service;
            return [
                'name' => $service ? $service->name : 'General Consultation',
                'count' => $group->count(),
                'billed' => (float) $group->sum('total_amount'),
                'collected' => (float) $group->sum('paid_amount'),
                'hospital_share' => (float) $group->sum('hospital_share'),
                'doctor_share' => (float) $group->sum('doctor_share'),
            ];
        });

        // Breakdown by Doctor
        $doctorBreakdown = $bills->groupBy('doctor_id')->map(function ($group) {
            $doctor = $group->first()->doctor;
            return [
                'name' => $doctor ? $doctor->name : 'N/A',
                'count' => $group->count(),
                'billed' => (float) $group->sum('total_amount'),
                'collected' => (float) $group->sum('paid_amount'),
                'hospital_share' => (float) $group->sum('hospital_share'),
                'doctor_share' => (float) $group->sum('doctor_share'),
            ];
        });

        return view('admin.reports.hospital_collection', compact(
            'metrics',
            'serviceBreakdown',
            'doctorBreakdown',
            'filter',
            'startDate',
            'endDate'
        ));
    }

    /**
     * 15. Hospital Services Report
     */
    public function hospitalServices(Request $request)
    {
        $filter = $request->get('filter', 'today'); // today, yesterday, weekly, monthly, custom, all
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $billQuery = HospitalBill::query();

        if ($filter == 'today') {
            $billQuery->whereDate('bill_date', Carbon::today());
        } elseif ($filter == 'yesterday') {
            $billQuery->whereDate('bill_date', Carbon::yesterday());
        } elseif ($filter == 'weekly') {
            $billQuery->whereBetween('bill_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
        } elseif ($filter == 'monthly') {
            $billQuery->whereMonth('bill_date', Carbon::now()->month)->whereYear('bill_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $billQuery->whereBetween('bill_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
        }

        $bills = $billQuery->get();
        $services = HospitalService::orderBy('name')->get();

        $serviceRows = [];
        $totals = [
            'count' => 0,
            'billed' => 0,
            'collected' => 0,
            'hospital_share' => 0,
            'doctor_share' => 0,
        ];

        foreach ($services as $srv) {
            $srvBills = $bills->where('hospital_service_id', $srv->id);
            $cnt = $srvBills->count();
            $bld = (float) $srvBills->sum('total_amount');
            $clt = (float) $srvBills->sum('paid_amount');
            $hsh = (float) $srvBills->sum('hospital_share');
            $dsh = (float) $srvBills->sum('doctor_share');

            $serviceRows[] = [
                'service' => $srv,
                'count' => $cnt,
                'billed' => $bld,
                'collected' => $clt,
                'hospital_share' => $hsh,
                'doctor_share' => $dsh,
            ];

            $totals['count'] += $cnt;
            $totals['billed'] += $bld;
            $totals['collected'] += $clt;
            $totals['hospital_share'] += $hsh;
            $totals['doctor_share'] += $dsh;
        }

        return view('admin.reports.hospital_services', compact(
            'serviceRows',
            'totals',
            'filter',
            'startDate',
            'endDate'
        ));
    }

    /**
     * 16. Doctor Payable Report
     */
    public function doctorPayable(Request $request)
    {
        $doctors = Doctor::orderBy('name')->get();

        $rows = [];
        $grandTotal = [
            'total_earned' => 0,
            'total_settled' => 0,
            'current_payable' => 0,
        ];

        foreach ($doctors as $doc) {
            $earned = (float) $doc->total_earned;
            $settled = (float) $doc->total_paid;
            $payable = (float) $doc->current_payable;
            $lastSettlement = $doc->settlements()->latest('settlement_date')->first();

            $rows[] = [
                'doctor' => $doc,
                'earned' => $earned,
                'settled' => $settled,
                'payable' => $payable,
                'last_settlement_date' => $lastSettlement ? $lastSettlement->settlement_date->format('d-M-Y') : 'Never',
            ];

            $grandTotal['total_earned'] += $earned;
            $grandTotal['total_settled'] += $settled;
            $grandTotal['current_payable'] += $payable;
        }

        return view('admin.reports.doctor_payable', compact('rows', 'grandTotal'));
    }

    /**
     * 17. Doctor Settlements History Report
     */
    public function doctorSettlements(Request $request)
    {
        $doctors = Doctor::orderBy('name')->get();
        $doctorId = $request->get('doctor_id');
        $filter = $request->get('filter', 'all'); // all, today, weekly, monthly, custom
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $paymentMethod = $request->get('payment_method');

        $query = DoctorSettlement::with(['doctor', 'settler']);

        if ($doctorId) {
            $query->where('doctor_id', $doctorId);
        }

        if ($filter == 'today') {
            $query->whereDate('settlement_date', Carbon::today());
        } elseif ($filter == 'yesterday') {
            $query->whereDate('settlement_date', Carbon::yesterday());
        } elseif ($filter == 'weekly') {
            $query->whereBetween('settlement_date', [Carbon::now()->startOfWeek()->toDateString(), Carbon::now()->endOfWeek()->toDateString()]);
        } elseif ($filter == 'monthly') {
            $query->whereMonth('settlement_date', Carbon::now()->month)->whereYear('settlement_date', Carbon::now()->year);
        } elseif ($filter == 'custom' && $startDate && $endDate) {
            $query->whereBetween('settlement_date', [Carbon::parse($startDate)->toDateString(), Carbon::parse($endDate)->toDateString()]);
        }

        if ($paymentMethod) {
            $query->where('payment_method', $paymentMethod);
        }

        $settlements = $query->latest('id')->get();
        $totalAmount = (float) $settlements->sum('amount');
        $totalCount = $settlements->count();

        return view('admin.reports.doctor_settlements', compact(
            'settlements',
            'doctors',
            'doctorId',
            'filter',
            'startDate',
            'endDate',
            'paymentMethod',
            'totalAmount',
            'totalCount'
        ));
    }
}
