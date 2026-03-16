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
        Schema::create('hall_pricing_rules', function (Blueprint $table) {
            $table->uuid('id')->primary(); // rule_id
            $table->foreignUuid('hall_id')->constrained('halls')->onDelete('cascade');
            $table->integer('priority')->default(0);
            $table->decimal('price_per_hour', 12, 2);
            $table->date('apply_from_date');
            $table->date('apply_until_date')->nullable();
            $table->string('weekdays', 50)->nullable(); // Список дней: "1,2,3"
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_pricing_rules');
    }
};
