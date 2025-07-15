<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\ProjectSetupTeam;

class ProcessProjectSetup extends BaseJob
{
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $projectId,
        public string $team,
        public string $section
    ) {
        // Disable model serialization to save memory
        $this->projectId = $projectId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Load only what we need
        $project = Project::select(['id', 'name', 'hotel_brand_id', 'pms_type_id'])
            ->find($this->projectId);

        if (!$project) {
            return;
        }

        // Process setup team data
        $setupTeam = ProjectSetupTeam::where('project_id', $this->projectId)
            ->where('team', $this->team)
            ->where('section', $this->section)
            ->first();

        if ($setupTeam) {
            // Recalculate progress
            $setupTeam->progress = $setupTeam->calculateProgress();
            $setupTeam->save();
        }

        // Clear model instances from memory
        unset($project);
        unset($setupTeam);
        
        // Run cleanup
        $this->cleanup();
    }
}