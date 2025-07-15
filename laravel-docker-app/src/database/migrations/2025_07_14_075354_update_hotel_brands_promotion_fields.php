<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing hotel_brands promotions JSON data
        DB::table('hotel_brands')->get()->each(function ($brand) {
            if ($brand->promotions) {
                $promotions = json_decode($brand->promotions, true);
                
                if (is_array($promotions)) {
                    foreach ($promotions as &$promotion) {
                        // Replace show_small_label with show_button
                        if (isset($promotion['show_small_label'])) {
                            $promotion['show_button'] = $promotion['show_small_label'];
                            unset($promotion['show_small_label']);
                        }
                        
                        // Replace show_large_label with show_url
                        if (isset($promotion['show_large_label'])) {
                            $promotion['show_url'] = $promotion['show_large_label'];
                            unset($promotion['show_large_label']);
                        }
                    }
                    
                    DB::table('hotel_brands')
                        ->where('id', $brand->id)
                        ->update(['promotions' => json_encode($promotions)]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse the changes if needed
        DB::table('hotel_brands')->get()->each(function ($brand) {
            if ($brand->promotions) {
                $promotions = json_decode($brand->promotions, true);
                
                if (is_array($promotions)) {
                    foreach ($promotions as &$promotion) {
                        // Revert show_button to show_small_label
                        if (isset($promotion['show_button'])) {
                            $promotion['show_small_label'] = $promotion['show_button'];
                            unset($promotion['show_button']);
                        }
                        
                        // Revert show_url to show_large_label
                        if (isset($promotion['show_url'])) {
                            $promotion['show_large_label'] = $promotion['show_url'];
                            unset($promotion['show_url']);
                        }
                    }
                    
                    DB::table('hotel_brands')
                        ->where('id', $brand->id)
                        ->update(['promotions' => json_encode($promotions)]);
                }
            }
        });
    }
};
