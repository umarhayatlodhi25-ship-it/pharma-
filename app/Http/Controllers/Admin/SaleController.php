<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountProfile;
use App\Models\Sale;

class SaleController extends Controller
{
    // Tamam sales ki list dikhane ke liye
    public function index()
    {
        $sales = Sale::with('items.medicine', 'items.batch')->latest()->paginate(10);
        return view('admin.sales.index', compact('sales'));
    }

    // Single invoice ki receipt/print view ke liye
    public function show($id)
    {
        $sale = Sale::with('items.medicine', 'items.batch')->findOrFail($id);
        $accountProfile = AccountProfile::current();
        return view('admin.sales.receipt', compact('sale', 'accountProfile'));
    }
}