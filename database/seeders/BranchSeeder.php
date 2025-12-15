<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Branch\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = [
            [
                'id' => 1,
                'name' => 'Remera Branch',
                'description' => 'Remera Branch'
            ],
            [
                'id' => 2,
                'name' => 'Kicukiro Branch',
                'description' => 'Kicukiro Branch'
            ],
            [
                'id' => 3,
                'name' => 'Kigali Branch',
                'description' => 'Kigali Branch'
            ]
            ];

            foreach ($branches as $branch) {
                // Check if the branch already exists in the database
                if (!Branch::where('id', $branch['id'])->exists()) {
                    Branch::create($branch);
                }
            }
    }
}
