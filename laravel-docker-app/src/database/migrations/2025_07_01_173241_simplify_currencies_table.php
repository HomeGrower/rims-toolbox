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
        Schema::table('currencies', function (Blueprint $table) {
            $table->dropColumn(['symbol', 'decimal_places', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table) {
            $table->string('symbol')->nullable()->after('name');
            $table->integer('decimal_places')->default(2)->after('symbol');
            $table->integer('sort_order')->default(0)->after('decimal_places');
        });
    }
};
