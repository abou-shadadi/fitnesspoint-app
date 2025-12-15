<?php

namespace App\Http\Controllers\Api\V1\Utils\Benefit;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Benefit\Benefit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Schema(
 *     schema="Benefit",
 *     title="Benefit",
 *     description="Benefit schema",
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active","inactive"},
 *         default="active"
 *     )
 * )
 */
class BenefitController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/benefits",
     *     tags={"Utils | Benefits"},
     *     summary="List all benefits",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index()
    {
        try {
            $benefits = Benefit::all();

            return response()->json([
                "success" => true,
                "data" => $benefits
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/utils/benefits",
     *     tags={"Utils | Benefits"},
     *     summary="Create a benefit",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Benefit")
     *     ),
     *     @OA\Response(response=201, description="Created successfully")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name" => "required|string",
                "description" => "nullable|string",
                "status" => "required|in:active,inactive",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "success" => false,
                    "message" => $validator->errors()->first()
                ], 400);
            }

            $benefit = Benefit::create($request->all());

            return response()->json([
                "success" => true,
                "message" => "Benefit created successfully",
                "data" => $benefit
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/utils/benefits/{id}",
     *     tags={"Utils | Benefits"},
     *     summary="Show a benefit",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, description="Benefit ID"),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            $benefit = Benefit::findOrFail($id);

            return response()->json([
                "success" => true,
                "data" => $benefit
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Benefit not found"
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utils/benefits/{id}",
     *     tags={"Utils | Benefits"},
     *     summary="Update a benefit",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Benefit")),
     *     @OA\Response(response=200, description="Updated successfully")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                "name" => "required|string",
                "description" => "nullable|string",
                "status" => "required|in:active,inactive",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "success" => false,
                    "message" => $validator->errors()->first()
                ], 400);
            }

            $benefit = Benefit::findOrFail($id);
            $benefit->update($request->all());

            return response()->json([
                "success" => true,
                "message" => "Benefit updated successfully",
                "data" => $benefit
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Benefit not found"
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/utils/benefits/{id}",
     *     tags={"Utils | Benefits"},
     *     summary="Delete a benefit",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Deleted successfully")
     * )
     */
    public function destroy($id)
    {
        try {
            $benefit = Benefit::findOrFail($id);
            $benefit->delete();

            return response()->json([
                "success" => true,
                "message" => "Benefit deleted successfully"
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Benefit not found"
            ], 404);
        }
    }
}
