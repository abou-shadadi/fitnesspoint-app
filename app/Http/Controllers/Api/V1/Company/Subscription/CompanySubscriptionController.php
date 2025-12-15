<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionBenefit;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\File\Base64Service;

class CompanySubscriptionController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions",
     *     summary="List company subscriptions",
     *     tags={"Companies | Subscriptions"},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of subscriptions"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index($companyId)
    {
        try {
            $company = Company::find($companyId);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            $subscriptions = $company->company_subscriptions()
                ->with(['currency', 'branch','duration_type', 'billing_type', 'benefits.benefit', 'created_by', 'company'])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Company subscriptions retrieved successfully',
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
     *     path="/api/companies/{companyId}/subscriptions",
     *     summary="Create a new company subscription",
     *     tags={"Companies | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"unit_price","currency_id","duration_type_id","billing_type_id","start_date"},
     *             @OA\Property(property="unit_price", type="number", example="600000"),
     *             @OA\Property(property="currency_id", type="integer", example=1),
     *             @OA\Property(property="duration_type_id", type="integer", example=1),
     *             @OA\Property(property="billing_type_id", type="integer", example=2),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-12-31"),
     *             @OA\Property(
     *                 property="attachment",
     *                 type="string",
     *                 description="Base64 encoded file or file path",
     *                 example="data:application/pdf;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB..."
     *             ),
     *             @OA\Property(property="branch_id", type="integer", example=1),
     *             @OA\Property(property="notes", type="string", example="Annual fitness subscription"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending","in_progress","cancelled","expired","refunded","rejected"},
     *                 example="pending"
     *             ),
     *             @OA\Property(
     *                 property="benefits",
     *                 type="array",
     *                 description="Array of benefit IDs to assign to this subscription",
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Subscription created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId)
    {
        // Find company
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'unit_price' => 'required|numeric|min:0',
            'currency_id' => 'required|exists:currencies,id',
            'duration_type_id' => 'required|exists:duration_types,id',
            'billing_type_id' => 'required|exists:billing_types,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'integer|exists:benefits,id',
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

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create subscription
            $subscription = new CompanySubscription([
                'company_id' => $companyId,
                'unit_price' => $request->input('unit_price'),
                'currency_id' => $request->input('currency_id'),
                'duration_type_id' => $request->input('duration_type_id'),
                'billing_type_id' => $request->input('billing_type_id'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'notes' => $request->input('notes'),
                'status' => $request->input('status', 'pending'),
                'created_by_id' => auth()->user()->id,
                'branch_id' => $request->input('branch_id'),
            ]);

            // Save subscription first to get ID
            $subscription->save();

            // Process and store attachment file
            if ($request->has('attachment') && !empty($request->input('attachment'))) {
                $this->base64Service->processBase64File($subscription, $request->input('attachment'), 'attachment');
            }

            // Process benefits if provided
            if ($request->has('benefits') && is_array($request->input('benefits'))) {
                $this->syncSubscriptionBenefits($subscription, $request->input('benefits'));
            }

            // Load relationships
            $subscription->load(['branch', 'currency', 'duration_type', 'billing_type', 'benefits.benefit']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription created successfully',
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
     *     path="/api/companies/{companyId}/subscriptions/{id}",
     *     summary="Show specific subscription",
     *     tags={"Companies | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription details"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $id)
    {
        try {
            $subscription = CompanySubscription::with(['branch','currency', 'duration_type', 'billing_type', 'benefits.benefit', 'company', 'created_by'])
                ->where('company_id', $companyId)
                ->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company subscription retrieved successfully',
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
     *     path="/api/companies/{companyId}/subscriptions/{id}",
     *     summary="Update subscription",
     *     tags={"Companies | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="unit_price", type="number"),
     *             @OA\Property(property="currency_id", type="integer"),
     *             @OA\Property(property="duration_type_id", type="integer"),
     *             @OA\Property(property="billing_type_id", type="integer"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(
     *                 property="attachment",
     *                 type="string",
     *                 description="Base64 encoded file or file path"
     *             ),
     *             @OA\Property(property="branch_id", type="integer"),
     *             @OA\Property(property="notes", type="string"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending","in_progress","cancelled","expired","refunded","rejected"}
     *             ),
     *             @OA\Property(
     *                 property="benefits",
     *                 type="array",
     *                 description="Array of benefit IDs to assign to this subscription. Existing benefits not in this array will be set to inactive.",
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Subscription updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $id)
    {
        // Find subscription
        $subscription = CompanySubscription::where('company_id', $companyId)->find($id);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Company subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'unit_price' => 'nullable|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'duration_type_id' => 'nullable|exists:duration_types,id',
            'billing_type_id' => 'nullable|exists:billing_types,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'attachment' => 'nullable|string',
            'notes' => 'nullable|string',
            'benefits' => 'nullable|array',
            'benefits.*' => 'integer|exists:benefits,id',
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

        // Start database transaction
        DB::beginTransaction();

        try {
            // Prepare update data
            $updateData = [];
            if ($request->has('unit_price')) $updateData['unit_price'] = $request->input('unit_price');
            if ($request->has('currency_id')) $updateData['currency_id'] = $request->input('currency_id');
            if ($request->has('duration_type_id')) $updateData['duration_type_id'] = $request->input('duration_type_id');
            if ($request->has('billing_type_id')) $updateData['billing_type_id'] = $request->input('billing_type_id');
            if ($request->has('start_date')) $updateData['start_date'] = $request->input('start_date');
            if ($request->has('end_date')) $updateData['end_date'] = $request->input('end_date');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('status')) $updateData['status'] = $request->input('status');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');

            // Update subscription
            $subscription->update($updateData);

            // Process and update attachment file if provided
            if ($request->has('attachment') && $request->input('attachment') !== null) {
                $this->base64Service->processBase64File($subscription, $request->input('attachment'), 'attachment', true);
            }

            // Process benefits if provided
            if ($request->has('benefits')) {
                $this->syncSubscriptionBenefits($subscription, $request->input('benefits'));
            }

            // Load relationships
            $subscription->load(['currency', 'duration_type', 'billing_type', 'benefits.benefit', 'branch', 'created_by']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company subscription updated successfully',
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
     *     path="/api/companies/{companyId}/subscriptions/{id}",
     *     summary="Delete subscription",
     *     tags={"Companies | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Subscription deleted successfully"),
     *     @OA\Response(response=404, description="Subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $id)
    {
        try {
            $subscription = CompanySubscription::where('company_id', $companyId)->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction to delete benefits first
            DB::beginTransaction();

            try {
                // Delete associated benefits first
                $subscription->benefits()->delete();

                // Then delete the subscription
                $subscription->delete();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Company subscription deleted successfully',
                    'data' => null
                ], 200);
            } catch (\Exception $e) {
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
     * Sync subscription benefits
     *
     * @param CompanySubscription $subscription
     * @param array $benefitIds Array of benefit IDs
     * @return void
     */
    private function syncSubscriptionBenefits(CompanySubscription $subscription, array $benefitIds)
    {
        // Get current subscription benefits
        $currentBenefits = $subscription->benefits()->pluck('benefit_id')->toArray();

        // Benefits to add (new ones not in current)
        $benefitsToAdd = array_diff($benefitIds, $currentBenefits);

        // Benefits to deactivate (current ones not in new list)
        $benefitsToDeactivate = array_diff($currentBenefits, $benefitIds);

        // Benefits to keep active (intersection)
        $benefitsToKeepActive = array_intersect($currentBenefits, $benefitIds);

        // Add new benefits
        foreach ($benefitsToAdd as $benefitId) {
            // Check if benefit already exists (inactive)
            $existingBenefit = CompanySubscriptionBenefit::where('company_subscription_id', $subscription->id)
                ->where('benefit_id', $benefitId)
                ->first();

            if ($existingBenefit) {
                // Update existing inactive benefit to active
                $existingBenefit->update(['status' => 'active']);
            } else {
                // Create new benefit assignment
                CompanySubscriptionBenefit::create([
                    'company_subscription_id' => $subscription->id,
                    'benefit_id' => $benefitId,
                    'status' => 'active'
                ]);
            }
        }

        // Deactivate benefits not in new list
        if (!empty($benefitsToDeactivate)) {
            CompanySubscriptionBenefit::where('company_subscription_id', $subscription->id)
                ->whereIn('benefit_id', $benefitsToDeactivate)
                ->update(['status' => 'inactive']);
        }

        // Ensure benefits in new list are active
        if (!empty($benefitsToKeepActive)) {
            CompanySubscriptionBenefit::where('company_subscription_id', $subscription->id)
                ->whereIn('benefit_id', $benefitsToKeepActive)
                ->update(['status' => 'active']);
        }
    }
}
