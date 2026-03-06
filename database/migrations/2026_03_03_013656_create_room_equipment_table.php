<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
// room_equipment (pivot)
return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained()->cascadeOnDelete();
            $table->unique(['room_id', 'equipment_id']);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('room_equipment');
    }
};
