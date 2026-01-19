<?php

namespace App\Http\Controllers\Api\V1\Member\Import\Log;

use App\Http\Controllers\Controller;
use App\Models\Member\MemberImportLog;
use App\Models\Member\MemberImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MemberImportLogController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/imports/member/{memberImportId}/logs",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     summary="Get all member import logs",
     *     @OA\Response(response="200", description="Get all member import logs"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="Not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function index($memberImportId)
    {
        try {
            $importLogs = MemberImportLog::with('member_import')
                ->where('member_import_id', $memberImportId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Member import logs retrieved successfully',
                'data' => $importLogs
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
     *     path="/api/imports/member/{memberImportId}/logs/{logId}",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Get a member import log by ID",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response="200", description="Get a member import log by ID"),
     *     @OA\Response(response="401", description="Unauthorized"),
     *     @OA\Response(response="404", description="Not found"),
     *     @OA\Response(response="500", description="Internal server error")
     * )
     */
    public function show($memberImportId, $logId)
    {
        try {
            $importLog = MemberImportLog::with('member_import')
                ->where('member_import_id', $memberImportId)
                ->find($logId);

            if (!$importLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import log not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Member import log retrieved successfully',
                'data' => $importLog
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/imports/member/{memberImportId}/logs/{logId}",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Update member import log",
     *     description="Update member import log status",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="is_resolved",
     *                 type="boolean",
     *                 description="Is resolved",
     *                 example=true
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member import log updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member import log updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Member import log not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function update(Request $request, $memberImportId, $logId)
    {
        $validator = Validator::make($request->all(), [
            'is_resolved' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $importLog = MemberImportLog::with('member_import')
                ->where('member_import_id', $memberImportId)
                ->find($logId);

            if (!$importLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import log not found',
                ], 404);
            }

            $importLog->update([
                'is_resolved' => $request->is_resolved
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Member import log updated successfully',
                'data' => $importLog
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/imports/member/{memberImportId}/logs/{logId}/data",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Update member import log data",
     *     description="Update the row data in import log before retrying",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"data"},
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Updated row data",
     *                 example={
     *                     "reference": "MBR-2024-001",
     *                     "first_name": "John",
     *                     "last_name": "Doe",
     *                     "gender": "Male",
     *                     "national_id_number": "1199999999999999",
     *                     "date_of_birth": "1990-01-15",
     *                     "phone": "0788123456",
     *                     "email": "john.doe@example.com",
     *                     "address": "Kigali, Rwanda",
     *                     "membership_start_date": "2024-01-01"
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="update_comment",
     *                 type="string",
     *                 description="Comment about what was updated",
     *                 example="Fixed phone number format"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member import log data updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member import log data updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Member import log not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function updateData(Request $request, $memberImportId, $logId)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'data.first_name' => 'required|string|max:255',
            'data.last_name' => 'required|string|max:255',
            'data.gender' => 'nullable|in:male,female,Male,Female,other,Other',
            'data.date_of_birth' => 'nullable',
            'data.email' => 'nullable|email',
            'data.national_id_number' => 'nullable',
            'data.reference' => 'nullable|string',
            'data.phone' => 'nullable',
            'data.membership_start_date' => 'nullable',
            'data.address' => 'nullable|string|max:500',
            'update_comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $importLog = MemberImportLog::with('member_import')
                ->where('member_import_id', $memberImportId)
                ->find($logId);

            if (!$importLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import log not found',
                ], 404);
            }

            // Get current data
            $currentData = $importLog->data ?? [];

            // Merge with new data (new data overrides old)
            $updatedData = array_merge($currentData, $request->data);

            // Add update metadata
            $updateMetadata = [
                'updated_at' => Carbon::now()->toDateTimeString(),
                'updated_by' => auth()->id(),
                'update_comment' => $request->input('update_comment', 'Data updated manually'),
                'previous_data' => $currentData // Keep previous data for reference
            ];

            // Update the log
            $importLog->update([
                'data' => $updatedData,
                'log_message' => $importLog->log_message . ' | Data updated: ' . $request->input('update_comment', 'Manual update'),
                'is_resolved' => false, // Mark as unresolved since data changed
            ]);

            // Store update metadata separately
            $importLog->update_metadata = $updateMetadata;
            $importLog->save();

            return response()->json([
                'success' => true,
                'message' => 'Member import log data updated successfully',
                'data' => [
                    'log' => $importLog,
                    'update_metadata' => $updateMetadata,
                    'changes' => $this->getDataChanges($currentData, $updatedData)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/imports/member/{memberImportId}/logs/{logId}",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Delete member import log",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Member import log deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Member import log deleted successfully"),
     *             @OA\Property(property="data", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Member import log not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function destroy($memberImportId, $logId)
    {
        try {
            $importLog = MemberImportLog::where('member_import_id', $memberImportId)
                ->find($logId);

            if (!$importLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import log not found',
                    'data' => null
                ], 404);
            }

            $importLog->delete();

            return response()->json([
                'success' => true,
                'message' => 'Member import log deleted successfully',
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
     *     path="/api/imports/member/{memberImportId}/logs",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Create a manual import log",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"log_message"},
     *             @OA\Property(
     *                 property="log_message",
     *                 type="string",
     *                 description="Log message",
     *                 example="Manual log entry"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Additional data",
     *                 example={"key": "value"}
     *             ),
     *             @OA\Property(
     *                 property="is_resolved",
     *                 type="boolean",
     *                 description="Is resolved",
     *                 example=false
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Import log created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Import log created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Validation error"),
     *     @OA\Response(response=404, description="Member import not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function store(Request $request, $memberImportId)
    {
        $validator = Validator::make($request->all(), [
            'log_message' => 'required|string|max:500',
            'data' => 'nullable|array',
            'is_resolved' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $memberImport = MemberImport::find($memberImportId);

            if (!$memberImport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import not found',
                ], 404);
            }

            $importLog = MemberImportLog::create([
                'member_import_id' => $memberImportId,
                'log_message' => $request->log_message,
                'data' => $request->data,
                'is_resolved' => $request->input('is_resolved', false),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import log created successfully',
                'data' => $importLog
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/imports/member/{memberImportId}/logs/{logId}/retry",
     *     tags={"Import | Members | Member Import | Log"},
     *     security={{"sanctum": {}}},
     *     summary="Retry a failed row from import log",
     *     @OA\Parameter(
     *         name="memberImportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="logId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Row retry completed",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Row processed successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Log not found"),
     *     @OA\Response(response=500, description="Internal server error")
     * )
     */
    public function retry($memberImportId, $logId)
    {
        try {
            $importLog = MemberImportLog::with('member_import')
                ->where('member_import_id', $memberImportId)
                ->find($logId);

            if (!$importLog) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member import log not found',
                ], 404);
            }

            // Get the failed row data
            $rowData = $importLog->data;

            if (!$rowData || !is_array($rowData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid row data found in log',
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Get the original import
                $memberImport = $importLog->member_import;

                // Create a new MembersImport instance
                $membersImport = new MemberImport(
                    $memberImport->branch_id,
                    $memberImport->created_by_id,
                    $memberImport->id,
                    $memberImport->member_subscription_id,
                    $memberImport->company_subscription_id
                );

                // Process the single row
                $row = collect($rowData);
                $processedMember = $this->processSingleRow($membersImport, $row);

                // Update log as resolved
                $importLog->update([
                    'is_resolved' => true,
                    'log_message' => 'Successfully retried and processed on ' . Carbon::now()->toDateTimeString(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Row processed successfully',
                    'data' => [
                        'member' => $processedMember,
                        'log' => $importLog,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollBack();

                // Update log with new error
                $importLog->update([
                    'log_message' => "Retry failed: " . $e->getMessage(),
                    'data' => array_merge($rowData, [
                        'retry_error' => $e->getMessage(),
                        'retry_timestamp' => Carbon::now()->toDateTimeString()
                    ])
                ]);

                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retry row: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a single row (helper method)
     */
    private function processSingleRow($membersImport, $row)
    {
        // Validate required fields
        if (empty($row['first_name']) || empty($row['last_name'])) {
            throw new \Exception('First name and last name are required');
        }

        // Process gender
        $gender = $this->processGender($row['gender'] ?? '');

        // Check if member exists
        $this->checkMemberExistence($row);

        // Parse phone
        $phone = $membersImport->parsePhone($row['phone'] ?? null);

        // Prepare member data
        $memberData = [
            'reference' => $row['reference'] ?? $membersImport->generateMemberReference(),
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'gender' => $gender,
            'date_of_birth' => $membersImport->parseDate($row['date_of_birth'] ?? null),
            'email' => $row['email'] ?? null,
            'national_id_number' => $row['national_id_number'] ?? null,
            'phone' => $phone,
            'address' => $row['address'] ?? null,
            'created_by_id' => $membersImport->user->id,
            'branch_id' => $membersImport->branch->id,
        ];

        // Add subscription references if provided
        if ($membersImport->memberSubscriptionId) {
            $memberData['member_subscription_id'] = $membersImport->memberSubscriptionId;
        }

        if ($membersImport->companySubscriptionId) {
            $memberData['company_subscription_id'] = $membersImport->companySubscriptionId;
        }

        // Parse membership start date for individual members
        if (isset($row['membership_start_date'])) {
            $startDate = $membersImport->parseDate($row['membership_start_date']);
            if ($startDate) {
                $memberData['membership_start_date'] = $startDate;
            }
        }

        return \App\Models\Member\Member::create($memberData);
    }

    // Helper methods
    private function processGender($gender)
    {
        if (empty($gender)) {
            return 'other';
        }

        $gender = strtolower(trim($gender));

        if (in_array($gender, ['male', 'm'])) {
            return 'male';
        } elseif (in_array($gender, ['female', 'f'])) {
            return 'female';
        } else {
            return 'other';
        }
    }

    private function checkMemberExistence($row)
    {
        // Check by reference
        if (isset($row['reference']) && \App\Models\Member\Member::where('reference', $row['reference'])->exists()) {
            throw new \Exception('Member with this reference already exists');
        }

        // Check by email
        if (isset($row['email']) && \App\Models\Member\Member::where('email', $row['email'])->exists()) {
            throw new \Exception('Member with this email already exists');
        }

        // Check by national ID
        if (isset($row['national_id_number']) && \App\Models\Member\Member::where('national_id_number', $row['national_id_number'])->exists()) {
            throw new \Exception('Member with this national ID number already exists');
        }
    }

    /**
     * Helper method to get changes between two data arrays
     */
    private function getDataChanges($oldData, $newData)
    {
        $changes = [];

        // Get all unique keys
        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));

        foreach ($allKeys as $key) {
            $oldValue = $oldData[$key] ?? null;
            $newValue = $newData[$key] ?? null;

            if ($oldValue != $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                    'changed' => true
                ];
            }
        }

        return $changes;
    }
}
