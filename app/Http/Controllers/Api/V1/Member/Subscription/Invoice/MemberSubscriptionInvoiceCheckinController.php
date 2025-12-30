<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\Invoice\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionInvoice;
use App\Models\Member\MemberSubscriptionCheckIn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;

class MemberSubscriptionInvoiceCheckInController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins",
     *     summary="List check-ins for a member invoice",
     *     tags={"Members | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
     *         in="path",
     *         required=true,
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
     *         description="Filter by branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"date", "check_in_method", "status", "datetime", "branch"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15, enum={10, 15, 25, 50, 100})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of check-ins for member invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-ins retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="check_ins",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="first_page_url", type="string"),
     *                     @OA\Property(property="from", type="integer"),
     *                     @OA\Property(property="last_page", type="integer"),
     *                     @OA\Property(property="last_page_url", type="string"),
     *                     @OA\Property(property="links", type="array", @OA\Items(type="object")),
     *                     @OA\Property(property="next_page_url", type="string", nullable=true),
     *                     @OA\Property(property="path", type="string"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                     @OA\Property(property="to", type="integer"),
     *                     @OA\Property(property="total", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="summary",
     *                     type="object",
     *                     @OA\Property(property="total_days", type="integer"),
     *                     @OA\Property(property="total_check_ins", type="integer"),
     *                     @OA\Property(property="total_completed", type="integer"),
     *                     @OA\Property(property="total_failed", type="integer"),
     *                     @OA\Property(
     *                         property="daily_summary",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="date", type="string", format="date"),
     *                             @OA\Property(property="total_check_ins", type="integer"),
     *                             @OA\Property(property="completed", type="integer"),
     *                             @OA\Property(property="failed", type="integer"),
     *                             @OA\Property(property="branches_used", type="array", @OA\Items(type="string"))
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member, subscription, or invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $memberId, $subscriptionId, $invoiceId)
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
                ->with(['plan', 'branch', 'created_by'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get invoice date range (adjust based on your invoice model structure)
            // Assuming invoice has from_date and to_date or similar fields
            // If not, you may need to adjust this logic
            $fromDate = Carbon::parse($invoice->created_at); // Adjust as needed
            $toDate = Carbon::parse($invoice->created_at); // Adjust as needed

            // If your invoice model has specific date fields, use them:
            // $fromDate = Carbon::parse($invoice->from_date);
            // $toDate = Carbon::parse($invoice->to_date);

            // Base query for check-ins for this member subscription
            $query = MemberSubscriptionCheckIn::where('member_subscription_id', $subscriptionId)
                ->with([
                    'member_subscription.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ]);

            // Apply date range filter
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('datetime', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('datetime', '<=', $request->date_to);
            }

            // Status filter
            if ($request->has('status') && in_array($request->status, ['completed', 'failed'])) {
                $query->where('status', $request->status);
            }

            // Check-in method filter
            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $query->where('check_in_method_id', $request->check_in_method_id);
            }

            // Branch filter
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'datetime');
            $sortOrder = $request->input('sort_order', 'desc');

            // Map sort fields
            $sortMap = [
                'date' => 'datetime',
                'check_in_method' => 'check_in_method_id',
                'status' => 'status',
                'datetime' => 'datetime',
                'branch' => 'branch_id'
            ];

            $sortField = $sortMap[$sortBy] ?? $sortBy;
            $query->orderBy($sortField, $sortOrder);

            // Get all check-ins for summary calculation
            $allCheckIns = $query->get();

            // Calculate summary statistics
            $summary = $this->calculateMemberSummary($allCheckIns, $fromDate, $toDate);

            // For pagination, we need to re-execute the query
            $paginationQuery = MemberSubscriptionCheckIn::where('member_subscription_id', $subscriptionId)
                ->with([
                    'member_subscription.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ]);

            // Reapply filters for pagination
            if ($request->has('date_from') && $request->date_from) {
                $paginationQuery->whereDate('datetime', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $paginationQuery->whereDate('datetime', '<=', $request->date_to);
            }

            if ($request->has('status') && in_array($request->status, ['completed', 'failed'])) {
                $paginationQuery->where('status', $request->status);
            }

            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $paginationQuery->where('check_in_method_id', $request->check_in_method_id);
            }

            if ($request->has('branch_id') && $request->branch_id) {
                $paginationQuery->where('branch_id', $request->branch_id);
            }

            $paginationQuery->orderBy($sortField, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
            $checkIns = $paginationQuery->paginate($perPage);

            // Transform data for response
            $checkIns->getCollection()->transform(function ($checkIn) {
                return [
                    'id' => $checkIn->id,
                    'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                    'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                    'datetime' => $checkIn->datetime,
                    'member_id' => $checkIn->member_subscription->member_id,
                    'member_name' => $checkIn->member_subscription->member->name ?? 'N/A',
                    'check_in_method_id' => $checkIn->check_in_method_id,
                    'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                    'status' => $checkIn->status,
                    'branch_id' => $checkIn->branch_id,
                    'branch_name' => $checkIn->branch->name ?? 'N/A',
                    'notes' => $checkIn->notes,
                    'metadata' => $checkIn->metadata,
                    'created_by_id' => $checkIn->created_by_id,
                    'created_by_name' => $checkIn->created_by->name ?? 'N/A',
                    'signature_url' => $checkIn->signature ? Storage::url($checkIn->signature) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-ins retrieved successfully',
                'data' => [
                    'check_ins' => $checkIns,
                    'summary' => $summary,
                    'member_info' => [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'phone' => $member->phone,
                    ],
                    'subscription_info' => [
                        'id' => $subscription->id,
                        'plan_name' => $subscription->plan->name ?? 'N/A',
                        'start_date' => $subscription->start_date->format('Y-m-d H:i:s'),
                        'end_date' => $subscription->end_date ? $subscription->end_date->format('Y-m-d H:i:s') : null,
                        'status' => $subscription->status,
                    ],
                    'invoice_info' => [
                        'id' => $invoice->id,
                        // Add invoice fields as per your model
                        'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
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
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins/{id}",
     *     summary="Get specific check-in for member invoice",
     *     tags={"Members | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
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
     *     @OA\Response(
     *         response=200,
     *         description="Check-in details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-in retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="time", type="string", format="time"),
     *                 @OA\Property(property="datetime", type="string", format="date-time"),
     *                 @OA\Property(property="member_id", type="integer"),
     *                 @OA\Property(property="member_name", type="string"),
     *                 @OA\Property(property="check_in_method_id", type="integer"),
     *                 @OA\Property(property="check_in_method", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="branch_id", type="integer"),
     *                 @OA\Property(property="branch_name", type="string"),
     *                 @OA\Property(property="notes", type="string"),
     *                 @OA\Property(property="metadata", type="object"),
     *                 @OA\Property(property="created_by_id", type="integer"),
     *                 @OA\Property(property="created_by_name", type="string"),
     *                 @OA\Property(property="signature_url", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Check-in not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($memberId, $subscriptionId, $invoiceId, $id)
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

            // Verify invoice exists and belongs to subscription
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get the check-in
            $checkIn = MemberSubscriptionCheckIn::where('member_subscription_id', $subscriptionId)
                ->with([
                    'member_subscription.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ])
                ->find($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found for this subscription',
                    'data' => null
                ], 404);
            }

            $checkInData = [
                'id' => $checkIn->id,
                'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                'datetime' => $checkIn->datetime,
                'member_id' => $checkIn->member_subscription->member_id,
                'member_name' => $checkIn->member_subscription->member->name ?? 'N/A',
                'check_in_method_id' => $checkIn->check_in_method_id,
                'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                'status' => $checkIn->status,
                'branch_id' => $checkIn->branch_id,
                'branch_name' => $checkIn->branch->name ?? 'N/A',
                'notes' => $checkIn->notes,
                'metadata' => $checkIn->metadata,
                'created_by_id' => $checkIn->created_by_id,
                'created_by_name' => $checkIn->created_by->name ?? 'N/A',
                'signature_url' => $checkIn->signature ? Storage::url($checkIn->signature) : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Check-in retrieved successfully',
                'data' => $checkInData
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins/export",
     *     summary="Export member check-ins to PDF",
     *     tags={"Members | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
     *         in="path",
     *         required=true,
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
     *         description="Filter by branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member, subscription, or invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function export(Request $request, $memberId, $subscriptionId, $invoiceId)
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
                ->with(['plan', 'branch', 'created_by'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get all check-ins with filters
            $query = MemberSubscriptionCheckIn::where('member_subscription_id', $subscriptionId)
                ->with([
                    'member_subscription.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ]);

            // Apply filters
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('datetime', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('datetime', '<=', $request->date_to);
            }

            if ($request->has('status') && in_array($request->status, ['completed', 'failed'])) {
                $query->where('status', $request->status);
            }

            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $query->where('check_in_method_id', $request->check_in_method_id);
            }

            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            $query->orderBy('datetime', 'desc');
            $checkIns = $query->get();

            // Transform data for export
            $exportData = $checkIns->map(function ($checkIn) {
                return [
                    'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                    'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                    'member_name' => $checkIn->member_subscription->member->name ?? 'N/A',
                    'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                    'status' => $checkIn->status,
                    'branch' => $checkIn->branch->name ?? 'N/A',
                    'notes' => $checkIn->notes,
                    'created_by' => $checkIn->created_by->name ?? 'N/A',
                    'has_signature' => !empty($checkIn->signature),
                    'signature_path' => $checkIn->signature,
                    'metadata' => $checkIn->metadata,
                ];
            })->toArray();

            // Calculate summary
            $summary = $this->calculateMemberSummary($checkIns, null, null);

            // Generate PDF
            return $this->generateMemberPdf($exportData, $summary, $member, $subscription, $invoice);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Calculate summary statistics for member check-ins
     */
    private function calculateMemberSummary($checkIns, Carbon $fromDate = null, Carbon $toDate = null): array
    {
        // Group check-ins by date
        $dailyData = [];

        foreach ($checkIns as $checkIn) {
            $date = Carbon::parse($checkIn->datetime)->format('Y-m-d');
            $branchName = $checkIn->branch->name ?? 'N/A';

            // Initialize daily data if not exists
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'total_check_ins' => 0,
                    'completed' => 0,
                    'failed' => 0,
                    'branches_used' => []
                ];
            }

            // Count by status
            $dailyData[$date]['total_check_ins']++;
            $dailyData[$date][$checkIn->status]++;

            // Track branches used
            if (!in_array($branchName, $dailyData[$date]['branches_used'])) {
                $dailyData[$date]['branches_used'][] = $branchName;
            }
        }

        // Sort daily data by date
        ksort($dailyData);
        $dailySummary = array_values($dailyData);

        // Calculate totals
        $totalCompleted = $checkIns->where('status', 'completed')->count();
        $totalFailed = $checkIns->where('status', 'failed')->count();

        // Calculate total days in range if dates provided
        $totalDays = 0;
        if ($fromDate && $toDate) {
            $totalDays = $fromDate->diffInDays($toDate) + 1;
        }

        return [
            'total_days' => $totalDays,
            'total_check_ins' => $checkIns->count(),
            'total_completed' => $totalCompleted,
            'total_failed' => $totalFailed,
            'daily_summary' => $dailySummary,
        ];
    }

    /**
     * Generate PDF for member check-ins
     */
    private function generateMemberPdf($data, $summary, $member, $subscription, $invoice)
    {
        $fileName = 'member_checkins_' . $member->id . '_invoice_' . $invoice->id . '_' . date('Ymd_His') . '.pdf';

        // Render Blade template
        $html = view('exports.member.invoice.checkins', [
            'data' => $data,
            'summary' => $summary,
            'member' => $member,
            'subscription' => $subscription,
            'invoice' => $invoice,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ])->render();

        // Create mPDF instance
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'default_font_size' => 10,
            'default_font' => 'dejavusans',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5,
        ]);

        // Set document information
        $mpdf->SetTitle('Member Check-ins Report - ' . $member->name);
        $mpdf->SetAuthor('System');
        $mpdf->SetCreator('Member Subscription System');

        // Add header
        $mpdf->SetHTMLHeader('
        <div style="text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px;">
            <span style="font-size: 12px; color: #666;">Member Check-ins Report - ' . $member->name . '</span>
        </div>');

        // Add footer with page numbers
        $mpdf->SetHTMLFooter('
        <div style="text-align: center; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 5px;">
            Page {PAGENO} of {nbpg} | Generated on ' . now()->format('Y-m-d H:i:s') . '
        </div>');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Output PDF for download
        return $mpdf->Output($fileName, 'D');
    }
}
