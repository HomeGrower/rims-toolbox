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
            $table->json('policy_example_images')->nullable()->after('reservation_settings_config');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pms_types', function (Blueprint $table) {
            $table->dropColumn('policy_example_images');
        });
    }
};