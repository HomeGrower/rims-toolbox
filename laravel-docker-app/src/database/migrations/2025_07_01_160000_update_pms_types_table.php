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
        Schema::table('pms_types', function (Blueprint $table) {
            // Remove sort_order column
            $table->dropColumn('sort_order');
            
            // Add configuration columns
            $table->json('setup_requirements')->nullable()->after('is_active');
            $table->json('module_configurations')->nullable()->after('setup_requirements');
            $table->json('brand_configurations')->nullable()->after('module_configurations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pms_types', function (Blueprint $table) {
            $table->integer('sort_order')->default(0)->after('is_active');
            $table->dropColumn(['setup_requirements', 'module_configurations', 'brand_configurations']);
        });
    }
};