<?php

namespace App\Http\Controllers\Api\V1\Utils\Duration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Duration\DurationType;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="DurationType",
 *     title="Duration Type",
 *     description="Schema for duration type",
 *     @OA\Property(property="name", type="string", description="Name of the duration type"),
 *     @OA\Property(
 *         property="unit",
 *         type="string",
 *         description="Unit of time",
 *         enum={"days", "weeks", "months", "years"}
 *     ),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         default="active"
 *     )
 * )
 */
class DurationTypeController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/utils/duration-types",
     *     tags={"Utils | Duration Types"},
     *     summary="List all duration types",
     *     description="Retrieve all duration types available.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DurationType"))
     *     )
     * )
     */
    public function index()
    {
        try {
            $items = DurationType::all();
            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/utils/duration-types",
     *     tags={"Utils | Duration Types"},
     *     summary="Create a duration type",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/DurationType")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/DurationType")
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'unit' => 'required|in:days,weeks,months,years',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $item = DurationType::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Duration type created successfully',
                'data' => $item
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/utils/duration-types/{id}",
     *     tags={"Utils | Duration Types"},
     *     summary="Show a duration type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(ref="#/components/schemas/DurationType")),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            $item = DurationType::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $item
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Duration type not found'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utils/duration-types/{id}",
     *     tags={"Utils | Duration Types"},
     *     summary="Update a duration type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/DurationType")),
     *     @OA\Response(response=200, description="Updated successfully")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'unit' => 'required|in:days,weeks,months,years',
                'description' => 'nullable|string',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $item = DurationType::findOrFail($id);
            $item->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Duration type updated successfully',
                'data' => $item
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Duration type not found'
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/utils/duration-types/{id}",
     *     tags={"Utils | Duration Types"},
     *     summary="Delete a duration type",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Deleted successfully")
     * )
     */
    public function destroy($id)
    {
        try {
            $item = DurationType::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Duration type deleted successfully'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Duration type not found'
            ], 404);
        }
    }
}
