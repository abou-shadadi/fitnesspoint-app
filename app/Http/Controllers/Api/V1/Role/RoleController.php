<?php

namespace App\Http\Controllers\Api\V1\Role;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;


/**
 * @OA\Schema(
 *     schema="Role",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", format="int64", description="The ID of the role"),
 *     @OA\Property(property="name", type="string", description="The name of the role"),
 *     @OA\Property(property="description", type="string", nullable=true, description="The description of the role"),
 *     @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the role (active/inactive)")
 * )
 */


class RoleController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/roles",
     *   tags={"Role"},
     *   summary="Get all roles",
     *   description="Retrieve all roles.",
     *   security={{"sanctum": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="Roles retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Roles retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Role")
     *       )
     *     )
     *   )
     * )
     */
    public function index()
    {
        $roles = Role::withCount('users')->with(['permissions'])->get();
        return response()->json([
            'success' => true,
            'message' => 'Roles retrieved successfully',
            'data' => $roles,
        ]);
    }
    /**
     * @OA\Get(
     *   path="/api/roles/{id}",
     *   tags={"Role"},
     *   summary="Get a specific role",
     *   description="Retrieve a specific role by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Role retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Role retrieved successfully"),
     *       @OA\Property(property="data", ref="#/components/schemas/Role")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role not found")
     *     )
     *   )
     * )
     */
    public function show($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Role retrieved successfully',
            'data' => $role,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/roles",
     *   tags={"Role"},
     *   summary="Create a role",
     *   description="Create a new role.",
     *   security={{"sanctum": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Role details",
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string", description="The name of the role"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the role"),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the role (active/inactive)"),
     *       example={"name": "Role Name", "description": "Description of the role", "status": "active"}
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Role created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Role created successfully"),
     *       @OA\Property(property="data", ref="#/components/schemas/Role")
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name',
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

        $role = Role::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully',
            'data' => $role,
        ], 201);
    }

    /**
     * @OA\Put(
     *   path="/api/roles/{id}",
     *   tags={"Role"},
     *   summary="Update a role",
     *   description="Update an existing role by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Role details to update",
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", description="The name of the role"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the role"),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the role (active/inactive)")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Role updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Role updated successfully"),
     *       @OA\Property(property="data", ref="#/components/schemas/Role")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role not found")
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
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|unique:roles,name,' . $role->id,
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

        $role->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully',
            'data' => $role,
        ]);
    }


    /**
     * @OA\Delete(
     *   path="/api/roles/{id}",
     *   tags={"Role"},
     *   summary="Delete a role",
     *   description="Delete a role by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Role deleted successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Role deleted successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role not found")
     *     )
     *   )
     * )
     */
    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully',
        ]);
    }
}
