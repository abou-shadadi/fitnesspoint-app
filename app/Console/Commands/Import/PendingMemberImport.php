<?php

namespace App\Console\Commands\Import;

use Illuminate\Console\Command;
use App\Models\Member\MemberImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Member\MembersImport;
use App\Exports\Member\FailedMembersExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class PendingMemberImport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:pending-member-import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process pending member imports and store detailed errors';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Getting imports in progress...');
        $inProgressImport = MemberImport::where('status', 'in_progress')->first();

        if ($inProgressImport) {
            $this->warn("There is already an import in progress (ID: {$inProgressImport->id}). Exiting.");
            return;
        }

        $this->info('Getting pending member imports...');
        $pendingImports = MemberImport::where('status', 'pending')->get();
        $this->info('Found ' . $pendingImports->count() . ' pending imports');

        foreach ($pendingImports as $import) {
            try {
                $this->info("Processing import {$import->id}...");

                // Update the import status to in_progress
                $import->update(['status' => 'in_progress']);

                // Process the file
                $membersImport = new MembersImport(
                    $import->company_id,
                    $import->branch_id,
                    $import->created_by_id,
                    $import->id
                );

                Excel::import($membersImport, storage_path('app/' . $import->file));

                // Handle failed rows if any
                $failedRows = $membersImport->getFailedRows();

                if (!empty($failedRows)) {
                    $this->handleFailedRows($failedRows, $import);
                } else {
                    // Mark import as completed if no errors
                    $import->update(['status' => 'completed']);
                }

                $this->info("Successfully processed import ID: {$import->id}");

            } catch (\Exception $e) {
                // Handle exceptions and update the import record
                $import->update([
                    'status' => 'failed',
                    'data' => ['error' => $e->getMessage()]
                ]);

                $this->error("Failed to process import ID: {$import->id} - " . $e->getMessage());
            }
        }

        $this->info('All pending imports processed.');
    }

    private function handleFailedRows($failedRows, $import)
    {
        $timestamp = time();
        $failedFilePath = 'public/member/failed_imports/failed_members_' . $timestamp . '.xlsx';

        // Export failed rows to Excel
        Excel::store(new FailedMembersExport(
            array_map(function ($failedRow) {
                return Arr::except($failedRow, ['row', 'column', 'comment']);
            }, $failedRows)
        ), $failedFilePath);

        // Store detailed error information
        $detailedErrors = array_map(function ($failedRow) {
            return [
                'row_number' => $failedRow['row'] ?? 'Unknown',
                'column' => $failedRow['column'] ?? 'Unknown',
                'comment' => $failedRow['comment'] ?? 'Unknown',
                'data' => Arr::except($failedRow, ['row', 'column', 'comment']),
            ];
        }, $failedRows);

        // Update the import record
        $import->update([
            'failed_import_file' => $failedFilePath,
            'status' => 'completed_with_errors',
            'data' => $detailedErrors
        ]);
    }
}
