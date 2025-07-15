<?php

namespace Database\Seeders;

use App\Models\HotelChain;
use App\Models\HotelBrand;
use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IHGSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get module IDs
        $letterTemplates = Module::where('code', 'letter_templates')->first()->id;
        $mailingTemplates = Module::where('code', 'mailing_templates')->first()->id;
        $notificationServices = Module::where('code', 'notification_services')->first()->id;
        $textMessaging = Module::where('code', 'text_messaging')->first()->id;
        $roomService = Module::where('code', 'room_service')->first()->id;
        $spa = Module::where('code', 'spa')->first()->id;
        $concierge = Module::where('code', 'concierge')->first()->id;
        $training = Module::where('code', 'training_material')->first()->id;
        
        // Create IHG Chain
        $ihg = HotelChain::create([
            'name' => 'IHG Hotels & Resorts',
            'code' => 'IHG',
            'default_modules' => [
                $letterTemplates,
                $mailingTemplates,
                $notificationServices,
                $training
            ],
            'required_documents' => [
                'brand_standards_certification',
                'property_management_agreement',
                'insurance_documentation',
                'health_safety_permits'
            ],
            'is_active' => true,
        ]);

        // Create IHG Brands with specific configurations
        HotelBrand::create([
            'hotel_chain_id' => $ihg->id,
            'name' => 'InterContinental Hotels',
            'code' => 'IC',
            'additional_modules' => [$spa, $roomService, $concierge, $textMessaging],
            'brand_specific_questions' => [
                [
                    'type' => 'select', 
                    'question' => 'Property Type', 
                    'options' => ['City Center', 'Resort', 'Airport', 'Convention'],
                    'required' => true
                ],
                [
                    'type' => 'boolean', 
                    'question' => 'Club InterContinental Lounge?',
                    'required' => true
                ],
                [
                    'type' => 'multiselect',
                    'question' => 'Signature Experiences Offered',
                    'options' => ['Local Immersion', 'Culinary Journey', 'Wellness Program', 'Cultural Tours'],
                    'required' => false
                ],
                [
                    'type' => 'text',
                    'question' => 'Number of Meeting Rooms',
                    'required' => true
                ],
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $ihg->id,
            'name' => 'Crowne Plaza Hotels',
            'code' => 'CP',
            'additional_modules' => [$roomService, $textMessaging],
            'brand_specific_questions' => [
                [
                    'type' => 'boolean', 
                    'question' => 'Sleep Advantage Program Implemented?',
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'question' => 'Plaza Workspace Configuration',
                    'options' => ['Standard', 'Enhanced', 'Premium'],
                    'required' => true
                ],
                [
                    'type' => 'boolean',
                    'question' => 'Fast & Fresh Meeting Options Available?',
                    'required' => false
                ],
            ],
            'is_active' => true,
            'sort_order' => 2,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $ihg->id,
            'name' => 'voco Hotels',
            'code' => 'VOCO',
            'additional_modules' => [$textMessaging],
            'brand_specific_questions' => [
                [
                    'type' => 'select',
                    'question' => 'voco Style',
                    'options' => ['Classic Heritage', 'Contemporary Chic', 'Natural Retreat'],
                    'required' => true
                ],
                [
                    'type' => 'boolean',
                    'question' => 'Me Time Amenities Package?',
                    'required' => true
                ],
                [
                    'type' => 'text',
                    'question' => 'Signature Local Experience',
                    'required' => false
                ],
            ],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $ihg->id,
            'name' => 'Holiday Inn Express',
            'code' => 'HIX',
            'additional_modules' => [],
            'brand_specific_questions' => [
                [
                    'type' => 'boolean',
                    'question' => 'Express Start Breakfast Bar?',
                    'required' => true
                ],
                [
                    'type' => 'select',
                    'question' => 'Simply Smart Bedding Type',
                    'options' => ['Standard', 'Premium'],
                    'required' => true
                ],
            ],
            'is_active' => true,
            'sort_order' => 4,
        ]);
    }
}
