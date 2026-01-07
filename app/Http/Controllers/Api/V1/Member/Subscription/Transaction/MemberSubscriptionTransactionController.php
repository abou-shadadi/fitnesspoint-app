<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionTransaction;
use App\Models\Member\MemberSubscriptionInvoice;
use App\Models\Payment\PaymentMethod;
use App\Models\Branch\Branch;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MemberSubscriptionTransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions",
     *     summary="List subscription transactions",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         description="Member ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscriptionId",
     *         in="path",
     *         required=true,
     *         description="Subscription ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of transactions"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $memberId, $subscriptionId)
    {
        try {
            // Verify member exists
            $member = Member::find($memberId);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to member
            $subscription = MemberSubscription::where('member_id', $memberId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $query = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
                ->with([
                    'payment_method',
                    'branch',
                    'created_by',
                    'member_subscription',
                    'member_subscription_invoice'
                ]);

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

            // Filter by invoice_id
            if ($request->has('invoice_id') && $request->invoice_id) {
                $query->where('member_subscription_invoice_id', $request->invoice_id);
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

            $transactions = $query->get();

            // Calculate summary statistics
            $summary = [
                'total_transactions' => $transactions->count(),
                'total_amount_due' => $transactions->sum('amount_due'),
                'total_amount_paid' => $transactions->sum('amount_paid'),
                'completed_amount_paid' => $transactions->where('status', 'completed')->sum('amount_paid'),
                'pending_amount_paid' => $transactions->where('status', 'pending')->sum('amount_paid'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Subscription transactions retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'summary' => $summary
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions",
     *     summary="Create a new transaction",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *             required={"reference", "amount_paid", "date", "payment_method_id", "branch_id", "member_subscription_invoice_id"},
     *             @OA\Property(property="reference", type="string", example="TXN-2024-001"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=100000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="member_subscription_invoice_id", type="integer", example=1),
     *             @OA\Property(property="attachment", type="string", example="invoice.pdf"),
     *             @OA\Property(property="notes", type="string", example="Payment for monthly subscription"),
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
     *     @OA\Response(response=404, description="Member, subscription, payment method, branch, or invoice not found"),
     *     @OA\Response(response=409, description="Duplicate reference"),
     *     @OA\Response(response=422, description="Validation error - amount paid exceeds invoice balance"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $memberId, $subscriptionId)
    {
        // Verify member exists
        $member = Member::find($memberId);
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
                'data' => null
            ], 404);
        }

        // Verify subscription exists and belongs to member
        $subscription = MemberSubscription::where('member_id', $memberId)
            ->with(['plan.duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request - amount_due is removed from request
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:100|unique:member_subscription_transactions,reference',
            'amount_paid' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'branch_id' => 'required|exists:branches,id',
            'member_subscription_invoice_id' => 'required|exists:member_subscription_invoices,id',
            'attachment' => 'nullable|string|max:255',
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

        // Verify invoice exists and belongs to this subscription
        $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
            ->find($request->input('member_subscription_invoice_id'));

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this subscription',
                'data' => null
            ], 404);
        }

        // Get invoice total_amount
        $invoiceAmount = $invoice->total_amount;

        // Calculate already paid amount for this invoice
        $alreadyPaid = MemberSubscriptionTransaction::where('member_subscription_invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount_paid');

        // Calculate remaining balance
        $remainingBalance = max(0, $invoiceAmount - $alreadyPaid);

        // Validate that amount_paid doesn't exceed remaining balance
        $amountPaid = $request->input('amount_paid');
        if ($amountPaid > $remainingBalance) {
            return response()->json([
                'success' => false,
                'message' => "Amount paid (${amountPaid}) exceeds invoice remaining balance (${remainingBalance})",
                'data' => [
                    'invoice_total' => $invoiceAmount,
                    'already_paid' => $alreadyPaid,
                    'remaining_balance' => $remainingBalance,
                    'amount_paid' => $amountPaid
                ]
            ], 422);
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

        // Check for duplicate reference
        $existingTransaction = MemberSubscriptionTransaction::where('reference', $request->input('reference'))->first();
        if ($existingTransaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction with this reference already exists',
                'data' => null
            ], 409);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Calculate current_expiry_date (use subscription's existing end_date)
            $currentExpiryDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

            // For payment transactions, next_expiry_date should be the same as current
            $nextExpiryDate = $currentExpiryDate;

            // Create transaction - amount_due comes from invoice total_amount
            $transaction = new MemberSubscriptionTransaction([
                'member_subscription_id' => $subscriptionId,
                'member_subscription_invoice_id' => $request->input('member_subscription_invoice_id'),
                'reference' => $request->input('reference'),
                'amount_due' => $invoiceAmount, // Get from invoice total_amount
                'amount_paid' => $request->input('amount_paid'),
                'date' => $request->input('date'),
                'payment_method_id' => $request->input('payment_method_id'),
                'branch_id' => $request->input('branch_id'),
                'attachment' => $request->input('attachment'),
                'notes' => $request->input('notes'),
                'current_expiry_date' => $currentExpiryDate,
                'next_expiry_date' => $nextExpiryDate,
                'created_by_id' => Auth::id(),
                'status' => $request->input('status', 'pending'),
            ]);

            $transaction->save();

            // Update invoice payment status
            $this->updateInvoicePaymentStatus($invoice, $transaction);

            // If transaction status is "completed" and invoice is fully paid, update subscription
            if ($transaction->status === 'completed') {
                $this->updateSubscriptionOnPayment($subscription, $transaction, $invoice);
            }

            // Load relationships
            $transaction->load([
                'payment_method',
                'branch',
                'created_by',
                'member_subscription.member',
                'member_subscription_invoice'
            ]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction created successfully',
                'data' => $transaction
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Get specific transaction",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
    public function show($memberId, $subscriptionId, $id)
    {
        try {
            // Verify member exists
            $member = Member::find($memberId);
            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to member
            $subscription = MemberSubscription::where('member_id', $memberId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $transaction = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
                ->with([
                    'payment_method',
                    'branch',
                    'created_by',
                    'member_subscription.member',
                    'member_subscription_invoice'
                ])
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
                'message' => 'Transaction retrieved successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Update transaction",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *             @OA\Property(property="reference", type="string", example="TXN-2024-001-UPDATED"),
     *             @OA\Property(property="amount_paid", type="number", format="float", example=120000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-16"),
     *             @OA\Property(property="payment_method_id", type="integer", example=2),
     *             @OA\Property(property="branch_id", type="integer", example=2),
     *             @OA\Property(property="member_subscription_invoice_id", type="integer", example=2),
     *             @OA\Property(property="attachment", type="string", example="updated_invoice.pdf"),
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
     *     @OA\Response(response=409, description="Duplicate reference"),
     *     @OA\Response(response=422, description="Validation error - amount paid exceeds invoice balance"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $memberId, $subscriptionId, $id)
    {
        // Find transaction with subscription relationship
        $transaction = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
            ->with(['member_subscription.plan.duration_type', 'member_subscription_invoice'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'data' => null
            ], 404);
        }

        // Validate request - amount_due is removed
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|max:100|unique:member_subscription_transactions,reference,' . $id,
            'amount_paid' => 'nullable|numeric|min:0.01',
            'date' => 'nullable|date',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'branch_id' => 'nullable|exists:branches,id',
            'member_subscription_invoice_id' => 'nullable|exists:member_subscription_invoices,id',
            'attachment' => 'nullable|string|max:255',
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

        // Get invoice (either existing or new if changing invoice_id)
        $invoice = null;
        if ($request->has('member_subscription_invoice_id')) {
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($request->input('member_subscription_invoice_id'));

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found for this subscription',
                    'data' => null
                ], 404);
            }
        } else {
            $invoice = $transaction->member_subscription_invoice;
        }

        // Validate amount_paid against invoice balance if amount_paid is being updated
        if ($request->has('amount_paid') && $invoice) {
            // Get invoice total_amount
            $invoiceAmount = $invoice->total_amount;

            // Calculate already paid amount for this invoice (excluding current transaction)
            $alreadyPaid = MemberSubscriptionTransaction::where('member_subscription_invoice_id', $invoice->id)
                ->where('id', '!=', $transaction->id) // Exclude current transaction
                ->where('status', 'completed')
                ->sum('amount_paid');

            // Calculate remaining balance
            $remainingBalance = max(0, $invoiceAmount - $alreadyPaid);

            // Validate that amount_paid doesn't exceed remaining balance
            $amountPaid = $request->input('amount_paid');
            if ($amountPaid > $remainingBalance) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount paid (${amountPaid}) exceeds invoice remaining balance (${remainingBalance})",
                    'data' => [
                        'invoice_total' => $invoiceAmount,
                        'already_paid' => $alreadyPaid,
                        'remaining_balance' => $remainingBalance,
                        'amount_paid' => $amountPaid
                    ]
                ], 422);
            }
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

        // Start database transaction
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            $newStatus = $request->input('status', $transaction->status);

            // Prepare update data
            $updateData = [];
            if ($request->has('reference')) $updateData['reference'] = $request->input('reference');
            if ($request->has('amount_paid')) $updateData['amount_paid'] = $request->input('amount_paid');
            if ($request->has('date')) $updateData['date'] = $request->input('date');
            if ($request->has('payment_method_id')) $updateData['payment_method_id'] = $request->input('payment_method_id');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('member_subscription_invoice_id')) $updateData['member_subscription_invoice_id'] = $request->input('member_subscription_invoice_id');
            if ($request->has('attachment')) $updateData['attachment'] = $request->input('attachment');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');

            // If invoice is changed, update amount_due from new invoice
            if ($request->has('member_subscription_invoice_id') && $invoice) {
                $updateData['amount_due'] = $invoice->total_amount;
            }

            // Handle status change
            if ($request->has('status')) {
                $updateData['status'] = $newStatus;

                // If changing to "completed", calculate dates based on invoice action
                if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                    $subscription = $transaction->member_subscription;

                    // Get invoice action to determine date logic
                    $invoiceAction = $invoice->action ?? 'new';

                    // Calculate dates based on invoice action
                    $dates = $this->calculateDatesForInvoice($subscription, $invoice, $invoiceAction);

                    $updateData['current_expiry_date'] = $dates['current_expiry_date'];
                    $updateData['next_expiry_date'] = $dates['next_expiry_date'];
                }
            }

            // Update transaction
            $transaction->update($updateData);

            // Update related invoice payment status
            $this->updateInvoicePaymentStatus($invoice, $transaction);

            // If transaction is completed and invoice is fully paid, update subscription
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $this->updateSubscriptionOnPayment($transaction->member_subscription, $transaction, $invoice);
            }

            // Load relationships
            $transaction->load([
                'payment_method',
                'branch',
                'created_by',
                'member_subscription.member',
                'member_subscription_invoice'
            ]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/{id}",
     *     summary="Delete transaction",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
    public function destroy($memberId, $subscriptionId, $id)
    {
        try {
            $transaction = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
                ->with('member_subscription_invoice')
                ->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Store invoice reference before deletion
                $invoice = $transaction->member_subscription_invoice;

                // Delete transaction
                $transaction->delete();

                // Recalculate invoice payment status if invoice exists
                if ($invoice) {
                    $this->recalculateInvoicePaymentStatus($invoice);
                }

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction deleted successfully',
                    'data' => null
                ], 200);
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                throw $e;
            }
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/{id}/status",
     *     summary="Update transaction status",
     *     tags={"Members | Subscriptions | Transactions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStatus(Request $request, $memberId, $subscriptionId, $id)
    {
        // Find transaction with subscription relationship
        $transaction = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
            ->with(['member_subscription.plan.duration_type', 'member_subscription_invoice'])
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

        // Start database transaction
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            $newStatus = $request->input('status');

            // If changing to "completed", update dates and status
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $subscription = $transaction->member_subscription;
                $invoice = $transaction->member_subscription_invoice;

                // Get invoice action to determine date logic
                $invoiceAction = $invoice->action ?? 'new';

                // Calculate dates based on invoice action
                $dates = $this->calculateDatesForInvoice($subscription, $invoice, $invoiceAction);

                // Update transaction with calculated dates
                $transaction->update([
                    'status' => $newStatus,
                    'current_expiry_date' => $dates['current_expiry_date'],
                    'next_expiry_date' => $dates['next_expiry_date']
                ]);

                // Update invoice payment status
                if ($invoice) {
                    $this->updateInvoicePaymentStatus($invoice, $transaction);
                }

                // Update subscription based on invoice action
                $this->updateSubscriptionOnPayment($subscription, $transaction, $invoice);
            } else {
                // For other status changes, just update the status
                $transaction->update(['status' => $newStatus]);

                // Update invoice payment status if moving from completed to another status
                if ($oldStatus === 'completed' && $newStatus !== 'completed' && $transaction->member_subscription_invoice) {
                    $this->recalculateInvoicePaymentStatus($transaction->member_subscription_invoice);
                }
            }

            // Load relationships
            $transaction->load([
                'payment_method',
                'branch',
                'created_by',
                'member_subscription.member',
                'member_subscription_invoice'
            ]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Transaction status updated from '{$oldStatus}' to '{$newStatus}'",
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
     * Calculate dates for transaction based on invoice action
     */
    private function calculateDatesForInvoice(MemberSubscription $subscription, MemberSubscriptionInvoice $invoice, $action)
    {
        $currentExpiryDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

        switch ($action) {
            case 'renew':
                // For renewals: current expiry is old end date, next expiry is new end date from invoice
                $currentExpiryDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;
                $nextExpiryDate = $invoice->to_date ? Carbon::parse($invoice->to_date) : null;
                break;

            case 'upgrade':
                // For upgrades: current expiry is old end date (from old subscription), next expiry is new end date
                $currentExpiryDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;
                $nextExpiryDate = $invoice->to_date ? Carbon::parse($invoice->to_date) : null;
                break;

            case 'new':
            default:
                // For new subscriptions: both dates are the same (no previous expiry)
                $currentExpiryDate = null;
                $nextExpiryDate = $invoice->to_date ? Carbon::parse($invoice->to_date) : null;
                break;
        }

        return [
            'current_expiry_date' => $currentExpiryDate,
            'next_expiry_date' => $nextExpiryDate
        ];
    }

    /**
     * Helper method to update invoice payment status
     */
    private function updateInvoicePaymentStatus(MemberSubscriptionInvoice $invoice, MemberSubscriptionTransaction $transaction)
    {
        // Get all completed transactions for this invoice
        $totalPaid = MemberSubscriptionTransaction::where('member_subscription_invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount_paid');

        // Get the invoice total_amount
        $invoiceTotal = $invoice->total_amount;

        // Determine payment status based on invoice schema
        if ($totalPaid >= $invoiceTotal) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partially_paid';
        } else {
            $paymentStatus = 'pending';
        }

        // Update invoice status
        $invoice->update([
            'status' => $paymentStatus,
        ]);
    }

    /**
     * Helper method to recalculate invoice payment status after transaction deletion
     */
    private function recalculateInvoicePaymentStatus(MemberSubscriptionInvoice $invoice)
    {
        // Get all completed transactions for this invoice
        $totalPaid = MemberSubscriptionTransaction::where('member_subscription_invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount_paid');

        // Get the invoice total_amount
        $invoiceTotal = $invoice->total_amount;

        // Determine payment status
        if ($totalPaid >= $invoiceTotal) {
            $paymentStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $paymentStatus = 'partially_paid';
        } else {
            $paymentStatus = 'pending';
        }

        // Update invoice
        $invoice->update([
            'status' => $paymentStatus,
        ]);
    }

    /**
     * Helper method to update subscription when payment is completed
     *//**
 * Helper method to update subscription when payment is completed
 */
private function updateSubscriptionOnPayment(MemberSubscription $subscription, MemberSubscriptionTransaction $transaction, MemberSubscriptionInvoice $invoice)
{
    // Check if invoice is fully paid
    $totalPaid = MemberSubscriptionTransaction::where('member_subscription_invoice_id', $invoice->id)
        ->where('status', 'completed')
        ->sum('amount_paid');

    $invoiceTotal = $invoice->total_amount;

    // Only proceed if invoice is fully paid
    if ($totalPaid >= $invoiceTotal) {
        // Get the invoice action to determine what type of adjustment is needed
        $action = $invoice->action ?? 'new';

        // Update subscription based on invoice action
        switch ($action) {
            case 'renew':
                // For renewals: Use invoice dates (already calculated correctly by service)
                $subscription->start_date = $invoice->from_date;
                $subscription->end_date = $invoice->to_date;
                $subscription->status = 'in_progress';
                $subscription->save();

                // Find and mark the original subscription as expired
                // The original subscription ID should be in the notes
                preg_match('/subscription #(\d+)/', $subscription->notes ?? '', $matches);
                if (!empty($matches[1])) {
                    $originalSubscriptionId = $matches[1];
                    $originalSubscription = MemberSubscription::find($originalSubscriptionId);

                    if ($originalSubscription && $originalSubscription->id != $subscription->id) {
                        $originalSubscription->update([
                            'status' => 'expired',
                            'notes' => $originalSubscription->notes . "\nRenewed by subscription #{$subscription->id} on " . now()->format('Y-m-d')
                        ]);
                    }
                }
                break;

            case 'upgrade':
                // For upgrades: Use invoice dates
                $subscription->start_date = $invoice->from_date;
                $subscription->end_date = $invoice->to_date;
                $subscription->status = 'in_progress';
                $subscription->save();

                // Find and cancel old subscription
                preg_match('/Upgrade from.*?#(\d+)/', $subscription->notes ?? '', $matches);
                if (!empty($matches[1])) {
                    $oldSubscriptionId = $matches[1];
                    $oldSubscription = MemberSubscription::find($oldSubscriptionId);

                    if ($oldSubscription && $oldSubscription->id != $subscription->id) {
                        $oldSubscription->update([
                            'status' => 'cancelled',
                            'notes' => $oldSubscription->notes . "\nUpgraded to subscription #{$subscription->id} on " . now()->format('Y-m-d')
                        ]);
                    }
                }
                break;

            case 'new':
            default:
                // For new subscriptions: Just activate it
                if ($subscription->status === 'pending') {
                    $subscription->status = 'in_progress';
                    $subscription->save();
                }
                break;
        }

        // Log the action
        // \Log::info("Subscription #{$subscription->id} updated with action '{$action}' after payment completion");
    }
}
}
