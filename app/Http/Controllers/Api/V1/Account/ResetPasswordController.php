<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Snowfire\Beautymail\Beautymail;
use Illuminate\Support\Carbon;


class ResetPasswordController extends Controller
{
    //

    // swagger documentation for reset password
    /**
     * @OA\Post(
     *   path="/api/account/reset-password",
     *   tags={"Account | Security"},
     *   summary="Reset Password",
     *   description="Reset Password",
     *   @OA\Parameter(
     *     name="token",
     *     description="Reset Token",
     *     required=true,
     *     in="query",
     *     @OA\Schema(
     *       type="string",
     *       example="123456"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="password",
     *     description="Password",
     *     required=true,
     *     in="query",
     *     @OA\Schema(
     *       type="string",
     *       example="123456"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="password_confirmation",
     *     description="Confirm Password",
     *     required=true,
     *     in="query",
     *     @OA\Schema(
     *       type="string",
     *       example="123456"
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Reset Password",
     * @OA\JsonContent()
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     * @OA\JsonContent()

     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Unprocessable Entity",
     * @OA\JsonContent()

     *   )
     * )
     */
    public function resetPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();

        try {
            // Find the user by the reset token
            $user = User::where('reset_password_token', $request->token)->first();

            if (!$user) {
                return response()->json(['status' => 'error', 'message' => 'Invalid token.'], 401);
            }

            // Check if token has expired
            if ($user->password_reset_at && Carbon::parse($user->password_reset_at)->addHours(config('auth.passwords.users.expire'))->isPast()) {
                return response()->json(['status' => 'error', 'message' => 'Token has expired.'], 401);
            }

            // Resets the user's password
            $user->forceFill([
                'password' => bcrypt($request->password),
                'reset_password_token' => null,
                'password_reset_at' => null,
            ])->save();


            // Send success email
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.account.password-reset', ['user' => $user], function ($message) use ($user) {
                $message
                    ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                    ->to($user->email, $user->first_name . ' ' . $user->last_name)
                    ->subject('Password Reset Successful');
            });

            DB::commit();

            return response()->json(['status' => 'success', 'message' => 'Password reset successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => 'Failed to reset password, please try again.'], 500);
        }
    }
}
