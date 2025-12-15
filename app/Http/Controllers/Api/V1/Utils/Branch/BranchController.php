<?php

namespace App\Http\Controllers\Api\V1\Utils\Branch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch\Branch;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Branch",
 *     required={"name", "status"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Branch name"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Branch description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class BranchController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/branches",
     *     tags={"Utils | Branch"},
     *     summary="Get a list of branches",
     *   security={{"sanctum": {}}},
     *     description="Returns a list of all branches.",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $branchs = Branch::all();

            return response()->json([
                'success' => true,
                'message' => 'Branchs retrieved successfully',
                'data' => $branchs
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
     *     path="/api/utils/branches/{id}",
     *     tags={"Utils | Branch"},
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
     *     @OA\Response(response=404, description="Branch not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $branch = Branch::find($id);

            if (!$branch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Branch not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Branch retrieved successfully',
                'data' => $branch
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
     *     path="/api/utils/branches",
     *     tags={"Utils | Branch"},
     *     summary="Create a new company type",
     *   security={{"sanctum": {}}},
     *     description="Creates a new company type with the provided data.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Branch")
     *     ),
     *     @OA\Response(response=201, description="Branch created successfully"),
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
            $branch = Branch::create($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Branch created successfully',
                'data' => $branch
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
     *     path="/api/utils/branches/{id}",
     *     tags={"Utils | Branch"},
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
     *         @OA\JsonContent(ref="#/components/schemas/Branch")
     *     ),
     *     @OA\Response(response=200, description="Branch updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Branch not found"),
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
            $branch = Branch::updateOrCreate(['id' => $id], $validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'Branch updated successfully',
                'data' => $branch
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
     *     path="/api/utils/branches/{id}",
     *     tags={"Utils | Branch"},
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
     *     @OA\Response(response=200, description="Branch deleted successfully"),
     *     @OA\Response(response=404, description="Branch not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $branch = Branch::findOrFail($id);
            $branch->delete();

            return response()->json([
                'success' => true,
                'message' => 'Branch deleted successfully',
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
