<?php

namespace App\Http\Controllers\Api\V1\Company\Subscription\Invoice\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanySubscription;
use App\Models\Company\CompanySubscriptionInvoice;
use App\Models\Company\CompanySubscriptionMemberCheckIn;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;
use finfo; // Add this line
use Illuminate\Support\Facades\Log;

class CompanySubscriptionInvoiceCheckInController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins",
     *     summary="List check-ins for an invoice",
     *     tags={"Companies | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
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
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "completed", "failed"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="check_in_method_id",
     *         in="query",
     *         required=false,
     *         description="Filter by check-in method",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         required=false,
     *         description="Filter by member ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"date", "member_name", "check_in_method", "status", "datetime"})
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
     *         description="List of check-ins for invoice",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-ins retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="check_ins",
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
     *                     @OA\Property(property="total_days", type="integer"),
     *                     @OA\Property(property="total_check_ins", type="integer"),
     *                     @OA\Property(property="total_completed", type="integer"),
     *                     @OA\Property(property="total_failed", type="integer"),
     *                     @OA\Property(property="total_pending", type="integer"),
     *                     @OA\Property(property="unique_members", type="integer"),
     *                     @OA\Property(
     *                         property="daily_summary",
     *                         type="array",
     *                         @OA\Items(
     *                             type="object",
     *                             @OA\Property(property="date", type="string", format="date"),
     *                             @OA\Property(property="total_check_ins", type="integer"),
     *                             @OA\Property(property="completed", type="integer"),
     *                             @OA\Property(property="failed", type="integer"),
     *                             @OA\Property(property="pending", type="integer"),
     *                             @OA\Property(property="unique_members", type="integer")
     *                         )
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Company, subscription, or invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $companyId, $subscriptionId, $invoiceId)
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
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get invoice date range
            $fromDate = Carbon::parse($invoice->from_date);
            $toDate = Carbon::parse($invoice->to_date);

            // Base query for check-ins within invoice date range
            $query = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($q) use ($subscriptionId) {
                $q->where('company_subscription_id', $subscriptionId);
            })
                ->whereBetween('datetime', [$fromDate, $toDate])
                ->with([
                    'company_subscription_member.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ]);

            // Apply filters
            // Date range filter (overrides invoice date range if provided)
            if ($request->has('date_from') && $request->date_from) {
                $query->whereDate('datetime', '>=', $request->date_from);
            } else {
                $query->whereDate('datetime', '>=', $fromDate->format('Y-m-d'));
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('datetime', '<=', $request->date_to);
            } else {
                $query->whereDate('datetime', '<=', $toDate->format('Y-m-d'));
            }

            // Status filter
            if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'failed'])) {
                $query->where('status', $request->status);
            }

            // Check-in method filter
            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $query->where('check_in_method_id', $request->check_in_method_id);
            }

            // Member filter
            if ($request->has('member_id') && $request->member_id) {
                $query->whereHas('company_subscription_member', function ($q) use ($request) {
                    $q->where('member_id', $request->member_id);
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'datetime');
            $sortOrder = $request->input('sort_order', 'desc');

            // Map sort fields
            $sortMap = [
                'date' => 'datetime',
                'member_name' => 'company_subscription_member.member.name',
                'check_in_method' => 'check_in_method_id',
                'status' => 'status',
                'datetime' => 'datetime'
            ];

            $sortField = $sortMap[$sortBy] ?? $sortBy;

            if ($sortField === 'company_subscription_member.member.name') {
                $query->join('company_subscription_members', 'company_subscription_member_check_ins.company_subscription_member_id', '=', 'company_subscription_members.id')
                    ->join('members', 'company_subscription_members.member_id', '=', 'members.id')
                    ->orderBy('members.name', $sortOrder)
                    ->select('company_subscription_member_check_ins.*');
            } else {
                $query->orderBy($sortField, $sortOrder);
            }

            // Get all check-ins for summary calculation
            $allCheckIns = $query->get();

            // Calculate summary statistics
            $summary = $this->calculateSummary($allCheckIns, $fromDate, $toDate);

            // For pagination, we need to re-execute the query
            $paginationQuery = clone $query;

            // Pagination
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
            $checkIns = $paginationQuery->paginate($perPage);

            // Transform data for response
            $checkIns->getCollection()->transform(function ($checkIn) {
                return [
                    'id' => $checkIn->id,
                    'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                    'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                    'datetime' => $checkIn->datetime,
                    'member_id' => $checkIn->company_subscription_member->member_id,
                    'member_name' => $checkIn->company_subscription_member->member->name ?? 'N/A',
                    'check_in_method_id' => $checkIn->check_in_method_id,
                    'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                    'status' => $checkIn->status,
                    'branch_id' => $checkIn->branch_id,
                    'branch_name' => $checkIn->branch->name ?? 'N/A',
                    'notes' => $checkIn->notes,
                    'created_by_id' => $checkIn->created_by_id,
                    'created_by_name' => $checkIn->created_by->name ?? 'N/A',
                    'signature_url' => $checkIn->signature ? Storage::url($checkIn->signature) : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Check-ins retrieved successfully',
                'data' => [
                    'check_ins' => $checkIns,
                    'summary' => $summary,
                    'invoice_info' => [
                        'id' => $invoice->id,
                        'reference' => $invoice->reference,
                        'from_date' => $invoice->from_date->format('Y-m-d'),
                        'to_date' => $invoice->to_date->format('Y-m-d'),
                        'total_member_check_ins' => $invoice->total_member_check_ins,
                        'amount' => $invoice->amount,
                        'total_amount' => $invoice->total_amount,
                        'status' => $invoice->status,
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
     * @OA\Get(
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins/{id}",
     *     summary="Get specific check-in for invoice",
     *     tags={"Companies | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
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
     *         description="Check-in details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Check-in retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="time", type="string", format="time"),
     *                 @OA\Property(property="datetime", type="string", format="date-time"),
     *                 @OA\Property(property="member_id", type="integer"),
     *                 @OA\Property(property="member_name", type="string"),
     *                 @OA\Property(property="check_in_method_id", type="integer"),
     *                 @OA\Property(property="check_in_method", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="branch_id", type="integer"),
     *                 @OA\Property(property="branch_name", type="string"),
     *                 @OA\Property(property="notes", type="string"),
     *                 @OA\Property(property="created_by_id", type="integer"),
     *                 @OA\Property(property="created_by_name", type="string"),
     *                 @OA\Property(property="signature_url", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Check-in not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $subscriptionId, $invoiceId, $id)
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
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get invoice date range
            $fromDate = Carbon::parse($invoice->from_date);
            $toDate = Carbon::parse($invoice->to_date);

            // Get the check-in
            $checkIn = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($q) use ($subscriptionId) {
                $q->where('company_subscription_id', $subscriptionId);
            })
                ->whereBetween('datetime', [$fromDate, $toDate])
                ->with([
                    'company_subscription_member.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ])
                ->find($id);

            if (!$checkIn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in not found for this invoice period',
                    'data' => null
                ], 404);
            }

            $checkInData = [
                'id' => $checkIn->id,
                'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                'datetime' => $checkIn->datetime,
                'member_id' => $checkIn->company_subscription_member->member_id,
                'member_name' => $checkIn->company_subscription_member->member->name ?? 'N/A',
                'check_in_method_id' => $checkIn->check_in_method_id,
                'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                'status' => $checkIn->status,
                'branch_id' => $checkIn->branch_id,
                'branch_name' => $checkIn->branch->name ?? 'N/A',
                'notes' => $checkIn->notes,
                'created_by_id' => $checkIn->created_by_id,
                'created_by_name' => $checkIn->created_by->name ?? 'N/A',
                'signature_url' => $checkIn->signature ? Storage::url($checkIn->signature) : null,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Check-in retrieved successfully',
                'data' => $checkInData
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
     *     path="/api/companies/{companyId}/subscriptions/{subscriptionId}/invoices/{invoiceId}/check-ins/export",
     *     summary="Export check-ins to PDF",
     *     tags={"Companies | Subscriptions | Invoices | Check-ins"},
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
     *         name="invoiceId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
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
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"pending", "completed", "failed"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="check_in_method_id",
     *         in="query",
     *         required=false,
     *         description="Filter by check-in method",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="member_id",
     *         in="query",
     *         required=false,
     *         description="Filter by member ID",
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
     *     @OA\Response(response=404, description="Company, subscription, or invoice not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function export(Request $request, $companyId, $subscriptionId, $invoiceId)
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
                ->with(['company', 'billing_type', 'currency'])
                ->find($subscriptionId);

            if (!$subscription) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company subscription not found',
                    'data' => null
                ], 404);
            }

            // Verify invoice exists and belongs to subscription
            $invoice = CompanySubscriptionInvoice::where('company_subscription_id', $subscriptionId)
                ->with(['rate_type', 'tax_rate', 'currency'])
                ->find($invoiceId);

            if (!$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found',
                    'data' => null
                ], 404);
            }

            // Get invoice date range
            $fromDate = Carbon::parse($invoice->from_date);
            $toDate = Carbon::parse($invoice->to_date);

            // Get all check-ins with filters (same as index method)
            $query = CompanySubscriptionMemberCheckIn::whereHas('company_subscription_member', function ($q) use ($subscriptionId) {
                $q->where('company_subscription_id', $subscriptionId);
            })
                ->whereBetween('datetime', [$fromDate, $toDate])
                ->with([
                    'company_subscription_member.member',
                    'check_in_method',
                    'branch',
                    'created_by'
                ]);

            // Apply filters
            if ($request->has('date_from') && $request->date_from) {
                //      $query->whereDate('datetime', '>=', $request->date_from);
            }

            if ($request->has('date_to') && $request->date_to) {
                $query->whereDate('datetime', '<=', $request->date_to);
            }

            if ($request->has('status') && in_array($request->status, ['pending', 'completed', 'failed'])) {
                $query->where('status', $request->status);
            }

            if ($request->has('check_in_method_id') && $request->check_in_method_id) {
                $query->where('check_in_method_id', $request->check_in_method_id);
            }

            if ($request->has('member_id') && $request->member_id) {
                $query->whereHas('company_subscription_member', function ($q) use ($request) {
                    $q->where('member_id', $request->member_id);
                });
            }

            $query->orderBy('datetime', 'desc');
            $checkIns = $query->get();

            $exportData = $checkIns->map(function ($checkIn) {
                // Get signature as base64 for PDF
                $signatureBase64 = null;
                if (!empty($checkIn->signature)) {
                    try {
                        $signaturePath = $checkIn->signature;

                        // Method 1: Check if it's in storage
                        if (Storage::exists($signaturePath)) {
                            $mimeType = Storage::mimeType($signaturePath);
                            $imageData = base64_encode(Storage::get($signaturePath));
                            $signatureBase64 = 'data:' . $mimeType . ';base64,' . $imageData;
                        }
                        // Method 2: Check if it's a full URL
                        elseif (filter_var($signaturePath, FILTER_VALIDATE_URL)) {
                            // Use Guzzle or CURL instead of file_get_contents for better reliability
                            $client = new \GuzzleHttp\Client([
                                'timeout' => 5,
                                'verify' => false, // Disable SSL verification for local/dev
                            ]);

                            try {
                                $response = $client->get($signaturePath);
                                if ($response->getStatusCode() === 200) {
                                    $imageData = $response->getBody()->getContents();
                                    // Try to detect mime type from content
                                    $mimeType = $this->detectMimeTypeFromContent($imageData);
                                    $signatureBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                                }
                            } catch (\Exception $e) {
                                // Log and continue
                                Log::warning('Failed to download signature from URL: ' . $e->getMessage());
                            }
                        }
                        // Method 3: Check if it's a local public file
                        elseif (file_exists(public_path($signaturePath))) {
                            $filePath = public_path($signaturePath);
                            $imageData = file_get_contents($filePath);
                            $mimeType = mime_content_type($filePath);
                            $signatureBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        }
                    } catch (\Exception $e) {
                        //Log::warning('Failed to load signature for check-in ' . $checkIn->id . ': ' . $e->getMessage());
                        $signatureBase64 = null;
                    }
                }

                $member = $checkIn->company_subscription_member->member ?? null;

                $memberFullName = collect([
                    $member->first_name ?? null,
                    $member->last_name ?? null,
                ])->filter()->implode(' ');

                return [
                    'date' => Carbon::parse($checkIn->datetime)->format('Y-m-d'),
                    'time' => Carbon::parse($checkIn->datetime)->format('H:i:s'),
                    'member_name' => $memberFullName ?: 'N/A',
                    'check_in_method' => $checkIn->check_in_method->name ?? 'N/A',
                    'status' => $checkIn->status,
                    'branch' => $checkIn->branch->name ?? 'N/A',
                    'notes' => $checkIn->notes ?? '',
                    'created_by' => $checkIn->created_by->name ?? 'N/A',
                    'has_signature' => !empty($checkIn->signature),
                    'signature_base64' => $signatureBase64,
                    'signature_path' => $checkIn->signature,
                ];
            })->toArray();

            // return response()->json([
            //     'data' => $exportData
            // ]);

            // Calculate summary
            $summary = $this->calculateSummary($checkIns, $fromDate, $toDate);

            // Generate PDF using mPDF and Blade template
            return $this->generatePdf($exportData, $summary, $company, $subscription, $invoice);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Calculate summary statistics - UPDATED for per-pass billing (only completed check-ins)
     */
    private function calculateSummary($checkIns, Carbon $fromDate, Carbon $toDate): array
    {
        // For per-pass billing: Count unique members per day (only completed check-ins)
        $dailyData = [];
        $uniqueMembers = [];
        $memberDates = [];
        $totalCheckInsCount = 0;

        foreach ($checkIns as $checkIn) {
            $date = Carbon::parse($checkIn->datetime)->format('Y-m-d');
            $memberId = $checkIn->company_subscription_member->member_id;

            // Initialize daily data if not exists
            if (!isset($dailyData[$date])) {
                $dailyData[$date] = [
                    'date' => $date,
                    'total_check_ins' => 0,
                    'unique_member_check_ins' => 0, // Unique members per day (for billing)
                    'completed' => 0,
                    'failed' => 0,
                    'pending' => 0,
                    'unique_members' => 0,
                    'members' => []
                ];
            }

            // Count total check-ins (for reporting purposes)
            $dailyData[$date]['total_check_ins']++;
            $totalCheckInsCount++;

            // Count by status
            $dailyData[$date][$checkIn->status]++;

            // Track unique members per day (ONLY FOR COMPLETED CHECK-INS)
            $memberKey = $date . '_' . $memberId;

            // Only count for billing if check-in is completed
            if ($checkIn->status === 'completed' && !isset($memberDates[$memberKey])) {
                $memberDates[$memberKey] = true;
                $dailyData[$date]['members'][$memberId] = true;
                $dailyData[$date]['unique_members'] = count($dailyData[$date]['members']);
                $dailyData[$date]['unique_member_check_ins']++; // Increment unique member count for this day
            }

            // Track overall unique members (all statuses for reporting)
            if (!in_array($memberId, $uniqueMembers)) {
                $uniqueMembers[] = $memberId;
            }
        }

        // Sort daily data by date
        ksort($dailyData);
        $dailySummary = array_values($dailyData);

        // Calculate totals
        $totalCompleted = $checkIns->where('status', 'completed')->count();
        $totalFailed = $checkIns->where('status', 'failed')->count();
        $totalPending = $checkIns->where('status', 'pending')->count();

        // Calculate unique member check-ins across all days (only completed for billing)
        $totalUniqueMemberCheckIns = count($memberDates);

        // Calculate total days in range
        $totalDays = $fromDate->diffInDays($toDate) + 1;

        return [
            'total_days' => $totalDays,
            'total_check_ins' => $totalCheckInsCount, // Total check-ins (including all statuses)
            'unique_member_check_ins' => $totalUniqueMemberCheckIns, // Unique members per day (only completed)
            'total_completed' => $totalCompleted,
            'total_failed' => $totalFailed,
            'total_pending' => $totalPending,
            'unique_members' => count($uniqueMembers), // Overall unique members (all statuses)
            'daily_summary' => $dailySummary,
        ];
    }

    /**
     * Generate PDF using mPDF and Blade template
     */
    private function generatePdf($data, $summary, $company, $subscription, $invoice)
    {
        $fileName = 'checkins_invoice_' . $invoice->reference . '_' . date('Ymd_His') . '.pdf';

        // Render Blade template
        $html = view('exports.company.invoice.checkins', [
            'data' => $data,
            'summary' => $summary,
            'company' => $company,
            'subscription' => $subscription,
            'invoice' => $invoice,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ])->render();

        // Create mPDF instance
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L', // Landscape orientation
            'default_font_size' => 10,
            'default_font' => 'dejavusans', // Supports UTF-8
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 15,
            'margin_bottom' => 15,
            'margin_header' => 5,
            'margin_footer' => 5,
            'enable_remote' => true
        ]);

        // Set document information
        $mpdf->SetTitle('Check-ins Report - Invoice ' . $invoice->reference);
        $mpdf->SetAuthor('System');
        $mpdf->SetCreator('Company Subscription System');

        // Add header
        $mpdf->SetHTMLHeader('
        <div style="text-align: center; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px;">
            <span style="font-size: 12px; color: #666;">Check-ins Report - Invoice ' . $invoice->reference . '</span>
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
