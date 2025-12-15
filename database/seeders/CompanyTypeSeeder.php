<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companyTypes = [
            [
                'id' => 1,
                'name' => 'Private',
                'description'=> 'Private',
            ],
            [
                'id' => 2,
                'name' => 'Government',
                'description'=> 'Government',
            ],
            [
                'id' => 3,
                'name' => 'NGO',
                'description'=> 'NGO',
            ]
        ];

        foreach ($companyTypes as $companyType) {
            // Check if the company type already exists in the database
            if (!\App\Models\Company\CompanyType::where('id', $companyType['id'])->exists()) {
                \App\Models\Company\CompanyType::create($companyType);
            }
        }
    }
}
