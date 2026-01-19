<?php

namespace App\Console\Commands\Import;

use Illuminate\Console\Command;
use App\Models\Member\MemberImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\Member\MembersImport;
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
                    $import->member_subscription_id,
                    $import->company_subscription_id
                );

                // Import without throwing exceptions for individual row errors
                Excel::import($membersImport, storage_path('app/' . $import->file));

                // Get import statistics
                $stats = $membersImport->getImportStatistics();

                if ($stats['failed_count'] > 0) {
                    // Update import with statistics
                    $import->update([
                        'status' => 'completed_with_errors',
                        'data' => [
                            'statistics' => $stats,
                            'completed_at' => now(),
                        ]
                    ]);

                    $this->warn("Import {$import->id} completed with {$stats['failed_count']} errors, {$stats['success_count']} successful");
                } else {
                    // Mark import as completed if no errors
                    $import->update([
                        'status' => 'completed',
                        'data' => [
                            'statistics' => $stats,
                            'completed_at' => now(),
                        ]
                    ]);

                    $this->info("Import {$import->id} completed successfully. Created: {$stats['success_count']} members");
                }

            } catch (\Exception $e) {
                // Only handle catastrophic errors that stop the entire import
                $import->update([
                    'status' => 'failed',
                    'data' => ['error' => $e->getMessage()]
                ]);

                $this->error("Failed to process import ID: {$import->id} - " . $e->getMessage());
                Log::error("Import {$import->id} failed: " . $e->getMessage());
            }
        }

        $this->info('All pending imports processed.');
    }
}
