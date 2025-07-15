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
            $table->json('datastore_tables')->nullable()->after('template_examples');
            $table->json('custom_datastore_tables')->nullable()->after('datastore_tables');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_brands', function (Blueprint $table) {
            $table->dropColumn(['datastore_tables', 'custom_datastore_tables']);
        });
    }
};
