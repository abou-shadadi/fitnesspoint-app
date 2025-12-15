<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Duration\DurationType;

class DurationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $durationTypes = [
            1 => ['name' => 'Dayly',    'unit' => 'days',   'description' => 'Single day duration', 'status' => 'active'],
            2 => ['name' => 'Weekly',   'unit' => 'weeks',  'description' => 'One week duration',   'status' => 'active'],
            3 => ['name' => 'Monthly',  'unit' => 'months', 'description' => 'One month duration',  'status' => 'active'],
            4 => ['name' => 'Yearly',   'unit' => 'years',  'description' => 'One year duration',   'status' => 'active'],
        ];

        foreach ($durationTypes as $id => $data) {
            // Check if a duration type with this ID exists
            if (!DurationType::where('id', $id)->exists()) {
                DurationType::create(array_merge(['id' => $id], $data));
            }
        }
    }
}
