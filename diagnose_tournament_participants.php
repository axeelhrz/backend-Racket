<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "=== TOURNAMENT PARTICIPANTS TABLE DIAGNOSIS ===\n\n";

try {
    // Check if table exists
    if (Schema::hasTable('tournament_participants')) {
        echo "✓ Table 'tournament_participants' exists\n\n";
        
        // Get column listing
        $columns = Schema::getColumnListing('tournament_participants');
        echo "Current columns (" . count($columns) . " total):\n";
        foreach ($columns as $column) {
            echo "  - $column\n";
        }
        
        echo "\nDetailed column information:\n";
        $columnDetails = DB::select("DESCRIBE tournament_participants");
        foreach ($columnDetails as $detail) {
            echo "  - {$detail->Field}: {$detail->Type}";
            if ($detail->Null === 'YES') echo " (nullable)";
            if ($detail->Default !== null) echo " (default: {$detail->Default})";
            if ($detail->Key !== '') echo " (key: {$detail->Key})";
            echo "\n";
        }
        
        // Check what columns we expect vs what exists
        $expectedColumns = [
            'id', 'tournament_id', 'member_id', 'user_name', 'user_email', 
            'user_phone', 'ranking', 'status', 'registration_date', 'notes', 
            'custom_fields', 'created_at', 'updated_at'
        ];
        
        echo "\nColumn status check:\n";
        foreach ($expectedColumns as $expectedCol) {
            $exists = in_array($expectedCol, $columns);
            echo "  " . ($exists ? "✓" : "✗") . " $expectedCol" . ($exists ? "" : " (missing)") . "\n";
        }
        
        // Check for foreign keys
        echo "\nForeign key constraints:\n";
        $foreignKeys = DB::select("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'tournament_participants' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if (empty($foreignKeys)) {
            echo "  No foreign key constraints found\n";
        } else {
            foreach ($foreignKeys as $fk) {
                echo "  - {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
            }
        }
        
        // Check row count
        $count = DB::table('tournament_participants')->count();
        echo "\nTable contains $count rows\n";
        
    } else {
        echo "✗ Table 'tournament_participants' does not exist\n";
        
        // Check if any related tables exist
        $relatedTables = ['tournaments', 'members', 'users'];
        echo "\nChecking related tables:\n";
        foreach ($relatedTables as $table) {
            $exists = Schema::hasTable($table);
            echo "  " . ($exists ? "✓" : "✗") . " $table\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DIAGNOSIS ===\n";