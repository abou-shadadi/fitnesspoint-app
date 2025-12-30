<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice\InvoiceTaxRate;
class InvoiceTaxRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $taxRates = [
            [
                'id' => 1,
                'name' => 'VAT - Value Added Tax - 15%',
                'description' => 'Value Added Tax',
                'rate_type_id' => 1, // percentage
                'rate' => 15,
                'status' => 'active'
            ],
            [
                'id' => 2,
                'name' => 'Income Tax - 18%',
                'description' => 'Income Tax',
                'rate_type_id' => 1, // percentage
                'rate' => 18,
                'status' => 'active'
            ]
        ];


        foreach ($taxRates as $taxRate) {
            if (!InvoiceTaxRate::where('id', $taxRate['id'])->exists()) {
                InvoiceTaxRate::create($taxRate);
            }
        }
    }
}
