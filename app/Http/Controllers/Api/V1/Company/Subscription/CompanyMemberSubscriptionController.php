<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionMember;
use App\Models\Member\Member;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CompanyMemberSubscriptionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members",
     *     summary="List members assigned to a company subscription",
     *     tags={"Companies | Subscriptions | Members"},
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
     *     @OA\Response(response=200, description="List of subscription members"),
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
                ->with(['benefits'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Get subscription members with member details
            $members = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->with('member')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Subscription members retrieved successfully',
                'data' => [
                    'subscription' => $subscription,
                    'members' => $members
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members",
     *     summary="Assign members to a company subscription",
     *     tags={"Companies | Subscriptions | Members"},
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
     *             required={"members"},
     *             @OA\Property(
     *                 property="members",
     *                 type="array",
     *                 description="Array of member IDs to assign to this subscription",
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Members assigned successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=409, description="Some members already assigned"),
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
            'members' => 'required|array|min:1',
            'members.*' => 'integer|exists:members,id',
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
            $memberIds = $request->input('members');
            $status = $request->input('status', 'active');
            $addedMembers = [];
            $duplicateMembers = [];

            // Check for existing assignments
            $existingMembers = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->whereIn('member_id', $memberIds)
                ->pluck('member_id')
                ->toArray();

            // Filter out duplicates
            $newMemberIds = array_diff($memberIds, $existingMembers);
            $duplicateMembers = array_intersect($memberIds, $existingMembers);

            if (empty($newMemberIds)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'All members are already assigned to this subscription',
                    'data' => [
                        'duplicate_members' => $duplicateMembers
                    ]
                ], 409);
            }

            // Add new members
            foreach ($newMemberIds as $memberId) {
                $subscriptionMember = CompanySubscriptionMember::create([
                    'company_subscription_id' => $subscriptionId,
                    'member_id' => $memberId,
                    'status' => $status
                ]);

                $subscriptionMember->load('member');
                $addedMembers[] = $subscriptionMember;
            }

            // Commit transaction
            DB::commit();

            $response = [
                'success' => true,
                'message' => count($addedMembers) . ' member(s) assigned to subscription successfully',
                'data' => [
                    'added_members' => $addedMembers
                ]
            ];

            // Add warning if there were duplicates
            if (!empty($duplicateMembers)) {
                $response['warning'] = count($duplicateMembers) . ' member(s) were already assigned';
                $response['data']['duplicate_members'] = $duplicateMembers;
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

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members/{id}",
     *     summary="Get specific member subscription assignment",
     *     tags={"Companies | Subscriptions | Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, description="Subscription member assignment ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Member subscription details"),
     *     @OA\Response(response=404, description="Subscription member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $subscriptionId, $id)
    {
        try {
            // Verify subscription belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $subscriptionMember = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->with(['member', 'company_subscription'])
                ->find($id);

            if (!$subscriptionMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription member assignment not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Subscription member assignment retrieved successfully',
                'data' => $subscriptionMember
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members/{id}",
     *     summary="Update member subscription assignment",
     *     tags={"Companies | Subscriptions | Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, description="Subscription member assignment ID", @OA\Schema(type="integer")),
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
     *     @OA\Response(response=200, description="Member subscription updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Subscription member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $subscriptionId, $id)
    {
        try {
            // Verify subscription belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $subscriptionMember = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->find($id);

            if (!$subscriptionMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription member assignment not found',
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
                // Update subscription member
                $updateData = [];
                if ($request->has('status')) {
                    $updateData['status'] = $request->input('status');
                }

                $subscriptionMember->update($updateData);

                // Load relationships
                $subscriptionMember->load(['member', 'company_subscription']);

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription member assignment updated successfully',
                    'data' => $subscriptionMember
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members/{id}",
     *     summary="Remove member from subscription",
     *     tags={"Companies | Subscriptions | Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="id", in="path", required=true, description="Subscription member assignment ID", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Member removed from subscription successfully"),
     *     @OA\Response(response=404, description="Subscription member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $subscriptionId, $id)
    {
        try {
            // Verify subscription belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $subscriptionMember = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->find($id);

            if (!$subscriptionMember) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subscription member assignment not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                $subscriptionMember->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Member removed from subscription successfully',
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
     * @OA\Post(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/members/bulk-update",
     *     summary="Bulk update member subscription assignments",
     *     tags={"Companies | Subscriptions | Members"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="companyId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="subscriptionId", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"members"},
     *             @OA\Property(
     *                 property="members",
     *                 type="array",
     *                 description="Array of member IDs to sync with the subscription",
     *                 @OA\Items(
     *                     type="integer",
     *                     example=1
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 description="Status for newly added members",
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Members synced successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function bulkUpdate(Request $request, $companyId, $subscriptionId)
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
            'members' => 'required|array',
            'members.*' => 'integer|exists:members,id',
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
            $memberIds = $request->input('members');
            $statusForNew = $request->input('status', 'active');

            // Get current subscription members
            $currentMembers = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                ->pluck('member_id')
                ->toArray();

            // Members to add (new ones not in current)
            $membersToAdd = array_diff($memberIds, $currentMembers);

            // Members to remove (current ones not in new list)
            $membersToRemove = array_diff($currentMembers, $memberIds);

            // Members to keep (intersection)
            $membersToKeep = array_intersect($currentMembers, $memberIds);

            // Add new members
            $addedMembers = [];
            foreach ($membersToAdd as $memberId) {
                $subscriptionMember = CompanySubscriptionMember::create([
                    'company_subscription_id' => $subscriptionId,
                    'member_id' => $memberId,
                    'status' => $statusForNew
                ]);
                $addedMembers[] = $subscriptionMember;
            }

            // Remove members not in new list
            $removedCount = 0;
            if (!empty($membersToRemove)) {
                $removedCount = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                    ->whereIn('member_id', $membersToRemove)
                    ->delete();
            }

            // Ensure kept members are active
            $activatedCount = 0;
            if (!empty($membersToKeep)) {
                $activatedCount = CompanySubscriptionMember::where('company_subscription_id', $subscriptionId)
                    ->whereIn('member_id', $membersToKeep)
                    ->where('status', '!=', 'active')
                    ->update(['status' => 'active']);
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subscription members synced successfully',
                'data' => [
                    'added' => count($addedMembers),
                    'removed' => $removedCount,
                    'activated' => $activatedCount,
                    'total_assigned' => count($memberIds)
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
}
