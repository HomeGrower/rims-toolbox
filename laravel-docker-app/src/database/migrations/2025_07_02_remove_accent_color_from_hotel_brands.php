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
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn('accent_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->string('accent_color')->nullable()->after('secondary_color');
        });
    }
};