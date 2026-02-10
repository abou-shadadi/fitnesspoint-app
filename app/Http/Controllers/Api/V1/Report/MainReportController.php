<?php


namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionCheckIn;
use App\Models\Member\MemberSubscriptionTransaction;

use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionMember;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\Company\CompanySubscriptionTransaction;

use App\Models\Payment\PaymentMethod;
use App\Models\Branch\Branch;

class MainReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/reports",
     *     tags={"Reports"},
     *     summary="Get Main Report Data",
     *     description="Returns comprehensive report data including Overview, Attendance, Corporate, and Insights.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="overview",
     *                 type="object",
     *                 @OA\Property(
     *                     property="mothly_performance",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="individual_checkins", type="integer"),
     *                         @OA\Property(property="corporate_checkins", type="integer"),
     *                         @OA\Property(property="individual_revenue", type="number", format="float"),
     *                         @OA\Property(property="corporate_revenue", type="number", format="float"),
     *                         @OA\Property(property="individual_members", type="integer"),
     *                         @OA\Property(property="corporate_members", type="integer"),
     *                         @OA\Property(property="individual_expiring_soon", type="integer"),
     *                         @OA\Property(property="corporate_expiring_soon", type="integer"),
     *                         @OA\Property(property="total_checkins", type="integer"),
     *                         @OA\Property(property="total_revenue", type="number", format="float"),
     *                         @OA\Property(property="total_members", type="integer"),
     *                         @OA\Property(property="total_expiring_soon", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="payment_methods",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\AdditionalProperties(type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="member_growth",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="corporate_new_members", type="integer"),
     *                         @OA\Property(property="individual_new_members", type="integer"),
     *                         @OA\Property(property="corporate_churned_members", type="integer"),
     *                         @OA\Property(property="individual_churned_members", type="integer"),
     *                         @OA\Property(property="total_company_and_individual_new_members", type="integer"),
     *                         @OA\Property(property="total_company_and_individual_churned_members", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="member_demographics",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="label", type="string"),
     *                         @OA\Property(property="total_male", type="integer"),
     *                         @OA\Property(property="total_female", type="integer"),
     *                         @OA\Property(property="total_other", type="integer"),
     *                         @OA\Property(property="total", type="integer")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="attendance",
     *                 type="object",
     *                 @OA\Property(
     *                     property="cards",
     *                     type="object",
     *                     @OA\Property(
     *                         property="weekly",
     *                         type="object",
     *                         @OA\Property(property="total_checkins", type="object", @OA\Property(property="total", type="integer")),
     *                         @OA\Property(property="avarage_daily_visits", type="object", @OA\Property(property="total", type="number", format="float")),
     *                         @OA\Property(property="growth_rate", type="object", @OA\Property(property="total", type="number", format="float"))
     *                     ),
     *                     @OA\Property(
     *                         property="monthly",
     *                         type="object",
     *                         @OA\Property(property="total_checkins", type="object", @OA\Property(property="total", type="integer")),
     *                         @OA\Property(property="avarage_daily_visits", type="object", @OA\Property(property="total", type="number", format="float")),
     *                         @OA\Property(property="growth_rate", type="object", @OA\Property(property="total", type="number", format="float"))
     *                     ),
     *                     @OA\Property(
     *                         property="yearly",
     *                         type="object",
     *                         @OA\Property(property="total_checkins", type="object", @OA\Property(property="total", type="integer")),
     *                         @OA\Property(property="avarage_daily_visits", type="object", @OA\Property(property="total", type="number", format="float")),
     *                         @OA\Property(property="growth_rate", type="object", @OA\Property(property="total", type="number", format="float"))
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="monthly_attendance",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\AdditionalProperties(type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="weekly_peach_hours",
     *                     type="object",
     *                     @OA\AdditionalProperties(type="integer")
     *                 ),
     *                 @OA\Property(
     *                     property="weekly_analysis",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="individual_checkins", type="integer"),
     *                         @OA\Property(property="corporate_checkins", type="integer"),
     *                         @OA\Property(property="total_checkins", type="integer"),
     *                         @OA\Property(property="daily_average", type="number", format="float"),
     *                         @OA\Property(property="growth_rate", type="number", format="float")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="corporate",
     *                 type="object",
     *                 @OA\Property(
     *                     property="performance_analysis",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="company_name", type="string"),
     *                         @OA\Property(property="total_members", type="integer"),
     *                         @OA\Property(property="total_checkins", type="integer"),
     *                         @OA\Property(property="total_active_members", type="integer"),
     *                         @OA\Property(property="utilization_rate", type="number", format="float"),
     *                         @OA\Property(property="monthly_revenue", type="number", format="float")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="companies_monthly_attendance",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="company_name", type="string"),
     *                         @OA\Property(property="total_checkins", type="integer"),
     *                         @OA\Property(property="total_active_members", type="integer"),
     *                         @OA\Property(property="utilization_rate", type="number", format="float")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="monthly_revenue_analysis",
     *                     type="object",
     *                     @OA\AdditionalProperties(type="number", format="float")
     *                 ),
     *                 @OA\Property(
     *                     property="payment_methods",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="total_revenue", type="number", format="float"),
     *                         @OA\Property(property="total_transactions", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="month_revenue_metrics",
     *                     type="object",
     *                     @OA\Property(property="total_revenue", type="number", format="float"),
     *                     @OA\Property(property="growth_rate", type="number", format="float"),
     *                     @OA\Property(property="peek_month", type="number", format="float"),
     *                     @OA\Property(property="lowest_month", type="number", format="float")
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="insights",
     *                 type="object",
     *                 @OA\Property(
     *                     property="branch_insights",
     *                     type="object",
     *                     @OA\AdditionalProperties(
     *                         type="object",
     *                         @OA\Property(property="total_individual_member_checkins", type="integer"),
     *                         @OA\Property(property="total_corporate_member_checkins", type="integer"),
     *                         @OA\Property(property="utilization_rate", type="number", format="float"),
     *                         @OA\Property(property="monthly_revenue", type="number", format="float")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
   public function index(Request $request)
    {
        $branchId = $request->branch_id;

        return response()->json([
            'overview'   => $this->getOverviewData($branchId),
            'attendance' => $this->getAttendanceData($branchId),
            'corporate'  => $this->getCorporateData($branchId),
            'insights'   => $this->getInsightsData($branchId),
        ]);
    }

    /* ======================================================
     | OVERVIEW
     ====================================================== */
    private function getOverviewData($branchId = null)
    {
        $year   = now()->year;
        $months = collect(range(1, 12))->mapWithKeys(function ($month) {
            return [strtolower(Carbon::create()->month($month)->format('F')) => $month];
        });

        /* ---------------- Monthly Performance ---------------- */
        $monthlyPerformance = $months->map(function ($monthNum, $monthName) use ($year, $branchId) {
            $start = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $end   = Carbon::create($year, $monthNum, 1)->endOfMonth();

            // Individual checkins with branch filter
            $individualCheckinsQuery = MemberSubscriptionCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$start, $end]);

            if ($branchId) {
                $individualCheckinsQuery->where('branch_id', $branchId);
            }
            $individualCheckins = $individualCheckinsQuery->count();

            // Corporate checkins with branch filter
            $corporateCheckinsQuery = CompanySubscriptionMemberCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$start, $end]);

            if ($branchId) {
                $corporateCheckinsQuery->where('branch_id', $branchId);
            }
            $corporateCheckins = $corporateCheckinsQuery->count();

            // Individual revenue with branch filter
            $individualRevenueQuery = MemberSubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year);

            if ($branchId) {
                $individualRevenueQuery->where('branch_id', $branchId);
            }
            $individualRevenue = $individualRevenueQuery->sum('amount_paid');

            // Corporate revenue with branch filter
            $corporateRevenueQuery = CompanySubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year);

            // Note: Company transactions might not have branch_id, adjust if needed
            if ($branchId) {
                $corporateRevenueQuery->where('branch_id', $branchId);
            }
            $corporateRevenue = $corporateRevenueQuery->sum('amount_paid');

            // Individual members with branch filter
            $individualMembersQuery = MemberSubscription::where(function($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            });

            if ($branchId) {
                $individualMembersQuery->where('branch_id', $branchId);
            }
            $individualMembers = $individualMembersQuery->count();

            // Corporate members with branch filter
            $corporateMembersQuery = CompanySubscriptionMember::whereHas(
                'company_subscription',
                function ($q) use ($start, $end, $branchId) {
                    $q->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);

                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                }
            );
            $corporateMembers = $corporateMembersQuery->count();

            // Expiring subscriptions with branch filter
            $individualExpiringQuery = MemberSubscription::whereBetween('end_date', [$start, $end]);
            if ($branchId) {
                $individualExpiringQuery->where('branch_id', $branchId);
            }
            $individualExpiring = $individualExpiringQuery->count();

            $corporateExpiringQuery = CompanySubscription::whereBetween('end_date', [$start, $end]);
            if ($branchId) {
                $corporateExpiringQuery->where('branch_id', $branchId);
            }
            $corporateExpiring = $corporateExpiringQuery->count();

            return [
                'individual_checkins' => $individualCheckins,
                'corporate_checkins'  => $corporateCheckins,
                'individual_revenue'  => $individualRevenue,
                'corporate_revenue'   => $corporateRevenue,
                'individual_members'  => $individualMembers,
                'corporate_members'   => $corporateMembers,
                'individual_expiring_soon' => $individualExpiring,
                'corporate_expiring_soon'  => $corporateExpiring,
                'total_checkins'      => $individualCheckins + $corporateCheckins,
                'total_revenue'       => $individualRevenue + $corporateRevenue,
                'total_members'       => $individualMembers + $corporateMembers,
                'total_expiring_soon' => $individualExpiring + $corporateExpiring,
            ];
        });

        /* ---------------- Payment Methods ---------------- */
        $paymentMethods = PaymentMethod::all()->mapWithKeys(function ($pm) use ($months, $year, $branchId) {
            return [
                $pm->name => $months->map(function ($monthNum) use ($pm, $year, $branchId) {
                    $individualQuery = MemberSubscriptionTransaction::where('status', 'completed')
                        ->where('payment_method_id', $pm->id)
                        ->whereMonth('created_at', $monthNum)
                        ->whereYear('created_at', $year);

                    if ($branchId) {
                        $individualQuery->where('branch_id', $branchId);
                    }
                    $individualCount = $individualQuery->count();

                    $corporateQuery = CompanySubscriptionTransaction::where('status', 'completed')
                        ->where('payment_method_id', $pm->id)
                        ->whereMonth('created_at', $monthNum)
                        ->whereYear('created_at', $year);

                    if ($branchId) {
                        $corporateQuery->where('branch_id', $branchId);
                    }
                    $corporateCount = $corporateQuery->count();

                    return $individualCount + $corporateCount;
                })
            ];
        });

        /* ---------------- Member Growth ---------------- */
        $memberGrowth = $months->map(function ($monthNum) use ($year, $branchId) {
            // New individual members with branch filter
            $indNewQuery = MemberSubscription::whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year);

            if ($branchId) {
                $indNewQuery->where('branch_id', $branchId);
            }
            $indNew = $indNewQuery->count();

            // New corporate members with branch filter
            $corpNew = CompanySubscriptionMember::whereHas(
                'company_subscription',
                function ($q) use ($monthNum, $year, $branchId) {
                    $q->whereMonth('created_at', $monthNum)
                      ->whereYear('created_at', $year);

                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                }
            )->count();

            // Churned individual members with branch filter
            $indChurnQuery = MemberSubscription::where('status', 'expired')
                ->whereMonth('updated_at', $monthNum)
                ->whereYear('updated_at', $year);

            if ($branchId) {
                $indChurnQuery->where('branch_id', $branchId);
            }
            $indChurn = $indChurnQuery->count();

            // Churned corporate members with branch filter
            $corpChurn = CompanySubscriptionMember::whereHas(
                'company_subscription',
                function ($q) use ($monthNum, $year, $branchId) {
                    $q->where('status', 'expired')
                        ->whereMonth('updated_at', $monthNum)
                        ->whereYear('updated_at', $year);

                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                }
            )->count();

            return [
                'corporate_new_members' => $corpNew,
                'individual_new_members' => $indNew,
                'corporate_churned_members' => $corpChurn,
                'individual_churned_members' => $indChurn,
                'total_company_and_individual_new_members' => $corpNew + $indNew,
                'total_company_and_individual_churned_members' => $corpChurn + $indChurn,
            ];
        });

        /* ---------------- Demographics ---------------- */
        $demographics = collect([
            ['label' => '18-25', 'min' => 18, 'max' => 25],
            ['label' => '26-35', 'min' => 26, 'max' => 35],
            ['label' => '36-45', 'min' => 36, 'max' => 45],
            ['label' => '65+',   'min' => 65, 'max' => 150],
        ])->mapWithKeys(function ($range, $index) use ($branchId) {
            $minDate = now()->subYears($range['max'] + 1);
            $maxDate = now()->subYears($range['min']);

            // Members query with branch filter if applicable
            $membersQuery = Member::whereBetween('date_of_birth', [$minDate, $maxDate]);

            // If you need to filter by branch for members, you might need to join with subscriptions
            if ($branchId) {
                $membersQuery->whereHas('subscriptions', function ($q) use ($branchId) {
                    $q->where('branch_id', $branchId);
                });
            }

            $members = $membersQuery;

            $male   = (clone $members)->where('gender', 'male')->count();
            $female = (clone $members)->where('gender', 'female')->count();
            $total  = $members->count();

            return [
                "age_range_" . ($index + 1) => [
                    'label' => $range['label'],
                    'total_male' => $male,
                    'total_female' => $female,
                    'total_other' => $total - ($male + $female),
                    'total' => $total,
                ]
            ];
        });

        return [
            'monthly_performance' => $monthlyPerformance,
            'payment_methods' => $paymentMethods,
            'member_growth' => $memberGrowth,
            'member_demographics' => $demographics,
        ];
    }

    /* ======================================================
     | CORPORATE
     ====================================================== */
    private function getCorporateData($branchId = null)
    {
        $companiesQuery = Company::whereHas('subscriptions', function ($q) use ($branchId) {
            $q->where('status', 'in_progress');

            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });

        $companies = $companiesQuery->get();

        $performance = $companies->mapWithKeys(function ($company) use ($branchId) {
            $subQuery = $company->subscriptions()->where('status', 'in_progress');

            if ($branchId) {
                $subQuery->where('branch_id', $branchId);
            }

            $sub = $subQuery->first();

            if (!$sub) {
                return [
                    $company->name => [
                        'company_name' => $company->name,
                        'total_members' => 0,
                        'total_checkins' => 0,
                        'total_active_members' => 0,
                        'utilization_rate' => 0,
                        'monthly_revenue' => 0,
                    ]
                ];
            }

            // Checkins with branch filter
            $checkinsQuery = CompanySubscriptionMemberCheckIn::where('status', 'completed')
                ->whereHas('company_subscription_member',
                    fn ($q) => $q->where('company_subscription_id', $sub->id)
                );

            if ($branchId) {
                $checkinsQuery->where('branch_id', $branchId);
            }

            $total = $checkinsQuery->count();
            $completed = $checkinsQuery->count();

            return [
                $company->name => [
                    'company_name' => $company->name,
                    'total_members' => $sub->company_subscription_members()->count(),
                    'total_checkins' => $completed,
                    'total_active_members' => $sub->company_subscription_members()
                        ->whereHas('member', fn ($q) => $q->where('status', 'active'))->count(),
                    'utilization_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                    'monthly_revenue' => CompanySubscriptionTransaction::where('status', 'completed')
                        ->where('company_subscription_id', $sub->id)
                        ->whereMonth('created_at', now()->month)
                        ->when($branchId, function ($query) use ($branchId) {
                            return $query->where('branch_id', $branchId);
                        })
                        ->sum('amount_paid'),
                ]
            ];
        });

        return [
            'performance_analysis' => $performance,
        ];
    }

    /* ======================================================
     | INSIGHTS
     ====================================================== */
    private function getInsightsData($branchId = null)
    {
        // If branch_id is provided, only get that branch, otherwise all branches
        $branches = $branchId ? Branch::where('id', $branchId)->get() : Branch::all();

        return [
            'branch_insights' => $branches->mapWithKeys(function ($branch) use ($branchId) {
                // Individual checkins for this branch
                $ind = MemberSubscriptionCheckIn::where('status', 'completed')
                    ->where('branch_id', $branch->id)->count();

                // Corporate checkins for this branch
                $corp = CompanySubscriptionMemberCheckIn::where('status', 'completed')
                    ->where('branch_id', $branch->id)->count();

                $total = MemberSubscriptionCheckIn::where('branch_id', $branch->id)->count();

                return [
                    $branch->name => [
                        'total_individual_member_checkins' => $ind,
                        'total_corporate_member_checkins' => $corp,
                        'utilization_rate' => $total > 0 ? round(($ind / $total) * 100, 2) : 0,
                        'monthly_revenue' => MemberSubscriptionTransaction::where('status', 'completed')
                            ->where('branch_id', $branch->id)
                            ->whereMonth('created_at', now()->month)
                            ->sum('amount_paid'),
                    ]
                ];
            })
        ];
    }

    /* ======================================================
     | ATTENDANCE - Added this missing method
     ====================================================== */
    private function getAttendanceData($branchId = null)
    {
        // You need to implement this method based on your requirements
        // Example implementation with branch filter:
        $today = now();
        $startOfMonth = $today->copy()->startOfMonth();
        $endOfMonth = $today->copy()->endOfMonth();

        // Individual attendance
        $individualAttendanceQuery = MemberSubscriptionCheckIn::where('status', 'completed')
            ->whereBetween('datetime', [$startOfMonth, $endOfMonth]);

        if ($branchId) {
            $individualAttendanceQuery->where('branch_id', $branchId);
        }

        // Corporate attendance
        $corporateAttendanceQuery = CompanySubscriptionMemberCheckIn::where('status', 'completed')
            ->whereBetween('datetime', [$startOfMonth, $endOfMonth]);

        if ($branchId) {
            $corporateAttendanceQuery->where('branch_id', $branchId);
        }

        return [
            'attendance_data' => [
                'individual_checkins_this_month' => $individualAttendanceQuery->count(),
                'corporate_checkins_this_month' => $corporateAttendanceQuery->count(),
                'total_checkins_this_month' => $individualAttendanceQuery->count() + $corporateAttendanceQuery->count(),
                // Add more attendance metrics as needed
            ],
        ];
    }
}
