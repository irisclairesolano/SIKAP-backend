<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\EmployerProfile;
use App\Models\JobPost;
use Illuminate\Support\Facades\Hash;

class SampleJobPostSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Sample Employers
        $employers = [
            [
                'name' => 'Nena Cruz',
                'email' => 'nena@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employer',
                'phone' => '09171234567',
                'barangay' => 'San Rafael',
                'municipality' => 'Bulan',
                'verification_status' => 'approved',
                'verification_badge' => true,
                'reputation_score' => 4.9,
                'profile' => [
                    'description' => 'Local retail and domestic service provider in Bulan, Sorsogon. Vetted and active since 2025.',
                    'contact_info' => 'nena@example.com | 09171234567',
                    'reputation_score' => 4.9,
                ]
            ],
            [
                'name' => 'Jose Santos',
                'email' => 'jose@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employer',
                'phone' => '09189876543',
                'barangay' => 'Bibincahan',
                'municipality' => 'Sorsogon City',
                'verification_status' => 'approved',
                'verification_badge' => true,
                'reputation_score' => 4.7,
                'profile' => [
                    'description' => 'Leading local developer specializing in residential and commercial masonry, carpentry, and electrical works.',
                    'contact_info' => 'jose@example.com | 09189876543',
                    'reputation_score' => 4.7,
                ]
            ],
            [
                'name' => 'Don Ramon',
                'email' => 'ramon@example.com',
                'password' => Hash::make('password123'),
                'role' => 'employer',
                'phone' => '09223334444',
                'barangay' => 'Lajong',
                'municipality' => 'Bulan',
                'verification_status' => 'approved',
                'verification_badge' => true,
                'reputation_score' => 4.8,
                'profile' => [
                    'description' => 'Agricultural cooperative providing rice farming and crop cultivation in Sorsogon region.',
                    'contact_info' => 'ramon@example.com | 09223334444',
                    'reputation_score' => 4.8,
                ]
            ]
        ];

        foreach ($employers as $empData) {
            $user = User::updateOrCreate(
                ['email' => $empData['email']],
                [
                    'name' => $empData['name'],
                    'password' => $empData['password'],
                    'role' => $empData['role'],
                    'phone' => $empData['phone'],
                    'barangay' => $empData['barangay'],
                    'municipality' => $empData['municipality'],
                    'verification_status' => $empData['verification_status'],
                    'verification_badge' => $empData['verification_badge'],
                    'reputation_score' => $empData['reputation_score'],
                ]
            );

            EmployerProfile::updateOrCreate(
                ['user_id' => $user->id],
                $empData['profile']
            );
        }

        // 2. Get the created employers IDs
        $nena = User::where('email', 'nena@example.com')->first()->id;
        $jose = User::where('email', 'jose@example.com')->first()->id;
        $ramon = User::where('email', 'ramon@example.com')->first()->id;

        // 3. Create Sample Job Posts
        $jobs = [
            [
                'employer_id' => $nena,
                'title' => 'Residential House Painter Needed',
                'description' => 'Looking for a skilled painter to touch up external walls and apply protective coating on a 2-story home in San Rafael, Bulan. Materials will be provided. Coffee and lunch included.',
                'category' => 'Domestic',
                'barangay' => 'San Rafael',
                'municipality' => 'Bulan',
                'duration_type' => 'project',
                'compensation' => 4500.00,
                'slots' => 2,
                'status' => 'open'
            ],
            [
                'employer_id' => $ramon,
                'title' => 'Rice Farm Harvest Help',
                'description' => 'Looking for 3 farm hands to assist in harvesting crops for a 2-hectare plot in Lajong, Bulan. Experience in agricultural labor or crop cutting is preferred. Daily compensation with free meals.',
                'category' => 'Agriculture',
                'barangay' => 'Lajong',
                'municipality' => 'Bulan',
                'duration_type' => 'daily',
                'compensation' => 450.00,
                'slots' => 3,
                'status' => 'open'
            ],
            [
                'employer_id' => $jose,
                'title' => 'Concrete Mason for Perimeter Fence',
                'description' => 'Need an experienced masonry worker to construct a 15-meter hollow block perimeter fence in Bibincahan, Sorsogon City. Must know how to mix concrete and align blocks correctly.',
                'category' => 'Construction',
                'barangay' => 'Bibincahan',
                'municipality' => 'Sorsogon City',
                'duration_type' => 'project',
                'compensation' => 6000.00,
                'slots' => 1,
                'status' => 'open'
            ],
            [
                'employer_id' => $jose,
                'title' => 'Cabinet Maker Carpenter',
                'description' => 'Cabinet installation and carpentry work for a newly renovated kitchen in Central, Casiguran. Sanding, polishing, and precise alignment are needed. Hand tools required.',
                'category' => 'Skilled Trade',
                'barangay' => 'Central',
                'municipality' => 'Casiguran',
                'duration_type' => 'project',
                'compensation' => 3500.00,
                'slots' => 1,
                'status' => 'open'
            ],
            [
                'employer_id' => $nena,
                'title' => 'Delivery Truck Assistant',
                'description' => 'Looking for a helper to assist in loading and unloading goods for regional transport. Delivery route starts from Zone I Pob., Bulan. Must be physically fit.',
                'category' => 'Transport',
                'barangay' => 'Zone I Pob.',
                'municipality' => 'Bulan',
                'duration_type' => 'daily',
                'compensation' => 500.00,
                'slots' => 2,
                'status' => 'open'
            ]
        ];

        foreach ($jobs as $jobData) {
            // Check if job already exists to prevent duplicate seeding
            if (!JobPost::where('title', $jobData['title'])->where('employer_id', $jobData['employer_id'])->exists()) {
                JobPost::create($jobData);
            }
        }
    }
}
