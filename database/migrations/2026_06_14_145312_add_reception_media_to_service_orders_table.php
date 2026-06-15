<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Reception-time documentation of the vehicle's physical state:
     * intake photos, fuel gauge reading, and the client's acceptance signature.
     */
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->json('reception_photos')->nullable()->after('reception_condition');
            $table->unsignedTinyInteger('fuel_level')->nullable()->after('reception_photos');
            $table->string('signature_path')->nullable()->after('fuel_level');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['reception_photos', 'fuel_level', 'signature_path']);
        });
    }
};
