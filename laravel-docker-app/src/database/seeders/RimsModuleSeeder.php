<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RimsModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Erstelle Kategorien
        $categories = [
            'templates' => ModuleCategory::firstOrCreate(
                ['code' => 'templates'],
                [
                    'name' => 'Templates',
                    'description' => 'Document and communication templates',
                    'sort_order' => 1,
                    'is_active' => true,
                ]
            ),
            'communication' => ModuleCategory::firstOrCreate(
                ['code' => 'communication'],
                [
                    'name' => 'Communication',
                    'description' => 'Guest communication and messaging',
                    'sort_order' => 2,
                    'is_active' => true,
                ]
            ),
            'operations' => ModuleCategory::firstOrCreate(
                ['code' => 'operations'],
                [
                    'name' => 'Operations',
                    'description' => 'Daily operations and services',
                    'sort_order' => 3,
                    'is_active' => true,
                ]
            ),
            'training' => ModuleCategory::firstOrCreate(
                ['code' => 'training'],
                [
                    'name' => 'Training',
                    'description' => 'Staff training and resources',
                    'sort_order' => 4,
                    'is_active' => true,
                ]
            ),
        ];

        // Erstelle Module basierend auf der Liste
        $modules = [
            // Templates Kategorie
            [
                'category' => 'templates',
                'modules' => [
                    ['name' => 'Letter Templates', 'code' => 'letter_templates'],
                    ['name' => 'Mailing Templates', 'code' => 'mailing_templates'],
                    ['name' => 'Arrival Letter', 'code' => 'arrival_letter'],
                    ['name' => 'Pre-Departure Letter', 'code' => 'pre_departure_letter'],
                    ['name' => 'Departure Letter', 'code' => 'departure_letter'],
                    ['name' => 'Letter Templates - Loyalty', 'code' => 'letter_templates_loyalty'],
                ]
            ],
            // Communication Kategorie
            [
                'category' => 'communication',
                'modules' => [
                    ['name' => 'Notification Services', 'code' => 'notification_services'],
                    ['name' => 'Auto Welcome Messages', 'code' => 'auto_welcome_messages'],
                    ['name' => 'Guest Service Recovery', 'code' => 'guest_service_recovery'],
                    ['name' => 'Phone Scripts', 'code' => 'phone_scripts'],
                    ['name' => 'Chat Scripts', 'code' => 'chat_scripts'],
                    ['name' => 'Text Messaging', 'code' => 'text_messaging'],
                ]
            ],
            // Operations Kategorie
            [
                'category' => 'operations',
                'modules' => [
                    ['name' => 'Lost and Found', 'code' => 'lost_and_found'],
                    ['name' => 'Room Service', 'code' => 'room_service'],
                    ['name' => 'Spa', 'code' => 'spa'],
                    ['name' => 'Wake-up Call', 'code' => 'wake_up_call'],
                    ['name' => 'Engineering', 'code' => 'engineering'],
                    ['name' => 'Housekeeping', 'code' => 'housekeeping'],
                    ['name' => 'F&B', 'code' => 'f_and_b'],
                    ['name' => 'Front Office', 'code' => 'front_office'],
                    ['name' => 'Valet', 'code' => 'valet'],
                    ['name' => 'Transportation', 'code' => 'transportation'],
                    ['name' => 'Concierge', 'code' => 'concierge'],
                ]
            ],
            // Training Kategorie
            [
                'category' => 'training',
                'modules' => [
                    ['name' => 'Training Material', 'code' => 'training_material'],
                    ['name' => 'Guest Feedback', 'code' => 'guest_feedback'],
                ]
            ],
        ];

        // Erstelle Module
        $sortOrder = 1;
        foreach ($modules as $categoryData) {
            $category = $categories[$categoryData['category']];
            
            foreach ($categoryData['modules'] as $moduleData) {
                // Bestimme die neue Kategorie basierend auf dem Modultyp
                $newCategory = 'single_message'; // Default
                if (str_contains(strtolower($moduleData['name']), 'mailing')) {
                    $newCategory = 'mailing';
                } elseif (str_contains(strtolower($moduleData['name']), 'form') || str_contains(strtolower($moduleData['name']), 'feedback')) {
                    $newCategory = 'form';
                } elseif (str_contains(strtolower($moduleData['name']), 'training') || str_contains(strtolower($moduleData['name']), 'material')) {
                    $newCategory = 'development';
                }
                
                // Beispiel Setup-Felder basierend auf Modultyp
                $setupFields = [];
                if ($newCategory === 'mailing') {
                    $setupFields = [
                        'template_id' => [
                            'type' => 'text',
                            'label' => 'Template ID',
                            'description' => 'Enter the mailing template identifier',
                            'required' => true,
                        ],
                        'subject_line' => [
                            'type' => 'text',
                            'label' => 'Default Subject Line',
                            'description' => 'Default subject for this mailing template',
                            'required' => true,
                        ],
                    ];
                } elseif ($newCategory === 'form') {
                    $setupFields = [
                        'form_url' => [
                            'type' => 'url',
                            'label' => 'Form URL',
                            'description' => 'URL to the feedback/form page',
                            'required' => true,
                            'validation' => 'url',
                        ],
                    ];
                } elseif ($moduleData['code'] === 'text_messaging') {
                    $setupFields = [
                        'sms_provider' => [
                            'type' => 'select',
                            'label' => 'SMS Provider',
                            'description' => 'Select your SMS service provider',
                            'required' => true,
                            'options' => ['Twilio', 'Nexmo', 'MessageBird'],
                        ],
                        'api_key' => [
                            'type' => 'password',
                            'label' => 'API Key',
                            'description' => 'Provider API key',
                            'required' => true,
                        ],
                    ];
                }
                
                Module::updateOrCreate(
                    ['code' => $moduleData['code']],
                    [
                    'name' => $moduleData['name'],
                    'slug' => str_replace('_', '-', $moduleData['code']),
                    'code' => $moduleData['code'],
                    'module_category_id' => $category->id,
                    'description' => $moduleData['name'] . ' module for hotel operations',
                    'category' => $newCategory,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                    'dependencies' => null,
                    'settings' => null,
                    'available_for_chains' => null,
                    'available_for_brands' => null,
                    'required_questions' => null,
                    'conditional_questions' => null,
                    'required_documents' => null,
                    'requires_approval' => in_array($moduleData['code'], ['guest_service_recovery', 'training_material']),
                    'setup_fields' => $setupFields,
                ]
                );
            }
        }
    }
}
