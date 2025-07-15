<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectData;
use Illuminate\Http\Request;

class ProjectDataController extends Controller
{
    /**
     * Get all project data in a structured format
     */
    public function show(Request $request, string $accessCode)
    {
        $project = Project::where('access_code', $accessCode)->first();
        
        if (!$project) {
            return response()->json([
                'error' => 'Project not found'
            ], 404);
        }

        $groupedData = ProjectData::getGroupedDataForProject($project->id);
        
        // Get language names for mapping
        $allLanguageCodes = [];
        if ($project->primary_language) {
            $allLanguageCodes[] = $project->primary_language;
        }
        if (is_array($project->languages)) {
            $allLanguageCodes = array_merge($allLanguageCodes, $project->languages);
        }
        $allLanguageCodes = array_unique($allLanguageCodes);
        
        $languageNames = [];
        if (!empty($allLanguageCodes)) {
            $languageNames = \App\Models\Language::whereIn('code', $allLanguageCodes)
                ->pluck('name', 'code')
                ->toArray();
        }
        
        // Transform data for API response
        $apiData = [
            'project' => [
                'id' => $project->id,
                'hotel_name' => $project->hotel_name,
                'project_type' => $project->project_type,
                'access_code' => $project->access_code,
                'status' => $project->status,
                'chain' => $project->hotelChain ? $project->hotelChain->name : null,
                'brand' => $project->hotelBrand ? $project->hotelBrand->name : null,
                'pms_type' => $project->pmsType ? $project->pmsType->name : null,
                'primary_language' => $project->primary_language,
                'primary_language_name' => $languageNames[$project->primary_language] ?? $project->primary_language,
                'languages' => $project->languages,
                'language_names' => array_map(function($code) use ($languageNames) {
                    return $languageNames[$code] ?? $code;
                }, $project->languages ?? []),
                'created_at' => $project->created_at->toIso8601String(),
                'updated_at' => $project->updated_at->toIso8601String(),
            ],
            'data' => []
        ];
        
        foreach ($groupedData as $team => $sections) {
            $teamData = [];
            
            foreach ($sections as $section => $fields) {
                $sectionData = [];
                
                foreach ($fields as $field) {
                    $sectionData[$field->field_key] = [
                        'label' => $field->field_label,
                        'value' => $field->field_value,
                        'type' => $field->field_type,
                        'updated_at' => $field->updated_at->toIso8601String(),
                    ];
                }
                
                $teamData[$section] = $sectionData;
            }
            
            $apiData['data'][$team] = $teamData;
        }
        
        return response()->json($apiData);
    }
    
    /**
     * Export project data as CSV
     */
    public function export(Request $request, string $accessCode)
    {
        $project = Project::where('access_code', $accessCode)->first();
        
        if (!$project) {
            return response()->json([
                'error' => 'Project not found'
            ], 404);
        }

        $data = ProjectData::where('project_id', $project->id)
            ->orderBy('team')
            ->orderBy('section')
            ->orderBy('field_key')
            ->get();

        $csv = "Team,Section,Field,Label,Value,Type,Updated At\n";
        
        foreach ($data as $item) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $item->team,
                $item->section,
                $item->field_key,
                $item->field_label,
                str_replace('"', '""', $item->field_value ?? ''),
                $item->field_type,
                $item->updated_at->format('Y-m-d H:i:s')
            );
        }
        
        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="project-' . $project->access_code . '-data.csv"');
    }
}