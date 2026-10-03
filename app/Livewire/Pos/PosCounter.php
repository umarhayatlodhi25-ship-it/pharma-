<?php

namespace App\Livewire\Pos;

use App\Models\Customer;
use App\Models\HoldInvoice;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\PackagingService;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class PosCounter extends Component
{
    public $search = '';
    public $productTypeFilter = 'all';
    public $cart = [];
    public $discount = 0;
    public $tax = 0;
    public $paid_amount = 0;
    public $payment_type = 'full'; // full, partial, credit
    public $customer_id = '';
    public $customer_outstanding = 0;
    public $restored_hold_ref = '';

    // Public properties for direct calculation
    public $subtotal = 0;
    public $totalAmount = 0;
    public $changeAmount = 0;

    // Customer Modal Properties
    public $showCustomerModal = false;
    public $new_customer_name;
    public $new_customer_phone;

    // Invoice Print Modal Properties
    public $showInvoiceModal = false;
    public $completedSale = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'productTypeFilter' => ['except' => 'all'],
    ];

    public function setProductTypeFilter($type)
    {
        $this->productTypeFilter = $type;
    }

    public function mount()
    {
        if (request()->has('restore_hold')) {
            $this->restoreHoldInvoice(request('restore_hold'));
        } else {
            $this->calculateTotals();
        }
    }

    public function holdInvoice()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty! Cannot hold an empty invoice.');
            return;
        }

        $customer = $this->customer_id ? Customer::find($this->customer_id) : null;
        $customerName = $customer ? $customer->name : 'Walk-in Customer';

        $hold = HoldInvoice::create([
            'reference_number' => 'HOLD-' . rand(1000, 9999),
            'user_id' => auth()->id() ?? 1,
            'customer_id' => $this->customer_id ?: null,
            'customer_name' => $customerName,
            'cart_data' => $this->cart,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total_amount' => $this->totalAmount,
            'status' => 'held',
        ]);

        $this->clearCart();
        session()->flash('message', "Invoice held successfully! Ref: {$hold->reference_number}");
    }

    public function restoreHoldInvoice($holdId)
    {
        $hold = HoldInvoice::find($holdId);
        if (!$hold) {
            session()->flash('error', 'Held invoice not found.');
            return;
        }

        $this->cart = $hold->cart_data ?? [];
        $this->customer_id = $hold->customer_id ?? '';
        $this->discount = $hold->discount ?? 0;
        $this->tax = $hold->tax ?? 0;
        $this->restored_hold_ref = $hold->reference_number;
        $this->calculateTotals();

        $hold->update(['status' => 'restored']);
        session()->flash('message', "Held Invoice {$hold->reference_number} restored to cart!");
    }

    public function render()
    {
        $medicines = Medicine::with([
                'batches' => function ($q) {
                    $q->where('quantity', '>', 0);
                },
                'packagings.unit',
                'category',
                'inventory',
            ])
            ->when($this->productTypeFilter !== 'all', function($query) {
                $query->productType($this->productTypeFilter);
            })
            ->when($this->search, function($query) {
                $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
                $terms = array_filter(explode(' ', strtolower($this->search)));
                
                $query->where(function($q) use ($like, $terms) {
                    $q->where('barcode', '=', $this->search)
                      ->orWhere('name', $like, '%' . $this->search . '%')
                      ->orWhere('generic_name', $like, '%' . $this->search . '%')
                      ->orWhere('brand', $like, '%' . $this->search . '%')
                      ->orWhereHas('packagings', function($pq) use ($like) {
                          $pq->where('barcode', '=', $this->search);
                      })
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
            })
            ->take(24)
            ->get();

        $customers = Customer::all();

        return view('livewire.pos.pos-counter', compact('medicines', 'customers'));
    }

    /**
     * Add product to cart with chosen packaging unit or barcode scan
     */
    public function addToCart($medicineId, $packagingId = null)
    {
        $medicine = Medicine::with(['batches' => function($q) {
            $q->where('quantity', '>', 0)->oldest('expiry_date');
        }, 'packagings.unit'])->find($medicineId);

        if (!$medicine) {
            session()->flash('error', 'Product not found!');
            return;
        }

        // Determine packaging level
        $pkg = null;
        if ($packagingId) {
            $pkg = $medicine->packagings->firstWhere('id', (int)$packagingId);
        }
        if (!$pkg) {
            // Default to Base Unit or smallest saleable packaging
            $pkg = $medicine->packagings->where('allow_sale', true)->firstWhere('conversion_to_base', 1.0)
                ?: $medicine->packagings->where('allow_sale', true)->first();
        }

        $conversionToBase = $pkg ? (float)$pkg->conversion_to_base : 1.0;
        $unitName = $pkg?->unit?->name ?? $pkg?->display_name ?? ($medicine->base_unit ?: 'Tablet');
        $unitId = $pkg?->unit_id ?? $medicine->base_unit_id;
        $price = $pkg && $pkg->sale_price !== null ? (float)$pkg->sale_price : (float)($medicine->unit_price ?? 0);

        // FEFO: Find oldest valid, non-expired batch with available stock
        $now = now()->toDateString();
        $availableBatches = $medicine->batches->filter(function($b) use ($medicine, $now) {
            if ($b->quantity <= 0) return false;
            if ($medicine->has_expiry && $b->expiry_date && $b->expiry_date->format('Y-m-d') < $now) {
                return false;
            }
            return true;
        })->sortBy('expiry_date');

        $batch = $availableBatches->first();

        // Calculate available stock in base units
        $totalBaseAvailable = $availableBatches->sum('quantity');
        $existingCartBase = collect($this->cart)->where('medicine_id', $medicine->id)->sum('base_qty');
        $requiredBase = $existingCartBase + $conversionToBase;

        if ($totalBaseAvailable < $requiredBase) {
            session()->flash('error', "Insufficient stock for {$medicine->name}! Available: {$totalBaseAvailable} {$medicine->base_unit}s.");
            return;
        }

        if (!$batch) {
            session()->flash('error', "No valid/unexpired batch available for {$medicine->name}!");
            return;
        }

        $cartKey = $medicine->id . '_' . ($pkg ? $pkg->id : 'base');

        if (isset($this->cart[$cartKey])) {
            $newQty = $this->cart[$cartKey]['qty'] + 1;
            $this->cart[$cartKey]['qty'] = $newQty;
            $this->cart[$cartKey]['base_qty'] = round($newQty * $conversionToBase, 4);
            $this->cart[$cartKey]['subtotal'] = round($newQty * $price, 2);
        } else {
            $this->cart[$cartKey] = [
                'cart_key' => $cartKey,
                'medicine_id' => $medicine->id,
                'batch_id' => $batch->id,
                'packaging_id' => $pkg?->id,
                'unit_id' => $unitId,
                'name' => $medicine->name,
                'generic_name' => $medicine->generic_name ?? ($medicine->is_general ? 'General Store' : 'Medicine'),
                'batch_number' => $batch->batch_number ?? 'DEFAULT',
                'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('d M Y') : 'N/A',
                'unit' => $unitName,
                'conversion_to_base' => $conversionToBase,
                'price' => (float) $price,
                'qty' => 1,
                'base_qty' => (float) $conversionToBase,
                'base_unit' => $medicine->base_unit ?: 'Tablet',
                'subtotal' => (float) $price,
                'total_base_available' => $totalBaseAvailable,
            ];
        }

        $this->calculateTotals();
    }

    /**
     * Handle barcode scan in POS search input
     */
    public function scanBarcode($barcode)
    {
        $barcode = trim($barcode);
        if (empty($barcode)) {
            return;
        }

        $packagingService = app(PackagingService::class);
        $result = $packagingService->findByBarcode($barcode);

        if ($result) {
            $this->addToCart($result['medicine']->id, $result['packaging']?->id);
            $this->search = '';
            session()->flash('message', "Scanned: {$result['medicine']->name} ({$result['packaging']?->display_name})");
        } else {
            session()->flash('error', "Barcode '{$barcode}' not found!");
        }
    }

    /**
     * Switch packaging unit for an item currently in the cart
     */
    public function updateCartPackaging($cartKey, $newPackagingId)
    {
        if (!isset($this->cart[$cartKey])) {
            return;
        }

        $item = $this->cart[$cartKey];
        $medicine = Medicine::with(['packagings.unit', 'batches'])->find($item['medicine_id']);

        if (!$medicine) {
            return;
        }

        $newPkg = $medicine->packagings->firstWhere('id', (int)$newPackagingId);
        if (!$newPkg) {
            return;
        }

        $conversionToBase = (float)$newPkg->conversion_to_base;
        $price = $newPkg->sale_price !== null ? (float)$newPkg->sale_price : (float)$medicine->unit_price;
        $unitName = $newPkg->unit?->name ?? $newPkg->display_name ?? 'Unit';
        $newBaseQty = round($item['qty'] * $conversionToBase, 4);

        // Check stock availability
        $otherBaseQty = collect($this->cart)->reject(fn($c, $k) => $k === $cartKey)->where('medicine_id', $medicine->id)->sum('base_qty');
        $totalBaseAvailable = $medicine->batches->where('quantity', '>', 0)->sum('quantity');

        if ($totalBaseAvailable < ($otherBaseQty + $newBaseQty)) {
            session()->flash('error', "Insufficient stock for unit change! Available: {$totalBaseAvailable} {$medicine->base_unit}s.");
            return;
        }

        unset($this->cart[$cartKey]);

        $newCartKey = $medicine->id . '_' . $newPkg->id;

        $item['cart_key'] = $newCartKey;
        $item['packaging_id'] = $newPkg->id;
        $item['unit_id'] = $newPkg->unit_id;
        $item['unit'] = $unitName;
        $item['conversion_to_base'] = $conversionToBase;
        $item['price'] = $price;
        $item['base_qty'] = $newBaseQty;
        $item['subtotal'] = round($item['qty'] * $price, 2);
        $item['total_base_available'] = $totalBaseAvailable;

        $this->cart[$newCartKey] = $item;
        $this->calculateTotals();
    }

    public function incrementQty($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            $item = $this->cart[$cartKey];
            $medicine = Medicine::with('batches')->find($item['medicine_id']);

            if ($medicine) {
                $newQty = $item['qty'] + 1;
                $newBase = round($newQty * $item['conversion_to_base'], 4);

                $otherBase = collect($this->cart)->reject(fn($c, $k) => $k === $cartKey)->where('medicine_id', $medicine->id)->sum('base_qty');
                $totalAvailable = $medicine->batches->where('quantity', '>', 0)->sum('quantity');

                if ($totalAvailable < ($otherBase + $newBase)) {
                    session()->flash('error', "Insufficient stock! Only {$totalAvailable} {$medicine->base_unit}s available in total.");
                    return;
                }

                $this->cart[$cartKey]['qty'] = $newQty;
                $this->cart[$cartKey]['base_qty'] = $newBase;
                $this->cart[$cartKey]['subtotal'] = round($newQty * $item['price'], 2);
                $this->cart[$cartKey]['total_base_available'] = $totalAvailable;
                $this->calculateTotals();
            }
        }
    }

    public function decrementQty($cartKey)
    {
        if (isset($this->cart[$cartKey])) {
            if ($this->cart[$cartKey]['qty'] > 1) {
                $this->cart[$cartKey]['qty']--;
                $this->cart[$cartKey]['base_qty'] = round($this->cart[$cartKey]['qty'] * $this->cart[$cartKey]['conversion_to_base'], 4);
                $this->cart[$cartKey]['subtotal'] = round($this->cart[$cartKey]['qty'] * $this->cart[$cartKey]['price'], 2);
            } else {
                unset($this->cart[$cartKey]);
            }
            $this->calculateTotals();
        }
    }

    public function removeItem($cartKey) 
    { 
        unset($this->cart[$cartKey]); 
        $this->calculateTotals();
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->discount = 0;
        $this->tax = 0;
        $this->paid_amount = 0;
        $this->calculateTotals();
    }

    public function updatedDiscount()
    {
        $this->calculateTotals();
    }

    public function updatedPaidAmount()
    {
        if ($this->payment_type === 'full') {
            $this->payment_type = 'partial';
        }
        $this->calculateTotals();
    }

    public function updatedPaymentType()
    {
        $this->calculateTotals();
    }

    public function updatedCustomerId()
    {
        if ($this->customer_id) {
            $customer = Customer::find($this->customer_id);
            $this->customer_outstanding = $customer ? $customer->outstanding_balance : 0;
        } else {
            $this->customer_outstanding = 0;
            $this->payment_type = 'full';
        }
        $this->calculateTotals();
    }

    public function calculateTotals()
    {
        $this->subtotal = round(collect($this->cart)->sum(fn($i) => ($i['price'] ?? 0) * ($i['qty'] ?? 1)), 2);
        $disc = is_numeric($this->discount) ? (float)$this->discount : 0;
        $tx = is_numeric($this->tax) ? (float)$this->tax : 0;
        
        $this->totalAmount = max(0, round(($this->subtotal - $disc) + $tx, 2));

        if ($this->payment_type === 'full') {
            $this->paid_amount = $this->totalAmount;
        } elseif ($this->payment_type === 'credit') {
            $this->paid_amount = 0;
        }
        
        $paid = is_numeric($this->paid_amount) ? (float)$this->paid_amount : 0;
        $this->changeAmount = max(0, round($paid - $this->totalAmount, 2));
    }

    public function openCustomerModal()
    {
        $this->showCustomerModal = true;
    }

    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
        $this->new_customer_name = '';
        $this->new_customer_phone = '';
    }

    public function saveCustomer()
    {
        $this->validate([
            'new_customer_name' => 'required|string|max:255',
            'new_customer_phone' => 'required|string|max:20',
        ]);

        $customer = Customer::create([
            'name' => $this->new_customer_name,
            'phone' => $this->new_customer_phone,
        ]);

        $this->customer_id = $customer->id;
        $this->closeCustomerModal();
    }

    public function checkout()
    {
        if (empty($this->cart)) {
            session()->flash('error', 'Cart is empty!');
            return;
        }

        try {
            if (!$this->customer_id && $this->payment_type !== 'full') {
                session()->flash('error', 'Only registered customers can use partial payment or credit.');
                return;
            }

            if ($this->payment_type === 'partial' && (empty($this->paid_amount) || $this->paid_amount <= 0 || $this->paid_amount > $this->totalAmount)) {
                 session()->flash('error', 'For partial payments, the paid amount must be greater than 0 and less than or equal to the total amount.');
                 return;
            }

            $lastSaleId = null;

            DB::transaction(function () use (&$lastSaleId) {
                \App\Models\User::firstOrCreate(
                    ['id' => 1],
                    ['name' => 'Admin', 'email' => 'admin@pharmacy.com', 'password' => bcrypt('password')]
                );

                $paid = is_numeric($this->paid_amount) ? (float)$this->paid_amount : 0;
                
                $status = 'paid';
                if ($paid == 0 && $this->totalAmount > 0) {
                    $status = 'unpaid';
                } elseif ($paid < $this->totalAmount) {
                    $status = 'partial';
                }

                $sale = Sale::create([
                    'invoice_number' => 'INV-' . time(),
                    'user_id' => auth()->id() ?? 1,
                    'customer_id' => $this->customer_id ?: null,
                    'subtotal' => $this->subtotal,
                    'total_amount' => $this->totalAmount,
                    'paid_amount' => $paid,
                    'change_amount' => $this->changeAmount,
                    'payment_status' => $status,
                ]);

                if ($paid > 0 && $this->customer_id) {
                    \App\Models\CustomerPayment::create([
                        'customer_id' => $this->customer_id,
                        'sale_id' => $sale->id,
                        'amount' => $paid,
                        'payment_method' => 'cash',
                        'payment_date' => now(),
                        'user_id' => auth()->id() ?? 1,
                        'remarks' => 'Initial payment for ' . $sale->invoice_number,
                    ]);
                }

                $lastSaleId = $sale->id;
                $packagingService = app(PackagingService::class);
                $stockLedgerService = app(StockLedgerService::class);

                foreach ($this->cart as $item) {
                    $medicineId = $item['medicine_id'];
                    $baseQuantityNeeded = (float)$item['base_qty'];

                    // Allocate batches using FEFO
                    $allocatedBatches = $packagingService->allocateBatchesFefo($medicineId, $baseQuantityNeeded);

                    foreach ($allocatedBatches as $allocation) {
                        $batch = $allocation['batch'];
                        $deductedBase = (float)($allocation['allocated_quantity'] ?? $allocation['base_quantity'] ?? $allocation['allocated_base_quantity'] ?? 0);

                        // Calculate item proportion if split across batches
                        $itemQtyProportion = $baseQuantityNeeded > 0 ? ($deductedBase / $baseQuantityNeeded) * $item['qty'] : $item['qty'];
                        $subtotalProportion = round($itemQtyProportion * $item['price'], 2);

                        $saleItem = SaleItem::create([
                            'sale_id' => $sale->id,
                            'medicine_id' => $medicineId,
                            'batch_id' => $batch->id,
                            'packaging_id' => $item['packaging_id'] ?? null,
                            'unit_id' => $item['unit_id'] ?? null,
                            'unit' => $item['unit'] ?? 'base',
                            'conversion_to_base' => $item['conversion_to_base'] ?? 1.0,
                            'quantity' => round($itemQtyProportion, 4),
                            'base_quantity' => $deductedBase,
                            'unit_price' => $item['price'],
                            'subtotal' => $subtotalProportion,
                        ]);

                        // 1. Decrement batch stock in strictly Base Units
                        $batch->decrement('quantity', $deductedBase);

                        // 2. Record immutable stock movement
                        $stockLedgerService->recordMovement(
                            medicineId: $medicineId,
                            batchId: $batch->id,
                            type: 'SALE',
                            referenceId: $saleItem->id,
                            referenceType: SaleItem::class,
                            selectedUnitId: $item['unit_id'] ?? null,
                            quantity: $itemQtyProportion,
                            conversionToBase: $item['conversion_to_base'] ?? 1.0,
                            baseQuantity: $deductedBase,
                            userId: auth()->id() ?? 1,
                            notes: "POS Checkout #{$sale->invoice_number}: {$itemQtyProportion} {$item['unit']} (batch {$batch->batch_number})"
                        );
                    }
                }
            });

            $this->clearCart();
            $this->completedSale = Sale::with(['items.medicine', 'items.packaging', 'customer', 'user'])->find($lastSaleId);
            $this->showInvoiceModal = true;
            session()->flash('message', 'Sale Successful & Stock Deducted in Base Units!');
            session()->flash('last_sale_id', $lastSaleId);

        } catch (\Exception $e) {
            session()->flash('error', 'Checkout Error: ' . $e->getMessage());
        }
    }

    public function closeInvoiceModal()
    {
        $this->showInvoiceModal = false;
        $this->completedSale = null;
    }
}