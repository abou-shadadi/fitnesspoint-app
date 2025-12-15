<?php

namespace App\Services;

use Snowfire\Beautymail\Beautymail;
use App\Models\User;

class UserService
{
    protected $user;
    protected $beautymail;

    public function __construct(User $user, Beautymail $beautymail)
    {
        $this->user = $user;
        $this->beautymail = $beautymail;
    }


    public function updateUser($userId, $userData)
    {
        $user = $this->user->find($userId);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found.',
            ], 404);
        }

        try {
            $user->update($userData);

            return response()->json([
                'status' => 'success',
                'message' => 'User updated successfully.',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update user.',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    public function activate($userId)
    {
        $user = $this->user->findOrFail($userId);

        if ($user->status === 'active') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already activated.',
            ], 400);
        }

        $user->status = 'active';
        $user->save();

        // Send email notification for user activation
        $this->sendActivationEmail($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User activated successfully.',
            'user' => $user,
        ], 200);
    }

    public function deactivate($userId)
    {
        $user = $this->user->findOrFail($userId);

        if ($user->status === 'inactive') {
            return response()->json([
                'status' => 'error',
                'message' => 'User is already deactivated.',
            ], 400);
        }

        $user->status = 'inactive';
        $user->save();

        // Send email notification for user deactivation
        $this->sendDeactivationEmail($user);

        return response()->json([
            'status' => 'success',
            'message' => 'User deactivated successfully.',
            'user' => $user,
        ], 200);
    }

    protected function sendActivationEmail($user)
    {
        $this->beautymail->send('emails.account.activation', ['user' => $user], function ($message) use ($user) {
            $message
                ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                ->to($user->email, $user->name)
                ->subject('Account Activated');
        });
    }

    protected function sendDeactivationEmail($user)
    {
        $this->beautymail->send('emails.account.deactivation', ['user' => $user], function ($message) use ($user) {
            $message
                ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                ->to($user->email, $user->name)
                ->subject('Account Deactivated');
        });
    }
}
