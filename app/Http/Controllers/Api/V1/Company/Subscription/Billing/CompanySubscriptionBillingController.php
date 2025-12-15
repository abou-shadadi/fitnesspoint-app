<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\CompanySubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
/**
 * @OA\Schema(
 *     schema="CompanySubscription",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="company_id", type="integer", example=1),
 *     @OA\Property(property="plan_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59.000000Z"),
 *     @OA\Property(property="status", type="string", example="in_progress"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Annual subscription"),
 *     @OA\Property(property="branch_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="created_by_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00.000000Z"),
 *     @OA\Property(
 *         property="company",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="ABC Company"),
 *         @OA\Property(property="email", type="string", example="contact@abc.com"),
 *         @OA\Property(property="phone", type="string", example="+250788123456"),
 *     ),
 *     @OA\Property(
 *         property="branch",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Main Branch"),
 *         @OA\Property(property="address", type="string", example="Kigali, Rwanda"),
 *     ),
 *     @OA\Property(
 *         property="created_by",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com"),
 *     ),
 * )
 *
 * @OA\Schema(
 *     schema="MemberSubscription",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="member_id", type="integer", example=1),
 *     @OA\Property(property="plan_id", type="integer", example=1),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", nullable=true, example="2024-12-31T23:59:59.000000Z"),
 *     @OA\Property(property="status", type="string", example="in_progress"),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Annual membership"),
 *     @OA\Property(property="branch_id", type="integer", example=1),
 *     @OA\Property(property="created_by_id", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T12:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T12:00:00.000000Z"),
 *     @OA\Property(
 *         property="member",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="first_name", type="string", example="John"),
 *         @OA\Property(property="last_name", type="string", example="Doe"),
 *         @OA\Property(property="email", type="string", example="john@example.com"),
 *         @OA\Property(property="phone", type="string", example="+250788123456"),
 *         @OA\Property(property="membership_number", type="string", example="MEM001"),
 *     ),
 *     @OA\Property(
 *         property="plan",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Premium Plan"),
 *         @OA\Property(property="description", type="string", example="Premium membership with all benefits"),
 *     ),
 * )
 */
class CompanySubscriptionBillingController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/billing/company-subscriptions",
     *     summary="List all company subscriptions with pagination (Billing Index)",
     *     tags={"Billing | Company | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         required=false,
     *         description="Filter by company ID",
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
     *         name="duration_type_id",
     *         in="query",
     *         required=false,
     *         description="Filter by duration type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="billing_type_id",
     *         in="query",
     *         required=false,
     *         description="Filter by billing type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="query",
     *         required=false,
     *         description="Filter by currency ID",
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
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by company name, subscription notes",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(
     *             type="string",
     *             enum={"start_date", "end_date", "unit_price", "created_at", "company_name"}
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
     *         description="Paginated list of company subscriptions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Company subscriptions retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="subscriptions",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/CompanySubscription")),
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
     *                     @OA\Property(property="total_amount", type="number", format="float"),
     *                     @OA\Property(property="active_subscriptions", type="integer"),
     *                     @OA\Property(property="pending_subscriptions", type="integer"),
     *                     @OA\Property(property="expired_subscriptions", type="integer")
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
            $query = CompanySubscription::with([
                'company',
                'currency',
                'duration_type',
                'billing_type',
                'branch',
                'benefits.benefit',
                'created_by'
            ]);

            // Filter by company_id
            if ($request->has('company_id') && $request->company_id) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected'])) {
                $query->where('status', $request->status);
            }

            // Filter by branch_id
            if ($request->has('branch_id') && $request->branch_id) {
                $query->where('branch_id', $request->branch_id);
            }

            // Filter by duration_type_id
            if ($request->has('duration_type_id') && $request->duration_type_id) {
                $query->where('duration_type_id', $request->duration_type_id);
            }

            // Filter by billing_type_id
            if ($request->has('billing_type_id') && $request->billing_type_id) {
                $query->where('billing_type_id', $request->billing_type_id);
            }

            // Filter by currency_id
            if ($request->has('currency_id') && $request->currency_id) {
                $query->where('currency_id', $request->currency_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('start_date', '<=', $request->date_to);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('notes', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                            $companyQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'created_at');
            $sortOrder = $request->input('sort_order', 'desc');

            // Handle special sorting cases
            if ($sortBy === 'company_name') {
                $query->join('companies', 'company_subscriptions.company_id', '=', 'companies.id')
                    ->orderBy('companies.name', $sortOrder)
                    ->select('company_subscriptions.*');
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

            // Get all subscriptions for summary calculations (this could be optimized if you have many records)
            $allSubscriptionsForSummary = CompanySubscription::when($request->has('company_id') && $request->company_id, function ($q) use ($request) {
                $q->where('company_id', $request->company_id);
            })
                ->when($request->has('status') && in_array($request->status, ['pending', 'in_progress', 'cancelled', 'expired', 'refunded', 'rejected']), function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->has('date_from') && $request->date_from, function ($q) use ($request) {
                    $q->whereDate('start_date', '>=', $request->date_from);
                })
                ->when($request->has('date_to') && $request->date_to, function ($q) use ($request) {
                    $q->whereDate('start_date', '<=', $request->date_to);
                })
                ->get();

            // Calculate summary statistics
            $summary = [
                'total_subscriptions' => $allSubscriptionsForSummary->count(),
                'total_amount' => $allSubscriptionsForSummary->sum('unit_price'),
                'active_subscriptions' => $allSubscriptionsForSummary->where('status', 'in_progress')->count(),
                'pending_subscriptions' => $allSubscriptionsForSummary->where('status', 'pending')->count(),
                'expired_subscriptions' => $allSubscriptionsForSummary->where('status', 'expired')->count(),
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
                'message' => 'Company subscriptions retrieved successfully',
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
     *     path="/api/billing/company-subscriptions/{id}",
     *     summary="Show specific company subscription (Billing Details)",
     *     tags={"Billing | Company | Subscriptions"},
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
            $subscription = CompanySubscription::with([
                'company',
                'currency',
                'duration_type',
                'billing_type',
                'branch',
                'benefits.benefit',
                'created_by',
                'company_subscription_members.member' // If you have members relationship
            ])->find($id);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
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
                'total_benefits' => $subscription->benefits->count(),
                'active_benefits' => $subscription->benefits->where('status', 'active')->count(),
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

            return response()->json([
                'success' => true,
                'message' => 'Company subscription details retrieved successfully',
                'data' => [
                    'subscription' => $subscription,
                    'details' => $details
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
     *     path="/api/billing/company-subscriptions/expiring-soon",
     *     summary="Get subscriptions expiring soon with pagination",
     *     tags={"Billing | Company | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to consider as 'soon' (default: 30)",
     *         @OA\Schema(type="integer", default=30)
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

            $query = CompanySubscription::with(['company', 'branch'])
                ->where('status', 'in_progress')
                ->whereNotNull('end_date')
                // For timestamps, use where() instead of whereDate()
                ->where('end_date', '>=', $now->toDateTimeString())
                ->where('end_date', '<=', $futureDate->toDateTimeString())
                ->orderBy('end_date');

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
            $fullQuery = CompanySubscription::with(['company', 'branch'])
                ->where('status', 'in_progress')
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now->toDateTimeString())
                ->where('end_date', '<=', $futureDate->toDateTimeString())
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

            return response()->json([
                'success' => true,
                'message' => 'Expiring subscriptions retrieved successfully',
                'data' => [
                    'expiring_subscriptions' => $expiringSubscriptions,
                    'grouped_summary' => [
                        'within_7_days_count' => $grouped['within_7_days']->count(),
                        'within_15_days_count' => $grouped['within_15_days']->count(),
                        'within_30_days_count' => $grouped['within_30_days']->count(),
                    ],
                    'total_expiring' => $totalExpiring,
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
     *     path="/api/billing/company-subscriptions/summary/status",
     *     summary="Get subscription status summary",
     *     tags={"Billing | Company | Subscriptions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         required=false,
     *         description="Filter by company ID",
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
            $query = CompanySubscription::query();

            // Filter by company_id
            if ($request->has('company_id') && $request->company_id) {
                $query->where('company_id', $request->company_id);
            }

            // Filter by date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('start_date', '<=', $request->date_to);
            }

            // Get all subscriptions
            $subscriptions = $query->get();

            // Calculate status breakdown
            $statusBreakdown = [
                'pending' => [
                    'count' => $subscriptions->where('status', 'pending')->count(),
                    'amount' => $subscriptions->where('status', 'pending')->sum('unit_price')
                ],
                'in_progress' => [
                    'count' => $subscriptions->where('status', 'in_progress')->count(),
                    'amount' => $subscriptions->where('status', 'in_progress')->sum('unit_price')
                ],
                'cancelled' => [
                    'count' => $subscriptions->where('status', 'cancelled')->count(),
                    'amount' => $subscriptions->where('status', 'cancelled')->sum('unit_price')
                ],
                'expired' => [
                    'count' => $subscriptions->where('status', 'expired')->count(),
                    'amount' => $subscriptions->where('status', 'expired')->sum('unit_price')
                ],
                'refunded' => [
                    'count' => $subscriptions->where('status', 'refunded')->count(),
                    'amount' => $subscriptions->where('status', 'refunded')->sum('unit_price')
                ],
                'rejected' => [
                    'count' => $subscriptions->where('status', 'rejected')->count(),
                    'amount' => $subscriptions->where('status', 'rejected')->sum('unit_price')
                ]
            ];

            // Calculate totals
            $totals = [
                'total_subscriptions' => $subscriptions->count(),
                'total_amount' => $subscriptions->sum('unit_price'),
                'active_amount' => $subscriptions->where('status', 'in_progress')->sum('unit_price')
            ];

            return response()->json([
                'success' => true,
                'message' => 'Subscription status summary retrieved successfully',
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
}
