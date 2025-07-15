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
        // Check if setup_fields column exists and add it if not
        if (!Schema::hasColumn('modules', 'setup_fields')) {
            Schema::table('modules', function (Blueprint $table) {
                $table->json('setup_fields')->nullable()->after('settings');
            });
        }
        
        // Skip category modification - it's already handled by other migrations
        // This prevents conflicts with existing data
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            if (Schema::hasColumn('modules', 'setup_fields')) {
                $table->dropColumn('setup_fields');
            }
        });
    }
};