<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\MedicinePackaging;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Services\PackagingService;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class MedicineList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $productTypeFilter = 'all';
    public string $categoryFilter = '';
    public string $supplierFilter = '';
    public string $stockFilter = '';
    public string $expiryFilter = '';
    public int $perPage = 15;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public array $selectedMedicines = [];
    public bool $selectAll = false;
    public array $expandedRows = [];

    // Modals
    public bool $showViewModal = false;
    public ?int $viewMedicineId = null;

    public bool $showEditModal = false;
    public ?int $editMedicineId = null;
    public string $edit_name = '';
    public string $edit_generic_name = '';
    public string $edit_brand = '';
    public string $edit_strength = '';
    public string $edit_dosage_form = '';
    public string $edit_category_id = '';
    public string $edit_manufacturer = '';
    public string $edit_barcode = '';
    public string $edit_sku = '';
    public string $edit_alert_quantity = '10';
    public string $edit_reorder_level = '10';
    public string $edit_tax_rate = '0';
    public string $edit_status = 'active';
    public string $edit_product_type = 'medicine';
    public string $edit_primary_unit = '';
    public string $edit_secondary_unit = '';
    public string $edit_base_unit = '';
    public string $edit_unit_price = '';
    public string $edit_purchase_price = '';

    public bool $showAddBatchModal = false;
    public ?int $batchMedicineId = null;
    public string $batchMedicineName = '';
    public string $new_batch_number = '';
    public string $new_batch_supplier_id = '';
    public string $new_batch_quantity = '';
    public string $new_batch_unit = 'base';
    public string $new_batch_purchase_price = '';
    public string $new_batch_selling_price = '';
    public string $new_batch_expiry_date = '';

    public bool $showAdjustStockModal = false;
    public ?int $adjustBatchId = null;
    public ?int $adjustMedicineId = null;
    public string $adjustBatchNumber = '';
    public string $adjustMedicineName = '';
    public float $adjustCurrentQty = 0;
    public string $adjustType = 'ADJUSTMENT_IN';
    public string $adjustQuantity = '';
    public string $adjustNotes = '';

    public bool $showDeleteModal = false;
    public ?int $deleteMedicineId = null;
    public string $deleteMedicineName = '';
    public int $deleteMedicineStock = 0;
    public int $deleteMedicineBatchesCount = 0;
    public bool $deleteHasSales = false;
    public bool $showBulkDeleteModal = false;

    public bool $showBarcodeModal = false;
    public ?int $barcodeMedicineId = null;
    public int $barcodeCopies = 1;
    public bool $barcodeShowPrice = true;
    public bool $barcodeShowExpiry = true;
    public bool $barcodeShowGeneric = false;

    public function mount(): void
    {
        Medicine::where('status', 'inactive')
            ->orWhereIn('name', ['Paracetamol 500mg', 'Panadol Extra', 'Lux Beauty Soap 100g', 'Nestle Pure Life 1.5L'])
            ->get()
            ->each(function ($med) {
                DB::table('sale_items')->where('medicine_id', $med->id)->delete();
                DB::table('sales_return_items')->where('medicine_id', $med->id)->delete();
                DB::table('purchase_return_items')->where('medicine_id', $med->id)->delete();
                DB::table('purchase_invoice_items')->where('medicine_id', $med->id)->delete();
                if (\Illuminate\Support\Facades\Schema::hasTable('hold_invoices')) {
                    DB::table('hold_invoices')->where('medicine_id', $med->id)->delete();
                }
                StockMovement::where('medicine_id', $med->id)->delete();
                MedicineBatch::where('medicine_id', $med->id)->delete();
                MedicinePackaging::where('medicine_id', $med->id)->delete();
                Inventory::where('medicine_id', $med->id)->delete();
                $med->delete();
            });
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingProductTypeFilter(): void { $this->resetPage(); }
    public function updatingCategoryFilter(): void { $this->resetPage(); }
    public function updatingSupplierFilter(): void { $this->resetPage(); }
    public function updatingStockFilter(): void { $this->resetPage(); }
    public function updatingExpiryFilter(): void { $this->resetPage(); }
    public function updatingPerPage(): void { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->resetPage();
    }

    public function updatedSelectAll($value): void
    {
        if ($value) {
            $this->selectedMedicines = Medicine::latest()->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selectedMedicines = [];
        }
    }

    public function toggleExpandRow(int $id): void
    {
        if (in_array($id, $this->expandedRows)) {
            $this->expandedRows = array_values(array_diff($this->expandedRows, [$id]));
        } else {
            $this->expandedRows[] = $id;
        }
    }

    public function openViewModal(int $id): void
    {
        $this->viewMedicineId = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewMedicineId = null;
    }

    public function openBarcodeModal(int $id): void
    {
        $this->barcodeMedicineId = $id;
        $this->barcodeCopies = 1;
        $this->showBarcodeModal = true;
    }

    public function closeBarcodeModal(): void
    {
        $this->showBarcodeModal = false;
        $this->barcodeMedicineId = null;
    }

    public function openEditModal(int $id): void
    {
        $med = Medicine::with(['packagings.unit', 'category'])->find($id);
        if (!$med) return;

        $this->editMedicineId = $med->id;
        $this->edit_name = $med->name;
        $this->edit_generic_name = $med->generic_name ?? '';
        $this->edit_brand = $med->brand ?? '';
        $this->edit_strength = $med->strength ?? '';
        $this->edit_dosage_form = $med->dosage_form ?? '';
        $this->edit_category_id = (string)($med->category_id ?? '');
        $this->edit_manufacturer = $med->manufacturer ?? '';
        $this->edit_barcode = $med->barcode ?? '';
        $this->edit_sku = $med->sku ?? '';
        $this->edit_alert_quantity = (string)($med->alert_quantity ?? 10);
        $this->edit_reorder_level = (string)($med->reorder_level ?? 10);
        $this->edit_tax_rate = (string)($med->tax_rate ?? 0);
        $this->edit_status = $med->status ?? 'active';
        $this->edit_product_type = $med->product_type ?? 'medicine';
        $this->edit_primary_unit = $med->primary_unit ?? '';
        $this->edit_secondary_unit = $med->secondary_unit ?? '';
        $this->edit_base_unit = $med->base_unit ?? 'Tablet';
        $this->edit_unit_price = (string)($med->unit_price ?? 0);
        $this->edit_purchase_price = (string)($med->purchase_price ?? 0);

        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editMedicineId = null;
    }

    public function updateMedicine(): void
    {
        if (!$this->editMedicineId) return;

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_category_id' => 'required|exists:categories,id',
            'edit_unit_price' => 'required|numeric|min:0',
            'edit_purchase_price' => 'nullable|numeric|min:0',
        ]);

        $medicine = Medicine::find($this->editMedicineId);
        if (!$medicine) return;

        DB::transaction(function () use ($medicine) {
            $basePrice = (float)$this->edit_unit_price;
            $basePurchasePrice = (float)($this->edit_purchase_price ?: 0);

            $medicine->update([
                'name' => trim($this->edit_name),
                'generic_name' => $this->edit_product_type === 'medicine' ? ($this->edit_generic_name ?: null) : null,
                'brand' => $this->edit_brand ?: null,
                'strength' => $this->edit_strength ?: null,
                'dosage_form' => $this->edit_dosage_form ?: null,
                'category_id' => $this->edit_category_id,
                'manufacturer' => $this->edit_manufacturer ?: null,
                'barcode' => $this->edit_barcode ?: null,
                'sku' => $this->edit_sku ?: null,
                'alert_quantity' => (int)($this->edit_alert_quantity ?: 10),
                'reorder_level' => (int)($this->edit_reorder_level ?: 10),
                'tax_rate' => (float)($this->edit_tax_rate ?: 0),
                'status' => $this->edit_status ?: 'active',
                'unit_price' => $basePrice,
                'purchase_price' => $basePurchasePrice,
                'base_unit_selling_price' => $basePrice,
            ]);

            MedicinePackaging::where('medicine_id', $medicine->id)
                ->where('conversion_to_base', 1.0)
                ->update([
                    'sale_price' => $basePrice,
                    'purchase_price' => $basePurchasePrice,
                    'barcode' => $this->edit_barcode ?: null,
                ]);
        });

        session()->flash('message', "Product '{$medicine->name}' updated successfully.");
        $this->closeEditModal();
    }

    public function openAddBatchModal(int $id): void
    {
        $med = Medicine::find($id);
        if (!$med) return;

        $this->batchMedicineId = $med->id;
        $this->batchMedicineName = $med->name;
        $this->new_batch_number = ($med->product_type === 'general' ? 'GEN-' : 'BAT-') . strtoupper(Str::random(6));
        $this->new_batch_supplier_id = '';
        $this->new_batch_quantity = '';
        $this->new_batch_unit = 'base';
        $this->new_batch_purchase_price = (string)($med->purchase_price ?? '');
        $this->new_batch_selling_price = (string)($med->unit_price ?? '');
        $this->new_batch_expiry_date = '';

        $this->showAddBatchModal = true;
    }

    public function closeAddBatchModal(): void
    {
        $this->showAddBatchModal = false;
        $this->batchMedicineId = null;
    }

    public function saveNewBatch(): void
    {
        if (!$this->batchMedicineId) return;

        $medicine = Medicine::find($this->batchMedicineId);
        if (!$medicine) return;

        $this->validate([
            'new_batch_number' => 'required|string|max:255',
            'new_batch_quantity' => 'required|numeric|min:0.01',
            'new_batch_selling_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($medicine) {
            $baseQty = (float)$this->new_batch_quantity;
            $sellPrice = (float)$this->new_batch_selling_price;
            $costPrice = (float)($this->new_batch_purchase_price ?: $medicine->purchase_price ?: 0);

            $batch = MedicineBatch::create([
                'medicine_id' => $medicine->id,
                'supplier_id' => $this->new_batch_supplier_id ?: null,
                'batch_number' => trim($this->new_batch_number),
                'quantity' => $baseQty,
                'purchase_price' => $costPrice,
                'selling_price' => $sellPrice,
                'purchase_price_per_base_unit' => $costPrice,
                'selling_price_per_base_unit' => $sellPrice,
                'expiry_date' => $medicine->has_expiry ? ($this->new_batch_expiry_date ?: null) : null,
                'status' => 'active',
            ]);

            app(StockLedgerService::class)->recordMovement(
                medicineId: $medicine->id,
                batchId: $batch->id,
                type: 'PURCHASE',
                referenceId: null,
                referenceType: null,
                selectedUnitId: $medicine->base_unit_id,
                quantity: $baseQty,
                conversionToBase: 1.0,
                baseQuantity: $baseQty,
                userId: auth()->id() ?? 1,
                notes: "Restocked via Inventory List: Batch {$batch->batch_number}"
            );
        });

        session()->flash('message', "New batch added for '{$medicine->name}'.");
        $this->closeAddBatchModal();
    }

    public function openAdjustStockModal(int $batchId): void
    {
        $batch = MedicineBatch::with('medicine')->find($batchId);
        if (!$batch) return;

        $this->adjustBatchId = $batch->id;
        $this->adjustMedicineId = $batch->medicine_id;
        $this->adjustBatchNumber = $batch->batch_number;
        $this->adjustMedicineName = $batch->medicine->name ?? 'Medicine';
        $this->adjustCurrentQty = (float)$batch->quantity;
        $this->adjustType = 'ADJUSTMENT_IN';
        $this->adjustQuantity = '';
        $this->adjustNotes = '';

        $this->showAdjustStockModal = true;
    }

    public function closeAdjustStockModal(): void
    {
        $this->showAdjustStockModal = false;
        $this->adjustBatchId = null;
    }

    public function saveStockAdjustment(): void
    {
        if (!$this->adjustBatchId) return;

        $this->validate([
            'adjustQuantity' => 'required|numeric|min:0.01',
            'adjustType' => 'required|in:ADJUSTMENT_IN,ADJUSTMENT_OUT,DAMAGE,EXPIRED',
        ]);

        $batch = MedicineBatch::with('medicine')->find($this->adjustBatchId);
        if (!$batch) return;

        $qty = (float)$this->adjustQuantity;
        $current = (float)$batch->quantity;

        if (in_array($this->adjustType, ['ADJUSTMENT_OUT', 'DAMAGE', 'EXPIRED']) && $qty > $current) {
            $this->addError('adjustQuantity', "Adjustment quantity cannot exceed current stock ({$current}).");
            return;
        }

        DB::transaction(function () use ($batch, $qty) {
            $isIncoming = in_array($this->adjustType, ['ADJUSTMENT_IN']);
            $newQty = $isIncoming ? ($batch->quantity + $qty) : max(0, $batch->quantity - $qty);

            $batch->update([
                'quantity' => $newQty,
                'status' => $newQty <= 0 ? 'depleted' : 'active',
            ]);

            app(StockLedgerService::class)->recordMovement(
                medicineId: $batch->medicine_id,
                batchId: $batch->id,
                type: $this->adjustType,
                referenceId: null,
                referenceType: null,
                selectedUnitId: $batch->medicine?->base_unit_id,
                quantity: $qty,
                conversionToBase: 1.0,
                baseQuantity: $qty,
                userId: auth()->id() ?? 1,
                notes: $this->adjustNotes ?: "Stock Adjustment ({$this->adjustType})"
            );
        });

        $this->dispatch('stockUpdated');
        session()->flash('message', "Stock adjusted successfully for Batch {$batch->batch_number}.");
        $this->closeAdjustStockModal();
    }

    public function confirmDelete(int $id): void
    {
        $medicine = Medicine::with(['batches', 'inventory'])->find($id);
        if (!$medicine) return;

        $this->deleteMedicineId = $id;
        $this->deleteMedicineName = $medicine->name;
        $this->deleteMedicineStock = $medicine->batches->sum('quantity');
        $this->deleteMedicineBatchesCount = $medicine->batches->count();
        $this->deleteHasSales = DB::table('sale_items')->where('medicine_id', $id)->exists()
            || DB::table('purchase_invoice_items')->where('medicine_id', $id)->exists();

        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deleteMedicineId = null;
    }

    public function deleteMedicine(): void
    {
        if (!$this->deleteMedicineId) return;

        $medicine = Medicine::find($this->deleteMedicineId);
        if (!$medicine) {
            $this->closeDeleteModal();
            return;
        }

        try {
            DB::transaction(function () use ($medicine) {
                $id = $medicine->id;

                DB::table('sale_items')->where('medicine_id', $id)->delete();
                DB::table('sales_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_invoice_items')->where('medicine_id', $id)->delete();
                if (\Illuminate\Support\Facades\Schema::hasTable('hold_invoices')) {
                    DB::table('hold_invoices')->where('medicine_id', $id)->delete();
                }

                StockMovement::where('medicine_id', $id)->delete();
                MedicineBatch::where('medicine_id', $id)->delete();
                MedicinePackaging::where('medicine_id', $id)->delete();
                Inventory::where('medicine_id', $id)->delete();

                $medicineName = $medicine->name;
                $medicine->delete();

                session()->flash('message', "Product '{$medicineName}' deleted permanently.");
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Could not delete product: ' . $e->getMessage());
        }

        $this->closeDeleteModal();
        $this->resetPage();
    }

    public function deleteBatch(int $batchId): void
    {
        try {
            DB::transaction(function () use ($batchId) {
                $batch = MedicineBatch::find($batchId);
                if (!$batch) return;

                $medicineId = $batch->medicine_id;
                $batchNum = $batch->batch_number;

                DB::table('sale_items')->where('batch_id', $batchId)->delete();
                DB::table('sales_return_items')->where('batch_id', $batchId)->delete();
                DB::table('purchase_return_items')->where('batch_id', $batchId)->delete();
                StockMovement::where('batch_id', $batchId)->delete();
                $batch->delete();

                app(StockLedgerService::class)->syncInventory($medicineId);
                session()->flash('message', "Batch '{$batchNum}' deleted permanently.");
            });
        } catch (\Exception $e) {
            session()->flash('error', 'Error deleting batch: ' . $e->getMessage());
        }
    }

    public function confirmBulkDelete(): void
    {
        if (empty($this->selectedMedicines)) {
            session()->flash('error', 'No products selected.');
            return;
        }
        $this->showBulkDeleteModal = true;
    }

    public function closeBulkDeleteModal(): void
    {
        $this->showBulkDeleteModal = false;
    }

    public function bulkDelete(): void
    {
        if (empty($this->selectedMedicines)) {
            $this->closeBulkDeleteModal();
            return;
        }

        $count = 0;

        DB::transaction(function () use (&$count) {
            foreach ($this->selectedMedicines as $id) {
                $medicine = Medicine::find($id);
                if (!$medicine) continue;

                DB::table('sale_items')->where('medicine_id', $id)->delete();
                DB::table('sales_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_return_items')->where('medicine_id', $id)->delete();
                DB::table('purchase_invoice_items')->where('medicine_id', $id)->delete();
                if (\Illuminate\Support\Facades\Schema::hasTable('hold_invoices')) {
                    DB::table('hold_invoices')->where('medicine_id', $id)->delete();
                }

                StockMovement::where('medicine_id', $id)->delete();
                MedicineBatch::where('medicine_id', $id)->delete();
                MedicinePackaging::where('medicine_id', $id)->delete();
                Inventory::where('medicine_id', $id)->delete();

                $medicine->delete();
                $count++;
            }
        });

        $this->selectedMedicines = [];
        $this->selectAll = false;
        $this->closeBulkDeleteModal();

        session()->flash('message', "{$count} products deleted permanently.");
        $this->resetPage();
    }

    public function bulkUpdateStatus(string $status): void
    {
        if (empty($this->selectedMedicines)) return;

        Medicine::whereIn('id', $this->selectedMedicines)->update(['status' => $status]);
        session()->flash('message', count($this->selectedMedicines) . " products updated to '{$status}'.");
        $this->selectedMedicines = [];
        $this->selectAll = false;
    }

    public function exportCsv()
    {
        $medicines = Medicine::with(['category', 'batches', 'packagings.unit', 'inventory'])->get();
        $filename = 'inventory-products-' . date('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($medicines) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'ID', 'Type', 'Name', 'Generic Name', 'Brand', 'Category', 'Base Unit', 
                'Total Stock', 'Purchase Price (PKR)', 'Selling Price (PKR)', 'Barcode', 
                'SKU', 'Batches Count', 'Status'
            ]);

            foreach ($medicines as $m) {
                $totalStock = $m->batches->sum('quantity');
                $firstBatch = $m->batches->first();
                fputcsv($file, [
                    $m->id,
                    $m->product_type,
                    $m->name,
                    $m->generic_name ?? '',
                    $m->brand ?? '',
                    $m->category?->name ?? '',
                    $m->base_unit,
                    $totalStock,
                    $firstBatch->purchase_price ?? $m->purchase_price ?? 0,
                    $firstBatch->selling_price ?? $m->unit_price ?? 0,
                    $m->barcode ?? '',
                    $m->sku ?? '',
                    $m->batches->count(),
                    $m->status ?? 'active',
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        $query = Medicine::with(['category', 'batches.supplier', 'packagings.unit', 'inventory']);

        if (trim($this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('generic_name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('batches', function ($bq) use ($search) {
                        $bq->where('batch_number', 'like', "%{$search}%");
                    });
            });
        }

        if ($this->productTypeFilter !== 'all') {
            $query->productType($this->productTypeFilter);
        }

        if ($this->categoryFilter !== '') {
            $query->where('category_id', $this->categoryFilter);
        }

        if ($this->supplierFilter !== '') {
            $query->whereHas('batches', function ($q) {
                $q->where('supplier_id', $this->supplierFilter);
            });
        }

        if ($this->stockFilter === 'out_of_stock') {
            $query->whereDoesntHave('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        } elseif ($this->stockFilter === 'in_stock') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        } elseif ($this->stockFilter === 'expired') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0)
                    ->whereDate('expiry_date', '<', now()->toDateString());
            });
        } elseif ($this->stockFilter === 'low_stock') {
            $query->whereHas('batches', function ($q) {
                $q->where('quantity', '>', 0);
            });
        }

        if ($this->sortField === 'name') {
            $query->orderBy('name', $this->sortDirection);
        } elseif ($this->sortField === 'unit_price') {
            $query->orderBy('unit_price', $this->sortDirection);
        } else {
            $query->orderBy('created_at', $this->sortDirection);
        }

        $medicines = $query->paginate($this->perPage);

        $allMedicines = Medicine::with('batches')->get();
        $totalMedicines = $allMedicines->count();
        $totalMedicineProducts = $allMedicines->where('product_type', 'medicine')->count();
        $totalGeneralProducts = $allMedicines->where('product_type', 'general')->count();
        $totalStock = $allMedicines->sum(fn ($m) => $m->batches->sum('quantity'));
        $totalStockValue = $allMedicines->sum(fn ($m) => $m->batches->sum(fn ($b) => (float)($b->quantity * ($b->selling_price_per_base_unit ?? $b->selling_price ?? 0))));

        $lowStock = $allMedicines->filter(fn ($m) => $m->batches->sum('quantity') > 0 && $m->batches->sum('quantity') <= ($m->reorder_level ?? $m->alert_quantity ?? 10))->count();

        $expired = $allMedicines->filter(fn ($m) => $m->batches->where('quantity', '>', 0)->contains(fn ($b) => $b->expiry_date && $b->expiry_date->isPast()))->count();

        $categories = Category::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();
        $availableUnits = Unit::active()->orderBy('name')->get();

        $viewMedicine = $this->viewMedicineId ? Medicine::with(['category', 'batches.supplier', 'packagings.unit', 'inventory'])->find($this->viewMedicineId) : null;
        $barcodeMedicine = $this->barcodeMedicineId ? Medicine::with(['batches', 'packagings'])->find($this->barcodeMedicineId) : null;

        return view('livewire.admin.medicine-list', [
            'medicines' => $medicines,
            'categories' => $categories,
            'suppliers' => $suppliers,
            'availableUnits' => $availableUnits,
            'totalMedicines' => $totalMedicines,
            'totalMedicineProducts' => $totalMedicineProducts,
            'totalGeneralProducts' => $totalGeneralProducts,
            'totalStock' => $totalStock,
            'totalStockValue' => $totalStockValue,
            'lowStock' => $lowStock,
            'expired' => $expired,
            'viewMedicine' => $viewMedicine,
            'barcodeMedicine' => $barcodeMedicine,
        ])->layout('layouts.app');
    }
}