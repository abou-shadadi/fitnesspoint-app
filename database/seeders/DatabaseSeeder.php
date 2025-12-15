<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // lets seed the countries stored in location folder
            \Database\Seeders\Location\CountrySeeder::class,
            \Database\Seeders\Location\ProvinceSeeder::class,
            \Database\Seeders\Location\DistrictSeeder::class,
            \Database\Seeders\Location\SectorSeeder::class,
         \Database\Seeders\Location\CellSeeder::class,
            \Database\Seeders\Location\VillageSeeder::class,
            \Database\Seeders\UserSeeder::class,
            \Database\Seeders\FeatureSeeder::class,
            \Database\Seeders\BranchSeeder::class,
            \Database\Seeders\CompanyTypeSeeder::class,
            \Database\Seeders\CurrencySeeder::class,
            \Database\Seeders\DurationTypeSeeder::class,
            \Database\Seeders\BenefitSeeder::class,
            \Database\Seeders\BillingTypeSeeder::class,
            \Database\Seeders\PaymentMethodSeeder::class,
            \Database\Seeders\CheckInMethodSeeder::class,
            \Database\Seeders\PlanSeeder::class
        ]);
    }
}
