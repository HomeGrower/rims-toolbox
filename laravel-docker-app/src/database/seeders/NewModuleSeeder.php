<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing modules
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('project_modules')->truncate();
        DB::table('document_uploads')->whereNotNull('module_id')->delete();
        Module::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $sortOrder = 1;
        
        // Letter Templates (Single Message category)
        $letterTemplates = [
            ['name' => 'Letter Templates', 'code' => 'letter_templates'],
            ['name' => 'Confirmation', 'code' => 'confirmation'],
            ['name' => 'Cancellation', 'code' => 'cancellation'],
            ['name' => 'Party Confirmation', 'code' => 'party_confirmation'],
            ['name' => 'Reservation Offer', 'code' => 'reservation_offer'],
            ['name' => 'Reservation Offer Reminder', 'code' => 'reservation_offer_reminder', 'dependencies' => ['reservation-offer']],
            ['name' => 'Upgrade Offer', 'code' => 'upgrade_offer'],
            ['name' => 'Upgrade Confirmation', 'code' => 'upgrade_confirmation'],
            ['name' => 'Reservation Amendment', 'code' => 'reservation_amendment'],
            ['name' => 'Transfer Confirmation', 'code' => 'transfer_confirmation'],
            ['name' => 'Restaurant Confirmation', 'code' => 'restaurant_confirmation'],
            ['name' => 'Payment Request', 'code' => 'payment_request'],
            ['name' => 'Payment Reminder', 'code' => 'payment_reminder'],
            ['name' => 'E-Invoice', 'code' => 'e_invoice', 'pms_specific' => 'OPERA_ONPREM'],
        ];
        
        foreach ($letterTemplates as $template) {
            $setupFields = [
                'template_code' => [
                    'type' => 'text',
                    'label' => 'Template Code',
                    'description' => 'Unique identifier for this template',
                    'required' => true,
                ],
                'default_subject' => [
                    'type' => 'text',
                    'label' => 'Default Subject',
                    'description' => 'Default email subject line',
                    'required' => true,
                ],
            ];
            
            // Special fields for E-Invoice
            if ($template['code'] === 'e_invoice') {
                $setupFields['invoice_prefix'] = [
                    'type' => 'text',
                    'label' => 'Invoice Number Prefix',
                    'description' => 'Prefix for invoice numbers (e.g., INV-)',
                    'required' => true,
                ];
            }
            
            Module::create([
                'name' => $template['name'],
                'slug' => str_replace('_', '-', $template['code']),
                'code' => $template['code'],
                'module_category_id' => 1, // Assuming this exists
                'description' => $template['name'] . ' - Automated letter template',
                'category' => 'single_message',
                'sort_order' => $sortOrder++,
                'is_active' => true,
                'dependencies' => $template['dependencies'] ?? null,
                'settings' => isset($template['pms_specific']) ? ['pms_requirement' => $template['pms_specific']] : null,
                'setup_fields' => $setupFields,
                'requires_approval' => false,
            ]);
        }
        
        // Mailings (Mailing category)
        $mailings = [
            ['name' => 'Post-Booking Mailing', 'code' => 'post_booking_mailing'],
            ['name' => 'Room Upsell Mailing', 'code' => 'room_upsell_mailing'],
            ['name' => 'Pre-Arrival Mailing', 'code' => 'pre_arrival_mailing'],
            ['name' => 'E-Registration Mailing', 'code' => 'e_registration_mailing'],
            ['name' => 'In-House Mailing', 'code' => 'in_house_mailing'],
            ['name' => 'Post-Stay Mailing', 'code' => 'post_stay_mailing'],
        ];
        
        foreach ($mailings as $mailing) {
            $setupFields = [
                'campaign_name' => [
                    'type' => 'text',
                    'label' => 'Campaign Name',
                    'description' => 'Name for this mailing campaign',
                    'required' => true,
                ],
                'send_timing' => [
                    'type' => 'select',
                    'label' => 'Send Timing',
                    'description' => 'When to send this mailing',
                    'required' => true,
                    'options' => ['Immediate', '1 hour', '2 hours', '6 hours', '12 hours', '24 hours', '2 days', '3 days', '7 days'],
                ],
                'target_segment' => [
                    'type' => 'multiselect',
                    'label' => 'Target Segments',
                    'description' => 'Guest segments to receive this mailing',
                    'required' => true,
                    'options' => ['All Guests', 'VIP', 'Loyalty Members', 'First Time Guests', 'Returning Guests', 'Business Travelers', 'Leisure Travelers'],
                ],
            ];
            
            // Special fields for specific mailings
            if ($mailing['code'] === 'room_upsell_mailing') {
                $setupFields['upsell_categories'] = [
                    'type' => 'multiselect',
                    'label' => 'Room Categories to Offer',
                    'description' => 'Which room upgrades to offer',
                    'required' => true,
                    'options' => ['Suite', 'Club Floor', 'Ocean View', 'Higher Floor', 'Larger Room'],
                ];
            }
            
            Module::create([
                'name' => $mailing['name'],
                'slug' => str_replace('_', '-', $mailing['code']),
                'code' => $mailing['code'],
                'module_category_id' => 1,
                'description' => $mailing['name'] . ' - Automated marketing communication',
                'category' => 'mailing',
                'sort_order' => $sortOrder++,
                'is_active' => true,
                'setup_fields' => $setupFields,
                'requires_approval' => false,
            ]);
        }
        
        // Landing Pages (Landingpage category)
        $landingPages = [
            ['name' => 'Offer Reconfirmation', 'code' => 'offer_reconfirmation', 'dependencies' => ['reservation-offer']],
            ['name' => 'E-Registration Form', 'code' => 'e_registration_form'],
            ['name' => 'Preference Planner', 'code' => 'preference_planner'],
            ['name' => 'Room Upsell', 'code' => 'room_upsell'],
            ['name' => 'Package Upsell', 'code' => 'package_upsell'],
            ['name' => 'Transfer Request', 'code' => 'transfer_request'],
        ];
        
        foreach ($landingPages as $page) {
            $setupFields = [
                'page_url_slug' => [
                    'type' => 'text',
                    'label' => 'Page URL Slug',
                    'description' => 'URL path for this page (e.g., /room-upgrade)',
                    'required' => true,
                    'validation' => 'alpha_dash',
                ],
                'page_title' => [
                    'type' => 'text',
                    'label' => 'Page Title',
                    'description' => 'Title displayed on the page',
                    'required' => true,
                ],
                'primary_color' => [
                    'type' => 'color',
                    'label' => 'Primary Brand Color',
                    'description' => 'Main color for buttons and highlights',
                    'required' => true,
                    'default_value' => '#000000',
                ],
            ];
            
            // Special fields for specific pages
            if ($page['code'] === 'e_registration_form') {
                $setupFields['required_fields'] = [
                    'type' => 'multiselect',
                    'label' => 'Required Registration Fields',
                    'description' => 'Which fields must guests complete',
                    'required' => true,
                    'options' => ['Passport/ID', 'Address', 'Phone', 'Email', 'Arrival Time', 'Departure Time', 'Special Requests', 'Dietary Restrictions'],
                ];
            } elseif (in_array($page['code'], ['room_upsell', 'package_upsell'])) {
                $setupFields['display_mode'] = [
                    'type' => 'select',
                    'label' => 'Display Mode',
                    'description' => 'How to display upgrade options',
                    'required' => true,
                    'options' => ['Grid View', 'List View', 'Carousel', 'Comparison Table'],
                ];
            } elseif ($page['code'] === 'transfer_request') {
                $setupFields['transfer_options'] = [
                    'type' => 'multiselect',
                    'label' => 'Available Transfer Types',
                    'description' => 'Transportation options to offer',
                    'required' => true,
                    'options' => ['Airport Shuttle', 'Private Car', 'Limousine', 'Helicopter', 'Water Taxi', 'Train Station Transfer'],
                ];
            }
            
            Module::create([
                'name' => $page['name'],
                'slug' => str_replace('_', '-', $page['code']),
                'code' => $page['code'],
                'module_category_id' => 1,
                'description' => $page['name'] . ' - Interactive guest landing page',
                'category' => 'landingpage',
                'sort_order' => $sortOrder++,
                'is_active' => true,
                'dependencies' => $page['dependencies'] ?? null,
                'setup_fields' => $setupFields,
                'requires_approval' => false,
            ]);
        }
        
        $this->command->info('Created ' . Module::count() . ' modules successfully.');
    }
}