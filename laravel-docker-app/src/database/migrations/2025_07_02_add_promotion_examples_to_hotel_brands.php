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
            // Remove design_guidelines
            $table->dropColumn('design_guidelines');
            
            // Add promotion example images
            $table->string('promotion_example')->nullable()->after('heading_font_family');
            $table->string('promotion_tile_example')->nullable()->after('promotion_example');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn(['promotion_example', 'promotion_tile_example']);
            $table->json('design_guidelines')->nullable()->after('heading_font_family');
        });
    }
};