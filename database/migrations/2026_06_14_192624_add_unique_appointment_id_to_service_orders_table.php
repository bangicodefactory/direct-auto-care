<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // One service order per appointment — backstops the check-in flow against
            // a double check-in (NULLs stay exempt, so walk-ins are unaffected).
            $table->unique('appointment_id');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // MariaDB folds the FK's index into this unique one, so the unique index
            // can't be dropped while the foreign key exists. Drop the FK, drop the
            // unique, then re-create the FK exactly as the original migration declared it.
            $table->dropForeign(['appointment_id']);
            $table->dropUnique(['appointment_id']);
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
        });
    }
};
