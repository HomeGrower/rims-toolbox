<?php

namespace Database\Seeders;

use App\Models\HotelChain;
use Illuminate\Database\Seeder;

class AddIHGMembershipLevelsSeeder extends Seeder
{
    public function run()
    {
        $ihgChain = HotelChain::where('code', 'IHG')->first();
        
        if (!$ihgChain) {
            $this->command->error('IHG chain not found!');
            return;
        }

        $membershipLevelsTable = [
            'name' => 'membershipLevels',
            'label' => 'Membership Levels',
            'icon' => 'fa-star',
            'description' => 'IHG One Rewards Levels',
            'fields' => [
                [
                    'name' => 'code',
                    'type' => 'select',
                    'label' => 'Membership Level',
                    'description' => null,
                    'required' => false,
                    'translate' => false,
                    'showInList' => true,
                    'options' => [
                        'club' => 'Club',
                        'silver' => 'Silver Elite',
                        'gold' => 'Gold Elite',
                        'platinum' => 'Platinum Elite',
                        'platinum_ambassador' => 'InterContinental Platinum Ambassador',
                        'diamond' => 'Diamond Elite',
                        'diamond_ambassador' => 'InterContinental Diamond Ambassador',
                        'diamond_royal_ambassador' => 'InterContinental Diamond Royal Ambassador',
                        'kimpton_inner_circle' => 'Kimpton Inner Circle'
                    ],
                    'default' => null,
                    'relation_table' => null,
                    'relation_display' => null
                ],
                [
                    'name' => 'description',
                    'type' => 'summernote',
                    'label' => 'Benefits Description',
                    'description' => 'Benefits for this level',
                    'required' => false,
                    'translate' => true,
                    'showInList' => false,
                    'options' => [],
                    'default' => null,
                    'relation_table' => null,
                    'relation_display' => null
                ],
                [
                    'name' => 'addTags',
                    'type' => 'relation',
                    'label' => 'Add Tags',
                    'description' => 'Adds the above tags to the level.',
                    'required' => false,
                    'translate' => false,
                    'showInList' => true,
                    'options' => [],
                    'default' => null,
                    'relation_table' => 'tags',
                    'relation_display' => 'name'
                ]
            ]
        ];

        $existingTables = $ihgChain->custom_datastore_tables ?? [];
        
        // Check if membershipLevels already exists
        $tableExists = false;
        foreach ($existingTables as $key => $table) {
            if ($table['name'] === 'membershipLevels') {
                $existingTables[$key] = $membershipLevelsTable;
                $tableExists = true;
                break;
            }
        }
        
        if (!$tableExists) {
            $existingTables[] = $membershipLevelsTable;
        }
        
        $ihgChain->update([
            'custom_datastore_tables' => $existingTables
        ]);

        $this->command->info('Successfully added membershipLevels table to IHG chain!');
    }
}