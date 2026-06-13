<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Distinguishes B2C (particulier) from B2B (société) clients.
            $table->string('type', 20)->default('individual')->after('last_name');
            // Identifiant Commun de l'Entreprise — mandatory on B2B invoices (CGI Art. 145).
            $table->string('ice', 20)->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['type', 'ice']);
        });
    }
};
