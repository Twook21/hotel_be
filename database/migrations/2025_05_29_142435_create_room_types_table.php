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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->string('name'); // Standard, Deluxe, Suite
            $table->text('description');
            $table->decimal('price_per_night', 10, 2);
            $table->integer('capacity'); // max guests
            $table->decimal('size', 5, 2)->nullable(); // room size in m²
            $table->json('facilities')->nullable(); // ["ac", "tv", "minibar"]
            $table->json('images')->nullable();
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
