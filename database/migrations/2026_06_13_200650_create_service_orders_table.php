<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();           // OS-2026-00001
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mileage_in')->nullable();
            $table->string('status');                     // ServiceOrderStatus enum
            $table->text('diagnosis_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->foreignId('quote_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            // Phase timestamps — one per ServiceOrderStatus.
            $table->timestamp('received_at')->nullable();
            $table->timestamp('diagnosed_at')->nullable();
            $table->timestamp('quote_sent_at')->nullable();
            $table->timestamp('in_progress_at')->nullable();
            $table->timestamp('quality_control_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamp('ready_pickup_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
