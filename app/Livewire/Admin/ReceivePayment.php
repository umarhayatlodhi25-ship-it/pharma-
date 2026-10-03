<?php

namespace App\Livewire\Admin;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\CustomerPayment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ReceivePayment extends Component
{
    public $customer_id;
    public $payment_amount;
    public $payment_method = 'cash';
    public $remarks;

    public $customers = [];
    public $outstanding_balance = 0;
    public $unpaid_invoices = [];

    public function mount($customerId = null)
    {
        $this->customers = Customer::orderBy('name')->get();
        if ($customerId) {
            $this->customer_id = $customerId;
            $this->updatedCustomerId();
        }
    }

    public function updatedCustomerId()
    {
        if ($this->customer_id) {
            $customer = Customer::find($this->customer_id);
            $this->outstanding_balance = $customer ? $customer->outstanding_balance : 0;
            
            $this->unpaid_invoices = Sale::where('customer_id', $this->customer_id)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->orderBy('created_at', 'asc')
                ->get();
        } else {
            $this->outstanding_balance = 0;
            $this->unpaid_invoices = [];
        }
    }

    public function savePayment()
    {
        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'payment_amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,card,easypaisa,bank_transfer',
        ]);

        if ($this->payment_amount > $this->outstanding_balance) {
            session()->flash('error', 'Payment amount cannot exceed the total outstanding balance.');
            return;
        }

        try {
            DB::transaction(function () {
                $amountToDistribute = (float)$this->payment_amount;
                
                $invoices = Sale::where('customer_id', $this->customer_id)
                    ->whereIn('payment_status', ['unpaid', 'partial'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($invoices as $invoice) {
                    if ($amountToDistribute <= 0) {
                        break;
                    }

                    $invoiceDue = $invoice->total_amount - $invoice->paid_amount;
                    $paymentForThisInvoice = min($amountToDistribute, $invoiceDue);

                    $invoice->paid_amount += $paymentForThisInvoice;
                    if ($invoice->paid_amount >= $invoice->total_amount) {
                        $invoice->payment_status = 'paid';
                    } else {
                        $invoice->payment_status = 'partial';
                    }
                    $invoice->save();

                    CustomerPayment::create([
                        'customer_id' => $this->customer_id,
                        'sale_id' => $invoice->id,
                        'amount' => $paymentForThisInvoice,
                        'payment_method' => $this->payment_method,
                        'payment_date' => now(),
                        'user_id' => auth()->id() ?? 1,
                        'remarks' => $this->remarks,
                    ]);

                    $amountToDistribute -= $paymentForThisInvoice;
                }
            });

            session()->flash('message', 'Payment received successfully!');
            $this->payment_amount = null;
            $this->remarks = '';
            $this->updatedCustomerId();

        } catch (\Exception $e) {
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.receive-payment');
    }
}
