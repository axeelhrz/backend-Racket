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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->enum('field_type', [
                'brand',
                'racket_model',
                'drive_rubber_model',
                'backhand_rubber_model',
                'drive_rubber_hardness',
                'backhand_rubber_hardness'
            ]);
            $table->string('value');
            $table->string('normalized_value'); // Para búsquedas rápidas
            $table->integer('usage_count')->default(1); // Contador de uso
            $table->timestamp('first_used_at');
            $table->timestamp('last_used_at');
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['field_type', 'normalized_value']);
            $table->unique(['field_type', 'normalized_value']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_fields');
    }
};