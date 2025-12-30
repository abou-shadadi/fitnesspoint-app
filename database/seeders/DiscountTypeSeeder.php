<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount\DiscountType;

class DiscountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $discountTypes=[
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

            foreach ($discountTypes as $discountType) {
                if (!DiscountType::where('id', $discountType['id'])->exists()) {
                    DiscountType::create($discountType);
                }
            }
    }
}
