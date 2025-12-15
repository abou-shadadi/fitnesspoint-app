<?php

namespace App\Http\Controllers\Api\V1\Company\Designation;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\CompanyDesignation;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="CompanyDesignation",
 *     required={"name", "status"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Company designation name"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Company designation description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class CompanyDesignationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/company/designations",
     *     tags={"Company | Designations"},
     *     summary="Get a list of company designations",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Returns a list of all company designations.",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $companyDesignations = CompanyDesignation::all();

            return response()->json([
                'success' => true,
                'message' => 'Company designations retrieved successfully',
                'data' => $companyDesignations
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
     *     path="/api/company/designations/{id}",
     *     tags={"Company | Designations"},
     *     summary="Get a company designation by ID",
     *     description="Returns a single company designation based on the provided ID.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company designation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Company designation not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $companyDesignation = CompanyDesignation::find($id);

            if (!$companyDesignation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company designation not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company designation retrieved successfully',
                'data' => $companyDesignation
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
     *     path="/api/company/designations",
     *     tags={"Company | Designations"},
     *     summary="Create a new company designation",
     *     description="Creates a new company designation with the provided data.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyDesignation")
     *     ),
     *     @OA\Response(response=201, description="Company designation created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:company_designations,name',
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
            $companyDesignation = CompanyDesignation::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Company designation created successfully',
                'data' => $companyDesignation
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
     *     path="/api/company/designations/{id}",
     *     tags={"Company | Designations"},
     *     summary="Update an existing company designation",
     *     description="Updates an existing company designation with the provided data.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company designation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyDesignation")
     *     ),
     *     @OA\Response(response=200, description="Company designation updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company designation not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:company_designations,name,' . $id,
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
            $companyDesignation = CompanyDesignation::updateOrCreate(['id' => $id], $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Company designation updated successfully',
                'data' => $companyDesignation
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
     *     path="/api/company/designations/{id}",
     *     tags={"Company | Designations"},
     *     summary="Delete a company designation",
     *     description="Deletes a company designation by ID.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company designation",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company designation deleted successfully"),
     *     @OA\Response(response=404, description="Company designation not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $companyDesignation = CompanyDesignation::find($id);
            if (!$companyDesignation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company designation not found',
                    'data' => null
                ], 404);
            }

            $companyDesignation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company designation deleted successfully',
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
