<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Member Invoice {{ $invoice->reference }}</title>
    <style>
        /* Reset and base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background-color: #fff;
        }

        .invoice-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 20px;
        }

        .company-info h1 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h2 {
            font-size: 28px;
            color: #e74c3c;
            margin-bottom: 5px;
        }

        /* Invoice info section */
        .invoice-info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .info-box {
            flex: 1;
            margin: 0 10px;
        }

        .info-box h3 {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-left: 4px solid #3498db;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-details {
            padding: 0 12px;
        }

        .info-row {
            display: flex;
            margin-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            min-width: 120px;
            color: #555;
        }

        /* Billing summary */
        .billing-summary {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }

        .billing-summary h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .summary-label {
            font-size: 11px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Invoice items table */
        .invoice-items {
            margin-bottom: 30px;
        }

        .invoice-items h3 {
            background-color: #2c3e50;
            color: white;
            padding: 10px 15px;
            margin-bottom: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table th {
            background-color: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            border: 1px solid #ddd;
            font-weight: bold;
            color: #2c3e50;
        }

        .items-table td {
            padding: 12px 15px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Amounts section */
        .amounts-section {
            margin-bottom: 30px;
        }

        .amounts-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }

        .amounts-table td {
            padding: 10px 15px;
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
            font-size: 16px;
        }

        /* Payment status */
        .payment-status {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }

        .status-paid {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-partially-paid {
            background-color: #cce5ff;
            color: #004085;
            border: 1px solid #b8daff;
        }

        /* Check-in summary */
        .checkin-summary {
            background-color: #f0f8ff;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .checkin-summary p {
            margin: 5px 0;
        }

        /* Footer */
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #2c3e50;
            font-size: 10px;
            color: #7f8c8d;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .footer-box h4 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 12px;
        }

        .terms-conditions {
            font-style: italic;
            color: #666;
            line-height: 1.6;
        }

        /* Utility classes */
        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mt-20 {
            margin-top: 20px;
        }

        .text-uppercase {
            text-transform: uppercase;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>Member Subscription Invoice</h1>
                <p class="mb-10">Subscription Management System</p>
                <p>Generated: {{ $generated_at }}</p>
            </div>

            <div class="invoice-title">
                <h2>INVOICE</h2>
                <p class="mb-10">Reference: <strong>{{ $invoice->reference }}</strong></p>
                <p>Date: {{ $invoice->invoice_date->format('F d, Y') }}</p>
                <p>Due Date: {{ $invoice->due_date->format('F d, Y') }}</p>
            </div>
        </div>

        <!-- Payment Status -->
        <div class="payment-status status-{{ str_replace('_', '-', $invoice->status) }}">
            INVOICE STATUS: {{ strtoupper(str_replace('_', ' ', $invoice->status)) }}
        </div>

        <!-- Invoice Info Section -->
        <div class="invoice-info-section">
            <div class="info-box">
                <h3>MEMBER INFORMATION</h3>
                <div class="info-details">
                    <div class="info-row">
                        <span class="info-label">Member:</span>
                        <span>{{ $member->name }}</span>
                    </div>
                    @if ($member->email)
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span>{{ $member->email }}</span>
                        </div>
                    @endif
                    @if ($member->phone)
                        <div class="info-row">
                            <span class="info-label">Phone:</span>
                            <span>{{ $member->phone }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Member ID:</span>
                        <span>#{{ $member->id }}</span>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h3>SUBSCRIPTION DETAILS</h3>
                <div class="info-details">
                    <div class="info-row">
                        <span class="info-label">Plan:</span>
                        <span>{{ $subscription->plan->name ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Subscription ID:</span>
                        <span>#{{ $subscription->id }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Start Date:</span>
                        <span>{{ $subscription->start_date->format('M d, Y') }}</span>
                    </div>
                    @if ($subscription->end_date)
                        <div class="info-row">
                            <span class="info-label">End Date:</span>
                            <span>{{ $subscription->end_date->format('M d, Y') }}</span>
                        </div>
                    @endif
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span>{{ ucfirst($subscription->status) }}</span>
                    </div>
                </div>
            </div>

            <div class="info-box">
                <h3>INVOICE DETAILS</h3>
                <div class="info-details">
                    <div class="info-row">
                        <span class="info-label">Invoice #:</span>
                        <span>{{ $invoice->reference }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Invoice Date:</span>
                        <span>{{ $invoice->invoice_date->format('M d, Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Due Date:</span>
                        <span>{{ $invoice->due_date->format('M d, Y') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Period:</span>
                        <span>{{ $invoice->from_date->format('M d, Y') }} to
                            {{ $invoice->to_date->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Check-in Summary -->
        @if ($invoice->total_check_ins > 0)
            <div class="checkin-summary">
                <h4>CHECK-IN ACTIVITY</h4>
                <p>Total Check-ins during period: <strong>{{ $invoice->total_check_ins }}</strong></p>
                <p>Check-in period: {{ $invoice->from_date->format('M d, Y') }} to
                    {{ $invoice->to_date->format('M d, Y') }}</p>
            </div>
        @endif

        <!-- Billing Summary -->
        <div class="billing-summary">
            <h3>BILLING SUMMARY</h3>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Plan Amount</div>
                    <div class="summary-value">${{ number_format($invoice->amount, 2) }}</div>
                </div>

                <div class="summary-item">
                    <div class="summary-label">Tax ({{ $invoice->tax_rate->rate ?? 0 }}%)</div>
                    <div class="summary-value">${{ number_format($invoice->tax_amount, 2) }}</div>
                </div>

                @if ($invoice->discount_amount > 0)
                    <div class="summary-item">
                        <div class="summary-label">Discount</div>
                        <div class="summary-value">${{ number_format($invoice->discount_amount, 2) }}</div>
                    </div>
                @endif

                <div class="summary-item">
                    <div class="summary-label">Total Amount</div>
                    <div class="summary-value">${{ number_format($invoice->total_amount, 2) }}</div>
                </div>
            </div>
        </div>

        <!-- Invoice Items Table -->
        <div class="invoice-items">
            <h3>INVOICE DETAILS</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Rate Type</th>
                        <th class="text-center">Tax Rate</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Tax</th>
                        <th class="text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>Member Subscription - {{ $subscription->plan->name ?? 'Plan' }}</strong><br>
                            {{ $subscription->plan->description ?? 'Subscription service' }}<br>
                            Period: {{ $invoice->from_date->format('M d, Y') }} to
                            {{ $invoice->to_date->format('M d, Y') }}
                            @if ($invoice->total_check_ins > 0)
                                <br>Total Check-ins: {{ $invoice->total_check_ins }}
                            @endif
                        </td>
                        <td class="text-center">{{ $invoice->rate_type->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $invoice->tax_rate->rate ?? 0 }}%</td>
                        <td class="text-right">${{ number_format($invoice->amount, 2) }}</td>
                        <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                        <td class="text-right">${{ number_format($invoice->amount + $invoice->tax_amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Amounts Section -->
        <div class="amounts-section">
            <table class="amounts-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="text-right">${{ number_format($invoice->amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Tax ({{ $invoice->tax_rate->rate ?? 0 }}%):</td>
                    <td class="text-right">${{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @if ($invoice->discount_amount > 0)
                    <tr>
                        <td class="label">Discount:</td>
                        <td class="text-right">-${{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL DUE:</td>
                    <td class="text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                @if ($total_paid > 0)
                    <tr>
                        <td class="label">Amount Paid:</td>
                        <td class="text-right">${{ number_format($total_paid, 2) }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>BALANCE DUE:</td>
                        <td class="text-right">${{ number_format($balance_due, 2) }}</td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- Payment Transactions -->
        @if ($transactions->count() > 0)
            <div class="invoice-items">
                <h3>PAYMENT TRANSACTIONS</h3>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Date</th>
                            <th>Payment Method</th>
                            <th class="text-right">Amount Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->reference }}</td>
                                <td>{{ $transaction->date->format('M d, Y') }}</td>
                                <td>{{ $transaction->payment_method->name ?? 'N/A' }}</td>
                                <td class="text-right">${{ number_format($transaction->amount_paid, 2) }}</td>
                                <td>{{ ucfirst($transaction->status) }}</td>
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
                    <p>Please make payment by the due date to avoid service interruption.</p>
                    <p>Payment can be made at any branch or via online payment.</p>
                </div>

                <div class="footer-box">
                    <h4>Contact Information</h4>
                    <p>For billing inquiries:</p>
                    <p>Email: billing@example.com</p>
                    <p>Phone: +255 XXX XXX XXX</p>
                </div>
            </div>

            <div class="terms-conditions mt-20">
                <p><strong>Terms & Conditions:</strong></p>
                <p>1. Payment is due within {{ $invoice->due_date->diffInDays($invoice->invoice_date) }} days of
                    invoice date.</p>
                <p>2. Late payments may result in service suspension.</p>
                <p>3. All amounts are in USD.</p>
            </div>

            <div class="text-center mt-20">
                <p>Generated on {{ $generated_at }}</p>
                <p>This is a computer-generated invoice. No signature is required.</p>
            </div>
        </div>
    </div>
</body>

</html>
