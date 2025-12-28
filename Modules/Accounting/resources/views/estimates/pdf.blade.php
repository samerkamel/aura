<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estimate {{ $estimate->estimate_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .container {
            padding: 30px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .logo {
            max-width: 180px;
            max-height: 80px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 10px;
            color: #666;
            line-height: 1.6;
        }
        .estimate-title {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .estimate-number {
            font-size: 14px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .status-draft { background: #e5e7eb; color: #374151; }
        .status-sent { background: #dbeafe; color: #1d4ed8; }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-rejected { background: #fee2e2; color: #dc2626; }

        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 20px;
        }
        .info-box:last-child {
            padding-right: 0;
            padding-left: 20px;
        }
        .info-box h3 {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-box .client-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #f3f4f6;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        .items-table th.text-center { text-align: center; }
        .items-table th.text-right { text-align: right; }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        .items-table td.text-center { text-align: center; }
        .items-table td.text-right { text-align: right; }
        .item-description {
            font-weight: 500;
        }
        .item-details {
            font-size: 10px;
            color: #666;
            margin-top: 3px;
        }

        .totals-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .totals-spacer {
            display: table-cell;
            width: 60%;
        }
        .totals-table {
            display: table-cell;
            width: 40%;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 6px 0;
        }
        .totals-label {
            display: table-cell;
            text-align: right;
            padding-right: 15px;
            color: #666;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-weight: 500;
        }
        .totals-row.total {
            border-top: 2px solid #e5e7eb;
            margin-top: 5px;
            padding-top: 10px;
        }
        .totals-row.total .totals-label,
        .totals-row.total .totals-value {
            font-size: 14px;
            font-weight: bold;
            color: #2563eb;
        }

        .notes-section {
            margin-bottom: 30px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 6px;
        }
        .notes-section h3 {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .notes-section p {
            font-size: 11px;
            color: #374151;
            white-space: pre-wrap;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .bank-details {
            margin-bottom: 20px;
        }
        .bank-details h3 {
            font-size: 11px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .bank-details p {
            font-size: 10px;
            color: #374151;
            margin-bottom: 2px;
        }
        .footer-info {
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                @if($companySettings->logo_base64)
                    <img src="{{ $companySettings->logo_base64 }}" alt="Company Logo" class="logo">
                @endif
                <div class="company-name">{{ $companySettings->company_name }}</div>
                @if($companySettings->company_name_ar)
                    <div class="company-name" style="font-size: 14px;">{{ $companySettings->company_name_ar }}</div>
                @endif
                <div class="company-info">
                    @if($companySettings->address){{ $companySettings->address }}<br>@endif
                    @if($companySettings->phone)Tel: {{ $companySettings->phone }}<br>@endif
                    @if($companySettings->email)Email: {{ $companySettings->email }}<br>@endif
                    @if($companySettings->website){{ $companySettings->website }}<br>@endif
                    @if($companySettings->tax_id)Tax ID: {{ $companySettings->tax_id }}@endif
                </div>
            </div>
            <div class="header-right">
                <div class="estimate-title">ESTIMATE</div>
                <div class="estimate-number">{{ $estimate->estimate_number }}</div>
                <div class="status-badge status-{{ $estimate->status }}">{{ $estimate->status_label }}</div>
            </div>
        </div>

        <!-- Client & Estimate Info -->
        <div class="info-section">
            <div class="info-box">
                <h3>Bill To</h3>
                <p class="client-name">{{ $estimate->client_name }}</p>
                @if($estimate->client_email)
                    <p>{{ $estimate->client_email }}</p>
                @endif
                @if($estimate->client_address)
                    <p>{{ $estimate->client_address }}</p>
                @endif
            </div>
            <div class="info-box">
                <h3>Estimate Details</h3>
                <p><strong>Issue Date:</strong> {{ $estimate->issue_date->format('F d, Y') }}</p>
                @if($estimate->valid_until)
                    <p><strong>Valid Until:</strong> {{ $estimate->valid_until->format('F d, Y') }}</p>
                @endif
                @if($estimate->project)
                    <p><strong>Project:</strong> {{ $estimate->project->name }}</p>
                @endif
            </div>
        </div>

        <!-- Title & Description -->
        @if($estimate->title || $estimate->description)
            <div style="margin-bottom: 20px;">
                <h2 style="font-size: 16px; color: #333; margin-bottom: 8px;">{{ $estimate->title }}</h2>
                @if($estimate->description)
                    <p style="font-size: 11px; color: #666;">{{ $estimate->description }}</p>
                @endif
            </div>
        @endif

        <!-- Line Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th class="text-center" style="width: 10%;">Qty</th>
                    <th class="text-center" style="width: 10%;">Unit</th>
                    <th class="text-right" style="width: 15%;">Unit Price</th>
                    <th class="text-right" style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($estimate->items as $item)
                    <tr>
                        <td>
                            <div class="item-description">{{ $item->description }}</div>
                            @if($item->details)
                                <div class="item-details">{{ $item->details }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-center">{{ ucfirst($item->unit) }}</td>
                        <td class="text-right">EGP {{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">EGP {{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <div class="totals-spacer"></div>
            <div class="totals-table">
                <div class="totals-row">
                    <div class="totals-label">Subtotal:</div>
                    <div class="totals-value">EGP {{ number_format($estimate->subtotal, 2) }}</div>
                </div>
                <div class="totals-row">
                    <div class="totals-label">VAT ({{ $estimate->vat_rate }}%):</div>
                    <div class="totals-value">EGP {{ number_format($estimate->vat_amount, 2) }}</div>
                </div>
                <div class="totals-row total">
                    <div class="totals-label">Total:</div>
                    <div class="totals-value">EGP {{ number_format($estimate->total, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Amount in Arabic Words -->
        <table style="width: 100%; margin-bottom: 30px;">
            <tr>
                <td style="text-align: right; font-size: 12px; color: #333; padding: 10px; background: #f9fafb; border-radius: 6px;">
                    {{ \App\Helpers\ArabicNumberHelper::toArabicWordsForPdf($estimate->total, $companySettings->currency ?? 'EGP') }} <strong>{{ \App\Helpers\ArabicNumberHelper::reverseArabicText('المبلغ بالحروف:') }}</strong>
                </td>
            </tr>
        </table>

        <!-- Notes -->
        @if($estimate->notes)
            <div class="notes-section">
                <h3>Notes & Terms</h3>
                <p>{{ $estimate->notes }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            @if($companySettings->formatted_bank_details)
                <div class="bank-details">
                    <h3>Bank Details</h3>
                    @foreach(explode("\n", $companySettings->formatted_bank_details) as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                </div>
            @endif

            <div class="footer-info">
                <p>{{ $companySettings->company_name }}</p>
                @if($companySettings->commercial_register)
                    <p>Commercial Register: {{ $companySettings->commercial_register }}</p>
                @endif
                <p>Thank you for your business!</p>
            </div>
        </div>
    </div>
</body>
</html>
