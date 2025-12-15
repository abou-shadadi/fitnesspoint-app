<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Billing\BillingType;

class BillingTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            1 => [
                'name' => 'Per-Pass Billing',
                'description' => 'Company is charged based on how many members use the service.',
                'key' => 'per_pass',
                'status' => 'active'
            ],
            2 => [
                'name' => 'Retail Fixed Billing',
                'description' => 'Company is billed a constant recurring amount, regardless of usage.',
                'key' => 'retail_fixed',
                'status' => 'active'
            ],
        ];

        foreach ($serviceTypes as $id => $data) {
            // If this specific ID does not exist, insert it
            if (!BillingType::where('id', $id)->exists()) {
                BillingType::create(array_merge(['id' => $id], $data));
            }
        }
    }
}
