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
        Schema::create('dishes', function (Blueprint $table) {
            $table->id();
            $table->string('dish_id', 64)->unique();
            $table->string('name');
            $table->string('category', 32)->index(); // indian, mexican, italian, chinese
            $table->text('description');
            $table->decimal('price', 8, 2);
            $table->string('image');
            $table->string('badge')->nullable();
            $table->string('spice_level', 32)->nullable();
            $table->boolean('is_dessert')->default(false);
            $table->string('sweetness')->nullable();
            $table->boolean('is_veg')->default(false);
            $table->string('portion_size')->nullable();
            $table->string('prep_time')->nullable();
            $table->string('calories')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('allergens')->nullable();
            $table->boolean('is_available')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
