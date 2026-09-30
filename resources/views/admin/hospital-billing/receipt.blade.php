<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - #{{ $bill->bill_number }}</title>
    @php
        $profile = account_profile();
    @endphp
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, monospace;
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
            width: 320px;
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
            width: 320px;
            background: #fff;
            padding: 18px 16px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .hospital-logo {
            max-height: 48px;
            max-width: 140px;
            margin: 0 auto 6px auto;
            display: block;
            object-fit: contain;
        }

        .hospital-name {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .hospital-meta {
            font-size: 11px;
            color: #333;
            margin-top: 3px;
            line-height: 1.3;
        }

        .sub-header {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 6px;
            display: inline-block;
            border: 1px solid #000;
            padding: 2px 8px;
            border-radius: 3px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 10px;
            border-collapse: collapse;
        }

        .info-table tr td {
            padding: 3px 0;
            font-size: 12px;
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

        .divider {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .divider-thick {
            border-top: 2px dashed #000;
            margin: 10px 0;
        }

        .revenue-box {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            padding: 6px;
            border-radius: 4px;
            margin: 8px 0;
            font-size: 11px;
        }

        .footer {
            text-align: center;
            font-size: 11px;
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
                max-width: 80mm;
                border: none;
                box-shadow: none;
                padding: 6px 2px;
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

    <!-- Thermal Print Slip Container (80mm standard) -->
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
            <div class="sub-header">OFFICIAL RECEIPT</div>
        </div>

        <table class="info-table">
            <tr>
                <td>Receipt / Bill #:</td>
                <td>{{ $bill->bill_number }}</td>
            </tr>
            <tr>
                <td>Date / Time:</td>
                <td>{{ $bill->bill_date->format('d-M-Y') }} {{ $bill->created_at ? $bill->created_at->format('h:i A') : '' }}</td>
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
                <td>Token Number:</td>
                <td>#{{ $bill->token->formatted_token_number }}</td>
            </tr>
            @endif
            <tr>
                <td>Doctor:</td>
                <td>{{ $bill->doctor ? $bill->doctor->name : 'Hospital Direct' }}</td>
            </tr>
            <tr>
                <td>Service:</td>
                <td>{{ $bill->service ? $bill->service->name : 'Consultation' }}</td>
            </tr>
        </table>

        <div class="divider-thick"></div>

        <table class="info-table">
            <tr>
                <td>Total Fee:</td>
                <td>PKR {{ number_format($bill->total_amount, 2) }}</td>
            </tr>
            @if($bill->discount_amount > 0)
            <tr>
                <td>Free / Discount:</td>
                <td>- PKR {{ number_format($bill->discount_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td>Collected Amount:</td>
                <td>PKR {{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Remaining Due:</td>
                <td>PKR {{ number_format($bill->due_amount, 2) }}</td>
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
            <p class="notice">Hospital Collects All Payments</p>
            @if($profile->footer_text)
                <p style="margin-top: 4px;">{{ $profile->footer_text }}</p>
            @else
                <p style="margin-top: 4px;">Thank you for your visit. Get well soon!</p>
            @endif
            <p style="font-size: 10px; color: #777; margin-top: 6px;">Cashier: {{ auth()->user()->name ?? 'System' }}</p>
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
