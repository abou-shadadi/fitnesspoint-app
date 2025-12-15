<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Plan\Plan;
use App\Models\Branch\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class MemberSubscriptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions",
     *     summary="List member subscriptions",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         description="Member ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "in_progress", "cancelled", "expired", "refunded", "rejected"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="plan_id",
     *         in="query",
     *         required=false,
     *         description="Filter by plan ID",
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
     *         description="Filter by start date from (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter by start date to (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Response(response=200, description="List of member subscriptions"),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $memberId)
    {
        try {
            $member = Member::find($memberId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                    'data' => null
                ], 404);
            }

            $query = MemberSubscription::where('member_id', $memberId)
                ->with(['plan', 'branch', 'created_by']);

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])) {
                $query->where('status', $request->status);
            }

            // Filter by plan_id
            if ($request->has('plan_id') && $request->plan_id) {
                $query->where('plan_id', $request->plan_id);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('start_date', '<=', $request->date_to);
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            $subscriptions = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Member subscriptions retrieved successfully',
                'data' => $subscriptions
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
     *     path="/api/members/{memberId}/subscriptions",
     *     summary="Create a new member subscription",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"plan_id", "start_date", "branch_id"},
     *             @OA\Property(property="plan_id", type="integer", example=1),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="notes", type="string", example="Annual membership subscription"),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "in_progress", "cancelled", "expired", "refunded", "rejected"},
     *                 example="pending"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Subscription created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Member, plan, or branch not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $memberId)
    {
        // Find member
        $member = Member::find($memberId);
        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Member not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'branch_id' => 'required|exists:branches,id',
            'status' => [
                'nullable',
                Rule::in(['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify plan exists
        $plan = Plan::find($request->input('plan_id'));
        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
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

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create subscription
            $subscription = new MemberSubscription([
                'member_id' => $memberId,
                'plan_id' => $request->input('plan_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'notes' => $request->input('notes'),
                'branch_id' => $request->input('branch_id'),
                'created_by_id' => Auth::id(),
                'status' => $request->input('status', 'pending'),
            ]);

            $subscription->save();

            // Load relationships
            $subscription->load(['plan', 'branch', 'created_by', 'member']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription created successfully',
                'data' => $subscription
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
     *     path="/api/members/{memberId}/subscriptions/{id}",
     *     summary="Get specific member subscription",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *     @OA\Response(response=200, description="Subscription details"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($memberId, $id)
    {
        try {
            $subscription = MemberSubscription::where('member_id', $memberId)
                ->with(['plan', 'branch', 'created_by', 'member'])
                ->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member subscription retrieved successfully',
                'data' => $subscription
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
     *     path="/api/members/{memberId}/subscriptions/{id}",
     *     summary="Update member subscription",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *             @OA\Property(property="plan_id", type="integer", example=2),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-02-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-01-31"),
     *             @OA\Property(property="notes", type="string", example="Updated subscription notes"),
     *             @OA\Property(property="branch_id", type="integer", example=2),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "in_progress", "cancelled", "expired", "refunded", "rejected"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subscription updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $memberId, $id)
    {
        // Find subscription
        $subscription = MemberSubscription::where('member_id', $memberId)->find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'plan_id' => 'nullable|exists:plans,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string',
            'branch_id' => 'nullable|exists:branches,id',
            'status' => [
                'nullable',
                Rule::in(['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify plan exists if provided
        if ($request->has('plan_id')) {
            $plan = Plan::find($request->input('plan_id'));
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found',
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
            if ($request->has('plan_id')) $updateData['plan_id'] = $request->input('plan_id');
            if ($request->has('start_date')) $updateData['start_date'] = $request->input('start_date');
            if ($request->has('end_date')) $updateData['end_date'] = $request->input('end_date');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('status')) $updateData['status'] = $request->input('status');

            // Update subscription
            $subscription->update($updateData);

            // Load relationships
            $subscription->load(['plan', 'branch', 'created_by', 'member']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription updated successfully',
                'data' => $subscription
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
     *     path="/api/members/{memberId}/subscriptions/{id}",
     *     summary="Delete member subscription",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *     @OA\Response(response=200, description="Subscription deleted successfully"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($memberId, $id)
    {
        try {
            $subscription = MemberSubscription::where('member_id', $memberId)->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                $subscription->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Member subscription deleted successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{id}/status",
     *     summary="Update subscription status",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
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
     *                 enum={"pending", "in_progress", "cancelled", "expired", "refunded", "rejected"},
     *                 example="in_progress"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStatus(Request $request, $memberId, $id)
    {
        // Find subscription
        $subscription = MemberSubscription::where('member_id', $memberId)->find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])
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
            $oldStatus = $subscription->status;
            $newStatus = $request->input('status');

            $subscription->update(['status' => $newStatus]);

            // Load relationships
            $subscription->load(['plan', 'branch', 'created_by', 'member']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Subscription status updated from '{$oldStatus}' to '{$newStatus}'",
                'data' => $subscription
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
     *     path="/api/members/{memberId}/subscriptions/active",
     *     summary="Get active member subscriptions",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Active subscriptions"),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function activeSubscriptions($memberId)
    {
        try {
            $member = Member::find($memberId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                    'data' => null
                ], 404);
            }

            // Active subscriptions are those with status 'in_progress'
            $activeSubscriptions = MemberSubscription::where('member_id', $memberId)
                ->where('status', 'in_progress')
                ->with(['plan', 'branch', 'created_by'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Active member subscriptions retrieved successfully',
                'data' => $activeSubscriptions
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
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/current",
     *     summary="Get current active subscription",
     *     tags={"Members | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Current subscription"),
     *     @OA\Response(response=404, description="Member not found or no active subscription"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function currentSubscription($memberId)
    {
        try {
            $member = Member::find($memberId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                    'data' => null
                ], 404);
            }

            // Get the most recent active subscription
            $currentSubscription = MemberSubscription::where('member_id', $memberId)
                ->where('status', 'in_progress')
                ->whereDate('end_date', '>=', now()) // Not expired
                ->orWhereNull('end_date') // Or no end date
                ->with(['plan', 'branch', 'created_by'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$currentSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found for this member',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Current member subscription retrieved successfully',
                'data' => $currentSubscription
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
