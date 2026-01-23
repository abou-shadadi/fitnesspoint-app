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

        .summary-highlight {
            background-color: #e8f5e9;
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

        .day-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .day-header {
            background-color: #f0f0f0;
            padding: 8px;
            font-weight: bold;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
        }

        .day-checkins-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .day-checkins-table th,
        .day-checkins-table td {
            border: 1px solid #ddd;
            padding: 5px;
            font-size: 9pt;
            vertical-align: top;
        }

        .day-checkins-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
            margin-left: 5px;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-failed {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
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



        .signature-image {
            max-width: 80px;
            max-height: 40px;
            border: 1px solid #ddd;
            border-radius: 0px;
        }

        .no-signature {
            color: #999;
            font-style: italic;
            font-size: 8pt;
        }

        .notes-cell {
            max-width: 150px;
            word-wrap: break-word;
        }

        .counter-cell {
            width: 30px;
            text-align: center;
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .date-summary {
            font-size: 9pt;
            color: #666;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 8pt;
            color: #666;
            margin-top: 2px;
        }

        .billing-note {
            font-size: 9pt;
            color: #d32f2f;
            font-style: italic;
            margin-top: 10px;
            padding: 5px;
            background-color: #ffebee;
            border-left: 3px solid #d32f2f;
        }

        .unique-count {
            font-weight: bold;
            color: #2e7d32;
        }

        .billing-count {
            background-color: #e8f5e9;
            font-weight: bold;
        }

        .member-name-cell {
            position: relative;
        }

        .status-indicator {
            position: absolute;
            right: 5px;
            top: 50%;
            transform: translateY(-50%);
        }

        .signature-cell {
            width: 120px;
            height: 40px;
            padding: 0;
            margin: 0;
            text-align: center;
            vertical-align: middle;
            background-color: #fafafa;
            /* border: 1px solid #e0e0e0; */
            background-size: cover;
            /* or 'cover' to fill completely */
            background-position: center center;
            background-repeat: no-repeat;
            position: relative;
        }

        .no-signature {
            font-size: 8pt;
            color: #aaa;
            font-style: italic;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin: 0;
            padding: 0;
            white-space: nowrap;
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
            <td class="label">Billing Check-ins:</td>
            <td>{{ $invoice->total_member_check_ins }}</td>
        </tr>
    </table>

    <h3>Summary Statistics</h3>
    <table class="summary-table">
        <tr>
            <th>Total Days</th>
            <th>Total Check-ins</th>
            <th class="summary-highlight">Billable Check-ins*</th>
            <th>Completed</th>
            <th>Failed</th>
            <th>Pending</th>
            <th>Unique Members</th>
        </tr>
        <tr>
            <td>{{ $summary['total_days'] }}</td>
            <td>{{ $summary['total_check_ins'] }}</td>
            <td class="summary-highlight billing-count">{{ $summary['unique_member_check_ins'] }}</td>
            <td>{{ $summary['total_completed'] }}</td>
            <td>{{ $summary['total_failed'] }}</td>
            <td>{{ $summary['total_pending'] }}</td>
            <td>{{ $summary['unique_members'] }}</td>
        </tr>
    </table>

    @if ($subscription->billing_type->key === 'per_pass')
        <div class="billing-note">
            * <strong>Billing Calculation:</strong> For per-pass billing, only <strong>completed check-ins</strong> of
            <strong>unique members per day</strong> are counted.<br>
            Each member is counted once per day. Failed/pending check-ins are not included in billing.
        </div>
    @endif

    @if (!empty($summary['daily_summary']))
        <h3>Daily Summary</h3>
        <table class="daily-table">
            <tr>
                <th>Date</th>
                <th>Total Check-ins</th>
                <th class="summary-highlight">Billable Members</th>
                <th>Completed</th>
                <th>Failed</th>
                <th>Pending</th>
            </tr>
            @foreach ($summary['daily_summary'] as $daily)
                <tr>
                    <td>{{ $daily['date'] }}</td>
                    <td>{{ $daily['total_check_ins'] }}</td>
                    <td class="summary-highlight billing-count">{{ $daily['unique_member_check_ins'] }}</td>
                    <td>{{ $daily['completed'] }}</td>
                    <td>{{ $daily['failed'] }}</td>
                    <td>{{ $daily['pending'] }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3>Detailed Check-ins by Date</h3>

    @if (!empty($data))
        @php
            // Group data by date
            $groupedData = [];
            foreach ($data as $row) {
                $date = $row['date'];
                if (!isset($groupedData[$date])) {
                    $groupedData[$date] = [];
                }
                $groupedData[$date][] = $row;
            }

            // Sort dates in descending order
            krsort($groupedData);
        @endphp

        @foreach ($groupedData as $date => $dayCheckIns)
            @php
                // Calculate billable members for this day (only completed check-ins, unique per day)
                $billableMembers = [];
                $completedCheckIns = array_filter($dayCheckIns, function ($row) {
                    return $row['status'] === 'completed';
                });

                foreach ($completedCheckIns as $row) {
                    $memberName = $row['member_name'];
                    if (!in_array($memberName, $billableMembers)) {
                        $billableMembers[] = $memberName;
                    }
                }
            @endphp

            <div class="day-section">
                <div class="day-header">
                    Date: {{ $date }}
                    <span class="date-summary">
                        (Total: {{ count($dayCheckIns) }} check-ins,
                        Billable: <span class="billing-count">{{ count($billableMembers) }}</span> members)
                    </span>
                </div>


                <table class="day-checkins-table">
                    <thead>
                        <tr>
                            <th class="counter-cell">#</th>
                            <th class="text-center">Time</th>
                            <th class="text-left">Member Name</th>
                            <th class="text-left">Check-in Method</th>
                            <th class="text-left">Branch</th>
                            <th class="text-left">Notes</th>
                            <th class="text-center">Signature</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $uniqueMembersProcessed = [];
                        @endphp
                        @foreach ($dayCheckIns as $index => $row)
                            @php
                                $isBillable =
                                    $row['status'] === 'completed' &&
                                    !in_array($row['member_name'], $uniqueMembersProcessed);
                                if ($isBillable) {
                                    $uniqueMembersProcessed[] = $row['member_name'];
                                }
                            @endphp
                            <tr>
                                <td class="counter-cell">{{ $index + 1 }}</td>
                                <td class="text-center">{{ $row['time'] }}</td>
                                <td class="text-left member-name-cell">
                                    {{ $row['member_name'] }}
                                </td>
                                <td class="text-left">{{ $row['check_in_method'] }}</td>
                                <td class="text-left">{{ $row['branch'] }}</td>
                                <td class="text-left notes-cell">{{ Str::limit($row['notes'], 50) }}</td>
                                <td class="signature-cell">
                                    @php
                                        $signatureFile = !empty($row['signature_path'])
                                            ? storage_path('app/public/' . $row['signature_path'])
                                            : null;
                                    @endphp

                                    @if ($row['has_signature'] && $signatureFile && file_exists($signatureFile))
                                        <img src="{{ $signatureFile }}" class="signature-image" alt="Signature">
                                    @else
                                        <div class="no-signature">No signature</div>
                                    @endif
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @else
        <div class="no-data">No check-in records found for the selected period.</div>
    @endif

</body>

</html>
