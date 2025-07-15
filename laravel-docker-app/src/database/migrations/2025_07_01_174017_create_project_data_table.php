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
        Schema::create('project_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('team');
            $table->string('section');
            $table->string('field_key');
            $table->string('field_label');
            $table->text('field_value')->nullable();
            $table->string('field_type')->nullable();
            $table->timestamps();
            
            $table->index(['project_id', 'team', 'section']);
            $table->unique(['project_id', 'team', 'section', 'field_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_data');
    }
};
