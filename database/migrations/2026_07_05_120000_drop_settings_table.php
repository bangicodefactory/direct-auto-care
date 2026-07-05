<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the settings singleton. Branding + company identity are now provisioning-
 * time config (env → config('company')), not runtime-editable, so nothing reads
 * this table. down() recreates it for reversibility.
 */
return new class () extends Migration {
    public function up(): void
    {
        Schema::dropIfExists('settings');
    }

    public function down(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_ice', 20)->nullable();
            $table->string('company_if', 20)->nullable();
            $table->string('company_rc', 50)->nullable();
            $table->decimal('tva_rate', 5, 2)->default(20.00);

            $table->string('logo_path')->nullable();

            $table->string('color_primary', 7)->default('#F4A81C');
            $table->string('color_accent', 7)->default('#2FA84F');
            $table->string('color_bg', 7)->default('#F6F2EA');
            $table->string('color_text_primary', 7)->default('#211E1A');
            $table->string('color_text_muted', 7)->default('#6B655E');
            $table->string('color_success', 7)->default('#2FA84F');
            $table->string('color_warning', 7)->default('#F4A81C');
            $table->string('color_error', 7)->default('#E5484D');

            $table->tinyInteger('singleton')->unsigned()->default(1);
            $table->unique('singleton');

            $table->timestamps();
        });
    }
};
