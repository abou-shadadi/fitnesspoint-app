<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Feature;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Feature",
 *     required={"id", "name", "key"},
 *     @OA\Property(property="id", type="integer", format="int64", description="The ID of the feature"),
 *     @OA\Property(property="name", type="string", description="The name of the feature"),
 *     @OA\Property(property="key", type="string", description="The key of the feature"),
 *     @OA\Property(property="description", type="string", nullable=true, description="The description of the feature"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the feature (active/inactive)")
 * )
 */
class FeatureController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/features",
     *   tags={"Features"},
     *   summary="Get all features",
     * security={{"sanctum": {}}},
     *   description="Retrieve all features.",
     *   @OA\Response(
     *     response=200,
     *     description="Features retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Features retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Feature")
     *       )
     *     )
     *   )
     * )
     */
    public function index()
    {
        $features = Feature::with(['roles'])->get();
        return response()->json([
            'success' => true,
            'message' => 'Features retrieved successfully',
            'data' => $features,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/features/{id}",
     *   tags={"Features"},
     *   security={{"sanctum": {}}},
     *   summary="Get a specific feature",
     *   description="Retrieve a specific feature by ID.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID of the feature",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Feature retrieved successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Feature")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Feature not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Feature not found")
     *     )
     *   )
     * )
     */
    public function show($id)
    {
        $feature = Feature::find($id);
        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Feature retrieved successfully',
            'data' => $feature,
        ]);
    }



    /**
     * @OA\Put(
     *   path="/api/features/{id}",
     *   tags={"Features"},
     *   summary="Update a specific feature",
     * security={{"sanctum": {}}},
     *   description="Update the specified feature by ID.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID of the feature",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Feature details to update",
     *     @OA\JsonContent(
     *       required={"name", "key"},
     *       @OA\Property(property="name", type="string", description="The name of the feature"),
     *       @OA\Property(property="key", type="string", description="The key of the feature"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the feature"),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the feature (active/inactive)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Feature updated successfully",
     *     @OA\JsonContent(ref="#/components/schemas/Feature")
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Feature not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Feature not found")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Validation error"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */

    public function update(Request $request, $id)
    {
        $feature = Feature::find($id);
        if (!$feature) {
            return response()->json([
                'success' => false,
                'message' => 'Feature not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string',
            'key' => 'sometimes|string|unique:features,key,' . $id,
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $feature->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Feature updated successfully',
            'data' => $feature,
        ]);
    }
}
