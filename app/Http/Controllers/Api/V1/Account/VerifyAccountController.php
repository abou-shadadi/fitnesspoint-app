<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Snowfire\Beautymail\Beautymail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VerifyAccountController extends Controller
{
    // swagger documentation for verify account
    /**
     * @OA\Post(
     *   path="/api/account/verify",
     *   tags={"Account | Security"},
     *   summary="Verify Account",
     *   description="Verify Account",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"token"},
     *       @OA\Property(property="token", type="string", example="token"),
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Verify Account",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="success"),
     *       @OA\Property(property="message", type="string", example="Account verified successfully."),
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Unauthorized",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="Invalid token."),
     *     )
     *   ),
     *   @OA\Response(
     *     response=400,
     *     description="Bad Request",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="User is already verified."),
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Not Found",
     *     @OA\JsonContent(
     *       @OA\Property(property="status", type="string", example="error"),
     *       @OA\Property(property="message", type="string", example="User not found."),
     *     )
     *   )
     *
     * )
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     **/
    public function verify(Request $request)
    {
        // Validate the request
        $request->validate([
            'token' => 'required|string',
        ]);

        // Find the user by the verification token
        $user = User::where('email_verification_token', $request->token)->first();

        // Check if user exists
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token.'], 401);
        }

        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json(['status' => 'error', 'message' => 'User is already verified.'], 400);
        }

        // Verify the user
        $user->email_verified_at = now();
        $user->email_verification_token = null;
        $user->save();

        // Return success response
        return response()->json(['status' => 'success', 'message' => 'Account verified successfully.']);
    }




    // swagger documentation for resendVerifyToken within api/account/verify
    /**
     * @OA\Put(
     *   path="/api/account/verify/resend",
     *  tags={"Account | Verifications"},
     * summary="Resend Verify Token",
     * description="Resend Verify Token",
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Verify",
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent()
     * ),
     * @OA\Response(
     * response=422,
     * description="Unprocessable Entity",
     * @OA\JsonContent()
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal Server Error",
     * @OA\JsonContent()
     * )
     * )
     */
    public function resend(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Check if user exists
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User not found.'], 404);
        }

        // Check if user is already verified
        if ($user->email_verified_at !== null) {
            return response()->json(['status' => 'error', 'message' => 'User is already verified.'], 400);
        }

        // Check if there's an existing valid verification token
        if ($user->email_verification_token && Carbon::parse($user->email_verification_expires_at)->isFuture()) {
            $token = $user->email_verification_token;
        } else {
            // Generate a new verification token
            $token = Str::random(60);
            $user->email_verification_token = $token;
            $user->email_verification_expires_at = now()->addHours(24); // Set expiry time for 24 hours
            $user->save();
        }

        // Send verification email
        $newAccountMail = app()->make(Beautymail::class);
        $newAccountMail->send('emails.account.verify-account', [
            'user' => $user,
            'token' => $token
        ], function ($message) use ($user) {
            $message
                ->from("no-reply@fitnesspoint.rw", env('APP_NAME'))
                ->to($user->email, $user->first_name . ' ' . $user->last_name)
                ->subject('Verification Link');
        });

        // Return success response
        return response()->json(['status' => 'success', 'message' => 'Verification link sent successfully.']);
    }
}
