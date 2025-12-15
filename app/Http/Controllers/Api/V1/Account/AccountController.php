<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Account\UserOtp;
use App\Models\Account\UserRole;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Snowfire\Beautymail\Beautymail;
use Swagger\Annotations as SWG;
use App\Models\User;
use App\Services\File\Base64Service;
use Carbon\Carbon;



class AccountController extends Controller
{

    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {

        $this->base64Service = $base64Service;
    }

    // swagger documentation


    /**
     * Update user's email and send a verification email.
     *
     * @OA\Put(
     *     path="/api/account/update-account-email",
     *   tags={"Account | Settings"},
     *     summary="Update user's email and send verification email",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     description="Update the authenticated user's email and send an email for account verification.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="email", type="string", format="email", maxLength=255, description="New email address"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email updated successfully and verification email sent",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email updated successfully and verification email sent"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email field is required.")),
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Email update failed"),
     *             @OA\Property(property="data", type="string", example="Internal Server Error Message"),
     *         )
     *     )
     * )
     */


    public function updateAccountEmail(Request $request)
    {

        $request->validate([
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->user()->id,
        ]);

        try {
            // lets send an email tp verify account
            $user = User::find(Auth::user()->id);
            if ($user) {

                $token = bin2hex(random_bytes(32));

                $accountVerification = app()->make(Beautymail::class);
                $accountVerification->send('emails.account.verify-account', [
                    'user' => $user,
                    'token' => $token,
                ], function ($message) use ($user) {
                    $message
                        ->from("no-reply@fitnesspoint.rw", env('APP_NAME'))
                        ->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('Verify Account');
                });



                $user->update([
                    'email' => $request->email,
                    'email_verified_at' => null,
                    'email_verification_token' => $token,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email updated successfully and verification email sent',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found, please try again later',
                ], 404);
            }
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Email update failed',
                'data' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/account/update-account",
     *     tags={"Account | Settings"},
     *     summary="Update user's account information",
     *     security={
     *         {"sanctum": {}}
     *     },
     *     description="Update the authenticated user's account information.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="first_name", type="string", maxLength=255, description="First name"),
     *             @OA\Property(property="last_name", type="string", maxLength=255, description="Last name"),
     *             @OA\Property(
     *                 property="phone",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="250"),
     *                 @OA\Property(property="number", type="string", example="780000000")
     *             ),
     *             @OA\Property(property="gender", type="string", enum={"male", "female", "Other"}, description="Gender"),
     *             @OA\Property(property="birth_date", type="string", format="date", description="Date of birth (YYYY-MM-DD)"),
     *             @OA\Property(
     *                 property="address",
     *                 type="array",
     *                 description="Array of address objects",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", description="Address name"),
     *                     @OA\Property(property="value", type="string", description="Address value")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Account information updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Account information updated successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="phone", type="string", example="+1234567890"),
     *                 @OA\Property(property="country_id", type="integer", example=1),
     *                 @OA\Property(property="gender", type="string", example="Male"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1990-01-01"),
     *                 @OA\Property(
     *                     property="address",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="name", type="string", example="Home"),
     *                         @OA\Property(property="value", type="string", example="123 Main St")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="phone_code", type="array", @OA\Items(type="string", example="The selected phone code is invalid."))
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Account update failed"),
     *             @OA\Property(property="data", type="string", example="Internal Server Error Message")
     *         )
     *     )
     * )
     */



    public function updateAccount(Request $request)
    {
        // Validate request for user information
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'gender' => 'nullable|in:male,female,other',
            'birth_date' => 'nullable|date_format:Y-m-d',
            'address' => 'nullable|string|max:255',
        ]);

        try {
            // Find the country ID based on the phone code

            // Update user information within a transaction
            DB::beginTransaction();

            $user = User::find(Auth::user()->id);
            if ($user) {
                $user->first_name = $request->first_name;
                $user->last_name = $request->last_name;
                $user->phone = empty($request->phone) ? null : json_encode($request->phone);
                $user->gender = empty($request->gender) ? null : $request->gender;
                $user->birth_date = empty($user->birt_date) ? null : $request->birth_date;
                $user->address = $request->address;
                $user->save();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found, Please try again later!',
                ], 404);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Account information updated successfully',
                'data' => $user,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Account update failed',
                'data' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *  path="/api/account/update-password",
     *   tags={"Account | Settings"},
     *  summary="Update user's password",
     *  security={
     *      {"sanctum": {}},
     *  },
     *  description="Update the authenticated user's password.",
     *  @OA\RequestBody(
     *      required=true,
     *      @OA\JsonContent(
     *          @OA\Property(property="current_password", type="string", format="password", minLength=8, description="Current password"),
     *          @OA\Property(property="new_password", type="string", format="password", minLength=8, description="New password"),
     *          @OA\Property(property="new_password_confirmation", type="string", format="password", minLength=8, description="Confirm new password, should match new password"),
     *      )
     *  ),
     *  @OA\Response(
     *      response=200,
     *      description="Password updated successfully",
     *      @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example=true),
     *          @OA\Property(property="message", type="string", example="Password updated successfully"),
     *      )
     *  ),
     *  @OA\Response(
     *      response=401,
     *      description="Unauthorized",
     *      @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example=false),
     *          @OA\Property(property="message", type="string", example="Unauthenticated"),
     *      )
     *  ),
     *  @OA\Response(
     *      response=422,
     *      description="Validation error",
     *      @OA\JsonContent(
     *          @OA\Property(property="success", type="boolean", example=false),
     *          @OA\Property(property="errors", type="object",
     *              @OA\Property(property="current_password", type="array", @OA\Items(type="string", example="The current password is incorrect.")),
     *              @OA\Property(property="new_password", type="array", @OA\Items(type="string", example="The new password and confirmation do not match.")),
     *          ),
     *      )
     *  )
     * )
     */

    public function updatePassword(Request $request, Hash $hash)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d).+$/',
            ],
            [
                'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number',
            ]

        ]);
        try {
            if (!$hash::check($request->current_password, auth()->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect',
                ], 422);
            }

            $user = User::find(Auth::user()->id);
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => $e->getMessage(),
            ], 422);
        }
    }



    // swagger documentation
    /**
     * @OA\Put(
     *    path="/api/account/update-account-image",
     *  tags={"Account | Settings"},
     *   summary="Update user's profile image",
     *  description="Update user's profile image",
     * security={
     *    {"sanctum": {}},
     * },
     * @OA\RequestBody(
     *   required=true,
     *  @OA\JsonContent(
     *      @OA\Property(property="avatar", type="string", format="base64", description="Base64 encoded image"),
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Update user's profile image",
     * @OA\JsonContent()
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
     * )
     * )
     */

    public function updateImage(Request $request)
    {
        // get user profile
        $request->validate([
            'avatar' => 'required|string'
        ]);

        $user = User::find(Auth::user()->id);

        // if exists
        if ($user) {
            // handle media file
            $this->base64Service->processBase64File($user, $request->avatar, 'avatar', true);
            // return success message
            return response()->json([
                'status' => 'success',
                'message' => 'User profile image updated successfully',
            ], 200);
        } else {
            // return error message
            return response()->json([
                'status' => 'error',
                'message' => 'User profile not found',
            ], 404);
        }
    }



    //api/account/logs

    /**
     * @OA\Get(
     *     path="/api/account/auth/logs",
     *     tags={"Account | Settings"},
     *     summary="Get account logs for the authenticated user",
     *     security={
     *         {"sanctum": {}}
     *     },
     *     description="Retrieve authentication logs associated with the authenticated user.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="logs", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="user_id", type="integer", example=1),
     *                     @OA\Property(property="login_at", type="string", format="date-time", example="2024-03-18 08:00:00"),
     *                     @OA\Property(property="logout_at", type="string", format="date-time", example="2024-03-18 08:30:00"),
     *                     @OA\Property(property="ip_address", type="string", example="192.168.1.1"),
     *                 )
     *             ),
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Unauthenticated"),
     *         )
     *     )
     * )
     */


    public function getAccountAuthLogs(Request $request)
    {
        // Get the currently authenticated user
        $user = $request->user();

        // Check if the user is authenticated
        if ($user) {
            // Retrieve authentication logs associated with the user
            $logs = $user->authentications()->latest('login_at')->get();

            // Add the last login time to each record
            $logs->transform(function ($log) {
                $log->last_login = $log->login_at ? Carbon::parse($log->login_at)->diffForHumans() : null;
                return $log;
            });

            // Return the logs in the response
            return response()->json([
                'status' => 'success',
                'message' => 'User authentication logs retrieved successfully!',
                'data' => $logs,
            ]);
        } else {
            // Return an error response if the user is not authenticated
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated.',
            ], 401);
        }
    }
}
