<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // Mode de règlement (espèces, chèque, virement, carte) — CGI Art. 145.
            $table->string('payment_method', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
