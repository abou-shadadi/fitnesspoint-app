<?php

namespace App\Http\Controllers\Api\V1\Member\Subscription\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member\Member;
use App\Models\Member\MemberSubscription;
use App\Models\Member\MemberSubscriptionInvoice;
use App\Models\Member\MemberSubscriptionCheckIn;
use App\Models\Rate\RateType;
use App\Models\Invoice\TaxRate;
use App\Models\Discount\DiscountType;
use App\Models\Plan\Plan;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\File\Base64Service;
use Carbon\Carbon;
use Mpdf\Mpdf;

class MemberSubscriptionInvoiceController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices",
     *     summary="List member subscription invoices",
     *     tags={"Members | Subscription Invoices"},
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
     *         name="status",
     *         in="query",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "paid", "overdue", "cancelled", "refunded", "rejected", "partially_paid"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of invoices"),
     *     @OA\Response(response=404, description="Member or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $memberId, $subscriptionId)
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
                ->with(['plan', 'plan.currency', 'plan.duration_type'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member subscription not found',
                    'data' => null
                ], 404);
            }

            $query = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->with(['rate_type', 'tax_rate', 'member_subscription.plan']);

            // Filter by status
            if ($request->has('status') && $request->status) {
                $query->where('status', $request->status);
            }

            // Search by reference
            if ($request->has('search') && $request->search) {
                $query->where('reference', 'like', '%' . $request->search . '%');
            }

            $invoices = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Member subscription invoices retrieved successfully',
                'data' => [
                    'subscription' => $subscription,
                    'invoices' => $invoices
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}",
     *     summary="Get specific invoice",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\Response(response=200, description="Invoice details"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($memberId, $subscriptionId, $invoiceId)
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

            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->with([
                    'rate_type',
                    'tax_rate',
                    'member_subscription.plan',
                    'member_subscription.member'
                ])
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice retrieved successfully',
                'data' => $invoice
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
     * @OA\Post(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices",
     *     summary="Create a new invoice",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"rate_type_id", "tax_rate_id", "due_date", "invoice_date", "notes"},
     *             @OA\Property(property="rate_type_id", type="integer", example=1),
     *             @OA\Property(property="tax_rate_id", type="integer", example=1),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="invoice_date", type="string", format="date", example="2024-12-01"),
     *             @OA\Property(property="notes", type="string", example="Monthly subscription fee"),
     *             @OA\Property(property="file", type="string", description="Base64 encoded file"),
     *             @OA\Property(
     *                 property="discount_amount",
     *                 type="number",
     *                 format="float",
     *                 example=0.00
     *             ),
     *             @OA\Property(
     *                 property="discount_type_id",
     *                 type="integer",
     *                 description="ID of discount type (fixed or percentage)",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Member, subscription, rate type, or tax rate not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $memberId, $subscriptionId)
    {
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
            ->with(['plan', 'plan.currency', 'plan.duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'rate_type_id' => 'required|exists:rate_types,id',
            'tax_rate_id' => 'required|exists:tax_rates,id',
            'due_date' => 'required|date',
            'invoice_date' => 'required|date',
            'notes' => 'nullable|string',
            'file' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type_id' => 'nullable|exists:discount_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Verify rate type exists
        $rateType = RateType::find($request->input('rate_type_id'));
        if (!$rateType) {
            return response()->json([
                'success' => false,
                'message' => 'Rate type not found',
                'data' => null
            ], 404);
        }

        // Verify tax rate exists
        $taxRate = TaxRate::find($request->input('tax_rate_id'));
        if (!$taxRate) {
            return response()->json([
                'success' => false,
                'message' => 'Tax rate not found',
                'data' => null
            ], 404);
        }

        // Verify discount type exists if provided
        if ($request->has('discount_type_id')) {
            $discountType = DiscountType::find($request->input('discount_type_id'));
            if (!$discountType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Discount type not found',
                    'data' => null
                ], 404);
            }
        }

        // Check for duplicate pending invoices with same date range
        $existingPendingInvoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
            ->where('status', 'pending')
            ->where('from_date', $subscription->start_date)
            ->where('to_date', $subscription->end_date)
            ->first();

        if ($existingPendingInvoice) {
            return response()->json([
                'success' => false,
                'message' => 'A pending invoice already exists for this subscription period',
                'data' => $existingPendingInvoice
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Calculate amount - just use plan price directly (no multiplication)
            $amount = $this->calculateSubscriptionAmount($subscription);

            // Calculate tax amount
            $taxAmount = $this->calculateTaxAmount($amount, $taxRate);

            // Calculate discount amount
            $discountAmount = $this->calculateDiscountAmount(
                $amount,
                $request->input('discount_amount', 0),
                $request->input('discount_type_id')
            );

            // Calculate total amount
            $totalAmount = ($amount + $taxAmount) - $discountAmount;

            // Get total check-ins
            $totalCheckIns = $this->getTotalCheckIns($subscriptionId);

            // Generate reference
            $reference = $this->generateInvoiceReference($subscription);

            // Create invoice
            $invoice = new MemberSubscriptionInvoice([
                'reference' => $reference,
                'member_subscription_id' => $subscriptionId,
                'rate_type_id' => $request->input('rate_type_id'),
                'tax_rate_id' => $request->input('tax_rate_id'),
                'from_date' => $subscription->start_date,
                'to_date' => $subscription->end_date,
                'due_date' => $request->input('due_date'),
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'invoice_date' => $request->input('invoice_date'),
                'notes' => $request->input('notes'),
                'total_check_ins' => $totalCheckIns,
                'discount_type_id' => $request->input('discount_type_id'),
                'status' => 'pending',
                'is_sent' => false,
            ]);

            $invoice->save();

            // Process file if provided
            if ($request->has('file') && !empty($request->input('file'))) {
                $this->base64Service->processBase64File($invoice, $request->input('file'), 'file');
            }

            // Load relationships
            $invoice->load(['rate_type', 'tax_rate', 'member_subscription.plan']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}",
     *     summary="Update invoice",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="rate_type_id", type="integer", example=2),
     *             @OA\Property(property="tax_rate_id", type="integer", example=2),
     *             @OA\Property(property="due_date", type="string", format="date", example="2024-12-31"),
     *             @OA\Property(property="invoice_date", type="string", format="date", example="2024-12-01"),
     *             @OA\Property(property="notes", type="string", example="Updated notes"),
     *             @OA\Property(property="file", type="string", description="Base64 encoded file"),
     *             @OA\Property(
     *                 property="discount_amount",
     *                 type="number",
     *                 format="float",
     *                 example=100.00
     *             ),
     *             @OA\Property(
     *                 property="discount_type_id",
     *                 type="integer",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="is_sent",
     *                 type="boolean",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Invoice updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Invoice not found or not pending"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $memberId, $subscriptionId, $invoiceId)
    {
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
            ->with(['plan', 'plan.currency', 'plan.duration_type'])
            ->find($subscriptionId);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Member subscription not found',
                'data' => null
            ], 404);
        }

        // Get invoice
        $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
            ->find($invoiceId);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'data' => null
            ], 404);
        }

        // Check if invoice is pending (only pending invoices can be updated)
        if ($invoice->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending invoices can be updated',
                'data' => null
            ], 400);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'rate_type_id' => 'nullable|exists:rate_types,id',
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'due_date' => 'nullable|date',
            'invoice_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'file' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type_id' => 'nullable|exists:discount_types,id',
            'is_sent' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Prepare update data
            $updateData = [];
            if ($request->has('due_date')) $updateData['due_date'] = $request->input('due_date');
            if ($request->has('invoice_date')) $updateData['invoice_date'] = $request->input('invoice_date');
            if ($request->has('notes')) $updateData['notes'] = $request->input('notes');
            if ($request->has('is_sent')) $updateData['is_sent'] = $request->input('is_sent');

            // Update rate type if provided
            if ($request->has('rate_type_id')) {
                $rateType = RateType::find($request->input('rate_type_id'));
                if (!$rateType) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Rate type not found',
                        'data' => null
                    ], 404);
                }
                $updateData['rate_type_id'] = $request->input('rate_type_id');
            }

            // Update tax rate if provided
            if ($request->has('tax_rate_id')) {
                $taxRate = TaxRate::find($request->input('tax_rate_id'));
                if (!$taxRate) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Tax rate not found',
                        'data' => null
                    ], 404);
                }
                $updateData['tax_rate_id'] = $request->input('tax_rate_id');

                // Recalculate tax amount if tax rate changed
                $taxAmount = $this->calculateTaxAmount($invoice->amount, $taxRate);
                $updateData['tax_amount'] = $taxAmount;

                // Recalculate total amount
                $totalAmount = ($invoice->amount + $taxAmount) - $invoice->discount_amount;
                $updateData['total_amount'] = $totalAmount;
            }

            // Handle discount if provided
            if ($request->has('discount_amount') || $request->has('discount_type_id')) {
                $discountAmount = $request->input('discount_amount', $invoice->discount_amount);
                $discountTypeId = $request->input('discount_type_id');

                if ($request->has('discount_type_id')) {
                    $discountType = DiscountType::find($discountTypeId);
                    if (!$discountType) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Discount type not found',
                            'data' => null
                        ], 404);
                    }
                }

                $updateData['discount_amount'] = $discountAmount;

                // Recalculate total amount
                $currentTaxAmount = $updateData['tax_amount'] ?? $invoice->tax_amount;
                $totalAmount = ($invoice->amount + $currentTaxAmount) - $discountAmount;
                $updateData['total_amount'] = $totalAmount;
            }

            // Update invoice
            $invoice->update($updateData);

            // Process file if provided
            if ($request->has('file') && $request->input('file') !== null) {
                $this->base64Service->processBase64File($invoice, $request->input('file'), 'file', true);
            }

            // Load relationships
            $invoice->load(['rate_type', 'tax_rate', 'member_subscription.plan']);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice
            ], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}",
     *     summary="Delete invoice",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\Response(response=200, description="Invoice deleted successfully"),
     *     @OA\Response(response=400, description="Cannot delete non-pending invoice"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($memberId, $subscriptionId, $invoiceId)
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

            // Get invoice
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Check if invoice is pending (only pending invoices can be deleted)
            if ($invoice->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending invoices can be deleted',
                    'data' => null
                ], 400);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Delete associated transactions first
                $invoice->transactions()->delete();

                // Delete the invoice
                $invoice->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Invoice deleted successfully',
                    'data' => null
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/status",
     *     summary="Update invoice status",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status"},
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "paid", "overdue", "cancelled", "refunded", "rejected", "partially_paid"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Status updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateStatus(Request $request, $memberId, $subscriptionId, $invoiceId)
    {
        try {
            // Get invoice
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'status' => [
                    'required',
                    Rule::in(['pending', 'paid', 'overdue', 'cancelled', 'refunded', 'rejected', 'partially_paid'])
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'data' => null
                ], 400);
            }

            $oldStatus = $invoice->status;
            $newStatus = $request->input('status');

            // Update status
            $invoice->update(['status' => $newStatus]);

            return response()->json([
                'success' => true,
                'message' => "Invoice status updated from '{$oldStatus}' to '{$newStatus}'",
                'data' => $invoice
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
     *     path="/api/members/{memberId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/export",
     *     summary="Export invoice to PDF",
     *     tags={"Members | Subscription Invoices"},
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
     *     @OA\Response(
     *         response=200,
     *         description="PDF file download",
     *         @OA\MediaType(
     *             mediaType="application/pdf",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function export($memberId, $subscriptionId, $invoiceId)
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

            // Get invoice with relationships
            $invoice = MemberSubscriptionInvoice::where('member_subscription_id', $subscriptionId)
                ->with([
                    'rate_type',
                    'tax_rate',
                    'member_subscription.plan',
                    'member_subscription.member'
                ])
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Generate PDF
            return $this->generateInvoicePdf($invoice, $subscription, $member);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    /**
     * Calculate subscription amount - just return the plan price as-is
     * The plan price in the seeder is already the total price for the duration
     * (e.g., 80,000 for 1 month, 220,000 for 3 months)
     */
    private function calculateSubscriptionAmount($subscription)
    {
        $plan = $subscription->plan;

        // Just return the plan price directly - it's already the total for the duration
        return $plan->price;
    }

    /**
     * Calculate tax amount
     */
    private function calculateTaxAmount($amount, $taxRate)
    {
        return ($amount * $taxRate->rate) / 100;
    }

    /**
     * Calculate discount amount
     */
    private function calculateDiscountAmount($amount, $discountValue, $discountTypeId = null)
    {
        if (!$discountTypeId || $discountValue <= 0) {
            return 0;
        }

        // Get discount type to determine calculation method
        $discountType = DiscountType::find($discountTypeId);

        if (!$discountType) {
            return 0;
        }

        // Check discount type key (fixed or percentage)
        if ($discountType->key === 'percentage') {
            // Percentage discount
            return ($amount * $discountValue) / 100;
        } else {
            // Fixed amount discount
            return min($discountValue, $amount); // Can't discount more than the amount
        }
    }

    /**
     * Get total completed check-ins for subscription
     */
    private function getTotalCheckIns($subscriptionId)
    {
        return MemberSubscriptionCheckIn::where('member_subscription_id', $subscriptionId)
            ->where('status', 'completed')
            ->count();
    }

    /**
     * Generate invoice reference
     */
    private function generateInvoiceReference($subscription)
    {
        $date = now()->format('Ymd');
        $subscriptionId = str_pad($subscription->id, 6, '0', STR_PAD_LEFT);
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return "INV-{$date}-MS{$subscriptionId}-{$random}";
    }

    /**
     * Generate PDF for a single invoice
     */
    private function generateInvoicePdf($invoice, $subscription, $member)
    {
        $fileName = 'invoice_' . $invoice->reference . '_' . date('Ymd_His') . '.pdf';

        // Render Blade template
        $html = view('exports.member.invoice.general-invoice', [
            'invoice' => $invoice,
            'subscription' => $subscription,
            'member' => $member,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ])->render();

        // Create mPDF instance
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'default_font_size' => 10,
            'default_font' => 'dejavusans', // Supports UTF-8
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_header' => 5,
            'margin_footer' => 5,
        ]);

        // Set document information
        $mpdf->SetTitle('Invoice ' . $invoice->reference);
        $mpdf->SetAuthor('System');
        $mpdf->SetCreator('Member Subscription System');

        // Add header
        $mpdf->SetHTMLHeader('
    <div style="text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px;">
        <span style="font-size: 12px; color: #666;">Invoice ' . $invoice->reference . ' - ' . $member->name . '</span>
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
