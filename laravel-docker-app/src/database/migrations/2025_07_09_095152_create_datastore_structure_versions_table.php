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
        Schema::create('datastore_structure_versions', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('filename');
            $table->longText('structure');
            $table->integer('file_size');
            $table->string('uploaded_by');
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            
            $table->index('version');
            $table->index('is_active');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datastore_structure_versions');
    }
};
