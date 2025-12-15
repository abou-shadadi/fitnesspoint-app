<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Benefit\Benefit;

class BenefitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $benefits = [
            1 => ['name' => 'Full Gym Access', 'description' => 'Access to the full gym facilities', 'status' => 'active'],
            2 => ['name' => 'Basketball Court Access', 'description' => 'Use of basketball court anytime', 'status' => 'active'],
            3 => ['name' => 'Group Fitness Classes', 'description' => 'Participate in all group fitness classes', 'status' => 'active'],
            4 => ['name' => 'Sauna Access', 'description' => 'Access to sauna facilities', 'status' => 'active'],
            5 => ['name' => 'Swimming Pool Access', 'description' => 'Access to the swimming pool', 'status' => 'active'],
            6 => ['name' => 'Personal Locker Access', 'description' => 'Secure personal locker access', 'status' => 'active'],
            7 => ['name' => 'Athletic Gym Access', 'description' => 'Special access to athletic gym area', 'status' => 'active'],
        ];

        foreach ($benefits as $id => $data) {
            // Only insert if this ID doesn't already exist
            if (!Benefit::where('id', $id)->exists()) {
                Benefit::create(array_merge(['id' => $id], $data));
            }
        }
    }
}
