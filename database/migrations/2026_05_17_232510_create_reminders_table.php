<?php

use App\Enums\ReminderStatus;
use App\Enums\ReminderType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', array_column(ReminderType::cases(), 'value'));
            $table->date('scheduled_for');
            $table->enum('status', array_column(ReminderStatus::cases(), 'value'))
                  ->default(ReminderStatus::Pending->value);
            $table->unsignedInteger('trigger_km')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['vehicle_id', 'type', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
