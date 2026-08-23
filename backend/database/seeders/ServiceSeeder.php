<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            ['name' => 'Initial Consultation', 'description' => 'A first discussion about the requested service.', 'active' => true],
            ['name' => 'Follow-up Review', 'description' => 'Review progress and agree on next steps.', 'active' => true],
            ['name' => 'Legacy Service Review', 'description' => 'An inactive service retained for historical appointments.', 'active' => false],
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(['name' => $serviceData['name']], $serviceData);
        }
    }
}
