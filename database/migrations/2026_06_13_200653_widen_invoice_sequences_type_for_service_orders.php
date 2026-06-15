<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * The invoice_sequences table is reused for service-order numbering
     * (type = 'service_order'). The original type column is varchar(10),
     * too narrow for 'service_order' (13 chars), so widen it.
     */
    public function up(): void
    {
        Schema::table('invoice_sequences', function ($table) {
            $table->string('type', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_sequences', function ($table) {
            $table->string('type', 10)->change();
        });
    }
};
