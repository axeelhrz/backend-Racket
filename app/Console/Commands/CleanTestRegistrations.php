<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QuickRegistration;
use Illuminate\Support\Facades\DB;

class CleanTestRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registrations:clean-test-data 
                            {--confirm : Skip confirmation prompt}
                            {--email= : Clean only registrations with specific email pattern}
                            {--name= : Clean only registrations with specific name pattern}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean test registrations from quick_registrations table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Limpieza de registros de prueba en quick_registrations');
        $this->newLine();

        // Obtener estadísticas actuales
        $stats = $this->getCurrentStats();
        
        if ($stats['total'] === 0) {
            $this->info('✅ No hay registros para limpiar.');
            return 0;
        }

        // Mostrar estadísticas actuales
        $this->displayCurrentStats($stats);

        // Mostrar algunos registros de ejemplo
        $this->displaySampleRegistrations();

        // Confirmar acción si no se usa --confirm
        if (!$this->option('confirm')) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar estos registros de prueba?')) {
                $this->info('❌ Operación cancelada.');
                return 0;
            }
        }

        // Limpiar datos
        $deletedCount = $this->cleanTestData();

        $this->newLine();
        $this->info("✅ Limpieza completada. Se eliminaron {$deletedCount} registros.");
        
        // Mostrar estadísticas finales
        $finalStats = $this->getCurrentStats();
        $this->info("📈 Registros restantes: {$finalStats['total']}");

        return 0;
    }

    /**
     * Obtener estadísticas actuales de la tabla
     */
    private function getCurrentStats(): array
    {
        $query = QuickRegistration::query();
        
        // Aplicar filtros si se especifican
        $this->applyFilters($query);
        
        $total = $query->count();
        
        return [
            'total' => $total,
            'with_test_emails' => QuickRegistration::where('email', 'LIKE', '%test%')
                                                  ->orWhere('email', 'LIKE', '%prueba%')
                                                  ->orWhere('email', 'LIKE', '%demo%')
                                                  ->count(),
            'with_test_names' => QuickRegistration::where('first_name', 'LIKE', '%test%')
                                                 ->orWhere('first_name', 'LIKE', '%prueba%')
                                                 ->orWhere('last_name', 'LIKE', '%test%')
                                                 ->orWhere('last_name', 'LIKE', '%prueba%')
                                                 ->count(),
        ];
    }

    /**
     * Mostrar estadísticas en formato tabla
     */
    private function displayCurrentStats(array $stats): void
    {
        $this->info('📊 Registros actuales:');
        
        $tableData = [
            ['Tipo', 'Cantidad'],
            ['Total de registros', $stats['total']],
            ['Con emails de prueba', $stats['with_test_emails']],
            ['Con nombres de prueba', $stats['with_test_names']],
        ];
        
        $this->table(['Tipo', 'Cantidad'], array_slice($tableData, 1));
        $this->newLine();
    }

    /**
     * Mostrar algunos registros de ejemplo
     */
    private function displaySampleRegistrations(): void
    {
        $query = QuickRegistration::query();
        $this->applyFilters($query);
        
        $samples = $query->select('first_name', 'last_name', 'email', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

        if ($samples->isNotEmpty()) {
            $this->info('📋 Ejemplos de registros que serán eliminados:');
            
            $tableData = [];
            foreach ($samples as $sample) {
                $tableData[] = [
                    'Nombre' => $sample->first_name . ' ' . $sample->last_name,
                    'Email' => $sample->email,
                    'Fecha' => $sample->created_at->format('Y-m-d H:i:s')
                ];
            }
            
            $this->table(['Nombre', 'Email', 'Fecha'], $tableData);
            $this->newLine();
        }
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query): void
    {
        $emailFilter = $this->option('email');
        $nameFilter = $this->option('name');

        if ($emailFilter) {
            $query->where('email', 'LIKE', "%{$emailFilter}%");
        }

        if ($nameFilter) {
            $query->where(function($q) use ($nameFilter) {
                $q->where('first_name', 'LIKE', "%{$nameFilter}%")
                  ->orWhere('last_name', 'LIKE', "%{$nameFilter}%");
            });
        }

        // Si no hay filtros específicos, buscar patrones comunes de prueba
        if (!$emailFilter && !$nameFilter) {
            $query->where(function($q) {
                $q->where('email', 'LIKE', '%test%')
                  ->orWhere('email', 'LIKE', '%prueba%')
                  ->orWhere('email', 'LIKE', '%demo%')
                  ->orWhere('first_name', 'LIKE', '%test%')
                  ->orWhere('first_name', 'LIKE', '%prueba%')
                  ->orWhere('last_name', 'LIKE', '%test%')
                  ->orWhere('last_name', 'LIKE', '%prueba%')
                  ->orWhere('first_name', '=', 'Juan') // Datos por defecto del formulario
                  ->orWhere('doc_id', '=', '0999999999') // Cédula por defecto
                  ->orWhere('phone', '=', '0989999999'); // Teléfono por defecto
            });
        }
    }

    /**
     * Limpiar datos de prueba
     */
    private function cleanTestData(): int
    {
        $this->info('🗑️  Eliminando registros de prueba...');
        
        $query = QuickRegistration::query();
        $this->applyFilters($query);
        
        $deletedCount = $query->count();
        $query->delete();
        
        return $deletedCount;
    }
}