<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'staff@example.com'],
            ['name' => 'Business Staff', 'password' => 'password', 'is_staff' => true],
        );
    }
}
