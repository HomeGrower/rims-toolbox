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
        Schema::create('datastore_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('base_template')->nullable(); // Stores the master template
            $table->json('configuration'); // Stores the custom configuration
            $table->json('disabled_tables')->nullable(); // List of disabled tables
            $table->json('custom_fields')->nullable(); // Custom fields added
            $table->json('module_overrides')->nullable(); // Module-specific overrides
            $table->json('chain_overrides')->nullable(); // Chain-specific overrides
            $table->boolean('is_active')->default(true);
            $table->integer('version')->default(1);
            $table->timestamps();
            
            $table->unique(['project_id', 'name']);
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datastore_configurations');
    }
};
