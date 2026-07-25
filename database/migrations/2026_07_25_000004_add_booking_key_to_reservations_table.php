<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A nullable unique key reserves one active appointment per time slot.
     * Cancelled appointments clear the key, retaining their history while
     * making the time slot available for a new reservation.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('booking_key', 32)->nullable()->unique()->after('reservation_time');
        });

        DB::table('reservations')
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('id')
            ->each(function (object $reservation): void {
                DB::table('reservations')
                    ->where('id', $reservation->id)
                    ->update([
                        'booking_key' => sprintf('%s|%s', $reservation->reservation_date, $reservation->reservation_time),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropUnique(['booking_key']);
            $table->dropColumn('booking_key');
        });
    }
};
