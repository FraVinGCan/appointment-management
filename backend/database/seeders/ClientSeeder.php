<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            ['name' => 'Maria Santos', 'email' => 'maria@example.com', 'phone' => '555-0101'],
            ['name' => 'James Wilson', 'email' => 'james@example.com', 'phone' => '555-0102'],
            ['name' => 'Priya Patel', 'email' => 'priya@example.com', 'phone' => '555-0103'],
            ['name' => 'Daniel Kim', 'email' => 'daniel@example.com', 'phone' => '555-0104'],
            ['name' => 'Olivia Brown', 'email' => 'olivia@example.com', 'phone' => '555-0105'],
            ['name' => 'Noah Garcia', 'email' => 'noah@example.com', 'phone' => '555-0106'],
            ['name' => 'Ava Martinez', 'email' => 'ava@example.com', 'phone' => '555-0107'],
            ['name' => 'Ethan Davis', 'email' => 'ethan@example.com', 'phone' => '555-0108'],
            ['name' => 'Sophia Lee', 'email' => 'sophia@example.com', 'phone' => '555-0109'],
            ['name' => 'Lucas Anderson', 'email' => 'lucas@example.com', 'phone' => '555-0110'],
            ['name' => 'Mia Thompson', 'email' => 'mia@example.com', 'phone' => '555-0111'],
            ['name' => 'Henry Moore', 'email' => 'henry@example.com', 'phone' => '555-0112'],
        ];

        foreach ($clients as $clientData) {
            $user = User::updateOrCreate(
                ['email' => $clientData['email']],
                ['name' => $clientData['name'], 'password' => 'password', 'is_admin' => false],
            );

            Client::updateOrCreate(
                ['user_id' => $user->id],
                ['name' => $clientData['name'], 'phone' => $clientData['phone']],
            );
        }
    }
}
