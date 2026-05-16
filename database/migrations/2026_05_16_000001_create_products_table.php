<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20);
            $table->string('name', 150);
            $table->string('sku', 50)->nullable()->unique();
            $table->string('unit', 20);
            $table->decimal('price_buy', 10, 2)->nullable();
            $table->decimal('price_sell', 10, 2);
            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
