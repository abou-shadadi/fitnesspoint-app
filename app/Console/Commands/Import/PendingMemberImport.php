<?php

namespace App\Console\Commands\Import;

use Illuminate\Console\Command;
use App\Models\Member\MemberImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Member\MembersImport;
use App\Exports\Member\FailedMembersExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        $this->info('Getting pending member imports...');
        $pendingImports = MemberImport::where('status', 'pending')->get();
        $this->info('Found ' . $pendingImports->count() . ' pending imports');

        foreach ($pendingImports as $import) {
            try {
                $this->info("Processing import {$import->id}...");

                // Update the import status to in_progress
                $import->update(['status' => 'in_progress']);

                // Process the file with subscription IDs
                $membersImport = new MembersImport(
                    $import->branch_id,
                    $import->created_by_id,
                    $import->id,
                    $import->plan_id,
                    $import->company_subscription_id,
                );

                // Import without throwing exceptions for individual row errors
                Excel::import($membersImport, storage_path('app/' . $import->file));

                // Get import statistics and failed rows
                $stats = $membersImport->getImportStatistics();
                $failedRows = $membersImport->getFailedRows();

                // Generate failed rows report if there are failures
                $failedReportPath = null;
                $failedReportUrl = null;

                if ($stats['failed_count'] > 0) {
                    $reportData = $this->generateFailedReport($failedRows, $import);
                    $failedReportPath = $reportData['path'] ?? null;
                    $failedReportUrl = $reportData['url'] ?? null;
                }

                // Determine final status
                $status = 'completed';
                if ($stats['failed_count'] > 0) {
                    $status = $stats['success_count'] > 0 ? 'completed_with_errors' : 'failed';
                }

                // Update import with statistics and failed report
                $import->update([
                    'status' => $status,
                    'failed_import_file' => $failedReportPath, // Store the file path
                    'data' => array_merge($import->data ?? [], [
                        'statistics' => $stats,
                        'failed_report_path' => $failedReportPath,
                        'failed_report_url' => $failedReportUrl,
                        'completed_at' => now(),
                        'total_rows' => $stats['success_count'] + $stats['failed_count'],
                    ])
                ]);

                if ($status === 'completed_with_errors') {
                    $this->warn("Import {$import->id} completed with {$stats['failed_count']} errors, {$stats['success_count']} successful");
                    if ($failedReportPath) {
                        $this->info("Failed rows report generated: {$failedReportPath}");
                    }
                } elseif ($status === 'failed') {
                    $this->error("Import {$import->id} failed completely. No records imported. Errors: {$stats['failed_count']}");
                    if ($failedReportPath) {
                        $this->info("Failed rows report generated: {$failedReportPath}");
                    }
                } else {
                    $this->info("Import {$import->id} completed successfully. Created: {$stats['success_count']} members");
                }

            } catch (\Exception $e) {
                // Only handle catastrophic errors that stop the entire import
                $import->update([
                    'status' => 'failed',
                    'data' => array_merge($import->data ?? [], [
                        'error' => $e->getMessage(),
                        'completed_at' => now()
                    ])
                ]);

                $this->error("Failed to process import ID: {$import->id} - " . $e->getMessage());
                Log::error("Import {$import->id} failed: " . $e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }

        $this->info('All pending imports processed.');
    }

    /**
     * Generate failed rows report
     */
    private function generateFailedReport($failedRows, $import)
    {
        try {
            $fileName = 'failed-members-import-' . $import->id . '-' . date('Y-m-d-H-i-s') . '.xlsx';
            $relativePath = 'failed-imports/' . $fileName; // Relative path for storage
            $absolutePath = storage_path('app/' . $relativePath); // Absolute path on server

            // Create directory if it doesn't exist
            $directory = storage_path('app/failed-imports');
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // Export failed rows to Excel
            $failedExport = new FailedMembersExport($failedRows, $import->type);

            // Store the file
            Excel::store($failedExport, $relativePath);

            // Return both relative and absolute paths
            return [
                'path' => $relativePath, // Relative path (e.g., 'failed-imports/filename.xlsx')
                'absolute_path' => $absolutePath, // Full server path
                'url' => Storage::url($relativePath), // URL for downloading
                'filename' => $fileName
            ];
        } catch (\Exception $e) {
            Log::error("Failed to generate failed report: " . $e->getMessage(), [
                'exception' => $e,
                'import_id' => $import->id
            ]);
            return null;
        }
    }
}
