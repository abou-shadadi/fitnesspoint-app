<?php

namespace App\Http\Controllers\Api\V1\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\Member\MemberSubscriptionCheckIn;
use App\Models\Company\Company;
use App\Models\Member\Member;
use App\Models\CheckIn\CheckInMethod;
use App\Models\Branch\Branch;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MemberCheckInController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/check-ins",
     *     summary="Get all check-ins (combined company and member)",
     *     tags={"Check-Ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         required=false,
     *         description="Filter by check-in type",
     *         @OA\Schema(
     *             type="string",
     *             enum={"company", "member", "all"},
     *             default="all"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         required=false,
     *         description="Filter by member ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="company_id",
     *         in="query",
     *         required=false,
     *         description="Filter by company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="subscription_id",
     *         in="query",
     *         required=false,
     *         description="Filter by subscription ID (member subscription)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="company_subscription_id",
     *         in="query",
     *         required=false,
     *         description="Filter by company subscription ID",
     *         @OA\Schema(type="integer")
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
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"completed", "failed", "pending"}
     *         )
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
     *         name="time_from",
     *         in="query",
     *         required=false,
     *         description="Filter by time from (HH:MM)",
     *         @OA\Schema(type="string", pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$")
     *     ),
     *     @OA\Parameter(
     *         name="time_to",
     *         in="query",
     *         required=false,
     *         description="Filter by time to (HH:MM)",
     *         @OA\Schema(type="string", pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$")
     *     ),
     *     @OA\Parameter(
     *         name="has_signature",
     *         in="query",
     *         required=false,
     *         description="Filter by presence of signature",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by member name, company name, or notes",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(
     *             type="string",
     *             enum={"datetime", "created_at", "member_name", "company_name"},
     *             default="datetime"
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
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(response=200, description="Combined check-ins list"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $type = $request->input('type', 'all');

            // Get company check-ins
            $companyQuery = CompanySubscriptionMemberCheckIn::with([
                'company_subscription_member.member',
                'company_subscription_member.company_subscription.company',
                'check_in_method',
                'branch',
                'created_by'
            ]);

            // Get member check-ins
            $memberQuery = MemberSubscriptionCheckIn::with([
                'member_subscription.member',
                'member_subscription.plan',
                'check_in_method',
                'branch',
                'created_by'
            ]);

            // Apply filters to both queries
            $this->applyCommonFilters($companyQuery, $request, 'company');
            $this->applyCommonFilters($memberQuery, $request, 'member');

            // Apply type-specific filters
            if ($type === 'company') {
                $companyCheckIns = $companyQuery->paginate($perPage);
                $memberCheckIns = collect();
            } elseif ($type === 'member') {
                $memberCheckIns = $memberQuery->paginate($perPage);
                $companyCheckIns = collect();
            } else {
                $companyCheckIns = $companyQuery->get();
                $memberCheckIns = $memberQuery->get();
            }

            // Combine and transform data
            $combinedCheckIns = $this->combineCheckIns($companyCheckIns, $memberCheckIns, $type !== 'all');

            // Calculate statistics
            $stats = $this->calculateStatistics($companyCheckIns, $memberCheckIns);

            // Apply sorting if not paginated
            if ($type === 'all') {
                $sortBy = $request->input('sort_by', 'datetime');
                $sortOrder = $request->input('sort_order', 'desc');

                $combinedCheckIns = $combinedCheckIns->sortBy(function ($item) use ($sortBy) {
                    return match ($sortBy) {
                        'member_name' => $item['member_name'] ?? '',
                        'company_name' => $item['company_name'] ?? '',
                        'created_at' => $item['created_at'],
                        default => $item['datetime']
                    };
                }, SORT_REGULAR, $sortOrder === 'desc');

                // Manual pagination for combined results
                $page = $request->input('page', 1);
                $offset = ($page - 1) * $perPage;
                $paginatedData = $combinedCheckIns->slice($offset, $perPage)->values();

                $response = [
                    'data' => $paginatedData,
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $combinedCheckIns->count(),
                        'last_page' => ceil($combinedCheckIns->count() / $perPage),
                    ]
                ];
            } else {
                $response = [
                    'data' => $combinedCheckIns,
                    'meta' => $type === 'company' ? $companyCheckIns->toArray()['meta'] : $memberCheckIns->toArray()['meta']
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-ins retrieved successfully',
                'data' => array_merge($response, [
                    'stats' => $stats,
                    'filters_applied' => $request->all()
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
     * @OA\Get(
     *     path="/api/check-ins/{id}",
     *     summary="Get specific check-in by ID",
     *     tags={"Check-Ins"},
     *     security={{"sanctum":{}}},
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
    public function show($id)
    {
        try {
            // Try to find in company check-ins first
            $checkIn = CompanySubscriptionMemberCheckIn::with([
                'company_subscription_member.member',
                'company_subscription_member.company_subscription.company',
                'check_in_method',
                'branch',
                'created_by'
            ])->find($id);

            $type = 'company';

            // If not found in company check-ins, try member check-ins
            if (!$checkIn) {
                $checkIn = MemberSubscriptionCheckIn::with([
                    'member_subscription.member',
                    'member_subscription.plan',
                    'check_in_method',
                    'branch',
                    'created_by'
                ])->find($id);
                $type = 'member';
            }

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found',
                    'data' => null
                ], 404);
            }

            // Transform to common format
            $transformed = $this->transformCheckIn($checkIn, $type);

            // Add signature URL if exists
            if ($checkIn->signature) {
                $transformed['signature_url'] = \Illuminate\Support\Facades\Storage::url($checkIn->signature);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in retrieved successfully',
                'data' => $transformed
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
     *     path="/api/check-ins/summary/daily",
     *     summary="Get daily check-in summary",
     *     tags={"Check-Ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Specific date (YYYY-MM-DD), defaults to today",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="days",
     *         in="query",
     *         required=false,
     *         description="Number of days to include (default: 7)",
     *         @OA\Schema(type="integer", default=7)
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="check_in_method_id",
     *         in="query",
     *         required=false,
     *         description="Filter by check-in method",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Daily summary"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function dailySummary(Request $request)
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));
            $days = $request->input('days', 7);
            $endDate = Carbon::parse($date);
            $startDate = $endDate->copy()->subDays($days - 1);

            $summary = [];
            $currentDate = $startDate->copy();

            while ($currentDate <= $endDate) {
                $dateString = $currentDate->format('Y-m-d');

                // Get company check-ins for the day
                $companyQuery = CompanySubscriptionMemberCheckIn::whereDate('datetime', $dateString)
                    ->where('status', 'completed');

                // Get member check-ins for the day
                $memberQuery = MemberSubscriptionCheckIn::whereDate('datetime', $dateString)
                    ->where('status', 'completed');

                // Apply filters
                if ($request->has('branch_id')) {
                    $companyQuery->where('branch_id', $request->branch_id);
                    $memberQuery->where('branch_id', $request->branch_id);
                }

                if ($request->has('check_in_method_id')) {
                    $companyQuery->where('check_in_method_id', $request->check_in_method_id);
                    $memberQuery->where('check_in_method_id', $request->check_in_method_id);
                }

                $companyCount = $companyQuery->count();
                $memberCount = $memberQuery->count();

                // Get hourly breakdown
                $hourlyBreakdown = $this->getHourlyBreakdown($dateString, $request);

                $summary[] = [
                    'date' => $dateString,
                    'day_name' => $currentDate->format('l'),
                    'total_check_ins' => $companyCount + $memberCount,
                    'company_check_ins' => $companyCount,
                    'member_check_ins' => $memberCount,
                    'hourly_breakdown' => $hourlyBreakdown,
                    'peak_hour' => $this->getPeakHour($hourlyBreakdown),
                ];

                $currentDate->addDay();
            }

            // Calculate overall statistics
            $overallStats = [
                'total_days' => count($summary),
                'total_check_ins' => collect($summary)->sum('total_check_ins'),
                'average_daily' => round(collect($summary)->avg('total_check_ins'), 2),
                'busiest_day' => collect($summary)->sortByDesc('total_check_ins')->first(),
                'company_total' => collect($summary)->sum('company_check_ins'),
                'member_total' => collect($summary)->sum('member_check_ins'),
                'company_percentage' => $this->calculatePercentage(
                    collect($summary)->sum('company_check_ins'),
                    collect($summary)->sum('total_check_ins')
                ),
                'member_percentage' => $this->calculatePercentage(
                    collect($summary)->sum('member_check_ins'),
                    collect($summary)->sum('total_check_ins')
                ),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Daily check-in summary retrieved successfully',
                'data' => [
                    'summary' => $summary,
                    'overall_stats' => $overallStats,
                    'period' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'days' => $days
                    ],
                    'filters' => $request->only(['branch_id', 'check_in_method_id'])
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
     *     path="/api/check-ins/summary/hourly",
     *     summary="Get hourly check-in summary",
     *     tags={"Check-Ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="date",
     *         in="query",
     *         required=false,
     *         description="Specific date (YYYY-MM-DD), defaults to today",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="branch_id",
     *         in="query",
     *         required=false,
     *         description="Filter by branch",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Hourly summary"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function hourlySummary(Request $request)
    {
        try {
            $date = $request->input('date', now()->format('Y-m-d'));

            $hourlySummary = [];
            for ($hour = 0; $hour < 24; $hour++) {
                $startTime = Carbon::parse($date)->setHour($hour)->setMinute(0)->setSecond(0);
                $endTime = Carbon::parse($date)->setHour($hour)->setMinute(59)->setSecond(59);

                // Company check-ins
                $companyQuery = CompanySubscriptionMemberCheckIn::whereBetween('datetime', [$startTime, $endTime])
                    ->where('status', 'completed');

                // Member check-ins
                $memberQuery = MemberSubscriptionCheckIn::whereBetween('datetime', [$startTime, $endTime])
                    ->where('status', 'completed');

                if ($request->has('branch_id')) {
                    $companyQuery->where('branch_id', $request->branch_id);
                    $memberQuery->where('branch_id', $request->branch_id);
                }

                $companyCount = $companyQuery->count();
                $memberCount = $memberQuery->count();

                $hourlySummary[] = [
                    'hour' => sprintf('%02d:00', $hour),
                    'hour_display' => sprintf('%02d:00 - %02d:59', $hour, $hour),
                    'company_check_ins' => $companyCount,
                    'member_check_ins' => $memberCount,
                    'total_check_ins' => $companyCount + $memberCount,
                ];
            }

            // Calculate peak hours
            $peakHours = collect($hourlySummary)
                ->sortByDesc('total_check_ins')
                ->take(3)
                ->values()
                ->all();

            return response()->json([
                'success' => true,
                'message' => 'Hourly check-in summary retrieved successfully',
                'data' => [
                    'date' => $date,
                    'hourly_summary' => $hourlySummary,
                    'peak_hours' => $peakHours,
                    'total_for_day' => collect($hourlySummary)->sum('total_check_ins'),
                    'busiest_hour' => collect($hourlySummary)->sortByDesc('total_check_ins')->first(),
                    'average_per_hour' => round(collect($hourlySummary)->avg('total_check_ins'), 2),
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
     *     path="/api/check-ins/summary/member-activity",
     *     summary="Get member activity summary",
     *     tags={"Check-Ins"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Start date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="End date (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         required=false,
     *         description="Number of top members to return (default: 10)",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(response=200, description="Member activity summary"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function memberActivitySummary(Request $request)
    {
        try {
            $dateFrom = $request->input('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->input('date_to', now()->format('Y-m-d'));
            $limit = $request->input('limit', 10);

            // Get active members from company check-ins
            $companyMembers = CompanySubscriptionMemberCheckIn::with('company_subscription_member.member')
                ->whereBetween('datetime', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('status', 'completed')
                ->get()
                ->groupBy('company_subscription_member.member_id')
                ->map(function ($checkIns, $memberId) {
                    $member = $checkIns->first()->company_subscription_member->member;
                    return [
                        'member_id' => $memberId,
                        'member_name' => $member->name ?? 'Unknown',
                        'member_email' => $member->email ?? null,
                        'check_in_count' => $checkIns->count(),
                        'last_check_in' => $checkIns->sortByDesc('datetime')->first()->datetime,
                        'type' => 'company',
                        'details' => [
                            'company_check_ins' => $checkIns->count(),
                            'member_check_ins' => 0,
                        ]
                    ];
                });

            // Get active members from member check-ins
            $memberMembers = MemberSubscriptionCheckIn::with('member_subscription.member')
                ->whereBetween('datetime', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('status', 'completed')
                ->get()
                ->groupBy('member_subscription.member_id')
                ->map(function ($checkIns, $memberId) {
                    $member = $checkIns->first()->member_subscription->member;
                    return [
                        'member_id' => $memberId,
                        'member_name' => $member->name ?? 'Unknown',
                        'member_email' => $member->email ?? null,
                        'check_in_count' => $checkIns->count(),
                        'last_check_in' => $checkIns->sortByDesc('datetime')->first()->datetime,
                        'type' => 'member',
                        'details' => [
                            'company_check_ins' => 0,
                            'member_check_ins' => $checkIns->count(),
                        ]
                    ];
                });

            // Merge and combine counts for members who have both types
            $allMembers = collect();

            foreach ($companyMembers as $memberId => $companyData) {
                if ($memberMembers->has($memberId)) {
                    $memberData = $memberMembers[$memberId];
                    $allMembers->push([
                        'member_id' => $memberId,
                        'member_name' => $companyData['member_name'],
                        'member_email' => $companyData['member_email'] ?? $memberData['member_email'],
                        'check_in_count' => $companyData['check_in_count'] + $memberData['check_in_count'],
                        'last_check_in' => max($companyData['last_check_in'], $memberData['last_check_in']),
                        'type' => 'both',
                        'details' => [
                            'company_check_ins' => $companyData['check_in_count'],
                            'member_check_ins' => $memberData['check_in_count'],
                            'total_check_ins' => $companyData['check_in_count'] + $memberData['check_in_count']
                        ]
                    ]);
                    $memberMembers->forget($memberId);
                } else {
                    $allMembers->push($companyData);
                }
            }

            // Add remaining member-only members
            $allMembers = $allMembers->merge($memberMembers->values());

            // Sort by check-in count and take top N
            $topMembers = $allMembers->sortByDesc('check_in_count')->take($limit)->values();

            // Calculate overall statistics
            $overallStats = [
                'total_members_active' => $allMembers->count(),
                'total_check_ins' => $allMembers->sum('check_in_count'),
                'average_check_ins_per_member' => $allMembers->count() > 0
                    ? round($allMembers->sum('check_in_count') / $allMembers->count(), 2)
                    : 0,
                'top_member' => $topMembers->first(),
                'members_by_type' => [
                    'company_only' => $allMembers->where('type', 'company')->count(),
                    'member_only' => $allMembers->where('type', 'member')->count(),
                    'both' => $allMembers->where('type', 'both')->count(),
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Member activity summary retrieved successfully',
                'data' => [
                    'top_members' => $topMembers,
                    'overall_stats' => $overallStats,
                    'period' => [
                        'date_from' => $dateFrom,
                        'date_to' => $dateTo,
                    ],
                    'limit' => $limit
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
     * Apply common filters to query
     */
    private function applyCommonFilters($query, Request $request, string $type): void
    {
        // Filter by date range
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('datetime', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('datetime', '<=', $request->date_to);
        }

        // Filter by time range
        if ($request->has('time_from') && $request->time_from) {
            $query->whereTime('datetime', '>=', $request->time_from);
        }

        if ($request->has('time_to') && $request->time_to) {
            $query->whereTime('datetime', '<=', $request->time_to);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by check-in method
        if ($request->has('check_in_method_id') && $request->check_in_method_id) {
            $query->where('check_in_method_id', $request->check_in_method_id);
        }

        // Filter by branch
        if ($request->has('branch_id') && $request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filter by signature presence
        if ($request->has('has_signature')) {
            if ($request->boolean('has_signature')) {
                $query->whereNotNull('signature');
            } else {
                $query->whereNull('signature');
            }
        }

        // Apply type-specific filters
        if ($type === 'company') {
            if ($request->has('company_id') && $request->company_id) {
                $query->whereHas('company_subscription_member.company_subscription', function ($q) use ($request) {
                    $q->where('company_id', $request->company_id);
                });
            }

            if ($request->has('company_subscription_id') && $request->company_subscription_id) {
                $query->whereHas('company_subscription_member', function ($q) use ($request) {
                    $q->where('company_subscription_id', $request->company_subscription_id);
                });
            }

            if ($request->has('member_id') && $request->member_id) {
                $query->whereHas('company_subscription_member', function ($q) use ($request) {
                    $q->where('member_id', $request->member_id);
                });
            }
        } else {
            if ($request->has('member_id') && $request->member_id) {
                $query->whereHas('member_subscription', function ($q) use ($request) {
                    $q->where('member_id', $request->member_id);
                });
            }

            if ($request->has('subscription_id') && $request->subscription_id) {
                $query->where('member_subscription_id', $request->subscription_id);
            }
        }

        // Search functionality
        if ($request->has('search') && $request->search) {
            $searchTerm = $request->search;
            if ($type === 'company') {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('notes', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company_subscription_member.member', function ($memberQuery) use ($searchTerm) {
                            $memberQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('company_subscription_member.company_subscription.company', function ($companyQuery) use ($searchTerm) {
                            $companyQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            } else {
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('notes', 'like', "%{$searchTerm}%")
                        ->orWhereHas('member_subscription.member', function ($memberQuery) use ($searchTerm) {
                            $memberQuery->where('name', 'like', "%{$searchTerm}%")
                                ->orWhere('email', 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('member_subscription.plan', function ($planQuery) use ($searchTerm) {
                            $planQuery->where('name', 'like', "%{$searchTerm}%");
                        });
                });
            }
        }
    }

    /**
     * Combine check-ins from both sources
     */
    private function combineCheckIns($companyCheckIns, $memberCheckIns, bool $isPaginated = false): \Illuminate\Support\Collection
    {
        $combined = collect();

        // Transform company check-ins
        foreach ($companyCheckIns as $checkIn) {
            $combined->push($this->transformCheckIn($checkIn, 'company'));
        }

        // Transform member check-ins
        foreach ($memberCheckIns as $checkIn) {
            $combined->push($this->transformCheckIn($checkIn, 'member'));
        }

        return $combined;
    }

    /**
     * Transform check-in to common format
     */
    private function transformCheckIn($checkIn, string $type): array
    {
        $baseData = [
            'id' => $checkIn->id,
            'type' => $type,
            'datetime' => $checkIn->datetime,
            'notes' => $checkIn->notes,
            'status' => $checkIn->status,
            'signature' => $checkIn->signature,
            'metadata' => $checkIn->metadata,
            'created_at' => $checkIn->created_at,
            'updated_at' => $checkIn->updated_at,
            'check_in_method' => $checkIn->check_in_method ? [
                'id' => $checkIn->check_in_method->id,
                'name' => $checkIn->check_in_method->name,
            ] : null,
            'branch' => $checkIn->branch ? [
                'id' => $checkIn->branch->id,
                'name' => $checkIn->branch->name,
            ] : null,
            'created_by' => $checkIn->created_by ? [
                'id' => $checkIn->created_by->id,
                'name' => $checkIn->created_by->name,
                'email' => $checkIn->created_by->email,
            ] : null,
        ];

        if ($type === 'company') {
            $baseData['member'] = $checkIn->company_subscription_member->member ? [
                'id' => $checkIn->company_subscription_member->member->id,
                'name' => $checkIn->company_subscription_member->member->name,
                'email' => $checkIn->company_subscription_member->member->email,
            ] : null;
            $baseData['company'] = $checkIn->company_subscription_member->company_subscription->company ? [
                'id' => $checkIn->company_subscription_member->company_subscription->company->id,
                'name' => $checkIn->company_subscription_member->company_subscription->company->name,
            ] : null;
            $baseData['subscription'] = [
                'id' => $checkIn->company_subscription_member->company_subscription_id,
                'type' => 'company',
            ];
        } else {
            $baseData['member'] = $checkIn->member_subscription->member ? [
                'id' => $checkIn->member_subscription->member->id,
                'name' => $checkIn->member_subscription->member->name,
                'email' => $checkIn->member_subscription->member->email,
            ] : null;
            $baseData['plan'] = $checkIn->member_subscription->plan ? [
                'id' => $checkIn->member_subscription->plan->id,
                'name' => $checkIn->member_subscription->plan->name,
            ] : null;
            $baseData['subscription'] = [
                'id' => $checkIn->member_subscription_id,
                'type' => 'member',
            ];
        }

        return $baseData;
    }

    /**
     * Calculate statistics
     */
    private function calculateStatistics($companyCheckIns, $memberCheckIns): array
    {
        $companyCount = $companyCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $companyCheckIns->total()
            : $companyCheckIns->count();

        $memberCount = $memberCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? $memberCheckIns->total()
            : $memberCheckIns->count();

        $total = $companyCount + $memberCount;

        return [
            'total_check_ins' => $total,
            'company_check_ins' => $companyCount,
            'member_check_ins' => $memberCount,
            'company_percentage' => $total > 0 ? round(($companyCount / $total) * 100, 2) : 0,
            'member_percentage' => $total > 0 ? round(($memberCount / $total) * 100, 2) : 0,
            'completed_count' => $this->getCompletedCount($companyCheckIns, $memberCheckIns),
            'failed_count' => $this->getFailedCount($companyCheckIns, $memberCheckIns),
        ];
    }

    /**
     * Get completed count
     */
    private function getCompletedCount($companyCheckIns, $memberCheckIns): int
    {
        $companyCompleted = $companyCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? CompanySubscriptionMemberCheckIn::where('status', 'completed')->count()
            : $companyCheckIns->where('status', 'completed')->count();

        $memberCompleted = $memberCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? MemberSubscriptionCheckIn::where('status', 'completed')->count()
            : $memberCheckIns->where('status', 'completed')->count();

        return $companyCompleted + $memberCompleted;
    }

    /**
     * Get failed count
     */
    private function getFailedCount($companyCheckIns, $memberCheckIns): int
    {
        $companyFailed = $companyCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? CompanySubscriptionMemberCheckIn::where('status', 'failed')->count()
            : $companyCheckIns->where('status', 'failed')->count();

        $memberFailed = $memberCheckIns instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? MemberSubscriptionCheckIn::where('status', 'failed')->count()
            : $memberCheckIns->where('status', 'failed')->count();

        return $companyFailed + $memberFailed;
    }

    /**
     * Get hourly breakdown for a date
     */
    private function getHourlyBreakdown(string $date, Request $request): array
    {
        $breakdown = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $startTime = Carbon::parse($date)->setHour($hour)->setMinute(0)->setSecond(0);
            $endTime = Carbon::parse($date)->setHour($hour)->setMinute(59)->setSecond(59);

            $companyCount = CompanySubscriptionMemberCheckIn::whereBetween('datetime', [$startTime, $endTime])
                ->where('status', 'completed');

            $memberCount = MemberSubscriptionCheckIn::whereBetween('datetime', [$startTime, $endTime])
                ->where('status', 'completed');

            if ($request->has('branch_id')) {
                $companyCount->where('branch_id', $request->branch_id);
                $memberCount->where('branch_id', $request->branch_id);
            }

            if ($request->has('check_in_method_id')) {
                $companyCount->where('check_in_method_id', $request->check_in_method_id);
                $memberCount->where('check_in_method_id', $request->check_in_method_id);
            }

            $breakdown[] = [
                'hour' => sprintf('%02d:00', $hour),
                'company_check_ins' => $companyCount->count(),
                'member_check_ins' => $memberCount->count(),
                'total_check_ins' => $companyCount->count() + $memberCount->count(),
            ];
        }

        return $breakdown;
    }

    /**
     * Get peak hour from hourly breakdown
     */
    private function getPeakHour(array $hourlyBreakdown): ?array
    {
        if (empty($hourlyBreakdown)) {
            return null;
        }

        $peak = collect($hourlyBreakdown)->sortByDesc('total_check_ins')->first();
        return $peak['total_check_ins'] > 0 ? $peak : null;
    }

    /**
     * Calculate percentage
     */
    private function calculatePercentage(int $part, int $total): float
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }
}
