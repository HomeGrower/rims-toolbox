<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SplitConfirmationCancellationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Find the existing combined module
            $combinedModule = Module::where('code', 'confirmation_cancellation')->first();
            
            if ($combinedModule) {
                // Get all project associations for the combined module
                $projectAssociations = DB::table('project_modules')
                    ->where('module_id', $combinedModule->id)
                    ->get();
                
                // Create Confirmation module
                $confirmationModule = Module::create([
                    'name' => 'Confirmation',
                    'code' => 'confirmation',
                    'slug' => 'confirmation',
                    'category' => $combinedModule->category,
                    'description' => 'Automated booking confirmation letters',
                    'is_active' => true,
                    'sort_order' => $combinedModule->sort_order,
                    'setup_fields' => [
                        'confirmation_template_code' => [
                            'type' => 'text',
                            'label' => 'Confirmation Template Code',
                            'description' => 'Unique identifier for confirmation template',
                            'required' => true,
                        ],
                        'default_confirmation_subject' => [
                            'type' => 'text',
                            'label' => 'Default Confirmation Subject',
                            'description' => 'Default subject line for confirmations',
                            'required' => true,
                            'default_value' => 'Your Reservation is Confirmed',
                        ],
                    ],
                    'dependencies' => ['cancellation'], // Always requires cancellation
                ]);
                
                // Create Cancellation module
                $cancellationModule = Module::create([
                    'name' => 'Cancellation',
                    'code' => 'cancellation',
                    'slug' => 'cancellation',
                    'category' => $combinedModule->category,
                    'description' => 'Automated booking cancellation letters',
                    'is_active' => true,
                    'sort_order' => $combinedModule->sort_order + 1,
                    'setup_fields' => [
                        'cancellation_template_code' => [
                            'type' => 'text',
                            'label' => 'Cancellation Template Code',
                            'description' => 'Unique identifier for cancellation template',
                            'required' => true,
                        ],
                        'default_cancellation_subject' => [
                            'type' => 'text',
                            'label' => 'Default Cancellation Subject',
                            'description' => 'Default subject line for cancellations',
                            'required' => true,
                            'default_value' => 'Your Reservation has been Cancelled',
                        ],
                    ],
                    'dependencies' => ['confirmation'], // Always requires confirmation
                ]);
                
                // Add both modules to all projects that had the combined module
                foreach ($projectAssociations as $project) {
                    // Add confirmation module
                    DB::table('project_modules')->insert([
                        'project_id' => $project->project_id,
                        'module_id' => $confirmationModule->id,
                        'status' => $project->status,
                        'progress' => $project->progress,
                        'configuration' => $project->configuration,
                        'tasks' => $project->tasks,
                        'started_at' => $project->started_at,
                        'completed_at' => $project->completed_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    // Add cancellation module
                    DB::table('project_modules')->insert([
                        'project_id' => $project->project_id,
                        'module_id' => $cancellationModule->id,
                        'status' => $project->status,
                        'progress' => $project->progress,
                        'configuration' => $project->configuration,
                        'tasks' => $project->tasks,
                        'started_at' => $project->started_at,
                        'completed_at' => $project->completed_at,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                
                // Delete the project associations for the combined module
                DB::table('project_modules')
                    ->where('module_id', $combinedModule->id)
                    ->delete();
                
                // Delete the combined module
                $combinedModule->delete();
                
                // Update other modules that had confirmation-cancellation as dependency
                $modulesWithDependencies = Module::whereNotNull('dependencies')->get();
                foreach ($modulesWithDependencies as $module) {
                    if (is_array($module->dependencies)) {
                        $updatedDependencies = [];
                        foreach ($module->dependencies as $dep) {
                            if ($dep === 'confirmation-cancellation' || $dep === 'confirmation_cancellation') {
                                // Replace with both modules
                                $updatedDependencies[] = 'confirmation';
                                $updatedDependencies[] = 'cancellation';
                            } else {
                                $updatedDependencies[] = $dep;
                            }
                        }
                        
                        // Remove duplicates
                        $updatedDependencies = array_unique($updatedDependencies);
                        
                        if ($updatedDependencies !== $module->dependencies) {
                            $module->update(['dependencies' => array_values($updatedDependencies)]);
                        }
                    }
                }
                
                $this->command->info('Successfully split Confirmation/Cancellation into separate Confirmation and Cancellation modules');
            } else {
                $this->command->warn('Confirmation/Cancellation module not found');
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}