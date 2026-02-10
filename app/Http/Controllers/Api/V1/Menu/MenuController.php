<?php

namespace App\Http\Controllers\Api\V1\Menu;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu\Menu;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/menus",
     *   tags={"Menus"},
     *   summary="Get all menus",
     *   description="Retrieve a list of all menus.",
     *   security={{"sanctum": {}}},
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
     *           @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *           @OA\Property(property="name", type="string", description="The name of the menu"),
     *           @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *           @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *           @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
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
        $menus = Menu::with(['menu_group', 'children'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Menus retrieved successfully',
            'data' => $menus,
        ]);
    }

    /**
     * @OA\Get(
     *   path="/api/menus/{menuId}",
     *   tags={"Menus"},
     *   summary="Get a specific menu",
     *   description="Retrieve a specific menu by its ID.",
     *   security={{"sanctum": {}}},
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
     *         @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *         @OA\Property(property="name", type="string", description="The name of the menu"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *         @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu not found")
     *     )
     *   )
     * )
     */
    public function show($menuId)
    {
        $menu = Menu::with(['menu_group', 'children'])->find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Menu retrieved successfully',
            'data' => $menu,
        ]);
    }

    /**
     * @OA\Post(
     *   path="/api/menus",
     *   tags={"Menus"},
     *   summary="Create a new menu",
     *   description="Create a new menu.",
     *   security={{"sanctum": {}}},
     *   @OA\RequestBody(
     *     required=true,
     *     description="Menu details",
     *     @OA\JsonContent(
     *       required={"name", "menu_group_id"},
     *       @OA\Property(property="name", type="string", description="The name of the menu"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *       @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *       @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *       @OA\Property(property="parent_id", type="integer", nullable=true, description="The ID of the parent menu"),
     *       @OA\Property(property="slug", type="string", nullable=true, description="The slug of the menu"),
     *       example={"name": "Dashboard", "description": "Main dashboard menu", "icon": "fa-home", "menu_group_id": 1, "parent_id": null}
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Menu created successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu created successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *         @OA\Property(property="name", type="string", description="The name of the menu"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *         @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
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
            'menu_group_id' => 'required|exists:menu_groups,id',
            'parent_id' => 'nullable|exists:menus,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $menu = Menu::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu created successfully',
            'data' => $menu,
        ], 201);
    }

    /**
     * @OA\Put(
     *   path="/api/menus/{menuId}",
     *   tags={"Menus"},
     *   summary="Update a specific menu",
     *   description="Update the specified menu by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="menuId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     description="Menu details to update",
     *     @OA\JsonContent(
     *       @OA\Property(property="name", type="string", description="The name of the menu"),
     *       @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *       @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *       @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *       @OA\Property(property="parent_id", type="integer", nullable=true, description="The ID of the parent menu"),
     *       example={"name": "Updated Dashboard", "description": "Updated dashboard menu", "icon": "fa-home", "menu_group_id": 1, "parent_id": 1}
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu updated successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu updated successfully"),
     *       @OA\Property(
     *         property="data",
     *         type="object",
     *         @OA\Property(property="id", type="integer", description="The ID of the menu"),
     *         @OA\Property(property="name", type="string", description="The name of the menu"),
     *         @OA\Property(property="description", type="string", nullable=true, description="The description of the menu"),
     *         @OA\Property(property="icon", type="string", nullable=true, description="The icon of the menu"),
     *         @OA\Property(property="menu_group_id", type="integer", description="The ID of the menu group"),
     *         @OA\Property(property="created_at", type="string", format="date-time", description="Creation timestamp"),
     *         @OA\Property(property="updated_at", type="string", format="date-time", description="Last update timestamp")
     *       )
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu not found")
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
    public function update(Request $request, $menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'menu_group_id' => 'exists:menu_groups,id',
            'parent_id' => 'nullable|exists:menus,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $menu->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Menu updated successfully',
            'data' => $menu,
        ]);
    }

    /**
     * @OA\Delete(
     *   path="/api/menus/{menuId}",
     *   tags={"Menus"},
     *   summary="Delete a specific menu",
     *   description="Delete the specified menu by its ID.",
     *   security={{"sanctum": {}}},
     *   @OA\Parameter(
     *     name="menuId",
     *     in="path",
     *     required=true,
     *     description="ID of the menu",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Menu deleted successfully",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=true),
     *       @OA\Property(property="message", type="string", example="Menu deleted successfully")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Menu not found",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="success", type="boolean", example=false),
     *       @OA\Property(property="message", type="string", example="Menu not found")
     *     )
     *   )
     * )
     */
    public function destroy($menuId)
    {
        $menu = Menu::find($menuId);

        if (!$menu) {
            return response()->json([
                'success' => false,
                'message' => 'Menu not found',
            ], 404);
        }

        $menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu deleted successfully',
        ]);
    }
}
