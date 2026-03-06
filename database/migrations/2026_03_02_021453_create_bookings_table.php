<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');

            $table->enum('recurrence_type', [
                'none',
                'daily',
                'weekly',
                'monthly'
            ])->default('none');

            $table->json('recurrence_days')->nullable();

            $table->string('recurrence_period')->nullable();

            $table->date('recurrence_until')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancel_requested',
                'cancelled',
                'completed'
            ])->default('pending');

            $table->timestamps();

            $table->index([
                'room_id',
                'start_datetime',
                'end_datetime'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
