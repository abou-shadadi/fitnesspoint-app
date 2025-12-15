<?php

namespace App\Http\Controllers\Api\V1\Company\Type;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\CompanyType;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="CompanyType",
 *     required={"name", "status"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Company type name"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Company type description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class CompanyTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/company/types",
     *     tags={"Company | Type"},
     *     summary="Get a list of company types",
     *   security={{"sanctum": {}}},
     *     description="Returns a list of all company types.",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $companyTypes = CompanyType::all();

            return response()->json([
                'success' => true,
                'message' => 'Company types retrieved successfully',
                'data' => $companyTypes
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
     *     path="/api/company/types/{id}",
     *     tags={"Company | Type"},
     *     summary="Get a company type by ID",
     *   security={{"sanctum": {}}},
     *     description="Returns a single company type based on the provided ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company type",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Company type not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $companyType = CompanyType::find($id);

            if (!$companyType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company type not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company type retrieved successfully',
                'data' => $companyType
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
     *     path="/api/company/types",
     *     tags={"Company | Type"},
     *     summary="Create a new company type",
     *   security={{"sanctum": {}}},
     *     description="Creates a new company type with the provided data.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyType")
     *     ),
     *     @OA\Response(response=201, description="Company type created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:company_types,name',
            'description' => 'nullable|string',
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
            $companyType = CompanyType::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Company type created successfully',
                'data' => $companyType
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
     *     path="/api/company/types/{id}",
     *     tags={"Company | Type"},
     *     summary="Update an existing company type",
     *     description="Updates an existing company type with the provided data.",
     *   security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company type",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyType")
     *     ),
     *     @OA\Response(response=200, description="Company type updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company type not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:company_types,name,' . $id,
            'description' => 'nullable|string',
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
            $companyType = CompanyType::updateOrCreate(['id' => $id], $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Company type updated successfully',
                'data' => $companyType
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
     *     path="/api/company/types/{id}",
     *     tags={"Company | Type"},
     *     summary="Delete a company type",
     *     description="Deletes a company type by ID.",
     *   security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company type",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company type deleted successfully"),
     *     @OA\Response(response=404, description="Company type not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $companyType = CompanyType::findOrFail($id);
            $companyType->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company type deleted successfully',
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
