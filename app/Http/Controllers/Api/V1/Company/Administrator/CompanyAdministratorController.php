<?php

namespace App\Http\Controllers\Api\V1\Company\Administrator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\CompanyAdministrator;
use App\Models\Company\Company;
use App\Models\CompanyDesignation;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="CompanyAdministrator",
 *     required={"first_name", "last_name", "company_id", "company_designation_id", "status"},
 *     @OA\Property(
 *         property="first_name",
 *         type="string",
 *         example="John"
 *     ),
 *     @OA\Property(
 *         property="last_name",
 *         type="string",
 *         example="Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         nullable=true,
 *         example="john@example.com"
 *     ),
 *             @OA\Property(
 *                 property="phone",
 *                 type="object",
 *                 @OA\Property(property="code", type="string", example="250"),
 *                 @OA\Property(property="number", type="string", example="780000000")
 *             ),
 *     @OA\Property(
 *         property="company_id",
 *         type="integer",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="company_designation_id",
 *         type="integer",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class CompanyAdministratorController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/administrators",
     *     tags={"Company | Administrators"},
     *     summary="Get a list of company administrators",
     *     description="Returns a list of all company administrators for a specific company.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index($companyId)
    {
        try {
            $administrators = CompanyAdministrator::with(['designation'])->where('company_id', $companyId)->get();

            return response()->json([
                'success' => true,
                'message' => 'Company administrators retrieved successfully',
                'data' => $administrators
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/administrators/{id}",
     *     tags={"Company | Administrators"},
     *     summary="Get a company administrator by ID",
     *     description="Returns a single company administrator based on the provided ID for a specific company.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company administrator",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Company administrator not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $id)
    {
        try {
            $administrator = CompanyAdministrator::where('id', $id)->where('company_id', $companyId)->first();

            if (!$administrator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company administrator not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company administrator retrieved successfully',
                'data' => $administrator
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/companies/{companyId}/administrators",
     *     tags={"Company | Administrators"},
     *     summary="Create a new company administrator",
     *     description="Creates a new company administrator for the specified company.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyAdministrator")
     *     ),
     *     @OA\Response(response=201, description="Company administrator created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'nullable|email',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'company_designation_id' => 'required|exists:company_designations,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $company = Company::find($companyId);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }
            $administrator = CompanyAdministrator::updateOrCreate(
                ['company_id' => $companyId, 'company_designation_id' => $request->input('company_designation_id')],
                [
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'email' => $request->input('email'),
                    'phone' => $request->input('phone'),
                    'status' => $request->input('status'),
                    'company_id' => $companyId,
                    'company_designation_id' => $request->input('company_designation_id')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Company administrator created successfully',
                'data' => $administrator
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/companies/{companyId}/administrators/{id}",
     *     tags={"Company | Administrators"},
     *     summary="Update an existing company administrator",
     *     description="Updates an existing company administrator with the provided data for the specified company.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company administrator",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CompanyAdministrator")
     *     ),
     *     @OA\Response(response=200, description="Company administrator updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company administrator not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $id)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'nullable|email',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'company_designation_id' => 'required|exists:company_designations,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $company = Company::find($companyId);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }
            $administrator = CompanyAdministrator::find($id);

            if (!$administrator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company administrator not found',
                    'data' => null
                ], 404);
            }
            $administrator->update([
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'status' => $request->input('status'),
                'company_id' => $companyId,
                'company_designation_id' => $request->input('company_designation_id')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Company administrator updated successfully',
                'data' => $administrator
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{companyId}/administrators/{id}",
     *     tags={"Company | Administrators"},
     *     summary="Delete a company administrator",
     *     description="Deletes a company administrator by its ID for the specified company.",
     *     security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company administrator",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company administrator deleted successfully"),
     *     @OA\Response(response=404, description="Company administrator not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $id)
    {
        try {
            $company = Company::find($companyId);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }
            $administrator = CompanyAdministrator::find($id);

            if (!$administrator) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company administrator not found',
                    'data' => null
                ], 404);
            }
            $administrator->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company administrator deleted successfully',
                'data' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
