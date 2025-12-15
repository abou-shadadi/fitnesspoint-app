<?php

namespace App\Http\Controllers\Api\V1\Member;

use App\Http\Controllers\Controller;
use App\Models\Member\Member;
use App\Services\File\Base64Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MemberController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/members",
     *     operationId="getMembersList",
     *     tags={"Members"},
     *     summary="List members with optional location filtering",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="province_id", in="query", description="Filter by province", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="district_id", in="query", description="Filter by district", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="sector_id", in="query", description="Filter by sector", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="cell_id", in="query", description="Filter by cell", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="village_id", in="query", description="Filter by village", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="national_id_number", in="query", description="Filter by national ID number", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", @OA\Schema(type="integer", default=50)),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="per_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="next_page_url", type="string", nullable=true),
     *                 @OA\Property(property="prev_page_url", type="string", nullable=true),
     *                 @OA\Property(
     *                     property="data",
     *                     type="array",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="reference", type="string", example="MBR-2025-001"),
     *                         @OA\Property(property="first_name", type="string", example="John"),
     *                         @OA\Property(property="last_name", type="string", example="Doe"),
     *                         @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *                         @OA\Property(property="date_of_birth", type="string", format="date", example="1998-05-03"),
     *                         @OA\Property(
     *                             property="phone",
     *                             type="object",
     *                             @OA\Property(property="code", type="string", example="+250"),
     *                             @OA\Property(property="number", type="string", example="788000000")
     *                         ),
     *                         @OA\Property(property="email", type="string", example="john@example.com"),
     *                         @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
     *                         @OA\Property(property="national_id_number", type="string", example="1199999999999999"),
     *                         @OA\Property(property="province_id", type="integer", example=1),
     *                         @OA\Property(property="district_id", type="integer", example=10),
     *                         @OA\Property(property="sector_id", type="integer", example=55),
     *                         @OA\Property(property="cell_id", type="integer", example=350),
     *                         @OA\Property(property="village_id", type="integer", example=900),
     *                         @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/members/img.jpg"),
     *                         @OA\Property(property="created_at", type="string", format="date-time"),
     *                         @OA\Property(property="updated_at", type="string", format="date-time")
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Member::query();

            $locationFields = ['province_id', 'district_id', 'sector_id', 'cell_id', 'village_id', 'national_id_number'];
            if ($request->filled($locationFields)) {
                $query->where(function ($q) use ($request, $locationFields) {
                    foreach ($locationFields as $field) {
                        if ($request->filled($field)) {
                            $q->where($field, $request->input($field));
                        }
                    }
                });
            }

            $members = $query->with([
                'province',
                'district',
                'sector',
                'cell',
                'village',
                'created_by'
            ])->paginate($request->input('per_page', 50));

            return response()->json([
                'success' => true,
                'data' => $members,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/members/{id}",
     *     operationId="getMemberById",
     *     tags={"Members"},
     *     summary="Get a single member",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Member found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference", type="string", example="MBR-2025-001"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1998-05-03"),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="object",
     *                     @OA\Property(property="code", type="string", example="+250"),
     *                     @OA\Property(property="number", type="string", example="788000000")
     *                 ),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
     *                 @OA\Property(property="national_id_number", type="string", example="1199999999999999"),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *                 @OA\Property(property="district_id", type="integer", example=10),
     *                 @OA\Property(property="sector_id", type="integer", example=55),
     *                 @OA\Property(property="cell_id", type="integer", example=350),
     *                 @OA\Property(property="village_id", type="integer", example=900),
     *                 @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/members/img.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function show($id)
    {
        try {
            $member = Member::with(['province', 'district', 'sector', 'cell', 'village', 'created_by'])->find($id);

            if (!$member) {
                return response()->json(['success' => false, 'message' => 'Member not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $member]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/members",
     *     operationId="storeMember",
     *     tags={"Members"},
     *     summary="Create a new member",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","gender","date_of_birth","province_id","district_id","sector_id","cell_id","village_id"},
     *             @OA\Property(property="reference", type="string", nullable=true, description="Custom reference (auto-generated if empty)"),
     *             @OA\Property(property="first_name", type="string", example="Alice"),
     *             @OA\Property(property="last_name", type="string", example="Smith"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}),
     *             @OA\Property(property="date_of_birth", type="string", format="date", example="1998-05-20"),
     *             @OA\Property(property="phone", type="object", nullable=true,
     *                 @OA\Property(property="code", type="string", example="+250"),
     *                 @OA\Property(property="number", type="string", example="781234567")
     *             ),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="image", type="string", nullable=true, description="Base64 encoded image"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, default="active"),
     *             @OA\Property(property="national_id_number", type="string", nullable=true),
     *             @OA\Property(property="province_id", type="integer"),
     *             @OA\Property(property="district_id", type="integer"),
     *             @OA\Property(property="sector_id", type="integer"),
     *             @OA\Property(property="cell_id", type="integer"),
     *             @OA\Property(property="village_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Member created",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference", type="string", example="MBR-2025-001"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1998-05-03"),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="object",
     *                     @OA\Property(property="code", type="string", example="+250"),
     *                     @OA\Property(property="number", type="string", example="788000000")
     *                 ),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
     *                 @OA\Property(property="national_id_number", type="string", example="1199999999999999"),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *                 @OA\Property(property="district_id", type="integer", example=10),
     *                 @OA\Property(property="sector_id", type="integer", example=55),
     *                 @OA\Property(property="cell_id", type="integer", example=350),
     *                 @OA\Property(property="village_id", type="integer", example=900),
     *                 @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/members/img.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|unique:members,reference',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date|before:today',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'email' => 'nullable|email|unique:members,email',
            'image' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'national_id_number' => 'nullable|string|unique:members,national_id_number',
            'province_id' => 'required|exists:provinces,id',
            'district_id' => 'required|exists:districts,id',
            'sector_id' => 'required|exists:sectors,id',
            'cell_id' => 'required|exists:cells,id',
            'village_id' => 'required|exists:villages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $reference = $request->filled('reference')
                ? $request->reference
                : $this->generateMemberReference();

            $member = Member::create([
                'reference' => $reference,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'phone' => $request->filled('phone') ? json_encode($request->phone) : null,
                'email' => $request->email,
                'status' => $request->input('status', 'active'),
                'national_id_number' => $request->national_id_number,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'sector_id' => $request->sector_id,
                'cell_id' => $request->cell_id,
                'village_id' => $request->village_id,
                'created_by_id' => Auth::id(),
            ]);

            if ($request->filled('image')) {
                $this->base64Service->processBase64File($member, $request->image, 'image');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member created successfully',
                'data' => $member->load(['province', 'district', 'sector', 'cell', 'village', 'created_by']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/members/{id}",
     *     operationId="updateMember",
     *     tags={"Members"},
     *     summary="Update an existing member",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","province_id","district_id","sector_id","cell_id","village_id"},
     *             @OA\Property(property="reference", type="string", nullable=true),
     *             @OA\Property(property="first_name", type="string"),
     *             @OA\Property(property="last_name", type="string"),
     *             @OA\Property(property="gender", type="string", enum={"male","female"}, nullable=true),
     *             @OA\Property(property="date_of_birth", type="string", format="date", nullable=true),
     *             @OA\Property(
     *                 property="phone",
     *                 type="object",
     *                 nullable=true,
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="number", type="string")
     *             ),
     *             @OA\Property(property="email", type="string", format="email", nullable=true),
     *             @OA\Property(property="image", type="string", nullable=true, description="New base64 image (will replace old one)"),
     *             @OA\Property(property="status", type="string", enum={"active","inactive"}, nullable=true),
     *             @OA\Property(property="national_id_number", type="string", nullable=true),
     *             @OA\Property(property="province_id", type="integer"),
     *             @OA\Property(property="district_id", type="integer"),
     *             @OA\Property(property="sector_id", type="integer"),
     *             @OA\Property(property="cell_id", type="integer"),
     *             @OA\Property(property="village_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="reference", type="string", example="MBR-2025-001"),
     *                 @OA\Property(property="first_name", type="string", example="John"),
     *                 @OA\Property(property="last_name", type="string", example="Doe"),
     *                 @OA\Property(property="gender", type="string", enum={"male","female"}, example="male"),
     *                 @OA\Property(property="date_of_birth", type="string", format="date", example="1998-05-03"),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="object",
     *                     @OA\Property(property="code", type="string", example="+250"),
     *                     @OA\Property(property="number", type="string", example="788000000")
     *                 ),
     *                 @OA\Property(property="email", type="string", example="john@example.com"),
     *                 @OA\Property(property="status", type="string", enum={"active","inactive"}, example="active"),
     *                 @OA\Property(property="national_id_number", type="string", example="1199999999999999"),
     *                 @OA\Property(property="province_id", type="integer", example=1),
     *                 @OA\Property(property="district_id", type="integer", example=10),
     *                 @OA\Property(property="sector_id", type="integer", example=55),
     *                 @OA\Property(property="cell_id", type="integer", example=350),
     *                 @OA\Property(property="village_id", type="integer", example=900),
     *                 @OA\Property(property="image_url", type="string", nullable=true, example="https://example.com/members/img.jpg"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'nullable|string|unique:members,reference,' . $id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|in:male,female',
            'date_of_birth' => 'nullable|date|before:today',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'email' => 'nullable|email|unique:members,email,' . $id,
            'image' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'national_id_number' => 'nullable|string|unique:members,national_id_number,' . $id,
            'province_id' => 'required|exists:provinces,id',
            'district_id' => 'required|exists:districts,id',
            'sector_id' => 'required|exists:sectors,id',
            'cell_id' => 'required|exists:cells,id',
            'village_id' => 'required|exists:villages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        DB::beginTransaction();
        try {
            $member = Member::findOrFail($id);

            $member->update([
                'reference' => $request->filled('reference') ? $request->reference : $member->reference,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'gender' => $request->gender ?? $member->gender,
                'date_of_birth' => $request->date_of_birth ?? $member->date_of_birth,
                'phone' => $request->filled('phone') ? json_encode($request->phone) : $member->phone,
                'email' => $request->email ?? $member->email,
                'status' => $request->input('status', $member->status),
                'national_id_number' => $request->national_id_number ?? $member->national_id_number,
                'province_id' => $request->province_id,
                'district_id' => $request->district_id,
                'sector_id' => $request->sector_id,
                'cell_id' => $request->cell_id,
                'village_id' => $request->village_id,
            ]);

            if ($request->filled('image')) {
                $this->base64Service->processBase64File($member, $request->image, 'image', true); // true = replace old
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Member updated successfully',
                'data' => $member->fresh(['province', 'district', 'sector', 'cell', 'village', 'created_by']),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/members/{id}",
     *     operationId="deleteMember",
     *     tags={"Members"},
     *     summary="Delete a member",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Member deleted",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member and related records deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member not found"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $member = Member::find($id);

            if (!$member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member not found',
                ], 404);
            }

            $member->delete();

            return response()->json([
                'success' => true,
                'message' => 'Member and related records deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function generateMemberReference(): string
    {
        do {
            $reference = date('y') . rand(10000, 99999);
        } while (Member::where('reference', $reference)->exists());

        return $reference;
    }
}
