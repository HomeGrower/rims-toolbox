<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateConfirmationCancellationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();
        
        try {
            // Find the existing modules
            $confirmationModule = Module::where('code', 'confirmation')->first();
            $cancellationModule = Module::where('code', 'cancellation')->first();
            
            if ($confirmationModule && $cancellationModule) {
                // Get all project associations for both modules
                $confirmationProjects = DB::table('project_modules')
                    ->where('module_id', $confirmationModule->id)
                    ->get();
                    
                $cancellationProjects = DB::table('project_modules')
                    ->where('module_id', $cancellationModule->id)
                    ->get();
                
                // Delete the cancellation module associations
                DB::table('project_modules')
                    ->where('module_id', $cancellationModule->id)
                    ->delete();
                
                // Delete the cancellation module
                $cancellationModule->delete();
                
                // Update the confirmation module to be Confirmation/Cancellation
                $confirmationModule->update([
                    'name' => 'Confirmation/Cancellation',
                    'code' => 'confirmation_cancellation',
                    'slug' => 'confirmation-cancellation',
                    'description' => 'Confirmation/Cancellation - Automated letter template for booking confirmations and cancellations',
                    'setup_fields' => [
                        'confirmation_template_code' => [
                            'type' => 'text',
                            'label' => 'Confirmation Template Code',
                            'description' => 'Unique identifier for confirmation template',
                            'required' => true,
                        ],
                        'cancellation_template_code' => [
                            'type' => 'text',
                            'label' => 'Cancellation Template Code',
                            'description' => 'Unique identifier for cancellation template',
                            'required' => true,
                        ],
                        'default_confirmation_subject' => [
                            'type' => 'text',
                            'label' => 'Default Confirmation Subject',
                            'description' => 'Default subject line for confirmations',
                            'required' => true,
                            'default_value' => 'Your Reservation is Confirmed',
                        ],
                        'default_cancellation_subject' => [
                            'type' => 'text',
                            'label' => 'Default Cancellation Subject',
                            'description' => 'Default subject line for cancellations',
                            'required' => true,
                            'default_value' => 'Your Reservation has been Cancelled',
                        ],
                    ],
                ]);
                
                // Add cancellation projects to the confirmation/cancellation module if they weren't already there
                foreach ($cancellationProjects as $project) {
                    $exists = DB::table('project_modules')
                        ->where('project_id', $project->project_id)
                        ->where('module_id', $confirmationModule->id)
                        ->exists();
                        
                    if (!$exists) {
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
                    }
                }
                
                $this->command->info('Successfully merged Confirmation and Cancellation modules into Confirmation/Cancellation');
            } else {
                $this->command->warn('Confirmation or Cancellation module not found');
            }
            
            // Update module dependencies that reference the old slugs
            $modulesWithDependencies = Module::whereNotNull('dependencies')->get();
            foreach ($modulesWithDependencies as $module) {
                if (is_array($module->dependencies)) {
                    $updatedDependencies = array_map(function($dep) {
                        if ($dep === 'confirmation' || $dep === 'cancellation') {
                            return 'confirmation-cancellation';
                        }
                        return $dep;
                    }, $module->dependencies);
                    
                    // Remove duplicates
                    $updatedDependencies = array_unique($updatedDependencies);
                    
                    if ($updatedDependencies !== $module->dependencies) {
                        $module->update(['dependencies' => array_values($updatedDependencies)]);
                    }
                }
            }
            
            DB::commit();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}