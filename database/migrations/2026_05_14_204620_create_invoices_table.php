<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['quote', 'invoice']);
            $table->string('number', 30)->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('subtotal_ht', 12, 2);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('tva_rate', 5, 2)->default(20.00);
            $table->decimal('tva_amount', 12, 2);
            $table->decimal('total_ttc', 12, 2);
            $table->enum('status', ['draft', 'issued', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('converted_from_quote_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'fiscal_year', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
