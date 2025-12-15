<?php

namespace App\Http\Controllers\Api\V1\Role\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{


    /**
     * Retrieve all users belonging to a specific role.
     *
     * @OA\Get(
     *     path="/api/roles/{roleId}/users",
     *     summary="Retrieve users by role",
     *     tags={"Role | User"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Users retrieved successfully."),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/User"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Role not found."
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error."
     *     )
     * )
     */

    public function index($roleId)
    {
        $users = User::where('role_id', $roleId)->get();

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data' => $users
        ], 200);
    }
}
