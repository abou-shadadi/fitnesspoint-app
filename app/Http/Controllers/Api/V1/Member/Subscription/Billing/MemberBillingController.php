<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\MemberSubscription;
use App\Models\Member\Member;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberBillingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/billing/member-subscriptions",
     *     summary="List all member subscriptions with pagination (Billing Index)",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         required=false,
     *         description="Filter by member ID",
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
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="plan_id",
     *         in="query",
     *         required=false,
     *         description="Filter by plan ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Filter by start date from (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter by start date to (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by member name, subscription notes",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(
     *             type="string",
     *             enum={"start_date", "end_date", "created_at", "member_name"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(
     *             type="string",
     *             enum={"asc", "desc"},
     *             default="desc"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
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
     *         description="Paginated list of member subscriptions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member subscriptions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="subscriptions",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/MemberSubscription")),
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
     *                     @OA\Property(property="total_subscriptions", type="integer"),
     *                     @OA\Property(property="active_subscriptions", type="integer"),
     *                     @OA\Property(property="pending_subscriptions", type="integer"),
     *                     @OA\Property(property="expired_subscriptions", type="integer"),
     *                     @OA\Property(property="unique_members", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="filters",
     *                     type="object",
     *                     @OA\Property(property="applied_filters", type="object"),
     *                     @OA\Property(property="total_results", type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="pagination",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="per_page", type="integer"),
     *                     @OA\Property(property="total_pages", type="integer"),
     *                     @OA\Property(property="total_items", type="integer"),
     *                     @OA\Property(property="has_more_pages", type="boolean")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = MemberSubscription::with([
                'member',
                'plan',
                'branch',
                'created_by'
            ]);

            // Filter by member_id
            if ($request->has('member_id') && $request->member_id) {
                $query->where('member_id', $request->member_id);
            }

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])) {
                $query->where('status', $request->status);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by plan_id
            if ($request->has('plan_id') && $request->plan_id) {
                $query->where('plan_id', $request->plan_id);
            }

            // Filter by date range (for timestamps)
            if ($request->has('date_from') && $request->date_from) {
                try {
                    $dateFrom = Carbon::parse($request->date_from);
                    $query->where('start_date', '>=', $dateFrom->toDateTimeString());
                } catch (\Exception $e) {
                    // If parsing fails, try with date only
                    $query->whereDate('start_date', '>=', $request->date_from);
                }
            }

            if ($request->has('date_to') && $request->date_to) {
                try {
                    $dateTo = Carbon::parse($request->date_to);
                    $query->where('start_date', '<=', $dateTo->toDateTimeString());
                } catch (\Exception $e) {
                    // If parsing fails, try with date only
                    $query->whereDate('start_date', '<=', $request->date_to);
                }
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('notes', 'like', "%{$searchTerm}%")
                      ->orWhereHas('member', function ($memberQuery) use ($searchTerm) {
                          $memberQuery->where('first_name', 'like', "%{$searchTerm}%")
                                      ->orWhere('last_name', 'like', "%{$searchTerm}%")
                                      ->orWhere('email', 'like', "%{$searchTerm}%")
                                      ->orWhere('phone', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // Handle special sorting cases
            if ($sortBy === 'member_name') {
                $query->join('members', 'member_subscriptions.member_id', '=', 'members.id')
                    ->orderBy('members.first_name', $sortOrder)
                    ->orderBy('members.last_name', $sortOrder)
                    ->select('member_subscriptions.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination parameters
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
            $page = $request->input('page', 1);

            // Get total count for summary (before pagination)
            $totalQuery = clone $query;
            $totalSubscriptions = $totalQuery->count();

            // Get paginated results
            $subscriptions = $query->paginate($perPage, ['*'], 'page', $page);

            // Get all subscriptions for summary calculations (optimized query)
            $allSubscriptionsForSummary = MemberSubscription::when($request->has('member_id') && $request->member_id, function ($q) use ($request) {
                $q->where('member_id', $request->member_id);
            })
            ->when($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected']), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->has('date_from') && $request->date_from, function ($q) use ($request) {
                try {
                    $dateFrom = Carbon::parse($request->date_from);
                    $q->where('start_date', '>=', $dateFrom->toDateTimeString());
                } catch (\Exception $e) {
                    $q->whereDate('start_date', '>=', $request->date_from);
                }
            })
            ->when($request->has('date_to') && $request->date_to, function ($q) use ($request) {
                try {
                    $dateTo = Carbon::parse($request->date_to);
                    $q->where('start_date', '<=', $dateTo->toDateTimeString());
                } catch (\Exception $e) {
                    $q->whereDate('start_date', '<=', $request->date_to);
                }
            })
            ->get();

            // Calculate unique members count
            $uniqueMembers = $allSubscriptionsForSummary->pluck('member_id')->unique()->count();

            // Calculate summary statistics
            $summary = [
                'total_subscriptions' => $allSubscriptionsForSummary->count(),
                'active_subscriptions' => $allSubscriptionsForSummary->where('status', 'in_progress')->count(),
                'pending_subscriptions' => $allSubscriptionsForSummary->where('status', 'pending')->count(),
                'expired_subscriptions' => $allSubscriptionsForSummary->where('status', 'expired')->count(),
                'unique_members' => $uniqueMembers,
                'cancelled_subscriptions' => $allSubscriptionsForSummary->where('status', 'cancelled')->count(),
                'refunded_subscriptions' => $allSubscriptionsForSummary->where('status', 'refunded')->count(),
            ];

            // Additional pagination info
            $paginationInfo = [
                'current_page' => $subscriptions->currentPage(),
                'per_page' => $subscriptions->perPage(),
                'total_pages' => $subscriptions->lastPage(),
                'total_items' => $subscriptions->total(),
                'has_more_pages' => $subscriptions->hasMorePages(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member subscriptions retrieved successfully',
                'data' => [
                    'subscriptions' => $subscriptions,
                    'summary' => $summary,
                    'filters' => [
                        'applied_filters' => $request->all(),
                        'total_results' => $totalSubscriptions
                    ],
                    'pagination' => $paginationInfo
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
     *     path="/api/billing/member-subscriptions/{id}",
     *     summary="Show specific member subscription (Billing Details)",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
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
    public function show($id)
    {
        try {
            $subscription = MemberSubscription::with([
                'member',
                'plan',
                'branch',
                'created_by'
            ])->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            // Parse timestamps to Carbon instances
            $startDate = $subscription->start_date ? Carbon::parse($subscription->start_date) : null;
            $endDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : null;

            // Calculate additional details
            $details = [
                'duration_days' => null,
                'days_remaining' => null,
                'is_active' => false,
                'formatted_start_date' => $startDate ? $startDate->format('Y-m-d H:i:s') : null,
                'formatted_end_date' => $endDate ? $endDate->format('Y-m-d H:i:s') : null,
            ];

            // Calculate duration days
            if ($startDate && $endDate) {
                $details['duration_days'] = $startDate->diffInDays($endDate);
                $details['duration_hours'] = $startDate->diffInHours($endDate);
            }

            // Calculate days remaining
            if ($endDate) {
                $now = Carbon::now();
                $details['days_remaining'] = max(0, $now->diffInDays($endDate, false));
                $details['hours_remaining'] = max(0, $now->diffInHours($endDate, false));
                $details['is_expired'] = $endDate->isPast();
                $details['expires_in_human'] = $endDate->diffForHumans();
            }

            // Check if active
            $details['is_active'] = $subscription->status === 'in_progress' &&
                (!$endDate || $endDate->isFuture());

            // Member information
            $memberInfo = [];
            if ($subscription->member) {
                $memberInfo = [
                    'full_name' => $subscription->member->first_name . ' ' . $subscription->member->last_name,
                    'email' => $subscription->member->email,
                    'phone' => $subscription->member->phone,
                    'membership_number' => $subscription->member->membership_number,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Member subscription details retrieved successfully',
                'data' => [
                    'subscription' => $subscription,
                    'details' => $details,
                    'member_info' => $memberInfo
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
     *     path="/api/billing/member-subscriptions/summary/status",
     *     summary="Get member subscription status summary",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         required=false,
     *         description="Filter by member ID",
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
     *     @OA\Response(response=200, description="Status summary"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function statusSummary(Request $request)
    {
        try {
            $query = MemberSubscription::query();

            // Filter by member_id
            if ($request->has('member_id') && $request->member_id) {
                $query->where('member_id', $request->member_id);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                try {
                    $dateFrom = Carbon::parse($request->date_from);
                    $query->where('start_date', '>=', $dateFrom->toDateTimeString());
                } catch (\Exception $e) {
                    $query->whereDate('start_date', '>=', $request->date_from);
                }
            }

            if ($request->has('date_to') && $request->date_to) {
                try {
                    $dateTo = Carbon::parse($request->date_to);
                    $query->where('start_date', '<=', $dateTo->toDateTimeString());
                } catch (\Exception $e) {
                    $query->whereDate('start_date', '<=', $request->date_to);
                }
            }

            // Get all subscriptions
            $subscriptions = $query->get();

            // Calculate status breakdown
            $statusBreakdown = [
                'pending' => [
                    'count' => $subscriptions->where('status', 'pending')->count(),
                ],
                'in_progress' => [
                    'count' => $subscriptions->where('status', 'in_progress')->count(),
                ],
                'cancelled' => [
                    'count' => $subscriptions->where('status', 'cancelled')->count(),
                ],
                'expired' => [
                    'count' => $subscriptions->where('status', 'expired')->count(),
                ],
                'refunded' => [
                    'count' => $subscriptions->where('status', 'refunded')->count(),
                ],
                'rejected' => [
                    'count' => $subscriptions->where('status', 'rejected')->count(),
                ]
            ];

            // Calculate totals
            $totals = [
                'total_subscriptions' => $subscriptions->count(),
                'unique_members' => $subscriptions->pluck('member_id')->unique()->count(),
                'active_percentage' => $subscriptions->count() > 0
                    ? round(($subscriptions->where('status', 'in_progress')->count() / $subscriptions->count()) * 100, 2)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member subscription status summary retrieved successfully',
                'data' => [
                    'status_breakdown' => $statusBreakdown,
                    'totals' => $totals
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
     *     path="/api/billing/member-subscriptions/expiring-soon",
     *     summary="Get member subscriptions expiring soon with pagination",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to consider as 'soon' (default: 30)",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15, enum={10, 15, 25, 50})
     *     ),
     *     @OA\Response(response=200, description="Paginated expiring subscriptions"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function expiringSoon(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50]) ? $perPage : 15;
            $page = $request->input('page', 1);

            $now = Carbon::now();
            $futureDate = $now->copy()->addDays($days);

            $query = MemberSubscription::with(['member', 'branch', 'plan'])
                ->where('status', 'in_progress')
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now->toDateTimeString())
                ->where('end_date', '<=', $futureDate->toDateTimeString())
                ->orderBy('end_date');

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Get total count before pagination
            $totalExpiring = $query->count();

            // Get paginated results
            $expiringSubscriptions = $query->paginate($perPage, ['*'], 'page', $page);

            // Calculate days remaining for each subscription
            $expiringSubscriptions->getCollection()->transform(function ($subscription) use ($now) {
                if ($subscription->end_date) {
                    $endDate = Carbon::parse($subscription->end_date);
                    $subscription->days_remaining = max(0, $now->diffInDays($endDate, false));
                    $subscription->hours_remaining = max(0, $now->diffInHours($endDate, false));
                    $subscription->expires_in_human = $endDate->diffForHumans();
                } else {
                    $subscription->days_remaining = null;
                    $subscription->hours_remaining = null;
                    $subscription->expires_in_human = null;
                }
                return $subscription;
            });

            // Group by days remaining (using the full dataset, not paginated)
            $fullQuery = MemberSubscription::with(['member', 'branch', 'plan'])
                ->where('status', 'in_progress')
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now->toDateTimeString())
                ->where('end_date', '<=', $futureDate->toDateTimeString())
                ->when($request->has('branch_id') && $request->branch_id, function ($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                })
                ->orderBy('end_date')
                ->get();

            $grouped = [
                'within_7_days' => $fullQuery->filter(function ($subscription) use ($now) {
                    if (!$subscription->end_date) return false;
                    $endDate = Carbon::parse($subscription->end_date);
                    return $now->diffInDays($endDate) <= 7;
                })->values(),
                'within_15_days' => $fullQuery->filter(function ($subscription) use ($now) {
                    if (!$subscription->end_date) return false;
                    $endDate = Carbon::parse($subscription->end_date);
                    return $now->diffInDays($endDate) <= 15;
                })->values(),
                'within_30_days' => $fullQuery->filter(function ($subscription) use ($now) {
                    if (!$subscription->end_date) return false;
                    $endDate = Carbon::parse($subscription->end_date);
                    return $now->diffInDays($endDate) <= 30;
                })->values(),
            ];

            // Pagination info
            $paginationInfo = [
                'current_page' => $expiringSubscriptions->currentPage(),
                'per_page' => $expiringSubscriptions->perPage(),
                'total_pages' => $expiringSubscriptions->lastPage(),
                'total_items' => $expiringSubscriptions->total(),
                'has_more_pages' => $expiringSubscriptions->hasMorePages(),
            ];

            // Summary statistics
            $summary = [
                'total_expiring' => $totalExpiring,
                'unique_members_expiring' => $fullQuery->pluck('member_id')->unique()->count(),
                'by_plan' => $fullQuery->groupBy('plan_id')->map(function ($group) {
                    return [
                        'count' => $group->count(),
                        'plan_name' => $group->first()->plan->name ?? 'Unknown'
                    ];
                })->values(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Expiring member subscriptions retrieved successfully',
                'data' => [
                    'expiring_subscriptions' => $expiringSubscriptions,
                    'grouped_summary' => [
                        'within_7_days_count' => $grouped['within_7_days']->count(),
                        'within_15_days_count' => $grouped['within_15_days']->count(),
                        'within_30_days_count' => $grouped['within_30_days']->count(),
                    ],
                    'summary' => $summary,
                    'days_threshold' => $days,
                    'pagination' => $paginationInfo
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
     *     path="/api/billing/member-subscriptions/summary/daily-registrations",
     *     summary="Get daily subscription registrations",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to look back (default: 30)",
     *         @OA\Schema(type="integer", default=30)
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch ID",
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
     *     @OA\Response(response=200, description="Daily registration data"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function dailyRegistrations(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $branchId = $request->input('branch_id');
            $status = $request->input('status');

            $startDate = Carbon::now()->subDays($days);

            $query = MemberSubscription::query()
                ->whereDate('created_at', '>=', $startDate);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            if ($status && in_array($status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])) {
                $query->where('status', $status);
            }

            // Get daily counts
            $dailyData = $query->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as active_count')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Format data for chart
            $chartData = [
                'labels' => [],
                'datasets' => [
                    [
                        'label' => 'Total Subscriptions',
                        'data' => [],
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
                        'borderWidth' => 1
                    ],
                    [
                        'label' => 'Active Subscriptions',
                        'data' => [],
                        'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                        'borderColor' => 'rgba(75, 192, 192, 1)',
                        'borderWidth' => 1
                    ]
                ]
            ];

            foreach ($dailyData as $data) {
                $chartData['labels'][] = $data->date;
                $chartData['datasets'][0]['data'][] = $data->count;
                $chartData['datasets'][1]['data'][] = $data->active_count;
            }

            // Summary statistics
            $summary = [
                'total_period' => $dailyData->sum('count'),
                'average_daily' => $dailyData->count() > 0 ? round($dailyData->sum('count') / $dailyData->count(), 2) : 0,
                'peak_day' => $dailyData->isNotEmpty() ? $dailyData->sortByDesc('count')->first()->date : null,
                'peak_day_count' => $dailyData->isNotEmpty() ? $dailyData->sortByDesc('count')->first()->count : 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Daily subscription registrations retrieved successfully',
                'data' => [
                    'daily_data' => $dailyData,
                    'chart_data' => $chartData,
                    'summary' => $summary,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => Carbon::now()->format('Y-m-d'),
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

    /**
     * @OA\Get(
     *     path="/api/billing/member-subscriptions/active-by-branch",
     *     summary="Get active subscriptions count by branch",
     *     tags={"Billing | Member | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Active subscriptions by branch"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function activeByBranch()
    {
        try {
            $activeSubscriptions = MemberSubscription::where('status', 'in_progress')
                ->with('branch')
                ->get();

            // Group by branch
            $byBranch = $activeSubscriptions->groupBy('branch_id')->map(function ($group, $branchId) {
                $branch = $group->first()->branch;
                return [
                    'branch_id' => $branchId,
                    'branch_name' => $branch ? $branch->name : 'Unknown Branch',
                    'count' => $group->count(),
                    'unique_members' => $group->pluck('member_id')->unique()->count(),
                ];
            })->values();

            // Sort by count descending
            $byBranch = $byBranch->sortByDesc('count')->values();

            return response()->json([
                'success' => true,
                'message' => 'Active subscriptions by branch retrieved successfully',
                'data' => [
                    'by_branch' => $byBranch,
                    'total_active' => $activeSubscriptions->count(),
                    'total_unique_members' => $activeSubscriptions->pluck('member_id')->unique()->count(),
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
}
