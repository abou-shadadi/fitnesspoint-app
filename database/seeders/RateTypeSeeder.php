<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Rate\RateType;
class RateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rateTypes=[
            [
                'id' => 1,
                'name' => 'Percentage',
                'description' => 'Percentage',
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Fixed',
                'description' => 'Fixed',
                'status' => 'active'
            ]
        ];

        foreach ($rateTypes as $rateType) {
            if (!RateType::where('id', $rateType['id'])->exists()) {
                RateType::create($rateType);
            }
        }
    }
}
