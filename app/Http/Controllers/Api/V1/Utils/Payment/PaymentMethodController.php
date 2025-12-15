<?php

namespace App\Http\Controllers\Api\V1\Utils\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PaymentMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/payment-methods",
     *     summary="List all payment methods",
     *     tags={"Utils | Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filter by status",
     *         @OA\Schema(
     *             type="string",
     *             enum={"active", "inactive"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         required=false,
     *         description="Search by name or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of payment methods"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = PaymentMethod::query();

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }

            // Search functionality
            if ($request->has('search') && $request->search) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%");
                });
            }

            // Order by latest
            $query->orderBy('created_at', 'desc');

            $paymentMethods = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Payment methods retrieved successfully',
                'data' => $paymentMethods
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
     *     path="/api/utils/payment-methods",
     *     summary="Create a new payment method",
     *     tags={"Utils | Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Credit Card"),
     *             @OA\Property(property="description", type="string", example="Payment via credit or debit card"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Payment method created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:payment_methods,name',
            'description' => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive'])
            ]
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
            // Create payment method
            $paymentMethod = PaymentMethod::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status', 'active')
            ]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment method created successfully',
                'data' => $paymentMethod
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
     *     path="/api/utils/payment-methods/{id}",
     *     summary="Get specific payment method",
     *     tags={"Utils | Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payment method details"),
     *     @OA\Response(response=404, description="Payment method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $paymentMethod = PaymentMethod::find($id);

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment method retrieved successfully',
                'data' => $paymentMethod
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
     *     path="/api/utils/payment-methods/{id}",
     *     summary="Update payment method",
     *     tags={"Utils | Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Updated Payment Method"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Payment method updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Payment method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        // Find payment method
        $paymentMethod = PaymentMethod::find($id);

        if (!$paymentMethod) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:payment_methods,name,' . $id,
            'description' => 'nullable|string',
            'status' => [
                'nullable',
                Rule::in(['active', 'inactive'])
            ]
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
            if ($request->has('name')) $updateData['name'] = $request->input('name');
            if ($request->has('description')) $updateData['description'] = $request->input('description');
            if ($request->has('status')) $updateData['status'] = $request->input('status');

            // Update payment method
            $paymentMethod->update($updateData);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment method updated successfully',
                'data' => $paymentMethod
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
     *     path="/api/utils/payment-methods/{id}",
     *     summary="Delete payment method",
     *     tags={"Utils | Payment Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Payment method deleted successfully"),
     *     @OA\Response(response=404, description="Payment method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $paymentMethod = PaymentMethod::find($id);

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                $paymentMethod->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment method deleted successfully',
                    'data' => null
                ], 200);
            } catch (\Exception $e) {
                // Rollback transaction on error
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
}
