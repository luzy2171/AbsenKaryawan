<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Membuat akun Superadmin default
        User::create([
            'name'     => 'Super Admin',
            'username' => 'superadmin',
            'email'    => 'superadmin@example.com',
            'password' => Hash::make('superadmin123'),
            'role'     => 'superadmin',
        ]);
    }
}
