<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Para SQLite, necesitamos recrear la tabla porque no soporta ALTER COLUMN para ENUM
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal con la nueva estructura
            Schema::create('custom_fields_temp', function (Blueprint $table) {
                $table->id();
                $table->enum('field_type', [
                    'brand',
                    'racket_model',
                    'drive_rubber_model',
                    'backhand_rubber_model',
                    'drive_rubber_hardness',
                    'backhand_rubber_hardness',
                    'club',
                    'league'
                ]);
                $table->string('value');
                $table->string('normalized_value');
                $table->integer('usage_count')->default(1);
                $table->timestamp('first_used_at');
                $table->timestamp('last_used_at');
                $table->timestamps();
                
                // Índices para optimizar búsquedas
                $table->index(['field_type', 'normalized_value']);
                $table->unique(['field_type', 'normalized_value']);
            });

            // Copiar datos existentes
            DB::statement('INSERT INTO custom_fields_temp SELECT * FROM custom_fields');

            // Eliminar tabla original
            Schema::dropIfExists('custom_fields');

            // Renombrar tabla temporal
            Schema::rename('custom_fields_temp', 'custom_fields');
        } else {
            // Para MySQL/PostgreSQL, usar ALTER TABLE
            DB::statement("ALTER TABLE custom_fields MODIFY COLUMN field_type ENUM('brand', 'racket_model', 'drive_rubber_model', 'backhand_rubber_model', 'drive_rubber_hardness', 'backhand_rubber_hardness', 'club', 'league')");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Para SQLite, recrear tabla con estructura original
        if (DB::getDriverName() === 'sqlite') {
            // Crear tabla temporal con la estructura original
            Schema::create('custom_fields_temp', function (Blueprint $table) {
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
                $table->string('normalized_value');
                $table->integer('usage_count')->default(1);
                $table->timestamp('first_used_at');
                $table->timestamp('last_used_at');
                $table->timestamps();
                
                // Índices para optimizar búsquedas
                $table->index(['field_type', 'normalized_value']);
                $table->unique(['field_type', 'normalized_value']);
            });

            // Copiar solo los datos que no sean club o league
            DB::statement("INSERT INTO custom_fields_temp SELECT * FROM custom_fields WHERE field_type NOT IN ('club', 'league')");

            // Eliminar tabla actual
            Schema::dropIfExists('custom_fields');

            // Renombrar tabla temporal
            Schema::rename('custom_fields_temp', 'custom_fields');
        } else {
            // Para MySQL/PostgreSQL, usar ALTER TABLE
            DB::statement("ALTER TABLE custom_fields MODIFY COLUMN field_type ENUM('brand', 'racket_model', 'drive_rubber_model', 'backhand_rubber_model', 'drive_rubber_hardness', 'backhand_rubber_hardness')");
        }
    }
};