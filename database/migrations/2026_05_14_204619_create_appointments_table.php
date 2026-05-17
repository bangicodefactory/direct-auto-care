<?php

use App\Enums\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->dateTime('scheduled_at');
            $table->unsignedSmallInteger('duration'); // minutes
            $table->enum('status', array_column(AppointmentStatus::cases(), 'value'))
                  ->default(AppointmentStatus::Pending->value);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Supports overlap-check query: WHERE vehicle_id = ? AND scheduled_at BETWEEN ? AND ?
            $table->index(['vehicle_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
