<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionTransaction;
use App\Models\Company\CompanySubscriptionInvoice;
use App\Models\Payment\PaymentMethod;
use App\Models\Branch\Branch;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\Billing\BillingType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\File\Base64Service;
use Carbon\Carbon;

class CompanySubscriptionTransactionController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions",
     *     summary="List company subscription transactions",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         description="Subscription ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "completed", "failed", "cancelled", "refunded", "rejected"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="payment_method_id",
     *         in="query",
     *         required=false,
     *         description="Filter by payment method ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Filter by date from (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter by date to (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="amount_min",
     *         in="query",
     *         required=false,
     *         description="Minimum amount",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Parameter(
     *         name="amount_max",
     *         in="query",
     *         required=false,
     *         description="Maximum amount",
     *         @OA\Schema(type="number", format="float")
     *     ),
     *     @OA\Response(response=200, description="List of transactions"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $companyId, $subscriptionId)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->with(['billing_type'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $query = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                ->with(['payment_method', 'branch', 'created_by', 'company_subscription', 'company_subscription_invoice']);

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'failed', 'cancelled', 'refunded', 'rejected'])) {
                $query->where('status', $request->status);
            }

            // Filter by payment_method_id
            if ($request->has('payment_method_id') && $request->payment_method_id) {
                $query->where('payment_method_id', $request->payment_method_id);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            // Filter by amount range
            if ($request->has('amount_min') && $request->amount_min) {
                $query->where('amount_paid', '>=', $request->amount_min);
            }

            if ($request->has('amount_max') && $request->amount_max) {
                $query->where('amount_paid', '<=', $request->amount_max);
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            // Pagination
            $perPage = $request->input('per_page', 15);
            $transactions = $query->paginate($perPage);

            // Calculate summary statistics
            $summary = [
                'total_transactions' => $transactions->total(),
                'total_amount_due' => $transactions->sum('amount_due'),
                'total_amount_paid' => $transactions->sum('amount_paid'),
                'completed_amount' => $transactions->where('status', 'completed')->sum('amount_paid'),
                'pending_amount' => $transactions->where('status', 'pending')->sum('amount_paid'),
                'billing_type' => $subscription->billing_type->key ?? 'unknown',
            ];

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transactions retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'summary' => $summary,
                    'pagination' => [
                        'current_page' => $transactions->currentPage(),
                        'last_page' => $transactions->lastPage(),
                        'per_page' => $transactions->perPage(),
                        'total' => $transactions->total(),
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions",
     *     summary="Create a new company subscription transaction",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"company_subscription_invoice_id", "payment_method_id", "branch_id", "amount_paid"},
     *             @OA\Property(property="company_subscription_invoice_id", type="integer", example=1),
     *             @OA\Property(property="reference", type="string", example="CTXN-2024-001"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=100000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="attachment", type="string", nullable=true, example="base64 encoded file"),
     *             @OA\Property(property="notes", type="string", example="Payment for company subscription"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "completed", "failed", "cancelled", "refunded", "rejected"},
     *                 example="pending"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Transaction created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company, subscription, invoice, payment method, or branch not found"),
     *     @OA\Response(response=409, description="Duplicate reference or payment exceeds total amount"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId, $subscriptionId)
    {
        // Verify company exists
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Verify subscription exists and belongs to company
        $subscription = CompanySubscription::where('company_id', $companyId)
            ->with(['billing_type', 'duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Company subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'company_subscription_invoice_id' => 'required|exists:company_subscription_invoices,id',
            'reference' => 'nullable|string|max:100|unique:company_subscription_transactions,reference',
            'amount_paid' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'branch_id' => 'required|exists:branches,id',
            'attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in(['pending', 'completed', 'failed', 'cancelled', 'refunded', 'rejected'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify invoice exists and belongs to the subscription
        $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
            ->find($request->company_subscription_invoice_id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this subscription',
                'data' => null
            ], 404);
        }

        // Check if invoice is already paid
        if ($invoice->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'This invoice is already paid',
                'data' => null
            ], 400);
        }

        // Check if invoice is cancelled or rejected
        if (in_array($invoice->status, ['cancelled', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot process payment for a ' . $invoice->status . ' invoice',
                'data' => null
            ], 400);
        }

        // Verify payment method exists
        $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
                'data' => null
            ], 404);
        }

        // Verify branch exists
        $branch = Branch::find($request->input('branch_id'));
        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
                'data' => null
            ], 404);
        }

        // Generate reference if not provided
        $reference = $request->input('reference', $this->generateTransactionReference());

        // Check for duplicate reference
        $existingTransaction = CompanySubscriptionTransaction::where('reference', $reference)->first();
        if ($existingTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction with this reference already exists',
                'data' => null
            ], 409);
        }

        // Check if payment amount exceeds total invoice amount
        $amountPaid = $request->input('amount_paid');
        $invoiceTotalAmount = $invoice->total_amount;

        // Get total already paid amount for this invoice
        $totalAlreadyPaid = CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount_paid');

        $remainingAmount = $invoiceTotalAmount - $totalAlreadyPaid;

        if ($amountPaid > $remainingAmount) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount exceeds remaining invoice amount. Remaining amount: ' . number_format($remainingAmount, 2),
                'data' => [
                    'invoice_total' => $invoiceTotalAmount,
                    'already_paid' => $totalAlreadyPaid,
                    'remaining_amount' => $remainingAmount,
                    'attempted_payment' => $amountPaid,
                    'excess_amount' => $amountPaid - $remainingAmount
                ]
            ], 409);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Get amount due from invoice
            $amountDue = $invoice->total_amount;
            $amountPaid = $request->input('amount_paid');

            // Calculate current and next expiry dates
            $currentExpiryDate = $subscription->end_date ?: now();
            $nextExpiryDate = null;

            // Calculate next expiry date if duration type exists
            if ($subscription->duration_type) {
                $duration = $subscription->duration_type->duration ?? 1;
                $unit = $subscription->duration_type->unit ?? 'days';

                $nextExpiryDate = $this->calculateNextExpiryDate($currentExpiryDate, $duration, $unit);
            }

            // Create transaction
            $transaction = new CompanySubscriptionTransaction([
                'company_subscription_invoice_id' => $request->company_subscription_invoice_id,
                'company_subscription_id' => $subscriptionId,
                'reference' => $reference,
                'amount_due' => $amountDue,
                'amount_paid' => $amountPaid,
                'date' => $request->input('date'),
                'payment_method_id' => $request->input('payment_method_id'),
                'branch_id' => $request->input('branch_id'),
                'notes' => $request->input('notes'),
                'current_expiry_date' => $currentExpiryDate,
                'next_expiry_date' => $nextExpiryDate,
                'created_by_id' => Auth::id(),
                'status' => $request->input('status', 'pending'),
            ]);

            $transaction->save();

            // Handle base64 file attachment
            if ($request->has('attachment') && $request->attachment) {
                $this->base64Service->processBase64File($transaction, $request->attachment, 'attachment');
            }

            // If transaction status is "completed", update subscription and invoice
            if ($transaction->status === 'completed') {
                $this->handleCompletedTransaction($transaction, $subscription, $invoice);
            }

            // Calculate total amount paid for this invoice
            $this->updateInvoicePaymentStatus($invoice);

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company', 'company_subscription_invoice']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction created successfully',
                'data' => [
                    'transaction' => $transaction,
                    'invoice_info' => [
                        'id' => $invoice->id,
                        'reference' => $invoice->reference,
                        'total_amount' => $invoice->total_amount,
                        'already_paid' => $totalAlreadyPaid + $amountPaid,
                        'remaining_amount' => $invoiceTotalAmount - ($totalAlreadyPaid + $amountPaid),
                        'status' => $invoice->status,
                    ]
                ]
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Get specific company subscription transaction",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Transaction details"),
     *     @OA\Response(response=404, description="Transaction not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $subscriptionId, $id)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)->find($subscriptionId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                ->with(['payment_method', 'branch', 'created_by', 'company_subscription.company', 'company_subscription_invoice'])
                ->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction retrieved successfully',
                'data' => $transaction
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Update company subscription transaction",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="reference", type="string", example="CTXN-2024-001-UPDATED"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=120000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-16"),
     *             @OA\Property(property="payment_method_id", type="integer", example=2),
     *             @OA\Property(property="branch_id", type="integer", example=2),
     *             @OA\Property(property="attachment", type="string", nullable=true),
     *             @OA\Property(property="notes", type="string", example="Updated payment notes"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "completed", "failed", "cancelled", "refunded", "rejected"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Transaction updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Transaction not found"),
     *     @OA\Response(response=409, description="Duplicate reference or payment exceeds total amount"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $subscriptionId, $id)
    {
        // Find transaction
        $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
            ->with(['company_subscription', 'company_subscription_invoice'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|max:100|unique:company_subscription_transactions,reference,' . $id,
            'amount_paid' => 'nullable|numeric|min:0.01',
            'date' => 'nullable|date',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'branch_id' => 'nullable|exists:branches,id',
            'attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in(['pending', 'completed', 'failed', 'cancelled', 'refunded', 'rejected'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify payment method exists if provided
        if ($request->has('payment_method_id')) {
            $paymentMethod = PaymentMethod::find($request->input('payment_method_id'));
            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not found',
                    'data' => null
                ], 404);
            }
        }

        // Verify branch exists if provided
        if ($request->has('branch_id')) {
            $branch = Branch::find($request->input('branch_id'));
            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => null
                ], 404);
            }
        }

        // Check if updating amount_paid
        if ($request->has('amount_paid')) {
            $newAmountPaid = $request->input('amount_paid');
            $invoice = $transaction->company_subscription_invoice;

            // Validate payment amount
            $validation = $this->validatePaymentAmount($invoice->id, $newAmountPaid, $id);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['data']
                ], 409);
            }
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            $newStatus = $request->input('status', $transaction->status);
            $subscription = $transaction->company_subscription;
            $invoice = $transaction->company_subscription_invoice;

            // Prepare update data
            $updateData = [];
            if ($request->has('reference')) $updateData['reference'] = $request->input('reference');
            if ($request->has('amount_paid')) $updateData['amount_paid'] = $request->input('amount_paid');
            if ($request->has('date')) $updateData['date'] = $request->input('date');
            if ($request->has('payment_method_id')) $updateData['payment_method_id'] = $request->input('payment_method_id');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('status')) $updateData['status'] = $newStatus;

            // Handle attachment update
            if ($request->has('attachment') && $request->attachment) {
                $this->base64Service->processBase64File($transaction, $request->attachment, 'attachment', true);
            }

            // Handle status change to "completed"
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                // Calculate new dates if needed
                if (!$transaction->next_expiry_date && $subscription->duration_type) {
                    $duration = $subscription->duration_type->duration ?? 1;
                    $unit = $subscription->duration_type->unit ?? 'days';
                    $currentExpiryDate = $transaction->current_expiry_date ?: ($subscription->end_date ?: now());

                    $nextExpiryDate = $this->calculateNextExpiryDate($currentExpiryDate, $duration, $unit);
                    $updateData['next_expiry_date'] = $nextExpiryDate;

                    // Update subscription end_date
                    $subscription->end_date = $nextExpiryDate;
                }

                // Update subscription status
                $subscription->status = 'in_progress';
                $subscription->save();
            }

            // Handle status change from "completed"
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                // Revert subscription status if no other completed transactions
                $completedTransactionsCount = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'completed')
                    ->where('id', '!=', $id)
                    ->count();

                if ($completedTransactionsCount === 0) {
                    $subscription->status = 'pending';
                    $subscription->save();
                }
            }

            // Update transaction
            $transaction->update($updateData);

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice);

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company', 'company_subscription_invoice']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction updated successfully',
                'data' => $transaction
            ], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Delete company subscription transaction",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Transaction deleted successfully"),
     *     @OA\Response(response=404, description="Transaction not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $subscriptionId, $id)
    {
        DB::beginTransaction();

        try {
            $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                ->with(['company_subscription', 'company_subscription_invoice'])
                ->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                    'data' => null
                ], 404);
            }

            $subscription = $transaction->company_subscription;
            $invoice = $transaction->company_subscription_invoice;

            // Check if this is a completed transaction
            if ($transaction->status === 'completed') {
                // Check if there are other completed transactions
                $completedTransactionsCount = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'completed')
                    ->where('id', '!=', $id)
                    ->count();

                // If no other completed transactions, revert subscription status
                if ($completedTransactionsCount === 0) {
                    $subscription->status = 'pending';
                    $subscription->save();
                }
            }

            // Delete transaction
            $transaction->delete();

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions/{id}/status",
     *     summary="Update company subscription transaction status",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "completed", "failed", "cancelled", "refunded", "rejected"},
     *                 example="completed"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Transaction not found"),
     *     @OA\Response(response=409, description="Payment would exceed invoice amount"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStatus(Request $request, $companyId, $subscriptionId, $id)
    {
        // Find transaction
        $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
            ->with(['company_subscription', 'company_subscription_invoice'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                Rule::in(['pending', 'completed', 'failed', 'cancelled', 'refunded', 'rejected'])
            ]
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        $newStatus = $request->input('status');
        $oldStatus = $transaction->status;

        // Check if changing to completed status
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $invoice = $transaction->company_subscription_invoice;

            // Validate payment amount
            $validation = $this->validatePaymentAmount($invoice->id, $transaction->amount_paid, $id);
            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $validation['message'],
                    'data' => $validation['data']
                ], 409);
            }
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            $subscription = $transaction->company_subscription;
            $invoice = $transaction->company_subscription_invoice;

            // Handle status change to "completed"
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                // Calculate new dates if needed
                if (!$transaction->next_expiry_date && $subscription->duration_type) {
                    $duration = $subscription->duration_type->duration ?? 1;
                    $unit = $subscription->duration_type->unit ?? 'days';
                    $currentExpiryDate = $transaction->current_expiry_date ?: ($subscription->end_date ?: now());

                    $nextExpiryDate = $this->calculateNextExpiryDate($currentExpiryDate, $duration, $unit);
                    $transaction->next_expiry_date = $nextExpiryDate;

                    // Update subscription end_date
                    $subscription->end_date = $nextExpiryDate;
                }

                // Update subscription status
                $subscription->status = 'in_progress';
                $subscription->save();
            }

            // Handle status change from "completed"
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                // Revert subscription status if no other completed transactions
                $completedTransactionsCount = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'completed')
                    ->where('id', '!=', $id)
                    ->count();

                if ($completedTransactionsCount === 0) {
                    $subscription->status = 'pending';
                    $subscription->save();
                }
            }

            // Update transaction status
            $transaction->status = $newStatus;
            $transaction->save();

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice);

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company', 'company_subscription_invoice']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Company subscription transaction status updated from '{$oldStatus}' to '{$newStatus}'",
                'data' => $transaction
            ], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/transactions/summary",
     *     summary="Get company subscription transaction summary",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Transaction summary"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function summary($companyId, $subscriptionId)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->with(['billing_type', 'duration_type'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Get all transactions for the subscription
            $transactions = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)->get();

            // Calculate detailed summary
            $summary = [
                'total_transactions' => $transactions->count(),
                'total_amount_due' => $transactions->sum('amount_due'),
                'total_amount_paid' => $transactions->sum('amount_paid'),
                'status_breakdown' => [
                    'pending' => [
                        'count' => $transactions->where('status', 'pending')->count(),
                        'amount_due' => $transactions->where('status', 'pending')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'pending')->sum('amount_paid')
                    ],
                    'completed' => [
                        'count' => $transactions->where('status', 'completed')->count(),
                        'amount_due' => $transactions->where('status', 'completed')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'completed')->sum('amount_paid')
                    ],
                    'failed' => [
                        'count' => $transactions->where('status', 'failed')->count(),
                        'amount_due' => $transactions->where('status', 'failed')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'failed')->sum('amount_paid')
                    ],
                    'cancelled' => [
                        'count' => $transactions->where('status', 'cancelled')->count(),
                        'amount_due' => $transactions->where('status', 'cancelled')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'cancelled')->sum('amount_paid')
                    ],
                    'refunded' => [
                        'count' => $transactions->where('status', 'refunded')->count(),
                        'amount_due' => $transactions->where('status', 'refunded')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'refunded')->sum('amount_paid')
                    ],
                    'rejected' => [
                        'count' => $transactions->where('status', 'rejected')->count(),
                        'amount_due' => $transactions->where('status', 'rejected')->sum('amount_due'),
                        'amount_paid' => $transactions->where('status', 'rejected')->sum('amount_paid')
                    ]
                ],
                'payment_methods' => $transactions->groupBy('payment_method_id')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount_due' => $group->sum('amount_due'),
                        'amount_paid' => $group->sum('amount_paid')
                    ];
                }),
                'billing_info' => [
                    'type' => $subscription->billing_type->key ?? 'unknown',
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction summary retrieved successfully',
                'data' => $summary
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Handle completed transaction
     */
    private function handleCompletedTransaction($transaction, $subscription, $invoice)
    {
        // Update subscription end_date if next_expiry_date is set
        if ($transaction->next_expiry_date) {
            $subscription->end_date = $transaction->next_expiry_date;
        }

        // Update subscription status
        $subscription->status = 'in_progress';
        $subscription->save();
    }

    /**
     * Enhanced invoice payment status update
     */
    private function updateInvoicePaymentStatus($invoice)
    {
        if (!$invoice) {
            return;
        }

        // Get all completed transactions for this invoice
        $transactions = CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->get();

        $totalPaid = $transactions->sum('amount_paid');
        $invoiceTotalAmount = $invoice->total_amount;

        // Calculate payment percentage
        $paymentPercentage = $invoiceTotalAmount > 0 ? ($totalPaid / $invoiceTotalAmount) * 100 : 0;

        // Determine invoice status
        if ($totalPaid >= $invoiceTotalAmount) {
            $invoice->status = 'paid';
        } elseif ($totalPaid > 0 && $totalPaid < $invoiceTotalAmount) {
            $invoice->status = 'partially_paid';
        } else {
            // Check if there are pending transactions
            $pendingTransactions = CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->exists();

            $invoice->status = $pendingTransactions ? 'pending' : 'unpaid';
        }

        // Save payment information
        $invoice->amount_paid = $totalPaid;
        $invoice->remaining_amount = max(0, $invoiceTotalAmount - $totalPaid);
        $invoice->payment_percentage = $paymentPercentage;

        // Update payment date if fully paid
        if ($invoice->status === 'paid' && !$invoice->payment_date) {
            $lastPaymentDate = $transactions->max('date');
            $invoice->payment_date = $lastPaymentDate;
        }

        $invoice->save();
    }

    /**
     * Calculate total paid amount for an invoice
     */
    private function calculateTotalPaidForInvoice($invoiceId)
    {
        return CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoiceId)
            ->where('status', 'completed')
            ->sum('amount_paid');
    }

    /**
     * Get invoice payment summary
     */
    private function getInvoicePaymentSummary($invoiceId)
    {
        $invoice = CompanySubscriptionInvoice::find($invoiceId);
        if (!$invoice) {
            return null;
        }

        $totalPaid = $this->calculateTotalPaidForInvoice($invoiceId);
        $remaining = max(0, $invoice->total_amount - $totalPaid);

        return [
            'invoice_total' => $invoice->total_amount,
            'total_paid' => $totalPaid,
            'remaining_amount' => $remaining,
            'payment_percentage' => $invoice->total_amount > 0 ? ($totalPaid / $invoice->total_amount) * 100 : 0,
            'status' => $invoice->status,
        ];
    }

    /**
     * Validate payment amount doesn't exceed invoice amount
     */
    private function validatePaymentAmount($invoiceId, $amountPaid, $excludeTransactionId = null)
    {
        $invoice = CompanySubscriptionInvoice::find($invoiceId);
        if (!$invoice) {
            return [
                'valid' => false,
                'message' => 'Invoice not found',
                'data' => null
            ];
        }

        // Get total already paid
        $query = CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoiceId)
            ->where('status', 'completed');

        if ($excludeTransactionId) {
            $query->where('id', '!=', $excludeTransactionId);
        }

        $totalAlreadyPaid = $query->sum('amount_paid');
        $remainingAmount = max(0, $invoice->total_amount - $totalAlreadyPaid);

        if ($amountPaid > $remainingAmount) {
            return [
                'valid' => false,
                'message' => 'Payment amount exceeds remaining invoice amount',
                'data' => [
                    'invoice_total' => $invoice->total_amount,
                    'already_paid' => $totalAlreadyPaid,
                    'remaining_amount' => $remainingAmount,
                    'attempted_payment' => $amountPaid,
                    'excess_amount' => $amountPaid - $remainingAmount
                ]
            ];
        }

        return [
            'valid' => true,
            'message' => 'Payment amount is valid',
            'data' => [
                'invoice_total' => $invoice->total_amount,
                'already_paid' => $totalAlreadyPaid,
                'remaining_amount' => $remainingAmount,
                'new_total_paid' => $totalAlreadyPaid + $amountPaid,
                'new_remaining' => $remainingAmount - $amountPaid
            ]
        ];
    }

    /**
     * Generate transaction reference
     */
    private function generateTransactionReference()
    {
        $prefix = 'CTXN-';
        $date = date('Ymd');
        $random = strtoupper(substr(uniqid(), -6));

        return $prefix . $date . '-' . $random;
    }

    /**
     * Calculate next expiry date
     */
    private function calculateNextExpiryDate($startDate, $duration, $unit)
    {
        $start = Carbon::parse($startDate);

        switch ($unit) {
            case 'days':
                return $start->copy()->addDays($duration);
            case 'weeks':
                return $start->copy()->addWeeks($duration);
            case 'months':
                return $start->copy()->addMonths($duration);
            case 'years':
                return $start->copy()->addYears($duration);
            default:
                return $start->copy()->addDays($duration);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/payment-summary",
     *     summary="Get payment summary for a specific invoice",
     *     tags={"Companies | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payment summary retrieved"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getInvoicePaymentSummaryEndpoint($companyId, $subscriptionId, $invoiceId)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)->find($subscriptionId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found for this subscription',
                    'data' => null
                ], 404);
            }

            // Get payment summary
            $paymentSummary = $this->getInvoicePaymentSummary($invoiceId);

            // Get all transactions for this invoice
            $transactions = CompanySubscriptionTransaction::where('company_subscription_invoice_id', $invoiceId)
                ->with(['payment_method', 'branch', 'created_by'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Invoice payment summary retrieved successfully',
                'data' => [
                    'invoice' => [
                        'id' => $invoice->id,
                        'reference' => $invoice->reference,
                        'total_amount' => $invoice->total_amount,
                        'status' => $invoice->status,
                        'invoice_date' => $invoice->invoice_date,
                        'due_date' => $invoice->due_date,
                    ],
                    'payment_summary' => $paymentSummary,
                    'transactions' => $transactions
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
