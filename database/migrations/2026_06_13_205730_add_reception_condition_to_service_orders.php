<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Vehicle condition captured at reception via the interactive body diagram:
     * a { "PART_ID": "#hexcolor" } map of marked body parts.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->json('reception_condition')->nullable()->after('mileage_in');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn('reception_condition');
        });
    }
};
