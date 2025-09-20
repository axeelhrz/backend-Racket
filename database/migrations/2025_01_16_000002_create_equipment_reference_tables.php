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
        // Racket brands table
        Schema::create('racket_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Racket models table
        Schema::create('racket_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('racket_brands')->onDelete('cascade');
            $table->string('name');
            $table->string('type')->nullable(); // e.g., 'carbono', 'madera', etc.
            $table->integer('speed')->nullable(); // 1-10 scale
            $table->integer('control')->nullable(); // 1-10 scale
            $table->decimal('weight', 5, 2)->nullable(); // in grams
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['brand_id', 'name']);
        });

        // Rubber brands table
        Schema::create('rubber_brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('country')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Rubber models table
        Schema::create('rubber_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('rubber_brands')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin']);
            $table->integer('speed')->nullable(); // 1-10 scale
            $table->integer('spin')->nullable(); // 1-10 scale
            $table->integer('control')->nullable(); // 1-10 scale
            $table->json('available_colors')->nullable(); // JSON array of available colors
            $table->json('available_sponges')->nullable(); // JSON array of available sponge thicknesses
            $table->json('available_hardness')->nullable(); // JSON array of available hardness levels
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['brand_id', 'name']);
        });

        // Ecuador provinces and cities reference
        Schema::create('ecuador_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province');
            $table->string('city');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['province', 'city']);
            $table->index(['province']);
        });

        // Table tennis clubs reference (for the clubs mentioned in requirements)
        Schema::create('tt_clubs_reference', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->string('province');
            $table->string('federation')->nullable(); // e.g., 'Fede Guayas', 'Fede Manabi'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tt_clubs_reference');
        Schema::dropIfExists('ecuador_locations');
        Schema::dropIfExists('rubber_models');
        Schema::dropIfExists('rubber_brands');
        Schema::dropIfExists('racket_models');
        Schema::dropIfExists('racket_brands');
    }
};