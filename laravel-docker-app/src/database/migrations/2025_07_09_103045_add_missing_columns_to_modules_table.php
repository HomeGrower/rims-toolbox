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
        Schema::table('modules', function (Blueprint $table) {
            // Add missing columns that don't exist yet
            if (!Schema::hasColumn('modules', 'module_category_id')) {
                $table->unsignedBigInteger('module_category_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('modules', 'available_for_chains')) {
                $table->json('available_for_chains')->nullable()->after('datastore_tables');
            }
            if (!Schema::hasColumn('modules', 'available_for_brands')) {
                $table->json('available_for_brands')->nullable()->after('available_for_chains');
            }
            if (!Schema::hasColumn('modules', 'required_questions')) {
                $table->json('required_questions')->nullable()->after('available_for_brands');
            }
            if (!Schema::hasColumn('modules', 'conditional_questions')) {
                $table->json('conditional_questions')->nullable()->after('required_questions');
            }
            if (!Schema::hasColumn('modules', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('conditional_questions');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $columnsToRemove = [
                'module_category_id',
                'available_for_chains',
                'available_for_brands',
                'required_questions',
                'conditional_questions',
                'required_documents'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('modules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
