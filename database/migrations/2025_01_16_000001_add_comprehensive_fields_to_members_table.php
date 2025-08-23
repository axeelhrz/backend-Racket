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
        Schema::table('members', function (Blueprint $table) {
            // Location information
            $table->string('country')->default('Ecuador')->after('gender');
            $table->string('province')->nullable()->after('country');
            $table->string('city')->nullable()->after('province');
            
            // Playing style information
            $table->enum('dominant_hand', ['right', 'left'])->nullable()->after('city');
            $table->enum('playing_side', ['derecho', 'zurdo'])->nullable()->after('dominant_hand');
            $table->enum('playing_style', ['clasico', 'lapicero'])->nullable()->after('playing_side');
            
            // Racket information
            $table->string('racket_brand')->nullable()->after('playing_style');
            $table->string('racket_model')->nullable()->after('racket_brand');
            $table->string('racket_custom_brand')->nullable()->after('racket_model');
            $table->string('racket_custom_model')->nullable()->after('racket_custom_brand');
            
            // Drive rubber information
            $table->string('drive_rubber_brand')->nullable()->after('racket_custom_model');
            $table->string('drive_rubber_model')->nullable()->after('drive_rubber_brand');
            $table->enum('drive_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('drive_rubber_model');
            $table->enum('drive_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable()->after('drive_rubber_type');
            $table->string('drive_rubber_sponge')->nullable()->after('drive_rubber_color');
            $table->string('drive_rubber_hardness')->nullable()->after('drive_rubber_sponge');
            $table->string('drive_rubber_custom_brand')->nullable()->after('drive_rubber_hardness');
            $table->string('drive_rubber_custom_model')->nullable()->after('drive_rubber_custom_brand');
            
            // Backhand rubber information
            $table->string('backhand_rubber_brand')->nullable()->after('drive_rubber_custom_model');
            $table->string('backhand_rubber_model')->nullable()->after('backhand_rubber_brand');
            $table->enum('backhand_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('backhand_rubber_model');
            $table->enum('backhand_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable()->after('backhand_rubber_type');
            $table->string('backhand_rubber_sponge')->nullable()->after('backhand_rubber_color');
            $table->string('backhand_rubber_hardness')->nullable()->after('backhand_rubber_sponge');
            $table->string('backhand_rubber_custom_brand')->nullable()->after('backhand_rubber_hardness');
            $table->string('backhand_rubber_custom_model')->nullable()->after('backhand_rubber_custom_brand');
            
            // Additional information
            $table->text('notes')->nullable()->after('backhand_rubber_custom_model');
            $table->integer('ranking_position')->nullable()->after('notes');
            $table->date('ranking_last_updated')->nullable()->after('ranking_position');
            
            // Update existing fields
            $table->string('doc_id')->nullable()->change(); // Remove unique constraint temporarily
        });
        
        // Add indexes for better performance
        Schema::table('members', function (Blueprint $table) {
            $table->index(['country', 'province', 'city']);
            $table->index(['dominant_hand', 'playing_style']);
            $table->index(['ranking_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['country', 'province', 'city']);
            $table->dropIndex(['dominant_hand', 'playing_style']);
            $table->dropIndex(['ranking_position']);
            
            $table->dropColumn([
                'country', 'province', 'city',
                'dominant_hand', 'playing_side', 'playing_style',
                'racket_brand', 'racket_model', 'racket_custom_brand', 'racket_custom_model',
                'drive_rubber_brand', 'drive_rubber_model', 'drive_rubber_type', 'drive_rubber_color',
                'drive_rubber_sponge', 'drive_rubber_hardness', 'drive_rubber_custom_brand', 'drive_rubber_custom_model',
                'backhand_rubber_brand', 'backhand_rubber_model', 'backhand_rubber_type', 'backhand_rubber_color',
                'backhand_rubber_sponge', 'backhand_rubber_hardness', 'backhand_rubber_custom_brand', 'backhand_rubber_custom_model',
                'notes', 'ranking_position', 'ranking_last_updated'
            ]);
        });
    }
};