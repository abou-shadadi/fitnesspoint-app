<?php

namespace App\Http\Controllers\Api\V1\Utils\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Billing\BillingType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Schema(
 *     schema="BillingType",
 *     required={"name", "key", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Monthly Subscription"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Recurring monthly billing"
 *     ),
 *     @OA\Property(
 *         property="key",
 *         type="string",
 *         example="monthly"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-01T00:00:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-01T00:00:00.000000Z"
 *     )
 * )
 */

class BillingTypeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/billing-types",
     *     tags={"Utils | Billing Types"},
     *     summary="List all billing types",
     *     description="Retrieve all available billing types.",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/BillingType")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $types = BillingType::all();

        return response()->json([
            'success' => true,
            'data' => $types
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/utils/billing-types",
     *     tags={"Utils | Billing Types"},
     *     summary="Create a new billing type",
     *     description="Insert a new billing type.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "key", "status"},
     *             @OA\Property(property="name", type="string", example="Monthly Subscription"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Recurring monthly billing"),
     *             @OA\Property(property="key", type="string", example="monthly"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Billing type created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BillingType")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The name field is required.")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'key'         => 'required|string|unique:billing_types,key',
            'status'      => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $type = BillingType::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Billing type created successfully',
            'data' => $type
        ], 201);
    }


    /**
     * @OA\Get(
     *     path="/api/utils/billing-types/{id}",
     *     tags={"Utils | Billing Types"},
     *     summary="Show billing type details",
     *     description="Retrieve details of a billing type.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Billing type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/BillingType")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Billing type not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        try {
            $type = BillingType::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $type
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Billing type not found',
            ], 404);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/utils/billing-types/{id}",
     *     tags={"Utils | Billing Types"},
     *     summary="Update a billing type",
     *     description="Modify an existing billing type.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Billing type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "key", "status"},
     *             @OA\Property(property="name", type="string", example="Monthly Subscription"),
     *             @OA\Property(property="description", type="string", nullable=true, example="Updated description"),
     *             @OA\Property(property="key", type="string", example="monthly"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Billing type updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/BillingType")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The key has already been taken.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Billing type not found")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $type = BillingType::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name'        => 'required|string',
                'description' => 'nullable|string',
                'key'         => 'required|string|unique:billing_types,key,' . $id,
                'status'      => 'required|in:active,inactive',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 400);
            }

            $type->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Billing type updated successfully',
                'data' => $type
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Billing type not found',
            ], 404);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/utils/billing-types/{id}",
     *     tags={"Utils | Billing Types"},
     *     summary="Delete a billing type",
     *     description="Remove billing type from the system.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Billing type ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Billing type deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Billing type not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        try {
            $type = BillingType::findOrFail($id);
            $type->delete();

            return response()->json([
                'success' => true,
                'message' => 'Billing type deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Billing type not found',
            ], 404);
        }
    }
}
