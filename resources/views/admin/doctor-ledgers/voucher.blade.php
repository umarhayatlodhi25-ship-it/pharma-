<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Voucher - #{{ $settlement->settlement_number }}</title>
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
            background-color: #7c3aed;
            color: #fff;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .ticket {
            width: 340px;
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

        .hospital-name {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .sub-header {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
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
            width: 48%;
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

        .signatures {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 120px;
            text-align: center;
            padding-top: 4px;
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

    <div class="no-print-bar">
        <button class="btn btn-primary" onclick="window.print()">
            Print Voucher
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            Close
        </button>
    </div>

    <div class="ticket">
        <div class="header">
            <div class="hospital-name">{{ $profile->account_name ?: config('app.name', 'HOSPITAL CLINIC') }}</div>
            <div class="sub-header">DOCTOR PAYMENT VOUCHER</div>
        </div>

        <table class="info-table">
            <tr>
                <td>Voucher Number:</td>
                <td>{{ $settlement->settlement_number }}</td>
            </tr>
            <tr>
                <td>Payment Date:</td>
                <td>{{ $settlement->settlement_date->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td>Doctor Name:</td>
                <td>{{ $settlement->doctor->name }}</td>
            </tr>
            <tr>
                <td>Specialization:</td>
                <td>{{ $settlement->doctor->specialization ?: 'General' }}</td>
            </tr>
            <tr>
                <td>Payment Method:</td>
                <td>{{ strtoupper($settlement->payment_method) }}</td>
            </tr>
            @if($settlement->reference_note)
            <tr>
                <td>Reference / Note:</td>
                <td>{{ $settlement->reference_note }}</td>
            </tr>
            @endif
        </table>

        <div class="divider-thick"></div>

        <table class="info-table">
            <tr>
                <td>Previous Payable:</td>
                <td>PKR {{ number_format($settlement->previous_payable, 2) }}</td>
            </tr>
            <tr>
                <td>Paid / Disbursed:</td>
                <td style="font-size: 14px;">PKR {{ number_format($settlement->paid_amount, 2) }}</td>
            </tr>
            <tr style="border-top: 1px dashed #000;">
                <td style="padding-top: 4px;">Remaining Balance:</td>
                <td style="padding-top: 4px;">PKR {{ number_format($settlement->remaining_payable, 2) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="signatures">
            <div class="signature-line">Doctor Signature</div>
            <div class="signature-line">Authorized Sign</div>
        </div>

        <div style="text-align: center; font-size: 10px; color: #777; margin-top: 16px;">
            Issued By: {{ $settlement->createdBy ? $settlement->createdBy->name : 'System' }} | {{ now()->format('d-M-Y h:i A') }}
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
