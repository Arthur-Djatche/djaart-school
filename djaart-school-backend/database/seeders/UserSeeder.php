<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@djaart.school'],
            ['name' => 'Super Admin', 'password' => 'password'],
        );

        $admin->assignRole('super_admin');
    }
}
