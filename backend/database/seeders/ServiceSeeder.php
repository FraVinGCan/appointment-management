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
            [
                'name' => 'Business Strategy Session',
                'short_description' => 'Practical guidance for clearer business decisions.',
                'category' => 'Business Advisory',
                'description' => 'This one-on-one session provides a structured space to review current goals, identify the main challenges affecting progress, and consider practical options. You will leave with a clearer understanding of priorities and a set of recommended next steps that can be adapted to your circumstances.',
                'active' => true,
            ],
            [
                'name' => 'Document Review',
                'short_description' => 'A focused review with clear, useful recommendations.',
                'category' => 'Professional Support',
                'description' => 'Bring a document, proposal, process, or other material that would benefit from a second look. The review covers clarity, organization, completeness, and areas that may need attention, followed by practical recommendations for revisions or further action.',
                'active' => true,
            ],
            [
                'name' => 'Project Planning Workshop',
                'short_description' => 'A structured way to turn ideas into an actionable plan.',
                'category' => 'Planning',
                'description' => 'This workshop helps organize a project from early ideas through planned delivery. The discussion covers desired outcomes, key activities, responsibilities, dependencies, and a realistic timeline so that everyone involved has a shared view of the work ahead.',
                'active' => false,
            ],
            [
                'name' => 'Operations Review',
                'short_description' => 'Identify workflow improvements that make everyday work easier.',
                'category' => 'Operations',
                'description' => 'A practical review of how work is currently organized, communicated, and completed. Together we will identify bottlenecks, unnecessary repetition, and opportunities to improve consistency, then outline changes that can be introduced in manageable steps.',
                'active' => true,
            ],
            [
                'name' => 'Training and Development Session',
                'short_description' => 'Guidance to build useful skills and working confidence.',
                'category' => 'Training',
                'description' => 'A focused learning session shaped around the participant\'s goals and current level of experience. It combines discussion, practical examples, and time for questions to support skill development and produce clear actions for continued improvement after the appointment.',
                'active' => true,
            ],
            [
                'name' => 'On-site Assessment',
                'short_description' => 'An in-person review to understand needs in context.',
                'category' => 'Assessment',
                'description' => 'An in-person assessment designed to understand the current situation, gather relevant details, and identify practical requirements. The visit concludes with a summary of observations and suitable recommendations for the next stage of work.',
                'active' => true,
            ],
        ];

        foreach ($services as $serviceData) {
            Service::updateOrCreate(['name' => $serviceData['name']], $serviceData);
        }
    }
}
