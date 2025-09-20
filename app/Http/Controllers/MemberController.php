<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Member::with(['club.league', 'user']);

        // Apply filters
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('doc_id', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('club_id') && $request->club_id) {
            $query->where('club_id', $request->club_id);
        }

        if ($request->has('gender') && $request->gender) {
            $query->where('gender', $request->gender);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('province') && $request->province) {
            $query->where('province', $request->province);
        }

        if ($request->has('city') && $request->city) {
            $query->where('city', $request->city);
        }

        if ($request->has('dominant_hand') && $request->dominant_hand) {
            $query->where('dominant_hand', $request->dominant_hand);
        }

        if ($request->has('playing_style') && $request->playing_style) {
            $query->where('playing_style', $request->playing_style);
        }

        $perPage = $request->get('per_page', 15);
        $members = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($members);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Basic information
            'club_id' => 'required|exists:clubs,id',
            'first_name' => 'required|string|min:2|max:255',
            'last_name' => 'required|string|min:2|max:255',
            'doc_id' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'status' => 'nullable|in:active,inactive',
            
            // Location information
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            
            // Playing style information
            'dominant_hand' => 'nullable|in:right,left',
            'playing_side' => 'nullable|in:derecho,zurdo',
            'playing_style' => 'nullable|in:clasico,lapicero',
            
            // Racket information
            'racket_brand' => 'nullable|string|max:100',
            'racket_model' => 'nullable|string|max:100',
            'racket_custom_brand' => 'nullable|string|max:100',
            'racket_custom_model' => 'nullable|string|max:100',
            
            // Drive rubber information
            'drive_rubber_brand' => 'nullable|string|max:100',
            'drive_rubber_model' => 'nullable|string|max:100',
            'drive_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'drive_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'drive_rubber_sponge' => 'nullable|string|max:20',
            'drive_rubber_hardness' => 'nullable|string|max:20',
            'drive_rubber_custom_brand' => 'nullable|string|max:100',
            'drive_rubber_custom_model' => 'nullable|string|max:100',
            
            // Backhand rubber information
            'backhand_rubber_brand' => 'nullable|string|max:100',
            'backhand_rubber_model' => 'nullable|string|max:100',
            'backhand_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'backhand_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'backhand_rubber_sponge' => 'nullable|string|max:20',
            'backhand_rubber_hardness' => 'nullable|string|max:20',
            'backhand_rubber_custom_brand' => 'nullable|string|max:100',
            'backhand_rubber_custom_model' => 'nullable|string|max:100',
            
            // Additional information
            'notes' => 'nullable|string|max:1000',
            'ranking_position' => 'nullable|integer|min:1',
            'ranking_last_updated' => 'nullable|date',
            
            // Photo upload
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('members/photos', $filename, 'public');
            $validated['photo_path'] = $path;
        }

        // Convert birth_date to birthdate for database compatibility
        if (isset($validated['birth_date'])) {
            $validated['birthdate'] = $validated['birth_date'];
            unset($validated['birth_date']);
        }

        // Remove photo from validated data as it's not a database field
        unset($validated['photo']);

        // Set default values
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['country'] = $validated['country'] ?? 'Ecuador';

        $member = Member::create($validated);
        $member->load(['club.league', 'user']);

        return response()->json([
            'message' => 'Miembro creado exitosamente',
            'data' => $member
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member): JsonResponse
    {
        $member->load(['club.league', 'user']);
        return response()->json(['data' => $member]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member): JsonResponse
    {
        $validated = $request->validate([
            // Basic information
            'club_id' => 'sometimes|required|exists:clubs,id',
            'first_name' => 'sometimes|required|string|min:2|max:255',
            'last_name' => 'sometimes|required|string|min:2|max:255',
            'doc_id' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other',
            'status' => 'nullable|in:active,inactive',
            
            // Location information
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            
            // Playing style information
            'dominant_hand' => 'nullable|in:right,left',
            'playing_side' => 'nullable|in:derecho,zurdo',
            'playing_style' => 'nullable|in:clasico,lapicero',
            
            // Racket information
            'racket_brand' => 'nullable|string|max:100',
            'racket_model' => 'nullable|string|max:100',
            'racket_custom_brand' => 'nullable|string|max:100',
            'racket_custom_model' => 'nullable|string|max:100',
            
            // Drive rubber information
            'drive_rubber_brand' => 'nullable|string|max:100',
            'drive_rubber_model' => 'nullable|string|max:100',
            'drive_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'drive_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'drive_rubber_sponge' => 'nullable|string|max:20',
            'drive_rubber_hardness' => 'nullable|string|max:20',
            'drive_rubber_custom_brand' => 'nullable|string|max:100',
            'drive_rubber_custom_model' => 'nullable|string|max:100',
            
            // Backhand rubber information
            'backhand_rubber_brand' => 'nullable|string|max:100',
            'backhand_rubber_model' => 'nullable|string|max:100',
            'backhand_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'backhand_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'backhand_rubber_sponge' => 'nullable|string|max:20',
            'backhand_rubber_hardness' => 'nullable|string|max:20',
            'backhand_rubber_custom_brand' => 'nullable|string|max:100',
            'backhand_rubber_custom_model' => 'nullable|string|max:100',
            
            // Additional information
            'notes' => 'nullable|string|max:1000',
            'ranking_position' => 'nullable|integer|min:1',
            'ranking_last_updated' => 'nullable|date',
            
            // Photo upload
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
                Storage::disk('public')->delete($member->photo_path);
            }
            
            $photo = $request->file('photo');
            $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $path = $photo->storeAs('members/photos', $filename, 'public');
            $validated['photo_path'] = $path;
        }

        // Convert birth_date to birthdate for database compatibility
        if (isset($validated['birth_date'])) {
            $validated['birthdate'] = $validated['birth_date'];
            unset($validated['birth_date']);
        }

        // Remove photo from validated data as it's not a database field
        unset($validated['photo']);

        $member->update($validated);
        $member->load(['club.league', 'user']);

        return response()->json([
            'message' => 'Miembro actualizado exitosamente',
            'data' => $member
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member): JsonResponse
    {
        // Delete photo if exists
        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $member->delete();

        return response()->json([
            'message' => 'Miembro eliminado exitosamente'
        ]);
    }

    /**
     * Get equipment reference data for forms
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

            return response()->json($data);
        } catch (\Exception $e) {
            // Fallback to constants if database tables don't exist yet
            return response()->json([
                'brands' => [
                    'racket' => [],
                    'rubber' => [],
                ],
                'models' => [
                    'racket' => [],
                    'rubber' => [],
                ],
                'locations' => [],
                'tt_clubs' => [],
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
            ]);
        }
    }

    /**
     * Upload member photo
     */
    public function uploadPhoto(Request $request, Member $member): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Delete old photo if exists
        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
        }

        $photo = $request->file('photo');
        $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $path = $photo->storeAs('members/photos', $filename, 'public');

        $member->update(['photo_path' => $path]);

        return response()->json([
            'message' => 'Foto subida exitosamente',
            'photo_url' => Storage::url($path),
            'data' => $member->fresh(['club.league', 'user'])
        ]);
    }

    /**
     * Delete member photo
     */
    public function deletePhoto(Member $member): JsonResponse
    {
        if ($member->photo_path && Storage::disk('public')->exists($member->photo_path)) {
            Storage::disk('public')->delete($member->photo_path);
            $member->update(['photo_path' => null]);

            return response()->json([
                'message' => 'Foto eliminada exitosamente',
                'data' => $member->fresh(['club.league', 'user'])
            ]);
        }

        return response()->json([
            'message' => 'No hay foto para eliminar'
        ], 404);
    }

    /**
     * Get member statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        $query = Member::query();

        // Apply filters if provided
        if ($request->has('club_id') && $request->club_id) {
            $query->where('club_id', $request->club_id);
        }

        if ($request->has('league_id') && $request->league_id) {
            $query->whereHas('club', function ($q) use ($request) {
                $q->where('league_id', $request->league_id);
            });
        }

        $stats = [
            'total_members' => $query->count(),
            'active_members' => $query->where('status', 'active')->count(),
            'inactive_members' => $query->where('status', 'inactive')->count(),
            'male_members' => $query->where('gender', 'male')->count(),
            'female_members' => $query->where('gender', 'female')->count(),
            'by_province' => $query->select('province', DB::raw('count(*) as count'))
                ->whereNotNull('province')
                ->groupBy('province')
                ->orderBy('count', 'desc')
                ->get(),
            'by_playing_style' => $query->select('playing_style', DB::raw('count(*) as count'))
                ->whereNotNull('playing_style')
                ->groupBy('playing_style')
                ->get(),
            'by_dominant_hand' => $query->select('dominant_hand', DB::raw('count(*) as count'))
                ->whereNotNull('dominant_hand')
                ->groupBy('dominant_hand')
                ->get(),
            'average_age' => $query->whereNotNull('birthdate')
                ->selectRaw('AVG(YEAR(CURDATE()) - YEAR(birthdate)) as avg_age')
                ->value('avg_age'),
            'equipment_stats' => [
                'racket_brands' => $query->select('racket_brand', DB::raw('count(*) as count'))
                    ->whereNotNull('racket_brand')
                    ->groupBy('racket_brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'rubber_brands' => $query->select('drive_rubber_brand', DB::raw('count(*) as count'))
                    ->whereNotNull('drive_rubber_brand')
                    ->groupBy('drive_rubber_brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
            ]
        ];

        return response()->json($stats);
    }
}