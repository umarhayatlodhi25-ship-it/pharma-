<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Slip - #{{ $token->formatted_token_number }}</title>
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
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .hospital-name {
            font-size: 16px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .sub-header {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 2px;
            margin-top: 4px;
        }

        .token-hero {
            text-align: center;
            padding: 12px 0;
            margin-bottom: 12px;
            border-bottom: 2px dashed #000;
        }

        .token-label {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .token-num {
            font-size: 42px;
            font-weight: 900;
            line-height: 1;
            margin-top: 4px;
            letter-spacing: 2px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }

        .info-table tr td {
            padding: 4px 0;
            font-size: 13px;
            vertical-align: top;
        }

        .info-table tr td:first-child {
            color: #333;
            width: 38%;
            font-weight: normal;
        }

        .info-table tr td:last-child {
            font-weight: bold;
            color: #000;
            text-align: right;
        }

        .divider {
            border-top: 2px dashed #000;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            padding-top: 10px;
            line-height: 1.4;
        }

        .footer .notice {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
                padding: 8px 4px;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Navigation Controls (Hidden in Print) -->
    <div class="no-print-bar">
        <button class="btn btn-primary" onclick="window.print()">
            Print Slip
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            Close
        </button>
    </div>

    <!-- Thermal Print Slip Container -->
    <div class="ticket">
        <div class="header">
            <div class="hospital-name">OPD CONSULTATION</div>
            <div class="sub-header">TOKEN SLIP</div>
        </div>

        <div class="token-hero">
            <div class="token-label">TOKEN NO</div>
            <div class="token-num">{{ $token->formatted_token_number }}</div>
        </div>

        <table class="info-table">
            <tr>
                <td>Patient:</td>
                <td>{{ $token->patient->name ?? '—' }}</td>
            </tr>
            <tr>
                <td>Patient No:</td>
                <td>{{ $token->patient->patient_number ?? '—' }}</td>
            </tr>
            <tr>
                <td>Age / Gender:</td>
                <td>{{ $token->patient->age ?? '—' }} Y / {{ $token->patient->gender ?? '—' }}</td>
            </tr>
            <tr>
                <td>Doctor:</td>
                <td>{{ $token->doctor->name ?? '—' }}</td>
            </tr>
            @if($token->doctor && $token->doctor->specialization)
            <tr>
                <td>Specialization:</td>
                <td>{{ $token->doctor->specialization }}</td>
            </tr>
            @endif
            <tr>
                <td>Date:</td>
                <td>{{ \Carbon\Carbon::parse($token->token_date)->format('d-M-Y') }}</td>
            </tr>
            <tr>
                <td>Time:</td>
                <td>{{ $token->created_at ? $token->created_at->format('h:i A') : '—' }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="info-table">
            <tr>
                <td>Consultation Fee:</td>
                <td>PKR {{ number_format($token->consultation_fee, 0) }}</td>
            </tr>
            <tr>
                <td>Payment:</td>
                <td>{{ strtoupper($token->payment_type) }}</td>
            </tr>
            <tr>
                <td>Charged Amount:</td>
                <td>PKR {{ number_format($token->charged_amount, 0) }}</td>
            </tr>
            @if($token->payment_type === 'free' && $token->free_reason)
            <tr>
                <td>Free Reason:</td>
                <td>{{ $token->free_reason }}</td>
            </tr>
            @endif
        </table>

        <div class="divider"></div>

        <div class="footer">
            <p class="notice">Please wait for your turn.</p>
            <p style="font-size: 11px; margin-top: 4px; color: #555;">Thank you for your patience.</p>
        </div>
    </div>

    <script>
        // Auto trigger print dialog on page load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 350);
        };
    </script>
</body>
</html>
