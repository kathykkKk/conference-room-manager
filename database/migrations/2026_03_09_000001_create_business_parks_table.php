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
        Schema::create('business_parks', function (Blueprint $table) {
            $table->uuid('id')->primary(); // В Laravel 'id' будет хранить business_park_id
            $table->string('name', 150);
            $table->string('address', 255);
            $table->string('city', 100);
            $table->timestampsTz(); // Создаст created_at и updated_at с таймзоной
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_parks');
    }
};
