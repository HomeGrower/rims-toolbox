<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('type')->default('general'); // general or chain_specific
            $table->foreignId('hotel_chain_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('hotel_chain_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conditions');
    }
};