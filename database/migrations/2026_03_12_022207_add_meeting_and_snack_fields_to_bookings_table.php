<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('meeting_title')->nullable()->after('recurrence_until');
            $table->string('meeting_chairman')->nullable()->after('meeting_title');
            $table->boolean('snack_required')->default(false)->after('meeting_chairman');
            $table->text('snack_note')->nullable()->after('snack_required');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'meeting_title',
                'meeting_chairman',
                'snack_required',
                'snack_note',
            ]);
        });
    }
};
