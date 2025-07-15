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
            $table->foreignId('pms_type_id')->nullable()->after('hotel_brand_id')->constrained()->onDelete('set null');
            $table->json('notification_emails')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['pms_type_id']);
            $table->dropColumn(['pms_type_id', 'notification_emails']);
        });
    }
};
