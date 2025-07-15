<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateModulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update existing modules with new category and setup_fields
        $modules = Module::all();
        
        foreach ($modules as $module) {
            // Determine category based on module name/code
            $newCategory = 'single_message'; // Default
            
            if (str_contains(strtolower($module->name), 'mailing')) {
                $newCategory = 'mailing';
            } elseif (str_contains(strtolower($module->name), 'form') || str_contains(strtolower($module->name), 'feedback')) {
                $newCategory = 'form';
            } elseif (str_contains(strtolower($module->name), 'training') || str_contains(strtolower($module->name), 'material')) {
                $newCategory = 'development';
            } elseif (str_contains(strtolower($module->name), 'landing') || $module->code === 'spa') {
                $newCategory = 'landingpage';
            }
            
            // Example setup fields based on module type
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
                    'response_email' => [
                        'type' => 'email',
                        'label' => 'Response Email',
                        'description' => 'Email to receive form submissions',
                        'required' => true,
                        'validation' => 'email',
                    ],
                ];
            } elseif ($newCategory === 'landingpage') {
                $setupFields = [
                    'page_title' => [
                        'type' => 'text',
                        'label' => 'Page Title',
                        'description' => 'Title for the landing page',
                        'required' => true,
                    ],
                    'page_slug' => [
                        'type' => 'text',
                        'label' => 'Page URL Slug',
                        'description' => 'URL slug for the page (e.g., spa-services)',
                        'required' => true,
                        'validation' => 'alpha_dash',
                    ],
                ];
            } elseif ($module->code === 'text_messaging') {
                $setupFields = [
                    'sms_provider' => [
                        'type' => 'select',
                        'label' => 'SMS Provider',
                        'description' => 'Select your SMS service provider',
                        'required' => true,
                        'options' => ['Twilio', 'Nexmo', 'MessageBird', 'AWS SNS'],
                    ],
                    'api_key' => [
                        'type' => 'password',
                        'label' => 'API Key',
                        'description' => 'Provider API key or access key',
                        'required' => true,
                    ],
                    'api_secret' => [
                        'type' => 'password',
                        'label' => 'API Secret',
                        'description' => 'Provider API secret (if required)',
                        'required' => false,
                    ],
                    'sender_id' => [
                        'type' => 'text',
                        'label' => 'Sender ID/Number',
                        'description' => 'SMS sender identification',
                        'required' => true,
                    ],
                ];
            } elseif ($module->code === 'notification_services') {
                $setupFields = [
                    'notification_channels' => [
                        'type' => 'multiselect',
                        'label' => 'Notification Channels',
                        'description' => 'Select enabled notification channels',
                        'required' => true,
                        'options' => ['Email', 'SMS', 'Push', 'In-App'],
                    ],
                    'webhook_url' => [
                        'type' => 'url',
                        'label' => 'Webhook URL',
                        'description' => 'URL for receiving notifications (optional)',
                        'required' => false,
                        'validation' => 'url',
                    ],
                ];
            } elseif (in_array($module->code, ['wake_up_call', 'room_service'])) {
                $setupFields = [
                    'service_hours' => [
                        'type' => 'text',
                        'label' => 'Service Hours',
                        'description' => 'Operating hours (e.g., 24/7 or 6AM-11PM)',
                        'required' => true,
                    ],
                    'contact_extension' => [
                        'type' => 'number',
                        'label' => 'Contact Extension',
                        'description' => 'Internal phone extension',
                        'required' => true,
                        'validation' => 'numeric',
                    ],
                ];
            }
            
            // Update the module
            $module->update([
                'category' => $newCategory,
                'setup_fields' => $setupFields,
            ]);
        }
        
        $this->command->info('Updated ' . $modules->count() . ' modules with new categories and setup fields.');
    }
}