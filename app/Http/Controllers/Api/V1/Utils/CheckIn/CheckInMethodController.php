<?php

namespace App\Http\Controllers\Api\V1\Utils\CheckIn;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CheckIn\CheckInMethod;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CheckInMethodController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/check-in-methods",
     *     summary="List all check-in methods",
     *     tags={"Utils | Check-In Methods"},
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
     *     @OA\Response(response=200, description="List of check-in methods"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = CheckInMethod::query();

            // Filter by status
            if ($request->has('status') && in_array($request->status, ['active', 'inactive'])) {
                $query->where('status', $request->status);
            }

            // Order by name
            $query->orderBy('name');

            $methods = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Check-in methods retrieved successfully',
                'data' => $methods
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
     *     path="/api/utils/check-in-methods",
     *     summary="Create a new check-in method",
     *     tags={"Utils | Check-In Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Voice Recognition"),
     *             @OA\Property(property="description", type="string", example="Check-in using voice recognition"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"},
     *                 example="active"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Check-in method created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:check_in_methods,name',
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
            // Create check-in method
            $method = CheckInMethod::create([
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'status' => $request->input('status', 'active')
            ]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in method created successfully',
                'data' => $method
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
     *     path="/api/utils/check-in-methods/{id}",
     *     summary="Get specific check-in method",
     *     tags={"Utils | Check-In Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Check-in method details"),
     *     @OA\Response(response=404, description="Check-in method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $method = CheckInMethod::find($id);

            if (!$method) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in method not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Check-in method retrieved successfully',
                'data' => $method
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
     *     path="/api/utils/check-in-methods/{id}",
     *     summary="Update check-in method",
     *     tags={"Utils | Check-In Methods"},
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
     *             @OA\Property(property="name", type="string", example="Updated Fingerprint"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"active", "inactive"}
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Check-in method updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Check-in method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        // Find check-in method
        $method = CheckInMethod::find($id);

        if (!$method) {
            return response()->json([
                'success' => false,
                'message' => 'Check-in method not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255|unique:check_in_methods,name,' . $id,
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

            // Update check-in method
            $method->update($updateData);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Check-in method updated successfully',
                'data' => $method
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
     *     path="/api/utils/check-in-methods/{id}",
     *     summary="Delete check-in method",
     *     tags={"Utils | Check-In Methods"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Check-in method deleted successfully"),
     *     @OA\Response(response=404, description="Check-in method not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $method = CheckInMethod::find($id);

            if (!$method) {
                return response()->json([
                    'success' => false,
                    'message' => 'Check-in method not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                $method->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Check-in method deleted successfully',
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
