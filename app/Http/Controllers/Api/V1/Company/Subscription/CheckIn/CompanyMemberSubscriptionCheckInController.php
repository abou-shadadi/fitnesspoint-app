<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionMember;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\CheckIn\CheckInMethod;
use App\Models\Branch\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CompanyMemberSubscriptionCheckInController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins",
     *     summary="List member check-ins",
     *     tags={"Companies | Subscriptions | Members | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companySubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companyMemberSubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "completed", "failed"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="check_in_method_id",
     *         in="query",
     *         required=false,
     *         description="Filter by check-in method",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch",
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
     *     @OA\Response(response=200, description="List of check-ins"),
     *     @OA\Response(response=404, description="Company, subscription, or member not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $companyId, $companySubscriptionId, $companyMemberSubscriptionId)
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
                ->find($companySubscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify company subscription member exists and belongs to subscription
            $member = CompanySubscriptionMember::where('company_subscription_id', $companySubscriptionId)
                ->find($companyMemberSubscriptionId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription member not found',
                    'data' => null
                ], 404);
            }

            $query = CompanySubscriptionMemberCheckIn::where('company_subscription_member_id', $member->id)
                ->with(['company_subscription_member.member', 'check_in_method', 'branch', 'created_by']);

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'failed'])) {
                $query->where('status', $request->status);
            }

            // Filter by check_in_method_id
            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $query->where('check_in_method_id', $request->check_in_method_id);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('datetime', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('datetime', '<=', $request->date_to);
            }

            // Order by latest check-in
            $query->orderBy('datetime', 'desc');

            $checkIns = $query->get();

            // Calculate summary
            $summary = [
                'total_check_ins' => $checkIns->count(),
                'completed_check_ins' => $checkIns->where('status', 'completed')->count(),
                'pending_check_ins' => $checkIns->where('status', 'pending')->count(),
                'failed_check_ins' => $checkIns->where('status', 'failed')->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member check-ins retrieved successfully',
                'data' => [
                    'check_ins' => $checkIns,
                    'summary' => $summary,
                    'member_info' => [
                        'id' => $member->id,
                        'member_id' => $member->member_id,
                        'member_name' => $member->member->name ?? 'N/A',
                        'subscription_id' => $member->company_subscription_id,
                        'status' => $member->status,
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
     *     path="/api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins",
     *     summary="Create a new check-in",
     *     tags={"Companies | Subscriptions | Members | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companySubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companyMemberSubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"check_in_method_id", "branch_id"},
     *                 @OA\Property(
     *                     property="check_in_method_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="branch_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     example="Morning check-in"
     *                 ),
     *                 @OA\Property(
     *                     property="signature",
     *                     type="string",
     *                     format="binary",
     *                     description="Signature image file"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"pending", "completed", "failed"},
     *                     example="completed"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Check-in created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company, subscription, member, method, or branch not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId, $companySubscriptionId, $companyMemberSubscriptionId)
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
            ->find($companySubscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Company subscription not found',
                'data' => null
            ], 404);
        }

        // Verify company subscription member exists and belongs to subscription
        $member = CompanySubscriptionMember::where('company_subscription_id', $companySubscriptionId)
            ->find($companyMemberSubscriptionId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'Company subscription member not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'check_in_method_id' => 'required|exists:check_in_methods,id',
            'branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'status' => [
                'nullable',
                Rule::in(['pending', 'completed', 'failed'])
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify check-in method exists
        $checkInMethod = CheckInMethod::find($request->input('check_in_method_id'));
        if (!$checkInMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in method not found',
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
            // Extract metadata from request
            $metadata = $this->extractMetadata($request);

            // Process signature image if provided
            $signaturePath = null;
            if ($request->hasFile('signature')) {
                $signaturePath = $this->processSignatureImage($request->file('signature'), $companyId, $member->member_id);

                // Extract additional metadata from image
                $imageMetadata = $this->extractImageMetadata($request->file('signature'));
                $metadata = array_merge($metadata, $imageMetadata);
            }

            // Create check-in record
            $checkIn = new CompanySubscriptionMemberCheckIn([
                'company_subscription_member_id' => $member->id,
                'datetime' => now(),
                'notes' => $request->input('notes'),
                'check_in_method_id' => $request->input('check_in_method_id'),
                'branch_id' => $request->input('branch_id'),
                'created_by_id' => Auth::id(),
                'signature' => $signaturePath,
                'metadata' => $metadata,
                'status' => $request->input('status', 'completed'),
            ]);

            $checkIn->save();

            // Load relationships
            $checkIn->load([
                'company_subscription_member.member',
                'check_in_method',
                'branch',
                'created_by'
            ]);

            // Generate signature URL if signature exists
            if ($checkIn->signature) {
                $checkIn->signature_url = Storage::url($checkIn->signature);
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in recorded successfully',
                'data' => $checkIn
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
     *     path="/api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins/{id}",
     *     summary="Get specific check-in",
     *     tags={"Companies | Subscriptions | Members | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companySubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companyMemberSubscriptionId",
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
     *     @OA\Response(response=200, description="Check-in details"),
     *     @OA\Response(response=404, description="Check-in not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $companySubscriptionId, $companyMemberSubscriptionId, $id)
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
                ->find($companySubscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify company subscription member exists and belongs to subscription
            $member = CompanySubscriptionMember::where('company_subscription_id', $companySubscriptionId)
                ->find($companyMemberSubscriptionId);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription member not found',
                    'data' => null
                ], 404);
            }

            $checkIn = CompanySubscriptionMemberCheckIn::where('company_subscription_member_id', $member->id)
                ->with([
                    'company_subscription_member.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ])
                ->find($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found',
                    'data' => null
                ], 404);
            }

            // Generate signature URL if signature exists
            if ($checkIn->signature) {
                $checkIn->signature_url = Storage::url($checkIn->signature);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in retrieved successfully',
                'data' => $checkIn
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
     *     path="/api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins/{id}",
     *     summary="Update check-in",
     *     tags={"Companies | Subscriptions | Members | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companySubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companyMemberSubscriptionId",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="check_in_method_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="branch_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="datetime",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-01-15 09:00:00"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     example="Updated notes"
     *                 ),
     *                 @OA\Property(
     *                     property="signature",
     *                     type="string",
     *                     format="binary",
     *                     description="New signature image file"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"pending", "completed", "failed"}
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Check-in updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Check-in not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $companySubscriptionId, $companyMemberSubscriptionId, $id)
    {
        // Find check-in
        $checkIn = CompanySubscriptionMemberCheckIn::find($id);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in not found',
                'data' => null
            ], 404);
        }

        // Verify check-in belongs to the specified member
        $member = CompanySubscriptionMember::where('company_subscription_id', $companySubscriptionId)
            ->find($companyMemberSubscriptionId);

        if (!$member || $checkIn->company_subscription_member_id !== $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in does not belong to specified member',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'check_in_method_id' => 'nullable|exists:check_in_methods,id',
            'branch_id' => 'nullable|exists:branches,id',
            'datetime' => 'nullable|date',
            'notes' => 'nullable|string',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'status' => [
                'nullable',
                Rule::in(['pending', 'completed', 'failed'])
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
            if ($request->has('check_in_method_id')) $updateData['check_in_method_id'] = $request->input('check_in_method_id');
            if ($request->has('branch_id')) $updateData['branch_id'] = $request->input('branch_id');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('status')) $updateData['status'] = $request->input('status');

            // Process new signature image if provided
            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($checkIn->signature && Storage::exists($checkIn->signature)) {
                    Storage::delete($checkIn->signature);
                }

                // Save new signature
                $signaturePath = $this->processSignatureImage($request->file('signature'), $companyId, $member->member_id);
                $updateData['signature'] = $signaturePath;

                // Extract metadata from new image
                $imageMetadata = $this->extractImageMetadata($request->file('signature'));

                // Update metadata
                $currentMetadata = $checkIn->metadata ?? [];
                $updateData['metadata'] = array_merge($currentMetadata, $imageMetadata);
            }

            // Update check-in
            $checkIn->update($updateData);

            // Load relationships
            $checkIn->load([
                'company_subscription_member.member',
                'check_in_method',
                'branch',
                'created_by'
            ]);

            // Generate signature URL if signature exists
            if ($checkIn->signature) {
                $checkIn->signature_url = Storage::url($checkIn->signature);
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in updated successfully',
                'data' => $checkIn
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
     *     path="/api/companies/{companyId}/subscriptions/{companySubscriptionId}/members/{companyMemberSubscriptionId}/check-ins/{id}",
     *     summary="Delete check-in",
     *     tags={"Companies | Subscriptions | Members | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companySubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="companyMemberSubscriptionId",
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
     *     @OA\Response(response=200, description="Check-in deleted successfully"),
     *     @OA\Response(response=404, description="Check-in not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $companySubscriptionId, $companyMemberSubscriptionId, $id)
    {
        try {
            $checkIn = CompanySubscriptionMemberCheckIn::find($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found',
                    'data' => null
                ], 404);
            }

            // Verify check-in belongs to the specified member
            $member = CompanySubscriptionMember::where('company_subscription_id', $companySubscriptionId)
                ->find($companyMemberSubscriptionId);

            if (!$member || $checkIn->company_subscription_member_id !== $member->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in does not belong to specified member',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Delete signature file if exists
                if ($checkIn->signature && Storage::exists($checkIn->signature)) {
                    Storage::delete($checkIn->signature);
                }

                // Delete check-in record
                $checkIn->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in deleted successfully',
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

    // ============ HELPER METHODS ============

    /**
     * Extract metadata from request
     */
    private function extractMetadata(Request $request): array
    {
        return [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->getDeviceInfo($request),
            'browser' => $this->getBrowserInfo($request),
            'platform' => $this->getPlatformInfo($request),
            'check_in_timestamp' => now()->toISOString(),
            'server_timestamp' => now()->toISOString(),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
        ];
    }

    /**
     * Extract metadata from image
     */
    private function extractImageMetadata($image): array
    {
        $metadata = [
            'image_filename' => $image->getClientOriginalName(),
            'image_size' => $image->getSize(),
            'image_mime_type' => $image->getMimeType(),
            'image_extension' => $image->getClientOriginalExtension(),
            'image_hash' => hash_file('sha256', $image->getRealPath()),
            'uploaded_at' => now()->toISOString(),
        ];

        try {
            // Get image dimensions
            $imageInfo = @getimagesize($image->getRealPath());
            if ($imageInfo) {
                $metadata['image_dimensions'] = [
                    'width' => $imageInfo[0],
                    'height' => $imageInfo[1],
                    'mime' => $imageInfo['mime'],
                ];
            }

            // Try to get EXIF data
            $this->extractExifData($image, $metadata);

            // Get file timestamps
            $metadata['file_created'] = date('Y-m-d H:i:s', filectime($image->getRealPath()));
            $metadata['file_modified'] = date('Y-m-d H:i:s', filemtime($image->getRealPath()));

        } catch (\Exception $e) {
            Log::warning('Failed to extract image metadata: ' . $e->getMessage());
            $metadata['image_processing_error'] = $e->getMessage();
        }

        return $metadata;
    }

    /**
     * Extract EXIF data
     */
    private function extractExifData($image, array &$metadata): void
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        $mime = $image->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/tiff'])) {
            return;
        }

        try {
            $exif = @exif_read_data($image->getRealPath(), 'EXIF,IFD0,GPS', true);
            if (!$exif) {
                return;
            }

            $metadata['has_exif'] = true;

            // Extract date
            if (isset($exif['EXIF']['DateTimeOriginal'])) {
                $metadata['image_capture_date'] = $exif['EXIF']['DateTimeOriginal'];
            }

            // Extract GPS
            if (isset($exif['GPS'])) {
                $gpsData = $this->extractGpsData($exif['GPS']);
                if ($gpsData) {
                    $metadata['gps'] = $gpsData;
                }
            }

        } catch (\Exception $e) {
            // Silent fail for EXIF errors
        }
    }

    /**
     * Extract GPS data
     */
    private function extractGpsData(array $gpsData): ?array
    {
        try {
            if (!isset($gpsData['GPSLatitude'], $gpsData['GPSLongitude'])) {
                return null;
            }

            $lat = $this->parseGpsCoordinate($gpsData['GPSLatitude']);
            $lon = $this->parseGpsCoordinate($gpsData['GPSLongitude']);

            $latRef = $gpsData['GPSLatitudeRef'] ?? 'N';
            $lonRef = $gpsData['GPSLongitudeRef'] ?? 'E';

            return [
                'latitude' => ($latRef === 'S') ? -$lat : $lat,
                'longitude' => ($lonRef === 'W') ? -$lon : $lon,
                'latitude_ref' => $latRef,
                'longitude_ref' => $lonRef,
                'has_coordinates' => true,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse GPS coordinate
     */
    private function parseGpsCoordinate(array $coord): float
    {
        $degrees = $this->parseGpsFraction($coord[0] ?? '0');
        $minutes = $this->parseGpsFraction($coord[1] ?? '0');
        $seconds = $this->parseGpsFraction($coord[2] ?? '0');

        return $degrees + ($minutes / 60) + ($seconds / 3600);
    }

    /**
     * Parse GPS fraction
     */
    private function parseGpsFraction(string $fraction): float
    {
        if (strpos($fraction, '/') === false) {
            return floatval($fraction);
        }

        list($numerator, $denominator) = explode('/', $fraction);
        if ($denominator == 0) {
            return 0;
        }

        return floatval($numerator) / floatval($denominator);
    }

    /**
     * Process signature image
     */
    private function processSignatureImage($image, $companyId, $memberId): string
    {
        // Generate unique filename
        $filename = 'signature_' . $companyId . '_' . $memberId . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        // Define storage path
        $path = 'signatures/company_' . $companyId . '/member_' . $memberId . '/' . date('Y/m/d');

        // Store the image
        return $image->storeAs($path, $filename, 'public');
    }

    /**
     * Get device information
     */
    private function getDeviceInfo(Request $request): string
    {
        $agent = $request->userAgent();
        if (!$agent) return 'Unknown';

        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $agent)) {
            return 'Mobile';
        } elseif (preg_match('/Tablet|iPad|Kindle|SamsungTablet/i', $agent)) {
            return 'Tablet';
        }
        return 'Desktop';
    }

    /**
     * Get browser information
     */
    private function getBrowserInfo(Request $request): string
    {
        $agent = $request->userAgent();
        if (!$agent) return 'Unknown';

        if (preg_match('/Chrome/i', $agent)) return 'Chrome';
        if (preg_match('/Firefox/i', $agent)) return 'Firefox';
        if (preg_match('/Safari/i', $agent) && !preg_match('/Chrome/i', $agent)) return 'Safari';
        if (preg_match('/Edge/i', $agent)) return 'Edge';
        if (preg_match('/MSIE|Trident/i', $agent)) return 'Internet Explorer';
        if (preg_match('/Opera/i', $agent)) return 'Opera';

        return 'Unknown';
    }

    /**
     * Get platform information
     */
    private function getPlatformInfo(Request $request): string
    {
        $agent = $request->userAgent();
        if (!$agent) return 'Unknown';

        if (preg_match('/Windows/i', $agent)) return 'Windows';
        if (preg_match('/Mac OS X/i', $agent)) return 'macOS';
        if (preg_match('/Linux/i', $agent)) return 'Linux';
        if (preg_match('/Android/i', $agent)) return 'Android';
        if (preg_match('/iPhone|iPad|iPod/i', $agent)) return 'iOS';

        return 'Unknown';
    }
}
