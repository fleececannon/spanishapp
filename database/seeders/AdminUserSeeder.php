<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'chris@shopview.com'],
            [
                'name' => 'Chris',
                // Default admin password — change after first login.
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );
    }
}
