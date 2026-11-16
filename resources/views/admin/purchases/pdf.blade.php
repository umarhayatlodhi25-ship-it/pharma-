<!DOCTYPE html>
<html>
<head>

    <meta charset="UTF-8">

    <title>
        Purchase Invoice {{ $invoice->invoice_number }}
    </title>

    <style>

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #222;
            margin: 30px;
        }

        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
        }

        .subtitle {
            color: #666;
            margin-top: 5px;
        }

        .invoice-box {
            width: 100%;
        }

        .invoice-meta {
            width: 100%;
            margin-bottom: 20px;
        }

        .invoice-meta td {
            vertical-align: top;
            padding: 5px;
        }

        .label {
            color: #777;
            font-size: 10px;
            text-transform: uppercase;
        }

        .value {
            font-weight: bold;
            margin-top: 3px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table.items th {
            background: #f1f5f9;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }

        table.items td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .totals {
            width: 300px;
            margin-left: auto;
            margin-top: 20px;
        }

        .totals td {
            padding: 6px;
        }

        .grand-total {
            border-top: 2px solid #222;
            font-size: 15px;
            font-weight: bold;
        }

        .footer {
            margin-top: 40px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
            color: #777;
            font-size: 10px;
        }

    </style>

</head>

<body>

<div class="header">

    <div class="title">
        {{ $accountProfile?->account_name ?: 'Pharmacy Management System' }}
    </div>

    <div class="subtitle">
        Purchase Invoice
    </div>

</div>


<table class="invoice-meta">

    <tr>

        <td width="50%">

            <div class="label">
                Supplier
            </div>

            <div class="value">
                {{ $invoice->supplier?->name ?? 'N/A' }}
            </div>

            <br>

            <div class="label">
                Phone
            </div>

            <div class="value">
                {{ $invoice->supplier?->phone ?? 'N/A' }}
            </div>

        </td>


        <td width="50%">

            <div class="label">
                Invoice Number
            </div>

            <div class="value">
                {{ $invoice->invoice_number }}
            </div>

            <br>

            <div class="label">
                Purchase Date
            </div>

            <div class="value">
                {{ \Carbon\Carbon::parse($invoice->purchase_date)->format('d M Y') }}
            </div>

        </td>

    </tr>

</table>


<table class="items">

    <thead>

        <tr>

            <th>
                #
            </th>

            <th>
                Medicine
            </th>

            <th>
                Batch
            </th>

            <th class="center">
                Qty
            </th>

            <th class="right">
                Purchase Price
            </th>

            <th class="right">
                Sale Price
            </th>

            <th>
                Expiry
            </th>

            <th class="right">
                Total
            </th>

        </tr>

    </thead>


    <tbody>

        @foreach($invoice->items as $item)

            <tr>

                <td>
                    {{ $loop->iteration }}
                </td>

                <td>
                    {{ $item->medicine?->name ?? 'N/A' }}
                </td>

                <td>
                    {{ $item->batch_number }}
                </td>

                <td class="center">
                    {{ number_format($item->quantity, 0) }}
                </td>

                <td class="right">
                    PKR {{ number_format($item->purchase_price, 2) }}
                </td>

                <td class="right">
                    PKR {{ number_format($item->selling_price, 2) }}
                </td>

                <td>
                    {{ \Carbon\Carbon::parse($item->expiry_date)->format('d/m/Y') }}
                </td>

                <td class="right">
                    PKR {{ number_format($item->total, 2) }}
                </td>

            </tr>

        @endforeach

    </tbody>

</table>


<table class="totals">

    <tr>

        <td>
            Subtotal
        </td>

        <td class="right">
            PKR {{ number_format($invoice->subtotal, 2) }}
        </td>

    </tr>

    <tr>

        <td>
            Tax
        </td>

        <td class="right">
            PKR {{ number_format($invoice->tax_amount, 2) }}
        </td>

    </tr>

    <tr class="grand-total">

        <td>
            Grand Total
        </td>

        <td class="right">
            PKR {{ number_format($invoice->grand_total, 2) }}
        </td>

    </tr>

</table>


<div class="footer">

    {{ $accountProfile?->account_name ?: 'Pharmacy Management System' }}

    <br>

    Invoice:
    {{ $invoice->invoice_number }}

</div>

</body>
</html>