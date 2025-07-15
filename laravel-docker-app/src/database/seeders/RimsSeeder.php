<?php

namespace Database\Seeders;

use App\Models\ChecklistTemplate;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RimsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@rims.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // Create test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'user@rims.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // Create modules
        $modules = [
            // Core modules
            [
                'name' => 'Property Management System (PMS)',
                'slug' => 'pms',
                'description' => 'Core property management system integration',
                'category' => 'core',
                'sort_order' => 1,
            ],
            [
                'name' => 'Channel Manager',
                'slug' => 'channel-manager',
                'description' => 'Online distribution channel management',
                'category' => 'core',
                'sort_order' => 2,
            ],
            [
                'name' => 'Booking Engine',
                'slug' => 'booking-engine',
                'description' => 'Direct booking system for hotel website',
                'category' => 'core',
                'sort_order' => 3,
            ],
            // Revenue modules
            [
                'name' => 'Revenue Management',
                'slug' => 'revenue-management',
                'description' => 'Dynamic pricing and revenue optimization',
                'category' => 'revenue',
                'sort_order' => 4,
            ],
            [
                'name' => 'Rate Shopper',
                'slug' => 'rate-shopper',
                'description' => 'Competitor rate monitoring',
                'category' => 'revenue',
                'sort_order' => 5,
                'dependencies' => ['revenue-management'],
            ],
            // Guest Experience modules
            [
                'name' => 'Guest App',
                'slug' => 'guest-app',
                'description' => 'Mobile application for guests',
                'category' => 'guest-experience',
                'sort_order' => 6,
            ],
            [
                'name' => 'Digital Check-in/Check-out',
                'slug' => 'digital-checkin',
                'description' => 'Contactless check-in and check-out',
                'category' => 'guest-experience',
                'sort_order' => 7,
                'dependencies' => ['pms', 'guest-app'],
            ],
            // Operations modules
            [
                'name' => 'Housekeeping Management',
                'slug' => 'housekeeping',
                'description' => 'Room cleaning and maintenance tracking',
                'category' => 'operations',
                'sort_order' => 8,
                'dependencies' => ['pms'],
            ],
            [
                'name' => 'Staff Management',
                'slug' => 'staff-management',
                'description' => 'Employee scheduling and management',
                'category' => 'operations',
                'sort_order' => 9,
            ],
        ];

        foreach ($modules as $moduleData) {
            Module::create($moduleData);
        }

        // Create checklist templates
        $checklistTemplates = [
            [
                'question' => 'What is the size of your property?',
                'description' => 'Number of rooms in your hotel',
                'category' => 'property-info',
                'type' => 'select',
                'options' => ['1-50 rooms', '51-100 rooms', '101-200 rooms', '200+ rooms'],
                'is_required' => true,
                'sort_order' => 1,
                'module_mappings' => [
                    '1-50 rooms' => ['pms', 'booking-engine'],
                    '51-100 rooms' => ['pms', 'booking-engine', 'channel-manager'],
                    '101-200 rooms' => ['pms', 'booking-engine', 'channel-manager', 'revenue-management'],
                    '200+ rooms' => ['pms', 'booking-engine', 'channel-manager', 'revenue-management', 'rate-shopper'],
                ],
            ],
            [
                'question' => 'Do you want to offer mobile check-in?',
                'description' => 'Allow guests to check-in using their mobile devices',
                'category' => 'guest-services',
                'type' => 'boolean',
                'is_required' => true,
                'sort_order' => 2,
                'module_mappings' => [
                    'true' => ['guest-app', 'digital-checkin'],
                ],
            ],
            [
                'question' => 'Do you need housekeeping management?',
                'description' => 'Digital tracking of room cleaning and maintenance',
                'category' => 'operations',
                'type' => 'boolean',
                'is_required' => true,
                'sort_order' => 3,
                'module_mappings' => [
                    'true' => ['housekeeping'],
                ],
            ],
            [
                'question' => 'Do you need staff scheduling features?',
                'description' => 'Manage employee shifts and schedules',
                'category' => 'operations',
                'type' => 'boolean',
                'is_required' => true,
                'sort_order' => 4,
                'module_mappings' => [
                    'true' => ['staff-management'],
                ],
            ],
            [
                'question' => 'What channels do you currently sell through?',
                'description' => 'Select all that apply',
                'category' => 'distribution',
                'type' => 'multiselect',
                'options' => ['Hotel Website', 'Booking.com', 'Expedia', 'Airbnb', 'Other OTAs'],
                'is_required' => true,
                'sort_order' => 5,
                'module_mappings' => [
                    'multiple' => ['channel-manager'],
                ],
            ],
        ];

        foreach ($checklistTemplates as $template) {
            ChecklistTemplate::create($template);
        }
    }
}