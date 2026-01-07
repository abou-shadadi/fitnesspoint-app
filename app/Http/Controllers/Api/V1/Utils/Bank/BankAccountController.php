<?php

namespace App\Http\Controllers\Api\V1\Utils\Bank;

use App\Http\Controllers\Controller;
use App\Models\Bank\Bank;
use App\Models\Bank\BankAccount;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BankAccountController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/utils/bank-accounts",
     *     operationId="getBankAccountsList",
     *     tags={"Utils - Bank Accounts"},
     *     summary="List all bank accounts",
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
     *         name="bank_id",
     *         in="query",
     *         description="Filter by bank ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="currency_id",
     *         in="query",
     *         description="Filter by currency ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search by name or account number",
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
     *                         @OA\Property(property="number", type="string"),
     *                         @OA\Property(property="status", type="string"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time"),
     *                         @OA\Property(property="bank", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string")
     *                         ),
     *                         @OA\Property(property="currency", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="code", type="string"),
     *                             @OA\Property(property="symbol", type="string")
     *                         ),
     *                         @OA\Property(property="created_by", type="object",
     *                             @OA\Property(property="id", type="integer"),
     *                             @OA\Property(property="name", type="string"),
     *                             @OA\Property(property="email", type="string")
     *                         )
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
            $query = BankAccount::with(['bank', 'currency', 'created_by']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by bank ID
            if ($request->has('bank_id')) {
                $query->where('bank_id', $request->bank_id);
            }

            // Filter by currency ID
            if ($request->has('currency_id')) {
                $query->where('currency_id', $request->currency_id);
            }

            // Search by name or account number
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('number', 'LIKE', "%{$search}%");
                });
            }

            // Order by created_at (newest first)
            $query->orderBy('created_at', 'desc');

            $accounts = $query->paginate($request->input('per_page', 20));

            return response()->json([
                'success' => true,
                'data' => $accounts
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
     *     path="/api/utils/bank-accounts/{id}",
     *     operationId="getBankAccountById",
     *     tags={"Utils - Bank Accounts"},
     *     summary="Get a specific bank account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="bank", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="description", type="string", nullable=true),
     *                     @OA\Property(property="status", type="string")
     *                 ),
     *                 @OA\Property(property="currency", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string"),
     *                     @OA\Property(property="symbol", type="string")
     *                 ),
     *                 @OA\Property(property="created_by", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank account not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show($id)
    {
        try {
            $account = BankAccount::with(['bank', 'currency', 'created_by'])->find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $account
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
     *     path="/api/utils/bank-accounts",
     *     operationId="createBankAccount",
     *     tags={"Utils - Bank Accounts"},
     *     summary="Create a new bank account",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "number", "currency_id", "bank_id"},
     *             @OA\Property(property="name", type="string", example="Main Operating Account"),
     *             @OA\Property(property="number", type="string", example="1234567890"),
     *             @OA\Property(property="currency_id", type="integer", example=1),
     *             @OA\Property(property="bank_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, default="active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Bank account created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="bank", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(property="currency", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string")
     *                 )
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
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:50|unique:bank_accounts,number',
            'currency_id' => 'required|exists:currencies,id',
            'bank_id' => 'required|exists:banks,id',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Check if bank is active
        $bank = Bank::find($request->bank_id);
        if (!$bank || $bank->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Selected bank is not active'
            ], 400);
        }

        // Check if currency exists
        $currency = Currency::find($request->currency_id);
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Selected currency does not exist'
            ], 400);
        }

        DB::beginTransaction();
        try {
            $account = BankAccount::create([
                'name' => $request->name,
                'number' => $request->number,
                'currency_id' => $request->currency_id,
                'bank_id' => $request->bank_id,
                'created_by_id' => Auth::id(),
                'status' => $request->input('status', 'active')
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bank account created successfully',
                'data' => $account->load(['bank', 'currency', 'created_by'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/utils/bank-accounts/{id}",
     *     operationId="updateBankAccount",
     *     tags={"Utils - Bank Accounts"},
     *     summary="Update a bank account",
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
     *             required={"name", "number", "currency_id", "bank_id"},
     *             @OA\Property(property="name", type="string", example="Updated Account Name"),
     *             @OA\Property(property="number", type="string", example="0987654321"),
     *             @OA\Property(property="currency_id", type="integer", example=1),
     *             @OA\Property(property="bank_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="number", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time"),
     *                 @OA\Property(property="bank", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(property="currency", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="code", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank account not found"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'number' => 'required|string|max:50|unique:bank_accounts,number,' . $id,
            'currency_id' => 'required|exists:currencies,id',
            'bank_id' => 'required|exists:banks,id',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        // Check if bank is active
        $bank = Bank::find($request->bank_id);
        if (!$bank || $bank->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Selected bank is not active'
            ], 400);
        }

        // Check if currency exists
        $currency = Currency::find($request->currency_id);
        if (!$currency) {
            return response()->json([
                'success' => false,
                'message' => 'Selected currency does not exist'
            ], 400);
        }

        try {
            $account = BankAccount::find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account not found'
                ], 404);
            }

            $account->update([
                'name' => $request->name,
                'number' => $request->number,
                'currency_id' => $request->currency_id,
                'bank_id' => $request->bank_id,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank account updated successfully',
                'data' => $account->fresh(['bank', 'currency', 'created_by'])
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
     *     path="/api/utils/bank-accounts/{id}",
     *     operationId="deleteBankAccount",
     *     tags={"Utils - Bank Accounts"},
     *     summary="Delete a bank account",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bank account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bank account deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Bank account not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $account = BankAccount::find($id);

            if (!$account) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bank account not found'
                ], 404);
            }

            // Check if account has transactions (you might want to add this later)
            // if ($account->transactions()->exists()) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'Cannot delete bank account with transactions'
            //     ], 400);
            // }

            $account->delete();

            return response()->json([
                'success' => true,
                'message' => 'Bank account deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
