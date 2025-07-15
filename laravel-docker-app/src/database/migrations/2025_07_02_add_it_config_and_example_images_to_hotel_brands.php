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
        Schema::table('hotel_brands', function (Blueprint $table) {
            // Add IT-specific configuration
            if (!Schema::hasColumn('hotel_brands', 'it_configuration')) {
                $table->json('it_configuration')->nullable()->after('brand_specific_questions');
            }
            
            // Add flexible example images
            if (!Schema::hasColumn('hotel_brands', 'example_images')) {
                $table->json('example_images')->nullable()->after('it_configuration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn(['it_configuration', 'example_images']);
        });
    }
};