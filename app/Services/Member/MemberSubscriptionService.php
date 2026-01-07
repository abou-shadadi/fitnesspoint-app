<?php

namespace App\Services\Member;

use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionInvoice;
use App\Models\Plan\Plan;
use App\Models\Rate\RateType;
use App\Models\Invoice\TaxRate;
use App\Models\Discount\DiscountType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MemberSubscriptionService
{
    /**
     * Renew a subscription with automatic type detection
     */
    public function renewSubscription(
        MemberSubscription $currentSubscription,
        Plan $newPlan = null,
        array $options = []
    ) {
        $newPlan = $newPlan ?: $currentSubscription->plan;

        // Determine renewal type automatically
        $renewalType = $this->determineRenewalType($currentSubscription);

        // Calculate start date based on renewal type
        $startDate = $this->calculateRenewalStartDate($currentSubscription, $renewalType, $options);

        // Calculate end date based on new plan duration
        $endDate = $this->calculateEndDate($startDate, $newPlan->duration_type->unit, $newPlan->duration);

        // FIXED: For ALL renewal types, UPDATE the existing subscription
        // Instead of creating a new one for early/pre renewals
        $subscription = $this->updateSubscriptionForRenewal($currentSubscription, $newPlan, $startDate, $endDate, $renewalType);

        // Create invoice for the renewal
        $invoice = $this->createRenewalInvoice($subscription, $renewalType, $options);

        return [
            'subscription' => $subscription,
            'invoice' => $invoice,
            'renewal_type' => $renewalType,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];
    }

    /**
     * Update existing subscription for renewal (for ALL renewal types)
     */
    private function updateSubscriptionForRenewal(
        MemberSubscription $subscription,
        Plan $plan,
        Carbon $startDate,
        Carbon $endDate,
        $renewalType
    ) {
        $notes = $subscription->notes . "\n" . $this->getRenewalNote($renewalType, $subscription->id);

        // Update the existing subscription
        $subscription->update([
            'plan_id' => $plan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'pending', // Set to pending until paid
            'notes' => $notes,
        ]);

        return $subscription;
    }

    /**
     * Determine renewal type automatically based on subscription status and dates
     */
    private function determineRenewalType(MemberSubscription $subscription)
    {
        $now = now();

        if (!$subscription->end_date) {
            return 'new'; // No end date means it's a new subscription
        }

        $endDate = Carbon::parse($subscription->end_date);

        if ($subscription->status === 'expired') {
            return 'expired_renewal';
        }

        if ($endDate->isPast()) {
            // Subscription has expired but status might not be updated
            return 'expired_renewal';
        }

        if ($subscription->status === 'in_progress') {
            // Check if subscription is about to expire (within 7 days)
            $daysUntilExpiry = $now->diffInDays($endDate, false);

            if ($daysUntilExpiry <= 0) {
                return 'expired_renewal';
            } elseif ($daysUntilExpiry <= 7) {
                return 'early_renewal';
            } else {
                return 'pre_renewal';
            }
        }

        // Default to new subscription
        return 'new';
    }

    /**
     * Calculate renewal start date based on type
     */
    private function calculateRenewalStartDate(MemberSubscription $subscription, $renewalType, $options = [])
    {
        $now = now();

        if (!$subscription->end_date) {
            return $now; // New subscription starts now
        }

        $endDate = Carbon::parse($subscription->end_date);

        switch ($renewalType) {
            case 'expired_renewal':
                // Expired subscription: start from today
                return $now;

            case 'early_renewal':
                // Subscription expiring soon: can start from today or at expiry
                // Default to starting at expiry to avoid overlap
                return $endDate;

            case 'pre_renewal':
                // Subscription still has time: start at expiry date
                return $endDate;

            case 'new':
            default:
                return $now;
        }
    }

    /**
     * Create renewal invoice with action type
     */
    private function createRenewalInvoice(MemberSubscription $subscription, $renewalType, array $options = [])
    {
        // Get default rate, tax, and discount type if not provided
        $rateTypeId = $options['rate_type_id'] ?? RateType::first()->id;
        $taxRateId = $options['tax_rate_id'] ?? TaxRate::first()->id;
        $discountTypeId = $options['discount_type_id'] ?? null;
        $discountAmount = $options['discount_amount'] ?? 0;

        // Get discount type if provided
        $discountType = null;
        if ($discountTypeId) {
            $discountType = DiscountType::find($discountTypeId);
        }

        // Calculate base amount (plan price)
        $baseAmount = $subscription->plan->price;

        // Calculate tax amount
        $taxRate = TaxRate::find($taxRateId);
        $taxAmount = ($baseAmount * $taxRate->rate) / 100;

        // Calculate discount based on type
        $discountValue = 0;
        if ($discountType && $discountAmount > 0) {
            if ($discountType->name === 'Percentage') {
                // Percentage discount: discountAmount is the percentage
                $discountValue = ($baseAmount * $discountAmount) / 100;
            } else {
                // Fixed discount: discountAmount is the fixed amount
                $discountValue = $discountAmount;
            }

            // Ensure discount doesn't exceed base amount
            $discountValue = min($discountValue, $baseAmount);
        }

        // Calculate total with tax and discount
        $totalAmount = $baseAmount + $taxAmount - $discountValue;

        $invoice = new MemberSubscriptionInvoice([
            'member_subscription_id' => $subscription->id,
            'reference' => $this->generateInvoiceReference($subscription),
            'rate_type_id' => $rateTypeId,
            'tax_rate_id' => $taxRateId,
            'discount_type_id' => $discountTypeId,
            'from_date' => $subscription->start_date,
            'to_date' => $subscription->end_date,
            'due_date' => $options['due_date'] ?? now()->addDays(7),
            'amount' => $baseAmount,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountValue, // Store the calculated discount value
            'total_amount' => $totalAmount,
            'invoice_date' => now(),
            'notes' => $options['notes'] ?? "{$renewalType} for plan: {$subscription->plan->name}",
            'status' => 'pending',
            'is_sent' => false,
            'action' => $this->mapRenewalTypeToAction($renewalType),
        ]);

        $invoice->save();
        return $invoice;
    }

    /**
     * Map renewal type to invoice action
     */
    private function mapRenewalTypeToAction($renewalType)
    {
        $actionMap = [
            'new' => 'new',
            'expired_renewal' => 'renew',
            'early_renewal' => 'renew',
            'pre_renewal' => 'renew',
        ];

        return $actionMap[$renewalType] ?? 'new';
    }

    /**
     * Get appropriate note for renewal type
     */
    private function getRenewalNote($renewalType, $originalSubscriptionId = null)
    {
        $notes = [
            'expired_renewal' => "Expired subscription renewed on " . now()->format('Y-m-d'),
            'early_renewal' => "Early renewal on " . now()->format('Y-m-d'),
            'pre_renewal' => "Pre-renewal on " . now()->format('Y-m-d'),
            'new' => "New subscription created",
        ];

        return $notes[$renewalType] ?? "Subscription renewed on " . now()->format('Y-m-d');
    }

    /**
     * Calculate end date based on duration type
     */
    private function calculateEndDate(Carbon $startDate, $unit, $duration)
    {
        switch ($unit) {
            case 'days':
                return $startDate->copy()->addDays($duration);
            case 'weeks':
                return $startDate->copy()->addWeeks($duration);
            case 'months':
                return $startDate->copy()->addMonths($duration);
            case 'years':
                return $startDate->copy()->addYears($duration);
            default:
                return $startDate->copy()->addDays($duration);
        }
    }

    /**
     * Generate invoice reference
     */
    private function generateInvoiceReference(MemberSubscription $subscription)
    {
        $date = now()->format('Ymd');
        $subscriptionId = str_pad($subscription->id, 6, '0', STR_PAD_LEFT);
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "INV-{$date}-MS{$subscriptionId}-{$random}";
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(MemberSubscription $subscription)
    {
        // Business rules for renewal eligibility
        if ($subscription->status === 'cancelled' || $subscription->status === 'rejected') {
            return false;
        }

        // Additional business rules can be added here
        return true;
    }

    /**
     * Calculate proration for upgrade (same as before, but refined)
     */
    public function calculateUpgradeProration(MemberSubscription $currentSubscription, Plan $newPlan, $upgradeDate = null)
    {
        $upgradeDate = $upgradeDate ? Carbon::parse($upgradeDate) : now();
        $startDate = Carbon::parse($currentSubscription->start_date);
        $endDate = Carbon::parse($currentSubscription->end_date);

        // Calculate total and remaining days
        $totalDays = $startDate->diffInDays($endDate);
        $remainingDays = $upgradeDate->diffInDays($endDate);

        if ($remainingDays <= 0 || $totalDays <= 0) {
            return ['amount' => 0, 'remaining_days' => 0, 'credit_amount' => 0];
        }

        // Calculate daily rates
        $currentDailyRate = $currentSubscription->plan->price / $totalDays;
        $creditAmount = $currentDailyRate * $remainingDays;

        // Calculate new plan daily rate
        $newTotalDays = $this->getDaysFromDuration($newPlan->duration, $newPlan->duration_type->unit);
        $newDailyRate = $newPlan->price / $newTotalDays;
        $newCostForRemaining = $newDailyRate * $remainingDays;

        // Proration amount
        $prorationAmount = max(0, $newCostForRemaining - $creditAmount);

        return [
            'amount' => $prorationAmount,
            'remaining_days' => $remainingDays,
            'credit_amount' => $creditAmount,
            'old_daily_rate' => $currentDailyRate,
            'new_daily_rate' => $newDailyRate,
        ];
    }

    /**
     * Convert duration to days
     */
    private function getDaysFromDuration($duration, $unit)
    {
        switch ($unit) {
            case 'days':
                return $duration;
            case 'weeks':
                return $duration * 7;
            case 'months':
                return $duration * 30; // Approximation
            case 'years':
                return $duration * 365;
            default:
                return $duration;
        }
    }
    /**
     * Upgrade subscription with proration
     */
    public function upgradeSubscription(
        MemberSubscription $currentSubscription,
        Plan $newPlan,
        array $options = []
    ) {
        // Calculate proration if needed
        $prorationData = ['amount' => 0, 'remaining_days' => 0];
        $shouldProrate = $options['prorate'] ?? true;

        if ($shouldProrate && $currentSubscription->status === 'in_progress') {
            $prorationData = $this->calculateUpgradeProration($currentSubscription, $newPlan);
        }

        // FIXED: Determine start date automatically - default to immediate upgrade
        // If proration is applied, start from now. If no proration, start from current end date?
        // Let's always start from now for upgrades to be consistent
        $startDate = now();

        // Calculate end date
        $endDate = $this->calculateEndDate($startDate, $newPlan->duration_type->unit, $newPlan->duration);

        // Create upgraded subscription
        $subscription = $this->createUpgradedSubscription($currentSubscription, $newPlan, $startDate, $endDate);

        // Create invoice with proration
        $invoice = $this->createUpgradeInvoice($subscription, $currentSubscription, $prorationData, $options);

        // Handle old subscription
        $this->handleOldSubscription($currentSubscription, $subscription->id);

        return [
            'subscription' => $subscription,
            'invoice' => $invoice,
            'proration' => $prorationData,
        ];
    }

    /**
     * Create upgraded subscription
     */
    private function createUpgradedSubscription(
        MemberSubscription $currentSubscription,
        Plan $newPlan,
        Carbon $startDate,
        Carbon $endDate
    ) {
        return MemberSubscription::create([
            'member_id' => $currentSubscription->member_id,
            'plan_id' => $newPlan->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notes' => "Upgrade from '{$currentSubscription->plan->name}' (#{$currentSubscription->id})",
            'created_by_id' => auth()->id(),
            'branch_id' => $currentSubscription->branch_id,
            'status' => 'pending',
        ]);
    }

    /**
     * Create upgrade invoice
     */
    private function createUpgradeInvoice(
        MemberSubscription $newSubscription,
        MemberSubscription $oldSubscription,
        array $prorationData,
        array $options = []
    ) {
        // Calculate base amount + proration
        $baseAmount = $newSubscription->plan->price;
        $totalAmount = $baseAmount + $prorationData['amount'];

        // Get default rate, tax, and discount type
        $rateTypeId = $options['rate_type_id'] ?? RateType::first()->id;
        $taxRateId = $options['tax_rate_id'] ?? TaxRate::first()->id;
        $discountTypeId = $options['discount_type_id'] ?? null;
        $discountAmount = $options['discount_amount'] ?? 0;

        // Get discount type if provided
        $discountType = null;
        if ($discountTypeId) {
            $discountType = DiscountType::find($discountTypeId);
        }

        // Calculate discount based on type
        $discountValue = 0;
        if ($discountType && $discountAmount > 0) {
            if ($discountType->name === 'Percentage') {
                // Percentage discount: discountAmount is the percentage
                $discountValue = ($totalAmount * $discountAmount) / 100;
            } else {
                // Fixed discount: discountAmount is the fixed amount
                $discountValue = $discountAmount;
            }

            // Ensure discount doesn't exceed total amount
            $discountValue = min($discountValue, $totalAmount);
        }

        // Calculate tax on amount after discount
        $taxRate = TaxRate::find($taxRateId);
        $taxableAmount = $totalAmount - $discountValue;
        $taxAmount = ($taxableAmount * $taxRate->rate) / 100;

        // Calculate final amount
        $finalAmount = $totalAmount + $taxAmount - $discountValue;

        // Build notes
        $notes = "Upgrade from '{$oldSubscription->plan->name}' to '{$newSubscription->plan->name}'";
        if ($prorationData['amount'] > 0) {
            $notes .= "\nProrated amount for {$prorationData['remaining_days']} remaining days: " . number_format($prorationData['amount'], 2);
        }
        if (!empty($options['notes'])) {
            $notes .= "\n" . $options['notes'];
        }
        if ($discountValue > 0) {
            $discountTypeName = $discountType ? $discountType->name : 'Discount';
            $notes .= "\n{$discountTypeName} applied: " . number_format($discountValue, 2);
            if ($discountType && $discountType->name === 'Percentage') {
                $notes .= " ({$discountAmount}%)";
            }
        }

        $invoice = new MemberSubscriptionInvoice([
            'member_subscription_id' => $newSubscription->id,
            'reference' => $this->generateInvoiceReference($newSubscription),
            'rate_type_id' => $rateTypeId,
            'tax_rate_id' => $taxRateId,
            'discount_type_id' => $discountTypeId,
            'from_date' => $newSubscription->start_date,
            'to_date' => $newSubscription->end_date,
            'due_date' => $options['due_date'] ?? now()->addDays(7),
            'amount' => $baseAmount,
            'proration_amount' => $prorationData['amount'],
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountValue,
            'total_amount' => $finalAmount,
            'invoice_date' => now(),
            'notes' => $notes,
            'status' => 'pending',
            'is_sent' => false,
            'action' => 'upgrade',
        ]);

        $invoice->save();
        return $invoice;
    }

    /**
     * Handle old subscription after upgrade
     */
    private function handleOldSubscription(MemberSubscription $oldSubscription, $newSubscriptionId)
    {
        $oldSubscription->update([
            'status' => 'cancelled',
            'notes' => $oldSubscription->notes . "\nUpgraded to new subscription #{$newSubscriptionId} on " . now()->format('Y-m-d'),
        ]);
    }

    /**
     * Check if subscription can be upgraded
     */
    public function canUpgrade(MemberSubscription $subscription, Plan $newPlan)
    {
        if ($subscription->status === 'cancelled' || $subscription->status === 'rejected') {
            return false;
        }

        // Additional upgrade rules
        if ($subscription->plan_id === $newPlan->id) {
            return false; // Same plan, not an upgrade
        }

        return true;
    }
}
