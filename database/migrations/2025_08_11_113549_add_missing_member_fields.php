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
            // Censo information
            $table->string('census_code')->nullable()->after('doc_id');
            
            // Federation information
            $table->string('federation')->nullable()->after('city');
            
            // More specific rubber type options
            $table->dropColumn(['drive_rubber_type', 'backhand_rubber_type']);
        });
        
        // Re-add rubber type columns with more specific options
        Schema::table('members', function (Blueprint $table) {
            $table->enum('drive_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('drive_rubber_model');
            $table->enum('backhand_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('backhand_rubber_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['census_code', 'federation']);
            $table->dropColumn(['drive_rubber_type', 'backhand_rubber_type']);
        });
        
        // Restore original rubber type columns
        Schema::table('members', function (Blueprint $table) {
            $table->enum('drive_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('drive_rubber_model');
            $table->enum('backhand_rubber_type', ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'])->nullable()->after('backhand_rubber_model');
        });
    }
};