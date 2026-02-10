<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu\MenuGroup;
use Illuminate\Support\Facades\Validator;

class MenuGroupController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/menu-groups",
     *   tags={"Menu Groups"},
     *   summary="Get all menu groups",
     *   description="Retrieve a list of all menu groups.",
     *   security={{"sanctum": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="Menu groups retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu groups retrieved successfully"),
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
     *           @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *           @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function index()
    {
        $menuGroups = MenuGroup::with('menus')->get();

        return response()->json([
            'success' => true,
            'message' => 'Menu groups retrieved successfully',
            'data' => $menuGroups,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/menu-groups/{menuGroupId}",
     *   tags={"Menu Groups"},
     *   summary="Get a specific menu group",
     *   description="Retrieve a specific menu group by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="menuGroupId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu group",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu group retrieved successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu group retrieved successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="name", type="string", description="The name of the menu group"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *         @OA\Property(property="order", type="integer", description="The order of the menu group"),
     *         @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group"),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu group not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu group not found")
     *     )
     *   )
     * )
     */
    public function show($menuGroupId)
    {
        $menuGroup = MenuGroup::with('menus')->find($menuGroupId);

        if (!$menuGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Menu group not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu group retrieved successfully',
            'data' => $menuGroup,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/menu-groups",
     *   tags={"Menu Groups"},
     *   summary="Create a new menu group",
     *   description="Create a new menu group.",
     *   security={{"sanctum": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Menu group details",
     *     @OA\JsonContent(
     *       required={"name"},
     *       @OA\Property(property="name", type="string", description="The name of the menu group"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *       @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *       @OA\Property(property="order", type="integer", description="The order of the menu group", example=1),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group", example="active"),
     *       example={"name": "Main Group", "description": "Primary menu group", "icon": "fa-folder", "order": 1, "status": "active"}
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Menu group created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu group created successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="name", type="string", description="The name of the menu group"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *         @OA\Property(property="order", type="integer", description="The order of the menu group"),
     *         @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group"),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
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
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $menuGroup = MenuGroup::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu group created successfully',
            'data' => $menuGroup,
        ], 201);
    }

    /**
     * @OA\Put(
     *   path="/api/menu-groups/{menuGroupId}",
     *   tags={"Menu Groups"},
     *   summary="Update a specific menu group",
     *   description="Update the specified menu group by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="menuGroupId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu group",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Menu group details to update",
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", description="The name of the menu group"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *       @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *       @OA\Property(property="order", type="integer", description="The order of the menu group", example=1),
     *       @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group", example="active"),
     *       example={"name": "Updated Group", "description": "Updated menu group", "icon": "fa-folder", "order": 2, "status": "active"}
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu group updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu group updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="name", type="string", description="The name of the menu group"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu group"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu group"),
     *         @OA\Property(property="order", type="integer", description="The order of the menu group"),
     *         @OA\Property(property="status", type="string", enum={"active", "inactive"}, description="The status of the menu group"),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu group not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu group not found")
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
    public function update(Request $request, $menuGroupId)
    {
        $menuGroup = MenuGroup::find($menuGroupId);

        if (!$menuGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Menu group not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'order' => 'integer|min:1',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $menuGroup->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu group updated successfully',
            'data' => $menuGroup,
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/menu-groups/{menuGroupId}",
     *   tags={"Menu Groups"},
     *   summary="Delete a specific menu group",
     *   description="Delete the specified menu group by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="menuGroupId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu group",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu group deleted successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu group deleted successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu group not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu group not found")
     *     )
     *   )
     * )
     */
    public function destroy($menuGroupId)
    {
        $menuGroup = MenuGroup::find($menuGroupId);

        if (!$menuGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Menu group not found',
            ], 404);
        }

        $menuGroup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu group deleted successfully',
        ]);
    }
}
