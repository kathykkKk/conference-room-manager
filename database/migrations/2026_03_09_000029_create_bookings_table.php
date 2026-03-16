<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary(); // booking_id
            $table->foreignUuid('hall_id')->constrained('halls');
            $table->foreignUuid('client_id')->constrained('clients');
            $table->timestampTz('start_datetime');
            $table->timestampTz('end_datetime');
            $table->decimal('total_price', 12, 2);
            $table->string('status', 30)->default('pending'); // pending, confirmed, cancelled, completed
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
