<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EquipmentController extends Controller
{
    /**
     * Get all equipment reference data for forms
     */
    public function getEquipmentData(): JsonResponse
    {
        try {
            $data = [
                'brands' => [
                    'racket' => DB::table('racket_brands')
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name', 'country']),
                    'rubber' => DB::table('rubber_brands')
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->get(['id', 'name', 'country']),
                ],
                'models' => [
                    'racket' => DB::table('racket_models')
                        ->join('racket_brands', 'racket_models.brand_id', '=', 'racket_brands.id')
                        ->where('racket_models.is_active', true)
                        ->where('racket_brands.is_active', true)
                        ->select('racket_models.*', 'racket_brands.name as brand_name')
                        ->orderBy('racket_brands.name')
                        ->orderBy('racket_models.name')
                        ->get(),
                    'rubber' => DB::table('rubber_models')
                        ->join('rubber_brands', 'rubber_models.brand_id', '=', 'rubber_brands.id')
                        ->where('rubber_models.is_active', true)
                        ->where('rubber_brands.is_active', true)
                        ->select('rubber_models.*', 'rubber_brands.name as brand_name')
                        ->orderBy('rubber_brands.name')
                        ->orderBy('rubber_models.name')
                        ->get(),
                ],
                'locations' => DB::table('ecuador_locations')
                    ->where('is_active', true)
                    ->orderBy('province')
                    ->orderBy('city')
                    ->get(['province', 'city']),
                'tt_clubs' => DB::table('tt_clubs_reference')
                    ->where('is_active', true)
                    ->orderBy('province')
                    ->orderBy('city')
                    ->orderBy('name')
                    ->get(['name', 'city', 'province', 'federation']),
                'constants' => [
                    'rubber_colors' => ['negro', 'rojo', 'verde', 'azul', 'amarillo', 'morado', 'fucsia'],
                    'rubber_types' => ['liso', 'pupo_largo', 'pupo_corto', 'antitopspin'],
                    'sponge_thicknesses' => ['0.5', '0.7', '1.5', '1.6', '1.8', '1.9', '2', '2.1', '2.2', 'sin esponja'],
                    'hardness_levels' => ['h42', 'h44', 'h46', 'h48', 'h50', 'n/a'],
                    'popular_brands' => [
                        'Butterfly', 'DHS', 'Sanwei', 'Nittaku', 'Yasaka', 'Stiga', 
                        'Victas', 'Joola', 'Xiom', 'Saviga', 'Friendship', 'Dr. Neubauer'
                    ],
                    'provinces' => [
                        ['name' => 'Guayas', 'cities' => ['Guayaquil', 'Milagro', 'Buena Fe']],
                        ['name' => 'Pichincha', 'cities' => ['Quito']],
                        ['name' => 'Manabí', 'cities' => ['Manta', 'Portoviejo']],
                        ['name' => 'Azuay', 'cities' => ['Cuenca']],
                        ['name' => 'Tungurahua', 'cities' => ['Ambato']],
                        ['name' => 'Los Ríos', 'cities' => ['Quevedo', 'Urdaneta']],
                        ['name' => 'Santa Elena', 'cities' => ['La Libertad']],
                        ['name' => 'Galápagos', 'cities' => ['Puerto Ayora']],
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de equipamiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rubber brands
     */
    public function getRubberBrands(): JsonResponse
    {
        try {
            $brands = DB::table('rubber_brands')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'country']);

            return response()->json([
                'success' => true,
                'data' => $brands
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new rubber brand
     */
    public function addRubberBrand(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:rubber_brands,name',
                'country' => 'nullable|string|max:100',
            ]);

            $brandId = DB::table('rubber_brands')->insertGetId([
                'name' => $validated['name'],
                'country' => $validated['country'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $brand = DB::table('rubber_brands')->where('id', $brandId)->first();

            return response()->json([
                'success' => true,
                'message' => 'Marca de caucho agregada exitosamente',
                'data' => $brand
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar marca de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rubber models by brand
     */
    public function getRubberModels(Request $request): JsonResponse
    {
        try {
            $query = DB::table('rubber_models')
                ->join('rubber_brands', 'rubber_models.brand_id', '=', 'rubber_brands.id')
                ->where('rubber_models.is_active', true)
                ->where('rubber_brands.is_active', true)
                ->select('rubber_models.*', 'rubber_brands.name as brand_name');

            if ($request->has('brand_id') && $request->brand_id) {
                $query->where('rubber_models.brand_id', $request->brand_id);
            }

            $models = $query->orderBy('rubber_brands.name')
                ->orderBy('rubber_models.name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $models
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener modelos de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new rubber model
     */
    public function addRubberModel(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'brand_id' => 'required|exists:rubber_brands,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('rubber_models')->where(function ($query) use ($request) {
                        return $query->where('brand_id', $request->brand_id);
                    })
                ],
                'type' => 'required|in:liso,pupo_largo,pupo_corto,antitopspin',
                'speed' => 'nullable|integer|min:1|max:10',
                'spin' => 'nullable|integer|min:1|max:10',
                'control' => 'nullable|integer|min:1|max:10',
                'available_colors' => 'nullable|array',
                'available_colors.*' => 'in:negro,rojo,verde,azul,amarillo,morado,fucsia',
                'available_sponges' => 'nullable|array',
                'available_hardness' => 'nullable|array',
            ]);

            // Set default values for arrays
            $validated['available_colors'] = $validated['available_colors'] ?? ['negro', 'rojo'];
            $validated['available_sponges'] = $validated['available_sponges'] ?? ['1.8', '2.0', '2.1', '2.2'];
            $validated['available_hardness'] = $validated['available_hardness'] ?? ['h42', 'h44', 'h46'];

            $modelId = DB::table('rubber_models')->insertGetId([
                'brand_id' => $validated['brand_id'],
                'name' => $validated['name'],
                'type' => $validated['type'],
                'speed' => $validated['speed'],
                'spin' => $validated['spin'],
                'control' => $validated['control'],
                'available_colors' => json_encode($validated['available_colors']),
                'available_sponges' => json_encode($validated['available_sponges']),
                'available_hardness' => json_encode($validated['available_hardness']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $model = DB::table('rubber_models')
                ->join('rubber_brands', 'rubber_models.brand_id', '=', 'rubber_brands.id')
                ->where('rubber_models.id', $modelId)
                ->select('rubber_models.*', 'rubber_brands.name as brand_name')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Modelo de caucho agregado exitosamente',
                'data' => $model
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar modelo de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update rubber model
     */
    public function updateRubberModel(Request $request, $id): JsonResponse
    {
        try {
            $model = DB::table('rubber_models')->where('id', $id)->first();
            
            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modelo de caucho no encontrado'
                ], 404);
            }

            $validated = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('rubber_models')->where(function ($query) use ($request, $model) {
                        return $query->where('brand_id', $model->brand_id);
                    })->ignore($id)
                ],
                'type' => 'sometimes|required|in:liso,pupo_largo,pupo_corto,antitopspin',
                'speed' => 'nullable|integer|min:1|max:10',
                'spin' => 'nullable|integer|min:1|max:10',
                'control' => 'nullable|integer|min:1|max:10',
                'available_colors' => 'nullable|array',
                'available_colors.*' => 'in:negro,rojo,verde,azul,amarillo,morado,fucsia',
                'available_sponges' => 'nullable|array',
                'available_hardness' => 'nullable|array',
                'is_active' => 'sometimes|boolean',
            ]);

            // Convert arrays to JSON if provided
            if (isset($validated['available_colors'])) {
                $validated['available_colors'] = json_encode($validated['available_colors']);
            }
            if (isset($validated['available_sponges'])) {
                $validated['available_sponges'] = json_encode($validated['available_sponges']);
            }
            if (isset($validated['available_hardness'])) {
                $validated['available_hardness'] = json_encode($validated['available_hardness']);
            }

            $validated['updated_at'] = now();

            DB::table('rubber_models')->where('id', $id)->update($validated);

            $updatedModel = DB::table('rubber_models')
                ->join('rubber_brands', 'rubber_models.brand_id', '=', 'rubber_brands.id')
                ->where('rubber_models.id', $id)
                ->select('rubber_models.*', 'rubber_brands.name as brand_name')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Modelo de caucho actualizado exitosamente',
                'data' => $updatedModel
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar modelo de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete rubber model
     */
    public function deleteRubberModel($id): JsonResponse
    {
        try {
            $model = DB::table('rubber_models')->where('id', $id)->first();
            
            if (!$model) {
                return response()->json([
                    'success' => false,
                    'message' => 'Modelo de caucho no encontrado'
                ], 404);
            }

            // Soft delete by setting is_active to false
            DB::table('rubber_models')->where('id', $id)->update([
                'is_active' => false,
                'updated_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Modelo de caucho eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar modelo de caucho',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get racket brands
     */
    public function getRacketBrands(): JsonResponse
    {
        try {
            $brands = DB::table('racket_brands')
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'country']);

            return response()->json([
                'success' => true,
                'data' => $brands
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener marcas de raqueta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add new racket model
     */
    public function addRacketModel(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'brand_id' => 'required|exists:racket_brands,id',
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('racket_models')->where(function ($query) use ($request) {
                        return $query->where('brand_id', $request->brand_id);
                    })
                ],
                'type' => 'nullable|string|max:100',
                'speed' => 'nullable|integer|min:1|max:10',
                'control' => 'nullable|integer|min:1|max:10',
                'weight' => 'nullable|numeric|min:50|max:150',
            ]);

            $modelId = DB::table('racket_models')->insertGetId([
                'brand_id' => $validated['brand_id'],
                'name' => $validated['name'],
                'type' => $validated['type'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'control' => $validated['control'] ?? null,
                'weight' => $validated['weight'] ?? null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $model = DB::table('racket_models')
                ->join('racket_brands', 'racket_models.brand_id', '=', 'racket_brands.id')
                ->where('racket_models.id', $modelId)
                ->select('racket_models.*', 'racket_brands.name as brand_name')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Modelo de raqueta agregado exitosamente',
                'data' => $model
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar modelo de raqueta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}