<?php

namespace App\Http\Controllers;

use App\Models\QuickRegistration;
use App\Models\CustomField;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class QuickRegistrationController extends Controller
{
    /**
     * Store a new quick registration.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            Log::info('Quick registration request received', [
                'data' => $request->except(['photo']),
                'has_photo' => $request->hasFile('photo')
            ]);

            // Validate the request
            $validatedData = $request->validate([
                // Personal information
                'first_name' => 'required|string|max:255',
                'second_name' => 'nullable|string|max:255',
                'last_name' => 'required|string|max:255',
                'second_last_name' => 'required|string|max:255',
                'doc_id' => 'nullable|string|max:20',
                'email' => 'required|email|unique:quick_registrations,email',
                'phone' => 'required|string|max:20',
                'birth_date' => 'nullable|date',
                'gender' => 'nullable|in:masculino,femenino',
                
                // Location
                'country' => 'nullable|string|max:255',
                'province' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                
                // League and club
                'league' => 'nullable|string|max:255',
                'league_custom' => 'nullable|string|max:255',
                'club_name' => 'nullable|string|max:255',
                'club_name_custom' => 'nullable|string|max:255',
                'club_role' => 'nullable|in:ninguno,administrador,dueño',
                'ranking' => 'nullable|string|max:50',
                
                // Playing style
                'playing_side' => 'nullable|in:derecho,zurdo',
                'playing_style' => 'nullable|in:clasico,lapicero',
                
                // Racket
                'racket_brand' => 'nullable|string|max:255',
                'racket_model' => 'nullable|string|max:255',
                'custom_racket_brand' => 'nullable|string|max:255',
                'custom_racket_model' => 'nullable|string|max:255',
                
                // Drive rubber
                'drive_rubber_brand' => 'nullable|string|max:255',
                'drive_rubber_model' => 'nullable|string|max:255',
                'drive_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopsping',
                'drive_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
                'drive_rubber_sponge' => 'nullable|string|max:10',
                'drive_rubber_hardness' => 'nullable|string|max:50',
                'custom_drive_rubber_brand' => 'nullable|string|max:255',
                'custom_drive_rubber_model' => 'nullable|string|max:255',
                'custom_drive_rubber_hardness' => 'nullable|string|max:50',
                
                // Backhand rubber
                'backhand_rubber_brand' => 'nullable|string|max:255',
                'backhand_rubber_model' => 'nullable|string|max:255',
                'backhand_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopsping',
                'backhand_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
                'backhand_rubber_sponge' => 'nullable|string|max:10',
                'backhand_rubber_hardness' => 'nullable|string|max:50',
                'custom_backhand_rubber_brand' => 'nullable|string|max:255',
                'custom_backhand_rubber_model' => 'nullable|string|max:255',
                'custom_backhand_rubber_hardness' => 'nullable|string|max:50',
                
                // Additional info
                'notes' => 'nullable|string|max:1000',
                
                // Photo
                'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            ]);

            Log::info('Validation passed', ['validated_data' => array_keys($validatedData)]);

            // Handle custom fields
            if ($request->filled('league_custom')) {
                $validatedData['league'] = $request->league_custom;
                unset($validatedData['league_custom']);
            }

            if ($request->filled('club_name_custom')) {
                $validatedData['club_name'] = $request->club_name_custom;
                unset($validatedData['club_name_custom']);
            }

            // Handle custom racket fields
            if ($request->filled('custom_racket_brand')) {
                $validatedData['racket_brand'] = $request->custom_racket_brand;
                unset($validatedData['custom_racket_brand']);
            }

            if ($request->filled('custom_racket_model')) {
                $validatedData['racket_model'] = $request->custom_racket_model;
                unset($validatedData['custom_racket_model']);
            }

            // Handle custom drive rubber fields
            if ($request->filled('custom_drive_rubber_brand')) {
                $validatedData['drive_rubber_brand'] = $request->custom_drive_rubber_brand;
                unset($validatedData['custom_drive_rubber_brand']);
            }

            if ($request->filled('custom_drive_rubber_model')) {
                $validatedData['drive_rubber_model'] = $request->custom_drive_rubber_model;
                unset($validatedData['custom_drive_rubber_model']);
            }

            if ($request->filled('custom_drive_rubber_hardness')) {
                $validatedData['drive_rubber_hardness'] = $request->custom_drive_rubber_hardness;
                unset($validatedData['custom_drive_rubber_hardness']);
            }

            // Handle custom backhand rubber fields
            if ($request->filled('custom_backhand_rubber_brand')) {
                $validatedData['backhand_rubber_brand'] = $request->custom_backhand_rubber_brand;
                unset($validatedData['custom_backhand_rubber_brand']);
            }

            if ($request->filled('custom_backhand_rubber_model')) {
                $validatedData['backhand_rubber_model'] = $request->custom_backhand_rubber_model;
                unset($validatedData['custom_backhand_rubber_model']);
            }

            if ($request->filled('custom_backhand_rubber_hardness')) {
                $validatedData['backhand_rubber_hardness'] = $request->custom_backhand_rubber_hardness;
                unset($validatedData['custom_backhand_rubber_hardness']);
            }

            // Generate unique registration code
            do {
                $registrationCode = 'CENSO-' . strtoupper(substr(md5(uniqid()), 0, 8));
            } while (QuickRegistration::where('registration_code', $registrationCode)->exists());

            $validatedData['registration_code'] = $registrationCode;

            Log::info('Generated registration code', ['code' => $registrationCode]);

            // Handle photo upload
            if ($request->hasFile('photo')) {
                try {
                    $photo = $request->file('photo');
                    $photoPath = $photo->store('quick_registrations', 'public');
                    $validatedData['photo_path'] = $photoPath;
                    Log::info('Photo uploaded successfully', ['path' => $photoPath]);
                } catch (\Exception $e) {
                    Log::error('Photo upload failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Error al subir la foto: ' . $e->getMessage()
                    ], 500);
                }
            }

            // Create the registration
            $registration = QuickRegistration::create($validatedData);

            Log::info('Registration created successfully', ['id' => $registration->id]);

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso',
                'registration_code' => $registrationCode,
                'data' => $registration
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error', ['errors' => $e->errors()]);
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in quick registration', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all quick registrations.
     */
    public function index(): JsonResponse
    {
        try {
            $registrations = QuickRegistration::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $registrations
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registros'
            ], 500);
        }
    }

    /**
     * Get a specific registration by code.
     */
    public function show(string $code): JsonResponse
    {
        try {
            $registration = QuickRegistration::where('registration_code', $code)->first();
            
            if (!$registration) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $registration
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener registro'
            ], 500);
        }
    }

    /**
     * NUEVO: Agregar campo personalizado inmediatamente a la base de datos
     * ACTUALIZADO: Incluir club y league
     */
    public function addCustomField(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'field_type' => 'required|string|in:brand,racket_model,drive_rubber_model,backhand_rubber_model,drive_rubber_hardness,backhand_rubber_hardness,club,league',
                'value' => 'required|string|min:2|max:255'
            ]);

            $fieldType = $request->field_type;
            $value = trim($request->value);

            // Verificar si ya existe
            if (CustomField::exists($fieldType, $value)) {
                // Ya existe, solo incrementar contador
                $field = CustomField::addOrUpdate($fieldType, $value);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Campo ya existía, contador actualizado',
                    'field' => $field,
                    'was_new' => false
                ]);
            }

            // Agregar nuevo campo
            $field = CustomField::addOrUpdate($fieldType, $value);

            return response()->json([
                'success' => true,
                'message' => 'Campo agregado exitosamente',
                'field' => $field,
                'was_new' => true
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error adding custom field: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar campo personalizado'
            ], 500);
        }
    }

    /**
     * NUEVO: Obtener opciones dinámicas para un tipo de campo
     * ACTUALIZADO: Incluir club y league
     */
    public function getFieldOptions(string $fieldType): JsonResponse
    {
        try {
            if (!in_array($fieldType, ['brand', 'racket_model', 'drive_rubber_model', 'backhand_rubber_model', 'drive_rubber_hardness', 'backhand_rubber_hardness', 'club', 'league'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de campo no válido'
                ], 400);
            }

            // Obtener opciones predefinidas
            $predefinedOptions = $this->getPredefinedOptions($fieldType);
            
            // Obtener opciones personalizadas de la base de datos
            $customOptions = CustomField::getValuesForType($fieldType);
            
            // Combinar y eliminar duplicados
            $allOptions = array_unique(array_merge($predefinedOptions, $customOptions));
            sort($allOptions);

            return response()->json([
                'success' => true,
                'options' => array_values($allOptions),
                'predefined_count' => count($predefinedOptions),
                'custom_count' => count($customOptions),
                'total_count' => count($allOptions)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting field options: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener opciones'
            ], 500);
        }
    }

    /**
     * Validar campos personalizados contra la base de datos
     * ACTUALIZADO: Incluir club y league
     */
    public function validateCustomField(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'field_type' => 'required|string|in:brand,racket_model,drive_rubber_model,backhand_rubber_model,drive_rubber_hardness,backhand_rubber_hardness,club,league',
                'value' => 'required|string|min:2|max:255'
            ]);

            $fieldType = $request->field_type;
            $value = trim($request->value);
            $normalizedValue = strtolower($value);

            // Buscar en tabla custom_fields primero
            $customFieldMatch = CustomField::where('field_type', $fieldType)
                ->where('normalized_value', $normalizedValue)
                ->first();

            if ($customFieldMatch) {
                return response()->json([
                    'is_duplicate' => true,
                    'suggested_value' => $customFieldMatch->value,
                    'message' => "Ya existe '{$customFieldMatch->value}' en la base de datos",
                    'match_type' => 'exact',
                    'source' => 'custom_fields'
                ]);
            }

            // Buscar en registros existentes
            $searchFields = $this->getSearchFieldsForType($fieldType);
            $exactMatches = $this->findExactMatches($searchFields, $normalizedValue);
            
            if (!empty($exactMatches)) {
                return response()->json([
                    'is_duplicate' => true,
                    'suggested_value' => $exactMatches[0],
                    'message' => "Ya existe '{$exactMatches[0]}' en registros",
                    'match_type' => 'exact',
                    'source' => 'registrations'
                ]);
            }

            // Buscar coincidencias parciales en custom_fields
            $partialCustomMatches = CustomField::findSimilar($fieldType, $value);
            
            if (!empty($partialCustomMatches)) {
                return response()->json([
                    'is_duplicate' => false,
                    'suggested_value' => $partialCustomMatches[0],
                    'message' => "¿Quisiste decir '{$partialCustomMatches[0]}'?",
                    'match_type' => 'partial',
                    'source' => 'custom_fields',
                    'all_suggestions' => array_slice($partialCustomMatches, 0, 5)
                ]);
            }

            // Buscar coincidencias parciales en registros
            $partialMatches = $this->findPartialMatches($searchFields, $normalizedValue);
            
            if (!empty($partialMatches)) {
                return response()->json([
                    'is_duplicate' => false,
                    'suggested_value' => $partialMatches[0],
                    'message' => "¿Quisiste decir '{$partialMatches[0]}'?",
                    'match_type' => 'partial',
                    'source' => 'registrations',
                    'all_suggestions' => array_slice($partialMatches, 0, 5)
                ]);
            }

            // No se encontraron coincidencias
            return response()->json([
                'is_duplicate' => false,
                'suggested_value' => $value,
                'message' => 'Valor único, se puede agregar',
                'match_type' => null,
                'source' => null
            ]);

        } catch (\Exception $e) {
            Log::error('Error validating custom field: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al validar campo'
            ], 500);
        }
    }

    /**
     * Obtener sugerencias para un tipo de campo
     * ACTUALIZADO: Incluir club y league
     */
    public function getFieldSuggestions(Request $request, string $fieldType): JsonResponse
    {
        try {
            if (!in_array($fieldType, ['brand', 'racket_model', 'drive_rubber_model', 'backhand_rubber_model', 'drive_rubber_hardness', 'backhand_rubber_hardness', 'club', 'league'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tipo de campo no válido'
                ], 400);
            }

            $query = $request->query('query', '');
            
            // Obtener sugerencias de custom_fields
            $customSuggestions = [];
            if (!empty($query)) {
                $customSuggestions = CustomField::findSimilar($fieldType, $query);
            } else {
                $customSuggestions = CustomField::getValuesForType($fieldType);
            }
            
            // Obtener sugerencias de registros existentes
            $searchFields = $this->getSearchFieldsForType($fieldType);
            $registrationSuggestions = $this->getAllSuggestions($searchFields, $query);
            
            // Combinar y eliminar duplicados
            $allSuggestions = array_unique(array_merge($customSuggestions, $registrationSuggestions));
            sort($allSuggestions);
            
            return response()->json([
                'suggestions' => array_values($allSuggestions),
                'total_count' => count($allSuggestions),
                'custom_count' => count($customSuggestions),
                'registration_count' => count($registrationSuggestions)
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting field suggestions: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sugerencias'
            ], 500);
        }
    }

    /**
     * Obtener opciones predefinidas para un tipo de campo
     * ACTUALIZADO: Incluir club y league
     */
    private function getPredefinedOptions(string $fieldType): array
    {
        switch ($fieldType) {
            case 'brand':
                return [
                    'Andro', 'Avalox', 'Butterfly', 'Cornilleau', 'DHS', 'Donic', 'Double Fish', 
                    'Dr. Neubauer', 'Friendship', 'Gewo', 'Hurricane', 'Joola', 'Killerspin', 
                    'Nittaku', 'Palio', 'Sanwei', 'Saviga', 'Stiga', 'Tibhar', 'TSP', 
                    'Victas', 'Xiom', 'Yinhe', 'Yasaka'
                ];
                
            case 'racket_model':
                return [
                    'Allround Classic', 'Carbotec 7000', 'Clipper Wood', 'Defplay Senso', 
                    'Evolution MX-P', 'Harimoto ALC', 'Hurricane Long 5', 'Innerforce Layer ALC', 
                    'Kong Linghui', 'Ligna CO', 'Lin Gaoyuan ALC', 'Ma Lin Extra Offensive', 
                    'Ma Long Carbon', 'Offensive Classic', 'Ovtcharov Innerforce ALC', 
                    'Persson Powerplay', 'Power G7', 'Primorac Carbon', 'Quantum X Pro', 
                    'Stratus PowerWood', 'Timo Boll ALC', 'Viscaria', 'Waldner Offensive', 
                    'Zhang Jike Super ZLC'
                ];
                
            case 'drive_rubber_model':
                return [
                    'Acuda Blue P1', 'Acuda Blue P3', 'Battle 2', 'Big Dipper', 'Cross 729', 
                    'Dignics 05', 'Dignics 09C', 'Evolution MX-P', 'Evolution MX-S', 
                    'Focus 3', 'Friendship 802-40', 'Hexer HD', 'Hexer Powergrip', 
                    'Hurricane 3', 'Hurricane 8', 'Omega VII Euro', 'Omega VII Pro', 
                    'Rakza 7', 'Rakza 9', 'Rhyzer 48', 'Rhyzer 50', 'Rozena', 
                    'Skyline 3', 'Target Pro GT-H47', 'Target Pro GT-M43', 'Tenergy 05', 
                    'Tenergy 64', 'Tenergy 80', 'V > 15 Extra', 'V > 20 Double Extra'
                ];
                
            case 'backhand_rubber_model':
                return [
                    'Acuda Blue P1', 'Acuda Blue P2', 'Battle 2 Back', 'Cross 729-2', 
                    'Dignics 05', 'Dignics 80', 'Evolution EL-P', 'Evolution MX-P', 
                    'Focus Snipe', 'Friendship 729 Super FX', 'Grass D.TecS', 'Hexer Pips+', 
                    'Hexer Powergrip', 'Hurricane 3 Neo', 'Omega VII Euro', 'Omega VII Pro', 
                    'Plaxon 450', 'Rakza 7 Soft', 'Rakza X', 'Rhyzer 43', 'Rhyzer 48', 
                    'Rozena', 'Target Pro GT-M40', 'Target Pro GT-S43', 'Tenergy 05', 
                    'Tenergy 64', 'Tenergy 80', 'V > 15 Extra', 'V > 20 Double Extra'
                ];
                
            case 'drive_rubber_hardness':
            case 'backhand_rubber_hardness':
                return [
                    'Extra Hard', 'h35', 'h37', 'h39', 'h40', 'h42', 'h44', 'h46', 'h48', 'h50', 
                    'h52', 'h54', 'Hard', 'Medium', 'N/A', 'Soft'
                ];

            case 'club':
                return [
                    'Amazonas Ping Pong', 'Ambato', 'Azuay TT', 'BackSping', 'Billy Team', 
                    'Bolívar TT', 'Buena Fe', 'Cañar TT Club', 'Carchi Racket Club', 
                    'Chimborazo Ping', 'Club Deportivo Loja', 'Costa TT Club', 'Cotopaxi TT', 
                    'Cuenca', 'El Oro Table Tennis', 'Esmeraldas TT', 'Fede - Manabi', 
                    'Fede Guayas', 'Fede Santa Elena', 'Galapagos', 'Guayaquil City', 
                    'Imbabura Racket', 'Independiente', 'Los Ríos TT', 'Manabí Spin', 
                    'Oriente TT', 'Ping Pong Rick', 'Ping Pro', 'PPH', 'Primorac', 'Quito', 
                    'Selva TT', 'Sierra Racket', 'Spin Factor', 'Spin Zone', 'TM - Manta', 
                    'TT Quevedo', 'Tungurahua Ping Pong', 'Uartes'
                ];

            case 'league':
                return [
                    '593LATM'
                ];
                
            default:
                return [];
        }
    }

    /**
     * Obtener campos de búsqueda según el tipo
     * ACTUALIZADO: Incluir club y league
     */
    private function getSearchFieldsForType(string $fieldType): array
    {
        switch ($fieldType) {
            case 'brand':
                // MARCAS COMPARTIDAS: buscar en todos los campos de marca
                return [
                    'racket_brand',
                    'drive_rubber_brand', 
                    'backhand_rubber_brand'
                ];
                
            case 'racket_model':
                // MODELOS INDEPENDIENTES: solo modelos de raqueta
                return [
                    'racket_model'
                ];
                
            case 'drive_rubber_model':
                // MODELOS INDEPENDIENTES: solo modelos de caucho drive
                return [
                    'drive_rubber_model'
                ];
                
            case 'backhand_rubber_model':
                // MODELOS INDEPENDIENTES: solo modelos de caucho back
                return [
                    'backhand_rubber_model'
                ];
                
            case 'drive_rubber_hardness':
                return [
                    'drive_rubber_hardness'
                ];
                
            case 'backhand_rubber_hardness':
                return [
                    'backhand_rubber_hardness'
                ];

            case 'club':
                return [
                    'club_name'
                ];

            case 'league':
                return [
                    'league'
                ];
                
            default:
                return [];
        }
    }

    /**
     * Buscar coincidencias exactas
     */
    private function findExactMatches(array $searchFields, string $normalizedValue): array
    {
        $matches = [];
        
        foreach ($searchFields as $field) {
            $results = DB::table('quick_registrations')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->whereRaw("LOWER(TRIM({$field})) = ?", [$normalizedValue])
                ->pluck($field)
                ->unique()
                ->values()
                ->toArray();
                
            $matches = array_merge($matches, $results);
        }
        
        return array_unique($matches);
    }

    /**
     * Buscar coincidencias parciales
     */
    private function findPartialMatches(array $searchFields, string $normalizedValue): array
    {
        $matches = [];
        
        foreach ($searchFields as $field) {
            $results = DB::table('quick_registrations')
                ->whereNotNull($field)
                ->where($field, '!=', '')
                ->whereRaw("LOWER(TRIM({$field})) LIKE ?", ["%{$normalizedValue}%"])
                ->whereRaw("LOWER(TRIM({$field})) != ?", [$normalizedValue]) // Excluir coincidencias exactas
                ->pluck($field)
                ->unique()
                ->values()
                ->toArray();
                
            $matches = array_merge($matches, $results);
        }
        
        return array_unique($matches);
    }

    /**
     * Obtener todas las sugerencias para un tipo de campo
     */
    private function getAllSuggestions(array $searchFields, string $query = ''): array
    {
        $suggestions = [];
        
        foreach ($searchFields as $field) {
            $queryBuilder = DB::table('quick_registrations')
                ->whereNotNull($field)
                ->where($field, '!=', '');
                
            if (!empty($query)) {
                $queryBuilder->whereRaw("LOWER(TRIM({$field})) LIKE ?", ["%".strtolower(trim($query))."%"]);
            }
            
            $results = $queryBuilder
                ->pluck($field)
                ->unique()
                ->values()
                ->toArray();
                
            $suggestions = array_merge($suggestions, $results);
        }
        
        // Eliminar duplicados y ordenar
        $suggestions = array_unique($suggestions);
        sort($suggestions);
        
        return array_values($suggestions);
    }

    /**
     * Get waiting room status by email.
     */
    public function getWaitingRoomStatus(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $registration = QuickRegistration::where('email', $request->email)->first();
            
            if (!$registration) {
                return response()->json([
                    'found' => false,
                    'message' => 'No se encontró ningún registro con este email'
                ], 404);
            }
            
            // Prepare the response data with all the fields the frontend expects
            $responseData = [
                'id' => $registration->id,
                'registration_code' => $registration->registration_code,
                'full_name' => $registration->full_name,
                'status' => $registration->status ?? 'pending',
                'status_label' => $this->getStatusLabel($registration->status ?? 'pending'),
                'status_color' => $this->getStatusColor($registration->status ?? 'pending'),
                'days_waiting' => $registration->days_waiting,
                'club_summary' => $registration->club_name ?? 'Sin club especificado',
                'location_summary' => $registration->location_summary,
                'playing_side_label' => $this->getPlayingSideLabel($registration->playing_side),
                'playing_style_label' => $this->getPlayingStyleLabel($registration->playing_style),
                'racket_summary' => $registration->racket_summary,
                'drive_rubber_summary' => $registration->drive_rubber_summary,
                'backhand_rubber_summary' => $registration->backhand_rubber_summary,
                'created_at' => $registration->created_at,
                'contacted_at' => $registration->contacted_at ?? null,
                'approved_at' => $registration->approved_at ?? null,
            ];
            
            return response()->json([
                'found' => true,
                'data' => $responseData
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'found' => false,
                'message' => 'Email inválido',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error getting waiting room status: ' . $e->getMessage());
            return response()->json([
                'found' => false,
                'message' => 'Error al buscar el registro'
            ], 500);
        }
    }

    /**
     * Get status label for display
     */
    private function getStatusLabel(string $status): string
    {
        switch ($status) {
            case 'pending':
                return 'Pendiente de Revisión';
            case 'contacted':
                return 'Contactado';
            case 'approved':
                return 'Aprobado';
            case 'rejected':
                return 'Rechazado';
            default:
                return 'Pendiente';
        }
    }

    /**
     * Get status color for display
     */
    private function getStatusColor(string $status): string
    {
        switch ($status) {
            case 'pending':
                return '#F59E0B';
            case 'contacted':
                return '#3B82F6';
            case 'approved':
                return '#10B981';
            case 'rejected':
                return '#EF4444';
            default:
                return '#6B7280';
        }
    }

    /**
     * Get playing side label
     */
    private function getPlayingSideLabel(?string $playingSide): ?string
    {
        if (!$playingSide) return null;
        
        switch ($playingSide) {
            case 'derecho':
                return 'Derecho';
            case 'zurdo':
                return 'Zurdo';
            default:
                return $playingSide;
        }
    }

    /**
     * Get playing style label
     */
    private function getPlayingStyleLabel(?string $playingStyle): ?string
    {
        if (!$playingStyle) return null;
        
        switch ($playingStyle) {
            case 'clasico':
                return 'Clásico';
            case 'lapicero':
                return 'Lapicero';
            default:
                return $playingStyle;
        }
    }
}