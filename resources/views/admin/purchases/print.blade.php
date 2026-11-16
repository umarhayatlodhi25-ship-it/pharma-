<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <title>
        Purchase Invoice - {{ $invoice->invoice_number }}
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 30px;
            font-family: Arial, Helvetica, sans-serif;
            color: #222;
            background: #fff;
            font-size: 13px;
        }

        .invoice {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #222;
            padding-bottom: 18px;
            margin-bottom: 20px;
        }

        .company h1 {
            margin: 0 0 5px;
            font-size: 24px;
        }

        .company p {
            margin: 3px 0;
            color: #555;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            margin: 0 0 8px;
            font-size: 22px;
            text-transform: uppercase;
        }

        .invoice-title p {
            margin: 4px 0;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }

        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 12px;
            border: 1px solid #ddd;
        }

        .info-box + .info-box {
            border-left: 0;
        }

        .info-box h3 {
            margin: 0 0 10px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .info-row {
            margin: 5px 0;
        }

        .label {
            display: inline-block;
            width: 130px;
            font-weight: bold;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .items th {
            background: #f3f4f6;
            border: 1px solid #ccc;
            padding: 9px 7px;
            text-align: left;
            font-size: 12px;
        }

        .items td {
            border: 1px solid #ddd;
            padding: 9px 7px;
            vertical-align: middle;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .summary-wrapper {
            margin-top: 20px;
            display: table;
            width: 100%;
        }

        .summary-spacer {
            display: table-cell;
            width: 60%;
        }

        .summary {
            display: table-cell;
            width: 40%;
        }

        .summary-row {
            display: table;
            width: 100%;
            padding: 7px 0;
            border-bottom: 1px solid #ddd;
        }

        .summary-label,
        .summary-value {
            display: table-cell;
        }

        .summary-label {
            font-weight: bold;
        }

        .summary-value {
            text-align: right;
        }

        .grand-total {
            margin-top: 8px;
            padding: 12px;
            background: #f3f4f6;
            border: 1px solid #ccc;
            font-size: 16px;
            font-weight: bold;
        }

        .footer {
            margin-top: 45px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 11px;
        }

        .no-print {
            margin-bottom: 20px;
            text-align: center;
        }

        .print-button {
            padding: 9px 18px;
            border: 0;
            background: #2563eb;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
        }

        @media print {

            body {
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .invoice {
                max-width: none;
            }
        }
    </style>
</head>

<body>

<div class="no-print">

    <button
        type="button"
        onclick="window.print()"
        class="print-button"
    >
        Print Invoice
    </button>

</div>


<div class="invoice">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="header">

        <div class="company">

            @if($accountProfile?->hasLogo())
                <img src="{{ $accountProfile->logo_url }}" alt="{{ $accountProfile->account_name }}" style="max-height: 48px; max-width: 140px; margin-bottom: 6px; display: block;">
            @endif

            <h1>{{ $accountProfile?->account_name ?: 'Pharmacy Management System' }}</h1>

            @if($accountProfile?->formatted_address)
                <p>{{ $accountProfile->formatted_address }}</p>
            @endif

            @if($accountProfile?->phone)
                <p>Phone: {{ $accountProfile->phone }}</p>
            @endif

            <p>Purchase Management</p>

            <p>Medicine Purchase Invoice</p>

        </div>


        <div class="invoice-title">

            <h2>Purchase Invoice</h2>

            <p>
                <strong>Invoice:</strong>
                {{ $invoice->invoice_number }}
            </p>

            <p>
                <strong>Date:</strong>
                {{ \Carbon\Carbon::parse($invoice->purchase_date)->format('d/m/Y') }}
            </p>

        </div>

    </div>


    {{-- =========================================================
         SUPPLIER / PURCHASE INFORMATION
    ========================================================== --}}

    <div class="info-grid">

        <div class="info-box">

            <h3>Supplier Information</h3>

            <div class="info-row">
                <span class="label">Supplier:</span>
                {{ $invoice->supplier?->name ?? 'N/A' }}
            </div>

            <div class="info-row">
                <span class="label">Contact Person:</span>
                {{ $invoice->supplier?->contact_person ?? 'N/A' }}
            </div>

            <div class="info-row">
                <span class="label">Phone:</span>
                {{ $invoice->supplier?->phone ?? 'N/A' }}
            </div>

            <div class="info-row">
                <span class="label">Email:</span>
                {{ $invoice->supplier?->email ?? 'N/A' }}
            </div>

        </div>


        <div class="info-box">

            <h3>Purchase Information</h3>

            <div class="info-row">
                <span class="label">Invoice Number:</span>
                {{ $invoice->invoice_number }}
            </div>

            <div class="info-row">
                <span class="label">Purchase Date:</span>
                {{ \Carbon\Carbon::parse($invoice->purchase_date)->format('d/m/Y') }}
            </div>

            <div class="info-row">
                <span class="label">Total Items:</span>
                {{ $invoice->items->count() }}
            </div>

            <div class="info-row">
                <span class="label">Total Units:</span>
                {{ number_format($invoice->items->sum('quantity'), 0) }}
            </div>

        </div>

    </div>


    {{-- =========================================================
         ITEMS
    ========================================================== --}}

    <table class="items">

        <thead>

        <tr>

            <th style="width: 35px;">
                #
            </th>

            <th>
                Medicine
            </th>

            <th>
                Batch
            </th>

            <th class="text-center">
                Qty
            </th>

            <th class="text-right">
                Purchase Price
            </th>

            <th class="text-right">
                Sale Price
            </th>

            <th class="text-center">
                Expiry
            </th>

            <th class="text-right">
                Total
            </th>

        </tr>

        </thead>


        <tbody>

        @forelse($invoice->items as $index => $item)

            <tr>

                <td class="text-center">
                    {{ $index + 1 }}
                </td>

                <td>
                    <strong>
                        {{ $item->medicine?->name ?? 'N/A' }}
                    </strong>
                </td>

                <td>
                    {{ $item->batch_number ?? 'N/A' }}
                </td>

                <td class="text-center">
                    {{ number_format($item->quantity, 0) }}
                </td>

                <td class="text-right">
                    PKR {{ number_format($item->purchase_price, 2) }}
                </td>

                <td class="text-right">
                    PKR {{ number_format($item->selling_price, 2) }}
                </td>

                <td class="text-center">

                    @if($item->expiry_date)

                        {{ \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') }}

                    @else

                        N/A

                    @endif

                </td>

                <td class="text-right">

                    PKR {{ number_format($item->total, 2) }}

                </td>

            </tr>

        @empty

            <tr>

                <td
                    colspan="8"
                    class="text-center"
                >
                    No purchase items found.
                </td>

            </tr>

        @endforelse

        </tbody>

    </table>


    {{-- =========================================================
         SUMMARY
    ========================================================== --}}

    <div class="summary-wrapper">

        <div class="summary-spacer"></div>

        <div class="summary">

            <div class="summary-row">

                <div class="summary-label">
                    Subtotal
                </div>

                <div class="summary-value">
                    PKR {{ number_format($invoice->subtotal, 2) }}
                </div>

            </div>


            <div class="summary-row">

                <div class="summary-label">
                    Tax
                </div>

                <div class="summary-value">
                    PKR {{ number_format($invoice->tax_amount, 2) }}
                </div>

            </div>


            <div class="grand-total">

                <div style="display: table; width: 100%;">

                    <div style="display: table-cell;">
                        Grand Total
                    </div>

                    <div
                        style="
                            display: table-cell;
                            text-align: right;
                        "
                    >
                        PKR {{ number_format($invoice->grand_total, 2) }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         FOOTER
    ========================================================== --}}

    <div class="footer">

        <p>
            This is a computer-generated purchase invoice.
        </p>

        <p>
            {{ $accountProfile?->account_name ?: 'Pharmacy Management System' }}
        </p>

    </div>

</div>

</body>

</html>