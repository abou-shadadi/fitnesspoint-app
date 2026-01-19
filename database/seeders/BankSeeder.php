<?php

namespace Database\Seeders;

use App\Models\Bank\Bank;
use App\Models\Bank\BankAccount;
use App\Models\Currency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // First, ensure we have Rwandan Franc currency
            $rwfCurrency = Currency::find(1);

            if (!$rwfCurrency) {
                $this->command->error('RWF currency not found! Please seed currencies first.');
                return;
            }

            // List of popular Rwandan banks
            $banks = [
                [
                    'id' => 1,
                    'name' => 'Bank of Kigali',
                    'description' => 'Leading commercial bank in Rwanda, established in 1966',
                    'status' => 'active'
                ],
                [
                    'id' => 2,
                    'name' => 'I&M Bank Rwanda',
                    'description' => 'Part of I&M Group, offering comprehensive banking services',
                    'status' => 'active'
                ],
                [
                    'id' => 3,
                    'name' => 'Equity Bank Rwanda',
                    'description' => 'Part of Equity Group Holdings, serving East Africa',
                    'status' => 'active'
                ],
                [
                    'id' => 4,
                    'name' => 'Cogebanque',
                    'description' => 'Commercial Bank of Rwanda, established in 1999',
                    'status' => 'active'
                ],
                [
                    'id' => 5,
                    'name' => 'GT Bank Rwanda',
                    'description' => 'Guaranty Trust Bank Rwanda, part of GTBank Group',
                    'status' => 'active'
                ]
            ];

            $this->command->info('Seeding banks...');

            foreach ($banks as $bankData) {
                $bank = Bank::find($bankData['id']);

                if ($bank) {
                    // Update existing bank
                    $bank->update($bankData);
                    $this->command->info("Updated: {$bankData['name']} (ID: {$bankData['id']})");
                } else {
                    // Create new bank with specific ID
                    $bank = Bank::create($bankData);
                    $this->command->info("Created: {$bankData['name']} (ID: {$bankData['id']})");
                }
            }

            // Now create ONLY ONE bank account for Fitness Point
            $this->command->info('Seeding bank account...');

            $bankAccount = [
                'id' => 1,
                'name' => 'Fitness Point',
                'number' => '100027549898',
                'currency_id' => $rwfCurrency->id,
                'bank_id' => 1, // Bank of Kigali
                'status' => 'active'
            ];

            // Get a default user for created_by_id (usually user with ID 1 - admin)
            $defaultUserId = 1;

            $account = BankAccount::find($bankAccount['id']);

            if ($account) {
                // Update existing account
                $account->update(array_merge($bankAccount, ['created_by_id' => $defaultUserId]));
                $this->command->info("Updated account: {$bankAccount['name']} (ID: {$bankAccount['id']})");
            } else {
                // Create new account with specific ID
                BankAccount::create(array_merge($bankAccount, ['created_by_id' => $defaultUserId]));
                $this->command->info("Created account: {$bankAccount['name']} (ID: {$bankAccount['id']})");
            }

            // Delete any other accounts that might exist (optional)
            // BankAccount::where('id', '!=', 1)->delete();
            // $this->command->info('Deleted other accounts (kept only ID 1)');

            DB::commit();
            $this->command->info('Bank seeding completed successfully!');
            $this->command->info('Total banks: ' . Bank::count());
            $this->command->info('Total bank accounts: ' . BankAccount::count());
            $this->command->info('');
            $this->command->info('Bank Account Details:');
            $this->command->info('=====================');
            $this->command->info('Account Name: Fitness Point');
            $this->command->info('Account Number: 100027549898');
            $this->command->info('Bank: Bank of Kigali');
            $this->command->info('Currency: RWF');
            $this->command->info('Status: Active');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding banks: ' . $e->getMessage());
            throw $e;
        }
    }
}
