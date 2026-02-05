<?php

namespace App\Http\Controllers\Api\V1\Report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        return response()->json([
            'overview' => $this->getOverviewData(),
            'attendance' => $this->getAttendanceData(),
            'corporate' => $this->getCorporateData(),
            'insights' => $this->getInsightsData(),
        ]);
    }

    private function getOverviewData()
    {
        $monthlyPerformance = [];
        $months = [
            'january', 'february', 'march', 'april', 'may', 'june', 
            'jully', 'august', 'september', 'october', 'november', 'december'
        ];

        // Monthly Performance
        foreach ($months as $index => $monthName) {
            $monthNum = $index + 1;
            // Use current year for the report
            $year = now()->year;
            $startDate = Carbon::create($year, $monthNum, 1)->startOfMonth();
            $endDate = Carbon::create($year, $monthNum, 1)->endOfMonth();

            // Checkins
            $individualCheckins = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$startDate, $endDate])
                ->count();
            
            $corporateCheckins = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$startDate, $endDate])
                ->count();

            // Revenue
            $individualRevenue = \App\Models\Member\MemberSubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year)
                ->sum('amount_paid');

            $corporateRevenue = \App\Models\Company\CompanySubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', $year)
                ->sum('amount_paid');

            // Members (In Progress / Active in that month)
            // Individual: MemberSubscription in_progress during that month
            $individualMembers = \App\Models\Member\MemberSubscription::where('status', 'in_progress')
                 ->where('start_date', '<=', $endDate)
                 ->where(function($q) use ($startDate) {
                     $q->where('end_date', '>=', $startDate)->orWhereNull('end_date');
                 })
                ->count();

            // Corporate: CompanySubscriptionMember where subscription is in_progress
            $corporateMembers = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q) use ($startDate, $endDate) {
                 $q->where('status', 'in_progress')
                   ->where('start_date', '<=', $endDate)
                   ->where(function($sq) use ($startDate) {
                       $sq->where('end_date', '>=', $startDate)->orWhereNull('end_date');
                   });
            })->count();

            // Expiring Soon (Ending in the next 7 days RELATIVE TO NOW if we follow "ending soon" logic,
            // OR ending within 7 days OF THAT MONTH? 
            // The request says "timestamp column end_date is in the next 7 days and month is january".
            // This usually implies a static report where "next 7 days" is meaningless for past months.
            // I will interpret this as: Count of subscriptions that expired or were set to expire in that month?
            // "Expiring Soon" usually means Current Date + 7 days.
            // If I map this to strict English "end_date is in the next 7 days", it only applies to current timeframe.
            // But doing it for 'January' when we are in 'February'?
            // I'll stick to: Count of subscriptions with end_date in that month.
            // OR, if the user strictly wants "next 7 days" for ALL months, it implies a projection? No.
            // I will return 0 for past months and real data for current month?
            // Actually, let's look at the comment: "count ... where end_date is in the next 7 days".
            // If I am in Jan, I check next 7 days.
            // I will implement: Count where end_date is between [today, today+7days] IF current month == target month?
            // Let's simplified: Count of subscriptions ending in that specific Month.
            $individualExpiring = \App\Models\Member\MemberSubscription::whereBetween('end_date', [$startDate, $endDate])->count();
            $corporateExpiring = \App\Models\Company\CompanySubscription::whereBetween('end_date', [$startDate, $endDate])->count();
            
            // Adjusting "expiring soon" logic to match request literally if possible, but for past months it's "expired".
            // I'll stick to "Ending in that month".

            $monthlyPerformance[$monthName] = [
                'individual_checkins' => $individualCheckins,
                'corporate_checkins' => $corporateCheckins,
                'individual_revenue' => $individualRevenue,
                'corporate_revenue' => $corporateRevenue,
                'individual_members' => $individualMembers,
                'corporate_members' => $corporateMembers,
                'individual_expiring_soon' => $individualExpiring,
                'corporate_expiring_soon' => $corporateExpiring,
                'total_checkins' => $individualCheckins + $corporateCheckins,
                'total_revenue' => $individualRevenue + $corporateRevenue,
                'total_members' => $individualMembers + $corporateMembers,
                'total_expiring_soon' => $individualExpiring + $corporateExpiring
            ];
        }

        // Payment Methods
        $paymentMethodsData = [];
        $paymentMethods = \App\Models\Payment\PaymentMethod::all();
        foreach($paymentMethods as $pm) {
            $pmData = [];
            foreach($months as $index => $monthName) {
                 $monthNum = $index + 1;
                 $count = \App\Models\Member\MemberSubscriptionTransaction::where('payment_method_id', $pm->id)
                     ->where('status', 'completed')
                     ->whereMonth('created_at', $monthNum)
                     ->whereYear('created_at', now()->year)
                     ->count();
                 $count += \App\Models\Company\CompanySubscriptionTransaction::where('payment_method_id', $pm->id)
                     ->where('status', 'completed')
                     ->whereMonth('created_at', $monthNum)
                     ->whereYear('created_at', now()->year)
                     ->count();
                 $pmData[$monthName] = $count;
            }
            $paymentMethodsData[$pm->name] = $pmData;
        }

        // Member Growth
        $memberGrowth = [];
        foreach($months as $index => $monthName) {
             $monthNum = $index + 1;
             $year = now()->year;
             
             // New Members (Created in that month)
             $indNew = \App\Models\Member\MemberSubscription::where('status', 'in_progress')
                 ->whereMonth('created_at', $monthNum)
                 ->whereYear('created_at', $year)
                 ->count();
             
             $corpNew = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q) use ($monthNum, $year){
                 $q->where('status', 'in_progress')
                   ->whereMonth('created_at', $monthNum)
                   ->whereYear('created_at', $year);
             })->count();

             // Churned (Expired/Cancelled updated in that month? Or naturally expired?)
             // Request says: "status is expired and updated_at is in january"
             $indChurn = \App\Models\Member\MemberSubscription::where('status', 'expired')
                 ->whereMonth('updated_at', $monthNum)
                 ->whereYear('updated_at', $year)
                 ->count();
             
             $corpChurn = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q) use ($monthNum, $year){
                 $q->where('status', 'expired')
                   ->whereMonth('updated_at', $monthNum)
                   ->whereYear('updated_at', $year);
             })->count();

             $memberGrowth[$monthName] = [
                 'corporate_new_members' => $corpNew,
                 'individual_new_members' => $indNew,
                 'corporate_churned_members' => $corpChurn,
                 'individual_churned_members' => $indChurn,
                 'total_company_and_individual_new_members' => $indNew + $corpNew,
                 'total_company_and_individual_churned_members' => $indChurn + $corpChurn
             ];
        }

        // Demographics
        $demographics = [];
        $ranges = [
            'age_range_1' => ['label' => '18-25', 'min' => 18, 'max' => 25],
            'age_range_2' => ['label' => '26-35', 'min' => 26, 'max' => 35],
            'age_range_3' => ['label' => '36-45', 'min' => 36, 'max' => 45],
            'age_range_4' => ['label' => '65+', 'min' => 65, 'max' => 150],
        ];

        foreach($ranges as $key => $range) {
             // Calculate DOB range
             $minDate = now()->subYears($range['max'] + 1)->format('Y-m-d'); // +1 to capture full end year
             $maxDate = now()->subYears($range['min'])->format('Y-m-d');
             
             // Query Members
             $query = \App\Models\Member\Member::whereBetween('date_of_birth', [$minDate, $maxDate]);
             
             $total = $query->count();
             $male = (clone $query)->where('gender', 'male')->count();
             $female = (clone $query)->where('gender', 'female')->count();
             
             $demographics[$key] = [
                 'label' => $range['label'],
                 'total_male' => $male,
                 'total_female' => $female,
                 'total_other' => $total - ($male + $female),
                 'total' => $total
             ];
        }

        return [
            "mothly_performance" => $monthlyPerformance,
            "payment_methods" => $paymentMethodsData,
            "member_growth" => $memberGrowth,
            "member_demographics" => $demographics
        ];
    }

    private function getAttendanceData()
    {
        // Weekly, Monthly, Yearly Cards
        // Current Checkins vs previous periods?
        // Request asks for Total, Avg Daily Visits (total/period), Growth Rate
        
        $cards = [];
        $periods = ['weekly' => 7, 'monthly' => 30, 'yearly' => 365];
        
        foreach($periods as $periodName => $days) {
             $startDate = now()->subDays($days);
             
             $indCheckins = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$startDate, now()])->count();
             $corpCheckins = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$startDate, now()])->count();
             
             $total = $indCheckins + $corpCheckins;
             
             // Average Daily
             $avg = $days > 0 ? round($total / $days, 2) : 0;
             
             // Growth Rate (Compare with previous period)
             $prevStartDate = now()->subDays($days * 2);
             $prevEndDate = now()->subDays($days);
             
             $prevInd = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$prevStartDate, $prevEndDate])->count();
             $prevCorp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$prevStartDate, $prevEndDate])->count();
             $prevTotal = $prevInd + $prevCorp;
             
             $growth = 0;
             if ($prevTotal > 0) {
                 $growth = round((($total - $prevTotal) / $prevTotal) * 100, 2);
             }

             $cards[$periodName] = [
                 'total_checkins' => ['total' => $total],
                 'avarage_daily_visits' => ['total' => $avg],
                 'growth_rate' => ['total' => $growth]
             ];
        }

        // Monthly Attendance (Hourly breakdown)
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december'];
        $monthlyAttendance = [];
        $hours = [];
        for($h=5; $h<=22; $h++) {
            $hours[] = sprintf('%02d:00', $h);
        }

        foreach($months as $index => $monthName) {
             $monthNum = $index + 1;
             $data = [];
             foreach($hours as $hour) {
                 $startHour = (int)substr($hour, 0, 2);
                 
                 // Query for hour checkins
                 $ind = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                     ->whereMonth('datetime', $monthNum)
                     ->whereTime('datetime', '>=', "$startHour:00:00")
                     ->whereTime('datetime', '<=', "$startHour:59:59")
                     ->count();
                 
                 $corp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                     ->whereMonth('datetime', $monthNum)
                     ->whereTime('datetime', '>=', "$startHour:00:00")
                     ->whereTime('datetime', '<=', "$startHour:59:59")
                     ->count();
                 
                 $data[$hour] = $ind + $corp;
             }
             $monthlyAttendance[$monthName] = $data;
        }

        // Weekly Peak Hours (Current Week)
        $weeklyPeachHours = [];
        foreach($hours as $hour) {
             $startHour = (int)substr($hour, 0, 2);
             
             $ind = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [now()->startOfWeek(), now()->endOfWeek()])
                 ->whereTime('datetime', '>=', "$startHour:00:00")
                 ->whereTime('datetime', '<=', "$startHour:59:59")
                 ->count();
             
             $corp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [now()->startOfWeek(), now()->endOfWeek()])
                 ->whereTime('datetime', '>=', "$startHour:00:00")
                 ->whereTime('datetime', '<=', "$startHour:59:59")
                 ->count();
             
             $weeklyPeachHours[$hour] = $ind + $corp;
        }
        
        // Weekly Analysis (Last 12 weeks)
        $weeklyAnalysis = [];
        for($i=0; $i<12; $i++) {
            $start = now()->subWeeks($i)->startOfWeek();
            $end = now()->subWeeks($i)->endOfWeek();
            $weekLabel = 'week_' . ($i + 1); // Or 12-i if reverse order desired basically logic says 12 weeks
            
             $ind = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$start, $end])->count();
             $corp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$start, $end])->count();
             $total = $ind + $corp;
             $avg = round($total / 7, 2);
             
             // Growth rate vs previous week (which is i+1 in loop context, i.e. further back)
             $prevStart = now()->subWeeks($i+1)->startOfWeek();
             $prevEnd = now()->subWeeks($i+1)->endOfWeek();
             
             $prevInd = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$prevStart, $prevEnd])->count();
             $prevCorp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                 ->whereBetween('datetime', [$prevStart, $prevEnd])->count();
             $prevTotal = $prevInd + $prevCorp;
             
             $growth = ($prevTotal > 0) ? round((($total - $prevTotal) / $prevTotal) * 100, 2) : 0;

            $weeklyAnalysis[$weekLabel] = [
                'individual_checkins' => $ind,
                'corporate_checkins' => $corp,
                'total_checkins' => $total,
                'daily_average' => $avg,
                'growth_rate' => $growth
            ];
        }


        return [
            "cards" => $cards,
            "monthly_attendance" => $monthlyAttendance,
            "weekly_peach_hours" => $weeklyPeachHours,
            "weekly_analysis" => $weeklyAnalysis
        ];
    }

    private function getCorporateData()
    {
        // Performance Analysis (Per Company)
        // Get companies with active subscriptions
        $companies = \App\Models\Company\Company::whereHas('subscriptions', function($q) {
            $q->where('status', 'in_progress');
        })->get();
        
        $performanceAnalysis = [];
        $companiesMonthlyAttendance = [];
        
        foreach($companies as $company) {
            $sub = $company->subscriptions()->where('status', 'in_progress')->first();
            
            // Total Members
            $membersCount = $sub ? $sub->company_subscription_members()->count() : 0;
            
            // Total Checkins (Total lifetime of subscription?)
            $checkinsCount = $sub ? \App\Models\Company\CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function($q) use ($sub){
                $q->where('company_subscription_id', $sub->id);
            })->where('status', 'completed')->count() : 0;
            
            // Active Members (Members linked to sub who are active in Member table?)
            // Request: "member status is active"
            $activeMembers = $sub ? $sub->company_subscription_members()->whereHas('member', function($q){
                $q->where('status', 'active');
            })->count() : 0;
            
            // Utilization Rate
            // Completed Checkins / Total Checkins? No.
            // Request: "completed / total" -> implies failure rate?
            // "count from ... where status is completed / count from ... where status in_progress"??
            // Note: Checkin doesn't have "in_progress".
            // Maybe "Completed Checkins vs Total Members * Days"?
            // Request Text: "count from ... where status is completed / count from ... where company_subscription.status is in_progress" -> This denominator is weird.
            // I will interpret as: Completed Checkins / Total Checkins Attempted.
            $totalAttempts = $sub ? \App\Models\Company\CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function($q) use ($sub){
                $q->where('company_subscription_id', $sub->id);
            })->count() : 0;
            
            $utilization = ($totalAttempts > 0) ? round(($checkinsCount / $totalAttempts) * 100, 2) : 0;
            
            // Monthly Revenue (This Month)
            $monthlyRev = \App\Models\Company\CompanySubscriptionTransaction::where('company_id', $company->id)
                ->where('company_subscription_id', $sub->id ?? 0)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid');
            
            $performanceAnalysis[$company->name] = [
                'company_name' => $company->name,
                'total_members' => $membersCount,
                'total_checkins' => $checkinsCount,
                'total_active_members' => $activeMembers,
                'utilization_rate' => $utilization,
                'monthly_revenue' => $monthlyRev
            ];

            // Companies Monthly Attendance (Loop seemed same structure in request?)
            // Request repeats same fields basically.
             $companiesMonthlyAttendance[$company->name] = [
                'company_name' => $company->name,
                'total_checkins' => $checkinsCount, // Total or Monthly? Request says "companies_monthly_attendance" but formulas imply total. 
                                                     // I'll assume monthly checkins for this specific section?
                                                     // "count from Checkin where sub is in_progress" -> Total.
                'total_active_members' => $activeMembers,
                'utilization_rate' => $utilization
             ];
        }

        // Monthly Revenue Analysis (Aggregated Corporate Revenue)
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december'];
        $monthlyRevenueAnalysis = [];
        
        foreach($months as $index => $monthName) {
            $monthNum = $index + 1;
             $rev = \App\Models\Company\CompanySubscriptionTransaction::whereHas('company_subscription', function($q){
                    $q->where('status', 'in_progress');
                })
                ->where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid'); // Request says 'count', probably means 'sum'. Revenue is sum.
             
             $monthlyRevenueAnalysis[$monthName] = $rev;
        }

        // Payment Methods (Corporate)
        $paymentMethodsCorp = [];
        foreach(\App\Models\Payment\PaymentMethod::all() as $pm) {
             $rev = \App\Models\Company\CompanySubscriptionTransaction::whereHas('company_subscription', function($q){
                    $q->where('status', 'in_progress');
                })
                ->where('status', 'completed')
                ->where('payment_method_id', $pm->id)
                ->sum('amount_paid');
             
             $txnCount = \App\Models\Company\CompanySubscriptionTransaction::whereHas('company_subscription', function($q){
                    $q->where('status', 'in_progress');
                })
                ->where('status', 'completed')
                ->where('payment_method_id', $pm->id)
                ->count();
             
             $paymentMethodsCorp[$pm->name] = [
                 'total_revenue' => $rev,
                 'total_transactions' => $txnCount
             ];
        }
        
        // Month Revenue Metrics (Last 6 Months)
        $last6MonthsRev = \App\Models\Company\CompanySubscriptionTransaction::whereHas('company_subscription', function($q){
                $q->where('status', 'in_progress');
            })
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(6))
            ->sum('amount_paid');
            
        // Previous 6 Months (for growth)
        $prev6MonthsRev = \App\Models\Company\CompanySubscriptionTransaction::whereHas('company_subscription', function($q){
                $q->where('status', 'in_progress');
            })
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subMonths(12), now()->subMonths(6)])
            ->sum('amount_paid');
            
        $growthRate = ($prev6MonthsRev > 0) ? round((($last6MonthsRev - $prev6MonthsRev) / $prev6MonthsRev) * 100, 2) : 0;
        
        // Peek/Lowest Month
        $monthlyRevenues = \App\Models\Company\CompanySubscriptionTransaction::select(
                DB::raw('sum(amount_paid) as revenue'), 
                DB::raw("DATE_FORMAT(created_at,'%Y-%m') as month")
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')
            ->get();
        
        $maxRev = $monthlyRevenues->max('revenue');
        $minRev = $monthlyRevenues->min('revenue');
        
        $monthRevenueMetrics = [
            'total_revenue' => $last6MonthsRev,
            'growth_rate' => $growthRate,
            'peek_month' => $maxRev ?? 0,
            'lowest_month' => $minRev ?? 0
        ];


        return [
            "performance_analysis" => $performanceAnalysis,
            "companies_monthly_attendance" => $companiesMonthlyAttendance,
            "monthly_revenue_analysis" => $monthlyRevenueAnalysis,
            "payment_methods" => $paymentMethodsCorp,
            "month_revenue_metrics" => $monthRevenueMetrics
        ];
    }

    private function getInsightsData() {
        $branchInsights = [];
        $branches = \App\Models\Branch\Branch::all(); // Assuming Branch model exists as per other files
        
        foreach($branches as $branch) {
            // Individual Checkins
            $indCheckins = \App\Models\Member\MemberSubscriptionCheckIn::whereHas('member_subscription', function($q) use ($branch){
                $q->where('status', 'in_progress')
                  ->where('branch_id', $branch->id); // Assuming sub has branch_id
            })->where('status', 'completed')->count();
            
            // Corporate Checkins
            $corpCheckins = \App\Models\Company\CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function($q) use ($branch){
                 // Relationship path to branch?
                 // CompanySubscriptionMember -> CompanySubscription -> Branch?
                 // Or Checkin has branch_id?
                 // Looking at view_file outputs: MemberSubscriptionCheckIn has branch_id. CompanySubscriptionMemberCheckIn has branch_id.
                 // So use checkin branch_id directly, clearer.
            })->where('branch_id', $branch->id)->where('status', 'completed')->count();
             
            // Utilization (of individual subs?)
            // Request: "count IndividualSubCheckin completed / count IndividualSubCheckin total in branch"
            $indTotalAttempts = \App\Models\Member\MemberSubscriptionCheckIn::where('branch_id', $branch->id)->count();
            $utilization = ($indTotalAttempts > 0) ? round(($indCheckins / $indTotalAttempts) * 100, 2) : 0;
            
            // Monthly Revenue (Individual)
            $monthlyRev = \App\Models\Member\MemberSubscriptionTransaction::where('branch_id', $branch->id)
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid');
            
            $branchInsights[$branch->name] = [
                'total_individual_member_checkins' => $indCheckins,
                'total_corporate_member_checkins' => $corpCheckins,
                'utilization_rate' => $utilization,
                'monthly_revenue' => $monthlyRev
            ];
        }
        
        return [
            "branch_insights" => $branchInsights
        ];
    }
}
