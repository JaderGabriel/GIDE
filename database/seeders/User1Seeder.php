<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class User1Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $username = 'jadergabriel';
        $name = 'Jader Gabriel';
        $email = 'jadergabriel8@gmail.com';
        $password = '123456789';

        User::updateOrCreate(
            ['id' => 1],
            [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => (bool) env('USER1_IS_ADMIN', true),
                'email_verified_at' => env('USER1_EMAIL_VERIFIED', true) ? now() : null,
            ],
        );
    }
}
