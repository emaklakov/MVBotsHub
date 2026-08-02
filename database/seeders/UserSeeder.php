<?php

namespace Database\Seeders;

use App\Models\Users\Role;
use App\Models\Users\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'evgeniy.maklakov@gmail.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make(config('auth.super_admin_password', 'password')), // замените на свой пароль
            ]
        );

        $user->is_active = true;
        $user->save();

        // Назначаем роль (ensure — не упадёт, если уже есть)
        $role = Role::findByName('super-admin', 'moonshine');
        $user->assignRole($role);
    }
}
