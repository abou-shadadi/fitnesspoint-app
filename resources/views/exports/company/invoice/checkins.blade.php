<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Check-ins Report - Invoice {{ $invoice->reference }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            margin: 0;
            padding: 0;
        }

        h1 {
            font-size: 16pt;
            margin: 0 0 5px 0;
            color: #333;
        }

        h2 {
            font-size: 12pt;
            margin: 0 0 10px 0;
            color: #666;
        }

        h3 {
            font-size: 11pt;
            margin: 15px 0 8px 0;
            color: #444;
            border-bottom: 1px solid #ddd;
            padding-bottom: 3px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .info-table td {
            padding: 4px 8px;
            vertical-align: top;
        }

        .info-table .label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: center;
        }

        .summary-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .daily-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .daily-table th,
        .daily-table td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: center;
            font-size: 9pt;
        }

        .daily-table th {
            background-color: #f8f8f8;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th,
        .details-table td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 9pt;
            vertical-align: top;
        }

        .details-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .status-completed {
            color: #2e7d32;
        }

        .status-failed {
            color: #c62828;
        }

        .status-pending {
            color: #f57c00;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .page-break {
            page-break-before: always;
        }

        .no-data {
            text-align: center;
            color: #999;
            padding: 20px;
        }

        .totals {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .signature-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .signature-yes {
            background-color: #4caf50;
        }

        .signature-no {
            background-color: #f44336;
        }

        .signature-text {
            font-size: 8pt;
        }

        .notes-cell {
            max-width: 100px;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Check-ins Report</h1>
        <h2>Invoice: {{ $invoice->reference }}</h2>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Company:</td>
            <td>{{ $company->name }}</td>
            <td class="label">Subscription:</td>
            <td>{{ $subscription->id }} ({{ $subscription->billing_type->name ?? 'N/A' }})</td>
        </tr>
        <tr>
            <td class="label">Period:</td>
            <td>{{ $invoice->from_date->format('Y-m-d') }} to {{ $invoice->to_date->format('Y-m-d') }}</td>
            <td class="label">Invoice Amount:</td>
            <td>{{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency->code ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Generated:</td>
            <td>{{ $generated_at }}</td>
            <td class="label">Total Check-ins in Invoice:</td>
            <td>{{ $invoice->total_member_check_ins }}</td>
        </tr>
    </table>

    <h3>Summary Statistics</h3>
    <table class="summary-table">
        <tr>
            <th>Total Days</th>
            <th>Total Check-ins</th>
            <th>Completed</th>
            <th>Failed</th>
            <th>Pending</th>
            <th>Unique Members</th>
        </tr>
        <tr>
            <td>{{ $summary['total_days'] }}</td>
            <td>{{ $summary['total_check_ins'] }}</td>
            <td class="status-completed">{{ $summary['total_completed'] }}</td>
            <td class="status-failed">{{ $summary['total_failed'] }}</td>
            <td class="status-pending">{{ $summary['total_pending'] }}</td>
            <td>{{ $summary['unique_members'] }}</td>
        </tr>
    </table>

    @if (!empty($summary['daily_summary']))
        <h3>Daily Summary</h3>
        <table class="daily-table">
            <tr>
                <th>Date</th>
                <th>Total Check-ins</th>
                <th>Completed</th>
                <th>Failed</th>
                <th>Pending</th>
                <th>Unique Members</th>
            </tr>
            @foreach ($summary['daily_summary'] as $daily)
                <tr>
                    <td>{{ $daily['date'] }}</td>
                    <td>{{ $daily['total_check_ins'] }}</td>
                    <td class="status-completed">{{ $daily['completed'] }}</td>
                    <td class="status-failed">{{ $daily['failed'] }}</td>
                    <td class="status-pending">{{ $daily['pending'] }}</td>
                    <td>{{ $daily['unique_members'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3>Check-in Details (Total: {{ count($data) }})</h3>

    @if (!empty($data))
        <table class="details-table">
            <tr>
                <th class="text-center">Date</th>
                <th class="text-center">Time</th>
                <th class="text-left">Member Name</th>
                <th class="text-left">Check-in Method</th>
                <th class="text-center">Status</th>
                <th class="text-left">Branch</th>
                <th class="text-left">Notes</th>
                <th class="text-left">Created By</th>
                <th class="text-center">Attachment</th>
            </tr>

            @foreach ($data as $row)
                @php
                    $statusClass = 'status-' . $row['status'];
                @endphp
                <tr>
                    <td class="text-center">{{ $row['date'] }}</td>
                    <td class="text-center">{{ $row['time'] }}</td>
                    <td class="text-left">{{ $row['member_name'] }}</td>
                    <td class="text-left">{{ $row['check_in_method'] }}</td>
                    <td class="text-center {{ $statusClass }}">{{ ucfirst($row['status']) }}</td>
                    <td class="text-left">{{ $row['branch'] }}</td>
                    <td class="text-left notes-cell">{{ Str::limit($row['notes'], 50) }}</td>
                    <td class="text-left">{{ $row['created_by'] }}</td>
                    <td class="text-center">
                        @if ($row['has_signature'])
                            <div class="signature-indicator signature-yes" title="Signature available"></div>
                            <span class="signature-text">Yes</span>
                        @else
                            <div class="signature-indicator signature-no" title="No signature"></div>
                            <span class="signature-text">No</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="no-data">No check-in records found for the selected period.</div>
    @endif

</body>

</html>
