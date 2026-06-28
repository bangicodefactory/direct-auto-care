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

            // Brand colors (hex) — Direct Auto Care design system v1.0
            $table->string('color_primary', 7)->default('#F4A81C');      // amber 500
            $table->string('color_accent', 7)->default('#2FA84F');       // green
            $table->string('color_bg', 7)->default('#F6F2EA');           // warm paper
            $table->string('color_text_primary', 7)->default('#211E1A'); // ink
            $table->string('color_text_muted', 7)->default('#6B655E');   // warm grey
            $table->string('color_success', 7)->default('#2FA84F');      // green
            $table->string('color_warning', 7)->default('#F4A81C');      // amber
            $table->string('color_error', 7)->default('#E5484D');        // red

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
