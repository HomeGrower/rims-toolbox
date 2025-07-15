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
            $table->json('reservation_settings_config')->nullable()->after('brand_configurations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pms_types', function (Blueprint $table) {
            $table->dropColumn('reservation_settings_config');
        });
    }
};
