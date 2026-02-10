<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create three example users if they don't exist
        $users = [
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'phone' => json_encode(['code' => '+1', 'number' => '123456789']),
                'password' => Hash::make('password'),
                'gender' => 'male',
                'status' => 'active',
                'is_admin' => true,
            ]
        ];

        foreach ($users as $userData) {
            $existingUser = User::where('email', $userData['email'])->first();

            // Create user only if it doesn't already exist
            if (!$existingUser) {
                User::create($userData);
            }
        }
    }
}
