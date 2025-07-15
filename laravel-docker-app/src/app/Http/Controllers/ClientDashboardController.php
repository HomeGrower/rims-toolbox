<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectSetupTeam;
use App\Models\Language;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $project = $request->attributes->get('project');
        
        // Load project with all necessary relationships
        $project->load([
            'hotelChain',
            'hotelBrand', 
            'pmsType',
            'modules' => function ($query) {
                $query->orderBy('sort_order');
            }
        ]);

        // Get team progress data
        $teamProgress = $this->getTeamProgress($project);
        
        // Get all languages for mapping codes to names
        $languages = Language::pluck('name', 'code')->toArray();
        
        // Check if room details are required
        $roomDetailsRequired = $this->checkIfRoomDetailsRequired($project);
        
        // Get template examples for the project's modules
        $templateExamples = $this->getTemplateExamplesForModules($project);

        return Inertia::render('Dashboard', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'hotel_name' => $project->hotel_name,
                'access_code' => $project->access_code,
                'status' => $project->status,
                'overall_progress' => $project->overall_progress,
                'primary_language' => $project->primary_language,
                'languages' => $project->languages,
                'activated_at' => $project->activated_at,
                'hotelChain' => $project->hotelChain ? [
                    'id' => $project->hotelChain->id,
                    'name' => $project->hotelChain->name,
                ] : null,
                'hotelBrand' => $project->hotelBrand ? [
                    'id' => $project->hotelBrand->id,
                    'name' => $project->hotelBrand->name,
                ] : null,
                'pmsType' => $project->pmsType ? [
                    'id' => $project->pmsType->id,
                    'name' => $project->pmsType->name,
                    'code' => $project->pmsType->code,
                ] : null,
                'pms_type' => $project->pmsType,
                'modules' => $project->modules->map(function ($module) {
                    return [
                        'id' => $module->id,
                        'name' => $module->name,
                        'category' => $module->category,
                        'pivot' => [
                            'status' => $module->pivot->status,
                            'progress' => $module->pivot->progress,
                        ]
                    ];
                }),
            ],
            'teamProgress' => $teamProgress,
            'languages' => $languages,
            'roomDetailsRequired' => $roomDetailsRequired,
            'templateExamples' => $templateExamples,
        ]);
    }

    private function getTeamProgress(Project $project): array
    {
        // Get all team setup records for this project
        $teamSetups = ProjectSetupTeam::where('project_id', $project->id)->get();
        
        $progress = [
            'reservation' => [],
            'marketing' => [],
            'it' => [],
        ];

        // Define sections for each team
        $marketingSections = ['banner_pictures', 'logos', 'colors_fonts'];
        
        // Only include room_details if required
        if ($this->checkIfRoomDetailsRequired($project)) {
            $marketingSections[] = 'room_details';
        }
        
        $marketingSections = array_merge($marketingSections, ['greetings_texts', 'promotions']);
        
        // Build reservation sections based on PMS configuration
        $reservationSections = ['hotel_settings', 'user_settings', 'reservation_settings', 'room_types'];
        
        // Add policy sections only if PMS allows them AND they are active
        if ($project->pmsType && $project->pmsType->isReservationSettingsActive()) {
            // Check which policies are enabled
            $reservationSettings = ProjectSetupTeam::where('project_id', $project->id)
                ->where('team', 'reservation')
                ->where('section', 'reservation_settings')
                ->first();
                
            if ($reservationSettings && $reservationSettings->completed_fields) {
                $completedFields = $reservationSettings->completed_fields;
                
                if ($project->pmsType->shouldShowReservationPolicyField('cancellation_policies') && 
                    ($completedFields['show_cancellation_policies'] ?? false)) {
                    $reservationSections[] = 'cancellation_policies';
                }
                
                if ($project->pmsType->shouldShowReservationPolicyField('special_requests') && 
                    ($completedFields['show_special_requests'] ?? false)) {
                    $reservationSections[] = 'special_requests';
                }
                
                if ($project->pmsType->shouldShowReservationPolicyField('deposit_policies') && 
                    ($completedFields['show_deposit_policies'] ?? false)) {
                    $reservationSections[] = 'deposit_policies';
                }
                
                if ($project->pmsType->shouldShowReservationPolicyField('payment_methods') && 
                    ($completedFields['show_payment_methods'] ?? false)) {
                    $reservationSections[] = 'payment_methods';
                }
                
                if ($project->pmsType->shouldShowReservationPolicyField('transfer_types') && 
                    ($completedFields['show_transfer_types'] ?? false)) {
                    $reservationSections[] = 'transfer_types';
                }
            }
        }
        
        $teamSections = [
            'reservation' => $reservationSections,
            'marketing' => $marketingSections,
            'it' => ['it_settings'],
        ];

        // Initialize all sections as pending
        foreach ($teamSections as $team => $sections) {
            foreach ($sections as $section) {
                $progress[$team][$section] = 'pending';
            }
        }

        // Update with actual progress from database
        foreach ($teamSetups as $setup) {
            if (isset($progress[$setup->team][$setup->section])) {
                // Recalculate progress for greetings section
                if ($setup->section === 'greetings_texts') {
                    $setup->updateProgress();
                    $setup->save();
                }
                
                $progress[$setup->team][$setup->section] = [
                    'status' => $setup->status,
                    'progress' => $setup->progress,
                    'fields' => $setup->fields,
                    'completed_fields' => $setup->completed_fields,
                ];
            }
        }

        // Also include pending sections with 0 progress
        foreach ($teamSections as $team => $sections) {
            foreach ($sections as $section) {
                if ($progress[$team][$section] === 'pending') {
                    $progress[$team][$section] = [
                        'status' => 'pending',
                        'progress' => 0,
                    ];
                }
            }
        }

        return $progress;
    }
    
    private function checkIfRoomDetailsRequired(Project $project): bool
    {
        // Check if reservation settings has show_room_details_in_templates enabled
        $reservationSettings = ProjectSetupTeam::where('project_id', $project->id)
            ->where('team', 'reservation')
            ->where('section', 'reservation_settings')
            ->first();
            
        if ($reservationSettings && 
            isset($reservationSettings->completed_fields['show_room_details_in_templates']) && 
            $reservationSettings->completed_fields['show_room_details_in_templates'] === true) {
            return true;
        }
        
        // Check if any module requires room details
        foreach ($project->modules as $module) {
            if ($module->requires_room_details || 
                $module->requires_room_short_description || 
                $module->requires_room_long_description || 
                $module->requires_room_main_image || 
                $module->requires_room_slideshow_images) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getTemplateExamplesForModules(Project $project): array
    {
        $templateExamples = [];
        
        if (!$project->hotelBrand || !$project->hotelBrand->template_examples) {
            return $templateExamples;
        }
        
        // Get module IDs for this project
        $projectModuleIds = $project->modules->pluck('id')->toArray();
        
        // Map template examples by module ID
        foreach ($project->hotelBrand->template_examples as $example) {
            if (isset($example['module_id']) && in_array($example['module_id'], $projectModuleIds)) {
                $templateExamples[$example['module_id']] = $example['template_image'];
            }
        }
        
        return $templateExamples;
    }
}