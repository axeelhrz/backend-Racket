<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

echo "Checking tournament_participants table structure:\n";

try {
    // Check if table exists
    if (Schema::hasTable('tournament_participants')) {
        echo "✓ Table 'tournament_participants' exists\n";
        
        // Get column listing
        $columns = Schema::getColumnListing('tournament_participants');
        echo "\nColumns in tournament_participants:\n";
        foreach ($columns as $column) {
            echo "- $column\n";
        }
        
        // Get detailed column information
        echo "\nDetailed column information:\n";
        $columnDetails = DB::select("DESCRIBE tournament_participants");
        foreach ($columnDetails as $detail) {
            echo "- {$detail->Field}: {$detail->Type} (Null: {$detail->Null}, Default: {$detail->Default})\n";
        }
        
    } else {
        echo "✗ Table 'tournament_participants' does not exist\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}