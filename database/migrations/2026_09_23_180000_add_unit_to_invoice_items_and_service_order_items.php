<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the product's stock unit onto each line at creation time, like
 * `description` already is, so an invoice keeps rendering "2 L" after the
 * product is edited or soft-deleted. Existing product lines are backfilled
 * from the current product row (soft-deleted products included: the FK is
 * nullOnDelete, so a row that still has a product_id still joins).
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('unit', 20)->nullable()->after('description');
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->string('unit', 20)->nullable()->after('description');
        });

        DB::statement(
            'UPDATE invoice_items ii JOIN products p ON p.id = ii.product_id SET ii.unit = p.unit WHERE ii.product_id IS NOT NULL'
        );
        DB::statement(
            'UPDATE service_order_items soi JOIN products p ON p.id = soi.product_id SET soi.unit = p.unit WHERE soi.product_id IS NOT NULL'
        );
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('service_order_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
