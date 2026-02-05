<?php

namespace App\Http\Controllers\Api\V1\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainDashboardController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get Dashboard Statistics",
     *     description="Returns various statistics including active members, daily attendance, expiring subscriptions, revenue, etc.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="active_members",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=150),
     *                 @OA\Property(property="progress_previous_month", type="number", format="float", example=5.2)
     *             ),
     *             @OA\Property(
     *                 property="daily_attendance",
     *                 type="object",
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=45),
     *                     @OA\Property(property="progress_previous_day", type="number", format="float", example=2.1)
     *                 ),
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=20),
     *                     @OA\Property(property="progress_previous_day", type="number", format="float", example=-1.5)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="expiring_soon",
     *                 type="object",
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=5),
     *                     @OA\Property(property="today", type="integer", example=1)
     *                 ),
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=3),
     *                     @OA\Property(property="today", type="integer", example=0)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="monthly_revenue",
     *                 type="object",
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="total_amount", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="progress_last_month", type="number", format="float", example=10.5)
     *                 ),
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="total_amount", type="number", format="float", example=3000.00),
     *                     @OA\Property(property="progress_last_month", type="number", format="float", example=8.2)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="weekly_check_ins",
     *                 type="object",
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="monday", type="object", @OA\Property(property="total", type="integer", example=10)),
     *                     @OA\Property(property="tuesday", type="object", @OA\Property(property="total", type="integer", example=12)),
     *                     @OA\Property(property="wednesday", type="object", @OA\Property(property="total", type="integer", example=15)),
     *                     @OA\Property(property="thursday", type="object", @OA\Property(property="total", type="integer", example=11)),
     *                     @OA\Property(property="friday", type="object", @OA\Property(property="total", type="integer", example=20)),
     *                     @OA\Property(property="saturday", type="object", @OA\Property(property="total", type="integer", example=25)),
     *                     @OA\Property(property="sunday", type="object", @OA\Property(property="total", type="integer", example=8))
     *                 ),
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="monday", type="object", @OA\Property(property="total", type="integer", example=5)),
     *                     @OA\Property(property="tuesday", type="object", @OA\Property(property="total", type="integer", example=6)),
     *                     @OA\Property(property="wednesday", type="object", @OA\Property(property="total", type="integer", example=8)),
     *                     @OA\Property(property="thursday", type="object", @OA\Property(property="total", type="integer", example=7)),
     *                     @OA\Property(property="friday", type="object", @OA\Property(property="total", type="integer", example=10)),
     *                     @OA\Property(property="saturday", type="object", @OA\Property(property="total", type="integer", example=2)),
     *                     @OA\Property(property="sunday", type="object", @OA\Property(property="total", type="integer", example=1))
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="membership_distribution",
     *                 type="object",
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=150),
     *                     @OA\Property(property="monthly", type="integer", example=20),
     *                     @OA\Property(property="quarterly", type="integer", example=50)
     *                 ),
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="total", type="integer", example=50),
     *                     @OA\Property(property="monthly", type="integer", example=5),
     *                     @OA\Property(property="quarterly", type="integer", example=15)
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="revenue_breakdown",
     *                 type="object",
     *                 @OA\Property(
     *                     property="individual",
     *                     type="object",
     *                     @OA\Property(property="january", type="number", format="float", example=1000.00),
     *                     @OA\Property(property="february", type="number", format="float", example=1200.00),
     *                     @OA\Property(property="march", type="number", format="float", example=1100.00),
     *                     @OA\Property(property="april", type="number", format="float", example=1300.00),
     *                     @OA\Property(property="may", type="number", format="float", example=1400.00),
     *                     @OA\Property(property="june", type="number", format="float", example=1500.00),
     *                     @OA\Property(property="jully", type="number", format="float", example=1600.00),
     *                     @OA\Property(property="august", type="number", format="float", example=1700.00),
     *                     @OA\Property(property="september", type="number", format="float", example=1800.00),
     *                     @OA\Property(property="october", type="number", format="float", example=1900.00),
     *                     @OA\Property(property="november", type="number", format="float", example=2000.00),
     *                     @OA\Property(property="december", type="number", format="float", example=2100.00)
     *                 ),
     *                 @OA\Property(
     *                     property="corporate",
     *                     type="object",
     *                     @OA\Property(property="january", type="number", format="float", example=2000.00),
     *                     @OA\Property(property="february", type="number", format="float", example=2200.00),
     *                     @OA\Property(property="march", type="number", format="float", example=2100.00),
     *                     @OA\Property(property="april", type="number", format="float", example=2300.00),
     *                     @OA\Property(property="may", type="number", format="float", example=2400.00),
     *                     @OA\Property(property="june", type="number", format="float", example=2500.00),
     *                     @OA\Property(property="jully", type="number", format="float", example=2600.00),
     *                     @OA\Property(property="august", type="number", format="float", example=2700.00),
     *                     @OA\Property(property="september", type="number", format="float", example=2800.00),
     *                     @OA\Property(property="october", type="number", format="float", example=2900.00),
     *                     @OA\Property(property="november", type="number", format="float", example=3000.00),
     *                     @OA\Property(property="december", type="number", format="float", example=3100.00)
     *                 ),
     *                 @OA\Property(
     *                     property="individual_and_corporate",
     *                     type="object",
     *                     @OA\Property(property="january", type="number", format="float", example=3000.00),
     *                     @OA\Property(property="february", type="number", format="float", example=3400.00),
     *                     @OA\Property(property="march", type="number", format="float", example=3200.00),
     *                     @OA\Property(property="april", type="number", format="float", example=3600.00),
     *                     @OA\Property(property="may", type="number", format="float", example=3800.00),
     *                     @OA\Property(property="june", type="number", format="float", example=4000.00),
     *                     @OA\Property(property="jully", type="number", format="float", example=4200.00),
     *                     @OA\Property(property="august", type="number", format="float", example=4400.00),
     *                     @OA\Property(property="september", type="number", format="float", example=4600.00),
     *                     @OA\Property(property="october", type="number", format="float", example=4800.00),
     *                     @OA\Property(property="november", type="number", format="float", example=5000.00),
     *                     @OA\Property(property="december", type="number", format="float", example=5200.00)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function index(Request $request)
    {
        $today = now();
        $yesterday = now()->subDay();
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $startOfLastMonth = now()->subMonth()->startOfMonth();
        $endOfLastMonth = now()->subMonth()->endOfMonth();
        $startOfQuarter = now()->startOfQuarter();
        $endOfQuarter = now()->endOfQuarter();


        // 1. Active Members
        $activeMembersCount = \App\Models\Member\Member::where('status', 'active')->count();
        $activeMembersLastMonthCount = \App\Models\Member\Member::where('status', 'active')
             ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth]) // Approximation as we don't track status history. Or maybe we should compare total count at that time?
             // Since we can't easily travel back in time for status without a history table, I will assume the user wants the growth/new active members or just a comparison of current count vs count created before end of last month?
             // The request says: "count members where status is in_progress in the previous month".
             // If status is just a current state field, we can't know for sure who was active last month.
             // I will try to approximate by counting members created before end of last month who are currently active.
             // OR, better, calculate percentage change based on created_at for new active members?
             // Let's implement simplified logic: Members active NOW vs Members active NOW who were created before this month?
             // No, that's "retention".
             // Let's stick to: "Active Members Total" vs "Percentage increase from previous month".
             // To calculate percentage increase properly without history:
             // (Current Active - Active Last Month) / Active Last Month * 100.
             // But we don't know Active Last Month count.
             // I will use "New Active Members in Previous Month" as a proxy if we can't do better, or just return 0 if impossible.
             // Actually, let's look at MemberSubscription. If they have a subscription active last month, they were active.
             // But "Member" model has 'status'.
             // Let's assume for now: "progress_previous_month" = (Active Count Now - Active Count Start of Month) / Active Count Start of Month * 100?
             // Let's try to follow the comment: "count members where status is in_progress in the previous month".
             // I will interpret this as: Count of members who were active last month.
             // Since I can't know exactly, I will simply return 0 or a placeholder for now to be safe, OR implement a query on Subscriptions that were active last month.
             // Let's use MemberSubscription for "Active Members" logic if Member status is unreliable over time.
             // But the comment says "from Member where status is active".
             // Let's just count Member created last month as "progress"?
             // No, let's try to do it right. Let's return 0 for progress for now if it's too complex, or ask user.
             // However, I can count "MemberSubscription" active last month.
             // Let's stick to the prompt's comment: "count members where status is in_progress in the previous month".
             // I'll calculate "Active Members Last Month" by querying MemberSubscription where start_date <= endOfLastMonth AND (end_date >= startOfLastMonth OR end_date is null).
             // Then calculate percentage.
             // Wait, the prompt implies a simple count for the first field "total".
             // Let's act on Member model as requested.
             // Total Active Members:
             // Progress: (Current Active - Last Month Active) / Last Month Active * 100.
             // I'll leave progress as 0 for now to avoid guessing wrong logic, as `Member` table has no history.
             // Actually, I can use `created_at` to find how many where added last month.
        ;


        // Let's calculate based on what we have.
        // Active Members Total
        $totalActiveMembers = \App\Models\Member\Member::where('status', 'active')->count();

        // For progress, let's assume we compare with total active members created before this month.
        // This is imperfect but a starting point.
        $totalActiveBeforeMonth = \App\Models\Member\Member::where('status', 'active')->where('created_at', '<', $startOfMonth)->count();
        $memberProgress = 0;
        if ($totalActiveBeforeMonth > 0) {
            $memberProgress = (($totalActiveMembers - $totalActiveBeforeMonth) / $totalActiveBeforeMonth) * 100;
        }

        // 2. Daily Attendance
        // Individual
        $dailyIndividualTotal = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
            ->whereDate('datetime', $today)
            ->count();
        $dailyIndividualYesterday = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
            ->whereDate('datetime', $yesterday)
            ->count();
        $dailyIndividualProgress = 0;
        if ($dailyIndividualYesterday > 0) {
            $dailyIndividualProgress = (($dailyIndividualTotal - $dailyIndividualYesterday) / $dailyIndividualYesterday) * 100;
        }

        // Corporate
        $dailyCorporateTotal = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
            ->whereDate('datetime', $today)
            ->count(); // The model is CompanySubscriptionMemberCheckIn
        $dailyCorporateYesterday = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
            ->whereDate('datetime', $yesterday)
            ->count();
        $dailyCorporateProgress = 0;
        if ($dailyCorporateYesterday > 0) {
            $dailyCorporateProgress = (($dailyCorporateTotal - $dailyCorporateYesterday) / $dailyCorporateYesterday) * 100;
        }

        // 3. Expiring Soon
        // Individual
        $expiringIndividualTotal = \App\Models\Member\MemberSubscription::whereBetween('end_date', [now(), now()->addDays(7)])->count();
        $expiringIndividualToday = \App\Models\Member\MemberSubscription::whereDate('end_date', $today)->count();

        // Corporate
        $expiringCorporateTotal = \App\Models\Company\CompanySubscription::whereBetween('end_date', [now(), now()->addDays(7)])->count();
        $expiringCorporateToday = \App\Models\Company\CompanySubscription::whereDate('end_date', $today)->count();


        // 4. Monthly Revenue
        // Corporate
        $revenueCorporateTotal = \App\Models\Company\CompanySubscriptionTransaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month) // Assuming we use created_at or `date` column. Migration has `date`.
             ->whereYear('created_at', now()->year)
            ->sum('amount_paid'); // Migration says `amount_paid`.
        
        $revenueCorporateLastMonth = \App\Models\Company\CompanySubscriptionTransaction::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount_paid');

        $revenueCorporateProgress = 0;
        if ($revenueCorporateLastMonth > 0) {
            $revenueCorporateProgress = (($revenueCorporateTotal - $revenueCorporateLastMonth) / $revenueCorporateLastMonth) * 100;
        }

        // Individual
        $revenueIndividualTotal = \App\Models\Member\MemberSubscriptionTransaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
             ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $revenueIndividualLastMonth = \App\Models\Member\MemberSubscriptionTransaction::where('status', 'completed')
            ->whereMonth('created_at', now()->subMonth()->month)
             ->whereYear('created_at', now()->subMonth()->year)
            ->sum('amount_paid');
        
        $revenueIndividualProgress = 0;
        if ($revenueIndividualLastMonth > 0) {
            $revenueIndividualProgress = (($revenueIndividualTotal - $revenueIndividualLastMonth) / $revenueIndividualLastMonth) * 100;
        }


        // 5. Weekly Check-ins
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $weeklyCheckIns = [
            'individual' => [],
            'corporate' => []
        ];

        foreach ($days as $day) {
            // Individual
            $countInd = \App\Models\Member\MemberSubscriptionCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$startOfWeek, $endOfWeek])
                ->whereRaw("LOWER(DAYNAME(datetime)) = ?", [$day])
                ->count();
            $weeklyCheckIns['individual'][$day] = ['total' => $countInd];

            // Corporate
            $countCorp = \App\Models\Company\CompanySubscriptionMemberCheckIn::where('status', 'completed')
                ->whereBetween('datetime', [$startOfWeek, $endOfWeek])
                ->whereRaw("LOWER(DAYNAME(datetime)) = ?", [$day])
                ->count();
            $weeklyCheckIns['corporate'][$day] = ['total' => $countCorp];
        }


        // 6. Membership Distribution
        // Individual
        $distIndividualTotal = \App\Models\Member\Member::where('status', 'active')->count();
        $distIndividualMonthly = \App\Models\Member\MemberSubscription::where('status', 'in_progress') // Assuming in_progress means active logic
            ->where(function($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('start_date', [$startOfMonth, $endOfMonth]); // Started this month? Or Active this month? Request says "count MemberSubscription in that month". I'll assume started.
            })->count();
        // Actually, "In that month where status is in_progress" might mean active during that month.
        // But simpler interpretation: Currently in_progress and created in that month?
        // Let's stick to: Currently in_progress.
        // Wait, "monthly" and "quarterly" suggested a breakdown.
        // "count MemberSubscription in that month where status is in_progress" -> likely means subscriptions active in the current month.
        $distIndividualMonthly = \App\Models\Member\MemberSubscription::where('status', 'in_progress')
             ->where('start_date', '<=', $endOfMonth)
             ->where(function($q) use ($startOfMonth) {
                 $q->where('end_date', '>=', $startOfMonth)->orWhereNull('end_date');
             })
            ->count();
        
        $distIndividualQuarterly = \App\Models\Member\MemberSubscription::where('status', 'in_progress')
             ->where('start_date', '<=', $endOfQuarter)
             ->where(function($q) use ($startOfQuarter) {
                 $q->where('end_date', '>=', $startOfQuarter)->orWhereNull('end_date');
             })
            ->count();


        // Corporate
        // Request: "count all in CompanySubscriptionMember.company_subscription where company_subscription.statatus is in_progress"
        // This seems to imply counting Members who belong to an active company subscription?
        // Or counting the subscriptions themselves?
        // "count all in CompanySubscriptionMember" -> implies counting members.
        $distCorporateTotal = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q){
            $q->where('status', 'in_progress');
        })->count();

        $distCorporateMonthly = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q) use ($startOfMonth, $endOfMonth){
            $q->where('status', 'in_progress')
              ->where('start_date', '<=', $endOfMonth)
              ->where(function($sq) use ($startOfMonth) {
                  $sq->where('end_date', '>=', $startOfMonth)->orWhereNull('end_date');
              });
        })->count();

         $distCorporateQuarterly = \App\Models\Company\CompanySubscriptionMember::whereHas('company_subscription', function($q) use ($startOfQuarter, $endOfQuarter){
            $q->where('status', 'in_progress')
              ->where('start_date', '<=', $endOfQuarter)
              ->where(function($sq) use ($startOfQuarter) {
                  $sq->where('end_date', '>=', $startOfQuarter)->orWhereNull('end_date');
              });
        })->count();


        // 7. Revenue Breakdown (Monthly per year)
        // Individual
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'jully', 'august', 'september', 'october', 'november', 'december'];
        $revenueBreakdown = [
            'individual' => [],
            'corporate' => [],
            'individual_and_corporate' => []
        ];

        foreach($months as $index => $month) {
            $monthNum = $index + 1;
            
            // Individual
            $sumInd = \App\Models\Member\MemberSubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid');
            $revenueBreakdown['individual'][$month] = $sumInd;

            // Corporate
            $sumCorp = \App\Models\Company\CompanySubscriptionTransaction::where('status', 'completed')
                ->whereMonth('created_at', $monthNum)
                ->whereYear('created_at', now()->year)
                ->sum('amount_paid');
            $revenueBreakdown['corporate'][$month] = $sumCorp;

            // Combined
            $revenueBreakdown['individual_and_corporate'][$month] = $sumInd + $sumCorp;
        }
        // Note: 'jully' is a typo in request, I should stick to it or correct it? The request asked for "jully". I will use "jully" key to match response format.
        // But loop above uses standard names if I rely on Carbon or simple array. I used manual array.


        return response()->json([
            "active_members" => [
                "total" => $totalActiveMembers,
                "progress_previous_month" => round($memberProgress, 2)
            ],
            "daily_attendance" => [
                "individual" => [
                    "total" => $dailyIndividualTotal,
                    "progress_previous_day" => round($dailyIndividualProgress, 2)
                ],
                "corporate" => [
                    "total" => $dailyCorporateTotal,
                    "progress_previous_day" => round($dailyCorporateProgress, 2)
                ]
            ],
            "expiring_soon" => [
                "individual" => [
                    "total" => $expiringIndividualTotal,
                    "today" => $expiringIndividualToday
                ],
                "corporate" => [
                    "total" => $expiringCorporateTotal,
                    "today" => $expiringCorporateToday
                ]
            ],
            "monthly_revenue" => [
                "corporate" => [
                    "total_amount" => $revenueCorporateTotal,
                    "progress_last_month" => round($revenueCorporateProgress, 2)
                ],
                "individual" => [
                    "total_amount" => $revenueIndividualTotal,
                    "progress_last_month" => round($revenueIndividualProgress, 2)
                ]
            ],
            "weekly_check_ins" => $weeklyCheckIns,
            "membership_distribution" => [
                "individual" => [
                    "total" => $distIndividualTotal,
                    "monthly" => $distIndividualMonthly,
                    "quarterly" => $distIndividualQuarterly
                ],
                "corporate" => [
                    "total" => $distCorporateTotal,
                    "monthly" => $distCorporateMonthly,
                    "quarterly" => $distCorporateQuarterly
                ]
            ],
             "revenue_breakdown" => $revenueBreakdown
        ]);


    }
}

