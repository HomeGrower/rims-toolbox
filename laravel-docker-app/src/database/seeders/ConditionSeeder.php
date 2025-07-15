<?php

namespace Database\Seeders;

use App\Models\Condition;
use Illuminate\Database\Seeder;

class ConditionSeeder extends Seeder
{
    public function run(): void
    {
        // General conditions
        $conditions = [
            [
                'name' => 'Returning Guest',
                'code' => 'returning_guest',
                'description' => 'Guest has stayed at the property before',
                'type' => 'general',
                'sort_order' => 1,
            ],
            [
                'name' => 'First Time Guest',
                'code' => 'first_time_guest',
                'description' => 'Guest is staying for the first time',
                'type' => 'general',
                'sort_order' => 2,
            ],
            [
                'name' => 'Children',
                'code' => 'children',
                'description' => 'Reservation includes children',
                'type' => 'general',
                'sort_order' => 3,
            ],
            [
                'name' => 'Business Travel',
                'code' => 'business_travel',
                'description' => 'Guest is traveling for business',
                'type' => 'general',
                'sort_order' => 4,
            ],
            [
                'name' => 'Leisure Travel',
                'code' => 'leisure_travel',
                'description' => 'Guest is traveling for leisure',
                'type' => 'general',
                'sort_order' => 5,
            ],
            [
                'name' => 'Group Booking',
                'code' => 'group_booking',
                'description' => 'Part of a group reservation',
                'type' => 'general',
                'sort_order' => 6,
            ],
            [
                'name' => 'VIP Guest',
                'code' => 'vip_guest',
                'description' => 'Guest has VIP status',
                'type' => 'general',
                'sort_order' => 7,
            ],
            [
                'name' => 'Long Stay',
                'code' => 'long_stay',
                'description' => 'Stay is longer than 7 nights',
                'type' => 'general',
                'sort_order' => 8,
            ],
            [
                'name' => 'Weekend Stay',
                'code' => 'weekend_stay',
                'description' => 'Stay includes weekend nights',
                'type' => 'general',
                'sort_order' => 9,
            ],
            [
                'name' => 'Special Occasion',
                'code' => 'special_occasion',
                'description' => 'Guest is celebrating a special occasion',
                'type' => 'general',
                'sort_order' => 10,
            ],
        ];

        foreach ($conditions as $condition) {
            Condition::updateOrCreate(
                ['code' => $condition['code']],
                $condition
            );
        }
        
        // Chain-specific conditions examples
        $ihgChain = \App\Models\HotelChain::where('code', 'IHG')->first();
        if ($ihgChain) {
            $ihgConditions = [
                [
                    'name' => 'IHG Rewards Member',
                    'code' => 'ihg_rewards_member',
                    'description' => 'Guest is an IHG Rewards Club member',
                    'type' => 'chain_specific',
                    'hotel_chain_id' => $ihgChain->id,
                    'sort_order' => 20,
                ],
                [
                    'name' => 'Spire Elite Member',
                    'code' => 'ihg_spire_elite',
                    'description' => 'Guest has Spire Elite status',
                    'type' => 'chain_specific',
                    'hotel_chain_id' => $ihgChain->id,
                    'sort_order' => 21,
                ],
            ];
            
            foreach ($ihgConditions as $condition) {
                Condition::updateOrCreate(
                    ['code' => $condition['code']],
                    $condition
                );
            }
        }
    }
}