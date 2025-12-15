<?php

namespace App\Http\Controllers\Api\V1\Company\Document;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company\Company;
use App\Models\Company\CompanyDocument;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\File\Base64Service;

class CompanyDocumentController extends Controller
{
    protected $base64Service;

    public function __construct(Base64Service $base64Service)
    {
        $this->base64Service = $base64Service;
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/documents",
     *     summary="List company documents",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="group",
     *         in="query",
     *         required=false,
     *         description="Filter documents by group",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="List of company documents"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function index(Request $request, $companyId)
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

            $query = CompanyDocument::where('company_id', $companyId)
                ->with('created_by');

            // Filter by group if provided
            if ($request->has('group') && $request->group) {
                $query->where('group', $request->group);
            }

            $documents = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Company documents retrieved successfully',
                'data' => $documents
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
     *     path="/api/companies/{companyId}/documents",
     *     summary="Upload a company document",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"label", "file"},
     *             @OA\Property(property="group", type="string", example="contracts", description="Document group/category"),
     *             @OA\Property(property="label", type="string", example="Service Agreement"),
     *             @OA\Property(property="description", type="string", example="Annual service agreement document"),
     *             @OA\Property(property="type", type="string", example="pdf", description="Document type"),
     *             @OA\Property(
     *                 property="file",
     *                 type="string",
     *                 description="Base64 encoded file",
     *                 example="data:application/pdf;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAB..."
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Document uploaded successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $companyId)
    {
        // Find company
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'group' => 'nullable|string|max:100',
            'label' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'file' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create document record
            $document = new CompanyDocument([
                'company_id' => $companyId,
                'group' => $request->input('group', 'default'),
                'label' => $request->input('label'),
                'description' => $request->input('description'),
                'type' => $request->input('type'),
                'created_by_id' => auth()->id(),
            ]);

            // Save document first to get ID
            $document->save();

            // Process and store file
            $this->base64Service->processBase64File($document, $request->input('file'), 'file');

            // Load created_by relationship
            $document->load('created_by');

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company document uploaded successfully',
                'data' => $document
            ], 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/companies/{companyId}/documents/{id}",
     *     summary="Get specific company document",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Document details"),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function show($companyId, $id)
    {
        try {
            $document = CompanyDocument::where('company_id', $companyId)
                ->with('created_by')
                ->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company document not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Company document retrieved successfully',
                'data' => $document
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
     * @OA\Put(
     *     path="/api/companies/{companyId}/documents/{id}",
     *     summary="Update company document",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(property="group", type="string", example="contracts"),
     *             @OA\Property(property="label", type="string", example="Updated Service Agreement"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="type", type="string", example="pdf"),
     *             @OA\Property(
     *                 property="file",
     *                 type="string",
     *                 description="Base64 encoded file (optional, only if updating file)"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Document updated successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $companyId, $id)
    {
        // Find document
        $document = CompanyDocument::where('company_id', $companyId)->find($id);

        if (!$document) {
            return response()->json([
                'success' => false,
                'message' => 'Company document not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'group' => 'nullable|string|max:100',
            'label' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|string|max:50',
            'file' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            // Prepare update data
            $updateData = [];
            if ($request->has('group')) $updateData['group'] = $request->input('group');
            if ($request->has('label')) $updateData['label'] = $request->input('label');
            if ($request->has('description')) $updateData['description'] = $request->input('description');
            if ($request->has('type')) $updateData['type'] = $request->input('type');

            // Update document
            $document->update($updateData);

            // Process and update file if provided
            if ($request->has('file') && $request->input('file') !== null) {
                $this->base64Service->processBase64File($document, $request->input('file'), 'file', true);
            }

            // Load created_by relationship
            $document->load('created_by');

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company document updated successfully',
                'data' => $document
            ], 200);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/companies/{companyId}/documents/{id}",
     *     summary="Delete company document",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Document deleted successfully"),
     *     @OA\Response(response=404, description="Document not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($companyId, $id)
    {
        try {
            $document = CompanyDocument::where('company_id', $companyId)->find($id);

            if (!$document) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company document not found',
                    'data' => null
                ], 404);
            }

            // Start database transaction
            DB::beginTransaction();

            try {
                // Delete the document (this should trigger file deletion via Base64Service)
                $document->delete();

                // Commit transaction
                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Company document deleted successfully',
                    'data' => null
                ], 200);
            } catch (\Exception $e) {
                // Rollback transaction on error
                DB::rollBack();
                throw $e;
            }
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
     *     path="/api/companies/{companyId}/documents/groups",
     *     summary="Get list of document groups",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         description="Company ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="List of document groups"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function getGroups($companyId)
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

            $groups = CompanyDocument::where('company_id', $companyId)
                ->distinct('group')
                ->pluck('group')
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Document groups retrieved successfully',
                'data' => $groups
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
     *     path="/api/companies/{companyId}/documents/bulk-upload",
     *     summary="Upload multiple company documents",
     *     tags={"Companies | Documents"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="companyId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"documents"},
     *             @OA\Property(
     *                 property="documents",
     *                 type="array",
     *                 description="Array of documents to upload",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"label", "file"},
     *                     @OA\Property(property="group", type="string", example="contracts"),
     *                     @OA\Property(property="label", type="string", example="Document 1"),
     *                     @OA\Property(property="description", type="string", example="Document description"),
     *                     @OA\Property(property="type", type="string", example="pdf"),
     *                     @OA\Property(
     *                         property="file",
     *                         type="string",
     *                         description="Base64 encoded file"
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Documents uploaded successfully"),
     *     @OA\Response(response=400, description="Bad request"),
     *     @OA\Response(response=404, description="Company not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function bulkUpload(Request $request, $companyId)
    {
        // Find company
        $company = Company::find($companyId);
        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found',
                'data' => null
            ], 404);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'documents' => 'required|array|min:1',
            'documents.*.group' => 'nullable|string|max:100',
            'documents.*.label' => 'required|string|max:255',
            'documents.*.description' => 'nullable|string',
            'documents.*.type' => 'nullable|string|max:50',
            'documents.*.file' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        // Start database transaction
        DB::beginTransaction();

        try {
            $uploadedDocuments = [];
            $failedDocuments = [];

            foreach ($request->input('documents') as $index => $docData) {
                try {
                    // Create document record
                    $document = new CompanyDocument([
                        'company_id' => $companyId,
                        'group' => $docData['group'] ?? 'default',
                        'label' => $docData['label'],
                        'description' => $docData['description'] ?? null,
                        'type' => $docData['type'] ?? null,
                        'created_by_id' => auth()->id(),
                    ]);

                    // Save document
                    $document->save();

                    // Process and store file
                    $this->base64Service->processBase64File($document, $docData['file'], 'file');

                    // Load created_by relationship
                    $document->load('created_by');

                    $uploadedDocuments[] = $document;
                } catch (\Exception $e) {
                    $failedDocuments[] = [
                        'index' => $index,
                        'label' => $docData['label'] ?? 'Unknown',
                        'error' => $e->getMessage()
                    ];
                }
            }

            // Commit transaction
            DB::commit();

            $response = [
                'success' => true,
                'message' => count($uploadedDocuments) . ' document(s) uploaded successfully',
                'data' => [
                    'uploaded' => $uploadedDocuments,
                    'total_uploaded' => count($uploadedDocuments)
                ]
            ];

            // Add warning if some documents failed
            if (!empty($failedDocuments)) {
                $response['warning'] = count($failedDocuments) . ' document(s) failed to upload';
                $response['data']['failed'] = $failedDocuments;
            }

            return response()->json($response, 201);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
