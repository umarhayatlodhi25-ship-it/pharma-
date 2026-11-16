<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | PURCHASE LIST
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $purchases = PurchaseInvoice::with('supplier')
            ->latest()
            ->paginate(10);

        return view(
            'admin.purchases.index',
            compact('purchases')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();

        $medicines = Medicine::with('category')
            ->orderBy('name')
            ->get();

        return view(
            'admin.purchases.create',
            compact('suppliers', 'medicines')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:255',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.medicine_id' => [
                'required',
                'exists:medicines,id',
            ],

            'items.*.batch_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.purchase_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'items.*.selling_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'items.*.expiry_date' => [
                'nullable',
                'date',
            ],
        ]);


        DB::transaction(function () use ($request) {

            $subtotal = 0;
            $taxAmount = 0;

            /*
            |--------------------------------------------------------------------------
            | CALCULATE TOTALS
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $quantity = (float) $item['quantity'];

                $purchasePrice = (float) $item['purchase_price'];

                $taxPercent = (float) (
                    $item['tax_percent'] ?? 0
                );

                $baseTotal = $quantity * $purchasePrice;

                $itemTax = $baseTotal * ($taxPercent / 100);

                $subtotal += $baseTotal;

                $taxAmount += $itemTax;
            }

            $subtotal = round($subtotal, 2);

            $taxAmount = round($taxAmount, 2);

            $grandTotal = round(
                $subtotal + $taxAmount,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | CREATE PURCHASE INVOICE
            |--------------------------------------------------------------------------
            */

            $invoice = PurchaseInvoice::create([
                'supplier_id' => $request->supplier_id,

                'invoice_number' => $request->invoice_number,

                'purchase_date' => $request->purchase_date,

                'subtotal' => $subtotal,

                'tax_amount' => $taxAmount,

                'grand_total' => $grandTotal,
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE SUPPLIER BALANCE
            |--------------------------------------------------------------------------
            */

            $supplier = Supplier::findOrFail(
                $request->supplier_id
            );

            $supplier->increment(
                'opening_balance',
                $grandTotal
            );


            /*
            |--------------------------------------------------------------------------
            | CREATE PURCHASE ITEMS + MEDICINE BATCHES
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $quantity = (float) $item['quantity'];

                $purchasePrice = (float) $item['purchase_price'];

                $sellingPrice = (float) $item['selling_price'];

                $taxPercent = (float) (
                    $item['tax_percent'] ?? 0
                );

                $baseTotal =
                    $quantity * $purchasePrice;

                $tax =
                    $baseTotal * ($taxPercent / 100);

                $total = round(
                    $baseTotal + $tax,
                    2
                );


                $medicine = Medicine::with('packagings')->find($item['medicine_id']);
                $pkg = null;
                if (!empty($item['packaging_id'])) {
                    $pkg = $medicine?->packagings->firstWhere('id', (int)$item['packaging_id']);
                }
                if (!$pkg && $medicine) {
                    $pkg = $medicine->packagings->first();
                }

                $conversionToBase = $pkg ? (float)$pkg->conversion_to_base : 1.0;
                $unitId = $pkg?->unit_id ?? $medicine?->base_unit_id;
                $unitName = $pkg?->unit?->name ?? $item['unit'] ?? ($medicine?->base_unit ?: 'Tablet');
                $baseQuantity = round($quantity * $conversionToBase, 4);

                $purchasePerBase = round($purchasePrice / $conversionToBase, 4);
                $sellingPerBase = round($sellingPrice / $conversionToBase, 4);

                /*
                |--------------------------------------------------------------------------
                | PURCHASE INVOICE ITEM
                |--------------------------------------------------------------------------
                */

                $purchaseItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'medicine_id' => $item['medicine_id'],
                    'packaging_id' => $pkg?->id,
                    'unit_id' => $unitId,
                    'batch_number' => $item['batch_number'],
                    'unit' => $unitName,
                    'conversion_to_base' => $conversionToBase,
                    'quantity' => $quantity,
                    'base_quantity' => $baseQuantity,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'expiry_date' => $item['expiry_date'],
                    'tax_percent' => $taxPercent,
                    'total' => $total,
                ]);

                /*
                |--------------------------------------------------------------------------
                | MEDICINE BATCH (strictly in BASE UNITS)
                |--------------------------------------------------------------------------
                */

                $batch = MedicineBatch::create([
                    'medicine_id' => $item['medicine_id'],
                    'batch_number' => $item['batch_number'],
                    'quantity' => $baseQuantity,
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'purchase_price_per_base_unit' => $purchasePerBase,
                    'selling_price_per_base_unit' => $sellingPerBase,
                    'expiry_date' => $item['expiry_date'],
                    'supplier_id' => $request->supplier_id,
                    'status' => 'active',
                ]);

                // Record stock movement
                app(\App\Services\StockLedgerService::class)->recordMovement(
                    medicineId: $item['medicine_id'],
                    batchId: $batch->id,
                    type: 'PURCHASE',
                    referenceId: $purchaseItem->id,
                    referenceType: PurchaseInvoiceItem::class,
                    selectedUnitId: $unitId,
                    quantity: $quantity,
                    conversionToBase: $conversionToBase,
                    baseQuantity: $baseQuantity,
                    userId: auth()->id() ?? 1,
                    notes: "Purchase Invoice #{$invoice->invoice_number}: {$quantity} {$unitName} (= {$baseQuantity} Base Units)"
                );
            }
        });



        return redirect()
            ->route('purchases.index')
            ->with(
                'message',
                'Purchase Invoice Created & Stock Updated Successfully!'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $invoice = PurchaseInvoice::with([
            'supplier',
            'items.medicine.category',
        ])->findOrFail($id);

        return view(
            'admin.purchases.show',
            compact('invoice')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PRINT
    |--------------------------------------------------------------------------
    */

    public function printInvoice($id)
    {
        $invoice = PurchaseInvoice::with([
            'supplier',
            'items.medicine.category',
        ])->findOrFail($id);

        $accountProfile = \App\Models\AccountProfile::current();

        return view(
            'admin.purchases.print',
            compact('invoice', 'accountProfile')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $invoice = PurchaseInvoice::with([
            'supplier',
            'items.medicine.category',
        ])->findOrFail($id);

        $suppliers = Supplier::orderBy('name')->get();

        $medicines = Medicine::with('category')
            ->orderBy('name')
            ->get();

        return view(
            'admin.purchases.edit',
            compact(
                'invoice',
                'suppliers',
                'medicines'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $invoice = PurchaseInvoice::with('items')
            ->findOrFail($id);

        $request->validate([
            'supplier_id' => [
                'required',
                'exists:suppliers,id',
            ],

            'invoice_number' => [
                'required',
                'string',
                'max:255',
            ],

            'purchase_date' => [
                'required',
                'date',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.medicine_id' => [
                'required',
                'exists:medicines,id',
            ],

            'items.*.batch_number' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.purchase_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'items.*.selling_price' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'items.*.expiry_date' => [
                'required',
                'date',
            ],
        ]);


        DB::transaction(function () use (
            $request,
            $invoice
        ) {

            /*
            |--------------------------------------------------------------------------
            | REMOVE OLD SUPPLIER BALANCE
            |--------------------------------------------------------------------------
            */

            $oldSupplier = Supplier::find(
                $invoice->supplier_id
            );

            if ($oldSupplier) {

                $oldSupplier->decrement(
                    'opening_balance',
                    $invoice->grand_total
                );
            }


            /*
            |--------------------------------------------------------------------------
            | REMOVE OLD BATCHES
            |--------------------------------------------------------------------------
            */

            foreach ($invoice->items as $oldItem) {

                MedicineBatch::where(
                    'medicine_id',
                    $oldItem->medicine_id
                )
                    ->where(
                        'batch_number',
                        $oldItem->batch_number
                    )
                    ->where(
                        'quantity',
                        $oldItem->quantity
                    )
                    ->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE OLD ITEMS
            |--------------------------------------------------------------------------
            */

            $invoice->items()->delete();


            /*
            |--------------------------------------------------------------------------
            | CALCULATE NEW TOTAL
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;

            $taxAmount = 0;

            foreach ($request->items as $item) {

                $quantity =
                    (float) $item['quantity'];

                $purchasePrice =
                    (float) $item['purchase_price'];

                $taxPercent =
                    (float) (
                        $item['tax_percent'] ?? 0
                    );

                $baseTotal =
                    $quantity * $purchasePrice;

                $tax =
                    $baseTotal * (
                        $taxPercent / 100
                    );

                $subtotal += $baseTotal;

                $taxAmount += $tax;
            }

            $subtotal = round(
                $subtotal,
                2
            );

            $taxAmount = round(
                $taxAmount,
                2
            );

            $grandTotal = round(
                $subtotal + $taxAmount,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | UPDATE INVOICE
            |--------------------------------------------------------------------------
            */

            $invoice->update([
                'supplier_id' =>
                    $request->supplier_id,

                'invoice_number' =>
                    $request->invoice_number,

                'purchase_date' =>
                    $request->purchase_date,

                'subtotal' =>
                    $subtotal,

                'tax_amount' =>
                    $taxAmount,

                'grand_total' =>
                    $grandTotal,
            ]);


            /*
            |--------------------------------------------------------------------------
            | UPDATE NEW SUPPLIER BALANCE
            |--------------------------------------------------------------------------
            */

            $newSupplier = Supplier::findOrFail(
                $request->supplier_id
            );

            $newSupplier->increment(
                'opening_balance',
                $grandTotal
            );


            /*
            |--------------------------------------------------------------------------
            | CREATE NEW ITEMS + BATCHES
            |--------------------------------------------------------------------------
            */

            foreach ($request->items as $item) {

                $quantity =
                    (float) $item['quantity'];

                $purchasePrice =
                    (float) $item['purchase_price'];

                $sellingPrice =
                    (float) $item['selling_price'];

                $taxPercent =
                    (float) (
                        $item['tax_percent'] ?? 0
                    );

                $baseTotal =
                    $quantity * $purchasePrice;

                $tax =
                    $baseTotal * (
                        $taxPercent / 100
                    );

                $total = round(
                    $baseTotal + $tax,
                    2
                );


                PurchaseInvoiceItem::create([
                    'purchase_invoice_id' =>
                        $invoice->id,

                    'medicine_id' =>
                        $item['medicine_id'],

                    'batch_number' =>
                        $item['batch_number'],

                    'quantity' =>
                        $quantity,

                    'purchase_price' =>
                        $purchasePrice,

                    'selling_price' =>
                        $sellingPrice,

                    'expiry_date' =>
                        $item['expiry_date'],

                    'tax_percent' =>
                        $taxPercent,

                    'total' =>
                        $total,
                ]);


                MedicineBatch::create([
                    'medicine_id' =>
                        $item['medicine_id'],

                    'batch_number' =>
                        $item['batch_number'],

                    'quantity' =>
                        $quantity,

                    'purchase_price' =>
                        $purchasePrice,

                    'selling_price' =>
                        $sellingPrice,

                    'expiry_date' =>
                        $item['expiry_date'],

                    'supplier_id' =>
                        $request->supplier_id,
                ]);
            }
        });


        return redirect()
            ->route(
                'purchases.show',
                $invoice->id
            )
            ->with(
                'message',
                'Purchase updated successfully.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {

            $invoice = PurchaseInvoice::with('items')
                ->lockForUpdate()
                ->findOrFail($id);


            /*
            |--------------------------------------------------------------------------
            | REVERSE SUPPLIER BALANCE
            |--------------------------------------------------------------------------
            */

            $supplier = Supplier::find(
                $invoice->supplier_id
            );

            if ($supplier) {

                $supplier->decrement(
                    'opening_balance',
                    $invoice->grand_total
                );
            }


            /*
            |--------------------------------------------------------------------------
            | REMOVE MEDICINE BATCHES
            |--------------------------------------------------------------------------
            */

            foreach ($invoice->items as $item) {

                MedicineBatch::where(
                    'medicine_id',
                    $item->medicine_id
                )
                    ->where(
                        'batch_number',
                        $item->batch_number
                    )
                    ->where(
                        'quantity',
                        $item->quantity
                    )
                    ->delete();
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE ITEMS
            |--------------------------------------------------------------------------
            */

            $invoice->items()->delete();


            /*
            |--------------------------------------------------------------------------
            | DELETE INVOICE
            |--------------------------------------------------------------------------
            */

            $invoice->delete();
        });


        return redirect()
            ->route('purchases.index')
            ->with(
                'message',
                'Purchase deleted successfully and stock was reversed.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf($id)
    {
        $invoice = PurchaseInvoice::with([
            'supplier',
            'items.medicine.category',
        ])->findOrFail($id);

        $pdf = app('dompdf.wrapper');
        $accountProfile = \App\Models\AccountProfile::current();

        $pdf->loadView(
            'admin.purchases.pdf',
            compact('invoice', 'accountProfile')
        );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        return $pdf->download(
            'purchase-' .
            $invoice->invoice_number .
            '.pdf'
        );
    }
}