<?php

namespace App\Http\Controllers\Api\V1\Role\Permission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Validator;


class PermissionController extends Controller
{






    /**
     * @OA\Get(
     *   path="/api/roles/{roleId}/permissions",
     *   tags={"Role | Permissions"},
     *   summary="Get permissions for a specific role",
     *   description="Retrieve permissions associated with the specified role.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Permissions retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Permissions retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="id", type="integer", description="The ID of the permission"),
     *           @OA\Property(property="name", type="string", description="The name of the permission"),
     *           @OA\Property(property="description", type="string", nullable=true, description="The description of the permission"),
     *           @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *           @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *           @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *           @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *           @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *           @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the permission (active/inactive)")
     *         )
     *       )
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



    public function index($roleId)
    {
        $permissions = Permission::where('role_id', $roleId)->get();
        return response()->json([
            'success' => true,
            'message' => 'Permissions retrieved successfully',
            'data' => $permissions,
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/roles/{roleId}/permissions/{permissionId}",
     *     tags={"Role | Permissions"},
     *     summary="Get a specific permission for a role",
     *     description="Retrieve a specific permission associated with the specified role.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="permissionId",
     *         in="path",
     *         required=true,
     *         description="ID of the permission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer", description="The ID of the permission"),
     *             @OA\Property(property="name", type="string", description="The name of the permission"),
     *             @OA\Property(property="description", type="string", nullable=true, description="The description of the permission"),
     *             @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *             @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *             @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *             @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *             @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the permission (active/inactive)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
     *         )
     *     )
     * )
     */


    public function show($roleId, $permissionId)
    {
        $permission = Permission::where('role_id', $roleId)->find($permissionId);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Permission retrieved successfully',
            'data' => $permission,
        ]);
    }

    /**
     * Store a newly created permission for the specified role in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $roleId
     * @return \Illuminate\Http\Response
     */

    /**
     * @OA\Post(
     *   path="/api/roles/{roleId}/permissions",
     *   tags={"Role | Permissions"},
     *   summary="Create permission for a specific role",
     *   description="Create a new permission for the specified role.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Permission details",
     *     @OA\JsonContent(
     *       required={"name", "feature_id", "create", "read", "update", "delete"},
     *       @OA\Property(property="name", type="string", description="The name of the permission"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the permission"),
     *       @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *       @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *       @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *       @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *       @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the permission (active/inactive)"),
     *       example={"name": "Permission Name", "feature_id": 1, "create": true, "read": true, "update": false, "delete": false, "status": "active"}
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Permission created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the permission"),
     *         @OA\Property(property="name", type="string", description="The name of the permission"),
     *         @OA\Property(property="description", type="string", description="The description of the permission"),
     *         @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *         @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *         @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *         @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *         @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *         @OA\Property(property="status", type="string", description="The status of the permission (active/inactive)")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean"),
     *       @OA\Property(property="message", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean"),
     *       @OA\Property(property="message", type="string"),
     *       @OA\Property(property="errors", type="object")
     *     )
     *   )
     * )
     */

    public function store(Request $request, $roleId)
    {
        // Check if the role exists

        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        // Validation rules for permission creation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'description' => 'nullable|string',
            'feature_id' => 'required|exists:features,id',
            'create' => 'required|boolean',
            'read' => 'required|boolean',
            'update' => 'required|boolean',
            'delete' => 'required|boolean',
            'status' => 'string|in:active,inactive',
        ]);




        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }





        // Create or update the permission
        $permission = Permission::updateOrCreate(
            [
                'role_id' => $roleId,
                'feature_id' => $request->input('feature_id'),
            ],
            array_merge($request->except('feature_id'), ['role_id' => $roleId])
        );


        return response()->json([
            'success' => true,
            'message' => 'Permission created or updated successfully',
            'data' => $permission,
        ], 201);
    }



    /**
     * @OA\Put(
     *     path="/api/roles/{roleId}/permissions/{permissionId}",
     *     tags={"Role | Permissions"},
     *     summary="Update a specific permission for a role",
     *     description="Update the specified permission associated with the specified role.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="roleId",
     *         in="path",
     *         required=true,
     *         description="ID of the role",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="permissionId",
     *         in="path",
     *         required=true,
     *         description="ID of the permission",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Permission details to update",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", description="The ID of the permission"),
     *             @OA\Property(property="name", type="string", description="The name of the permission"),
     *             @OA\Property(property="description", type="string", nullable=true, description="The description of the permission"),
     *             @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *             @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *             @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *             @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *             @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the permission (active/inactive)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", description="The name of the permission"),
     *             @OA\Property(property="description", type="string", nullable=true, description="The description of the permission"),
     *             @OA\Property(property="feature_id", type="integer", description="The ID of the associated feature"),
     *             @OA\Property(property="create", type="boolean", description="Indicates whether the permission allows creation"),
     *             @OA\Property(property="read", type="boolean", description="Indicates whether the permission allows reading"),
     *             @OA\Property(property="update", type="boolean", description="Indicates whether the permission allows updating"),
     *             @OA\Property(property="delete", type="boolean", description="Indicates whether the permission allows deletion"),
     *             @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the permission (active/inactive)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Permission not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Validation error"),
     *             @OA\Property(property="errors", type="object", example={"name": {"The name field is required."}})
     *         )
     *     )
     * )
     */


    public function update(Request $request, $roleId, $permissionId)
    {
        // Find the permission
        $permission = Permission::where('role_id', $roleId)->find($permissionId);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        // Validation rules for permission update
        $validator = Validator::make($request->all(), [
            'name' => 'string',
            'description' => 'nullable|string',
            'feature_id' => 'exists:features,id',
            'create' => 'boolean',
            'read' => 'boolean',
            'update' => 'boolean',
            'delete' => 'boolean',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update the permission
        $permission->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully',
            'data' => $permission,
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/roles/{roleId}/permissions/{permissionId}",
     *   tags={"Role | Permissions"},
     *   summary="Delete permission for a specific role",
     *   description="Delete the specified permission associated with the specified role.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="permissionId",
     *     in="path",
     *     required=true,
     *     description="ID of the permission",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Permission deleted successfully",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean"),
     *       @OA\Property(property="message", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Permission not found",
     *     @OA\JsonContent(
     *       @OA\Property(property="success", type="boolean"),
     *       @OA\Property(property="message", type="string")
     *     )
     *   )
     * )
     */

    public function destroy($roleId, $permissionId)
    {
        // Find the permission
        $permission = Permission::where('role_id', $roleId)->find($permissionId);
        if (!$permission) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        }

        // Delete the permission
        $permission->delete();

        return response()->json([
            'success' => true,
            'message' => 'Permission deleted successfully',
        ]);
    }
}
