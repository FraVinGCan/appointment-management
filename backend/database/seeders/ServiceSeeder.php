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
            ['name' => 'Initial Consultation', 'description' => 'A first discussion about the requested service.', 'duration_minutes' => 30, 'active' => true],
            ['name' => 'Follow-up Review', 'description' => 'Review progress and agree on next steps.', 'duration_minutes' => 30, 'active' => true],
            ['name' => 'Legacy Service Review', 'description' => 'An inactive service retained for historical appointments.', 'duration_minutes' => 60, 'active' => false],
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(['name' => $serviceData['name']], $serviceData);
        }
    }
}
