<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class UpdateModuleRoomConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Modules that require full room details (images + descriptions)
        $modulesWithFullDetails = [
            'reservation_offer' => [
                'requires_room_details' => true,
                'requires_room_short_description' => true,
                'requires_room_long_description' => true,
                'requires_room_main_image' => true,
                'requires_room_slideshow_images' => false,
                'allow_room_details_toggle' => false,
            ],
            'room_upsell_mailing' => [
                'requires_room_details' => true,
                'requires_room_short_description' => true,
                'requires_room_long_description' => true,
                'requires_room_main_image' => true,
                'requires_room_slideshow_images' => false,
                'allow_room_details_toggle' => false,
            ],
        ];
        
        // Modules that require slideshow images (landing pages)
        $modulesWithSlideshow = [
            'offer_reconfirmation' => [
                'requires_room_details' => true,
                'requires_room_short_description' => true,
                'requires_room_long_description' => true,
                'requires_room_main_image' => true,
                'requires_room_slideshow_images' => true,
                'allow_room_details_toggle' => false,
            ],
            'room_upsell' => [
                'requires_room_details' => true,
                'requires_room_short_description' => true,
                'requires_room_long_description' => true,
                'requires_room_main_image' => true,
                'requires_room_slideshow_images' => true,
                'allow_room_details_toggle' => false,
            ],
        ];
        
        // Modules that allow optional room details toggle
        $modulesWithToggle = [
            'confirmation' => [
                'requires_room_details' => false,
                'requires_room_short_description' => false,
                'requires_room_long_description' => false,
                'requires_room_main_image' => false,
                'requires_room_slideshow_images' => false,
                'allow_room_details_toggle' => true,
            ],
            'pre_arrival' => [
                'requires_room_details' => false,
                'requires_room_short_description' => false,
                'requires_room_long_description' => false,
                'requires_room_main_image' => false,
                'requires_room_slideshow_images' => false,
                'allow_room_details_toggle' => true,
            ],
        ];
        
        // Update modules with full details
        foreach ($modulesWithFullDetails as $code => $config) {
            Module::where('code', $code)->update($config);
            $this->command->info("Updated module: {$code} with full room details");
        }
        
        // Update modules with slideshow
        foreach ($modulesWithSlideshow as $code => $config) {
            Module::where('code', $code)->update($config);
            $this->command->info("Updated module: {$code} with slideshow images");
        }
        
        // Update modules with toggle option
        foreach ($modulesWithToggle as $code => $config) {
            Module::where('code', $code)->update($config);
            $this->command->info("Updated module: {$code} with toggle option");
        }
        
        $this->command->info('Module room configuration updated successfully!');
    }
}