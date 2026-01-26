<?php

namespace App\Http\Controllers\Api\V1\Member\Import;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberImport;
use App\Models\Member\MemberImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\Member\MemberImportSampleExport;
use Carbon\Carbon;

class MemberImportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/imports/member",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Get all member imports",
     *     @OA\Response(response="200", description="Get all member imports"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function index()
    {
        try {
            $memberImports = MemberImport::with(['user', 'company', 'branch', 'member_import_logs'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Member imports retrieved successfully',
                'data' => $memberImports
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
     *     path="/api/imports/member/{id}",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Get a member import by ID",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Get a member import by ID"),
     *     @OA\Response(response="404", description="Not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function show($id)
    {
        try {
            $memberImport = MemberImport::with(['user', 'company', 'branch', 'member_import_logs'])
                ->find($id);

            if (!$memberImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member import retrieved successfully',
                'data' => $memberImport
            ]);
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
     *     path="/api/imports/member",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Import members",
     *     description="Endpoint to import members",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"file", "branch_id"},
     *                 @OA\Property(
     *                     property="file",
     *                     type="file",
     *                     description="CSV or Excel file containing member data",
     *                     example="members.csv"
     *                 ),
     *                 @OA\Property(
     *                     property="plan_id",
     *                     type="integer",
     *                     description="Plan ID (optional- mainly for members to be registered in a certain domain)",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="company_subscription_id",
     *                     type="integer",
     *                     description="Company subscription ID (optional)",
     *                     example=1
     *                 ),
     *                 @OA\Property(
     *                     property="branch_id",
     *                     type="integer",
     *                     description="Branch ID (required)",
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Members imported successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File stored successfully. It will be processed shortly."),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'branch_id' => 'required|exists:branches,id',
            'plan_id' => 'nullable|exists:plans,id',
            'company_subscription_id' => 'nullable|exists:company_subscriptions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            // Store the uploaded file
            $filePath = $request->file('file')->store('public/member/imports');

            // Create a new member import record
            $memberImport = MemberImport::create([
                'file' => $filePath,
                'branch_id' => $request->branch_id,
                'plan_id' => $request->plan_id,
                'company_subscription_id' => $request->company_subscription_id,
                'created_by_id' => Auth::id(),
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File stored successfully. It will be processed shortly.',
                'data' => $memberImport->load(['user', 'branch'])
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error storing the file: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }


    /**
     * @OA\Put(
     *     path="/api/imports/member/{id}",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Update member import",
     *     description="Update member import details",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="company_id",
     *                 type="integer",
     *                 description="Company ID",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="branch_id",
     *                 type="integer",
     *                 description="Branch ID",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="plan_id",
     *                 type="integer",
     *                 description="Plan ID",
     *                 example=1
     *             ),
     *             @OA\Property(
     *                 property="company_subscription_id",
     *                 type="integer",
     *                 description="Company subscription ID",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member import updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member import updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Member import not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'required|exists:branches,id',
            'plan_id' => 'nullable|exists:plans,id',
            'company_subscription_id' => 'nullable|exists:company_subscriptions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $memberImport = MemberImport::find($id);

            if (!$memberImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import not found',
                    'data' => null
                ], 404);
            }

            // Only allow updates for pending imports
            if ($memberImport->status != 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import must be in pending status to update',
                    'data' => null
                ], 400);
            }

            $memberImport->update([
                'company_id' => $request->company_id,
                'branch_id' => $request->branch_id,
                'plan_id' => $request->plan_id,
                'company_subscription_id' => $request->company_subscription_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member import updated successfully',
                'data' => $memberImport->load(['user', 'company', 'branch'])
            ]);
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
     *     path="/api/imports/member/{id}",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Delete member import",
     *     description="Delete a member import record",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member import deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member import deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member import not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($id)
    {
        try {
            $memberImport = MemberImport::find($id);

            if (!$memberImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import not found',
                    'data' => null
                ], 404);
            }

            // Delete associated files
            if ($memberImport->file && Storage::exists($memberImport->file)) {
                Storage::delete($memberImport->file);
            }

            if ($memberImport->failed_import_file && Storage::exists($memberImport->failed_import_file)) {
                Storage::delete($memberImport->failed_import_file);
            }

            if ($memberImport->imported_file && Storage::exists($memberImport->imported_file)) {
                Storage::delete($memberImport->imported_file);
            }

            // Delete the import record
            $memberImport->delete();

            return response()->json([
                'success' => true,
                'message' => 'Member import deleted successfully',
                'data' => null
            ]);
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
     *     path="/api/imports/member/{id}/retry",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Retry a failed import",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Import retry initiated",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Import retry initiated"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member import not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function retry($id)
    {
        try {
            $memberImport = MemberImport::find($id);

            if (!$memberImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import not found',
                    'data' => null
                ], 404);
            }

            // Reset status to pending for retry
            $memberImport->update([
                'status' => 'pending',
                'failed_import_file' => null,
                'imported_file' => null,
                'data' => null
            ]);

            // Clear old logs
            $memberImport->member_import_logs()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Import retry initiated',
                'data' => $memberImport
            ]);
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
     *     path="/api/imports/member/{id}/download",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Download imported file",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function download($id)
    {
        try {
            $memberImport = MemberImport::find($id);

            if (!$memberImport || !$memberImport->file) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found',
                    'data' => null
                ], 404);
            }

            $filePath = storage_path('app/' . $memberImport->file);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist',
                    'data' => null
                ], 404);
            }

            $filename = 'member_import_' . $id . '_' . Carbon::now()->format('Ymd_His') . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

            return Response::download($filePath, $filename);
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
     *     path="/api/imports/member/{id}/download-failed",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Download failed import file",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=404, description="File not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function downloadFailed($id)
    {
        try {
            $memberImport = MemberImport::find($id);

            if (!$memberImport || !$memberImport->failed_import_file) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed import file not found',
                    'data' => null
                ], 404);
            }

            $filePath = storage_path('app/' . $memberImport->failed_import_file);

            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File does not exist',
                    'data' => null
                ], 404);
            }

            $filename = 'failed_member_import_' . $id . '_' . Carbon::now()->format('Ymd_His') . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

            return Response::download($filePath, $filename);
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
     *     path="/api/imports/member/sample",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Download sample import file",
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Type of member: corporate or individual",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"corporate", "individual"},
     *             default="corporate"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sample file downloaded successfully",
     *         @OA\MediaType(
     *             mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function downloadSample(Request $request)
    {
        try {
            $type = $request->input('type', 'corporate');

            // Validate type parameter
            if (!in_array($type, ['corporate', 'individual'])) {
                $type = 'corporate';
            }

            $filename = 'member_import_sample_' . $type . '_' . Carbon::now()->format('Ymd_His') . '.xlsx';

            return Excel::download(new MemberImportSampleExport($type), $filename);
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
     *     path="/api/imports/member/bulk-status",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Bulk update import status",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"ids", "status"},
     *             @OA\Property(
     *                 property="ids",
     *                 type="array",
     *                 @OA\Items(type="integer", example=1)
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"pending", "in_progress", "completed", "failed", "cancelled"},
     *                 example="cancelled"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk status updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Bulk status updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="updated_count", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:member_imports,id',
            'status' => 'required|in:pending,in_progress,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'data' => null
            ], 400);
        }

        try {
            $updatedCount = MemberImport::whereIn('id', $request->ids)
                ->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Bulk status updated successfully',
                'data' => ['updated_count' => $updatedCount]
            ]);
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
     *     path="/api/imports/member/{id}/stats",
     *     tags={"Import | Members | Member Import"},
     *     security={{"sanctum": {}}},
     *     summary="Get import statistics",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistics retrieved",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Statistics retrieved"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function stats($id)
    {
        try {
            $memberImport = MemberImport::withCount([
                'member_import_logs',
                'member_import_logs as success_count' => function ($query) {
                    $query->where('status', 'success');
                },
                'member_import_logs as error_count' => function ($query) {
                    $query->where('status', 'error');
                }
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved',
                'data' => [
                    'total_records' => $memberImport->member_import_logs_count,
                    'success_count' => $memberImport->success_count,
                    'error_count' => $memberImport->error_count,
                    'progress_percentage' => $memberImport->member_import_logs_count > 0
                        ? round(($memberImport->success_count + $memberImport->error_count) / $memberImport->member_import_logs_count * 100, 2)
                        : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
