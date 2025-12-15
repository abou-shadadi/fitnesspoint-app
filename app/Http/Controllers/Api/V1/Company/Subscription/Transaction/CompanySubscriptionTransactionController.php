<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionTransaction;
use App\Models\Payment\PaymentMethod;
use App\Models\Branch\Branch;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\Billing\BillingType;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CompanySubscriptionTransactionController extends Controller
{
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
                ->with(['payment_method', 'branch', 'created_by', 'company_subscription']);

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
                'billing_description' => $this->getBillingDescription($subscription),
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
     *             required={"reference", "payment_method_id", "branch_id"},
     *             @OA\Property(property="reference", type="string", example="CTXN-2024-001"),
     *             @OA\Property(property="amount_due", type="number", format="float", example=100000.00),
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
     *     @OA\Response(response=404, description="Company, subscription, payment method, or branch not found"),
     *     @OA\Response(response=409, description="Duplicate reference"),
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
            'reference' => 'required|string|max:100|unique:company_subscription_transactions,reference',
            'amount_due' => 'required|numeric|min:0.01',
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
        $existingTransaction = CompanySubscriptionTransaction::where('reference', $request->input('reference'))->first();
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
            // Calculate dates based on subscription
            $currentExpiryDate = $subscription->end_date ?: now();
            $nextExpiryDate = null;

            // Calculate next expiry date if duration type exists
            if ($subscription->duration_type) {
                $duration = $subscription->duration_type->duration ?? 1;
                $unit = $subscription->duration_type->unit ?? 'days';

                $nextExpiryDate = $this->calculateNextExpiryDate($currentExpiryDate, $duration, $unit);
            }

            // Calculate amount due based on billing type
            $calculatedAmountDue = $this->calculateAmountDue($subscription);

            // Use calculated amount if not provided
            $amountDue = $request->input('amount_due', $calculatedAmountDue);

            // Create transaction
            $transaction = new CompanySubscriptionTransaction([
                'company_subscription_id' => $subscriptionId,
                'reference' => $request->input('reference'),
                'amount_due' => $amountDue,
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

            // If transaction status is "completed", update subscription status and end_date
            if ($transaction->status === 'completed') {
                $subscription->status = 'in_progress';
                if ($nextExpiryDate) {
                    $subscription->end_date = $nextExpiryDate;
                }
                $subscription->save();
            }

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction created successfully',
                'data' => [
                    'transaction' => $transaction,
                    'billing_info' => [
                        'type' => $subscription->billing_type->key ?? 'unknown',
                        'description' => $this->getBillingDescription($subscription),
                        'calculated_amount_due' => $calculatedAmountDue,
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
                ->with(['payment_method', 'branch', 'created_by', 'company_subscription.company', 'company_subscription.billing_type'])
                ->find($id);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                    'data' => null
                ], 404);
            }

            // Get billing description
            $billingDescription = $this->getBillingDescription($transaction->company_subscription);

            return response()->json([
                'success' => true,
                'message' => 'Company subscription transaction retrieved successfully',
                'data' => [
                    'transaction' => $transaction,
                    'billing_info' => [
                        'type' => $transaction->company_subscription->billing_type->key ?? 'unknown',
                        'description' => $billingDescription,
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
     *             @OA\Property(property="amount_due", type="number", format="float", example=120000.00),
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
     *     @OA\Response(response=409, description="Duplicate reference"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $subscriptionId, $id)
    {
        // Find transaction
        $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
            ->with(['company_subscription.billing_type', 'company_subscription.duration_type'])
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
            'amount_due' => 'nullable|numeric|min:0.01',
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

        // Start database transaction
        DB::beginTransaction();

        try {
            $oldStatus = $transaction->status;
            $newStatus = $request->input('status', $transaction->status);

            // Prepare update data
            $updateData = [];
            if ($request->has('reference')) $updateData['reference'] = $request->input('reference');
            if ($request->has('amount_due')) $updateData['amount_due'] = $request->input('amount_due');
            if ($request->has('amount_paid')) $updateData['amount_paid'] = $request->input('amount_paid');
            if ($request->has('date')) $updateData['date'] = $request->input('date');
            if ($request->has('payment_method_id')) $updateData['payment_method_id'] = $request->input('payment_method_id');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('attachment')) $updateData['attachment'] = $request->input('attachment');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('status')) $updateData['status'] = $newStatus;

            // Handle status change to "completed"
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $subscription = $transaction->company_subscription;

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

            // Update transaction
            $transaction->update($updateData);

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company']);

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
        try {
            $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
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
                $transaction->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Company subscription transaction deleted successfully',
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
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStatus(Request $request, $companyId, $subscriptionId, $id)
    {
        // Find transaction
        $transaction = CompanySubscriptionTransaction::where('company_subscription_id', $subscriptionId)
            ->with(['company_subscription.billing_type', 'company_subscription.duration_type'])
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

            // Handle status change to "completed"
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $subscription = $transaction->company_subscription;

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

            // Update transaction status
            $transaction->status = $newStatus;
            $transaction->save();

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'company_subscription.company']);

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
                    'description' => $this->getBillingDescription($subscription),
                    'calculated_amount_due' => $this->calculateAmountDue($subscription),
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
     * Calculate amount due based on billing type
     */
    private function calculateAmountDue(CompanySubscription $subscription)
    {
        $billingTypeKey = $subscription->billing_type->key ?? 'unknown';

        switch ($billingTypeKey) {
            case 'per_pass':
                // Count completed check-ins for this company subscription
                $checkInCount = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($query) use ($subscription) {
                    $query->where('company_subscription_id', $subscription->id);
                })
                    ->where('status', 'completed')
                    ->count();

                return $checkInCount * $subscription->unit_price;

            case 'retail_fixed':
                // Fixed price based on duration
                $duration = $subscription->duration_type->duration ?? 1;
                return $subscription->unit_price * $duration;

            default:
                return $subscription->unit_price;
        }
    }

    /**
     * Get billing description based on billing type
     */
    private function getBillingDescription(CompanySubscription $subscription)
    {
        $billingTypeKey = $subscription->billing_type->key ?? 'unknown';
        $duration = $subscription->duration_type->duration ?? 1;
        $unit = $subscription->duration_type->unit ?? 'days';
        $unitPrice = $subscription->unit_price;

        switch ($billingTypeKey) {
            case 'per_pass':
                $checkInCount = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($query) use ($subscription) {
                    $query->where('company_subscription_id', $subscription->id);
                })
                    ->where('status', 'completed')
                    ->count();

                return "Per-Pass Billing: {$checkInCount} completed check-ins × {$unitPrice} = " . ($checkInCount * $unitPrice);

            case 'retail_fixed':
                return "Retail Fixed Billing: {$duration} {$unit} × {$unitPrice} = " . ($duration * $unitPrice);

            default:
                return "Standard Billing: {$unitPrice} per billing period";
        }
    }

    /**
     * Calculate next expiry date
     */
    private function calculateNextExpiryDate($startDate, $duration, $unit)
    {
        $start = \Carbon\Carbon::parse($startDate);

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
}
