<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $guarded = [];
    use HasFactory;


    public function cell()
    {

        return $this->belongsTo(Cell::class);
    }



    public function fullAddress()
    {

        $cellName = isset($this->cell) && $this->cell->name ? $this->cell->name : null;

        $sectorName = isset($this->cell->sector) && $this->cell->sector->name ? $this->cell->sector->name : null;

        $districtName = isset($this->cell->sector->district) && $this->cell->sector->district->name ? $this->cell->sector->district->name : null;

        $provinceName = isset($this->cell->sector->district->province) && $this->cell->sector->district->province->name ? $this->cell->sector->district->province->name : null;



        return [
            "id" => $this->id,

            "name" => $provinceName . ' / ' . $districtName . ' / ' . $sectorName . ' / ' . $cellName . ' / ' . $this->name
        ];
    }
}
