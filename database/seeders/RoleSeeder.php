<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */



    public function run(): void
    {
        $roles = [ // id , name, description, status
            [
                'id' => 1,
                'name' => 'Super Admin',
                'description' => 'Super Admin',
                'status' => 'active',
            ],
            [
                'id' => 2,
                'name' => 'Admin',
                'description' => 'Admin',
                'status' => 'active',
            ],
            [
                'id' => 3,
                'name' => 'Help Desk',
                'description' => 'Help Desk',
                'status' => 'active',
            ],
            [
                'id' => 4,
                'name' => 'Operation',
                'description' => 'Operation',
                'status' => 'active',
            ],
            [
                'id' => 5,
                'name' => 'Member',
                'description' => 'Member',
                'status' => 'active',
            ],
            [
                'id' => 6,
                'name' => 'Finance',
                'description' => 'Finance',
                'status' => 'active'
            ]
        ];
        foreach ($roles as $role) {
            // check if role already exists
            $existingRole = \App\Models\Role::where('id', $role['id'])->first();
            if (!$existingRole) {
            }
            \App\Models\Role::create($role);
        }
    }
}
