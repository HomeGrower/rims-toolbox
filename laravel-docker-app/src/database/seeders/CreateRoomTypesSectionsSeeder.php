<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSetupTeam;
use Illuminate\Database\Seeder;

class CreateRoomTypesSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all projects
        $projects = Project::all();
        
        foreach ($projects as $project) {
            // Check if room_types section already exists
            $existingSection = ProjectSetupTeam::where('project_id', $project->id)
                ->where('team', 'reservation')
                ->where('section', 'room_types')
                ->first();
            
            if (!$existingSection) {
                // Create room_types section
                ProjectSetupTeam::create([
                    'project_id' => $project->id,
                    'team' => 'reservation',
                    'section' => 'room_types',
                    'fields' => ProjectSetupTeam::generateFieldsForSection('reservation', 'room_types', $project),
                    'completed_fields' => [],
                    'progress' => 0,
                    'status' => 'pending',
                ]);
                
                $this->command->info("Created room_types section for project: {$project->hotel_name}");
            }
        }
        
        $this->command->info('Room types sections created successfully!');
    }
}