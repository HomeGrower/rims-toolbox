<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('greeting_paragraphs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->integer('paragraph_number'); // 1-10
            $table->integer('priority'); // 1-10 (1 = highest)
            $table->text('content');
            $table->json('modules')->nullable(); // Array of module IDs this paragraph applies to
            $table->json('show_if_conditions')->nullable(); // Array of condition IDs
            $table->json('hide_if_conditions')->nullable(); // Array of condition IDs
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['project_id', 'paragraph_number', 'priority']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('greeting_paragraphs');
    }
};