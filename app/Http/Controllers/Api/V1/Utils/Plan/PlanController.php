<?php

namespace App\Http\Controllers\Api\V1\Utils\Plan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plan\Plan;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Plan",
 *     title="Plan",
 *     description="Plan schema",
 *     @OA\Property(property="label", type="string"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(property="price", type="number", format="float"),
 *     @OA\Property(property="currency_id", type="integer"),
 *     @OA\Property(property="duration", type="integer", nullable=true),
 *     @OA\Property(property="duration_type_id", type="integer"),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active","inactive"}
 *     )
 * )
 */
class PlanController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/plans",
     *     tags={"Utils | Plans"},
     *     summary="List all plans",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response"
     *     )
     * )
     */
    public function index()
    {
        try {
            $plans = Plan::with(['currency', 'duration_type', 'benefits'])->get();

            return response()->json([
                "success" => true,
                "data" => $plans
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "success" => false,
                "message" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/utils/plans",
     *     tags={"Utils | Plans"},
     *     summary="Create a plan",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Plan")
     *     ),
     *     @OA\Response(response=201, description="Created")
     * )
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "label" => "required|string",
                "description" => "nullable|string",
                "price" => "required|numeric",
                "currency_id" => "required|exists:currencies,id",
                "duration" => "nullable|integer",
                "duration_type_id" => "required|exists:duration_types,id",
                "status" => "required|in:active,inactive",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "success" => false,
                    "message" => $validator->errors()->first()
                ], 400);
            }

            $plan = Plan::create($request->all());

            return response()->json([
                "success" => true,
                "message" => "Plan created successfully",
                "data" => $plan
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
     *     path="/api/utils/plans/{id}",
     *     tags={"Utils | Plans"},
     *     summary="Get single plan",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show($id)
    {
        try {
            $plan = Plan::with(['currency', 'duration_type', 'benefits'])->findOrFail($id);

            return response()->json([
                "success" => true,
                "data" => $plan
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Plan not found"
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utils/plans/{id}",
     *     tags={"Utils | Plans"},
     *     summary="Update a plan",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/Plan")),
     *     @OA\Response(response=200, description="Updated")
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                "label" => "required|string",
                "description" => "nullable|string",
                "price" => "required|numeric",
                "currency_id" => "required|exists:currencies,id",
                "duration" => "nullable|integer",
                "duration_type_id" => "required|exists:duration_types,id",
                "status" => "required|in:active,inactive",
            ]);

            if ($validator->fails()) {
                return response()->json([
                    "success" => false,
                    "message" => $validator->errors()->first()
                ], 400);
            }

            $plan = Plan::findOrFail($id);
            $plan->update($request->all());

            return response()->json([
                "success" => true,
                "message" => "Plan updated successfully",
                "data" => $plan
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Plan not found"
            ], 404);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/utils/plans/{id}",
     *     tags={"Utils | Plans"},
     *     summary="Delete a plan",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true),
     *     @OA\Response(response=200, description="Deleted")
     * )
     */
    public function destroy($id)
    {
        try {
            $plan = Plan::findOrFail($id);
            $plan->delete();

            return response()->json([
                "success" => true,
                "message" => "Plan deleted successfully"
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                "success" => false,
                "message" => "Plan not found"
            ], 404);
        }
    }
}
