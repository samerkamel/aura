<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Flow Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }

        .header h1 {
            color: #007bff;
            font-size: 24px;
            margin: 0 0 10px 0;
        }

        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .summary-section {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 10px;
            text-align: center;
            vertical-align: top;
        }

        .summary-label {
            font-weight: bold;
            color: #666;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
        }

        .summary-value.positive {
            color: #28a745;
        }

        .summary-value.negative {
            color: #dc3545;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #dee2e6;
            padding: 8px 12px;
            text-align: left;
        }

        .table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .table td {
            vertical-align: middle;
        }

        .table td.number {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin: 30px 0 15px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-warning {
            color: #ffc107;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .deficit-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
            color: #856404;
        }

        .deficit-warning strong {
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Report Header -->
    <div class="header">
        <h1>Cash Flow Report</h1>
        <div class="subtitle">{{ ucfirst($selectedPeriod) }} Analysis</div>
        <div class="subtitle">Period: {{ $startDate->format('F j, Y') }} to {{ $endDate->format('F j, Y') }}</div>
        <div class="subtitle">Generated on {{ $generatedAt->format('F j, Y \a\t g:i A') }}</div>
    </div>

    <!-- Executive Summary -->
    <div class="summary-section">
        <h3 style="margin-top: 0; color: #007bff;">Executive Summary</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Total Income</div>
                <div class="summary-value positive">{{ number_format($totalIncome, 2) }} EGP</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Expenses</div>
                <div class="summary-value negative">{{ number_format($totalExpenses, 2) }} EGP</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Net Cash Flow</div>
                <div class="summary-value {{ $netCashFlow >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($netCashFlow, 2) }} EGP
                </div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Average per Period</div>
                <div class="summary-value {{ ($netCashFlow / $projections->count()) >= 0 ? 'positive' : 'negative' }}">
                    {{ number_format($netCashFlow / $projections->count(), 2) }} EGP
                </div>
            </div>
        </div>
    </div>

    <!-- Check for deficits -->
    @php
        $deficitCount = $projections->where('net_flow', '<', 0)->count();
    @endphp

    @if($deficitCount > 0)
        <div class="deficit-warning">
            <strong>⚠️ Cash Flow Warning:</strong> {{ $deficitCount }} period(s) with negative cash flow detected in this analysis.
            Review the detailed projections below for specific periods and amounts.
        </div>
    @endif

    <!-- Detailed Projections -->
    <div class="section-title">Detailed Cash Flow Projections</div>

    <table class="table">
        <thead>
            <tr>
                <th style="width: 20%;">Period</th>
                <th style="width: 20%;">Projected Income</th>
                <th style="width: 20%;">Projected Expenses</th>
                <th style="width: 20%;">Net Cash Flow</th>
                <th style="width: 20%;">Running Balance</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBalance = 10000; @endphp <!-- Starting balance -->
            @foreach($projections as $index => $projection)
                @php
                    $runningBalance += $projection['net_flow'];
                    $periodLabel = $selectedPeriod === 'weekly'
                        ? 'Week ' . ($index + 1)
                        : ($selectedPeriod === 'quarterly' ? 'Q' . ($index + 1) : $projection['projection_date']->format('M Y'));
                @endphp
                <tr style="{{ $projection['net_flow'] < 0 ? 'background-color: #ffebee;' : '' }}">
                    <td>
                        <strong>{{ $periodLabel }}</strong>
                        @if($selectedPeriod === 'monthly')
                            <br><small style="color: #666;">
                                {{ $projection['projection_date']->format('M 1') }} - {{ $projection['projection_date']->endOfMonth()->format('M j, Y') }}
                            </small>
                        @endif
                    </td>
                    <td class="number text-success">{{ number_format($projection['projected_income'], 2) }} EGP</td>
                    <td class="number text-danger">{{ number_format($projection['projected_expenses'], 2) }} EGP</td>
                    <td class="number {{ $projection['net_flow'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($projection['net_flow'], 2) }} EGP
                    </td>
                    <td class="number {{ $runningBalance >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($runningBalance, 2) }} EGP
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot style="background-color: #e9ecef; font-weight: bold;">
            <tr>
                <td><strong>TOTALS:</strong></td>
                <td class="number text-success">{{ number_format($totalIncome, 2) }} EGP</td>
                <td class="number text-danger">{{ number_format($totalExpenses, 2) }} EGP</td>
                <td class="number {{ $netCashFlow >= 0 ? 'text-success' : 'text-danger' }}">
                    {{ number_format($netCashFlow, 2) }} EGP
                </td>
                <td class="number">-</td>
            </tr>
        </tfoot>
    </table>

    <!-- Analysis and Recommendations -->
    @if($deficitCount > 0 || $netCashFlow < 0)
        <div class="section-title">Analysis & Recommendations</div>

        <div style="margin-bottom: 20px;">
            <h4 style="color: #dc3545; margin-bottom: 10px;">Cash Flow Concerns Identified:</h4>
            <ul style="line-height: 1.6;">
                @if($netCashFlow < 0)
                    <li>Overall negative cash flow of <strong>{{ number_format(abs($netCashFlow), 2) }} EGP</strong> over the analysis period</li>
                @endif
                @if($deficitCount > 0)
                    <li>{{ $deficitCount }} period(s) with negative cash flow requiring attention</li>
                @endif
            </ul>

            <h4 style="color: #007bff; margin: 20px 0 10px 0;">Recommended Actions:</h4>
            <ul style="line-height: 1.6;">
                <li>Review expense schedules for potential cost reduction opportunities</li>
                <li>Consider accelerating accounts receivable collection</li>
                <li>Evaluate timing of major expenses during deficit periods</li>
                <li>Establish a cash reserve buffer for operational continuity</li>
                @if($runningBalance < 0)
                    <li><strong>Priority:</strong> Address negative ending balance through additional financing or income acceleration</li>
                @endif
            </ul>
        </div>
    @else
        <div class="section-title">Financial Health Assessment</div>

        <div style="background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; color: #155724;">
            <h4 style="color: #155724; margin-top: 0;">✅ Positive Cash Flow Outlook</h4>
            <p>Your cash flow projections show a healthy financial position with consistent positive cash flow throughout the analysis period.</p>

            <h4 style="color: #155724; margin-bottom: 10px;">Key Strengths:</h4>
            <ul style="line-height: 1.6; margin-bottom: 0;">
                <li>Positive net cash flow of <strong>{{ number_format($netCashFlow, 2) }} EGP</strong></li>
                <li>Stable income streams from active contracts</li>
                <li>Well-managed expense structure</li>
                <li>Strong financial foundation for growth opportunities</li>
            </ul>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>This report was generated automatically by the Cash Flow Management System.</p>
        <p>Report Date: {{ $generatedAt->format('F j, Y \a\t g:i A') }} |
           Analysis Period: {{ $selectedPeriod }} |
           Data Source: Active Schedules & Contracts</p>
        <p style="margin-top: 10px; font-style: italic;">
            Note: Projections are based on current active income and expense schedules. Actual results may vary.
        </p>
    </div>
</body>
</html>