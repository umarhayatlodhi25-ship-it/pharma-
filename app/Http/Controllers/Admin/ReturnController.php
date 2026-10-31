<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MedicineBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | RETURNS DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $salesReturns = SalesReturn::with([
            'sale',
            'customer',
        ])
            ->latest()
            ->paginate(10, ['*'], 'sales_page');

        $purchaseReturns = PurchaseReturn::with([
            'purchaseInvoice',
            'supplier',
        ])
            ->latest()
            ->paginate(10, ['*'], 'purchase_page');

        return view(
            'admin.returns.index',
            compact(
                'salesReturns',
                'purchaseReturns'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SALES RETURN CREATE
    |--------------------------------------------------------------------------
    */

    public function salesCreate(Request $request)
    {
        $sales = Sale::with('customer')
            ->latest('id')
            ->get();

        $selectedSale = null;

        if ($request->filled('sale_id')) {
            $selectedSale = Sale::with([
                'customer',
                'items.medicine',
                'items.batch',
            ])->findOrFail($request->sale_id);
        }

        return view(
            'admin.returns.sales-create',
            compact(
                'sales',
                'selectedSale'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD SALES INVOICE
    |--------------------------------------------------------------------------
    */

    public function salesInvoice($id)
    {
        $selectedSale = Sale::with([
            'customer',
            'items.medicine.category',
            'items.batch',
        ])->findOrFail($id);

        $sales = Sale::with([
            'customer',
        ])
            ->latest('id')
            ->get();

        return view(
            'admin.returns.sales-create',
            [
                'sales' => $sales,
                'selectedSale' => $selectedSale,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE SALES RETURN
    |--------------------------------------------------------------------------
    */

    public function storeSalesReturn(Request $request)
    {
        $request->validate([
            'sale_id' => [
                'required',
                'exists:sales,id',
            ],

            'return_date' => [
                'required',
                'date',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);


        DB::transaction(function () use ($request) {

            /*
             * Lock selected sale
             */
            $sale = Sale::with('items')
                ->lockForUpdate()
                ->findOrFail($request->sale_id);


            /*
             * Generate return number
             */
            $returnNumber =
                'SRET-' .
                now()->format('YmdHis') .
                '-' .
                random_int(100, 999);


            $totalAmount = 0;


            /*
             * ---------------------------------------------------------
             * VALIDATE RETURN ITEMS
             * ---------------------------------------------------------
             */

            foreach ($request->items as $item) {

                if (
                    empty($item['sale_item_id']) ||
                    !isset($item['quantity'])
                ) {
                    continue;
                }

                $returnQty = (float) $item['quantity'];

                /*
                 * Ignore zero quantities
                 */
                if ($returnQty <= 0) {
                    continue;
                }


                $saleItem = SaleItem::with([
                    'medicine',
                    'batch',
                ])->findOrFail(
                    $item['sale_item_id']
                );


                /*
                 * Make sure item belongs to selected sale
                 */
                if (
                    (int) $saleItem->sale_id !==
                    (int) $sale->id
                ) {
                    abort(
                        422,
                        'Invalid sale item selected.'
                    );
                }


                /*
                 * Already returned quantity
                 */
                $alreadyReturned =
                    SalesReturnItem::where(
                        'sale_item_id',
                        $saleItem->id
                    )->sum('quantity');


                /*
                 * Remaining quantity available
                 */
                $availableToReturn =
                    (float) $saleItem->quantity -
                    (float) $alreadyReturned;


                if ($returnQty > $availableToReturn) {

                    $medicineName =
                        $saleItem->medicine?->name ??
                        'selected medicine';

                    abort(
                        422,
                        "Return quantity exceeds available quantity for {$medicineName}."
                    );
                }


                /*
                 * Calculate refund amount
                 */
                $totalAmount +=
                    $returnQty *
                    (float) $saleItem->unit_price;
            }


            /*
             * At least one item must be returned
             */
            if ($totalAmount <= 0) {
                abort(
                    422,
                    'Please enter a return quantity for at least one medicine.'
                );
            }


            /*
             * Round refund amount
             */
            $totalAmount = round(
                $totalAmount,
                2
            );


            /*
             * ---------------------------------------------------------
             * CREATE SALES RETURN
             * ---------------------------------------------------------
             */

            $salesReturn = SalesReturn::create([
                'sale_id' =>
                    $sale->id,

                'customer_id' =>
                    $sale->customer_id,

                'return_number' =>
                    $returnNumber,

                'return_date' =>
                    $request->return_date,

                'total_amount' =>
                    $totalAmount,

                'reason' =>
                    $request->reason,
            ]);


            /*
             * ---------------------------------------------------------
             * CREATE RETURN ITEMS + RESTORE STOCK
             * ---------------------------------------------------------
             */

            foreach ($request->items as $item) {

                if (
                    empty($item['sale_item_id']) ||
                    !isset($item['quantity'])
                ) {
                    continue;
                }


                $returnQty =
                    (float) $item['quantity'];


                if ($returnQty <= 0) {
                    continue;
                }


                $saleItem = SaleItem::with([
                    'medicine',
                    'batch',
                ])->findOrFail(
                    $item['sale_item_id']
                );


                $unitPrice = (float) $saleItem->unit_price;
                $subtotal = round($returnQty * $unitPrice, 2);

                // Use frozen historical conversion from saleItem
                $conversionToBase = (float) ($saleItem->conversion_to_base ?: 1.0);
                if ($conversionToBase <= 0) {
                    $medicine = $saleItem->medicine ?: \App\Models\Medicine::find($saleItem->medicine_id);
                    $conversionToBase = $medicine ? (float)$medicine->getMultiplierForUnit($saleItem->unit) : 1.0;
                }

                $returnedBaseQty = round($returnQty * $conversionToBase, 4);

                /*
                 * Create return item
                 */
                $returnItem = SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'sale_item_id' => $saleItem->id,
                    'medicine_id' => $saleItem->medicine_id,
                    'batch_id' => $saleItem->batch_id,
                    'packaging_id' => $saleItem->packaging_id,
                    'unit_id' => $saleItem->unit_id,
                    'unit' => $saleItem->unit ?? 'base',
                    'conversion_to_base' => $conversionToBase,
                    'quantity' => $returnQty,
                    'base_quantity' => $returnedBaseQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                /*
                 * Restore exact batch stock in BASE UNITS
                 */
                $batch = MedicineBatch::lockForUpdate()->find($saleItem->batch_id);

                if (!$batch) {
                    abort(422, 'Medicine batch not found.');
                }

                $batch->increment('quantity', $returnedBaseQty);
                if ($batch->fresh()->quantity > 0 && $batch->status === 'depleted') {
                    $batch->update(['status' => 'active']);
                }

                // Record in immutable stock movements ledger
                app(\App\Services\StockLedgerService::class)->recordMovement(
                    medicineId: $saleItem->medicine_id,
                    batchId: $batch->id,
                    type: 'SALE_RETURN',
                    referenceId: $returnItem->id,
                    referenceType: SalesReturnItem::class,
                    selectedUnitId: $saleItem->unit_id,
                    quantity: $returnQty,
                    conversionToBase: $conversionToBase,
                    baseQuantity: $returnedBaseQty,
                    userId: auth()->id() ?? 1,
                    notes: "Sales Return #{$salesReturn->return_number}: {$returnQty} {$saleItem->unit} (= {$returnedBaseQty} Base Units)"
                );
            }


            /*
             * ---------------------------------------------------------
             * REFUND CALCULATION
             * ---------------------------------------------------------
             *
             * Example:
             *
             * Patient purchased 3 tablets
             * Unit price = PKR 10
             * Patient returns 2 tablets
             *
             * Refund = 2 × 10 = PKR 20
             *
             * The refund amount is stored in the sales return
             * as total_amount.
             */

        });


        /*
         * Redirect after successful return
         */
        return redirect()
            ->route('returns.index')
            ->with(
                'message',
                'Sales return processed successfully. Refund amount has been calculated and stock restored in Base Units.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | PURCHASE RETURN CREATE
    |--------------------------------------------------------------------------
    */

    public function purchaseCreate(Request $request)
    {
        $purchases = PurchaseInvoice::with([
            'supplier',
            'items.medicine',
        ])
            ->latest('id')
            ->get();

        $purchase = null;

        if ($request->filled('purchase_id')) {
            $purchase = PurchaseInvoice::with([
                'supplier',
                'items.medicine.category',
            ])
                ->findOrFail($request->purchase_id);
        }

        return view(
            'admin.returns.purchase-create',
            compact(
                'purchases',
                'purchase'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | LOAD PURCHASE INVOICE
    |--------------------------------------------------------------------------
    */

    public function purchaseInvoice($id)
    {
        $purchase = PurchaseInvoice::with([
            'supplier',
            'items.medicine.category',
        ])
            ->findOrFail($id);

        $purchases = PurchaseInvoice::with([
            'supplier',
        ])
            ->latest('id')
            ->get();

        return view(
            'admin.returns.purchase-create',
            [
                'purchases' => $purchases,
                'purchase' => $purchase,
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE PURCHASE RETURN
    |--------------------------------------------------------------------------
    */

    public function storePurchaseReturn(Request $request)
    {
        $request->validate([
            'purchase_invoice_id' => [
                'required',
                'exists:purchase_invoices,id',
            ],
            'return_date' => [
                'required',
                'date',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        DB::transaction(function () use ($request) {
            $purchase = PurchaseInvoice::with('items')
                ->lockForUpdate()
                ->findOrFail($request->purchase_invoice_id);

            $returnNumber = 'PRET-' . now()->format('YmdHis') . '-' . random_int(100, 999);
            $totalAmount = 0;

            foreach ($request->items as $item) {
                if (empty($item['purchase_invoice_item_id']) || !isset($item['quantity'])) {
                    continue;
                }

                $returnQty = (float) $item['quantity'];
                if ($returnQty <= 0) {
                    continue;
                }

                $purchaseItem = PurchaseInvoiceItem::findOrFail($item['purchase_invoice_item_id']);

                if ((int) $purchaseItem->purchase_invoice_id !== (int) $purchase->id) {
                    abort(422, 'Invalid purchase item selected.');
                }

                $alreadyReturned = PurchaseReturnItem::where('purchase_invoice_item_id', $purchaseItem->id)->sum('quantity');
                $availableToReturn = (float) $purchaseItem->quantity - (float) $alreadyReturned;

                if ($returnQty > $availableToReturn) {
                    abort(422, 'Return quantity exceeds purchased quantity.');
                }

                $batch = MedicineBatch::where('medicine_id', $purchaseItem->medicine_id)
                    ->where('batch_number', $purchaseItem->batch_number)
                    ->lockForUpdate()
                    ->first();

                if (!$batch) {
                    abort(422, "Batch {$purchaseItem->batch_number} not found.");
                }

                $conversionToBase = (float) ($purchaseItem->conversion_to_base ?: 1.0);
                $returnedBaseQty = round($returnQty * $conversionToBase, 4);

                if ((float) $batch->quantity < $returnedBaseQty) {
                    abort(422, "Insufficient stock for batch {$purchaseItem->batch_number}. Available: {$batch->quantity} base units.");
                }

                $totalAmount += $returnQty * (float) $purchaseItem->purchase_price;
            }

            if ($totalAmount <= 0) {
                abort(422, 'Please enter a return quantity for at least one medicine.');
            }

            $totalAmount = round($totalAmount, 2);

            $purchaseReturn = PurchaseReturn::create([
                'purchase_invoice_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_number' => $returnNumber,
                'return_date' => $request->return_date,
                'total_amount' => $totalAmount,
                'reason' => $request->reason,
            ]);

            foreach ($request->items as $item) {
                if (empty($item['purchase_invoice_item_id']) || !isset($item['quantity'])) {
                    continue;
                }

                $returnQty = (float) $item['quantity'];
                if ($returnQty <= 0) {
                    continue;
                }

                $purchaseItem = PurchaseInvoiceItem::findOrFail($item['purchase_invoice_item_id']);
                $conversionToBase = (float) ($purchaseItem->conversion_to_base ?: 1.0);
                $returnedBaseQty = round($returnQty * $conversionToBase, 4);

                $unitPrice = (float) $purchaseItem->purchase_price;
                $subtotal = round($returnQty * $unitPrice, 2);

                $batch = MedicineBatch::where('medicine_id', $purchaseItem->medicine_id)
                    ->where('batch_number', $purchaseItem->batch_number)
                    ->lockForUpdate()
                    ->firstOrFail();

                $returnItem = PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_invoice_item_id' => $purchaseItem->id,
                    'medicine_id' => $purchaseItem->medicine_id,
                    'batch_id' => $batch->id,
                    'packaging_id' => $purchaseItem->packaging_id,
                    'unit_id' => $purchaseItem->unit_id,
                    'unit' => $purchaseItem->unit ?? 'base',
                    'conversion_to_base' => $conversionToBase,
                    'quantity' => $returnQty,
                    'base_quantity' => $returnedBaseQty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // Remove returned stock in strictly Base Units
                $batch->decrement('quantity', $returnedBaseQty);
                if ($batch->fresh()->quantity <= 0) {
                    $batch->update(['status' => 'depleted']);
                }

                // Record in immutable stock movements ledger
                app(\App\Services\StockLedgerService::class)->recordMovement(
                    medicineId: $purchaseItem->medicine_id,
                    batchId: $batch->id,
                    type: 'PURCHASE_RETURN',
                    referenceId: $returnItem->id,
                    referenceType: PurchaseReturnItem::class,
                    selectedUnitId: $purchaseItem->unit_id,
                    quantity: $returnQty,
                    conversionToBase: $conversionToBase,
                    baseQuantity: $returnedBaseQty,
                    userId: auth()->id() ?? 1,
                    notes: "Purchase Return #{$purchaseReturn->return_number}: {$returnQty} {$purchaseItem->unit} (= {$returnedBaseQty} Base Units)"
                );
            }

            if ($purchase->supplier) {
                $purchase->supplier->decrement('opening_balance', $totalAmount);
            }
        });

        return redirect()
            ->route('returns.index')
            ->with(
                'message',
                'Purchase return processed successfully and supplier balance updated.'
            );
    }



    /*
    |--------------------------------------------------------------------------
    | SALES RETURN DETAILS
    |--------------------------------------------------------------------------
    */

    public function salesShow($id)
    {
        $return = SalesReturn::with([
            'sale.customer',
            'customer',
            'items.medicine',
            'items.batch',
        ])->findOrFail($id);


        return view(
            'admin.returns.show',
            [
                'return' => $return,
                'type' => 'sales',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PURCHASE RETURN DETAILS
    |--------------------------------------------------------------------------
    */

    public function purchaseShow($id)
    {
        $return = PurchaseReturn::with([
            'purchaseInvoice',
            'supplier',
            'items.medicine',
            'items.batch',
        ])->findOrFail($id);


        return view(
            'admin.returns.show',
            [
                'return' => $return,
                'type' => 'purchase',
            ]
        );
    }
}