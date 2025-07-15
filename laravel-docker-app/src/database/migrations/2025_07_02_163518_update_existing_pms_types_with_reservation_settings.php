<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\PmsType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update Opera On-Premise and OHIP to have reservation settings enabled
        PmsType::whereIn('code', ['OPERA_ONPREM', 'OHIP'])
            ->update([
                'reservation_settings_config' => [
                    'cancellation_policies' => true,
                    'special_requests' => true,
                    'deposit_policies' => true,
                    'payment_methods' => true,
                    'transfer_types' => true,
                ]
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PmsType::whereIn('code', ['OPERA_ONPREM', 'OHIP'])
            ->update([
                'reservation_settings_config' => null
            ]);
    }
};