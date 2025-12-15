<?php

namespace App\Http\Controllers\Api\V1\Utils\Plan;

use App\Http\Controllers\Controller;
use App\Models\Plan\Plan;
use App\Models\Plan\PlanBenefit;
use App\Models\Benefit\Benefit;
use Illuminate\Http\Request;

class PlanBenefitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/plans/{id}/benefits",
     *     summary="List all benefits under a plan",
     *     tags={"Utils| Plan | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Plan ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Plan not found")
     * )
     */
    public function index($id)
    {
        $plan = Plan::with('benefits.benefit')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $plan->benefits
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/utils/plans/{id}/benefits",
     *     summary="Attach a benefit to a plan",
     *     tags={"Utils| Plan | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Plan ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *            @OA\Property(property="benefit_id", type="integer", example=1),
     *            @OA\Property(property="status", type="string", example="active")
     *        )
     *     ),
     *     @OA\Response(response=201, description="Benefit attached"),
     *     @OA\Response(response=404, description="Plan or Benefit not found")
     * )
     */
    public function store(Request $request, $id)
    {
        $request->validate([
            'benefit_id' => 'required|exists:benefits,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $plan = Plan::findOrFail($id);

        // Prevent duplicates
        $existing = PlanBenefit::where('plan_id', $id)
            ->where('benefit_id', $request->benefit_id)
            ->first();

        if ($existing) {
            return response()->json([
                'status' => false,
                'message' => 'Benefit already attached.'
            ], 409);
        }

        $benefit = PlanBenefit::create([
            'plan_id' => $id,
            'benefit_id' => $request->benefit_id,
            'status' => $request->status ?? 'active'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Benefit added to plan successfully',
            'data' => $benefit
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/utils/plans/{id}/benefits/{benefit_id}",
     *     summary="Update plan benefit status",
     *     tags={"Utils| Plan | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Parameter(name="benefit_id", in="path", required=true),
     *     @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *            @OA\Property(property="status", type="string", example="inactive")
     *        )
     *     ),
     *     @OA\Response(response=200, description="Updated"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function update(Request $request, $id, $benefitId)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        $planBenefit = PlanBenefit::where('plan_id', $id)
            ->where('benefit_id', $benefitId)
            ->firstOrFail();

        $planBenefit->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Benefit status updated',
            'data' => $planBenefit
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/utils/plans/{id}/benefits/{benefit_id}",
     *     summary="Remove a benefit from a plan",
     *     tags={"Utils| Plan | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Parameter(name="benefit_id", in="path", required=true),
     *     @OA\Response(response=200, description="Removed successfully")
     * )
     */
    public function destroy($id, $benefitId)
    {
        $planBenefit = PlanBenefit::where('plan_id', $id)
            ->where('benefit_id', $benefitId)
            ->firstOrFail();

        $planBenefit->delete();

        return response()->json([
            'status' => true,
            'message' => 'Benefit removed from plan'
        ]);
    }
}
