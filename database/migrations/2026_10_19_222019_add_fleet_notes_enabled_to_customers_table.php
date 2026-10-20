<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Fleet clients (e.g. a rental or transport company) get a per-vehicle
            // notes logbook under "Notes flotte". Off for everyone by default.
            $table->boolean('fleet_notes_enabled')->default(false)->after('notify_whatsapp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('fleet_notes_enabled');
        });
    }
};
