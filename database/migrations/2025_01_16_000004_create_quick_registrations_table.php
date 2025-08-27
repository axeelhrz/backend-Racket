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
        Schema::create('quick_registrations', function (Blueprint $table) {
            $table->id();
            
            // Código de registro secuencial
            $table->string('registration_code')->unique(); // CensoCodigo1, CensoCodigo2, etc.
            
            // Información personal básica
            $table->string('first_name'); // Nombres
            $table->string('last_name'); // Apellido
            $table->string('doc_id')->nullable(); // Cédula
            $table->string('email')->unique();
            $table->string('phone'); // Celular
            $table->date('birth_date')->nullable(); // Fecha de nacimiento
            $table->enum('gender', ['masculino', 'femenino'])->nullable(); // Sexo
            
            // Ubicación
            $table->string('country')->default('Ecuador'); // País
            $table->string('province'); // Provincia
            $table->string('city'); // Ciudad
            
            // Club (sin federación)
            $table->string('club_name')->nullable(); // Club
            
            // Estilo de juego
            $table->enum('playing_side', ['derecho', 'zurdo'])->nullable(); // Lado de juego
            $table->enum('playing_style', ['clasico', 'lapicero'])->nullable(); // Tipo de juego
            
            // Raqueta - palo
            $table->string('racket_brand')->nullable(); // Marca
            $table->string('racket_model')->nullable(); // Modelo
            $table->string('racket_custom_brand')->nullable(); // Marca personalizada
            $table->string('racket_custom_model')->nullable(); // Modelo personalizado
            
            // Caucho del drive - Updated with corrected rubber type
            $table->string('drive_rubber_brand')->nullable(); // Marca
            $table->string('drive_rubber_model')->nullable(); // Modelo
            $table->enum('drive_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopsping'])->nullable(); // Tipo corregido
            $table->enum('drive_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable(); // Color
            $table->string('drive_rubber_sponge')->nullable(); // Esponja - Updated to support new values
            $table->string('drive_rubber_hardness')->nullable(); // Hardness
            $table->string('drive_rubber_custom_brand')->nullable(); // Marca personalizada
            $table->string('drive_rubber_custom_model')->nullable(); // Modelo personalizado
            
            // Caucho del back - Updated with corrected rubber type
            $table->string('backhand_rubber_brand')->nullable(); // Marca
            $table->string('backhand_rubber_model')->nullable(); // Modelo
            $table->enum('backhand_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopsping'])->nullable(); // Tipo corregido
            $table->enum('backhand_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable(); // Color
            $table->string('backhand_rubber_sponge')->nullable(); // Esponja - Updated to support new values
            $table->string('backhand_rubber_hardness')->nullable(); // Hardness
            $table->string('backhand_rubber_custom_brand')->nullable(); // Marca personalizada
            $table->string('backhand_rubber_custom_model')->nullable(); // Modelo personalizado
            
            // Información adicional
            $table->text('notes')->nullable();
            $table->string('photo_path')->nullable(); // Ruta de la foto
            
            // Estados y metadatos
            $table->enum('status', ['pending', 'contacted', 'approved', 'rejected'])->default('pending');
            $table->timestamp('contacted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable(); // Para datos adicionales
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['province', 'city']);
            $table->index(['club_name']);
            $table->index('registration_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quick_registrations');
    }
};