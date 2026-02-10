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
    public function index(Request $request)
    {
        return response()->json([
            'overview'   => $this->getOverviewData(),
            'attendance' => $this->getAttendanceData(),
            'corporate'  => $this->getCorporateData(),
            'insights'   => $this->getInsightsData(),
        ]);
    }

    /* ======================================================
     | OVERVIEW
     ====================================================== */
    private function getOverviewData()
    {
        $year   = now()->year;
        $months = collect(range(1, 12))->mapWithKeys(function ($month) {
            return [strtolower(Carbon::create()->month($month)->format('F')) => $month];
        });

        /* ---------------- Monthly Performance ---------------- */
        $monthlyPerformance = $months->map(function ($monthNum, $monthName) use ($year) {
            $start = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $end   = Carbon::create($year, $monthNum, 1)->endOfMonth();

            $individualCheckins = MemberSubscriptionCheckIn::completed()
                ->whereBetween('datetime', [$start, $end])->count();

            $corporateCheckins = CompanySubscriptionMemberCheckIn::completed()
                ->whereBetween('datetime', [$start, $end])->count();

            $individualRevenue = MemberSubscriptionTransaction::completed()
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year)
                ->sum('amount_paid');

            $corporateRevenue = CompanySubscriptionTransaction::completed()
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year)
                ->sum('amount_paid');

            $individualMembers = MemberSubscription::activeWithin($start, $end)->count();

            $corporateMembers = CompanySubscriptionMember::whereHas(
                'company_subscription',
                fn ($q) => $q->activeWithin($start, $end)
            )->count();

            $individualExpiring = MemberSubscription::whereBetween('end_date', [$start, $end])->count();
            $corporateExpiring  = CompanySubscription::whereBetween('end_date', [$start, $end])->count();

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
        $paymentMethods = PaymentMethod::all()->mapWithKeys(function ($pm) use ($months, $year) {
            return [
                $pm->name => $months->map(function ($monthNum) use ($pm, $year) {
                    return MemberSubscriptionTransaction::completed()
                        ->where('payment_method_id', $pm->id)
                        ->whereMonth('created_at', $monthNum)
                        ->whereYear('created_at', $year)
                        ->count()
                        +
                        CompanySubscriptionTransaction::completed()
                        ->where('payment_method_id', $pm->id)
                        ->whereMonth('created_at', $monthNum)
                        ->whereYear('created_at', $year)
                        ->count();
                })
            ];
        });

        /* ---------------- Member Growth ---------------- */
        $memberGrowth = $months->map(function ($monthNum) use ($year) {
            $indNew = MemberSubscription::whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year)
                ->count();

            $corpNew = CompanySubscriptionMember::whereHas(
                'company_subscription',
                fn ($q) => $q->whereMonth('created_at', $monthNum)->whereYear('created_at', $year)
            )->count();

            $indChurn = MemberSubscription::where('status', 'expired')
                ->whereMonth('updated_at', $monthNum)
                ->whereYear('updated_at', $year)
                ->count();

            $corpChurn = CompanySubscriptionMember::whereHas(
                'company_subscription',
                fn ($q) => $q->where('status', 'expired')
                    ->whereMonth('updated_at', $monthNum)
                    ->whereYear('updated_at', $year)
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
        ])->mapWithKeys(function ($range, $index) {
            $minDate = now()->subYears($range['max'] + 1);
            $maxDate = now()->subYears($range['min']);

            $members = Member::whereBetween('date_of_birth', [$minDate, $maxDate]);

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
            'mothly_performance' => $monthlyPerformance,
            'payment_methods' => $paymentMethods,
            'member_growth' => $memberGrowth,
            'member_demographics' => $demographics,
        ];
    }

    /* ======================================================
     | CORPORATE
     ====================================================== */
    private function getCorporateData()
    {
        $companies = Company::whereHas('subscriptions', fn ($q) => $q->where('status', 'in_progress'))->get();

        $performance = $companies->mapWithKeys(function ($company) {
            $sub = $company->subscriptions()->where('status', 'in_progress')->first();

            $checkins = CompanySubscriptionMemberCheckIn::completed()
                ->whereHas('company_subscription_member',
                    fn ($q) => $q->where('company_subscription_id', $sub->id)
                );

            $total = $checkins->count();
            $completed = $checkins->count();

            return [
                $company->name => [
                    'company_name' => $company->name,
                    'total_members' => $sub->company_subscription_members()->count(),
                    'total_checkins' => $completed,
                    'total_active_members' => $sub->company_subscription_members()
                        ->whereHas('member', fn ($q) => $q->where('status', 'active'))->count(),
                    'utilization_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
                    'monthly_revenue' => CompanySubscriptionTransaction::completed()
                        ->where('company_subscription_id', $sub->id)
                        ->whereMonth('created_at', now()->month)
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
    private function getInsightsData()
    {
        $branches = Branch::all();

        return [
            'branch_insights' => $branches->mapWithKeys(function ($branch) {
                $ind = MemberSubscriptionCheckIn::completed()
                    ->where('branch_id', $branch->id)->count();

                $corp = CompanySubscriptionMemberCheckIn::completed()
                    ->where('branch_id', $branch->id)->count();

                $total = MemberSubscriptionCheckIn::where('branch_id', $branch->id)->count();

                return [
                    $branch->name => [
                        'total_individual_member_checkins' => $ind,
                        'total_corporate_member_checkins' => $corp,
                        'utilization_rate' => $total > 0 ? round(($ind / $total) * 100, 2) : 0,
                        'monthly_revenue' => MemberSubscriptionTransaction::completed()
                            ->where('branch_id', $branch->id)
                            ->whereMonth('created_at', now()->month)
                            ->sum('amount_paid'),
                    ]
                ];
            })
        ];
    }
}
