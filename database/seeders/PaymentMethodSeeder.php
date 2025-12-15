<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Payment\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            1 => [
                'name' => 'Cash',
                'description' => 'Cash payment method',
                'status' => 'active'
            ],
            2 => [
                'name' => 'Mobile Money (MOMO Code)',
                'description' => 'MTN or Airtel Mobile Money payments',
                'status' => 'active'
            ],
            3 => [
                'name' => 'Bank',
                'description' => 'Bank transfer or deposit payments',
                'status' => 'active'
            ],
            4 => [
                'name' => 'Other',
                'description' => 'Other payment methods not listed',
                'status' => 'active'
            ],
        ];

        foreach ($methods as $id => $data) {
            PaymentMethod::updateOrCreate(
                ['id' => $id], // match by ID
                $data          // insert or update
            );
        }
    }
}
