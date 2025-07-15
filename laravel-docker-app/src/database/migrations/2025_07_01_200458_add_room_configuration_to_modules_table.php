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
            $table->boolean('requires_room_details')->default(false)
                ->comment('Whether this module requires room images and descriptions');
            $table->boolean('requires_room_short_description')->default(false)
                ->comment('Whether this module requires short room descriptions');
            $table->boolean('requires_room_long_description')->default(false)
                ->comment('Whether this module requires long room descriptions');
            $table->boolean('requires_room_main_image')->default(false)
                ->comment('Whether this module requires main room images');
            $table->boolean('requires_room_slideshow_images')->default(false)
                ->comment('Whether this module requires additional slideshow images');
            $table->boolean('allow_room_details_toggle')->default(false)
                ->comment('Whether to show toggle for room details in email templates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'requires_room_details',
                'requires_room_short_description',
                'requires_room_long_description',
                'requires_room_main_image',
                'requires_room_slideshow_images',
                'allow_room_details_toggle'
            ]);
        });
    }
};
