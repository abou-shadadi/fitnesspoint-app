<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionBenefit;
use App\Models\Benefit\Benefit;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CompanySubscriptionBenefitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/benefits",
     *     summary="List benefits assigned to a company subscription",
     *     tags={"Companies | Subscriptions | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="List of subscription benefits"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index($companyId, $subscriptionId)
    {
        try {
            // Check if company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Check if subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $benefits = $subscription->benefits()->with('benefit')->get();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription benefits retrieved successfully',
                'data' => $benefits
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/benefits",
     *     summary="Assign a benefit to a company subscription",
     *     tags={"Companies | Subscriptions | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"benefit_id"},
     *             @OA\Property(property="benefit_id", type="integer", example=3),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Benefit assigned successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=409, description="Benefit already assigned"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId, $subscriptionId)
    {
        // Check if company exists
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Check if subscription exists and belongs to company
        $subscription = CompanySubscription::where('company_id', $companyId)
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
            'benefit_id' => 'required|exists:benefits,id',
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive'])
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
            // Check if benefit already assigned
            $exists = CompanySubscriptionBenefit::where('company_subscription_id', $subscriptionId)
                ->where('benefit_id', $request->input('benefit_id'))
                ->exists();

            if ($exists) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Benefit already assigned to this subscription',
                    'data' => null
                ], 409);
            }

            // Create subscription benefit
            $subscriptionBenefit = CompanySubscriptionBenefit::create([
                'company_subscription_id' => $subscriptionId,
                'benefit_id' => $request->input('benefit_id'),
                'status' => $request->input('status', 'active')
            ]);

            // Load benefit relationship
            $subscriptionBenefit->load('benefit');

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Benefit assigned to subscription successfully',
                'data' => $subscriptionBenefit
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/benefits/{id}",
     *     summary="Get specific benefit on subscription",
     *     tags={"Companies | Subscriptions | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Benefit detail"),
     *     @OA\Response(response=404, description="Benefit not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $subscriptionId, $id)
    {
        try {
            $subscriptionBenefit = CompanySubscriptionBenefit::where('company_subscription_id', $subscriptionId)
                ->whereHas('company_subscription', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->with('benefit')
                ->find($id);

            if (!$subscriptionBenefit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription benefit not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription benefit retrieved successfully',
                'data' => $subscriptionBenefit
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/benefits/{id}",
     *     summary="Update subscription benefit",
     *     tags={"Companies | Subscriptions | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 example="inactive"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Benefit updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Benefit not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $subscriptionId, $id)
    {
        try {
            $subscriptionBenefit = CompanySubscriptionBenefit::where('company_subscription_id', $subscriptionId)
                ->whereHas('company_subscription', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->find($id);

            if (!$subscriptionBenefit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription benefit not found',
                    'data' => null
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'status' => [
                    'nullable',
                    Rule::in(['active', 'inactive'])
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
                // Update subscription benefit
                $updateData = [];
                if ($request->has('status')) {
                    $updateData['status'] = $request->input('status');
                }

                $subscriptionBenefit->update($updateData);

                // Load benefit relationship
                $subscriptionBenefit->load('benefit');

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription benefit updated successfully',
                    'data' => $subscriptionBenefit
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
     * @OA\Delete(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/benefits/{id}",
     *     summary="Remove benefit from subscription",
     *     tags={"Companies | Subscriptions | Benefits"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Benefit removed successfully"),
     *     @OA\Response(response=404, description="Benefit not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $subscriptionId, $id)
    {
        try {
            $subscriptionBenefit = CompanySubscriptionBenefit::where('company_subscription_id', $subscriptionId)
                ->whereHas('company_subscription', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->find($id);

            if (!$subscriptionBenefit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription benefit not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                $subscriptionBenefit->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription benefit removed successfully',
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
}
