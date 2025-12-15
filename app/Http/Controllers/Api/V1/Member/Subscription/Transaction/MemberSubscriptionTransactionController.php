<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionTransaction;
use App\Models\Payment\PaymentMethod;
use App\Models\Branch\Branch;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
                ->with(['payment_method', 'branch', 'created_by', 'member_subscription']);

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
                $query->where('amount', '>=', $request->amount_min);
            }

            if ($request->has('amount_max') && $request->amount_max) {
                $query->where('amount', '<=', $request->amount_max);
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            $transactions = $query->get();

            // Calculate summary statistics
            $summary = [
                'total_transactions' => $transactions->count(),
                'total_amount' => $transactions->sum('amount'),
                'completed_amount' => $transactions->where('status', 'completed')->sum('amount'),
                'pending_amount' => $transactions->where('status', 'pending')->sum('amount'),
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
     *             required={"reference", "amount", "date", "payment_method_id", "branch_id"},
     *             @OA\Property(property="reference", type="string", example="TXN-2024-001"),
     *             @OA\Property(property="amount", type="number", format="float", example=100000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *             @OA\Property(property="payment_method_id", type="integer", example=1),
     *             @OA\Property(property="branch_id", type="integer", example=1),
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
     *     @OA\Response(response=404, description="Member, subscription, payment method, or branch not found"),
     *     @OA\Response(response=409, description="Duplicate reference"),
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

        // Validate request with new columns
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string|max:100|unique:member_subscription_transactions,reference',
            'amount_due' => 'required|numeric|min:0.01',
            'amount_paid' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'branch_id' => 'required|exists:branches,id',
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
            // Calculate current_expiry_date
            // If subscription status is 'expired', use today's date as current_expiry_date
            // Otherwise use subscription's end_date
            $currentExpiryDate = null;
            if ($subscription->status === 'expired') {
                $currentExpiryDate = now();
            } else {
                $currentExpiryDate = Carbon::parse($subscription->end_date); // keep as Carbon
            }


            // Calculate next_expiry_date based on plan's duration_type
            $nextExpiryDate = null;
            if ($subscription->plan && $subscription->plan->duration && $subscription->plan->duration_type) {
                $duration = $subscription->plan->duration;
                $unit = $subscription->plan->duration_type->unit;
                $startDate = $currentExpiryDate ?: now();

                switch ($unit) {
                    case 'days':
                        $nextExpiryDate = $startDate->copy()->addDays($duration);
                        break;
                    case 'weeks':
                        $nextExpiryDate = $startDate->copy()->addWeeks($duration);
                        break;
                    case 'months':
                        $nextExpiryDate = $startDate->copy()->addMonths($duration);
                        break;
                    case 'years':
                        $nextExpiryDate = $startDate->copy()->addYears($duration);
                        break;
                    default:
                        $nextExpiryDate = $startDate->copy()->addDays($duration);
                        break;
                }
            }

            // Create transaction with new columns
            $transaction = new MemberSubscriptionTransaction([
                'member_subscription_id' => $subscriptionId,
                'reference' => $request->input('reference'),
                'amount_due' => $request->input('amount_due'),
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
            if ($request->input('status') === 'completed' || $transaction->status === 'completed') {
                $subscription->status = 'in_progress'; // Or whatever status you want
                $subscription->end_date = $nextExpiryDate; // Update subscription end_date to next_expiry_date
                $subscription->save();
            }

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'member_subscription.member']);

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
                ->with(['payment_method', 'branch', 'created_by', 'member_subscription.member'])
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
     *             @OA\Property(property="amount", type="number", format="float", example=120000.00),
     *             @OA\Property(property="date", type="string", format="date", example="2024-01-16"),
     *             @OA\Property(property="payment_method_id", type="integer", example=2),
     *             @OA\Property(property="branch_id", type="integer", example=2),
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
    public function update(Request $request, $memberId, $subscriptionId, $id)
    {
        // Find transaction with subscription relationship
        $transaction = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)
            ->with(['member_subscription.plan.duration_type'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
                'data' => null
            ], 404);
        }

        // Validate request with new columns
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|max:100|unique:member_subscription_transactions,reference,' . $id,
            'amount_due' => 'nullable|numeric|min:0.01',
            'amount_paid' => 'nullable|numeric|min:0.01',
            'date' => 'nullable|date',
            'payment_method_id' => 'nullable|exists:payment_methods,id',
            'branch_id' => 'nullable|exists:branches,id',
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

            // Handle status change and recalculate dates if needed
            if ($request->has('status')) {
                $updateData['status'] = $request->input('status');

                // If status is being changed to "completed", recalculate dates
                if ($request->input('status') === 'completed' && $transaction->status !== 'completed') {
                    $subscription = $transaction->member_subscription;

                    // Calculate current_expiry_date
                    $currentExpiryDate = null;
                    if ($subscription->status === 'expired') {
                        $currentExpiryDate = now();
                    } else {
                        $currentExpiryDate = Carbon::parse($subscription->end_date);
                    }

                    // Calculate next_expiry_date based on plan's duration_type
                    $nextExpiryDate = null;
                    if ($subscription->plan && $subscription->plan->duration && $subscription->plan->duration_type) {
                        $duration = $subscription->plan->duration;
                        $unit = $subscription->plan->duration_type->unit;
                        $startDate = $currentExpiryDate ?: now();

                        switch ($unit) {
                            case 'days':
                                $nextExpiryDate = $startDate->copy()->addDays($duration);
                                break;
                            case 'weeks':
                                $nextExpiryDate = $startDate->copy()->addWeeks($duration);
                                break;
                            case 'months':
                                $nextExpiryDate = $startDate->copy()->addMonths($duration);
                                break;
                            case 'years':
                                $nextExpiryDate = $startDate->copy()->addYears($duration);
                                break;
                            default:
                                $nextExpiryDate = $startDate->copy()->addDays($duration);
                                break;
                        }
                    }

                    $updateData['current_expiry_date'] = $currentExpiryDate;
                    $updateData['next_expiry_date'] = $nextExpiryDate;

                    // Update subscription status and end_date
                    $subscription->status = 'in_progress';
                    $subscription->end_date = $nextExpiryDate;
                    $subscription->save();
                }
            }

            // Update transaction
            $transaction->update($updateData);

            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'member_subscription.member']);

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
            ->with(['member_subscription.plan.duration_type'])
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

            // If changing to "completed", calculate dates and update subscription
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $subscription = $transaction->member_subscription;

                // Calculate current_expiry_date
                $currentExpiryDate = null;
                if ($subscription->status === 'expired') {
                    $currentExpiryDate = now();
                } else {
                    $currentExpiryDate = Carbon::parse($subscription->end_date);
                }

                // Calculate next_expiry_date based on plan's duration_type
                $nextExpiryDate = null;
                if ($subscription->plan && $subscription->plan->duration && $subscription->plan->duration_type) {
                    $duration = $subscription->plan->duration;
                    $unit = $subscription->plan->duration_type->unit;
                    $startDate = $currentExpiryDate ?: now();

                    switch ($unit) {
                        case 'days':
                            $nextExpiryDate = $startDate->copy()->addDays($duration);
                            break;
                        case 'weeks':
                            $nextExpiryDate = $startDate->copy()->addWeeks($duration);
                            break;
                        case 'months':
                            $nextExpiryDate = $startDate->copy()->addMonths($duration);
                            break;
                        case 'years':
                            $nextExpiryDate = $startDate->copy()->addYears($duration);
                            break;
                        default:
                            $nextExpiryDate = $startDate->copy()->addDays($duration);
                            break;
                    }
                }

                // Update transaction with dates
                $transaction->update([
                    'status' => $newStatus,
                    'current_expiry_date' => $currentExpiryDate,
                    'next_expiry_date' => $nextExpiryDate
                ]);

                // Update subscription status and end_date
                $subscription->status = 'in_progress';
                $subscription->end_date = $nextExpiryDate;
                $subscription->save();
            } else {
                // For other status changes, just update the status
                $transaction->update(['status' => $newStatus]);
            }
            // Load relationships
            $transaction->load(['payment_method', 'branch', 'created_by', 'member_subscription.member']);

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
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/summary",
     *     summary="Get transaction summary",
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
     *     @OA\Response(response=200, description="Transaction summary"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function summary($memberId, $subscriptionId)
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

            // Get all transactions for the subscription
            $transactions = MemberSubscriptionTransaction::where('member_subscription_id', $subscriptionId)->get();

            // Calculate detailed summary
            $summary = [
                'total_transactions' => $transactions->count(),
                'total_amount' => $transactions->sum('amount'),
                'status_breakdown' => [
                    'pending' => [
                        'count' => $transactions->where('status', 'pending')->count(),
                        'amount' => $transactions->where('status', 'pending')->sum('amount')
                    ],
                    'completed' => [
                        'count' => $transactions->where('status', 'completed')->count(),
                        'amount' => $transactions->where('status', 'completed')->sum('amount')
                    ],
                    'failed' => [
                        'count' => $transactions->where('status', 'failed')->count(),
                        'amount' => $transactions->where('status', 'failed')->sum('amount')
                    ],
                    'cancelled' => [
                        'count' => $transactions->where('status', 'cancelled')->count(),
                        'amount' => $transactions->where('status', 'cancelled')->sum('amount')
                    ],
                    'refunded' => [
                        'count' => $transactions->where('status', 'refunded')->count(),
                        'amount' => $transactions->where('status', 'refunded')->sum('amount')
                    ],
                    'rejected' => [
                        'count' => $transactions->where('status', 'rejected')->count(),
                        'amount' => $transactions->where('status', 'rejected')->sum('amount')
                    ]
                ],
                'payment_methods' => $transactions->groupBy('payment_method_id')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'amount' => $group->sum('amount')
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Transaction summary retrieved successfully',
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
     * @OA\Post(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/transactions/bulk-create",
     *     summary="Bulk create transactions",
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
     *             required={"transactions"},
     *             @OA\Property(
     *                 property="transactions",
     *                 type="array",
     *                 description="Array of transactions to create",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"reference", "amount", "date", "payment_method_id", "branch_id"},
     *                     @OA\Property(property="reference", type="string", example="TXN-2024-001"),
     *                     @OA\Property(property="amount", type="number", format="float", example=50000.00),
     *                     @OA\Property(property="date", type="string", format="date", example="2024-01-15"),
     *                     @OA\Property(property="payment_method_id", type="integer", example=1),
     *                     @OA\Property(property="branch_id", type="integer", example=1),
     *                     @OA\Property(property="notes", type="string", example="Payment note"),
     *                     @OA\Property(property="status", type="string", example="pending")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Transactions created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function bulkCreate(Request $request, $memberId, $subscriptionId)
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
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'transactions' => 'required|array|min:1',
            'transactions.*.reference' => 'required|string|max:100',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'transactions.*.date' => 'required|date',
            'transactions.*.payment_method_id' => 'required|exists:payment_methods,id',
            'transactions.*.branch_id' => 'required|exists:branches,id',
            'transactions.*.notes' => 'nullable|string',
            'transactions.*.status' => [
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

        // Start database transaction
        DB::beginTransaction();

        try {
            $createdTransactions = [];
            $failedTransactions = [];
            $duplicateReferences = [];

            // Collect all references to check for duplicates
            $allReferences = collect($request->input('transactions'))->pluck('reference');
            $existingReferences = MemberSubscriptionTransaction::whereIn('reference', $allReferences)
                ->pluck('reference')
                ->toArray();

            foreach ($request->input('transactions') as $index => $txnData) {
                try {
                    // Check for duplicate reference
                    if (in_array($txnData['reference'], $existingReferences)) {
                        $duplicateReferences[] = [
                            'index' => $index,
                            'reference' => $txnData['reference'],
                            'error' => 'Duplicate reference'
                        ];
                        continue;
                    }

                    // Check for duplicate in current batch
                    $currentBatchRefs = array_column($createdTransactions, 'reference');
                    if (in_array($txnData['reference'], $currentBatchRefs)) {
                        $duplicateReferences[] = [
                            'index' => $index,
                            'reference' => $txnData['reference'],
                            'error' => 'Duplicate reference in current batch'
                        ];
                        continue;
                    }

                    // Create transaction
                    $transaction = new MemberSubscriptionTransaction([
                        'member_subscription_id' => $subscriptionId,
                        'reference' => $txnData['reference'],
                        'amount' => $txnData['amount'],
                        'date' => $txnData['date'],
                        'payment_method_id' => $txnData['payment_method_id'],
                        'branch_id' => $txnData['branch_id'],
                        'notes' => $txnData['notes'] ?? null,
                        'created_by_id' => Auth::id(),
                        'status' => $txnData['status'] ?? 'pending',
                    ]);

                    $transaction->save();
                    $transaction->load(['payment_method', 'branch', 'created_by']);

                    $createdTransactions[] = $transaction;
                    $existingReferences[] = $txnData['reference']; // Add to existing to prevent duplicates in same batch
                } catch (\Exception $e) {
                    $failedTransactions[] = [
                        'index' => $index,
                        'reference' => $txnData['reference'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Commit transaction
            DB::commit();

            $response = [
                'success' => true,
                'message' => count($createdTransactions) . ' transaction(s) created successfully',
                'data' => [
                    'created' => $createdTransactions,
                    'total_created' => count($createdTransactions)
                ]
            ];

            // Add warnings if any issues
            if (!empty($duplicateReferences)) {
                $response['warning'] = count($duplicateReferences) . ' transaction(s) skipped due to duplicate references';
                $response['data']['duplicates'] = $duplicateReferences;
            }

            if (!empty($failedTransactions)) {
                $response['warning'] = (isset($response['warning']) ? $response['warning'] . ' and ' : '') .
                    count($failedTransactions) . ' transaction(s) failed';
                $response['data']['failed'] = $failedTransactions;
            }

            return response()->json($response, 201);
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
}
