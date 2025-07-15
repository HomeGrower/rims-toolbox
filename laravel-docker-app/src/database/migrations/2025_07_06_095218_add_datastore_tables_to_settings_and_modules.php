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
        // Add datastore tables configuration to modules
        Schema::table('modules', function (Blueprint $table) {
            $table->json('datastore_tables')->nullable()->after('settings');
        });
        
        // Create default setting for standard datastore tables
        \App\Models\Setting::set('standard_datastore_tables', [
            'properties',
            'rooms', 
            'roomCategories',
            'buildings',
            'taxes',
            'cancellationPolicies',
            'colors',
            'tagMapping'
        ], 'json', 'datastore');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn('datastore_tables');
        });
        
        // Remove the setting
        \App\Models\Setting::where('key', 'standard_datastore_tables')->delete();
    }
};
