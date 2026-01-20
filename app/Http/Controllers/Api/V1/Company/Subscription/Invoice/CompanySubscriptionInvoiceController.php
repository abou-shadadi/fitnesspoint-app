<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionInvoice;
use App\Models\Company\CompanySubscriptionInvoiceRecipient;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use App\Models\Rate\RateType;
use App\Models\Invoice\TaxRate;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use App\Services\File\Base64Service;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Response;

class CompanySubscriptionInvoiceController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices",
     *     summary="List subscription invoices",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "paid", "overdue", "cancelled", "refunded", "rejected", "partially_paid"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         required=false,
     *         description="Filter by invoice date from (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         required=false,
     *         description="Filter by invoice date to (YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"invoice_date", "due_date", "total_amount", "created_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="desc")
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
     *         description="List of invoices",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoices retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="invoices",
     *                     type="object",
     *                     @OA\Property(property="current_page", type="integer"),
     *                     @OA\Property(property="data", type="array", @OA\Items(type="object")),
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
     *                     @OA\Property(property="total", type="integer"),
     *                     @OA\Property(property="total_pending_amount", type="number", format="float"),
     *                     @OA\Property(property="total_paid_amount", type="number", format="float"),
     *                     @OA\Property(property="total_overdue_amount", type="number", format="float")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company or subscription not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $companyId, $subscriptionId)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->with(['billing_type', 'currency', 'duration_type'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $query = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->with([
                    'rate_type',
                    'tax_rate',
                    'company_subscription.billing_type',
                    'company_subscription_invoice_recipients.company_administrator',
                    'company_subscription_invoice_bank_accounts.bank_account.bank', // Load bank account with bank
                    'company_subscription_invoice_bank_accounts.bank_account.currency' // Load currency
                ]);

            // Filter by status
            if ($request->has('status') && in_array($request->status, [
                'pending', 'paid', 'overdue', 'cancelled', 'refunded', 'rejected', 'partially_paid'
            ])) {
                $query->where('status', $request->status);
            }

            // Filter by invoice date range
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('invoice_date', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('invoice_date', '<=', $request->date_to);
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'invoice_date');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
            $invoices = $query->paginate($perPage);

            // Calculate summary statistics
            $summary = [
                'total' => $invoices->total(),
                'total_pending_amount' => CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'pending')
                    ->sum('total_amount'),
                'total_paid_amount' => CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'paid')
                    ->sum('total_amount'),
                'total_overdue_amount' => CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                    ->where('status', 'overdue')
                    ->sum('total_amount'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Invoices retrieved successfully',
                'data' => [
                    'invoices' => $invoices,
                    'summary' => $summary,
                    'subscription_info' => [
                        'id' => $subscription->id,
                        'company_id' => $subscription->company_id,
                        'unit_price' => $subscription->unit_price,
                        'currency' => $subscription->currency->code ?? 'N/A',
                        'billing_type' => $subscription->billing_type->key ?? 'N/A',
                        'duration_type' => $subscription->duration_type->name ?? 'N/A',
                        'status' => $subscription->status,
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
     * @OA\Post(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices",
     *     summary="Create a new invoice",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"rate_type_id", "tax_rate_id", "due_date", "invoice_date"},
     *                 @OA\Property(
     *                     property="rate_type_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="tax_rate_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="due_date",
     *                     type="string",
     *                     format="date",
     *                     example="2024-12-31"
     *                 ),
     *                 @OA\Property(
     *                     property="invoice_date",
     *                     type="string",
     *                     format="date",
     *                     example="2024-12-01"
     *                 ),
     *                 @OA\Property(
     *                     property="discount_amount",
     *                     type="number",
     *                     format="float",
     *                     example=0
     *                 ),
     *                 @OA\Property(
     *                     property="discount_type_id",
     *                     type="integer",
     *                     example=1,
     *                     description="1=Percentage, 2=Fixed"
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     description="Base64 encoded file"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     example="Monthly invoice for December"
     *                 ),
     *                 @OA\Property(
     *                     property="recipients",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="company_administrator_id", type="integer", example=1),
     *                         @OA\Property(property="is_primary", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="bank_accounts",
     *                     type="array",
     *                     @OA\Items(
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     description="Array of bank account IDs"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Company, subscription, or related data not found"),
     *     @OA\Response(response=409, description="Duplicate pending invoice for the same period"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId, $subscriptionId)
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)
                ->with(['billing_type', 'currency'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'rate_type_id' => 'required|exists:rate_types,id',
                'tax_rate_id' => 'required|exists:tax_rates,id',
                'due_date' => 'required|date',
                'invoice_date' => 'required|date',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_type_id' => 'nullable|exists:discount_types,id',
                'file' => 'nullable|string',
                'notes' => 'nullable|string',
                'recipients' => 'nullable|array',
                'recipients.*.company_administrator_id' => 'required_with:recipients|exists:users,id',
                'recipients.*.is_primary' => 'required_with:recipients|boolean',
                'bank_accounts' => 'nullable|array',
                'bank_accounts.*' => 'integer|exists:bank_accounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            // Verify rate type exists
            $rateType = RateType::find($request->rate_type_id);
            if (!$rateType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rate type not found',
                    'data' => null
                ], 404);
            }

            // Verify tax rate exists
            $taxRate = TaxRate::find($request->tax_rate_id);
            if (!$taxRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tax rate not found',
                    'data' => null
                ], 404);
            }

            // Check for duplicate pending invoices for the same period
            $hasDuplicate = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->where('status', 'pending')
                ->where(function ($query) use ($subscription) {
                    $query->whereBetween('from_date', [$subscription->start_date, $subscription->end_date ?? now()])
                        ->orWhereBetween('to_date', [$subscription->start_date, $subscription->end_date ?? now()]);
                })
                ->exists();

            if ($hasDuplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'There is already a pending invoice for this subscription period',
                    'data' => null
                ], 409);
            }

            // Generate reference
            $reference = $this->generateInvoiceReference();

            // Set date range from subscription
            $fromDate = Carbon::parse($subscription->start_date);
            $toDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : Carbon::now();

            // Calculate amount based on billing type
            if ($subscription->billing_type->key === 'retail_fixed') {
                $amount = $subscription->unit_price;
                $totalMemberCheckIns = 0;
            } else if ($subscription->billing_type->key === 'per_pass') {
                // Calculate total unique members per day within the date range
                $totalMemberCheckIns = $this->calculatePerPassCheckIns($subscriptionId, $fromDate, $toDate);
                $amount = $totalMemberCheckIns * $subscription->unit_price;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported billing type',
                    'data' => null
                ], 400);
            }

            // Calculate tax amount
            $taxAmount = $amount * ($taxRate->rate / 100);

            // Calculate discount
            $discountAmount = $this->calculateDiscount(
                $request->discount_amount ?? 0,
                $request->discount_type_id ?? null,
                $amount
            );

            // Calculate total amount
            $totalAmount = $amount + $taxAmount - $discountAmount;

            // Create invoice
            $invoice = CompanySubscriptionInvoice::create([
                'reference' => $reference,
                'company_subscription_id' => $subscriptionId,
                'rate_type_id' => $request->rate_type_id,
                'tax_rate_id' => $request->tax_rate_id,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'due_date' => $request->due_date,
                'amount' => $amount,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'invoice_date' => $request->invoice_date,
                'notes' => $request->notes,
                'total_member_check_ins' => $totalMemberCheckIns,
                'status' => 'pending',
            ]);

            // Handle base64 file upload
            if ($request->has('file') && $request->file) {
                $this->base64Service->processBase64File($invoice, $request->file, 'file');
            }

            // Add recipients if provided
            if ($request->has('recipients') && is_array($request->recipients)) {
                foreach ($request->recipients as $recipientData) {
                    CompanySubscriptionInvoiceRecipient::create([
                        'company_subscription_invoice_id' => $invoice->id,
                        'company_administrator_id' => $recipientData['company_administrator_id'],
                        'is_primary' => $recipientData['is_primary'],
                    ]);
                }
            }

            // Add bank accounts if provided
            if ($request->has('bank_accounts') && is_array($request->bank_accounts)) {
                foreach ($request->bank_accounts as $bankAccountId) {
                    \App\Models\Company\CompanySubscriptionInvoiceBankAccount::create([
                        'company_subscription_invoice_id' => $invoice->id,
                        'bank_account_id' => $bankAccountId,
                        'status' => 'active', // All provided IDs get active status
                    ]);
                }
            }

            // Load relationships
            $invoice->load([
                'rate_type',
                'tax_rate',
                'company_subscription.currency',
                'company_subscription.billing_type',
                'company_subscription_invoice_recipients.company_administrator',
                'company_subscription_invoice_bank_accounts.bank_account.bank',
                'company_subscription_invoice_bank_accounts.bank_account.currency'
            ]);

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
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{id}",
     *     summary="Get specific invoice",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $subscriptionId, $id)
    {
        try {
            // Verify company exists
            $company = Company::find($companyId);
            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            // Verify subscription exists and belongs to company
            $subscription = CompanySubscription::where('company_id', $companyId)->find($subscriptionId);
            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->with([
                    'rate_type',
                    'tax_rate',
                    'company_subscription.currency',
                    'company_subscription.billing_type',
                    'company_subscription_invoice_recipients.company_administrator',
                    'company_subscription_invoice_bank_accounts.bank_account.bank',
                    'company_subscription_invoice_bank_accounts.bank_account.currency'
                ])
                ->find($id);

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
     * @OA\Put(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{id}",
     *     summary="Update an invoice",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="rate_type_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="tax_rate_id",
     *                     type="integer",
     *                     example=2
     *                 ),
     *                 @OA\Property(
     *                     property="due_date",
     *                     type="string",
     *                     format="date",
     *                     example="2024-12-31"
     *                 ),
     *                 @OA\Property(
     *                     property="invoice_date",
     *                     type="string",
     *                     format="date",
     *                     example="2024-12-01"
     *                 ),
     *                 @OA\Property(
     *                     property="discount_amount",
     *                     type="number",
     *                     format="float",
     *                     example=50
     *                 ),
     *                 @OA\Property(
     *                     property="discount_type_id",
     *                     type="integer",
     *                     example=2,
     *                     description="1=Percentage, 2=Fixed"
     *                 ),
     *                 @OA\Property(
     *                     property="file",
     *                     type="string",
     *                     description="Base64 encoded file"
     *                 ),
     *                 @OA\Property(
     *                     property="notes",
     *                     type="string",
     *                     example="Updated notes"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"pending", "paid", "overdue", "cancelled", "refunded", "rejected", "partially_paid"},
     *                     example="paid"
     *                 ),
     *                 @OA\Property(
     *                     property="recipients",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="company_administrator_id", type="integer", example=1),
     *                         @OA\Property(property="is_primary", type="boolean", example=true)
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="bank_accounts",
     *                     type="array",
     *                     @OA\Items(
     *                         type="integer",
     *                         example=1
     *                     ),
     *                     description="Array of bank account IDs"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Invoice updated successfully"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=403, description="Only pending invoices can be updated"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $subscriptionId, $id)
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            // Find invoice
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Check if invoice can be updated (only pending invoices)
            if ($invoice->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending invoices can be updated',
                    'data' => null
                ], 403);
            }

            // Validate request
            $validator = Validator::make($request->all(), [
                'rate_type_id' => 'nullable|exists:rate_types,id',
                'tax_rate_id' => 'nullable|exists:tax_rates,id',
                'due_date' => 'nullable|date',
                'invoice_date' => 'nullable|date',
                'discount_amount' => 'nullable|numeric|min:0',
                'discount_type_id' => 'nullable|exists:discount_types,id',
                'file' => 'nullable|string',
                'notes' => 'nullable|string',
                'status' => [
                    'nullable',
                    Rule::in(['pending', 'paid', 'overdue', 'cancelled', 'refunded', 'rejected', 'partially_paid'])
                ],
                'recipients' => 'nullable|array',
                'recipients.*.company_administrator_id' => 'required_with:recipients|exists:users,id',
                'recipients.*.is_primary' => 'required_with:recipients|boolean',
                'bank_accounts' => 'nullable|array',
                'bank_accounts.*' => 'integer|exists:bank_accounts,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            // Update invoice data
            $updateData = [];

            if ($request->has('rate_type_id')) {
                $updateData['rate_type_id'] = $request->rate_type_id;
            }

            if ($request->has('tax_rate_id')) {
                $updateData['tax_rate_id'] = $request->tax_rate_id;
                // Recalculate tax if tax rate changed
                if ($invoice->tax_rate_id != $request->tax_rate_id) {
                    $taxRate = TaxRate::find($request->tax_rate_id);
                    $updateData['tax_amount'] = $invoice->amount * ($taxRate->rate / 100);
                }
            }

            if ($request->has('due_date')) {
                $updateData['due_date'] = $request->due_date;
            }

            if ($request->has('invoice_date')) {
                $updateData['invoice_date'] = $request->invoice_date;
            }

            if ($request->has('discount_amount') || $request->has('discount_type_id')) {
                $discountAmount = $this->calculateDiscount(
                    $request->discount_amount ?? $invoice->discount_amount,
                    $request->discount_type_id ?? null,
                    $invoice->amount
                );
                $updateData['discount_amount'] = $discountAmount;
            }

            if ($request->has('notes')) {
                $updateData['notes'] = $request->notes;
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            // Process file if provided
            if ($request->has('file') && $request->file) {
                // Delete old file and upload new one
                $this->base64Service->processBase64File($invoice, $request->file, 'file', true);
            }

            // Recalculate total amount if tax or discount changed
            if (isset($updateData['tax_amount']) || isset($updateData['discount_amount'])) {
                $newTaxAmount = $updateData['tax_amount'] ?? $invoice->tax_amount;
                $newDiscountAmount = $updateData['discount_amount'] ?? $invoice->discount_amount;
                $updateData['total_amount'] = $invoice->amount + $newTaxAmount - $newDiscountAmount;
            }

            // Update invoice
            $invoice->update($updateData);

            // Update recipients if provided
            if ($request->has('recipients')) {
                // Delete existing recipients
                CompanySubscriptionInvoiceRecipient::where('company_subscription_invoice_id', $invoice->id)->delete();

                // Add new recipients
                foreach ($request->recipients as $recipientData) {
                    CompanySubscriptionInvoiceRecipient::create([
                        'company_subscription_invoice_id' => $invoice->id,
                        'company_administrator_id' => $recipientData['company_administrator_id'],
                        'is_primary' => $recipientData['is_primary'],
                    ]);
                }
            }

            // Handle bank accounts update
            if ($request->has('bank_accounts')) {
                $this->updateInvoiceBankAccounts($invoice->id, $request->bank_accounts);
            }

            // Load relationships
            $invoice->load([
                'rate_type',
                'tax_rate',
                'company_subscription.currency',
                'company_subscription.billing_type',
                'company_subscription_invoice_recipients.company_administrator',
                'company_subscription_invoice_bank_accounts.bank_account.bank',
                'company_subscription_invoice_bank_accounts.bank_account.currency'
            ]);

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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{id}",
     *     summary="Delete an invoice",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Invoice deleted successfully"),
     *     @OA\Response(response=404, description="Invoice not found"),
     *     @OA\Response(response=403, description="Only pending invoices can be deleted"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $subscriptionId, $id)
    {
        DB::beginTransaction();

        try {
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->find($id);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Check if invoice can be deleted (only pending invoices)
            if ($invoice->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending invoices can be deleted',
                    'data' => null
                ], 403);
            }

            // Delete file if exists
            if ($invoice->file && Storage::exists($invoice->file)) {
                Storage::delete($invoice->file);
            }

            // Delete recipients
            CompanySubscriptionInvoiceRecipient::where('company_subscription_invoice_id', $invoice->id)->delete();

            // Delete bank accounts
            \App\Models\Company\CompanySubscriptionInvoiceBankAccount::where('company_subscription_invoice_id', $invoice->id)->delete();

            // Delete invoice
            $invoice->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Invoice deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{id}/export",
     *     summary="Export invoice to PDF",
     *     tags={"Companies | Subscriptions | Invoices"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
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
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         required=false,
     *         description="Export format",
     *         @OA\Schema(type="string", enum={"pdf", "print"}, default="pdf")
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
    public function export(Request $request, $companyId, $subscriptionId, $id)
{
    try {
        // Verify company exists
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Verify subscription exists and belongs to company
        $subscription = CompanySubscription::where('company_id', $companyId)->find($subscriptionId);
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Company subscription not found',
                'data' => null
            ], 404);
        }

        // Get invoice with all relationships
        $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
            ->with([
                'rate_type',
                'tax_rate',
                'company_subscription.currency',
                'company_subscription.billing_type',
                'company_subscription.duration_type',
                'company_subscription.company',
                'company_subscription_invoice_recipients.company_administrator',
                'company_subscription_invoice_bank_accounts.bank_account.bank',
                'company_subscription_invoice_bank_accounts.bank_account.currency'
            ])
            ->find($id);

        if (!$invoice) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found',
                'data' => null
            ], 404);
        }

        // Load related transactions for payment summary
        $transactions = $invoice->company_subscription_transactions()
            ->where('status', 'completed')
            ->with(['payment_method', 'branch'])
            ->get();

        // Calculate payment summary
        $totalPaid = $transactions->sum('amount_paid');
        $balanceDue = max(0, $invoice->total_amount - $totalPaid);

        // Get check-in summary for per-pass billing
        $checkInSummary = [];
        if ($subscription->billing_type && $subscription->billing_type->key === 'per_pass') {
            $checkInSummary = $this->getCheckInSummary($subscriptionId, $invoice->from_date, $invoice->to_date);
        }

        // Get currency symbol
        $currencySymbol = $this->getCurrencySymbol($subscription->currency ?? $invoice->company_subscription->currency ?? null);

        // Prepare data for PDF
        $pdfData = [
            'invoice' => $invoice,
            'company' => $company,
            'subscription' => $subscription,
            'transactions' => $transactions,
            'total_paid' => $totalPaid,
            'balance_due' => $balanceDue,
            'check_in_summary' => $checkInSummary,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'currency_symbol' => $currencySymbol, // Add this
            'is_paid' => $invoice->status === 'paid',
            'is_partially_paid' => $invoice->status === 'partially_paid',
            'is_overdue' => $invoice->status === 'overdue',
        ];

        $format = $request->input('format', 'pdf');

        if ($format === 'print') {
            // Return HTML for print preview
            return view('exports.company.invoice.general-invoice', $pdfData);
        }

        // Generate PDF
        return $this->generateInvoicePdf($pdfData);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
            'data' => null
        ], 500);
    }
}

    /**
     * Generate a unique invoice reference
     */
    private function generateInvoiceReference()
    {
        $prefix = 'INV';
        $year = date('Y');
        $month = date('m');

        // Get the last invoice reference for the current year and month
        $lastInvoice = CompanySubscriptionInvoice::where('reference', 'like', $prefix . '-' . $year . $month . '-%')
            ->orderBy('reference', 'desc')
            ->first();

        if ($lastInvoice) {
            // Extract the sequence number and increment it
            $parts = explode('-', $lastInvoice->reference);
            $lastNumber = (int) end($parts);
            $sequence = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $sequence = '00001';
        }

        return $prefix . '-' . $year . $month . '-' . $sequence;
    }

    /**
     * Calculate per-pass check-ins for a subscription period
     */
    private function calculatePerPassCheckIns($subscriptionId, $fromDate, $toDate)
    {
        // Get unique members who checked in per day within the date range
        $checkIns = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($query) use ($subscriptionId) {
                $query->where('company_subscription_id', $subscriptionId);
            })
            ->where('status', 'completed')
            ->whereBetween('datetime', [$fromDate, $toDate])
            ->get();

        // Group by date and member to count unique members per day
        $dailyMemberCounts = [];

        foreach ($checkIns as $checkIn) {
            $date = Carbon::parse($checkIn->datetime)->format('Y-m-d');
            $memberId = $checkIn->company_subscription_member->member_id;

            if (!isset($dailyMemberCounts[$date])) {
                $dailyMemberCounts[$date] = [];
            }

            if (!in_array($memberId, $dailyMemberCounts[$date])) {
                $dailyMemberCounts[$date][] = $memberId;
            }
        }

        // Sum up unique members per day
        $totalMemberCheckIns = 0;
        foreach ($dailyMemberCounts as $date => $members) {
            $totalMemberCheckIns += count($members);
        }

        return $totalMemberCheckIns;
    }

    /**
     * Calculate discount amount based on type
     */
    private function calculateDiscount($amount, $typeId, $baseAmount)
    {
        if (!$amount || $amount <= 0) {
            return 0;
        }

        // Assuming typeId 1 = percentage, typeId 2 = fixed amount
        if ($typeId == 1) { // Percentage
            // Ensure percentage is between 0 and 100
            $percentage = min(100, max(0, $amount));
            return ($baseAmount * $percentage) / 100;
        } elseif ($typeId == 2) { // Fixed amount
            // Ensure discount doesn't exceed base amount
            return min($baseAmount, $amount);
        }

        return 0;
    }

    /**
     * Get check-in summary for per-pass billing
     */
    private function getCheckInSummary($subscriptionId, $fromDate, $toDate)
    {
        $checkIns = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($query) use ($subscriptionId) {
                $query->where('company_subscription_id', $subscriptionId);
            })
            ->where('status', 'completed')
            ->whereBetween('datetime', [$fromDate, $toDate])
            ->with(['company_subscription_member.member', 'check_in_method', 'branch'])
            ->get();

        // Group by date and count unique members per day
        $dailySummary = [];
        $uniqueMembers = [];
        $memberDates = [];

        foreach ($checkIns as $checkIn) {
            $date = Carbon::parse($checkIn->datetime)->format('Y-m-d');
            $memberId = $checkIn->company_subscription_member->member_id;

            if (!isset($dailySummary[$date])) {
                $dailySummary[$date] = [
                    'date' => $date,
                    'total_check_ins' => 0,
                    'unique_members' => 0,
                    'members' => []
                ];
            }

            $dailySummary[$date]['total_check_ins']++;

            // Track unique members per day
            $memberKey = $date . '_' . $memberId;
            if (!isset($memberDates[$memberKey])) {
                $memberDates[$memberKey] = true;
                $dailySummary[$date]['members'][$memberId] = true;
                $dailySummary[$date]['unique_members'] = count($dailySummary[$date]['members']);
            }

            // Track overall unique members
            if (!in_array($memberId, $uniqueMembers)) {
                $uniqueMembers[] = $memberId;
            }
        }

        // Sort by date
        ksort($dailySummary);

        return [
            'total_check_ins' => $checkIns->count(),
            'unique_members' => count($uniqueMembers),
            'daily_summary' => array_values($dailySummary),
        ];
    }

    /**
     * Update invoice bank accounts based on provided IDs
     *
     * @param int $invoiceId
     * @param array $providedBankAccountIds
     * @return void
     */
    private function updateInvoiceBankAccounts($invoiceId, array $providedBankAccountIds)
    {
        // Get all existing bank accounts for this invoice
        $existingBankAccounts = \App\Models\Company\CompanySubscriptionInvoiceBankAccount::where('company_subscription_invoice_id', $invoiceId)->get();

        // Track which bank accounts already exist
        $existingBankAccountMap = [];
        foreach ($existingBankAccounts as $bankAccount) {
            $existingBankAccountMap[$bankAccount->bank_account_id] = $bankAccount;
        }

        // Process provided bank account IDs
        foreach ($providedBankAccountIds as $bankAccountId) {
            if (isset($existingBankAccountMap[$bankAccountId])) {
                // Bank account already exists, update status to active
                $existingBankAccountMap[$bankAccountId]->update([
                    'status' => 'active'
                ]);

                // Remove from map so we know it was handled
                unset($existingBankAccountMap[$bankAccountId]);
            } else {
                // New bank account, create with active status
                \App\Models\Company\CompanySubscriptionInvoiceBankAccount::create([
                    'company_subscription_invoice_id' => $invoiceId,
                    'bank_account_id' => $bankAccountId,
                    'status' => 'active',
                ]);
            }
        }

        // Any remaining bank accounts in the map were not provided in the request
        // Mark them as inactive instead of deleting
        foreach ($existingBankAccountMap as $bankAccount) {
            $bankAccount->update([
                'status' => 'inactive'
            ]);
        }
    }

    /**
     * Generate PDF using mPDF
     */
    private function generateInvoicePdf($data)
    {
        $fileName = 'invoice_' . $data['invoice']->reference . '_' . date('Ymd_His') . '.pdf';

        // Render Blade template
        $html = view('exports.company.invoice.general-invoice', $data)->render();

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
        $mpdf->SetTitle('Invoice ' . $data['invoice']->reference);
        $mpdf->SetAuthor($data['company']->name);
        $mpdf->SetCreator('Company Subscription System');

        // Add header
        $mpdf->SetHTMLHeader('
        <div style="text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px;">
            <span style="font-size: 10px; color: #666;">Invoice ' . $data['invoice']->reference . ' - ' . $data['company']->name . '</span>
        </div>');

        // Add footer with page numbers
        $mpdf->SetHTMLFooter('
        <div style="text-align: center; font-size: 8px; color: #666; border-top: 1px solid #ddd; padding-top: 5px;">
            Page {PAGENO} of {nbpg} | Generated on ' . $data['generated_at'] . '
        </div>');

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Output PDF for download
        return $mpdf->Output($fileName, 'D');
    }

    /**
     * Get currency symbol
     */
    private function getCurrencySymbol($currency)
    {
        if (!$currency) {
            return 'FRW'; // Default to FRW (Rwandan Franc)
        }

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'KES' => 'KSh',
            'TZS' => 'TSh',
            'UGX' => 'USh',
            'RWF' => 'FRW',
            'FRW' => 'FRW',
        ];

        return $symbols[$currency->code] ?? $currency->code;
    }
}
