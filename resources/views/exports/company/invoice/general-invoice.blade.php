<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Member Invoice {{ $invoice->reference ?? 'N/A' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9px; /* Reduced from 12px */
            line-height: 1.3; /* Slightly tighter */
            color: #333;
            background-color: #fff;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15px; /* Reduced from 20px */
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px; /* Reduced from 30px */
            border-bottom: 1px solid #2c3e50; /* Thinner border */
            padding-bottom: 10px; /* Reduced from 20px */
        }

        .company-info h1 {
            font-size: 18px; /* Reduced from 24px */
            color: #2c3e50;
            margin-bottom: 3px; /* Reduced from 5px */
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 22px; /* Reduced from 28px */
            color: #e74c3c;
            margin-bottom: 3px; /* Reduced from 5px */
        }

        .invoice-info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px; /* Reduced from 30px */
        }

        .info-box {
            flex: 1;
            margin: 0 5px; /* Reduced from 10px */
        }

        .info-box h3 {
            background-color: #f8f9fa;
            padding: 5px 8px; /* Reduced from 8px 12px */
            border-left: 3px solid #3498db; /* Thinner border */
            margin-bottom: 6px; /* Reduced from 10px */
            font-size: 11px; /* Reduced from 14px */
        }

        .info-details {
            padding: 0 8px; /* Reduced from 12px */
        }

        .info-row {
            display: flex;
            margin-bottom: 3px; /* Reduced from 5px */
            font-size: 8.5px; /* New - smaller */
        }

        .info-label {
            font-weight: bold;
            min-width: 100px; /* Slightly reduced from 120px */
            color: #555;
            font-size: 8.5px; /* New - smaller */
        }

        .billing-summary {
            background-color: #f8f9fa;
            padding: 12px; /* Reduced from 20px */
            border-radius: 4px; /* Slightly smaller */
            margin-bottom: 15px; /* Reduced from 30px */
        }

        .billing-summary h3 {
            color: #2c3e50;
            margin-bottom: 10px; /* Reduced from 15px */
            padding-bottom: 6px; /* Reduced from 10px */
            border-bottom: 1px solid #ddd;
            font-size: 12px; /* New - smaller */
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Smaller min width */
            gap: 8px; /* Reduced from 15px */
        }

        .summary-item {
            background: white;
            padding: 10px; /* Reduced from 15px */
            border-radius: 3px; /* Smaller radius */
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); /* Lighter shadow */
        }

        .summary-label {
            font-size: 9px; /* Reduced from 11px */
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 3px; /* Reduced from 5px */
        }

        .summary-value {
            font-size: 14px; /* Reduced from 18px */
            font-weight: bold;
            color: #2c3e50;
        }

        .invoice-items {
            margin-bottom: 15px; /* Reduced from 30px */
        }

        .invoice-items h3 {
            background-color: #2c3e50;
            color: white;
            padding: 6px 10px; /* Reduced from 10px 15px */
            margin-bottom: 0;
            font-size: 11px; /* New - smaller */
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px; /* New - smaller */
        }

        .items-table th,
        .items-table td {
            padding: 6px 8px; /* Reduced from 12px 15px */
            border: 1px solid #ddd;
        }

        .items-table th {
            background-color: #f8f9fa;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            font-size: 9px; /* New - smaller */
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .amounts-section {
            margin-bottom: 15px; /* Reduced from 30px */
        }

        .amounts-table {
            width: 250px; /* Reduced from 300px */
            margin-left: auto;
            border-collapse: collapse;
            font-size: 9px; /* New - smaller */
        }

        .amounts-table td {
            padding: 5px 8px; /* Reduced from 10px 15px */
            border: 1px solid #ddd;
        }

        .amounts-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
            font-size: 9px; /* New - smaller */
        }

        .total-row {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 11px; /* Reduced from 16px */
        }

        .payment-status {
            padding: 8px; /* Reduced from 15px */
            border-radius: 4px; /* Slightly smaller */
            margin-bottom: 12px; /* Reduced from 20px */
            text-align: center;
            font-weight: bold;
            font-size: 11px; /* Reduced from 16px */
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-partially-paid {
            background: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        .checkin-summary {
            background-color: #f0f8ff;
            padding: 8px; /* Reduced from 15px */
            border-radius: 4px; /* Smaller radius */
            margin-bottom: 12px; /* Reduced from 20px */
            font-size: 9px; /* New - smaller */
        }

        .checkin-summary h4 {
            font-size: 10px; /* New - smaller */
            margin-bottom: 4px;
        }

        .invoice-footer {
            margin-top: 20px; /* Reduced from 40px */
            padding-top: 10px; /* Reduced from 20px */
            border-top: 1px solid #2c3e50; /* Thinner border */
            font-size: 8px; /* Reduced from 10px */
            color: #7f8c8d;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Smaller min width */
            gap: 12px; /* Reduced from 20px */
            margin-bottom: 12px; /* Reduced from 20px */
        }

        .footer-box h4 {
            color: #2c3e50;
            margin-bottom: 5px; /* Reduced from 10px */
            font-size: 9px; /* Reduced from 12px */
        }

        .footer-box p {
            font-size: 8px; /* New - smaller */
            margin-bottom: 2px;
        }

        .terms-conditions {
            font-style: italic;
            color: #666;
            line-height: 1.4; /* Slightly tighter */
            font-size: 8px; /* New - smaller */
        }

        .terms-conditions p {
            margin-bottom: 2px; /* New - smaller spacing */
        }

        .mb-10 {
            margin-bottom: 5px; /* Reduced from 10px */
        }

        .mt-20 {
            margin-top: 10px; /* Reduced from 20px */
        }

        /* Additional small text utilities */
        .text-small {
            font-size: 8px;
        }

        .text-xsmall {
            font-size: 7px;
        }

        table td, table th {
            font-size: 8.5px;
        }

        /* Make currency symbols slightly smaller */
        .currency-symbol {
            font-size: 8.5px;
        }

        /* Adjust header fonts */
        .company-info p {
            font-size: 9px;
        }

        .invoice-title p {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="invoice-container">

        @php
            // ── Safe helpers ────────────────────────────────────────────────
            $safeFormat = function ($dateValue, $format = 'M d, Y', $default = 'N/A') {
                if (empty($dateValue)) {
                    return $default;
                }
                try {
                    return \Carbon\Carbon::parse($dateValue)->format($format);
                } catch (\Exception $e) {
                    return $default;
                }
            };

            $safeDaysBetween = function ($start, $end) {
                if (empty($start) || empty($end)) {
                    return '30';
                }
                try {
                    $s = \Carbon\Carbon::parse($start);
                    $e = \Carbon\Carbon::parse($end);
                    return $e->diffInDays($s);
                } catch (\Exception $e) {
                    return '30';
                }
            };

            // Tax rate display
            $taxRateDisplay = '0';
            if (isset($invoice->tax_rate)) {
                $rate = is_object($invoice->tax_rate)
                    ? $invoice->tax_rate->rate ?? null
                    : $invoice->tax_rate['rate'] ?? null;
                if (is_numeric($rate) || (is_string($rate) && trim($rate) !== '')) {
                    $taxRateDisplay = $rate;
                }
            }

            // Rate type name
            $rateTypeName = 'N/A';
            if (isset($invoice->rate_type)) {
                $rateTypeName = is_object($invoice->rate_type)
                    ? $invoice->rate_type->name ?? 'N/A'
                    : $invoice->rate_type['name'] ?? 'N/A';
            }

            // Payments summary
            $total_paid = 0;
            $balance_due = (float) ($invoice->total_amount ?? 0);

            if (!empty($invoice->transactions) && is_iterable($invoice->transactions)) {
                foreach ($invoice->transactions as $t) {
                    $status = is_object($t) ? $t->status ?? '' : $t['status'] ?? '';
                    if (strtolower($status) === 'completed') {
                        $amt = is_object($t) ? $t->amount_paid ?? 0 : $t['amount_paid'] ?? 0;
                        $total_paid += (float) $amt;
                    }
                }
                $balance_due = $balance_due - $total_paid;
            }
        @endphp

        <!-- Header with smaller fonts -->
        <div class="invoice-header" style="width: 100%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 60%; padding-right: 15px; vertical-align: top;">
                        <h1 style="margin: 0 0 3px 0; font-size: 20px; color: #2c3e50;">Member Subscription Invoice</h1>
                        <p style="margin: 0 0 3px 0; color: #7f8c8d; font-size: 9px;">Subscription Management System</p>
                        <p style="margin: 0; font-size: 9px;">Generated: {{ $generated_at }}</p>
                    </td>
                    <td style="width: 40%; text-align: right; vertical-align: top;">
                        <h2 style="margin: 0 0 3px 0; font-size: 22px; color: #e74c3c;">INVOICE</h2>
                        <p style="margin: 0 0 2px 0; font-size: 9px;">Reference:
                            <strong>{{ $invoice->reference ?? 'N/A' }}</strong></p>
                        <p style="margin: 0 0 2px 0; font-size: 9px;">Date:
                            {{ $safeFormat($invoice->invoice_date ?? null) }}</p>
                        <p style="margin: 0; font-size: 9px;">Due Date: {{ $safeFormat($invoice->due_date ?? null) }}
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Status -->
        <div class="payment-status status-{{ str_replace('_', '-', strtolower($invoice->status ?? 'pending')) }}">
            INVOICE STATUS: {{ strtoupper(str_replace('_', ' ', $invoice->status ?? 'pending')) }}
        </div>

        <!-- Info section -->
        <div class="invoice-info-section" style="width: 100%;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <!-- MEMBER INFORMATION -->
                    <td style="padding: 5px; vertical-align: top; width: 33.33%;">
                        <div class="info-box" style="margin: 0;">
                            <h3 style="font-size: 10px; padding: 4px 6px;">MEMBER INFORMATION</h3>
                            <div class="info-details" style="padding: 0 6px;">
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Member:</span>
                                    <span>{{ $member->name ?? 'N/A' }}</span>
                                </div>
                                @if (!empty($member->email))
                                    <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                        <span class="info-label" style="min-width: 70px; font-size: 8px;">Email:</span>
                                        <span>{{ $member->email }}</span>
                                    </div>
                                @endif
                                @if (!empty($member->phone))
                                    <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                        <span class="info-label" style="min-width: 70px; font-size: 8px;">Phone:</span>
                                        <span>{{ $member->phone }}</span>
                                    </div>
                                @endif
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Member ID:</span>
                                    <span>#{{ $member->id ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- SUBSCRIPTION DETAILS -->
                    <td style="padding: 5px; vertical-align: top; width: 33.33%;">
                        <div class="info-box" style="margin: 0;">
                            <h3 style="font-size: 10px; padding: 4px 6px;">SUBSCRIPTION DETAILS</h3>
                            <div class="info-details" style="padding: 0 6px;">
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Plan:</span>
                                    <span>{{ $subscription->plan->name ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Subscription ID:</span>
                                    <span>#{{ $subscription->id ?? 'N/A' }}</span>
                                </div>
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Start Date:</span>
                                    <span>{{ $safeFormat($subscription->start_date ?? null) }}</span>
                                </div>
                                @if (!empty($subscription->end_date))
                                    <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                        <span class="info-label" style="min-width: 70px; font-size: 8px;">End Date:</span>
                                        <span>{{ $safeFormat($subscription->end_date) }}</span>
                                    </div>
                                @endif
                                <div class="info-row" style="font-size: 8px; margin-bottom: 2px;">
                                    <span class="info-label" style="min-width: 70px; font-size: 8px;">Status:</span>
                                    <span>{{ ucfirst($subscription->status ?? 'N/A') }}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Check-ins -->
        @if (($invoice->total_check_ins ?? 0) > 0)
            <div class="checkin-summary">
                <h4 style="font-size: 9px; margin-bottom: 3px;">CHECK-IN ACTIVITY</h4>
                <p style="font-size: 8px; margin-bottom: 2px;">Total Check-ins during period: <strong>{{ $invoice->total_check_ins }}</strong></p>
                <p style="font-size: 8px;">Check-in period: {{ $safeFormat($invoice->from_date ?? null) }} to
                    {{ $safeFormat($invoice->to_date ?? null) }}</p>
            </div>
        @endif

        <!-- Billing Summary -->
        <div class="billing-summary">
            <h3 style="font-size: 11px;">BILLING SUMMARY</h3>
            <table style="width: 100%; border-collapse: collapse; border: 1px solid #ddd; font-size: 9px;">
                <tr>
                    <th style="background-color: #f8f9fa; padding: 5px; text-align: left; border: 1px solid #ddd; font-size: 9px;">Plan Amount</th>
                    <th style="background-color: #f8f9fa; padding: 5px; text-align: left; border: 1px solid #ddd; font-size: 9px;">Tax ({{ $taxRateDisplay }}%)</th>
                    @if (($invoice->discount_amount ?? 0) > 0)
                        <th style="background-color: #f8f9fa; padding: 5px; text-align: left; border: 1px solid #ddd; font-size: 9px;">Discount</th>
                    @endif
                    <th style="background-color: #f8f9fa; padding: 5px; text-align: left; border: 1px solid #ddd; font-size: 9px;">Total Amount</th>
                </tr>
                <tr>
                    <td style="padding: 5px; border: 1px solid #ddd; font-size: 9px;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->amount ?? 0, 2) }}</td>
                    <td style="padding: 5px; border: 1px solid #ddd; font-size: 9px;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                    @if (($invoice->discount_amount ?? 0) > 0)
                        <td style="padding: 5px; border: 1px solid #ddd; font-size: 9px;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format($invoice->discount_amount ?? 0, 2) }}</td>
                    @endif
                    <td style="padding: 5px; border: 1px solid #ddd; font-weight: bold; font-size: 10px;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Invoice Items -->
        <div class="invoice-items">
            <h3 style="font-size: 10px; padding: 5px 8px;">INVOICE DETAILS</h3>
            <table class="items-table" style="font-size: 8.5px;">
                <thead>
                    <tr>
                        <th style="padding: 5px; font-size: 9px;">Description</th>
                        <th class="text-center" style="padding: 5px; font-size: 9px;">Rate Type</th>
                        <th class="text-center" style="padding: 5px; font-size: 9px;">Tax Rate</th>
                        <th class="text-right" style="padding: 5px; font-size: 9px;">Amount</th>
                        <th class="text-right" style="padding: 5px; font-size: 9px;">Tax</th>
                        <th class="text-right" style="padding: 5px; font-size: 9px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="padding: 5px; font-size: 8.5px;">
                            <strong>Member Subscription - {{ $subscription->plan->name ?? 'Plan' }}</strong><br>
                            <span style="font-size: 8px;">{{ $subscription->plan->description ?? 'Subscription service' }}</span><br>
                            <span style="font-size: 8px;">Period: {{ $safeFormat($invoice->from_date ?? null) }} to
                            {{ $safeFormat($invoice->to_date ?? null) }}</span>
                            @if (($invoice->total_check_ins ?? 0) > 0)
                                <br><span style="font-size: 8px;">Total Check-ins: {{ $invoice->total_check_ins }}</span>
                            @endif
                        </td>
                        <td class="text-center" style="padding: 5px; font-size: 8.5px;">{{ $rateTypeName }}</td>
                        <td class="text-center" style="padding: 5px; font-size: 8.5px;">{{ $taxRateDisplay }}%</td>
                        <td class="text-right" style="padding: 5px; font-size: 8.5px;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format($invoice->amount ?? 0, 2) }}</td>
                        <td class="text-right" style="padding: 5px; font-size: 8.5px;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                        <td class="text-right" style="padding: 5px; font-size: 8.5px;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format(($invoice->amount ?? 0) + ($invoice->tax_amount ?? 0), 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Amounts -->
        <div class="amounts-section">
            <table class="amounts-table" style="width: 220px; font-size: 9px;">
                <tr>
                    <td class="label" style="padding: 4px 6px; font-size: 9px;">Subtotal:</td>
                    <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->amount ?? 0, 2) }}</td>
                </tr>
                <tr>
                    <td class="label" style="padding: 5px 6px; font-color: white; font-weight: bold;">Tax ({{ $taxRateDisplay }}%):</td>
                    <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->tax_amount ?? 0, 2) }}</td>
                </tr>
                @if (($invoice->discount_amount ?? 0) > 0)
                    <tr>
                        <td class="label" style="padding: 5px 6px; font-color: white; font-weight: bold;">Discount:</td>
                        <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">-{{ number_format($invoice->discount_amount ?? 0, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row" style="font-size: 10px;">
                    <td style="padding: 5px 6px;">TOTAL DUE:</td>
                    <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">
                        {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                        {{ number_format($invoice->total_amount ?? 0, 2) }}</td>
                </tr>
                @if ($total_paid > 0)
                    <tr>
                        <td class="label" style="padding: 5px 6px; font-color: white; font-weight: bold;">Amount Paid:</td>
                        <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format($total_paid, 2) }}</td>
                    </tr>
                    <tr class="total-row" style="font-size: 10px;">
                        <td style="padding: 5px 6px;">BALANCE DUE:</td>
                        <td class="text-right" style="padding: 5px 6px; font-color: white; font-weight: bold;">
                            {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                            {{ number_format($balance_due, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Transactions -->
        @if (!empty($invoice->transactions) && is_iterable($invoice->transactions) && count($invoice->transactions) > 0)
            <div class="invoice-items">
                <h3 style="font-size: 10px; padding: 5px 8px;">PAYMENT TRANSACTIONS</h3>
                <table class="items-table" style="font-size: 8.5px;">
                    <thead>
                        <tr>
                            <th style="padding: 5px; font-size: 9px;">Reference</th>
                            <th style="padding: 5px; font-size: 9px;">Date</th>
                            <th style="padding: 5px; font-size: 9px;">Payment Method</th>
                            <th class="text-right" style="padding: 5px; font-size: 9px;">Amount Paid</th>
                            <th style="padding: 5px; font-size: 9px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invoice->transactions as $t)
                            @php
                                $ref = is_object($t) ? $t->reference ?? 'N/A' : $t['reference'] ?? 'N/A';
                                $date = is_object($t) ? $t->date ?? null : $t['date'] ?? null;
                                $method = 'N/A';
                                if (is_object($t) && isset($t->payment_method)) {
                                    $method = is_object($t->payment_method)
                                        ? $t->payment_method->name ?? 'N/A'
                                        : $t->payment_method['name'] ?? 'N/A';
                                } elseif (is_array($t) && isset($t['payment_method'])) {
                                    $method = is_array($t['payment_method'])
                                        ? $t['payment_method']['name'] ?? 'N/A'
                                        : 'N/A';
                                }
                                $amt = (float) (is_object($t) ? $t->amount_paid ?? 0 : $t['amount_paid'] ?? 0);
                                $status = is_object($t) ? $t->status ?? 'N/A' : $t['status'] ?? 'N/A';
                            @endphp
                            <tr>
                                <td style="padding: 4px; font-size: 8.5px;">{{ $ref }}</td>
                                <td style="padding: 4px; font-size: 8.5px;">{{ $safeFormat($date, 'M d, Y', 'N/A') }}</td>
                                <td style="padding: 4px; font-size: 8.5px;">{{ $method }}</td>
                                <td class="text-right" style="padding: 4px; font-size: 8.5px;">
                                    {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}
                                    {{ number_format($amt, 2) }}</td>
                                <td style="padding: 4px; font-size: 8.5px;">{{ ucfirst($status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <div class="footer-grid">
                <div class="footer-box">
                    <h4 style="font-size: 9px; margin-bottom: 3px;">Payment Instructions</h4>
                    <p style="font-size: 8px; margin-bottom: 2px;">Please make payment by the due date to avoid service interruption.</p>
                    <p style="font-size: 8px;">Payment can be made at any branch or via online payment.</p>
                </div>
                <div class="footer-box">
                    <h4 style="font-size: 9px; margin-bottom: 3px;">Contact Information</h4>
                    <p style="font-size: 8px; margin-bottom: 2px;">For billing inquiries:</p>
                    <p style="font-size: 8px;">Email: billing@example.com</p>
                    <p style="font-size: 8px;">Phone: +255 XXX XXX XXX</p>
                </div>
            </div>

            <div class="terms-conditions mt-20" style="margin-top: 10px;">
                <p style="font-size: 8px; margin-bottom: 2px;"><strong>Terms & Conditions:</strong></p>
                <p style="font-size: 8px; margin-bottom: 2px;">1. Payment is due within
                    {{ $safeDaysBetween($invoice->invoice_date ?? null, $invoice->due_date ?? null) }} days of invoice
                    date.</p>
                <p style="font-size: 8px; margin-bottom: 2px;">2. Late payments may result in service suspension.</p>
                <p style="font-size: 8px;">3. All amounts are in
                    {{ optional(optional($invoice->company_subscription)->currency)->symbol ?? 'Frw' }}.</p>
            </div>

            <div class="text-center mt-20" style="margin-top: 10px;">
                <p style="font-size: 7px; margin-bottom: 1px;">Generated on {{ $generated_at }}</p>
                <p style="font-size: 7px;">This is a computer-generated invoice. No signature is required.</p>
            </div>
        </div>

    </div>
</body>

</html>
