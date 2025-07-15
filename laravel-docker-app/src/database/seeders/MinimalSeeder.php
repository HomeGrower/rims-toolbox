<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MinimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@rims.live',
            'password' => Hash::make('kaffeistkalt14'),
            'role' => 'admin',
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->command->info('Admin user created: admin@rims.live / kaffeistkalt14');
    }
}