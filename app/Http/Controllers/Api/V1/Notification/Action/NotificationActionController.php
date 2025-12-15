<?php

namespace App\Http\Controllers\Api\V1\Notification\Action;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationActionController extends Controller {
/**
 * @OA\Put(
 *     path="/api/notifications/actions/read",
 *     tags={"Notification | Action"},
 *     security={
 *         {"sanctum": {}},
 *     },
 *     summary="Mark notifications as read",
 *     description="Marks notifications as read based on the provided IDs.",
 *     @OA\RequestBody(
 *         required=true,
 *         description="Array of notification IDs to mark as read",
 *         @OA\JsonContent(
 *             type="array",
 *             @OA\Items(
 *                 type="object",
 *                 @OA\Property(property="id", type="integer", example=1)
 *             )
 *         )
 *     ),
 *     @OA\Response(response=200, description="Notifications marked as read successfully"),
 *     @OA\Response(response=401, description="Unauthenticated"),
 *     @OA\Response(response=500, description="Internal server error")
 * )
 */
	public function update(Request $request) {
		// Validate incoming request
		$request->validate([
			'*.id' => 'required|exists:notifications,id,user_id,' . Auth::user()->id(),
		]);

		$notificationIds = collect($request->json())->pluck('id');

		// Update notifications as read
		Notification::whereIn('id', $notificationIds)
			->update(['read_at' => now()]);

		return response()->json([
			'success' => true,
			'message' => 'Notifications marked as read successfully',
			'data' => null,
		], 200);
	}
}
