<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectSetupTeam;
use Illuminate\Database\Seeder;

class CreateRoomDetailsSectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all projects
        $projects = Project::all();
        
        foreach ($projects as $project) {
            // Check if room_details section already exists
            $existingSection = ProjectSetupTeam::where('project_id', $project->id)
                ->where('team', 'marketing')
                ->where('section', 'room_details')
                ->first();
            
            if (!$existingSection) {
                // Create room_details section
                ProjectSetupTeam::create([
                    'project_id' => $project->id,
                    'team' => 'marketing',
                    'section' => 'room_details',
                    'fields' => ProjectSetupTeam::generateFieldsForSection('marketing', 'room_details', $project),
                    'completed_fields' => [],
                    'progress' => 0,
                    'status' => 'pending',
                ]);
                
                $this->command->info("Created room_details section for project: {$project->hotel_name}");
            }
        }
        
        $this->command->info('Room details sections created successfully!');
    }
}