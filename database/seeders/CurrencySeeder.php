<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // add initial currencies
        $currencies = [
            [
                'id' => 1,
                'name' => 'Rwandan Francs',
                'is_default' => 1,
                'symbol' => 'RWF',
            ]
        ];


        foreach ($currencies as $currency) {

            // check if currency exists
            $currencyExists = \App\Models\Currency::where('id', $currency['id'])->first();

            if (!$currencyExists) {
                $currency_data = new \App\Models\Currency();
                $currency_data->id = $currency['id'];
                $currency_data->name = $currency['name'];
                $currency_data->symbol = $currency['symbol'];
                $currency_data->save();
            } else {
                $currencyExists->name = $currency['name'];
                $currencyExists->symbol = $currency['symbol'];
                $currencyExists->save();
            }
        }
    }
}
