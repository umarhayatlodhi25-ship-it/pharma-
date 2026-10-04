<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPD Token {{ $token === 'preview' ? 'Preview' : '#' . ($token->formatted_token_number ?? '') }}</title>
    @php
        $profile = account_profile();
        $hospitalName = $profile->account_name ?: config('app.name', 'HOSPITAL CLINIC');

        // Handle Preview Mode
        if ($token === 'preview') {
            $token = new \App\Models\PatientToken([
                'token_number' => 1,
                'status' => 'waiting',
                'consultation_fee' => 500,
                'created_at' => now(),
                'token_date' => now()->toDateString(),
            ]);
            $token->patient = new \App\Models\Patient(['name' => 'Patient Snapshot A', 'patient_number' => 'PT-00013']);
            $token->doctor = new \App\Models\Doctor(['name' => 'Dr. Snapshot Test']);
        }

        // Dynamic status formatting
        $statusRaw = strtolower($token->status ?? 'waiting');
        $displayStatus = match($statusRaw) {
            'in_consultation', 'in consultation', 'called' => 'IN CONSULTATION',
            'completed' => 'COMPLETED',
            'cancelled' => 'CANCELLED',
            'waiting' => 'WAITING',
            default => strtoupper($token->status ?? 'WAITING'),
        };

        // Patient ID formatting
        $patientIdDisplay = $token->patient ? ($token->patient->patient_number ?: 'PT-' . str_pad($token->patient->id, 5, '0', STR_PAD_LEFT)) : '—';
        
        $paperSize = $profile->thermal_paper_size ?? 80;
        $paperCss = $paperSize == 58 ? '58mm' : '80mm';
        $ticketWidth = $paperSize == 58 ? '48mm' : '74mm';
    @endphp
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Courier New', Courier, monospace, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background-color: #f1f5f9;
            color: #000;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 10px;
            font-size: 12px;
            line-height: 1.3;
        }

        /* Screen controls bar */
        .no-print-bar {
            width: 320px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }

        .btn {
            flex: 1;
            padding: 9px 12px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-align: center;
            text-decoration: none;
            font-family: system-ui, -apple-system, sans-serif;
        }

        .btn-primary {
            background-color: #0f172a;
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #1e293b;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover {
            background-color: #cbd5e1;
        }

        /* Thermal Ticket Container (Optimized for 80mm paper, ~72-76mm usable) */
        .ticket {
            width: 320px;
            background: #fff;
            padding: 16px 14px;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: center;
            color: #000;
        }

        /* Header */
        .header {
            padding-bottom: 8px;
            text-align: center;
        }

        .hospital-logo {
            max-height: 48px;
            max-width: 140px;
            margin: 0 auto 6px auto;
            display: block;
            object-fit: contain;
            filter: grayscale(100%) contrast(150%);
        }

        .hospital-name {
            font-size: 15px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.25;
            word-break: break-word;
        }

        .opd-badge {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            margin-top: 4px;
            text-transform: uppercase;
        }

        /* Dashed Dividers */
        .divider {
            border: none;
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }

        /* Token Hero Section */
        .token-hero {
            padding: 6px 0;
            text-align: center;
        }

        .token-label {
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .token-num {
            font-size: 40px;
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        /* Key-Value Info Table */
        .info-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            margin: 4px 0;
        }

        .info-table tr td {
            padding: 3px 0;
            font-size: 12px;
            vertical-align: top;
            line-height: 1.3;
        }

        .info-table tr td.label-col {
            color: #000;
            width: 38%;
            font-weight: 600;
            text-align: left;
        }

        .info-table tr td.val-col {
            color: #000;
            width: 62%;
            font-weight: 800;
            text-align: right;
            word-break: break-word;
        }

        /* Fee Section */
        .fee-section {
            padding: 6px 0;
            text-align: center;
        }

        .fee-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .fee-value {
            font-size: 18px;
            font-weight: 900;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }

        /* Status Section */
        .status-section {
            padding: 6px 0;
            text-align: center;
        }

        .status-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .status-value {
            font-size: 13px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        /* Footer */
        .footer {
            padding-top: 6px;
            text-align: center;
            font-size: 11px;
            line-height: 1.35;
        }

        .footer .footer-wait {
            font-weight: 600;
            margin-bottom: 6px;
        }

        .footer .footer-thanks {
            font-weight: 500;
        }

        /* Thermal Printer CSS (@media print) */
        @media print {
            @page {
                size: {{ $paperCss }} auto;
                margin: 0;
            }

            html, body {
                background: #fff !important;
                color: #000 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .no-print-bar {
                display: none !important;
            }

            .ticket {
                width: {{ $ticketWidth }} !important;
                max-width: {{ $ticketWidth }} !important;
                margin: 0 auto !important;
                padding: 4mm 2mm !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
        }
    </style>
</head>
<body>

    <!-- On-screen Navigation Controls (Hidden in Print) -->
    <div class="no-print-bar">
        <button class="btn btn-primary" onclick="window.print()">
            Print Token Slip
        </button>
        <button class="btn btn-secondary" onclick="window.close()">
            Close
        </button>
    </div>

    @for($i = 0; $i < ($profile->print_copies ?? 1); $i++)
    <!-- Thermal Print Slip Container -->
    <div class="ticket" @if($i > 0) style="page-break-before: always; margin-top: 10px;" @endif>

        <!-- HEADER: Logo + Organization Name + OPD Token -->
        <div class="header">
            @if(($profile->show_logo ?? true) && $profile->hasLogo())
                <img src="{{ $profile->logo_url }}" alt="{{ $hospitalName }}" class="hospital-logo">
            @endif
            @if($profile->show_organization_name ?? true)
                <div class="hospital-name">{{ $hospitalName }}</div>
            @endif
            <div class="opd-badge">OPD TOKEN</div>
        </div>

        <div class="divider"></div>

        <!-- TOKEN NO -->
        <div class="token-hero">
            <div class="token-label">TOKEN</div>
            <div class="token-num">{{ $token->formatted_token_number }}</div>
        </div>

        <div class="divider"></div>

        <!-- PATIENT INFORMATION -->
        <table class="info-table">
            <tr>
                <td class="label-col">Patient:</td>
                <td class="val-col">{{ $token->patient->name ?? '—' }}</td>
            </tr>
            @if($profile->show_patient_id ?? true)
            <tr>
                <td class="label-col">Patient ID:</td>
                <td class="val-col">{{ $patientIdDisplay }}</td>
            </tr>
            @endif
            @if($profile->show_doctor_name ?? true)
            <tr>
                <td class="label-col">Doctor:</td>
                <td class="val-col">{{ $token->doctor->name ?? '—' }}</td>
            </tr>
            @endif
            @if($profile->show_date ?? true)
            <tr>
                <td class="label-col">Date:</td>
                <td class="val-col">{{ \Carbon\Carbon::parse($token->token_date)->format('d M Y') }}</td>
            </tr>
            @endif
            @if($profile->show_time ?? true)
            <tr>
                <td class="label-col">Time:</td>
                <td class="val-col">{{ $token->created_at ? $token->created_at->format('h:i A') : now()->format('h:i A') }}</td>
            </tr>
            @endif
        </table>

        @if($profile->show_consultation_fee ?? true)
        <div class="divider"></div>
        <!-- Fee Section -->
        <div class="fee-section">
            <div class="fee-label">Consultation Fee:</div>
            <div class="fee-value">PKR {{ number_format($token->consultation_fee, 0) }}</div>
        </div>
        @endif

        @if($profile->show_token_status ?? true)
        <div class="divider"></div>
        <!-- STATUS -->
        <div class="status-section">
            <div class="status-label">STATUS:</div>
            <div class="status-value">{{ $displayStatus }}</div>
        </div>
        @endif

        <div class="divider"></div>

        <!-- FOOTER -->
        <div class="footer">
            @if(!empty($profile->footer_text))
                {!! nl2br(e($profile->footer_text)) !!}
            @else
                Thank you for choosing<br>our healthcare services.
            @endif
        </div>

        <div class="divider"></div>

    </div>
    @endfor

    <script>
        // Auto trigger print dialog on page load
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 300);
        };
    </script>
</body>
</html>
