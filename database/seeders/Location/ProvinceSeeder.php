<?php

namespace Database\Seeders\Location;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location\Province;

class ProvinceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $provinces = array(
            array('id' => '1', 'name' => 'City of Kigali'),
            array('id' => '2', 'name' => 'Eastern Province'),
            array('id' => '3', 'name' => 'Northern Province'),
            array('id' => '4', 'name' => 'Southern Province'),
            array('id' => '5', 'name' => 'Western Province')
        );


        // insert or update the records
        foreach ($provinces as $province) {
            $existing = Province::where('id', $province['id'])->get();
            if ($existing->count() <= 0) {
                Province::insert($province);
            }else{
                Province::where('id', $province['id'])->update($province);
            }
        }
    }
}
