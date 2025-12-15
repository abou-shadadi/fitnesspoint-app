<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Snowfire\Beautymail\Beautymail;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{


    // swagger documentation for forgot password
    /**
     * @OA\Post(
     *   path="/api/account/forgot-password",
     *   tags={"Account | Security"},
     *   summary="Forgot Password",
     *   description="Forgot Password",
     *   @OA\Parameter(
     *     name="email",
     *     description="Email",
     *     required=false,
     *     in="query",
     *     @OA\Schema(
     *       type="string",
     *       default="",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Forgot Password",
     *
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *         @OA\JsonContent()
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Unprocessable Entity",
     *          @OA\JsonContent()
     *   )
     * )
     */
    public function forgotPassword(Request $request)
    {
        // Validate the request
        $request->validate([
            'email' => 'required|email',
        ]);

        DB::beginTransaction();

        try {
            // Get user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists
            if (!$user) {
                // If user not found with provided email, return error
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found with this email address.',
                ], 404);
            }

            // Generate a unique token for password reset
            $token = Str::random(90);

            // Update user's reset token and reset timestamp in the database
            $user->update([
                'reset_password_token' => $token,
                'password_reset_at' => Carbon::now(),
            ]);

            // Send email with password reset link
            $beautymail = app()->make(Beautymail::class);
            $beautymail->send('emails.account.forgot-password', ['token' => $token, 'user' => $user], function ($message) use ($user) {
                $message
                    ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                    ->to($user->email, $user->first_name . ' ' . $user->last_name)
                    ->subject('Password Reset Request');
            });

            DB::commit();
            return response()->json([
                'status' => 'success',
                'message' => 'Password reset email sent successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email, please try again!, ' . $e->getMessage(),
            ], 401);
        }
    }
}
