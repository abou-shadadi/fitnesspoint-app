<?php

namespace Database\Seeders;

use App\Models\Company\CompanyDesignation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CompanyDesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->command->info('Seeding company designations...');

            // List of company designations with specific IDs
            $designations = [
                [
                    'id' => 1,
                    'name' => 'Finance Manager',
                    'description' => 'Responsible for financial planning, budgeting, and financial reporting',
                    'status' => 'active'
                ],
                [
                    'id' => 2,
                    'name' => 'Human Resource Manager',
                    'description' => 'Responsible for recruitment, employee relations, and HR policies',
                    'status' => 'active'
                ],
                [
                    'id' => 3,
                    'name' => 'General Manager',
                    'description' => 'Oversees all operations and strategic direction of the company',
                    'status' => 'active'
                ],
                [
                    'id' => 4,
                    'name' => 'Operations Manager',
                    'description' => 'Manages daily operations and ensures operational efficiency',
                    'status' => 'active'
                ],
                [
                    'id' => 5,
                    'name' => 'Sales Manager',
                    'description' => 'Leads sales team and develops sales strategies',
                    'status' => 'active'
                ],
                [
                    'id' => 6,
                    'name' => 'Marketing Manager',
                    'description' => 'Develops and executes marketing campaigns and strategies',
                    'status' => 'active'
                ],
                [
                    'id' => 7,
                    'name' => 'IT Manager',
                    'description' => 'Manages IT infrastructure and technology solutions',
                    'status' => 'active'
                ],
                [
                    'id' => 8,
                    'name' => 'Customer Service Manager',
                    'description' => 'Oversees customer support and ensures customer satisfaction',
                    'status' => 'active'
                ],
                [
                    'id' => 9,
                    'name' => 'Accountant',
                    'description' => 'Handles financial records, bookkeeping, and tax compliance',
                    'status' => 'active'
                ],
                [
                    'id' => 10,
                    'name' => 'Administrative Assistant',
                    'description' => 'Provides administrative support and office management',
                    'status' => 'active'
                ]
            ];

            foreach ($designations as $designationData) {
                $designation = CompanyDesignation::find($designationData['id']);

                if ($designation) {
                    // Update existing designation
                    $designation->update($designationData);
                    $this->command->info("Updated designation: {$designationData['name']} (ID: {$designationData['id']})");
                } else {
                    // Create new designation with specific ID
                    CompanyDesignation::create($designationData);
                    $this->command->info("Created designation: {$designationData['name']} (ID: {$designationData['id']})");
                }
            }

            // Optional: Delete any designations beyond ID 10 to keep the list clean
            // CompanyDesignation::where('id', '>', 10)->delete();

            DB::commit();

            $this->command->info('');
            $this->command->info('Company Designation seeding completed successfully!');
            $this->command->info('Total designations: ' . CompanyDesignation::count());
            $this->command->info('');

            // Display the first 3 designations as requested
            $firstThree = CompanyDesignation::whereIn('id', [1, 2, 3])->get();

            $this->command->info('First 3 Designations:');
            $this->command->info('====================');
            foreach ($firstThree as $designation) {
                $this->command->info("ID: {$designation->id} | Name: {$designation->name} | Status: {$designation->status}");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding company designations: ' . $e->getMessage());
            throw $e;
        }
    }
}
