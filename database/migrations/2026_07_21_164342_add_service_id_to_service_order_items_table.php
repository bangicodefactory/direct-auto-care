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
        Schema::table('service_order_items', function (Blueprint $table) {
            // A line references a product OR a service (or neither, free text) —
            // never both. Enforced in the Form Requests.
            $table->foreignId('service_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
        });
    }
};
