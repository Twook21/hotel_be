<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Admin user
        User::create([
            'name' => 'Admin Hotel',
            'email' => 'admin@hotel.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'phone' => '081234567890',
            'address' => null,
            'email_verified_at' => now(),
        ]);

        // Regular users
        $users = [
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567891',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567892',
                'address' => 'Jl. Thamrin No. 456, Jakarta',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Ahmad Wijaya',
                'email' => 'ahmad@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567893',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sari Indah',
                'email' => 'sari@example.com',
                'password' => Hash::make('password123'),
                'role' => 'user',
                'phone' => '081234567894',
                'address' => 'Jl. Kuningan No. 321, Jakarta',
                'email_verified_at' => now(),
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
