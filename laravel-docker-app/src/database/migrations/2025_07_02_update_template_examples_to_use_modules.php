<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update hotel_brands table to use module_id instead of template_type
        $brands = DB::table('hotel_brands')->whereNotNull('template_examples')->get();
        
        foreach ($brands as $brand) {
            $templateExamples = json_decode($brand->template_examples, true);
            if (!is_array($templateExamples)) {
                continue;
            }
            
            $updatedExamples = [];
            foreach ($templateExamples as $example) {
                // Skip if already has module_id
                if (isset($example['module_id'])) {
                    $updatedExamples[] = $example;
                    continue;
                }
                
                // Map template_type to module slug
                $moduleSlug = match($example['template_type'] ?? '') {
                    'confirmation' => 'confirmation',
                    'cancellation' => 'cancellation',
                    'modification' => 'reservation_amendment',
                    'pre_arrival' => 'arrival_letter',
                    'post_stay' => 'departure_letter',
                    'invoice' => 'e_invoice',
                    'welcome_letter' => 'arrival_letter',
                    'guest_folio' => 'e_invoice',
                    default => null
                };
                
                if ($moduleSlug) {
                    $module = DB::table('modules')->where('slug', $moduleSlug)->first();
                    if ($module) {
                        $example['module_id'] = $module->id;
                        unset($example['template_type']);
                        $updatedExamples[] = $example;
                    }
                }
            }
            
            DB::table('hotel_brands')
                ->where('id', $brand->id)
                ->update(['template_examples' => json_encode($updatedExamples)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to template_type
        $brands = DB::table('hotel_brands')->whereNotNull('template_examples')->get();
        
        foreach ($brands as $brand) {
            $templateExamples = json_decode($brand->template_examples, true);
            if (!is_array($templateExamples)) {
                continue;
            }
            
            $updatedExamples = [];
            foreach ($templateExamples as $example) {
                if (isset($example['module_id'])) {
                    $module = DB::table('modules')->find($example['module_id']);
                    if ($module) {
                        // Map module slug back to template_type
                        $templateType = match($module->slug) {
                            'confirmation' => 'confirmation',
                            'cancellation' => 'cancellation',
                            'reservation_amendment' => 'modification',
                            'arrival_letter' => 'pre_arrival',
                            'departure_letter' => 'post_stay',
                            'e_invoice' => 'invoice',
                            default => null
                        };
                        
                        if ($templateType) {
                            $example['template_type'] = $templateType;
                            unset($example['module_id']);
                            $updatedExamples[] = $example;
                        }
                    }
                }
            }
            
            DB::table('hotel_brands')
                ->where('id', $brand->id)
                ->update(['template_examples' => json_encode($updatedExamples)]);
        }
    }
};