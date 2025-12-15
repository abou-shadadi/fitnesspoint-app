<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Token;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller {
	public function rules() {
		return [
			'email' => 'required|string |email',
			'password' => 'required|string',
			// remember me
			'remember_me' => 'boolean | nullable',
		];
	}

	public function messages() {
		return [
			'email.required' => 'Email is required!',
			'password.required' => 'Password is required!',
			'email.email' => 'Email is invalid!',
			'remember_me.boolean' => 'Remember me is invalid!',
		];
	}

	/**
	 * @OA\Post(
	 *     path="/api/auth/login",
	 *     tags={"Account | Auth"},
	 *     summary="Login",
	 *     description="Login",
	 *     @OA\RequestBody(
	 *         description="Login",
	 *         required=true,
	 *        @OA\JsonContent(
	 *           required={"email","password"},
	 *         @OA\Property(property="email", type="string", example="john@example.com"),
	 *      @OA\Property(property="password", type="string", example="password"),
	 *       ),
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Login",
	 *      @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *     @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Unprocessable Entity",
	 *    @OA\JsonContent()
	 *     ),
	 * )
	 */

	public function login(Request $request) {
		// Validate the request
		$this->validate($request, $this->rules(), $this->messages());

		try {
			// Retrieve the user by email
			$user = User::where('email', $request->email)->first();

			// Check if the user exists and if the password is correct
			if ($user && Hash::check($request->password, $user->password)) {
				// Check if the user is active
				if ($user->status === 'active') {
					// Create a token for the user
					$token = $user->createToken('FITNESS')->plainTextToken;

					//	Event::dispatch(new Login('sanctum', $user, true));

					// Return a success response with the user and token

					return response()->json([
						'status' => 'success',
						'message' => 'Login successful',
						'user' => $user,
						'authorization' => [
							'token' => $token,
							'type' => 'bearer',
						],
					], 200);
				} else {

					// Return an error response if the user is not active
					return response()->json([
						'status' => 'error',
						'message' => 'Your account is not active. Please try again later.',
					], 401);
				}
			} else {
				// Return an error response for invalid credentials
				return response()->json([
					'status' => 'error',
					'message' => 'Invalid email or password.',
				], 401);
			}
		} catch (\Exception $e) {
			// Return an error response for unexpected errors
			return response()->json([
				'status' => 'error',
				'message' => 'An unexpected error occurred.',
				'error' => $e->getMessage(),
			], 500);
		}
	}
	// logout

	// swagger documentation
	// we will bearer token to logout

	/**
	 * @OA\Put(
	 *     path="/api/auth/logout",
	 *     tags={"Account | Auth"},
	 *     summary="Logout",
	 *     description="Logout",
	 *     security={
	 *      {"sanctum": {}},
	 *      },
	 *     @OA\Response(
	 *         response=200,
	 *         description="Logout",
	 *       @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent()
	 *
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Unprocessable Entity",
	 *   @OA\JsonContent()
	 *     )
	 * )
	 */

	public function logout(Request $request) {

		// Check if the user is authenticated
		if (Auth::check()) {
			$user = Auth::user();

			// Revoke all tokens associated with the user
			$user->tokens->each(function (PersonalAccessToken $token, $key) {
				$token->delete();
			});

			return response()->json(['message' => 'Logged out successfully']);
		}

		// Return response for unauthenticated users
		return response()->json([
			'status' => 'error',
			'message' => 'User not logged in.',
		], 401);
	}

	// refresh token

	/**
	 * @OA\Post(
	 *     path="/api/auth/refresh",
	 *     tags={"Account | Auth"},
	 *     summary="Refresh Token",
	 *     description="Refresh Token",
	 *     security={
	 *      {"sanctum": {}},
	 *      },
	 *     @OA\Response(
	 *         response=200,
	 *         description="Refresh Token",
	 *      @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *    @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Unprocessable Entity",
	 *   @OA\JsonContent()
	 *     )
	 * )
	 */

	public function refresh(Request $request) {
		$user = $request->user();

		// Check if the user is authenticated
		if ($user) {
			// Refresh the user's token
			$token = $user->createToken('token-name')->plainTextToken;

			return response()->json([
				'status' => 'success',
				'message' => 'Token refreshed successfully',
				'authorization' => [
					'token' => $token,
					'type' => 'bearer',
				],
			], 200);
		}

		// Return an error response if the user is not authenticated
		return response()->json([
			'status' => 'error',
			'message' => 'User not authenticated',
		], 401);
	}

	// me

	/**
	 * @OA\Get(
	 *     path="/api/auth/me",
	 *     tags={"Account | Auth"},
	 *     summary="Me",
	 *     description="Me",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Me",
	 *     ),
	 *     security={
	 *      {"sanctum": {}},
	 *      },
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent()
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Unprocessable Entity",
	 *   @OA\JsonContent()
	 *     )
	 * )
	 */

	public function me() {
		if (Auth::check()) {
			$user = User::with([
                'role.permissions.feature',
                'user_branches'
            ])->find(Auth::user()->id);

			if ($user) {
				return response()->json([
					'status' => 'success',
					'message' => 'Me',
					'user' => $user,
				], 200);
			} else {
				return response()->json([
					'status' => 'error',
					'message' => 'User not found',
				], 404);
			}
		} else {
			return response()->json([
				'status' => 'error',
				'message' => 'Unauthenticated',
			], 401);
		}
	}
}
