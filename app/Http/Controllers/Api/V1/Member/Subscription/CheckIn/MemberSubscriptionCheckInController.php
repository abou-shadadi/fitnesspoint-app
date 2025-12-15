<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionCheckIn;
use App\Models\CheckIn\CheckInMethod;
use App\Models\Branch\Branch;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\Member\MemberSubscriptionMemberCheckIn;


class MemberSubscriptionCheckInController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins",
     *     summary="List member subscription check-ins",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
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
     *             enum={"completed", "failed"}
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
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $memberId, $memberSubscriptionId)
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
                ->find($memberSubscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $query = MemberSubscriptionCheckIn::where('member_subscription_id', $subscription->id)
                ->with(['check_in_method', 'branch', 'created_by', 'member_subscription.plan']);

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['completed', 'failed'])) {
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

            // Calculate summary statistics
            $summary = [
                'total_check_ins' => $checkIns->count(),
                'completed_check_ins' => $checkIns->where('status', 'completed')->count(),
                'failed_check_ins' => $checkIns->where('status', 'failed')->count(),
                'today_check_ins' => $checkIns->whereDate('datetime', today())->count(),
                'this_week_check_ins' => $checkIns->whereBetween('datetime', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'this_month_check_ins' => $checkIns->whereBetween('datetime', [
                    now()->startOfMonth(),
                    now()->endOfMonth()
                ])->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member subscription check-ins retrieved successfully',
                'data' => [
                    'check_ins' => $checkIns,
                    'summary' => $summary,
                    'subscription_info' => [
                        'id' => $subscription->id,
                        'plan_name' => $subscription->plan->name ?? 'N/A',
                        'start_date' => $subscription->start_date,
                        'end_date' => $subscription->end_date,
                        'status' => $subscription->status,
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
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins",
     *     summary="Create a new member subscription check-in",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
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
     *                     property="datetime",
     *                     type="string",
     *                     format="date-time",
     *                     example="2024-01-15 08:30:00"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     example="Morning workout session"
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
     *                     enum={"completed", "failed"},
     *                     example="completed"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Check-in created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Member, subscription, method, or branch not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $memberId, $memberSubscriptionId)
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
            ->find($memberSubscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'check_in_method_id' => 'required|exists:check_in_methods,id',
            'branch_id' => 'required|exists:branches,id',
            'datetime' => 'nullable|date',
            'notes' => 'nullable|string',
            'signature' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            'status' => [
                'nullable',
                Rule::in(['completed', 'failed'])
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
                $signaturePath = $this->processSignatureImage($request->file('signature'), $memberId, $memberSubscriptionId);

                // Extract additional metadata from image
                $imageMetadata = $this->extractImageMetadata($request->file('signature'));
                $metadata = array_merge($metadata, $imageMetadata);
            }

            // Create check-in record
            $checkIn = new MemberSubscriptionCheckIn([
                'member_subscription_id' => $subscription->id,
                'datetime' => $request->input('datetime', now()),
                'notes' => $request->input('notes'),
                'check_in_method_id' => $request->input('check_in_method_id'),
                'branch_id' => $request->input('branch_id'),
                'signature' => $signaturePath,
                'metadata' => $metadata,
                'status' => $request->input('status', 'completed'),
            ]);

            // Set created_by if user is authenticated
            if (Auth::check()) {
                $checkIn->created_by_id = Auth::id();
            }

            $checkIn->save();

            // Load relationships
            $checkIn->load([
                'check_in_method',
                'branch',
                'created_by',
                'member_subscription.plan'
            ]);

            // Generate signature URL if signature exists
            if ($checkIn->signature) {
                $checkIn->signature_url = Storage::url($checkIn->signature);
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription check-in recorded successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins/{id}",
     *     summary="Get specific member subscription check-in",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
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
    public function show($memberId, $memberSubscriptionId, $id)
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
                ->find($memberSubscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $checkIn = MemberSubscriptionCheckIn::where('member_subscription_id', $subscription->id)
                ->with([
                    'check_in_method',
                    'branch',
                    'created_by',
                    'member_subscription.plan',
                    'member_subscription.member'
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

            // Add additional metadata information
            $checkIn->additional_info = [
                'time_ago' => $checkIn->datetime ? Carbon::parse($checkIn->datetime)->diffForHumans() : null,
                'date_formatted' => $checkIn->datetime ? Carbon::parse($checkIn->datetime)->format('F j, Y g:i A') : null,
                'is_today' => $checkIn->datetime ? Carbon::parse($checkIn->datetime)->isToday() : false,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member subscription check-in retrieved successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins/{id}",
     *     summary="Update member subscription check-in",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
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
     *                     enum={"completed", "failed"}
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
    public function update(Request $request, $memberId, $memberSubscriptionId, $id)
    {
        // Find check-in
        $checkIn = MemberSubscriptionCheckIn::find($id);

        if (!$checkIn) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in not found',
                'data' => null
            ], 404);
        }

        // Verify check-in belongs to the specified subscription
        $subscription = MemberSubscription::where('member_id', $memberId)
            ->find($memberSubscriptionId);

        if (!$subscription || $checkIn->member_subscription_id !== $subscription->id) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in does not belong to specified subscription',
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
                Rule::in(['completed', 'failed'])
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
            if ($request->has('datetime')) $updateData['datetime'] = $request->input('datetime');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('status')) $updateData['status'] = $request->input('status');

            // Process new signature image if provided
            if ($request->hasFile('signature')) {
                // Delete old signature if exists
                if ($checkIn->signature && Storage::exists($checkIn->signature)) {
                    Storage::delete($checkIn->signature);
                }

                // Save new signature
                $signaturePath = $this->processSignatureImage($request->file('signature'), $memberId, $memberSubscriptionId);
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
                'check_in_method',
                'branch',
                'created_by',
                'member_subscription.plan'
            ]);

            // Generate signature URL if signature exists
            if ($checkIn->signature) {
                $checkIn->signature_url = Storage::url($checkIn->signature);
            }

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription check-in updated successfully',
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
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins/{id}",
     *     summary="Delete member subscription check-in",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
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
    public function destroy($memberId, $memberSubscriptionId, $id)
    {
        try {
            $checkIn = MemberSubscriptionCheckIn::find($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found',
                    'data' => null
                ], 404);
            }

            // Verify check-in belongs to the specified subscription
            $subscription = MemberSubscription::where('member_id', $memberId)
                ->find($memberSubscriptionId);

            if (!$subscription || $checkIn->member_subscription_id !== $subscription->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in does not belong to specified subscription',
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
                    'message' => 'Member subscription check-in deleted successfully',
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
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{memberSubscriptionId}/check-ins/summary/daily",
     *     summary="Get daily check-in summary",
     *     tags={"Members | Subscriptions | Check-ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="memberId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="memberSubscriptionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to include (default: 30)",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Response(response=200, description="Daily summary"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function dailySummary(Request $request, $memberId, $memberSubscriptionId)
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
                ->find($memberSubscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $days = $request->input('days', 30);
            $startDate = now()->subDays($days);

            // Get check-ins for the period
            $checkIns = MemberSubscriptionCheckIn::where('member_subscription_id', $subscription->id)
                ->where('datetime', '>=', $startDate)
                ->where('status', 'completed')
                ->get();

            // Group by date
            $dailySummary = [];
            for ($i = 0; $i <= $days; $i++) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dailyCheckIns = $checkIns->filter(function ($checkIn) use ($date) {
                    return $checkIn->datetime->format('Y-m-d') === $date;
                });

                $dailySummary[] = [
                    'date' => $date,
                    'count' => $dailyCheckIns->count(),
                    'check_ins' => $dailyCheckIns->map(function ($checkIn) {
                        return [
                            'id' => $checkIn->id,
                            'time' => $checkIn->datetime->format('H:i:s'),
                            'method' => $checkIn->check_in_method->name ?? 'Unknown',
                            'branch' => $checkIn->branch->name ?? 'Unknown',
                        ];
                    })->values()
                ];
            }

            // Calculate statistics
            $stats = [
                'total_check_ins' => $checkIns->count(),
                'average_daily' => $days > 0 ? round($checkIns->count() / $days, 2) : 0,
                'most_active_day' => collect($dailySummary)->sortByDesc('count')->first(),
                'current_streak' => $this->calculateStreak($checkIns),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Daily check-in summary retrieved successfully',
                'data' => [
                    'daily_summary' => $dailySummary,
                    'stats' => $stats,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => now()->format('Y-m-d'),
                        'days' => $days
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
     * Process signature image
     */
    private function processSignatureImage($image, $memberId, $subscriptionId): string
    {
        // Generate unique filename
        $filename = 'signature_member_' . $memberId . '_subscription_' . $subscriptionId . '_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

        // Define storage path
        $path = 'signatures/members/' . $memberId . '/subscriptions/' . $subscriptionId . '/' . date('Y/m/d');

        // Store the image
        return $image->storeAs($path, $filename, 'public');
    }

    /**
     * Calculate check-in streak
     */
    private function calculateStreak($checkIns): int
    {
        if ($checkIns->isEmpty()) {
            return 0;
        }

        $dates = $checkIns->map(function ($checkIn) {
            return $checkIn->datetime->format('Y-m-d');
        })->unique()->sort()->values();

        $streak = 0;
        $currentDate = today();

        while ($dates->contains($currentDate->format('Y-m-d'))) {
            $streak++;
            $currentDate->subDay();
        }

        return $streak;
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
