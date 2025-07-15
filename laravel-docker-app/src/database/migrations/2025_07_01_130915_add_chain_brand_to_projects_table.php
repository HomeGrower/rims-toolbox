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
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('hotel_chain_id')->nullable()->after('created_by')->constrained()->onDelete('set null');
            $table->foreignId('hotel_brand_id')->nullable()->after('hotel_chain_id')->constrained()->onDelete('set null');
            
            $table->index(['hotel_chain_id', 'hotel_brand_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['hotel_chain_id']);
            $table->dropForeign(['hotel_brand_id']);
            $table->dropColumn(['hotel_chain_id', 'hotel_brand_id']);
        });
    }
};
