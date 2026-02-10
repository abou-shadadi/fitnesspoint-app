<?php

namespace App\Http\Controllers\Api\V1\Role\Menu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role\Role;
use App\Models\Menu\Menu;
use App\Models\Menu\MenuGroup;
use App\Models\Role\RoleMenu;
use Illuminate\Support\Facades\Validator;

class RoleMenuController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/roles/{roleId}/menus",
     *   tags={"Role | Menus"},
     *   summary="Get menus for a specific role, grouped by menu groups",
     *   description="Retrieve menus associated with the specified role, organized by their menu groups.",
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
     *     description="Menus retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menus retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(
     *           @OA\Property(property="id", type="integer", description="The ID of the menu group"),
     *           @OA\Property(property="name", type="string", description="The name of the menu group"),
     *           @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *           @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *           @OA\Property(property="order", type="integer", description="The order of the menu group"),
     *           @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group"),
     *           @OA\Property(
     *             property="menus",
     *             type="array",
     *             @OA\Items(
     *               @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *               @OA\Property(property="name", type="string", description="The name of the menu"),
     *               @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *               @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *               @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *               @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu association"),
     *               @OA\Property(property="is_default", type="boolean", description="Whether this menu is default for the role"),
     *               @OA\Property(property="order", type="integer", description="The order of the menu in this role"),
     *               @OA\Property(
     *                 property="permissions",
     *                 type="object",
     *                 @OA\Property(property="read", type="boolean"),
     *                 @OA\Property(property="create", type="boolean"),
     *                 @OA\Property(property="update", type="boolean"),
     *                 @OA\Property(property="delete", type="boolean")
     *               ),
     *               @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *               @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *             )
     *           )
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
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $menuGroups = MenuGroup::with([
            'menus' => function ($menuQuery) use ($roleId) {
                $menuQuery
                    ->whereHas('roles', fn($q) => $q->where('role_id', $roleId))
                    ->with([
                        'roles' => fn($q) => $q->where('role_id', $roleId)
                            ->select('role_menus.status', 'role_menus.is_default', 'role_menus.order', 'role_menus.permissions', 'menu_id'),
                        'children.roles' => fn($q) => $q->where('role_id', $roleId)
                            ->select('role_menus.status', 'role_menus.is_default', 'role_menus.order', 'role_menus.permissions', 'menu_id'),
                    ]);
            },
            'menus.children'
        ])->get();

        // Format menus & children
        $menuGroups->each(function ($group) {
            $group->menus->each(function ($menu) {
                $roleMenu = $menu->roles->first();
                $menu->status = $roleMenu->status ?? 'active';
                $menu->is_default = $roleMenu->is_default ?? false;
                $menu->order = $roleMenu->order ?? 1;
                $menu->permissions = $roleMenu->permissions ?? [
                    'read' => false,
                    'create' => false,
                    'update' => false,
                    'delete' => false
                ];
                unset($menu->roles, $menu->created_at, $menu->updated_at);

                $menu->children->each(function ($child) {
                    $childRoleMenu = $child->roles->first();
                    $child->status = $childRoleMenu->status ?? 'active';
                    $child->is_default = $childRoleMenu->is_default ?? false;
                    $child->order = $childRoleMenu->order ?? 1;
                    $child->permissions = $childRoleMenu->permissions ?? [
                        'read' => false,
                        'create' => false,
                        'update' => false,
                        'delete' => false
                    ];
                    unset($child->roles, $child->created_at, $child->updated_at);
                });
            });
        });

        return response()->json([
            'success' => true,
            'message' => 'Menus retrieved successfully',
            'data' => $menuGroups
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/roles/{roleId}/menus/{menuId}",
     *   tags={"Role | Menus"},
     *   summary="Get a specific menu for a role with its menu group",
     *   description="Retrieve a specific menu associated with the specified role, including its menu group details.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="menuId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(
     *           property="menu_group",
     *           type="object",
     *           @OA\Property(property="id", type="integer", description="The ID of the menu group"),
     *           @OA\Property(property="name", type="string", description="The name of the menu group"),
     *           @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *           @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *           @OA\Property(property="order", type="integer", description="The order of the menu group"),
     *           @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group")
     *         ),
     *         @OA\Property(
     *           property="menu",
     *           type="object",
     *           @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *           @OA\Property(property="name", type="string", description="The name of the menu"),
     *           @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *           @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *           @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *           @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu association"),
     *           @OA\Property(property="is_default", type="boolean", description="Whether this menu is default for the role"),
     *           @OA\Property(property="order", type="integer", description="The order of the menu in this role"),
     *           @OA\Property(
     *             property="permissions",
     *             type="object",
     *             @OA\Property(property="read", type="boolean"),
     *             @OA\Property(property="create", type="boolean"),
     *             @OA\Property(property="update", type="boolean"),
     *             @OA\Property(property="delete", type="boolean")
     *           ),
     *           @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *           @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *         )
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role or menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role or menu not found")
     *     )
     *   )
     * )
     */
    public function show($roleId, $menuId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $roleMenu = RoleMenu::with(['menu.menu_group', 'menu.children'])->where('role_id', $roleId)->where('menu_id', $menuId)->first();
        if (!$roleMenu || !$roleMenu->menu || !$roleMenu->menu->menu_group) {
            return response()->json([
                'success' => false,
                'message' => 'Role or menu not found',
            ], 404);
        }

        $menu = $roleMenu->menu;
        $menu->status = $roleMenu->status;
        $menu->is_default = $roleMenu->is_default;
        $menu->order = $roleMenu->order;
        $menu->permissions = $roleMenu->permissions;
        $menu->created_at = $roleMenu->created_at;
        $menu->updated_at = $roleMenu->updated_at;

        return response()->json([
            'success' => true,
            'message' => 'Menu retrieved successfully',
            'data' => [
                'menu_group' => $menu->menu_group,
                'menu' => $menu
            ]
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/roles/{roleId}/menus",
     *   tags={"Role | Menus"},
     *   summary="Associate a menu with a specific role",
     *   description="Associate a menu with the specified role.",
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
     *     description="Menu association details",
     *     @OA\JsonContent(
     *       required={"menu_id"},
     *       @OA\Property(property="menu_id", type="integer", description="The ID of the menu"),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, nullable=true, description="The status of the association (active/inactive)"),
     *       @OA\Property(property="is_default", type="boolean", nullable=true, description="Whether this menu is default for the role"),
     *       @OA\Property(property="order", type="integer", nullable=true, description="The order of the menu in this role"),
     *       @OA\Property(
     *         property="permissions",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="read", type="boolean"),
     *         @OA\Property(property="create", type="boolean"),
     *         @OA\Property(property="update", type="boolean"),
     *         @OA\Property(property="delete", type="boolean")
     *       ),
     *       example={
     *         "menu_id": 1,
     *         "status": "active",
     *         "is_default": false,
     *         "order": 1,
     *         "permissions": {"read": true, "create": false, "update": false, "delete": false}
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Menu associated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu associated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *         @OA\Property(property="name", type="string", description="The name of the menu"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *         @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu association"),
     *         @OA\Property(property="is_default", type="boolean", description="Whether this menu is default for the role"),
     *         @OA\Property(property="order", type="integer", description="The order of the menu in this role"),
     *         @OA\Property(
     *           property="permissions",
     *           type="object",
     *           @OA\Property(property="read", type="boolean"),
     *           @OA\Property(property="create", type="boolean"),
     *           @OA\Property(property="update", type="boolean"),
     *           @OA\Property(property="delete", type="boolean")
     *         ),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
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
    public function store(Request $request, $roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'menu_id' => 'required|integer|exists:menus,id',
            'status' => 'nullable|string|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'order' => 'nullable|integer|min:1',
            'permissions' => 'nullable|array',
            'permissions.read' => 'nullable|boolean',
            'permissions.create' => 'nullable|boolean',
            'permissions.update' => 'nullable|boolean',
            'permissions.delete' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $permissions = $request->input('permissions', [
            'read' => false,
            'create' => false,
            'update' => false,
            'delete' => false
        ]);

        $data = RoleMenu::updateOrCreate(
            [
                'menu_id' => $request->input('menu_id'),
                'role_id' => $role->id,
            ],
            [
                'status' => $request->input('status', 'active'),
                'is_default' => $request->input('is_default', false),
                'order' => $request->input('order', 1),
                'permissions' => json_encode($permissions)
            ]
        );

        $menu = Menu::with('menu_group')->find($request->input('menu_id'));
        $menu->status = $data->status;
        $menu->is_default = $data->is_default;
        $menu->order = $data->order;
        $menu->permissions = $data->permissions;
        $menu->created_at = $data->created_at;
        $menu->updated_at = $data->updated_at;

        return response()->json([
            'success' => true,
            'message' => 'Menu associated successfully',
            'data' => $menu,
        ], 201);
    }

    /**
     * @OA\Put(
     *   path="/api/roles/{roleId}/menus/{menuId}",
     *   tags={"Role | Menus"},
     *   summary="Update a specific menu association for a role",
     *   description="Update the association of the specified menu with the specified role.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="menuId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Menu association details to update",
     *     @OA\JsonContent(
     *       required={"status"},
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the association (active/inactive)"),
     *       @OA\Property(property="is_default", type="boolean", nullable=true, description="Whether this menu is default for the role"),
     *       @OA\Property(property="order", type="integer", nullable=true, description="The order of the menu in this role"),
     *       @OA\Property(
     *         property="permissions",
     *         type="object",
     *         nullable=true,
     *         @OA\Property(property="read", type="boolean"),
     *         @OA\Property(property="create", type="boolean"),
     *         @OA\Property(property="update", type="boolean"),
     *         @OA\Property(property="delete", type="boolean")
     *       ),
     *       example={
     *         "status": "inactive",
     *         "is_default": true,
     *         "order": 2,
     *         "permissions": {"read": true, "create": true, "update": false, "delete": false}
     *       }
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu association updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu association updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *         @OA\Property(property="name", type="string", description="The name of the menu"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *         @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu association"),
     *         @OA\Property(property="is_default", type="boolean", description="Whether this menu is default for the role"),
     *         @OA\Property(property="order", type="integer", description="The order of the menu in this role"),
     *         @OA\Property(
     *           property="permissions",
     *           type="object",
     *           @OA\Property(property="read", type="boolean"),
     *           @OA\Property(property="create", type="boolean"),
     *           @OA\Property(property="update", type="boolean"),
     *           @OA\Property(property="delete", type="boolean")
     *         ),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role or menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role or menu not found")
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
    public function update(Request $request, $roleId, $menuId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        $menu = Menu::find($menuId);
        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'order' => 'nullable|integer|min:1',
            'permissions' => 'nullable|array',
            'permissions.read' => 'nullable|boolean',
            'permissions.create' => 'nullable|boolean',
            'permissions.update' => 'nullable|boolean',
            'permissions.delete' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->has('is_default')) {
            $updateData['is_default'] = $request->is_default;
        }

        if ($request->has('order')) {
            $updateData['order'] = $request->order;
        }

        if ($request->has('permissions')) {
            $updateData['permissions'] = json_encode($request->permissions);
        }

        $data = RoleMenu::where('role_id', $roleId)->where('menu_id', $menuId)->update($updateData);

        $menu = Menu::with('menu_group')->find($menuId);
        $roleMenu = RoleMenu::where('role_id', $roleId)->where('menu_id', $menuId)->first();
        $menu->status = $roleMenu->status;
        $menu->is_default = $roleMenu->is_default;
        $menu->order = $roleMenu->order;
        $menu->permissions = $roleMenu->permissions;
        $menu->created_at = $roleMenu->created_at;
        $menu->updated_at = $roleMenu->updated_at;

        return response()->json([
            'success' => true,
            'message' => 'Menu association updated successfully',
            'data' => $menu,
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/roles/{roleId}/menus/{menuId}",
     *   tags={"Role | Menus"},
     *   summary="Delete menu association for a specific role",
     *   description="Delete the association of the specified menu with the specified role.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="roleId",
     *     in="path",
     *     required=true,
     *     description="ID of the role",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Parameter(
     *     name="menuId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu association deleted successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu association deleted successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Role or menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Role or menu not found")
     *     )
     *   )
     * )
     */
    public function destroy($roleId, $menuId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found',
            ], 404);
        }

        RoleMenu::where('role_id', $roleId)->where('menu_id', $menuId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu association deleted successfully',
        ]);
    }
}
