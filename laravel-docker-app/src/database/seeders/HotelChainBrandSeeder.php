<?php

namespace Database\Seeders;

use App\Models\HotelChain;
use App\Models\HotelBrand;
use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class HotelChainBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all module IDs for reference
        $letterTemplates = Module::where('code', 'letter_templates')->first()->id;
        $mailingTemplates = Module::where('code', 'mailing_templates')->first()->id;
        $notificationServices = Module::where('code', 'notification_services')->first()->id;
        $textMessaging = Module::where('code', 'text_messaging')->first()->id;
        $lostAndFound = Module::where('code', 'lost_and_found')->first()->id;
        $roomService = Module::where('code', 'room_service')->first()->id;
        $spa = Module::where('code', 'spa')->first()->id;
        $training = Module::where('code', 'training_material')->first()->id;
        
        // Create Marriott Chain
        $marriott = HotelChain::create([
            'name' => 'Marriott International',
            'code' => 'MAR',
            'default_modules' => [
                $letterTemplates,
                $mailingTemplates,
                $notificationServices,
                $roomService,
                $training
            ],
            'required_documents' => [
                'brand_standards_agreement',
                'property_license',
                'insurance_certificate'
            ],
            'is_active' => true,
        ]);

        // Create Marriott Brands
        HotelBrand::create([
            'hotel_chain_id' => $marriott->id,
            'name' => 'Ritz-Carlton',
            'code' => 'RC',
            'additional_modules' => [$spa, $textMessaging],
            'brand_specific_questions' => [
                ['type' => 'select', 'question' => 'Spa Type', 'options' => ['Full Service', 'Limited Service', 'Partner Operated']],
                ['type' => 'boolean', 'question' => 'Club Level Available?'],
            ],
            'is_active' => true,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $marriott->id,
            'name' => 'JW Marriott',
            'code' => 'JW',
            'additional_modules' => [$spa],
            'brand_specific_questions' => [
                ['type' => 'boolean', 'question' => 'Executive Lounge?'],
            ],
            'is_active' => true,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $marriott->id,
            'name' => 'Courtyard',
            'code' => 'CY',
            'additional_modules' => [],
            'brand_specific_questions' => [
                ['type' => 'boolean', 'question' => 'Bistro Available?'],
            ],
            'is_active' => true,
        ]);

        // Create Hilton Chain
        $hilton = HotelChain::create([
            'name' => 'Hilton Worldwide',
            'code' => 'HLT',
            'default_modules' => [
                $letterTemplates,
                $notificationServices,
                $lostAndFound,
                $training
            ],
            'required_documents' => [
                'franchise_agreement',
                'brand_compliance_certificate'
            ],
            'is_active' => true,
        ]);

        // Create Hilton Brands
        HotelBrand::create([
            'hotel_chain_id' => $hilton->id,
            'name' => 'Waldorf Astoria',
            'code' => 'WA',
            'additional_modules' => [$spa, $textMessaging, $roomService],
            'brand_specific_questions' => [
                ['type' => 'select', 'question' => 'Restaurant Count', 'options' => ['1', '2', '3', '4+']],
                ['type' => 'boolean', 'question' => 'Peacock Alley?'],
            ],
            'is_active' => true,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $hilton->id,
            'name' => 'Conrad',
            'code' => 'CON',
            'additional_modules' => [$spa, $roomService],
            'brand_specific_questions' => [
                ['type' => 'boolean', 'question' => 'Conrad Concierge Available?'],
            ],
            'is_active' => true,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $hilton->id,
            'name' => 'Hampton Inn',
            'code' => 'HMP',
            'additional_modules' => [],
            'brand_specific_questions' => [
                ['type' => 'boolean', 'question' => 'Hot Breakfast Included?'],
            ],
            'is_active' => true,
        ]);

        // Create Independent/Boutique Chain
        $independent = HotelChain::create([
            'name' => 'Independent Hotels',
            'code' => 'IND',
            'default_modules' => [
                $letterTemplates,
                $training
            ],
            'required_documents' => [
                'business_license',
                'insurance_policy'
            ],
            'is_active' => true,
        ]);

        HotelBrand::create([
            'hotel_chain_id' => $independent->id,
            'name' => 'Boutique Collection',
            'code' => 'BTC',
            'additional_modules' => [$spa, $roomService, $textMessaging],
            'brand_specific_questions' => [
                ['type' => 'text', 'question' => 'Unique Selling Point'],
                ['type' => 'select', 'question' => 'Style', 'options' => ['Modern', 'Classic', 'Eclectic', 'Minimalist']],
            ],
            'is_active' => true,
        ]);
    }
}
