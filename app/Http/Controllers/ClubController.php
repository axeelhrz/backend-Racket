<?php

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ClubController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Club::with('league');

        // Filter by league
        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by country/province/city
        if ($request->has('country')) {
            $query->where('country', $request->country);
        }
        if ($request->has('province')) {
            $query->where('province', $request->province);
        }
        if ($request->has('city')) {
            $query->where('city', $request->city);
        }

        // Filter by tournament creation capability
        if ($request->has('can_create_tournaments')) {
            $query->where('can_create_tournaments', $request->boolean('can_create_tournaments'));
        }

        // Search by name, city, or club code
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('club_code', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%");
            });
        }

        $clubs = $query->withCount('members')
                      ->orderBy('name')
                      ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $clubs,
            'message' => 'Clubs retrieved successfully',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Basic information
            'league_id' => 'nullable|exists:leagues,id',
            'name' => 'required|string|max:255',
            'ruc' => 'nullable|string|max:20|unique:clubs,ruc',
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_url' => 'nullable|url|max:500',
            'status' => 'nullable|in:active,inactive',
            'description' => 'nullable|string|max:1000',
            'founded_date' => 'nullable|date|before_or_equal:today',
            
            // Club statistics
            'number_of_tables' => 'nullable|integer|min:0|max:50',
            'can_create_tournaments' => 'nullable|boolean',
            
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
            
            // Logo upload
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $filename = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('clubs/logos', $filename, 'public');
            $validated['logo_path'] = $path;
        }

        // Set defaults
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['country'] = $validated['country'] ?? 'Ecuador';
        $validated['can_create_tournaments'] = $validated['can_create_tournaments'] ?? false;

        $club = Club::create($validated);
        $club->load('league');

        return response()->json([
            'data' => $club,
            'message' => 'Club created successfully',
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Club $club): JsonResponse
    {
        $club->load(['league', 'members' => function ($query) {
            $query->orderBy('last_name')->orderBy('first_name');
        }]);

        return response()->json([
            'data' => $club,
            'message' => 'Club retrieved successfully',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Club $club): JsonResponse
    {
        $validated = $request->validate([
            // Basic information
            'league_id' => 'nullable|exists:leagues,id',
            'name' => 'sometimes|required|string|max:255',
            'ruc' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('clubs', 'ruc')->ignore($club->id)
            ],
            'country' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'google_maps_url' => 'nullable|url|max:500',
            'status' => 'nullable|in:active,inactive',
            'description' => 'nullable|string|max:1000',
            'founded_date' => 'nullable|date|before_or_equal:today',
            
            // Club statistics
            'number_of_tables' => 'nullable|integer|min:0|max:50',
            'can_create_tournaments' => 'nullable|boolean',
            
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
            
            // Logo upload
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($club->logo_path && Storage::disk('public')->exists($club->logo_path)) {
                Storage::disk('public')->delete($club->logo_path);
            }
            
            $logo = $request->file('logo');
            $filename = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
            $path = $logo->storeAs('clubs/logos', $filename, 'public');
            $validated['logo_path'] = $path;
        }

        $club->update($validated);
        $club->load('league');

        return response()->json([
            'data' => $club,
            'message' => 'Club updated successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Club $club): JsonResponse
    {
        // Check if club has members
        if ($club->members()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete club with associated members',
                'errors' => ['club' => ['This club has members associated with it']],
            ], 422);
        }

        // Delete logo if exists
        if ($club->logo_path && Storage::disk('public')->exists($club->logo_path)) {
            Storage::disk('public')->delete($club->logo_path);
        }

        $club->delete();

        return response()->json([
            'message' => 'Club deleted successfully',
        ]);
    }

    /**
     * Add a club to a league
     */
    public function addToLeague(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'league_id' => 'required|exists:leagues,id',
        ]);

        $club->update(['league_id' => $request->league_id]);
        $club->load('league');

        return response()->json([
            'data' => $club,
            'message' => 'Club added to league successfully',
        ]);
    }

    /**
     * Remove a club from its current league
     */
    public function removeFromLeague(Club $club): JsonResponse
    {
        $club->update(['league_id' => null]);
        $club->load('league');

        return response()->json([
            'data' => $club,
            'message' => 'Club removed from league successfully',
        ]);
    }

    /**
     * Update club statistics manually
     */
    public function updateStatistics(Club $club): JsonResponse
    {
        $club->updateCategoryCounts();
        $club->refresh();

        return response()->json([
            'data' => $club,
            'message' => 'Club statistics updated successfully',
        ]);
    }

    /**
     * Get club statistics and analytics
     */
    public function getStatistics(Club $club): JsonResponse
    {
        $statistics = [
            'basic_info' => [
                'club_code' => $club->club_code,
                'name' => $club->name,
                'total_members' => $club->total_members,
                'average_ranking' => $club->average_ranking,
                'number_of_tables' => $club->number_of_tables,
                'can_create_tournaments' => $club->can_create_tournaments,
            ],
            'category_distribution' => $club->category_distribution,
            'contact_info' => $club->contact_info,
            'location_info' => $club->location_info,
            'ranking_trend' => $club->getRankingTrend(),
            'ranking_history' => $club->ranking_history ?? [],
            'monthly_stats' => $club->monthly_stats ?? [],
        ];

        return response()->json([
            'data' => $statistics,
            'message' => 'Club statistics retrieved successfully',
        ]);
    }

    /**
     * Upload club logo
     */
    public function uploadLogo(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Delete old logo if exists
        if ($club->logo_path && Storage::disk('public')->exists($club->logo_path)) {
            Storage::disk('public')->delete($club->logo_path);
        }

        $logo = $request->file('logo');
        $filename = time() . '_' . uniqid() . '.' . $logo->getClientOriginalExtension();
        $path = $logo->storeAs('clubs/logos', $filename, 'public');

        $club->update(['logo_path' => $path]);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'logo_url' => Storage::url($path),
            'data' => $club->fresh()
        ]);
    }

    /**
     * Get clubs that can create tournaments
     */
    public function getTournamentCreators(Request $request): JsonResponse
    {
        $query = Club::canCreateTournaments()
                    ->with('league')
                    ->active();

        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        $clubs = $query->orderBy('name')->get();

        return response()->json([
            'data' => $clubs,
            'message' => 'Tournament creator clubs retrieved successfully',
        ]);
    }

    /**
     * Toggle tournament creation permission
     */
    public function toggleTournamentPermission(Club $club): JsonResponse
    {
        $club->update([
            'can_create_tournaments' => !$club->can_create_tournaments
        ]);

        return response()->json([
            'data' => $club->fresh(),
            'message' => 'Tournament permission updated successfully',
        ]);
    }

    /**
     * Get club ranking history
     */
    public function getRankingHistory(Club $club): JsonResponse
    {
        $history = $club->ranking_history ?? [];
        $trend = $club->getRankingTrend();

        return response()->json([
            'data' => [
                'history' => $history,
                'trend' => $trend,
                'current_average' => $club->average_ranking,
            ],
            'message' => 'Ranking history retrieved successfully',
        ]);
    }

    /**
     * Add ranking history entry
     */
    public function addRankingHistory(Request $request, Club $club): JsonResponse
    {
        $request->validate([
            'average_ranking' => 'required|numeric|min:0|max:3000',
            'period' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $club->addRankingHistory(
            $request->average_ranking,
            $request->period
        );

        return response()->json([
            'data' => $club->fresh(),
            'message' => 'Ranking history entry added successfully',
        ]);
    }
}