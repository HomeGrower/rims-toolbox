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
            $table->string('primary_color')->nullable()->after('brand_specific_questions');
            $table->string('secondary_color')->nullable()->after('primary_color');
            $table->string('accent_color')->nullable()->after('secondary_color');
            $table->string('font_family')->nullable()->after('accent_color');
            $table->string('heading_font_family')->nullable()->after('font_family');
            $table->json('design_guidelines')->nullable()->after('heading_font_family');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn([
                'primary_color',
                'secondary_color',
                'accent_color',
                'font_family',
                'heading_font_family',
                'design_guidelines'
            ]);
        });
    }
};