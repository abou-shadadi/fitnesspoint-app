<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan\Plan;
use App\Models\Plan\PlanBenefit;
use App\Models\Currency;
use App\Models\Duration\DurationType;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get default currency
        $currency = Currency::where('is_default', 1)->first();

        if (!$currency) {
            $currency = Currency::first();
        }

        // Get duration types
        $monthlyDuration = DurationType::where('unit', 'months')->first();
        $yearlyDuration = DurationType::where('unit', 'years')->first();

        if (!$monthlyDuration || !$yearlyDuration) {
            throw new \Exception('Required duration types not found. Please run DurationTypeSeeder first.');
        }

        $plans = [
            [
                'id' => 1,
                'label' => 'Monthly Membership',
                'description' => 'Standard monthly gym membership',
                'price' => 80000,
                'currency_id' => $currency->id,
                'duration' => 1,
                'duration_type_id' => $monthlyDuration->id,
                'status' => 'active',
                'benefits' => [1, 2, 3, 4, 5, 6, 7], // All benefits
            ],
            [
                'id' => 2,
                'label' => '3 Months Membership',
                'description' => 'Quarterly gym membership with savings',
                'price' => 220000,
                'currency_id' => $currency->id,
                'duration' => 3,
                'duration_type_id' => $monthlyDuration->id,
                'status' => 'active',
                'benefits' => [1, 2, 3, 4, 5, 6, 7], // All benefits
            ],
            [
                'id' => 3,
                'label' => '6 Months Membership',
                'description' => 'Half-year gym membership',
                'price' => 350000,
                'currency_id' => $currency->id,
                'duration' => 6,
                'duration_type_id' => $monthlyDuration->id,
                'status' => 'active',
                'benefits' => [1, 2, 3, 4, 5, 6, 7], // All benefits
            ],
            [
                'id' => 4,
                'label' => '1 Year Membership',
                'description' => 'Annual gym membership',
                'price' => 600000,
                'currency_id' => $currency->id,
                'duration' => 1,
                'duration_type_id' => $yearlyDuration->id,
                'status' => 'active',
                'benefits' => [1, 2, 3, 4, 5, 6, 7], // All benefits
            ],
            [
                'id' => 5,
                'label' => '1 Year Membership For Couples',
                'description' => 'Annual membership for two people',
                'price' => 990000,
                'currency_id' => $currency->id,
                'duration' => 1,
                'duration_type_id' => $yearlyDuration->id,
                'status' => 'active',
                'benefits' => [1, 2, 3, 4, 5, 6, 7], // All benefits
            ],
        ];

        foreach ($plans as $planData) {
            $benefits = $planData['benefits'];
            unset($planData['benefits']);

            // Find or create the plan
            $plan = Plan::updateOrCreate(
                ['id' => $planData['id']],
                $planData
            );

            // Sync benefits
            foreach ($benefits as $benefitId) {
                PlanBenefit::updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'benefit_id' => $benefitId,
                    ],
                    ['status' => 'active']
                );
            }

            // Remove any benefits that are not in the current list (if needed)
            PlanBenefit::where('plan_id', $plan->id)
                ->whereNotIn('benefit_id', $benefits)
                ->delete();
        }

        // Optional: Remove any plans that are not in the seeder list
        // $planIds = array_column($plans, 'id');
        // Plan::whereNotIn('id', $planIds)->delete();
    }
}
