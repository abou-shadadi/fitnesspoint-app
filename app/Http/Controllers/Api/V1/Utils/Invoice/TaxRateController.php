<?php

namespace App\Http\Controllers\Api\V1\Utils\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice\TaxRate;
use App\Models\Rate\RateType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TaxRateController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/tax-rates",
     *     summary="Get all invoice tax rates",
     *     tags={"Utils | Invoice Tax Rates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="rate_type_id",
     *         in="query",
     *         required=false,
     *         description="Filter by rate type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by name or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         required=false,
     *         description="Sort field",
     *         @OA\Schema(type="string", enum={"name", "rate", "created_at", "updated_at"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         required=false,
     *         description="Sort order",
     *         @OA\Schema(type="string", enum={"asc", "desc"}, default="asc")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=15, enum={10, 15, 25, 50, 100})
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Page number",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of invoice tax rates",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice tax rates retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="tax_rates",
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
     *                     @OA\Property(property="active", type="integer"),
     *                     @OA\Property(property="inactive", type="integer"),
     *                     @OA\Property(property="average_rate", type="number", format="float")
     *                 ),
     *                 @OA\Property(
     *                     property="rate_types",
     *                     type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = TaxRate::with('rate_type');

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }

            // Filter by rate_type_id
            if ($request->has('rate_type_id') && $request->rate_type_id) {
                $query->where('rate_type_id', $request->rate_type_id);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Sorting
            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->input('per_page', 15);
            $perPage = in_array($perPage, [10, 15, 25, 50, 100]) ? $perPage : 15;
            $taxRates = $query->paginate($perPage);

            // Get summary statistics
            $allTaxRates = TaxRate::when($request->has('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })->when($request->has('rate_type_id'), function ($q) use ($request) {
                $q->where('rate_type_id', $request->rate_type_id);
            })->get();

            $summary = [
                'total' => $allTaxRates->count(),
                'active' => $allTaxRates->where('status', 'active')->count(),
                'inactive' => $allTaxRates->where('status', 'inactive')->count(),
                'average_rate' => $allTaxRates->avg('rate'),
            ];

            // Get rate types for dropdown
            $rateTypes = RateType::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(function ($rateType) {
                    return [
                        'id' => $rateType->id,
                        'name' => $rateType->name
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax rates retrieved successfully',
                'data' => [
                    'tax_rates' => $taxRates,
                    'summary' => $summary,
                    'rate_types' => $rateTypes
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
     *     path="/api/utils/tax-rates",
     *     summary="Create a new invoice tax rate",
     *     tags={"Utils | Invoice Tax Rates"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "rate_type_id", "rate"},
     *             @OA\Property(property="name", type="string", example="VAT"),
     *             @OA\Property(property="description", type="string", example="Value Added Tax", nullable=true),
     *             @OA\Property(property="rate_type_id", type="integer", example=1),
     *             @OA\Property(property="rate", type="number", format="float", example=18.00),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice tax rate created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice tax rate created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Rate type not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:tax_rates,name',
                'description' => 'nullable|string|max:500',
                'rate_type_id' => 'required|exists:rate_types,id',
                'rate' => 'required|numeric|min:0|max:100',
                'status' => ['nullable', Rule::in(['active', 'inactive'])],
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

            $taxRate = TaxRate::create([
                'name' => $request->name,
                'description' => $request->description,
                'rate_type_id' => $request->rate_type_id,
                'rate' => $request->rate,
                'status' => $request->status ?? 'active',
            ]);

            // Load the relationship
            $taxRate->load('rate_type');

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax rate created successfully',
                'data' => $taxRate
            ], 201);
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
     *     path="/api/utils/tax-rates/{id}",
     *     summary="Get a specific invoice tax rate",
     *     tags={"Utils | Invoice Tax Rates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice tax rate details",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice tax rate retrieved successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Invoice tax rate not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $taxRate = TaxRate::with('rate_type')->find($id);

            if (!$taxRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice tax rate not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax rate retrieved successfully',
                'data' => $taxRate
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
     *     path="/api/utils/tax-rates/{id}",
     *     summary="Update an invoice tax rate",
     *     tags={"Utils | Invoice Tax Rates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="VAT Updated"),
     *             @OA\Property(property="description", type="string", example="Updated VAT description"),
     *             @OA\Property(property="rate_type_id", type="integer", example=2),
     *             @OA\Property(property="rate", type="number", format="float", example=20.00),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice tax rate updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice tax rate updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Invoice tax rate or rate type not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $taxRate = TaxRate::find($id);

            if (!$taxRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice tax rate not found',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('tax_rates', 'name')->ignore($taxRate->id)
                ],
                'description' => 'nullable|string|max:500',
                'rate_type_id' => 'required|exists:rate_types,id',
                'rate' => 'required|numeric|min:0|max:100',
                'status' => ['required', Rule::in(['active', 'inactive'])],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 400);
            }

            // Verify rate type exists if provided
            if ($request->has('rate_type_id')) {
                $rateType = RateType::find($request->rate_type_id);
                if (!$rateType) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Rate type not found',
                        'data' => null
                    ], 404);
                }
            }

            $taxRate->update([
                'name' => $request->name,
                'description' => $request->description,
                'rate_type_id' => $request->rate_type_id,
                'rate' => $request->rate,
                'status' => $request->status,
            ]);

            // Refresh with relationships
            $taxRate->refresh();
            $taxRate->load('rate_type');

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax rate updated successfully',
                'data' => $taxRate
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
     * @OA\Delete(
     *     path="/api/utils/tax-rates/{id}",
     *     summary="Delete an invoice tax rate",
     *     tags={"Utils | Invoice Tax Rates"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice tax rate deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Invoice tax rate deleted successfully"),
     *             @OA\Property(property="data", type="null")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Invoice tax rate not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $taxRate = TaxRate::find($id);

            if (!$taxRate) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice tax rate not found',
                    'data' => null
                ], 404);
            }

            // Check if tax rate is being used before deletion
            // You can add checks here if tax rate has related invoice records
            // For example: if ($taxRate->invoices()->exists()) { return error }

            $taxRate->delete();

            return response()->json([
                'success' => true,
                'message' => 'Invoice tax rate deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
