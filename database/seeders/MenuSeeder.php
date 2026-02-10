<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Menu\MenuGroup;
use App\Models\Menu\Menu;
use App\Models\Role\RoleMenu;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Default Menu Group
        $defaultGroup = MenuGroup::create([
            'name' => 'Default',
            'description' => 'Default menu group for system navigation',
            'icon' => 'fa-folder',
            'order' => 1,
            'status' => 'active',
        ]);

        // Define menus
        $menus = [
            ['name' => 'Dashboard', 'description' => 'Main dashboard for system overview', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>', 'path' => '/dashboard'],
            ['name' => 'Members', 'description' => 'Manage members of the system', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>', 'path' => '/dashboard/members'],
            ['name' => 'Companies', 'description' => 'Manage Companies', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>', 'path' => '/dashboard/companies'],
            ['name' => 'Attendance', 'description'=>'Manage Attendances', 'icon'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>', 'path' => '/dashboard/attendance'],
            ['name'=> 'Billing', 'description'=> 'Manage billing', 'icon'=>'<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>', 'path' => '/dashboard/billing'],
            ['name' => 'Reports', 'description'=> 'Manage reports', 'icon'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>', 'path' => '/dashboard/reports'],
            ['name'=>'Users', 'description'=> 'Manage users', 'icon'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>', 'path' => '/dashboard/users'],
            ['name'=> 'Settings', 'description'=> 'Manage Settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>', 'path' => '/dashboard/settings'],
            ['name'=> 'Quick CheckIns', 'description'=> 'Manage quick check-in', 'icon'=> '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.658 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>', 'path' => '/dashboard/quick-checkin'],

        ];

        // Create main menus
        $menuIds = [];
        foreach ($menus as $index => $menuData) {
            $menu = Menu::create([
                'name' => $menuData['name'],
                'description' => $menuData['description'],
                'icon' => $menuData['icon'],
                'menu_group_id' => $defaultGroup->id,
                'order' => $index + 1,
                'parent_id' => null,
                'status' => 'active',
            ]);
            $menuIds[$menuData['name']] = ['id' => $menu->id, 'path' => $menuData['path']];
        }



        // Define role-menu associations
        $roleMenus = [
            2 => [ // Administrator
                'Dashboard' => ['status' => 'active',  'is_default' => 1],
                'Members' => ['status' => 'active',  'is_default' => 0],
                'Companies' => ['status' => 'active', 'is_default' => 0],
                'Attendance' => ['status' => 'active', 'is_default' => 0],
                'Quick CheckIns' => ['status' => 'active', 'is_default' => 0],
                'Billing' => ['status' => 'active', 'is_default' => 0],
                'Reports' => ['status' => 'active', 'is_default' => 0],
                'Users' => ['status' => 'active', 'is_default' => 0],
                'Settings' => ['status' => 'active', 'is_default' => 0],
            ],
            3 => [ // Help Desk
                'Dashboard' => ['status' => 'active', 'is_default' => 1],
                'Quick CheckIns' => ['status' => 'active', 'is_default' => 0],
            ],
            4 => [ // Operation
                'Dashboard' => ['status' => 'active', 'is_default' => 1],
                'Members' => ['status' => 'active',  'is_default' => 0],
                'Companies' => ['status' => 'active', 'is_default' => 0],
                'Attendance' => ['status' => 'active', 'is_default' => 0],
                'Quick CheckIns' => ['status' => 'active', 'is_default' => 0],
                'Billing' => ['status' => 'active', 'is_default' => 0],
                'Reports' => ['status' => 'active', 'is_default' => 0],
                'Users' => ['status' => 'active', 'is_default' => 0]
            ],
            6 => [ // Finance
                'Dashboard' => ['status' => 'active', 'is_default' => 1],
                'Members' => ['status' => 'active',  'is_default' => 0],
                'Companies' => ['status' => 'active', 'is_default' => 0],
                'Billing' => ['status' => 'active', 'is_default' => 0],
                'Reports' => ['status' => 'active', 'is_default' => 0],
            ],
        ];

        // Associate menus with roles
        foreach ($roleMenus as $roleId => $roleMenuItems) {
            foreach ($roleMenuItems as $menuName => $menuData) {
                if (isset($menuIds[$menuName])) {
                    RoleMenu::updateOrCreate(
                        ['role_id' => $roleId, 'menu_id' => $menuIds[$menuName]['id']],
                        [
                            'status'     => $menuData['status'],
                            'is_default' => $menuData['is_default'],
                        ]
                    );
                }
            }
        }
    }
}
