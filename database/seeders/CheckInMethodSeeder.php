<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CheckIn\CheckInMethod;
use Illuminate\Support\Facades\DB;

class CheckInMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Start a transaction
        DB::beginTransaction();

        try {
            // Define the check-in methods with their unique keys
            $methods = [
                [
                    'name' => 'Fingerprint',
                    'description' => 'Check-in using fingerprint biometrics',
                    'key' => 'fingerprint',
                    'status' => 'active'
                ],
                [
                    'name' => 'Signature',
                    'description' => 'Check-in using digital signature',
                    'key' => 'signature',
                    'require_file' => true,
                    'status' => 'active'
                ],
                [
                    'name' => 'Manual',
                    'description' => 'Manual check-in by staff',
                    'key' => 'manual',
                    'status' => 'active'
                ]
            ];

            // Insert or update each method
            foreach ($methods as $method) {
                CheckInMethod::updateOrCreate(
                    ['key' => $method['key']], // Search by unique key
                    $method                    // Update or create with these values
                );
            }

            // Commit the transaction
            DB::commit();

            $this->command->info('Check-in methods seeded successfully!');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();
            $this->command->error('Failed to seed check-in methods: ' . $e->getMessage());
        }
    }
}
