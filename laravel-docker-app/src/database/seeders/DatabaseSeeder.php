<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );
        
        // Create admin user if not exists
        User::updateOrCreate(
            ['email' => 'debug@rims.live'],
            [
                'name' => 'Admin',
                'password' => bcrypt('levinistganztoll'),
                'is_super_admin' => true,
            ]
        );
    }
}
