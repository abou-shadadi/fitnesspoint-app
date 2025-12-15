<?php

namespace Database\Seeders\Location;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location\District;


class DistrictSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //

        $districts = array(
            array('id' => '1', 'province_id' => '1', 'name' => 'Gasabo'),
            array('id' => '2', 'province_id' => '1', 'name' => 'Kicukiro'),
            array('id' => '3', 'province_id' => '1', 'name' => 'Nyarugenge'),
            array('id' => '4', 'province_id' => '2', 'name' => 'Kayonza'),
            array('id' => '5', 'province_id' => '2', 'name' => 'Bugesera'),
            array('id' => '6', 'province_id' => '2', 'name' => 'Gatsibo'),
            array('id' => '7', 'province_id' => '2', 'name' => 'Ngoma'),
            array('id' => '8', 'province_id' => '2', 'name' => 'Nyagatare'),
            array('id' => '9', 'province_id' => '2', 'name' => 'Kirehe'),
            array('id' => '10', 'province_id' => '2', 'name' => 'Rwamagana'),
            array('id' => '16', 'province_id' => '4', 'name' => 'Ruhango'),
            array('id' => '17', 'province_id' => '4', 'name' => 'Gisagara'),
            array('id' => '18', 'province_id' => '4', 'name' => 'Huye'),
            array('id' => '19', 'province_id' => '4', 'name' => 'Nyamagabe'),
            array('id' => '20', 'province_id' => '4', 'name' => 'Nyanza'),
            array('id' => '21', 'province_id' => '4', 'name' => 'Nyaruguru'),
            array('id' => '22', 'province_id' => '4', 'name' => 'Kamonyi'),
            array('id' => '23', 'province_id' => '4', 'name' => 'Muhanga'),
            array('id' => '39', 'province_id' => '3', 'name' => 'Gakenke'),
            array('id' => '40', 'province_id' => '3', 'name' => 'Gicumbi'),
            array('id' => '41', 'province_id' => '3', 'name' => 'Musanze'),
            array('id' => '42', 'province_id' => '3', 'name' => 'Burera'),
            array('id' => '43', 'province_id' => '3', 'name' => 'Rulindo'),

          array('id' => '44', 'province_id' => '3', 'name' => 'Karongi'),
          array('id' => '45', 'province_id' => '3', 'name' => 'Ngororero'),
          array('id' => '46', 'province_id' => '3', 'name' => 'Nyabihu'),
          array('id' => '47', 'province_id' => '3', 'name' => 'Nyamasheke'),
          array('id' => '48', 'province_id' => '3', 'name' => 'Rubavu'),
          array('id' => '49', 'province_id' => '3', 'name' => 'Rusizi'),
          array('id' => '50', 'province_id' => '3', 'name' => 'Rutsiro'),

        );


        // insert or update the records
        foreach ($districts as $district) {

            $existing = District::where('id', $district['id'])->get();
            if ($existing->count() <= 0) {
                District::insert($district);
            } else {
                District::where('id', $district['id'])->update($district);
            }
        }
    }
}
