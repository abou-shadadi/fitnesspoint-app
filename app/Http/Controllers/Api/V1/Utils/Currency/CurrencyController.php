<?php

namespace App\Http\Controllers\Api\V1\Utils\Currency;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Currency;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Currency",
 *     required={"name", "status"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="USD"
 *     ),
 *     @OA\Property(
 *         property="symbol",
 *         type="string",
 *         example="$"
 *     ),
 *     @OA\Property(
 *         property="is_default",
 *         type="boolean",
 *         example="0"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class CurrencyController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/currencies",
     *     tags={"Utils | Currency"},
     *     security={
     *         {"sanctum": {}},
     *     },
     *     summary="Get a list of currencies",
     *     description="Returns a list of all currencies.",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $currencies = Currency::orderBy('name', 'asc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Currencies retrieved successfully',
                'data' => $currencies
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
     *     path="/api/utils/currencies/{id}",
     *     tags={"Utils | Currency"},
     *     summary="Get a currency by ID",
     *     description="Returns a single currency based on the provided ID.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the currency",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Currency not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $currency = Currency::find($id);

            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Currency retrieved successfully',
                'data' => $currency
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
     *     path="/api/utils/currencies",
     *     tags={"Utils | Currency"},
     *     summary="Create a new currency",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Creates a new currency with the provided data.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Currency")
     *     ),
     *     @OA\Response(response=201, description="Currency created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:currencies',
            'symbol' => 'nullable|string',
            'is_default' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $currency = Currency::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Currency created successfully',
                'data' => $currency
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
     * @OA\Put(
     *     path="/api/utils/currencies/{id}",
     *     tags={"Utils | Currency"},
     *     summary="Update an existing currency",
     *     description="Updates an existing currency with the provided data.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the currency",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Currency")
     *     ),
     *     @OA\Response(response=200, description="Currency updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Currency not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:currencies,name,' . $id,
            'symbol' => 'nullable|string',
            'is_default' => 'boolean',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $currency = Currency::find($id);

            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found',
                    'data' => null
                ], 404);
            }

            $currency->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Currency updated successfully',
                'data' => $currency
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
     *     path="/api/utils/currencies/{id}",
     *     tags={"Utils | Currency"},
     *     summary="Delete a currency",
     *     description="Deletes a currency by ID.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the currency",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Currency deleted successfully"),
     *     @OA\Response(response=404, description="Currency not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $currency = Currency::find($id);

            if (!$currency) {
                return response()->json([
                    'success' => false,
                    'message' => 'Currency not found',
                    'data' => null
                ], 404);
            }

            $currency->delete();

            return response()->json([
                'success' => true,
                'message' => 'Currency deleted successfully',
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
