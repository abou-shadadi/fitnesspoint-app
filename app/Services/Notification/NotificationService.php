<?php

namespace App\Services\Notification;

use App\Models\Feature;
use App\Models\Notification\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class NotificationService {
	public function createNotification(int $userId, string $featureKey, string $action, array $data) {

		try {
			$feature = Feature::where('key', $featureKey)->first(); // get feature by key
			if (!$feature) {
				throw new \Exception('Feature not found');
			}

			// check if user exists
			$user = User::find($userId);

			if (!$user) {
				throw new \Exception('User not found');
			}

			$notification = Notification::create([
				'feature_id' => $feature->id,
				'action' => $action,
				'user_id' => $userId,
				'data' => $data,
				'initiated_by_id' => Auth::user()->id,
			]);
			return $notification;
		} catch (\Exception $e) {
			throw $e;
		}
	}

	public function updateNotification(Notification $notification, $data) {
		$notification->update($data);
		return $notification;
	}

	public function deleteNotification(Notification $notification) {
		$notification->delete();
	}
}
