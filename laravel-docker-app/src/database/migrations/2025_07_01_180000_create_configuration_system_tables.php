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
        // Chain-level configurations
        Schema::create('chain_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_chain_id')->constrained()->onDelete('cascade');
            $table->string('configuration_type'); // 'email', 'it_setup', 'reservation', etc.
            $table->string('team'); // 'it', 'reservation', 'marketing'
            $table->json('settings')->nullable(); // Technical settings
            $table->json('instructions')->nullable(); // Instructions for teams
            $table->json('additional_fields')->nullable(); // Extra fields to add
            $table->json('field_overrides')->nullable(); // Override specific fields
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['hotel_chain_id', 'configuration_type']);
            $table->index(['hotel_chain_id', 'team']);
        });

        // Brand-level configurations (override chain)
        Schema::create('brand_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_brand_id')->constrained()->onDelete('cascade');
            $table->string('configuration_type');
            $table->string('team');
            $table->json('settings')->nullable();
            $table->json('instructions')->nullable();
            $table->json('additional_fields')->nullable();
            $table->json('field_overrides')->nullable();
            $table->boolean('overrides_chain')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['hotel_brand_id', 'configuration_type']);
            $table->index(['hotel_brand_id', 'team']);
        });

        // Module + Brand specific configurations
        Schema::create('module_brand_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotel_brand_id')->constrained()->onDelete('cascade');
            $table->json('additional_fields')->nullable(); // Extra fields for this brand+module
            $table->json('field_overrides')->nullable(); // Override module base fields
            $table->json('conditional_fields')->nullable(); // Fields that appear based on conditions
            $table->json('layout_settings')->nullable(); // Brand-specific layout
            $table->json('dependencies')->nullable(); // Additional dependencies for this combo
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['module_id', 'hotel_brand_id']);
        });

        // Module dependencies (which modules require which)
        Schema::create('module_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->onDelete('cascade');
            $table->foreignId('depends_on_module_id')->references('id')->on('modules')->onDelete('cascade');
            $table->string('dependency_type')->default('required'); // 'required', 'recommended', 'conditional'
            $table->json('conditions')->nullable(); // Conditions when dependency applies
            $table->timestamps();
            
            $table->unique(['module_id', 'depends_on_module_id']);
        });

        // Add languages to projects
        Schema::table('projects', function (Blueprint $table) {
            $table->json('languages')->nullable()->after('notification_emails');
            $table->string('primary_language')->default('en')->after('languages');
        });

        // Configuration templates (predefined setups)
        Schema::create('configuration_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type'); // 'email_setup', 'module_setup', etc.
            $table->json('template_data');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['languages', 'primary_language']);
        });
        
        Schema::dropIfExists('configuration_templates');
        Schema::dropIfExists('module_dependencies');
        Schema::dropIfExists('module_brand_configurations');
        Schema::dropIfExists('brand_configurations');
        Schema::dropIfExists('chain_configurations');
    }
};