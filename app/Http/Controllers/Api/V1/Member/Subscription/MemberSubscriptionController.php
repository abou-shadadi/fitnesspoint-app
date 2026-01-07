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
use Carbon\Carbon;

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
                ->with(['plan', 'plan.duration_type', 'branch', 'created_by']);

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
     *             required={"plan_id", "branch_id"},
     *             @OA\Property(property="plan_id", type="integer", example=1),
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 description="Optional custom start date. If not provided, current date will be used.",
     *                 example="2024-01-01"
     *             ),
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
            'start_date' => 'nullable|date',
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

        // Verify plan exists with duration type relationship
        $plan = Plan::with('duration_type')->find($request->input('plan_id'));
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

        // Check if plan has duration type
        if (!$plan->duration_type) {
            return response()->json([
                'success' => false,
                'message' => 'Plan duration type not configured',
                'data' => null
            ], 400);
        }

        // Check if member already has an active subscription for this plan
        $hasActiveSubscription = MemberSubscription::where('member_id', $memberId)
            ->where('plan_id', $plan->id)
            ->where('status', 'in_progress')
            ->exists();

        if ($hasActiveSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member already has an active subscription for this plan. Please cancel or wait for the current subscription to expire.',
                'data' => null
            ], 400);
        }


        // restrict having two pending
        $hasPendingSubscription = MemberSubscription::where('member_id', $memberId)
            ->where('plan_id', $plan->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPendingSubscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member already has a pending subscription for this plan. Please cancel or wait for the current subscription to expire.',
                'data' => null
            ], 400);
        }


        // Start database transaction
        DB::beginTransaction();

        try {
            // Determine start date - use provided date or current date
            $startDate = $request->input('start_date')
                ? Carbon::parse($request->input('start_date'))
                : Carbon::now();

            // Calculate end date based on plan duration and duration type
            $endDate = $this->calculateEndDate($startDate, $plan->duration, $plan->duration_type);

            // Create subscription
            $subscription = new MemberSubscription([
                'member_id' => $memberId,
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'notes' => $request->input('notes'),
                'branch_id' => $branch->id,
                'created_by_id' => Auth::id(),
                'status' => $request->input('status', 'pending'),
            ]);

            $subscription->save();

            // Load relationships
            $subscription->load(['plan', 'plan.duration_type', 'branch', 'created_by', 'member']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription created successfully',
                'data' => [
                    'subscription' => $subscription,
                    'duration_info' => [
                        'duration' => $plan->duration,
                        'duration_type' => $plan->duration_type->unit,
                        'calculated_start_date' => $startDate->format('Y-m-d'),
                        'calculated_end_date' => $endDate->format('Y-m-d'),
                        'total_days' => $startDate->diffInDays($endDate)
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
                ->with(['plan', 'plan.duration_type', 'branch', 'created_by', 'member'])
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
     *             @OA\Property(
     *                 property="start_date",
     *                 type="string",
     *                 format="date",
     *                 description="If provided, end date will be recalculated based on plan duration",
     *                 example="2024-02-01"
     *             ),
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
        // Find subscription with plan relationship
        $subscription = MemberSubscription::where('member_id', $memberId)
            ->with(['plan.duration_type'])
            ->find($id);

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

        // Check if plan is being changed and member already has active subscription for new plan
        if ($request->has('plan_id') && $request->input('plan_id') != $subscription->plan_id) {
            $hasActiveSubscription = MemberSubscription::where('member_id', $memberId)
                ->where('plan_id', $request->input('plan_id'))
                ->where('status', 'in_progress')
                ->where('id', '!=', $id)
                ->exists();

            if ($hasActiveSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member already has an active subscription for this plan. Please cancel the existing subscription first.',
                    'data' => null
                ], 400);
            }
        }

        // Get the plan (either current or new)
        $plan = null;
        if ($request->has('plan_id')) {
            $plan = Plan::with('duration_type')->find($request->input('plan_id'));
            if (!$plan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Plan not found',
                    'data' => null
                ], 404);
            }
        } else {
            $plan = $subscription->plan;
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

            // Handle plan update
            if ($request->has('plan_id')) {
                $updateData['plan_id'] = $request->input('plan_id');

                // If plan changed, recalculate end date from start date
                $currentStartDate = $request->has('start_date')
                    ? Carbon::parse($request->input('start_date'))
                    : Carbon::parse($subscription->start_date);

                $updateData['end_date'] = $this->calculateEndDate($currentStartDate, $plan->duration, $plan->duration_type);
            }

            // Handle start date update
            if ($request->has('start_date')) {
                $newStartDate = Carbon::parse($request->input('start_date'));
                $updateData['start_date'] = $newStartDate;

                // Recalculate end date based on current plan
                if (!isset($updateData['end_date'])) {
                    $updateData['end_date'] = $this->calculateEndDate($newStartDate, $plan->duration, $plan->duration_type);
                }
            }

            // Handle other fields
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('status')) $updateData['status'] = $request->input('status');

            // Update subscription
            $subscription->update($updateData);

            // Load relationships
            $subscription->load(['plan', 'plan.duration_type', 'branch', 'created_by', 'member']);

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
            $subscription->load(['plan', 'plan.duration_type', 'branch', 'created_by', 'member']);

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
                ->with(['plan', 'plan.duration_type', 'branch', 'created_by'])
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
     *     @OA\Parameter(
     *         name="include_duration_info",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
     *     ),
     *     @OA\Response(response=200, description="Current subscription"),
     *     @OA\Response(response=404, description="Member not found or no active subscription"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function currentSubscription(Request $request, $memberId)
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
                ->where(function ($query) {
                    $query->whereDate('end_date', '>=', now()) // Not expired
                        ->orWhereNull('end_date'); // Or no end date
                })
                ->with(['plan', 'plan.duration_type', 'branch', 'created_by'])
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$currentSubscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active subscription found for this member',
                    'data' => null
                ], 404);
            }

            $responseData = $currentSubscription;

            // Include duration info if requested
            if ($request->boolean('include_duration_info') && $currentSubscription->plan) {
                $daysRemaining = 0;
                if ($currentSubscription->end_date) {
                    $endDate = Carbon::parse($currentSubscription->end_date);
                    $daysRemaining = max(0, now()->diffInDays($endDate, false));
                }

                $responseData = [
                    'subscription' => $currentSubscription,
                    'duration_info' => [
                        'days_remaining' => $daysRemaining,
                        'is_expired' => $daysRemaining <= 0,
                        'percentage_completed' => $this->calculateSubscriptionProgress($currentSubscription)
                    ]
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Current member subscription retrieved successfully',
                'data' => $responseData
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
     * Calculate end date based on start date, duration, and duration type
     */
    private function calculateEndDate(Carbon $startDate, int $duration, $durationType): Carbon
    {
        $unit = $durationType->unit ?? 'months'; // Default to months if not specified

        switch ($unit) {
            case 'days':
                return $startDate->copy()->addDays($duration);

            case 'weeks':
                return $startDate->copy()->addWeeks($duration);

            case 'months':
                return $startDate->copy()->addMonths($duration);

            case 'years':
                return $startDate->copy()->addYears($duration);

            default:
                return $startDate->copy()->addMonths($duration); // Default fallback
        }
    }

    /**
     * Calculate subscription progress percentage
     */
    private function calculateSubscriptionProgress($subscription): float
    {
        if (!$subscription->start_date || !$subscription->end_date) {
            return 0;
        }

        $startDate = Carbon::parse($subscription->start_date);
        $endDate = Carbon::parse($subscription->end_date);
        $now = Carbon::now();

        // If subscription hasn't started yet
        if ($now->lt($startDate)) {
            return 0;
        }

        // If subscription has ended
        if ($now->gt($endDate)) {
            return 100;
        }

        $totalDuration = $startDate->diffInSeconds($endDate);
        $elapsedDuration = $startDate->diffInSeconds($now);

        return min(100, max(0, ($elapsedDuration / $totalDuration) * 100));
    }
}
