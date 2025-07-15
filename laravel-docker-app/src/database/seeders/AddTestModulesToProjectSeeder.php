<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Module;
use Illuminate\Database\Seeder;

class AddTestModulesToProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first project
        $project = Project::first();
        
        if (!$project) {
            $this->command->error('No project found!');
            return;
        }
        
        // Get modules to add
        $modulesToAdd = [
            'confirmation',        // Has allow_room_details_toggle
            'pre_arrival',        // Has allow_room_details_toggle
            'reservation_offer',  // Requires room details
            'room_upsell',       // Requires room details with slideshow
        ];
        
        $moduleIds = Module::whereIn('code', $modulesToAdd)->pluck('id')->toArray();
        
        // Sync modules to project
        $project->modules()->sync($moduleIds);
        
        $this->command->info("Added " . count($moduleIds) . " modules to project: {$project->hotel_name}");
        
        // Show which modules were added
        foreach ($modulesToAdd as $code) {
            $module = Module::where('code', $code)->first();
            if ($module) {
                $this->command->info("- {$module->name} (toggle: " . ($module->allow_room_details_toggle ? 'yes' : 'no') . ")");
            }
        }
    }
}