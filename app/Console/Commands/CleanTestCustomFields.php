<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CustomField;
use Illuminate\Support\Facades\DB;

class CleanTestCustomFields extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-fields:clean-test-data 
                            {--confirm : Skip confirmation prompt}
                            {--type= : Clean only specific field type (brand, racket_model, drive_rubber_model, backhand_rubber_model, drive_rubber_hardness, backhand_rubber_hardness, club, league)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all test data from custom_fields table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧹 Limpieza de datos de prueba en custom_fields');
        $this->newLine();

        // Obtener estadísticas actuales
        $stats = $this->getCurrentStats();
        
        if (empty($stats)) {
            $this->info('✅ No hay datos personalizados para limpiar.');
            return 0;
        }

        // Mostrar estadísticas actuales
        $this->displayCurrentStats($stats);

        // Confirmar acción si no se usa --confirm
        if (!$this->option('confirm')) {
            if (!$this->confirm('¿Estás seguro de que quieres eliminar todos estos datos de prueba?')) {
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
        if (!empty($finalStats)) {
            $this->newLine();
            $this->warn('⚠️  Aún quedan algunos registros:');
            $this->displayCurrentStats($finalStats);
        } else {
            $this->info('🎉 Tabla custom_fields completamente limpia.');
        }

        return 0;
    }

    /**
     * Obtener estadísticas actuales de la tabla
     */
    private function getCurrentStats(): array
    {
        $typeFilter = $this->option('type');
        
        $query = CustomField::select('field_type', DB::raw('COUNT(*) as count'))
                           ->groupBy('field_type');
        
        if ($typeFilter) {
            $query->where('field_type', $typeFilter);
        }
        
        return $query->get()->pluck('count', 'field_type')->toArray();
    }

    /**
     * Mostrar estadísticas en formato tabla
     */
    private function displayCurrentStats(array $stats): void
    {
        $this->info('📊 Datos personalizados actuales:');
        
        $tableData = [];
        foreach ($stats as $fieldType => $count) {
            $tableData[] = [
                'Tipo de Campo' => $this->getFieldTypeLabel($fieldType),
                'Cantidad' => $count
            ];
        }
        
        $this->table(['Tipo de Campo', 'Cantidad'], $tableData);
        
        $total = array_sum($stats);
        $this->info("📈 Total de registros: {$total}");
        $this->newLine();
    }

    /**
     * Limpiar datos de prueba
     */
    private function cleanTestData(): int
    {
        $typeFilter = $this->option('type');
        
        $this->info('🗑️  Eliminando datos de prueba...');
        
        $query = CustomField::query();
        
        if ($typeFilter) {
            $query->where('field_type', $typeFilter);
            $this->info("   Filtrando por tipo: {$this->getFieldTypeLabel($typeFilter)}");
        }
        
        $deletedCount = $query->count();
        $query->delete();
        
        return $deletedCount;
    }

    /**
     * Obtener etiqueta legible para el tipo de campo
     */
    private function getFieldTypeLabel(string $fieldType): string
    {
        $labels = [
            'brand' => 'Marcas (Compartidas)',
            'racket_model' => 'Modelos de Raqueta',
            'drive_rubber_model' => 'Modelos de Caucho Drive',
            'backhand_rubber_model' => 'Modelos de Caucho Back',
            'drive_rubber_hardness' => 'Hardness Drive',
            'backhand_rubber_hardness' => 'Hardness Back',
            'club' => 'Clubes',
            'league' => 'Ligas'
        ];

        return $labels[$fieldType] ?? $fieldType;
    }
}