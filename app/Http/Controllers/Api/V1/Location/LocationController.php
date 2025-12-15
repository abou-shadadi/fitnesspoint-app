<?php

namespace App\Http\Controllers\Api\V1\Location;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/location/provinces",
     *     tags={"Locations"},
     *     summary="Get Provinces",
     *     description="Get Provinces",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Provinces Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getProvinces()
    {
        $provinces = \App\Models\Location\Province::all();
        return response()->json([
            'status' => 'success',
            'data' => $provinces
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/provinces/{id}",
     *     tags={"Locations"},
     *     summary="Get Province",
     *     description="Get Province",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of province",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Province Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getProvince($id)
    {
        $province = \App\Models\Location\Province::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $province
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/districts",
     *     tags={"Locations"},
     *     summary="Get Districts",
     *     description="Get Districts",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Districts Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getDistricts()
    {
        $districts = \App\Models\Location\District::all();
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/districts/{id}",
     *     tags={"Locations"},
     *     summary="Get District",
     *     description="Get District",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of district",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="District Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getDistrict($id)
    {
        $district = \App\Models\Location\District::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $district
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/sectors",
     *     tags={"Locations"},
     *     summary="Get Sectors",
     *     description="Get Sectors",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Sectors Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getSectors()
    {
        $sectors = \App\Models\Location\Sector::all();
        return response()->json([
            'status' => 'success',
            'data' => $sectors
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/sectors/{id}",
     *     tags={"Locations"},
     *     summary="Get Sector",
     *     description="Get Sector",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of sector",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sector Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getSector($id)
    {
        $sector = \App\Models\Location\Sector::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $sector
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/cells",
     *     tags={"Locations"},
     *     summary="Get Cells",
     *     description="Get Cells",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Cells Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCells()
    {
        $cells = \App\Models\Location\Cell::all();
        return response()->json([
            'status' => 'success',
            'data' => $cells
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/cells/{id}",
     *     tags={"Locations"},
     *     summary="Get Cell",
     *     description="Get Cell",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of cell",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cell Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCell($id)
    {
        $cell = \App\Models\Location\Cell::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $cell
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/villages",
     *     tags={"Locations"},
     *     summary="Get Villages",
     *     description="Get Villages",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Villages Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getVillages()
    {
        $villages = \App\Models\Location\Village::all();
        return response()->json([
            'status' => 'success',
            'data' => $villages
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/villages/{id}",
     *     tags={"Locations"},
     *     summary="Get Village",
     *     description="Get Village",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of village",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Village Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getVillage($id)
    {
        $village = \App\Models\Location\Village::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $village
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/countries",
     *     tags={"Locations"},
     *     summary="Get Countries",
     *     description="Get Countries",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Response(
     *         response=200,
     *         description="Countries Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCountries()
    {
        $countries = \App\Models\Location\Country::all();
        return response()->json([
            'status' => 'success',
            'data' => $countries
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/countries/{id}",
     *     tags={"Locations"},
     *     summary="Get Country",
     *     description="Get Country",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of country",
     *         in="path",
     *         name="id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Country Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCountry($id)
    {
        $country = \App\Models\Location\Country::find($id);
        return response()->json([
            'status' => 'success',
            'data' => $country
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/provinces/{province_id}/districts",
     *     tags={"Locations"},
     *     summary="Get Districts by Province",
     *     description="Get Districts by Province",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of province",
     *         in="path",
     *         name="province_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Districts Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getDistrictsByProvince($provinceId)
    {
        $districts = \App\Models\Location\District::where('province_id', $provinceId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $districts
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/districts/{district_id}/sectors",
     *     tags={"Locations"},
     *     summary="Get Sectors by District",
     *     description="Get Sectors by District",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of district",
     *         in="path",
     *         name="district_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sectors Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getSectorsByDistrict($districtId)
    {
        $sectors = \App\Models\Location\Sector::where('district_id', $districtId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $sectors
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/provinces/{province_id}/sectors",
     *     tags={"Locations"},
     *     summary="Get Sectors by Province",
     *     description="Get Sectors by Province",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of province",
     *         in="path",
     *         name="province_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sectors Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getSectorsByProvince($provinceId)
    {
        $sectors = \App\Models\Location\Sector::where('province_id', $provinceId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $sectors
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/sectors/{sector_id}/cells",
     *     tags={"Locations"},
     *     summary="Get Cells by Sector",
     *     description="Get Cells by Sector",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of sector",
     *         in="path",
     *         name="sector_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cells Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCellsBySector($sectorId)
    {
        $cells = \App\Models\Location\Cell::where('sector_id', $sectorId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $cells
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/districts/{district_id}/cells",
     *     tags={"Locations"},
     *     summary="Get Cells by District",
     *     description="Get Cells by District",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of district",
     *         in="path",
     *         name="district_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cells Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getCellsByDistrict($districtId)
    {
        $cells = \App\Models\Location\Cell::where('district_id', $districtId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $cells
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/sectors/{sector_id}/villages",
     *     tags={"Locations"},
     *     summary="Get Villages by Sector",
     *     description="Get Villages by Sector",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of sector",
     *         in="path",
     *         name="sector_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Villages Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getVillagesBySector($sectorId)
    {
        $villages = \App\Models\Location\Village::where('sector_id', $sectorId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $villages
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/location/cells/{cell_id}/villages",
     *     tags={"Locations"},
     *     summary="Get Villages by Cell",
     *     description="Get Villages by Cell",
     *       security={
     *         {"sanctum": {}},
     *     },
     *     @OA\Parameter(
     *         description="ID of cell",
     *         in="path",
     *         name="cell_id",
     *         required=true,
     *         example="1",
     *         @OA\Schema(
     *             type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Villages Retrieved Successfully",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getVillagesByCell($cellId)
    {
        $villages = \App\Models\Location\Village::where('cell_id', $cellId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $villages
        ]);
    }
}
