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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            // No DB unique on name: soft deletes must allow re-creating a
            // deleted name. Uniqueness among live rows is enforced in the
            // Form Requests.
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->decimal('default_price_ht', 12, 2);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
