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
        Schema::create('hall_equipment', function (Blueprint $table) {
            $table->uuid('id')->primary(); // hall_equipment_id
            $table->foreignUuid('hall_id')->constrained('halls')->onDelete('cascade');
            $table->foreignUuid('equipment_id')->constrained('equipment')->onDelete('cascade');
            $table->integer('quantity')->default(0);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_equipment');
    }
};
