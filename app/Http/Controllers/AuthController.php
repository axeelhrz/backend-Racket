<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\League;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Register a new user with role-specific information.
     */
    public function register(Request $request): JsonResponse
    {
        // Validación básica común
        $baseRules = [
            'role' => 'required|in:liga,club,miembro',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'required|string|max:20',
            'country' => 'required|string|max:100',
        ];

        // Validación específica por rol
        $roleSpecificRules = $this->getRoleSpecificValidationRules($request->role);
        $rules = array_merge($baseRules, $roleSpecificRules);

        $validatedData = $request->validate($rules);

        try {
            DB::beginTransaction();

            // Crear el usuario base
            $userData = [
                'name' => $this->getUserName($validatedData),
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'phone' => $validatedData['phone'],
                'country' => $validatedData['country'],
            ];

            // Agregar campos específicos del rol
            $userData = array_merge($userData, $this->getRoleSpecificData($validatedData));

            $user = User::create($userData);

            // Crear la entidad correspondiente (League, Club, o Member)
            $this->createRoleEntity($user, $validatedData);

            DB::commit();

            // Cargar relaciones para la respuesta
            $user->load(['parentLeague', 'parentClub', 'leagueEntity', 'clubEntity', 'memberEntity']);

            return response()->json([
                'data' => [
                    'user' => $user,
                    'role_info' => $user->role_info,
                ],
                'message' => 'Usuario registrado exitosamente',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Error al registrar usuario: ' . $e->getMessage(),
                'errors' => ['general' => ['Ocurrió un error durante el registro']],
            ], 500);
        }
    }

    /**
     * Get validation rules specific to each role.
     */
    private function getRoleSpecificValidationRules(string $role): array
    {
        switch ($role) {
            case 'liga':
                return [
                    'league_name' => 'required|string|max:255',
                    'province' => 'required|string|max:100',
                    'logo_path' => 'nullable|string|max:500',
                ];

            case 'club':
                return [
                    'club_name' => 'required|string|max:255',
                    'parent_league_id' => [
                        'required',
                        'integer',
                        Rule::exists('leagues', 'id')->where('status', 'active')
                    ],
                    'city' => 'required|string|max:100',
                    'address' => 'required|string|max:500',
                    'logo_path' => 'nullable|string|max:500',
                    
                    // Additional club fields
                    'ruc' => 'nullable|string|max:20|unique:clubs,ruc',
                    'province' => 'nullable|string|max:100',
                    'latitude' => 'nullable|numeric|between:-90,90',
                    'longitude' => 'nullable|numeric|between:-180,180',
                    'google_maps_url' => 'nullable|url|max:500',
                    'description' => 'nullable|string|max:1000',
                    'founded_date' => 'nullable|date|before_or_equal:today',
                    
                    // Representative information
                    'representative_name' => 'nullable|string|max:255',
                    'representative_phone' => 'nullable|string|max:20',
                    'representative_email' => 'nullable|email|max:255',
                    
                    // Administrator 1
                    'admin1_name' => 'nullable|string|max:255',
                    'admin1_phone' => 'nullable|string|max:20',
                    'admin1_email' => 'nullable|email|max:255',
                    
                    // Administrator 2
                    'admin2_name' => 'nullable|string|max:255',
                    'admin2_phone' => 'nullable|string|max:20',
                    'admin2_email' => 'nullable|email|max:255',
                    
                    // Administrator 3
                    'admin3_name' => 'nullable|string|max:255',
                    'admin3_phone' => 'nullable|string|max:20',
                    'admin3_email' => 'nullable|email|max:255',
                ];

            case 'miembro':
                return [
                    'full_name' => 'required|string|max:255',
                    'parent_club_id' => [
                        'required',
                        'integer',
                        Rule::exists('clubs', 'id')->where('status', 'active')
                    ],
                    'birth_date' => 'required|date|before:today',
                    'gender' => 'required|in:masculino,femenino',
                    'rubber_type' => 'required|in:liso,pupo,ambos',
                    'ranking' => 'nullable|string|max:100',
                    'photo_path' => 'nullable|string|max:500',
                ];

            default:
                return [];
        }
    }

    /**
     * Get role-specific data for user creation.
     */
    private function getRoleSpecificData(array $validatedData): array
    {
        $role = $validatedData['role'];
        $data = [];

        switch ($role) {
            case 'liga':
                $data = [
                    'league_name' => $validatedData['league_name'],
                    'province' => $validatedData['province'],
                    'logo_path' => $validatedData['logo_path'] ?? null,
                ];
                break;

            case 'club':
                $data = [
                    'club_name' => $validatedData['club_name'],
                    'parent_league_id' => $validatedData['parent_league_id'],
                    'city' => $validatedData['city'],
                    'address' => $validatedData['address'],
                    'logo_path' => $validatedData['logo_path'] ?? null,
                ];
                break;

            case 'miembro':
                $data = [
                    'full_name' => $validatedData['full_name'],
                    'parent_club_id' => $validatedData['parent_club_id'],
                    'birth_date' => $validatedData['birth_date'],
                    'gender' => $validatedData['gender'],
                    'rubber_type' => $validatedData['rubber_type'],
                    'ranking' => $validatedData['ranking'] ?? null,
                    'photo_path' => $validatedData['photo_path'] ?? null,
                ];
                break;
        }

        return $data;
    }

    /**
     * Get the user name based on role.
     */
    private function getUserName(array $validatedData): string
    {
        switch ($validatedData['role']) {
            case 'liga':
                return $validatedData['league_name'];
            case 'club':
                return $validatedData['club_name'];
            case 'miembro':
                return $validatedData['full_name'];
            default:
                return $validatedData['email'];
        }
    }

    /**
     * Create the corresponding entity based on user role.
     */
    private function createRoleEntity(User $user, array $validatedData): void
    {
        switch ($user->role) {
            case 'liga':
                $league = League::create([
                    'user_id' => $user->id,
                    'name' => $validatedData['league_name'],
                    'region' => $validatedData['province'],
                    'province' => $validatedData['province'],
                    'logo_path' => $validatedData['logo_path'] ?? null,
                    'status' => 'active',
                ]);
                $user->update([
                    'roleable_id' => $league->id,
                    'roleable_type' => League::class,
                ]);
                break;

            case 'club':
                $clubData = [
                    'user_id' => $user->id,
                    'league_id' => $validatedData['parent_league_id'],
                    'name' => $validatedData['club_name'],
                    'city' => $validatedData['city'],
                    'address' => $validatedData['address'],
                    'logo_path' => $validatedData['logo_path'] ?? null,
                    'status' => 'active',
                    'country' => $user->country,
                    
                    // Additional club fields with explicit defaults
                    'ruc' => $validatedData['ruc'] ?? null,
                    'province' => $validatedData['province'] ?? null,
                    'latitude' => $validatedData['latitude'] ?? null,
                    'longitude' => $validatedData['longitude'] ?? null,
                    'google_maps_url' => $validatedData['google_maps_url'] ?? null,
                    'description' => $validatedData['description'] ?? null,
                    'founded_date' => $validatedData['founded_date'] ?? null,
                    
                    // Critical fields that must have values
                    'total_members' => 0,
                    
                    // Representative information
                    'representative_name' => $validatedData['representative_name'] ?? null,
                    'representative_phone' => $validatedData['representative_phone'] ?? null,
                    'representative_email' => $validatedData['representative_email'] ?? null,
                    
                    // Administrator 1
                    'admin1_name' => $validatedData['admin1_name'] ?? null,
                    'admin1_phone' => $validatedData['admin1_phone'] ?? null,
                    'admin1_email' => $validatedData['admin1_email'] ?? null,
                    
                    // Administrator 2
                    'admin2_name' => $validatedData['admin2_name'] ?? null,
                    'admin2_phone' => $validatedData['admin2_phone'] ?? null,
                    'admin2_email' => $validatedData['admin2_email'] ?? null,
                    
                    // Administrator 3
                    'admin3_name' => $validatedData['admin3_name'] ?? null,
                    'admin3_phone' => $validatedData['admin3_phone'] ?? null,
                    'admin3_email' => $validatedData['admin3_email'] ?? null,
                ];
                
                // Ensure no null values for critical fields
                $clubData['total_members'] = $clubData['total_members'] ?? 0;
                
                $club = Club::createSafely($clubData);
                $user->update([
                    'roleable_id' => $club->id,
                    'roleable_type' => Club::class,
                ]);
                break;

            case 'miembro':
                $member = \App\Models\Member::create([
                    'user_id' => $user->id,
                    'club_id' => $validatedData['parent_club_id'],
                    'first_name' => explode(' ', $validatedData['full_name'])[0] ?? '',
                    'last_name' => implode(' ', array_slice(explode(' ', $validatedData['full_name']), 1)) ?: '',
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'birthdate' => $validatedData['birth_date'],
                    'gender' => $validatedData['gender'] === 'masculino' ? 'male' : 'female',
                    'rubber_type' => $validatedData['rubber_type'],
                    'ranking' => $validatedData['ranking'] ?? null,
                    'photo_path' => $validatedData['photo_path'] ?? null,
                    'status' => 'active',
                ]);
                $user->update([
                    'roleable_id' => $member->id,
                    'roleable_type' => \App\Models\Member::class,
                ]);
                break;
        }
    }

    /**
     * Login user.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $user = Auth::user();
        $user->load(['parentLeague', 'parentClub', 'leagueEntity', 'clubEntity', 'memberEntity']);

        // Delete any existing tokens for this user to prevent token accumulation
        $user->tokens()->delete();

        // Create a new token for the user with a specific name and abilities
        $token = $user->createToken('auth-token', ['*'], now()->addHours(24))->plainTextToken;

        return response()->json([
            'data' => [
                'user' => $user,
                'token' => $token,
                'role_info' => $user->role_info,
            ],
            'message' => 'Inicio de sesión exitoso',
        ]);
    }

    /**
     * Logout user.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Delete all tokens for the user
            if ($request->user()) {
                $request->user()->tokens()->delete();
            }
            
            // Logout from session
            Auth::logout();

            // Invalidate session
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Cierre de sesión exitoso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error durante el cierre de sesión',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user with role information.
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado',
                ], 401);
            }

            $user->load(['parentLeague', 'parentClub', 'leagueEntity', 'clubEntity', 'memberEntity']);

            return response()->json([
                'data' => [
                    'user' => $user,
                    'role_info' => $user->role_info,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener información del usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available leagues for club registration.
     */
    public function getAvailableLeagues(): JsonResponse
    {
        try {
            $leagues = League::where('status', 'active')
                ->select('id', 'name', 'region', 'province')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $leagues,
                'message' => 'Ligas disponibles obtenidas exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener ligas: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Get available clubs for member registration.
     */
    public function getAvailableClubs(Request $request): JsonResponse
    {
        try {
            $query = Club::with('league:id,name')
                ->where('status', 'active')
                ->select('id', 'name', 'city', 'province', 'league_id')
                ->orderBy('name');

            // Filtrar por liga si se proporciona
            if ($request->has('league_id') && $request->league_id) {
                $query->where('league_id', $request->league_id);
            }

            $clubs = $query->get();

            return response()->json([
                'data' => $clubs,
                'message' => 'Clubes disponibles obtenidos exitosamente',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener clubes: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }
}