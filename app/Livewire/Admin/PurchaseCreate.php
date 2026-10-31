<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseCreate extends Component
{
    use WithPagination;

    /*
    |--------------------------------------------------------------------------
    | PURCHASE HEADER
    |--------------------------------------------------------------------------
    */

    public string $supplier_id = '';

    public string $invoice_number = '';

    public string $purchase_date = '';


    /*
    |--------------------------------------------------------------------------
    | CURRENT MEDICINE ITEM
    |--------------------------------------------------------------------------
    */

    public string $medicine_id = '';

    public string $category_id = '';

    public string $product_type = 'all';

    public string $packaging_id = '';

    public string $medicineSearch = '';

    public string $batch_number = '';

    public string $quantity = '1';

    public string $purchase_price = '';

    public string $selling_price = '';

    public string $expiry_date = '';

    public string $tax_percent = '0';

    public string $purchase_unit = '';


    /*
    |--------------------------------------------------------------------------
    | MEDICINE SEARCH DROPDOWN
    |--------------------------------------------------------------------------
    */

    public bool $showMedicineDropdown = false;


    /*
    |--------------------------------------------------------------------------
    | CART
    |--------------------------------------------------------------------------
    */

    public array $cart = [];


    /*
    |--------------------------------------------------------------------------
    | HISTORY FILTERS
    |--------------------------------------------------------------------------
    */

    public string $history_from = '';

    public string $history_to = '';

    public string $history_supplier = '';


    /*
    |--------------------------------------------------------------------------
    | MOUNT
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->purchase_date = now()->format('Y-m-d');

        $this->generateInvoiceNumber();

        $this->resetItem();
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE INVOICE NUMBER
    |--------------------------------------------------------------------------
    */

    private function generateInvoiceNumber(): void
    {
        $this->invoice_number =
            'PINV-' . strtoupper(now()->format('YmdHis'));
    }


    /*
    |--------------------------------------------------------------------------
    | OPEN MEDICINE DROPDOWN
    |--------------------------------------------------------------------------
    */

    public function openMedicineDropdown(): void
    {
        $this->showMedicineDropdown = true;
    }


    /*
    |--------------------------------------------------------------------------
    | CLOSE MEDICINE DROPDOWN
    |--------------------------------------------------------------------------
    */

    public function closeMedicineDropdown(): void
    {
        $this->showMedicineDropdown = false;
    }


    /*
    |--------------------------------------------------------------------------
    | MEDICINE SEARCH UPDATED
    |--------------------------------------------------------------------------
    */

    public function updatedMedicineSearch($value): void
    {
        $this->medicine_id = '';
        $this->purchase_price = '';
        $this->selling_price = '';
        $this->showMedicineDropdown = true;

        $this->resetValidation([
            'medicine_id',
            'purchase_price',
            'selling_price',
        ]);
    }

    public function updatedCategoryId($value): void
    {
        $this->showMedicineDropdown = true;
    }

    public function updatedProductType($value): void
    {
        $this->category_id = '';
        $this->showMedicineDropdown = true;
    }


    /*
    |--------------------------------------------------------------------------
    | SELECT MEDICINE & PACKAGING
    |--------------------------------------------------------------------------
    */

    public function updatedPackagingId($val): void
    {
        if (empty($val) || empty($this->medicine_id)) {
            return;
        }

        $medicine = Medicine::with(['category', 'packagings.unit', 'baseUnit'])->find($this->medicine_id);
        if (!$medicine) {
            return;
        }

        if (is_numeric($val)) {
            $pkg = $medicine->packagings->firstWhere('id', (int)$val);
            if ($pkg) {
                $this->purchase_unit = $pkg->unit?->name ?? $pkg->display_name ?? 'Unit';
                if ($pkg->purchase_price !== null && (float)$pkg->purchase_price > 0) {
                    $this->purchase_price = (string) $pkg->purchase_price;
                }
                if ($pkg->sale_price !== null && (float)$pkg->sale_price > 0) {
                    $this->selling_price = (string) $pkg->sale_price;
                }
            }
        } else {
            // Direct unit selection like 'Carton', 'Box', 'Piece', 'Bottle'
            $this->purchase_unit = $val;
        }
    }

    public function selectMedicine(int $medicineId): void
    {
        $medicine = Medicine::with(['category', 'packagings.unit', 'baseUnit'])->find($medicineId);

        if (!$medicine) {
            return;
        }

        $this->medicine_id = (string) $medicine->id;
        $this->medicineSearch = $medicine->name;
        $this->category_id = (string) ($medicine->category_id ?? '');

        // Default to Primary/Largest purchaseable packaging, or Base Unit
        $primaryPkg = $medicine->packagings->where('allow_purchase', true)->sortByDesc('conversion_to_base')->first();
        if ($primaryPkg) {
            $this->packaging_id = (string) $primaryPkg->id;
            $this->purchase_unit = $primaryPkg->unit?->name ?? $primaryPkg->display_name ?? 'Unit';
            $this->purchase_price = $primaryPkg->purchase_price !== null ? (string)$primaryPkg->purchase_price : ($medicine->purchase_price !== null ? (string)$medicine->purchase_price : '0');
            $this->selling_price = $primaryPkg->sale_price !== null ? (string)$primaryPkg->sale_price : ($medicine->unit_price !== null ? (string)$medicine->unit_price : '0');
        } else {
            $this->packaging_id = '';
            $this->purchase_unit = $medicine->base_unit ?: ($medicine->is_general ? 'Piece' : 'Tablet');
            $this->purchase_price = $medicine->purchase_price !== null ? (string)$medicine->purchase_price : '0';
            $this->selling_price = $medicine->unit_price !== null ? (string)$medicine->unit_price : '0';
        }

        if ($medicine->has_expiry) {
            $this->expiry_date = now()->addYear()->format('Y-m-d');
        } else {
            $this->expiry_date = '';
        }

        $this->showMedicineDropdown = false;

        $this->resetValidation([
            'medicine_id',
            'purchase_price',
            'selling_price',
            'expiry_date',
        ]);
    }

    public function getSelectedMedicineProperty(): ?Medicine
    {
        if (empty($this->medicine_id)) {
            return null;
        }

        return Medicine::with(['category', 'packagings.unit', 'baseUnit'])->find($this->medicine_id);
    }

    public function addItem(): void
    {
        if (empty($this->medicine_id)) {
            $this->addError('medicine_id', 'Please search and select a medicine or product first.');
            return;
        }

        $medicine = Medicine::with(['category', 'packagings.unit', 'baseUnit'])->findOrFail($this->medicine_id);

        if ($medicine->has_expiry && empty($this->expiry_date)) {
            $this->expiry_date = now()->addYear()->format('Y-m-d');
        }

        if ($this->selling_price === '' || $this->selling_price === null) {
            $this->selling_price = $this->purchase_price ?: '0';
        }

        $rules = [
            'medicine_id' => ['required', 'exists:medicines,id'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ];

        if ($medicine->has_expiry) {
            $rules['expiry_date'] = ['required', 'date'];
        } else {
            $rules['expiry_date'] = ['nullable', 'date'];
        }

        $this->validate($rules);

        $quantity = (float) $this->quantity;
        $purchasePrice = (float) $this->purchase_price;
        $sellingPrice = (float) $this->selling_price;

        $pkg = null;
        if (!empty($this->packaging_id)) {
            $pkg = $medicine->packagings->firstWhere('id', (int)$this->packaging_id);
        }
        if (!$pkg) {
            $pkg = $medicine->packagings->firstWhere('display_name', $this->purchase_unit)
                ?: $medicine->packagings->first();
        }

        $conversionToBase = $pkg ? (float)$pkg->conversion_to_base : 1.0;
        $unitId = $pkg?->unit_id ?? $medicine->base_unit_id;
        $unitName = $pkg?->unit?->name ?? $this->purchase_unit ?: ($medicine->base_unit ?: 'Tablet');
        $baseQuantity = round($quantity * $conversionToBase, 4);

        $prefix = $medicine->is_general ? 'GEN-BATCH-' : 'BATCH-';
        $batchNumber = $this->batch_number !== ''
            ? $this->batch_number
            : $prefix . strtoupper(now()->format('YmdHis') . '-' . random_int(100, 999));

        $expiryDate = $medicine->has_expiry ? ($this->expiry_date ?: null) : null;

        $taxPercent = (float) ($this->tax_percent ?: 0);
        $baseTotal = $quantity * $purchasePrice;
        $taxAmount = $baseTotal * ($taxPercent / 100);
        $total = round($baseTotal + $taxAmount, 2);

        $this->cart[] = [
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->name,
            'category_name' => $medicine->category?->name ?? 'General',
            'product_type' => $medicine->product_type ?? 'medicine',
            'packaging_id' => $pkg?->id,
            'unit_id' => $unitId,
            'unit' => $unitName,
            'conversion_to_base' => $conversionToBase,
            'quantity' => $quantity,
            'base_quantity' => $baseQuantity,
            'base_unit' => $medicine->base_unit ?: 'Tablet',
            'selling_price' => $sellingPrice,
            'purchase_price' => $purchasePrice,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'tax_percent' => $taxPercent,
            'total' => $total,
        ];

        $this->resetItem();
        $this->resetValidation();
        $this->showMedicineDropdown = false;
    }

    public function removeItem(int $index): void
    {
        if (!isset($this->cart[$index])) {
            return;
        }

        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
    }

    public function getSubtotalProperty(): float
    {
        return round(
            collect($this->cart)->sum(function ($item) {
                return (float) $item['quantity'] * (float) $item['purchase_price'];
            }),
            2
        );
    }

    public function getTaxTotalProperty(): float
    {
        return round(
            collect($this->cart)->sum(function ($item) {
                $baseTotal = (float) $item['quantity'] * (float) $item['purchase_price'];
                $taxPercent = (float) ($item['tax_percent'] ?? 0);
                return $baseTotal * ($taxPercent / 100);
            }),
            2
        );
    }

    public function getGrandTotalProperty(): float
    {
        return round($this->subtotal + $this->taxTotal, 2);
    }

    public function getTotalQuantityProperty(): float
    {
        return collect($this->cart)->sum(fn ($item) => (float) $item['quantity']);
    }

    public function saveInvoice(): void
    {
        $this->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_number' => ['required', 'string', 'max:255'],
            'purchase_date' => ['required', 'date'],
        ]);

        if (empty($this->cart)) {
            $this->addError('cart', 'Please add at least one medicine.');
            return;
        }

        DB::transaction(function () {
            $invoice = PurchaseInvoice::create([
                'supplier_id' => $this->supplier_id,
                'invoice_number' => $this->invoice_number,
                'purchase_date' => $this->purchase_date,
                'subtotal' => $this->subtotal,
                'tax_amount' => $this->taxTotal,
                'grand_total' => $this->grandTotal,
            ]);

            $supplier = Supplier::findOrFail($this->supplier_id);
            $supplier->increment('opening_balance', $this->grandTotal);

            $stockService = app(\App\Services\StockLedgerService::class);

            foreach ($this->cart as $item) {
                $purchaseItem = PurchaseInvoiceItem::create([
                    'purchase_invoice_id' => $invoice->id,
                    'medicine_id' => $item['medicine_id'],
                    'packaging_id' => $item['packaging_id'] ?? null,
                    'unit_id' => $item['unit_id'] ?? null,
                    'batch_number' => $item['batch_number'],
                    'unit' => $item['unit'] ?? 'base',
                    'conversion_to_base' => $item['conversion_to_base'] ?? 1.0,
                    'quantity' => $item['quantity'],
                    'base_quantity' => $item['base_quantity'] ?? $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['selling_price'],
                    'expiry_date' => $item['expiry_date'],
                    'tax_percent' => $item['tax_percent'] ?? 0,
                    'total' => $item['total'],
                ]);

                // Base unit purchase and selling prices
                $conversion = (float)($item['conversion_to_base'] ?: 1.0);
                $purchasePerBase = round(((float)$item['purchase_price']) / $conversion, 4);
                $sellingPerBase = round(((float)$item['selling_price']) / $conversion, 4);

                // Stock stored strictly in Base Units
                $batch = MedicineBatch::create([
                    'medicine_id' => $item['medicine_id'],
                    'batch_number' => $item['batch_number'],
                    'quantity' => $item['base_quantity'] ?? $item['quantity'],
                    'purchase_price' => $item['purchase_price'],
                    'selling_price' => $item['selling_price'],
                    'purchase_price_per_base_unit' => $purchasePerBase,
                    'selling_price_per_base_unit' => $sellingPerBase,
                    'expiry_date' => $item['expiry_date'],
                    'supplier_id' => $this->supplier_id,
                    'status' => 'active',
                ]);

                // Record in immutable Stock Movements ledger
                $stockService->recordMovement(
                    medicineId: $item['medicine_id'],
                    batchId: $batch->id,
                    type: 'PURCHASE',
                    referenceId: $purchaseItem->id,
                    referenceType: PurchaseInvoiceItem::class,
                    selectedUnitId: $item['unit_id'] ?? null,
                    quantity: $item['quantity'],
                    conversionToBase: $conversion,
                    baseQuantity: $item['base_quantity'] ?? $item['quantity'],
                    userId: auth()->id() ?? 1,
                    notes: "Purchased invoice #{$invoice->invoice_number}: {$item['quantity']} {$item['unit']} (= {$item['base_quantity']} Base Units)"
                );
            }
        });


        $this->dispatch('stockUpdated');
        session()->flash('message', 'Purchase created successfully and stock updated.');

        $this->cart = [];
        $this->supplier_id = '';
        $this->purchase_date = now()->format('Y-m-d');
        $this->generateInvoiceNumber();
        $this->resetItem();
        $this->resetValidation();
        $this->resetPage();
    }

    private function resetItem(): void
    {
        $this->medicine_id = '';
        $this->category_id = '';
        $this->packaging_id = '';
        $this->medicineSearch = '';
        $this->purchase_unit = '';
        $this->batch_number = '';
        $this->quantity = '1';
        $this->purchase_price = '';
        $this->selling_price = '';
        $this->expiry_date = '';
        $this->tax_percent = '0';
        $this->showMedicineDropdown = false;
    }


    /*
    |--------------------------------------------------------------------------
    | RESET HISTORY FILTERS
    |--------------------------------------------------------------------------
    */

    public function resetHistoryFilters(): void
    {
        $this->history_from = '';

        $this->history_to = '';

        $this->history_supplier = '';

        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | HISTORY FILTER UPDATED
    |--------------------------------------------------------------------------
    */

    public function updatedHistoryFrom(): void
    {
        $this->resetPage();
    }


    public function updatedHistoryTo(): void
    {
        $this->resetPage();
    }


    public function updatedHistorySupplier(): void
    {
        $this->resetPage();
    }


    /*
    |--------------------------------------------------------------------------
    | RENDER
    |--------------------------------------------------------------------------
    */

    public function render()
    {
        $suppliers = Supplier::query()
            ->orderBy('name')
            ->get();

        $categories = Category::orderBy('product_type')->orderBy('name')->get();
        $medicineCategories = $categories->whereIn('product_type', ['medicine', 'both']);
        $generalCategories = $categories->whereIn('product_type', ['general', 'both']);

        $medicineQuery = Medicine::with(['category', 'packagings.unit', 'baseUnit']);

        if ($this->category_id !== '') {
            $medicineQuery->where('category_id', $this->category_id);
        }

        if ($this->product_type !== 'all') {
            $medicineQuery->productType($this->product_type);
        }

        $medicineSearch = trim($this->medicineSearch);
        if ($medicineSearch !== '') {
            $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            
            // Allow searching multiple terms (like AddMedicine)
            $terms = array_filter(explode(' ', strtolower($medicineSearch)));
            
            $medicineQuery->where(function ($query) use ($medicineSearch, $terms, $like) {
                $query->where('barcode', '=', $medicineSearch)
                    ->orWhere('name', $like, '%' . $medicineSearch . '%')
                    ->orWhere('generic_name', $like, '%' . $medicineSearch . '%')
                    ->orWhere('brand', $like, '%' . $medicineSearch . '%')
                    ->orWhere(function($subq) use ($terms, $like) {
                        foreach ($terms as $term) {
                            $subq->where(function($q2) use ($term, $like) {
                                $q2->where('name', $like, "%{$term}%")
                                   ->orWhere('strength', $like, "%{$term}%")
                                   ->orWhere('dosage_form', $like, "%{$term}%");
                            });
                        }
                    });
            });
        }

        $medicines = $medicineQuery->orderBy('name')->limit(40)->get();
        $availableUnits = \App\Models\Unit::active()->orderBy('name')->get();
        $selectedMedicine = !empty($this->medicine_id)
            ? Medicine::with(['category', 'packagings.unit', 'baseUnit'])->find($this->medicine_id)
            : null;

        /*
        |--------------------------------------------------------------------------
        | PURCHASE HISTORY
        |--------------------------------------------------------------------------
        */

        $historyQuery = PurchaseInvoice::with('supplier')
            ->latest('purchase_date')
            ->latest('id');

        if ($this->history_from !== '') {
            $historyQuery->whereDate(
                'purchase_date',
                '>=',
                $this->history_from
            );
        }

        if ($this->history_to !== '') {
            $historyQuery->whereDate(
                'purchase_date',
                '<=',
                $this->history_to
            );
        }

        if ($this->history_supplier !== '') {
            $historyQuery->where(
                'supplier_id',
                $this->history_supplier
            );
        }

        $purchases = $historyQuery->paginate(10);

        return view(
            'livewire.admin.purchase-create',
            compact(
                'suppliers',
                'medicines',
                'categories',
                'medicineCategories',
                'generalCategories',
                'availableUnits',
                'selectedMedicine',
                'purchases'
            )
        )->layout('layouts.app');
    }
}