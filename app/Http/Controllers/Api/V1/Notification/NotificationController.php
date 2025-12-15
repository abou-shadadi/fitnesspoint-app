<?php

namespace App\Http\Controllers\Api\V1\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller {
	/**
	 * @OA\Get(
	 *     path="/api/notifications",
	 *     tags={"Notification"},
	 *     security={
	 *         {"sanctum": {}},
	 *     },
	 *     summary="Get user notifications",
	 *     description="Returns notifications for the current logged-in user.",
	 *     @OA\Response(response=200, description="Successful operation"),
	 *     @OA\Response(response=401, description="Unauthenticated"),
	 *     @OA\Response(response=500, description="Internal server error")
	 * )
	 */
	public function index() {
		try {
			$notifications = Notification::with(['feature', 'initiated_by'])->where('user_id', Auth::user()->id)->get();

			return response()->json([
				'success' => true,
				'message' => 'User notifications retrieved successfully',
				'data' => $notifications,
			], 200);
		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => $e->getMessage(),
				'data' => null,
			], 500);
		}
	}

	/**
	 * @OA\Get(
	 *     path="/api/notifications/{id}",
	 *     tags={"Notification"},
	 *     security={
	 *         {"sanctum": {}},
	 *     },
	 *     summary="Get a notification by ID",
	 *     description="Returns a single notification based on the provided ID.",
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         description="ID of the notification",
	 *         required=true,
	 *         @OA\Schema(type="integer")
	 *     ),
	 *     @OA\Response(response=200, description="Successful operation"),
	 *     @OA\Response(response=401, description="Unauthenticated"),
	 *     @OA\Response(response=404, description="Notification not found"),
	 *     @OA\Response(response=500, description="Internal server error")
	 * )
	 */
	public function show($id) {

		$notification = Notification::with(['feature', 'initiated_by'])->where('user_id', Auth::user()->id)->find($id);

		if (!$notification) {
			return response()->json([
				'success' => false,
				'message' => 'Notification not found',
				'data' => null,
			], 404);
		}

		return response()->json([
			'success' => true,
			'message' => 'Notification retrieved successfully',
			'data' => $notification,
		], 200);
	}

	/**
	 * @OA\Delete(
	 *     path="/api/notifications/{id}",
	 *     tags={"Notification"},
	 *     security={
	 *         {"sanctum": {}},
	 *     },
	 *     summary="Delete a notification",
	 *     description="Deletes a notification by ID.",
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         description="ID of the notification",
	 *         required=true,
	 *         @OA\Schema(type="integer")
	 *     ),
	 *     @OA\Response(response=200, description="Notification deleted successfully"),
	 *     @OA\Response(response=401, description="Unauthenticated"),
	 *     @OA\Response(response=404, description="Notification not found"),
	 *     @OA\Response(response=500, description="Internal server error")
	 * )
	 */
	public function destroy($id) {

		$notification = Notification::where('user_id', Auth::user()->id)->find($id);

		if (!$notification) {
			return response()->json([
				'success' => false,
				'message' => 'Notification not found',
				'data' => null,
			], 404);
		}

		$notification->delete();

		return response()->json([
			'success' => true,
			'message' => 'Notification deleted successfully',
			'data' => null,
		], 200);
	}
}
