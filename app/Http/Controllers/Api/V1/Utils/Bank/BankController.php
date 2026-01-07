<?php

namespace App\Http\Controllers\Api\V1\Utils\Bank;

use App\Http\Controllers\Controller;
use App\Models\Bank\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/banks",
     *     operationId="getBanksList",
     *     tags={"Utils - Banks"},
     *     summary="List all banks",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Items per page",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         @OA\Schema(type="string", enum={"active", "inactive"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or description",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="description", type="string", nullable=true),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="accounts_count", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="first_page_url", type="string"),
     *                 @OA\Property(property="from", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="last_page_url", type="string"),
     *                 @OA\Property(property="links", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="url", type="string", nullable=true),
     *                         @OA\Property(property="label", type="string"),
     *                         @OA\Property(property="active", type="boolean")
     *                     )
     *                 ),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="path", type="string"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(property="to", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Bank::query();

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Search by name or description
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
                });
            }

            // Order by name
            $query->orderBy('name');

            // Get banks with accounts count
            $banks = $query->with('bank_ccounts')->withCount('bank_ccounts')->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $banks
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/utils/banks/{id}",
     *     operationId="getBankById",
     *     tags={"Utils - Banks"},
     *     summary="Get a specific bank",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="bank_accounts", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string"),
     *                         @OA\Property(property="number", type="string"),
     *                         @OA\Property(property="status", type="string")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show($id)
    {
        try {
            $bank = Bank::with(['bank_ccounts' => function($query) {
                // $query->where('status', 'active');
            }])->find($id);

            if (!$bank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $bank
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
     *     path="/api/utils/banks",
     *     operationId="createBank",
     *     tags={"Utils - Banks"},
     *     summary="Create a new bank",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Bank of Kigali"),
     *             @OA\Property(property="description", type="string", example="Leading commercial bank in Rwanda", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, default="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bank created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:banks,name',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            $bank = Bank::create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->input('status', 'active')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank created successfully',
                'data' => $bank
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utils/banks/{id}",
     *     operationId="updateBank",
     *     tags={"Utils - Banks"},
     *     summary="Update a bank",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Bank Name"),
     *             @OA\Property(property="description", type="string", example="Updated description", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="description", type="string", nullable=true),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank not found"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:banks,name,' . $id,
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        try {
            $bank = Bank::find($id);

            if (!$bank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found'
                ], 404);
            }

            $bank->update([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank updated successfully',
                'data' => $bank
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/utils/banks/{id}",
     *     operationId="deleteBank",
     *     tags={"Utils - Banks"},
     *     summary="Delete a bank",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $bank = Bank::find($id);

            if (!$bank) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank not found'
                ], 404);
            }

            // Check if bank has active accounts
            if ($bank->bank_ccounts()->where('status', 'active')->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete bank with active accounts. Please deactivate all accounts first.'
                ], 400);
            }

            $bank->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bank deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
