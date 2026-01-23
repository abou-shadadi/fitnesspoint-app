<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->reference }}</title>
    <style>
        /* Optimized for single page */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.2;
            color: #333;
            background-color: #fff;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 8px;
        }

        /* Header - Balanced */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #2c3e50;
        }

        .company-name {
            font-size: 16px;
            color: #2c3e50;
            margin-bottom: 2px;
        }

        .company-tagline {
            color: #7f8c8d;
            font-size: 9px;
            margin-bottom: 4px;
        }

        .company-details {
            font-size: 9px;
            margin-bottom: 2px;
        }

        .invoice-title-main {
            font-size: 18px;
            color: #e74c3c;
            margin-bottom: 3px;
        }

        .invoice-details {
            font-size: 9px;
            margin-bottom: 2px;
        }

        /* Payment Status */
        .payment-status {
            padding: 6px;
            border-radius: 3px;
            margin-bottom: 10px;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .status-paid { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-pending { background-color: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .status-overdue { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-partially-paid { background-color: #cce5ff; color: #004085; border: 1px solid #b8daff; }

        /* Combined Info Section */
        .combined-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 3px;
        }

        .info-section {
            flex: 1;
            padding: 0 8px;
        }

        .info-title {
            font-weight: bold;
            color: #2c3e50;
            font-size: 10px;
            margin-bottom: 4px;
            padding-bottom: 3px;
            border-bottom: 1px solid #ddd;
        }

        .info-row {
            display: flex;
            margin-bottom: 3px;
        }

        .info-label {
            font-weight: bold;
            min-width: 70px;
            color: #555;
            font-size: 9px;
        }

        .info-value {
            font-size: 9px;
        }

        /* Invoice Items Table */
        .invoice-items {
            margin-bottom: 12px;
        }

        .invoice-items h3 {
            background-color: #2c3e50;
            color: white;
            padding: 5px 8px;
            margin-bottom: 0;
            font-size: 11px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .items-table th {
            background-color: #f8f9fa;
            padding: 5px 6px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            color: #2c3e50;
        }

        .items-table td {
            padding: 5px 6px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Amounts section */
        .amounts-section {
            margin-bottom: 12px;
        }

        .amounts-table {
            width: 220px;
            margin-left: auto;
            border-collapse: collapse;
            font-size: 10px;
        }

        .amounts-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
        }

        .amounts-table .label {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-row {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        /* Payment Transactions */
        @if($transactions->count() > 0)
        .transactions-section {
            margin-bottom: 12px;
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        .transactions-table th {
            background-color: #f8f9fa;
            padding: 5px 6px;
            border: 1px solid #ddd;
            font-weight: bold;
        }

        .transactions-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
        }
        @endif

        /* Check-in summary */
        .checkin-summary {
            background: #f0f8ff;
            padding: 6px;
            margin-bottom: 8px;
            border-radius: 3px;
            font-size: 9px;
            border-left: 3px solid #3498db;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #2c3e50;
            font-size: 8px;
            color: #666;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 8px;
        }

        .footer-box h4 {
            color: #2c3e50;
            margin-bottom: 3px;
            font-size: 9px;
        }

        .terms-conditions {
            font-size: 8px;
            line-height: 1.3;
            margin-bottom: 5px;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .badge-completed { background-color: #d4edda; color: #155724; }
        .badge-pending { background-color: #fff3cd; color: #856404; }
        .badge-failed { background-color: #f8d7da; color: #721c24; }

        /* Column widths for tables */
        .col-desc { width: 40%; }
        .col-rate { width: 12%; }
        .col-tax { width: 10%; }
        .col-amount { width: 12%; }
        .col-tax-amt { width: 12%; }
        .col-subtotal { width: 14%; }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div>
                <div class="company-name">{{ $company->name }}</div>
                <div class="company-tagline">Professional Subscription Services</div>
                <div class="company-details">
                    @if($company->address)
                        {{ $company->address }}<br>
                    @endif
                    @if($company->full_phone)
                        Phone: {{ $company->full_phone }}<br>
                    @endif
                    @if($company->email)
                        Email: {{ $company->email }}
                    @endif
                </div>
            </div>

            <div style="text-align: right;">
                <div class="invoice-title-main">INVOICE</div>
                <div class="invoice-details">Reference: <strong>{{ $invoice->reference }}</strong></div>
                <div class="invoice-details">Date: {{ $invoice->invoice_date->format('M d, Y') }}</div>
                <div class="invoice-details">Due Date: {{ $invoice->due_date->format('M d, Y') }}</div>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="payment-status status-{{ str_replace('_', '-', $invoice->status) }}">
            INVOICE STATUS: {{ strtoupper(str_replace('_', ' ', $invoice->status)) }}
        </div>

        <!-- Combined Info Section -->
        <div class="combined-info">
            <div class="info-section">
                <div class="info-title">BILL TO</div>
                <div class="info-row">
                    <span class="info-label">Company:</span>
                    <span class="info-value">{{ $subscription->company->name }}</span>
                </div>
                @if($subscription->company->address)
                <div class="info-row">
                    <span class="info-label">Address:</span>
                    <span class="info-value">{{ $subscription->company->address }}</span>
                </div>
                @endif
                <div class="info-row">
                    <span class="info-label">Subscription:</span>
                    <span class="info-value">#{{ $subscription->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <span class="info-value">{{ $invoice->from_date->format('M d, Y') }} to {{ $invoice->to_date->format('M d, Y') }}</span>
                </div>
            </div>

            <div class="info-section">
                <div class="info-title">INVOICE DETAILS</div>
                <div class="info-row">
                    <span class="info-label">Billing Type:</span>
                    <span class="info-value">{{ $subscription->billing_type->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Duration:</span>
                    <span class="info-value">{{ $subscription->duration_type->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Rate Type:</span>
                    <span class="info-value">{{ $invoice->rate_type->name ?? 'N/A' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Tax Rate:</span>
                    <span class="info-value">{{ $invoice->tax_rate->rate ?? 0 }}%</span>
                </div>
            </div>
        </div>

        <!-- Check-in Summary -->
        @if(!empty($check_in_summary) && $subscription->billing_type->key === 'per_pass')
        <div class="checkin-summary">
            <strong>Check-in Summary:</strong>
            {{ $check_in_summary['total_check_ins'] }} total check-ins •
            {{ $check_in_summary['unique_members'] }} unique members
        </div>
        @endif

        <!-- Invoice Items Table -->
        <div class="invoice-items">
            <h3>INVOICE DETAILS</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th class="col-desc">Description</th>
                        <th class="col-rate text-center">Rate Type</th>
                        <th class="col-tax text-center">Tax Rate</th>
                        <th class="col-amount text-right">Amount</th>
                        <th class="col-tax-amt text-right">Tax</th>
                        <th class="col-subtotal text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Company Subscription</strong><br>
                            <span style="font-size: 8.5px;">
                                {{ $subscription->billing_type->description ?? 'Subscription service' }}<br>
                                Period: {{ $invoice->from_date->format('M d, Y') }} to {{ $invoice->to_date->format('M d, Y') }}
                                @if($subscription->billing_type->key === 'per_pass')
                                <br>Total Check-ins: {{ $invoice->total_member_check_ins }}
                                @endif
                            </span>
                        </td>
                        <td class="text-center">{{ $invoice->rate_type->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $invoice->tax_rate->rate ?? 0 }}%</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->amount, 2) }}</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->amount + $invoice->tax_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Amounts Section -->
        <div class="amounts-section">
            <table class="amounts-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax ({{ $invoice->tax_rate->rate ?? 0 }}%):</td>
                    <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount:</td>
                    <td class="text-right">-{{ $currency_symbol }}{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL DUE:</td>
                    <td class="text-right">{{ $currency_symbol }}{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                @if($total_paid > 0)
                <tr>
                    <td class="label">Amount Paid:</td>
                    <td class="text-right">{{ $currency_symbol }}{{ number_format($total_paid, 2) }}</td>
                </tr>
                <tr class="total-row">
                    <td>BALANCE DUE:</td>
                    <td class="text-right">{{ $currency_symbol }}{{ number_format($balance_due, 2) }}</td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Payment Transactions -->
        @if($transactions->count() > 0)
        <div class="transactions-section">
            <div style="background-color: #2c3e50; color: white; padding: 5px 8px; font-size: 11px; font-weight: bold;">
                PAYMENT TRANSACTIONS
            </div>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th style="width: 25%;">Reference</th>
                        <th style="width: 20%;">Date</th>
                        <th style="width: 25%;">Payment Method</th>
                        <th style="width: 20%;" class="text-right">Amount Paid</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                    <tr>
                        <td>{{ $transaction->reference }}</td>
                        <td>{{ $transaction->date->format('M d, Y') }}</td>
                        <td>{{ $transaction->payment_method->name ?? 'N/A' }}</td>
                        <td class="text-right">{{ $currency_symbol }}{{ number_format($transaction->amount_paid, 2) }}</td>
                        <td>
                            <span class="status-badge badge-{{ $transaction->status }}">
                                {{ ucfirst($transaction->status) }}
                            </span>
                        </td>
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
                    <h4>Payment Instructions</h4>
                    <div style="font-size: 8px;">
                        Please make payment by the due date to avoid late fees.<br>
                        Payment can be made via bank transfer or at any branch.
                    </div>
                </div>
                <div class="footer-box">
                    <h4>Contact Information</h4>
                    <div style="font-size: 8px;">
                        For billing inquiries:<br>
                        Email: billing@example.com<br>
                        Phone: +255 XXX XXX XXX
                    </div>
                </div>
            </div>

            <div class="terms-conditions">
                <p><strong>Terms & Conditions:</strong></p>
                <p>1. Payment is due within {{ $invoice->due_date->diffInDays($invoice->invoice_date) }} days of invoice date.</p>
                <p>2. Late payments may be subject to a late fee of 5% per month.</p>
                <p>3. All amounts are in {{ $invoice->currency->code ?? 'FRW' }}.</p>
            </div>

            <div style="text-align: center; margin-top: 5px; font-size: 8px;">
                <p>Generated on {{ $generated_at }}</p>
                <p>This is a computer-generated invoice. No signature is required.</p>
            </div>
        </div>
    </div>
</body>
</html>
