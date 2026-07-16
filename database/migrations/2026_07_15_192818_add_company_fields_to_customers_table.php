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
            $table->string('type', 20)->default('individual')->after('id');
            $table->string('company_name', 191)->nullable()->after('type');

            // Moroccan fiscal identifiers — only populated for company clients, and
            // required on a B2B invoice (ICE especially). RC is intentionally omitted.
            $table->string('ice', 50)->nullable()->after('company_name');
            $table->string('if', 50)->nullable()->after('ice');
            $table->string('patente', 50)->nullable()->after('if');
            $table->string('ville', 100)->nullable()->after('address');

            // Company clients bill under a raison sociale; the person name becomes an
            // optional contact, so first/last name are no longer mandatory at DB level.
            // (Attributes other than nullable are repeated so change() keeps them.)
            $table->string('first_name', 100)->nullable()->change();
            $table->string('last_name', 100)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['type', 'company_name', 'ice', 'if', 'patente', 'ville']);

            // Restore the NOT NULL constraint on the person-name columns.
            $table->string('first_name', 100)->nullable(false)->change();
            $table->string('last_name', 100)->nullable(false)->change();
        });
    }
};
