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
        Schema::create('booking_changes', function (Blueprint $table) {
            $table->uuid('id')->primary(); // change_id
            $table->foreignUuid('booking_id')->constrained('bookings');
            $table->foreignUuid('changed_by_manager_id')->constrained('managers');
            $table->string('change_type', 40);
            $table->timestampTz('old_start_datetime')->nullable();
            $table->timestampTz('old_end_datetime')->nullable();
            $table->decimal('old_total_price', 12, 2)->nullable();
            $table->timestampTz('new_start_datetime')->nullable();
            $table->timestampTz('new_end_datetime')->nullable();
            $table->decimal('new_total_price', 12, 2)->nullable();
            $table->decimal('payment_adjustment_amount', 12, 2)->default(0);
            $table->text('reason')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_changes');
    }
};
