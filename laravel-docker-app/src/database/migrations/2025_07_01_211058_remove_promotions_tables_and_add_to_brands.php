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
        // Drop the pivot table first
        Schema::dropIfExists('hotel_brand_promotions');
        
        // Drop the promotions table
        Schema::dropIfExists('promotions');
        
        // Add promotions field to hotel_brands
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->json('promotions')->nullable()->after('brand_specific_questions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove promotions field from hotel_brands
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn('promotions');
        });
        
        // Recreate promotions table
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->text('description')->nullable();
            $table->boolean('show_image')->default(false);
            $table->boolean('show_icon')->default(false);
            $table->boolean('show_text')->default(false);
            $table->boolean('show_url')->default(false);
            $table->boolean('show_button')->default(false);
            $table->boolean('show_title')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
        
        // Recreate pivot table
        Schema::create('hotel_brand_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_brand_id')->constrained()->onDelete('cascade');
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['hotel_brand_id', 'promotion_id']);
        });
    }
};