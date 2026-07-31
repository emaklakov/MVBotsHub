<?php

namespace Database\Seeders;

use App\Models\Admin\User\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Сначала роли и разрешения, потом пользователей
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}
