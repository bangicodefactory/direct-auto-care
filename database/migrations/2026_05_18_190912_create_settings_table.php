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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            // Company info
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_ice', 20)->nullable();  // Identifiant Commun de l'Entreprise
            $table->string('company_if', 20)->nullable();   // Identifiant Fiscal
            $table->string('company_rc', 50)->nullable();   // Registre de Commerce
            $table->decimal('tva_rate', 5, 2)->default(20.00);

            // Logo
            $table->string('logo_path')->nullable();

            // Brand colors (hex)
            $table->string('color_primary', 7)->default('#0B3D91');
            $table->string('color_accent', 7)->default('#E63946');
            $table->string('color_bg', 7)->default('#F8FAFC');
            $table->string('color_text_primary', 7)->default('#0F172A');
            $table->string('color_text_muted', 7)->default('#64748B');
            $table->string('color_success', 7)->default('#10B981');
            $table->string('color_warning', 7)->default('#F59E0B');
            $table->string('color_error', 7)->default('#EF4444');

            // Enforce single-row at the DB level
            $table->tinyInteger('singleton')->unsigned()->default(1);
            $table->unique('singleton');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
