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
        Schema::create('halls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_park_id')->constrained('business_parks')->onDelete('cascade');
            $table->string('name', 120);
            $table->integer('capacity');
            $table->decimal('area_sq_m', 8, 2);
            $table->text('description')->nullable();
            $table->string('status', 20)->default('available'); // available / unavailable
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('halls');
    }
};
