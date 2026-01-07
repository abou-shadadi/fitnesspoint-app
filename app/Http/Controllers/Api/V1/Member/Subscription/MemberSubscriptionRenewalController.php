<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Plan\Plan;
use App\Services\Member\MemberSubscriptionService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberSubscriptionRenewalController extends Controller
{
    protected $subscriptionService;

    public function __construct(MemberSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * @OA\Post(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/renew",
     *     summary="Renew a subscription",
     *     tags={"Members | Subscriptions | Renewals"},
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
     *             @OA\Property(property="rate_type_id", type="integer", example=1),
     *             @OA\Property(property="tax_rate_id", type="integer", example=1),
     *             @OA\Property(property="discount_type_id", type="integer", description="Discount type ID", example=1),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-07"),
     *             @OA\Property(property="discount_amount", type="number", format="float", example=0),
     *             @OA\Property(property="notes", type="string", example="Renewal with special discount")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subscription renewed successfully"),
     *     @OA\Response(response=400, description="Bad request or cannot renew"),
     *     @OA\Response(response=404, description="Member, subscription, or plan not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function renew(Request $request, $memberId, $subscriptionId)
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
            ->with(['plan', 'plan.duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Check if subscription can be renewed
        if (!$this->subscriptionService->canRenew($subscription)) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription cannot be renewed',
                'data' => null
            ], 400);
        }

        // Validate request - REMOVED plan_id completely
        $validator = Validator::make($request->all(), [
            'rate_type_id' => 'nullable|exists:rate_types,id',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'discount_type_id' => 'nullable|exists:discount_types,id',
            'due_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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
            // Prepare options - NO plan_id option
            $options = [
                'rate_type_id' => $request->input('rate_type_id'),
                'tax_rate_id' => $request->input('tax_rate_id'),
                'discount_type_id' => $request->input('discount_type_id'),
                'due_date' => $request->input('due_date'),
                'discount_amount' => $request->input('discount_amount', 0),
                'notes' => $request->input('notes'),
            ];

            // Renew subscription - pass null for newPlan to use current plan
            $result = $this->subscriptionService->renewSubscription($subscription, null, $options);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription renewed successfully',
                'data' => [
                    'renewal_type' => $result['renewal_type'],
                    'start_date' => $result['start_date']->format('Y-m-d'),
                    'end_date' => $result['end_date']->format('Y-m-d'),
                    'current_subscription' => $subscription->fresh(), // Get updated subscription
                    'invoice' => $result['invoice'],
                ]
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
     * @OA\Post(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/upgrade",
     *     summary="Upgrade a subscription",
     *     tags={"Members | Subscriptions | Upgrades"},
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
     *             required={"plan_id"},
     *             @OA\Property(property="plan_id", type="integer", description="New plan ID", example=3),
     *             @OA\Property(property="prorate", type="boolean", description="Apply proration for unused time", example=true),
     *             @OA\Property(property="rate_type_id", type="integer", example=1),
     *             @OA\Property(property="tax_rate_id", type="integer", example=1),
     *             @OA\Property(property="discount_type_id", type="integer", description="Discount type ID", example=1),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-02-07"),
     *             @OA\Property(property="discount_amount", type="number", format="float", example=0),
     *             @OA\Property(property="notes", type="string", example="Upgrade to premium plan")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subscription upgraded successfully"),
     *     @OA\Response(response=400, description="Bad request or cannot upgrade"),
     *     @OA\Response(response=404, description="Member, subscription, or plan not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function upgrade(Request $request, $memberId, $subscriptionId)
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
            ->with(['plan', 'plan.duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request - REMOVED start_date
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:plans,id',
            'prorate' => 'nullable|boolean',
            'rate_type_id' => 'nullable|exists:rate_types,id',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'discount_type_id' => 'nullable|exists:discount_types,id',
            'due_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Get new plan
        $newPlan = Plan::with('duration_type')->find($request->input('plan_id'));
        if (!$newPlan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found',
                'data' => null
            ], 404);
        }

        // Check if subscription can be upgraded
        if (!$this->subscriptionService->canUpgrade($subscription, $newPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'This subscription cannot be upgraded to the selected plan',
                'data' => null
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Prepare options - REMOVED start_date
            $options = [
                'prorate' => $request->input('prorate', true),
                'rate_type_id' => $request->input('rate_type_id'),
                'tax_rate_id' => $request->input('tax_rate_id'),
                'discount_type_id' => $request->input('discount_type_id'),
                'due_date' => $request->input('due_date'),
                'discount_amount' => $request->input('discount_amount', 0),
                'notes' => $request->input('notes'),
            ];

            // Upgrade subscription
            $result = $this->subscriptionService->upgradeSubscription($subscription, $newPlan, $options);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully',
                'data' => [
                    'old_subscription' => $subscription,
                    'new_subscription' => $result['subscription'],
                    'invoice' => $result['invoice'],
                    'proration' => $result['proration'],
                ]
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/renewal-analysis",
     *     summary="Get detailed renewal analysis",
     *     tags={"Members | Subscriptions | Renewals"},
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
     *     @OA\Response(response=200, description="Analysis retrieved successfully"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getRenewalAnalysis($memberId, $subscriptionId)
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
                ->with(['plan', 'plan.duration_type', 'plan.currency'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            // Get available plans for upgrade
            $availablePlans = Plan::where('id', '!=', $subscription->plan_id)
                ->where('status', 'active')
                ->with(['duration_type', 'currency'])
                ->get();

            // Calculate detailed renewal analysis
            $analysis = $this->calculateDetailedRenewalAnalysis($subscription);

            return response()->json([
                'success' => true,
                'message' => 'Renewal analysis retrieved successfully',
                'data' => array_merge($analysis, [
                    'current_subscription' => $subscription,
                    'can_renew' => $this->subscriptionService->canRenew($subscription),
                    'upgrade_options' => [
                        'available_plans' => $availablePlans,
                        'can_upgrade' => $this->subscriptionService->canUpgrade($subscription, $availablePlans->first() ?? $subscription->plan),
                    ],
                ])
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
     * Calculate detailed renewal analysis
     */
    private function calculateDetailedRenewalAnalysis(MemberSubscription $subscription)
    {
        $now = now();
        $startDate = Carbon::parse($subscription->start_date);
        $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

        $daysUsed = $endDate ? $startDate->diffInDays($now) : 0;
        $daysTotal = $endDate ? $startDate->diffInDays($endDate) : 0;
        $daysRemaining = $endDate ? $now->diffInDays($endDate, false) : 0;
        $percentageUsed = $daysTotal > 0 ? ($daysUsed / $daysTotal) * 100 : 0;

        // Determine renewal type
        $renewalType = $this->determineRenewalTypeForAnalysis($subscription, $daysRemaining);

        // Calculate suggested renewal dates
        $suggestedDates = $this->calculateSuggestedRenewalDates($subscription, $renewalType, $daysRemaining);

        return [
            'status_summary' => [
                'current_status' => $subscription->status,
                'is_expired' => $subscription->status === 'expired' || ($endDate && $endDate->isPast()),
                'is_active' => $subscription->status === 'in_progress',
                'is_pending' => $subscription->status === 'pending',
                'days_used' => $daysUsed,
                'days_total' => $daysTotal,
                'days_remaining' => $daysRemaining,
                'percentage_used' => round($percentageUsed, 2),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate ? $endDate->format('Y-m-d') : null,
                'current_date' => $now->format('Y-m-d'),
            ],
            'renewal_analysis' => [
                'renewal_type' => $renewalType['type'],
                'description' => $renewalType['description'],
                'recommendation' => $renewalType['recommendation'],
                'can_renew_now' => $renewalType['can_renew_now'],
                'suggested_start_date' => $suggestedDates['start_date'],
                'suggested_end_date' => $suggestedDates['end_date'],
                'suggested_duration_days' => $suggestedDates['duration_days'],
            ],
        ];
    }

    /**
     * Determine renewal type for analysis
     */
    private function determineRenewalTypeForAnalysis(MemberSubscription $subscription, $daysRemaining)
    {
        $now = now();
        $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

        if (!$endDate) {
            return [
                'type' => 'new',
                'description' => 'New subscription without end date',
                'recommendation' => 'Set up as new subscription',
                'can_renew_now' => true,
            ];
        }

        if ($subscription->status === 'expired' || $endDate->isPast()) {
            return [
                'type' => 'expired_renewal',
                'description' => 'Subscription has expired',
                'recommendation' => 'Renew immediately starting from today',
                'can_renew_now' => true,
            ];
        }

        if ($daysRemaining <= 0) {
            return [
                'type' => 'expired_renewal',
                'description' => 'Subscription ends today or has ended',
                'recommendation' => 'Renew immediately',
                'can_renew_now' => true,
            ];
        }

        if ($daysRemaining <= 7) {
            return [
                'type' => 'early_renewal',
                'description' => "Subscription expires in {$daysRemaining} days",
                'recommendation' => 'Renew now to avoid service interruption',
                'can_renew_now' => true,
            ];
        }

        if ($daysRemaining <= 30) {
            return [
                'type' => 'pre_renewal',
                'description' => "Subscription expires in {$daysRemaining} days",
                'recommendation' => 'Consider renewing soon to secure your rate',
                'can_renew_now' => true,
            ];
        }

        return [
            'type' => 'future_renewal',
            'description' => "Subscription has {$daysRemaining} days remaining",
            'recommendation' => 'Renewal not needed yet',
            'can_renew_now' => false,
        ];
    }

    /**
     * Calculate suggested renewal dates
     */
    private function calculateSuggestedRenewalDates(MemberSubscription $subscription, $renewalType, $daysRemaining)
    {
        $now = now();
        $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

        switch ($renewalType['type']) {
            case 'expired_renewal':
                // Start from today, use plan duration
                $startDate = $now;
                break;

            case 'early_renewal':
            case 'pre_renewal':
                // Start from current end date to avoid overlap
                $startDate = $endDate;
                break;

            default:
                $startDate = $endDate ?: $now;
        }

        // Calculate end date based on current plan duration
        $duration = $subscription->plan->duration;
        $unit = $subscription->plan->duration_type->unit;

        $calculatedEndDate = $this->calculateEndDate($startDate, $unit, $duration);
        $durationDays = $startDate->diffInDays($calculatedEndDate);

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $calculatedEndDate->format('Y-m-d'),
            'duration_days' => $durationDays,
        ];
    }

    /**
     * Calculate end date helper
     */
    private function calculateEndDate(Carbon $startDate, $unit, $duration)
    {
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
                return $startDate->copy()->addDays($duration);
        }
    }
}
