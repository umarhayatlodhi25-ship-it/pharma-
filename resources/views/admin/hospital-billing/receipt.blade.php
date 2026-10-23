<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - #{{ $bill->bill_number }}</title>
    @php
        $profile = account_profile();
        $paperWidth = (!empty($profile->thermal_paper_size) && $profile->thermal_paper_size === '58mm') ? '58mm' : '80mm';
        $containerWidth = ($paperWidth === '58mm') ? '240px' : '320px';
    @endphp
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace;
        }

        body {
            background-color: #f1f5f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 12px;
            color: #000;
        }

        .no-print-bar {
            width: {{ $containerWidth }};
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 10px 14px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-align: center;
            text-decoration: none;
        }

        .btn-primary {
            background-color: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #1d4ed8;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        .ticket {
            width: {{ $containerWidth }};
            background: #fff;
            padding: 16px 12px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }

        .hospital-logo {
            max-height: 48px;
            max-width: 140px;
            margin: 0 auto 6px auto;
            display: block;
            object-fit: contain;
        }

        .hospital-name {
            font-size: 14px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .hospital-meta {
            font-size: 10px;
            color: #333;
            margin-top: 2px;
            line-height: 1.3;
        }

        .sub-header {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-top: 6px;
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 6px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .mode-badge {
            font-size: 10px;
            font-weight: bold;
            margin-top: 4px;
            display: block;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            margin-bottom: 8px;
            border-collapse: collapse;
        }

        .info-table tr td {
            padding: 2.5px 0;
            font-size: 11px;
            vertical-align: top;
        }

        .info-table tr td:first-child {
            color: #444;
            width: 44%;
            font-weight: normal;
        }

        .info-table tr td:last-child {
            font-weight: bold;
            color: #000;
            text-align: right;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 11px;
        }

        .items-table th {
            border-bottom: 1px dashed #000;
            padding: 4px 0;
            font-size: 10px;
            text-transform: uppercase;
            color: #333;
        }

        .items-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-thick {
            border-top: 2px dashed #000;
            margin: 8px 0;
        }

        .footer {
            text-align: center;
            font-size: 10px;
            padding-top: 8px;
            line-height: 1.4;
            color: #333;
        }

        .footer .notice {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .no-print-bar {
                display: none !important;
            }

            .ticket {
                width: 100%;
                max-width: {{ $paperWidth }};
                border: none;
                box-shadow: none;
                padding: 4px 1px;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Navigation Controls (Hidden in Print) -->
    <div class="no-print-bar">
        <button class="btn btn-primary" onclick="window.print()">
            Print Receipt
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            Close
        </button>
    </div>

    <!-- Thermal Print Slip Container (58mm/80mm compatible) -->
    <div class="ticket">
        <div class="header">
            @if($profile->hasLogo())
                <img src="{{ $profile->logo_url }}" alt="{{ $profile->account_name }}" class="hospital-logo">
            @endif
            <div class="hospital-name">{{ $profile->account_name ?: config('app.name', 'HOSPITAL CLINIC') }}</div>
            @if($profile->formatted_address)
                <div class="hospital-meta">{{ $profile->formatted_address }}</div>
            @endif
            @if($profile->phone)
                <div class="hospital-meta">Tel: {{ $profile->phone }}</div>
            @endif
            <div class="sub-header">Official Hospital Receipt</div>
            <div class="mode-badge">
                {{ $bill->billing_type === 'opd' ? 'OPD Patient Service' : 'Direct Hospital Service' }}
            </div>
        </div>

        <table class="info-table">
            <tr>
                <td>Receipt / Bill #:</td>
                <td>{{ $bill->bill_number }}</td>
            </tr>
            <tr>
                <td>Date & Time:</td>
                <td>{{ $bill->bill_date->format('d-M-Y') }} {{ format_app_datetime($bill->created_at, 'h:i A', '') }}</td>
            </tr>
            <tr>
                <td>Patient Name:</td>
                <td>{{ $bill->patient->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>Patient ID:</td>
                <td>{{ $bill->patient->patient_number ?? '—' }}</td>
            </tr>
            @if($bill->token)
            <tr>
                <td>OPD Token #:</td>
                <td>#{{ $bill->token->formatted_token_number }}</td>
            </tr>
            <tr>
                <td>Attending Doctor:</td>
                <td>{{ $bill->doctor ? $bill->doctor->name : 'N/A' }}</td>
            </tr>
            @elseif($bill->doctor)
            <tr>
                <td>Referred Doctor:</td>
                <td>{{ $bill->doctor->name }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <!-- ITEMIZED SERVICES LIST -->
        @if($bill->items->count() > 0)
        <table class="items-table">
            <thead>
                <tr>
                    <th align="left">Service Description</th>
                    <th align="center" width="15%">Qty</th>
                    <th align="right" width="22%">Price</th>
                    <th align="right" width="26%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bill->items as $item)
                <tr>
                    <td align="left">
                        {{ $item->service_name }}
                    </td>
                    <td align="center">{{ number_format($item->quantity, 0) }}</td>
                    <td align="right">{{ number_format($item->unit_price, 2) }}</td>
                    <td align="right"><strong>{{ number_format($item->total_price, 2) }}</strong></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <table class="items-table">
            <thead>
                <tr>
                    <th align="left">Service Description</th>
                    <th align="right" width="30%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td align="left">{{ $bill->service ? $bill->service->name : 'Hospital Service' }}</td>
                    <td align="right"><strong>{{ number_format($bill->total_amount, 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        @endif

        <div class="divider-thick"></div>

        <table class="info-table">
            <tr>
                <td>Current Services Total:</td>
                <td>PKR {{ number_format($bill->total_amount, 2) }}</td>
            </tr>
            @if($bill->discount_amount > 0)
            <tr>
                <td>Discount / Waiver:</td>
                <td>- PKR {{ number_format($bill->discount_amount, 2) }}</td>
            </tr>
            @endif
            @if($bill->previous_due > 0)
            <tr>
                <td>Previous Outstanding:</td>
                <td>+ PKR {{ number_format($bill->previous_due, 2) }}</td>
            </tr>
            <tr>
                <td>Total Due in Context:</td>
                <td><strong>PKR {{ number_format($bill->net_amount + $bill->previous_due, 2) }}</strong></td>
            </tr>
            @endif
            <tr>
                <td>Amount Received:</td>
                <td><strong>PKR {{ number_format($bill->paid_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Remaining Balance:</td>
                <td><strong>PKR {{ number_format($bill->due_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Payment Method:</td>
                <td>{{ strtoupper($bill->payment_method) }}</td>
            </tr>
            <tr>
                <td>Payment Status:</td>
                <td>{{ strtoupper($bill->payment_status) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="footer">
            <p class="notice">Hospital Service Official Receipt</p>
            @if($profile->footer_text)
                <p style="margin-top: 4px;">{{ $profile->footer_text }}</p>
            @else
                <p style="margin-top: 4px;">Thank you for your visit. Get well soon!</p>
            @endif
            <p style="font-size: 9px; color: #555; margin-top: 5px;">
                Issued By: {{ $bill->createdBy ? $bill->createdBy->name : (auth()->user()->name ?? 'System Cashier') }}
            </p>
        </div>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
