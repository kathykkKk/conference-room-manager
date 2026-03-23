<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('policy_slot_holds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hall_id')->constrained('halls')->cascadeOnDelete();
            $table->date('hold_date');
            $table->string('slot_start', 5);
            $table->string('reason', 64)->default('morning_policy');
            $table->timestampsTz();

            $table->unique(['hall_id', 'hold_date', 'slot_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('policy_slot_holds');
    }
};
