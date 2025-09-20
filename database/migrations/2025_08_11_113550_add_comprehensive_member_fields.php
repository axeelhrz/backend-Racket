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
            // Only add columns if they don't already exist
            if (!Schema::hasColumn('members', 'country')) {
                $table->string('country')->default('Ecuador')->after('gender');
            }
            if (!Schema::hasColumn('members', 'province')) {
                $table->string('province')->nullable()->after('country');
            }
            if (!Schema::hasColumn('members', 'city')) {
                $table->string('city')->nullable()->after('province');
            }
            if (!Schema::hasColumn('members', 'dominant_hand')) {
                $table->enum('dominant_hand', ['right', 'left'])->nullable()->after('city');
            }
            if (!Schema::hasColumn('members', 'playing_side')) {
                $table->enum('playing_side', ['derecho', 'zurdo'])->nullable()->after('dominant_hand');
            }
            if (!Schema::hasColumn('members', 'playing_style')) {
                $table->enum('playing_style', ['clasico', 'lapicero'])->nullable()->after('playing_side');
            }
            if (!Schema::hasColumn('members', 'racket_brand')) {
                $table->string('racket_brand')->nullable()->after('playing_style');
            }
            if (!Schema::hasColumn('members', 'racket_model')) {
                $table->string('racket_model')->nullable()->after('racket_brand');
            }
            if (!Schema::hasColumn('members', 'racket_custom_brand')) {
                $table->string('racket_custom_brand')->nullable()->after('racket_model');
            }
            if (!Schema::hasColumn('members', 'racket_custom_model')) {
                $table->string('racket_custom_model')->nullable()->after('racket_custom_brand');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_brand')) {
                $table->string('drive_rubber_brand')->nullable()->after('racket_custom_model');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_model')) {
                $table->string('drive_rubber_model')->nullable()->after('drive_rubber_brand');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_type')) {
                $table->enum('drive_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('drive_rubber_model');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_color')) {
                $table->enum('drive_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable()->after('drive_rubber_type');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_sponge')) {
                $table->string('drive_rubber_sponge')->nullable()->after('drive_rubber_color');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_hardness')) {
                $table->string('drive_rubber_hardness')->nullable()->after('drive_rubber_sponge');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_custom_brand')) {
                $table->string('drive_rubber_custom_brand')->nullable()->after('drive_rubber_hardness');
            }
            if (!Schema::hasColumn('members', 'drive_rubber_custom_model')) {
                $table->string('drive_rubber_custom_model')->nullable()->after('drive_rubber_custom_brand');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_brand')) {
                $table->string('backhand_rubber_brand')->nullable()->after('drive_rubber_custom_model');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_model')) {
                $table->string('backhand_rubber_model')->nullable()->after('backhand_rubber_brand');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_type')) {
                $table->enum('backhand_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('backhand_rubber_model');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_color')) {
                $table->enum('backhand_rubber_color', ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'])->nullable()->after('backhand_rubber_type');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_sponge')) {
                $table->string('backhand_rubber_sponge')->nullable()->after('backhand_rubber_color');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_hardness')) {
                $table->string('backhand_rubber_hardness')->nullable()->after('backhand_rubber_sponge');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_custom_brand')) {
                $table->string('backhand_rubber_custom_brand')->nullable()->after('backhand_rubber_hardness');
            }
            if (!Schema::hasColumn('members', 'backhand_rubber_custom_model')) {
                $table->string('backhand_rubber_custom_model')->nullable()->after('backhand_rubber_custom_brand');
            }
            if (!Schema::hasColumn('members', 'notes')) {
                $table->text('notes')->nullable()->after('backhand_rubber_custom_model');
            }
            if (!Schema::hasColumn('members', 'ranking_position')) {
                $table->integer('ranking_position')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('members', 'ranking_last_updated')) {
                $table->date('ranking_last_updated')->nullable()->after('ranking_position');
            }
            if (!Schema::hasColumn('members', 'census_code')) {
                $table->string('census_code')->nullable()->after('doc_id');
            }
            if (!Schema::hasColumn('members', 'federation')) {
                $table->string('federation')->nullable()->after('city');
            }
        });
        
        // Skip index creation for now since they already exist in local environment
        // In production, these will be created when the columns are first added
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // Drop columns if they exist
            $columnsToCheck = [
                'country', 'province', 'city', 'dominant_hand', 'playing_side', 'playing_style',
                'racket_brand', 'racket_model', 'racket_custom_brand', 'racket_custom_model',
                'drive_rubber_brand', 'drive_rubber_model', 'drive_rubber_type', 'drive_rubber_color',
                'drive_rubber_sponge', 'drive_rubber_hardness', 'drive_rubber_custom_brand', 'drive_rubber_custom_model',
                'backhand_rubber_brand', 'backhand_rubber_model', 'backhand_rubber_type', 'backhand_rubber_color',
                'backhand_rubber_sponge', 'backhand_rubber_hardness', 'backhand_rubber_custom_brand', 'backhand_rubber_custom_model',
                'notes', 'ranking_position', 'ranking_last_updated', 'census_code', 'federation'
            ];
            
            $columnsToRemove = [];
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $columnsToRemove[] = $column;
                }
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};