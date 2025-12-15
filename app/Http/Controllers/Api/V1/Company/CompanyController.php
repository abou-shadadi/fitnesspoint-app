<?php

namespace App\Http\Controllers\Api\V1\Company;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanyCombination;
use App\Models\Company\CompanyCombinationClass;
use Illuminate\Support\Facades\Validator;
use App\Services\File\Base64Service;

/**
 * @OA\Schema(
 *     schema="Company",
 *     required={"name", "company_type_id", "status"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         example="Company name"
 *     ),
 *     @OA\Property(
 *         property="company_type_id",
 *         type="integer",
 *         example="1"
 *     ),
 *     @OA\Property(
 *         property="logo",
 *         type="string",
 *         nullable=true,
 *         example="company_logo.jpg"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         nullable=true,
 *         example="company@example.com"
 *     ),
 *               @OA\Property(
 *                 property="phone",
 *                 type="object",
 *                 @OA\Property(property="code", type="string", example="250"),
 *                 @OA\Property(property="number", type="string", example="780000000")
 *             ),
 *     @OA\Property(
 *         property="address",
 *         type="string",
 *         nullable=true,
 *         example="123 Street, City, Country"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         nullable=true,
 *         example="Company description"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"active", "inactive"},
 *         example="active"
 *     )
 * )
 */

class CompanyController extends Controller
{

    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }


    /**
     * @OA\Get(
     *     path="/api/companies",
     *     tags={"Companies"},
     *     summary="Get a list of companies",
     *   security={{"sanctum": {}}},
     *     description="Returns a list of all companies.",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $companies = Company::with([
                'company_type',
            ])->get();

            return response()->json([
                'success' => true,
                'message' => 'Companys retrieved successfully',
                'data' => $companies
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
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Get a company by ID",
     *     description="Returns a single company based on the provided ID.",
     *   security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $company = Company::with([
                'company_type'
            ])->find($id);

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company retrieved successfully',
                'data' => $company
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
     *     path="/api/companies",
     *     tags={"Companies"},
     *     summary="Create a new company",
     *     description="Creates a new company with the provided data.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name", "company_type_id"},
     *                 @OA\Property(
     *                     property="tin",
     *                     type="string",
     *                     nullable=true,
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Company name"
     *                 ),
     *                 @OA\Property(
     *                     property="logo",
     *                     type="string",
     *                     nullable=true,
     *                     example="iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAADoKXV+AAABFElEQVQ4T8WSURGAIAwDQ5M0OQAAAAASUVORK5CYII="
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     nullable=true,
     *                     example="company@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="object",
     *                     @OA\Property(
     *                         property="code",
     *                         type="string",
     *                         example="250"
     *                     ),
     *                     @OA\Property(
     *                         property="number",
     *                         type="string",
     *                         example="780000000"
     *                     )
     *                 ),
    *                 @OA\Property(
     *                     property="company_type_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     nullable=true,
     *                     example="123 Street, City, Country"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     nullable=true,
     *                     example="Company description"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"active", "inactive"},
     *                     example="active"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Company created successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */



    public function store(Request $request)
    {
        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'tin' => 'nullable|string|unique:companies,tin',
            'name' => 'required|string|unique:companies,name',
            'company_type_id' => 'required|exists:company_types,id',
            'logo' => 'nullable|string',
            'email' => 'nullable|email',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            // Create the company
            $company = Company::create([
                'tin' => $request->input('tin'),
                'name' => $request->input('name'),
                'company_type_id' => $request->input('company_type_id'),
                'email' => $request->input('email'),
                'phone' => empty($request->input('phone')) ? null : $request->input('phone'),
                'address' => $request->input('address'),
                'description' => $request->input('description'),
                'status' => $request->input('status')
            ]);

            // Process and store the file
            $this->base64Service->processBase64File($company, $request->input('logo'), 'logo');

            return response()->json([
                'success' => true,
                'message' => 'Company created successfully',
                'data' => $company
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
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Update an existing company",
     *     description="Updates an existing company with the provided data.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the company to update",
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"name", "company_type_id"},
     *                 @OA\Property(
     *                     property="tin",
     *                     type="string",
     *                     nullable=true,
     *                     example="123456789"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string",
     *                     example="Company name"
     *                 ),
     *                 @OA\Property(
     *                     property="company_type_id",
     *                     type="integer",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="logo",
     *                     type="string",
     *                     nullable=true,
     *                     example="company_logo.jpg"
     *                 ),
     *                 @OA\Property(
     *                     property="email",
     *                     type="string",
     *                     nullable=true,
     *                     example="company@example.com"
     *                 ),
     *                 @OA\Property(
     *                     property="phone",
     *                     type="object",
     *                     @OA\Property(
     *                         property="code",
     *                         type="string",
     *                         example="250"
     *                     ),
     *                     @OA\Property(
     *                         property="number",
     *                         type="string",
     *                         example="780000000"
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="address",
     *                     type="string",
     *                     nullable=true,
     *                     example="123 Street, City, Country"
     *                 ),
     *                 @OA\Property(
     *                     property="description",
     *                     type="string",
     *                     nullable=true,
     *                     example="Company description"
     *                 ),
     *                 @OA\Property(
     *                     property="status",
     *                     type="string",
     *                     enum={"active", "inactive"},
     *                     example="active"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Company updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */

    public function update(Request $request, $id)
    {
        // Find the company
        $company = Company::find($id);

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Validate incoming request
        $validator = Validator::make($request->all(), [
            'tin' => 'nullable|string|unique:companies,tin,' . $company->id,
            'name' => 'required|string|unique:companies,name,' . $company->id,
            'company_type_id' => 'exists:company_types,id',
            'logo' => 'nullable|string',
            'email' => 'nullable|email',
            'phone.code' => 'required_with:phone|exists:countries,phone_code',
            'phone.number' => 'required_with:phone',
            'address' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            // Update the company
            $company->update([
                'tin' => $request->input('tin'),
                'name' => $request->input('name'),
                'company_type_id' => $request->input('company_type_id'),
                'email' => $request->input('email'),
                'phone' => empty($request->input('phone')) ? null : $request->input('phone'),
                'address' => $request->input('address'),
                'description' => $request->input('description'),
                'status' => $request->input('status')
            ]);


            $this->base64Service->processBase64File($company, $request->input('logo'), 'logo', true);

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company
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
     *     path="/api/companies/{id}",
     *     tags={"Companies"},
     *     summary="Delete a company",
     *   security={{"sanctum": {}}},
     *     description="Deletes a company by ID.",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the company",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Company deleted successfully"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $company = Company::findOrFail($id);
            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Company deleted successfully',
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
