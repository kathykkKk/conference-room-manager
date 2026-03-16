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
        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary(); // refund_id
            $table->foreignUuid('payment_id')->constrained('payments');
            $table->decimal('amount', 12, 2);
            $table->text('reason')->nullable();
            $table->string('status', 30)->default('pending'); // pending, processed, rejected
            $table->foreignUuid('processed_by_manager_id')->nullable()->constrained('managers');
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
