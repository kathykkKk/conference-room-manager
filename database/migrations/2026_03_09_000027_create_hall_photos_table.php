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
        Schema::create('hall_photos', function (Blueprint $table) {
            $table->uuid('id')->primary(); // photo_id
            $table->foreignUuid('hall_id')->constrained('halls')->onDelete('cascade');
            $table->string('url', 512);
            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hall_photos');
    }
};
