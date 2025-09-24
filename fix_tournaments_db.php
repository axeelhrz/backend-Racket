<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'raquet_power',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "🔧 Checking tournaments table structure...\n";

try {
    // Check if tournaments table exists
    if (!Schema::hasTable('tournaments')) {
        echo "❌ Tournaments table does not exist!\n";
        echo "Creating tournaments table...\n";
        
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code', 50)->nullable()->unique();
            $table->string('tournament_type')->default('individual');
            $table->string('tournament_format')->default('single_elimination');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_deadline');
            $table->integer('max_participants')->default(0);
            $table->integer('current_participants')->default(0);
            $table->decimal('entry_fee', 10, 2)->default(0);
            $table->decimal('prize_pool', 10, 2)->default(0);
            $table->enum('status', ['upcoming', 'active', 'completed', 'cancelled', 'draft', 'open', 'in_progress'])->default('upcoming');
            $table->integer('matches_played')->default(0);
            $table->integer('matches_total')->default(0);
            $table->string('location')->nullable();
            $table->text('rules')->nullable();
            $table->timestamps();
        });
        
        echo "✅ Basic tournaments table created!\n";
    } else {
        echo "✅ Tournaments table exists\n";
    }

    // Check for required columns
    $requiredColumns = [
        'name', 'description', 'code', 'tournament_type', 'tournament_format',
        'start_date', 'end_date', 'registration_deadline', 'max_participants',
        'current_participants', 'entry_fee', 'status', 'location'
    ];

    $missingColumns = [];
    foreach ($requiredColumns as $column) {
        if (!Schema::hasColumn('tournaments', $column)) {
            $missingColumns[] = $column;
        }
    }

    if (!empty($missingColumns)) {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "Please run the migration: php artisan migrate\n";
    } else {
        echo "✅ All basic columns exist\n";
    }

    // Test a simple query
    $count = Capsule::table('tournaments')->count();
    echo "📊 Current tournaments count: {$count}\n";

    echo "🎉 Database check completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}