<?php

namespace Database\Seeders;

use App\Models\Admin\User\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
//        $permissions = [
//            'organizations.view',
//            'organizations.create',
//            'organizations.update',
//            'organizations.delete',
//            'categories.view',
//            'categories.create',
//            'categories.update',
//            'categories.delete',
//        ];
//
//        foreach ($permissions as $permission) {
//            Permission::firstOrCreate(['name' => $permission]);
//        }
//
//        $admin = Role::firstOrCreate(['name' => 'admin']);
//        $admin->syncPermissions($permissions);
//
//        Role::firstOrCreate(['name' => 'editor'])
//            ->syncPermissions(['organizations.view', 'organizations.update']);

        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'moonshine']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'moonshine']);
    }
}
