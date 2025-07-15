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
        Schema::table('hotel_chains', function (Blueprint $table) {
            $table->json('custom_datastore_tables')->nullable()->after('required_documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hotel_chains', function (Blueprint $table) {
            $table->dropColumn('custom_datastore_tables');
        });
    }
};
