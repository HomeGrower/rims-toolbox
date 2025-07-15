<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Project;
use App\Models\Language;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all languages for mapping
        $languages = Language::pluck('code', 'id')->toArray();
        
        // Fix all projects that have numeric language values
        $projects = Project::all();
        
        foreach ($projects as $project) {
            $updated = false;
            
            // Fix primary language if it's numeric
            if ($project->primary_language && is_numeric($project->primary_language)) {
                $languageId = (int) $project->primary_language;
                if (isset($languages[$languageId])) {
                    $project->primary_language = $languages[$languageId];
                    $updated = true;
                }
            }
            
            // Fix additional languages array
            if (is_array($project->languages)) {
                $fixedLanguages = [];
                foreach ($project->languages as $lang) {
                    if (is_numeric($lang)) {
                        $languageId = (int) $lang;
                        if (isset($languages[$languageId])) {
                            $fixedLanguages[] = $languages[$languageId];
                        }
                    } else {
                        // Keep existing language codes as-is
                        $fixedLanguages[] = $lang;
                    }
                }
                
                if (count($fixedLanguages) !== count($project->languages) || 
                    array_diff($fixedLanguages, $project->languages) || 
                    array_diff($project->languages, $fixedLanguages)) {
                    $project->languages = $fixedLanguages;
                    $updated = true;
                }
            }
            
            if ($updated) {
                $project->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be reversed as we're converting IDs to codes
        // Reversing would require guessing which IDs were originally used
    }
};
