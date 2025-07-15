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
            $table->foreignId('delegated_to')->nullable()->after('created_by')->constrained('users')->onDelete('set null');
            $table->timestamp('delegated_at')->nullable()->after('delegated_to');
            $table->index('delegated_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['delegated_to']);
            $table->dropColumn(['delegated_to', 'delegated_at']);
        });
    }
};
