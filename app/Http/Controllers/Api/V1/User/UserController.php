<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Role;
use App\Models\Account\UserRole;
use Illuminate\Support\Facades\Hash;
use Snowfire\Beautymail\Beautymail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Branch\Branch;
use App\Models\User\UserBranch;

/**
 * @OA\Schema(
 *     schema="User",
 *     required={"first_name", "email", "password", "role_id"},
 *     @OA\Property(
 *         property="first_name",
 *         type="string",
 *         example="John"
 *     ),
 *     @OA\Property(
 *         property="last_name",
 *         type="string",
 *         nullable=true,
 *         example="Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         example="john@example.com"
 *     ),
 *     @OA\Property(
 *         property="phone",
 *         type="object",
 *         nullable=true,
 *         @OA\Property(
 *             property="home",
 *             type="string",
 *             example="1234567890"
 *         ),
 *         @OA\Property(
 *             property="mobile",
 *             type="string",
 *             example="1234567890"
 *         )
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         example="password"
 *     ),
 *     @OA\Property(
 *         property="avatar",
 *         type="string",
 *         nullable=true,
 *         example="avatar.jpg"
 *     ),
 *     @OA\Property(
 *         property="birth_date",
 *         type="string",
 *         format="date",
 *         nullable=true,
 *         example="1990-01-01"
 *     ),
 *     @OA\Property(
 *         property="gender",
 *         type="string",
 *         enum={"male", "female", "other"},
 *         nullable=true,
 *         example="male"
 *     ),
 *     @OA\Property(
 *         property="is_admin",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     ),
 *     @OA\Property(
 *         property="email_verification_token",
 *         type="string",
 *         nullable=true,
 *         example="1234567890abcdef"
 *     ),
 *     @OA\Property(
 *         property="email_verified_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2023-01-01T00:00:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="reset_password_token",
 *         type="string",
 *         nullable=true,
 *         example="abcdef1234567890"
 *     ),
 *     @OA\Property(
 *         property="password_reset_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true,
 *         example="2023-01-01T00:00:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="remember_token",
 *         type="string",
 *         nullable=true,
 *         example="abcdef1234567890"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-01T00:00:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2023-01-01T00:00:00.000000Z"
 *     )
 * )
 */


class UserController extends Controller
{


    // get users
    // index
    // swagger documentation
    /**
     * @OA\Get(
     *   path="/api/users",
     *  tags={"Users"},
     * summary="Get Users",
     * description="Get Users",
     * security={{"sanctum": {}}},
     * @OA\Response(
     * response=200,
     * description="Get Users",
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


    public function index(Request $request)
    {
      

        $users = User::with(['role', 'user_branches.branch'])->get();

        $usersWithLastLogin = $users->map(function ($user) {
            $lastLogin = $user->authentications()->max('login_at');
            $lastLoginHumanReadable = $lastLogin ? Carbon::parse($lastLogin)->diffForHumans() : null;
            $user->last_login = $lastLoginHumanReadable;
            return $user;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Users retrieved successfully',
            'data' => $usersWithLastLogin,
        ], 200);
    }


    // show user
    // swagger documentation for show
    /**
     * @OA\Get(
     *   path="/api/users/{id}",
     *  tags={"Users"},
     * summary="Get User",
     * description="Get User",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * description="User id",
     * required=true,
     * in="path",
     * @OA\Schema(
     * type="integer",
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Get User",
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
    public function show($id)
    {
        $user = User::with(['role', 'user_branches.branch'])->find($id);

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        $lastLogin = $user->authentications->isNotEmpty() ? Carbon::parse($user->authentications->first()->login_at)->diffForHumans() : null;

        return response()->json([
            'status' => 'success',
            'message' => 'User fetched successfully',
            'data' => array_merge($user->toArray(), ['last_login' => $lastLogin]),
        ], 200);
    }



    /**
     * @OA\Post(
     *  path="/api/users",
     * tags={"Users"},
     * summary="Create User",
     * description="Create User",
     * security={{"sanctum": {}}},
     * @OA\RequestBody(
     *   required=true,
     *  @OA\JsonContent(
     * required={"first_name","last_name","email","phone_code","phone","role_id","status"},
     * @OA\Property(property="first_name", type="string", example="John"),
     * @OA\Property(property="last_name", type="string", example="Doe"),
     * @OA\Property(property="email", type="string", example="me@fitnesspoint.rw"),
     *             @OA\Property(
     *                 property="phone",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="250"),
     *                 @OA\Property(property="number", type="string", example="780000000")
     *             ),
     * @OA\Property(property="gender", type="string", example="male"),
     * @OA\Property(property="role_id", type="integer", example="1"),
     * @OA\Property(property="birth_date", type="string", example="2021-01-01"),
     * @OA\Property(property="status", type="string", example="active"),
     *          @OA\Property(
     *              property="branches",
     *              type="array",
     *              @OA\Items(type="integer", example=1)
     *          ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Create User",
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



    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'birth_date' => 'nullable',
            'gender' => 'nullable|in:male,female,other',
            'role_id' => 'required|exists:roles,id',
            'status' => 'nullable|in:active,inactive',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id',
        ]);

        DB::beginTransaction();
        try {

            // check if user exists
            $user = User::where('email', $request->email)->first();

            if ($user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User already exists, please try again!',
                ], 401);
            }

            $defaultPassword =  Str::random(8);

            $user = new User();
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->email = $request->email;
            $user->phone = json_encode($request->phone);
            $user->gender = $request->gender;
            $user->birth_date = $request->birth_date;
            $user->role_id = $request->role_id;
            $user->password = Hash::make($defaultPassword);
            $user->status = $request->status;
            $user->save();


            foreach ($request->branches as $branch) {
                if (!Branch::find($branch)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Branch not found, please try again!',
                    ], 404);
                }
                $existsingUserBranch = UserBranch::where('user_id', $user->id)->where('branch_id', $branch)->first();
                if (!$existsingUserBranch) {
                    $userBranch = new UserBranch();
                    $userBranch->user_id = $user->id;
                    $userBranch->branch_id = $branch;
                    $userBranch->status = 'active';
                    $userBranch->save();
                } else {
                    $existsingUserBranch->status = 'active';
                    $existsingUserBranch->save();
                }
            }

            // lets send an email tp verify account

            $newAccountMail = app()->make(Beautymail::class);
            $newAccountMail->send('emails.account.new-account', [
                'user' => $user,
                'default_password' => $defaultPassword
            ], function ($message) use ($user) {
                $message
                    ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                    ->to($user->email, $user->first_name . ' ' . $user->last_name)->subject('New account creation');
            });

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Account created successfully',
                'data' => $user,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error, please try again!',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // update user
    // full swagger documentation for update with all parameters(first_name, last_name, email, phone_code, phone, role_id, status)
    /**
     * @OA\Put(
     *   path="/api/users/{id}",
     *  tags={"Users"},
     * summary="Update User",
     * description="Update User",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * description="User id",
     * required=true,
     * in="path",
     * @OA\Schema(
     * type="integer",
     * )
     * ),
     * @OA\RequestBody(
     *   required=true,
     *  @OA\JsonContent(
     * required={"first_name","last_name","email","phone_code","phone","role_id","status"},
     * @OA\Property(property="first_name", type="string", example="John"),
     * @OA\Property(property="last_name", type="string", example="Doe"),
     * @OA\Property(property="email", type="string", example="me@fitnesspoint.rw"),
     *             @OA\Property(
     *                 property="phone",
     *                 type="object",
     *                 @OA\Property(property="code", type="string", example="250"),
     *                 @OA\Property(property="number", type="string", example="780000000")
     *             ),
     * @OA\Property(property="gender", type="string", example="male"),
     * @OA\Property(property="role_id", type="integer", example="1"),
     * @OA\Property(property="birth_date", type="string", example="2021-01-01"),
     * @OA\Property(property="status", type="string", example="active"),
     *          @OA\Property(
     *              property="branches",
     *              type="array",
     *              @OA\Items(type="integer", example=1)
     *          ),
     * ),
     * ),
     * @OA\Response(
     * response=200,
     * description="Update User",
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


    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'birth_date' => 'nullable',
            'gender' => 'nullable|in:male,female,other',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:active,inactive',
            'branches' => 'nullable|array',
            'branches.*' => 'exists:branches,id'

        ]);

        DB::beginTransaction();
        try {

            // Create a new user instance
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found, please try again!',
                ], 404);
            }


            // Generate a default password
            $defaultPassword = Str::random(8);

            // handle account status change email
            $sendActivationEmail = false;
            $sendDeactivationEmail = false;

            // chech if status was changed
            if ($user->status == 'active' && $request->status == 'inactive') {
                $sendDeactivationEmail = true;
            }

            if ($user->status == 'inactive' && $request->status == 'active') {
                $sendActivationEmail = true;
            }


            if ($sendActivationEmail) {
                $activationEmail = app()->make(Beautymail::class);
                $activationEmail->send('emails.account.activation', ['user' => $user], function ($message) use ($user) {
                    $message
                        ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                        ->to($user->email, $user->name)
                        ->subject('Account Activated');
                });
            }

            if ($sendDeactivationEmail) {
                $deactivationEmail = app()->make(Beautymail::class);
                $deactivationEmail->send('emails.account.deactivation', ['user' => $user], function ($message) use ($user) {
                    $message
                        ->from("no-reply@fitnesspoint.rw", env('MAIL_FROM_NAME'))
                        ->to($user->email, $user->name)
                        ->subject('Account Deactivated');
                });
            }

            $user->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone ? json_encode($request->phone) : null,
                'gender' => $request->gender,
                'birth_date' => $request->birth_date,
                'role_id' => $request->role_id,
                'status' => $request->status
            ]);

            // set all user branched not in provide branches as inactive
            UserBranch::where('user_id', $user->id)->whereNotIn('branch_id', $request->branches)->update(['status' => 'inactive']);

            foreach ($request->branches as $branch) {
                // check if branch exists
                if (!Branch::find($branch)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Branch not found, please try again!',
                    ], 404);
                }
                // check if user already has this branch
                //
                $existsingUserBranch = UserBranch::where('user_id', $user->id)->where('branch_id', $branch)->first();

                if (!$existsingUserBranch) {
                    UserBranch::create([
                        'user_id' => $user->id,
                        'branch_id' => $branch,
                        'status' => 'active'
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user, please try again!',
                'data' => $e->getMessage(),
            ], 500);
        }
    }

    // delete user
    // full swagger documentation for destroy
    /**
     * @OA\Delete(
     *   path="/api/users/{id}",
     *  tags={"Users"},
     * summary="Delete User",
     * description="Delete User",
     * security={{"sanctum": {}}},
     * @OA\Parameter(
     * name="id",
     * description="User id",
     * required=true,
     * in="path",
     * @OA\Schema(
     * type="integer",
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Delete User",
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

    public function destroy(Request $request, $id)
    {
        try {
            // check if user exists
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found, please try again!',
                ], 404);
            }


            // current user mus be an admin
            if (!auth()->user()->is_admin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this user!',
                ], 401);
            }


            //  also check if current user is trying to delete himself
            if ($user->id == auth()->user()->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete yourself!',
                ], 401);
            }

            // delete user
            $user->delete();


            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user, please try again!',
                'data' => $e->getMessage(),
            ], 500);
        }
    }
}
