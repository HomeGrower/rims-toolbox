<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update existing reservation_settings_config to include is_active field
        \App\Models\PmsType::all()->each(function ($pmsType) {
            if (!empty($pmsType->reservation_settings_config)) {
                $config = $pmsType->reservation_settings_config;
                // Add is_active with default true value if not already present
                if (!isset($config['is_active'])) {
                    $config['is_active'] = true;
                    $pmsType->reservation_settings_config = $config;
                    $pmsType->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove is_active field from reservation_settings_config
        \App\Models\PmsType::all()->each(function ($pmsType) {
            if (!empty($pmsType->reservation_settings_config)) {
                $config = $pmsType->reservation_settings_config;
                unset($config['is_active']);
                $pmsType->reservation_settings_config = $config;
                $pmsType->save();
            }
        });
    }
};
