<?php

namespace App\Http\Controllers;

use App\Models\QuickRegistration;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuickRegistrationController extends Controller
{
    /**
     * Store a new quick registration.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            // Información personal básica
            'first_name' => 'required|string|min:2|max:255',
            'last_name' => 'required|string|min:2|max:255',
            'doc_id' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:quick_registrations,email',
            'phone' => 'required|string|min:10|max:20',
            'birth_date' => 'nullable|date|before:today',
            'gender' => 'nullable|in:masculino,femenino',
            
            // Ubicación
            'country' => 'nullable|string|max:100',
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            
            // Club y federación
            'club_name' => 'nullable|string|max:255',
            'federation' => 'nullable|string|max:255',
            
            // Estilo de juego
            'playing_side' => 'nullable|in:derecho,zurdo',
            'playing_style' => 'nullable|in:clasico,lapicero',
            
            // Raqueta - palo
            'racket_brand' => 'nullable|string|max:100',
            'racket_model' => 'nullable|string|max:100',
            'racket_custom_brand' => 'nullable|string|max:100',
            'racket_custom_model' => 'nullable|string|max:100',
            
            // Caucho del drive
            'drive_rubber_brand' => 'nullable|string|max:100',
            'drive_rubber_model' => 'nullable|string|max:100',
            'drive_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'drive_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'drive_rubber_sponge' => 'nullable|string|max:20',
            'drive_rubber_hardness' => 'nullable|string|max:20',
            'drive_rubber_custom_brand' => 'nullable|string|max:100',
            'drive_rubber_custom_model' => 'nullable|string|max:100',
            
            // Caucho del back
            'backhand_rubber_brand' => 'nullable|string|max:100',
            'backhand_rubber_model' => 'nullable|string|max:100',
            'backhand_rubber_type' => 'nullable|in:liso,pupo_largo,pupo_corto,antitopspin',
            'backhand_rubber_color' => 'nullable|in:negro,rojo,verde,azul,amarillo,morado,fucsia',
            'backhand_rubber_sponge' => 'nullable|string|max:20',
            'backhand_rubber_hardness' => 'nullable|string|max:20',
            'backhand_rubber_custom_brand' => 'nullable|string|max:100',
            'backhand_rubber_custom_model' => 'nullable|string|max:100',
            
            // Información adicional
            'notes' => 'nullable|string|max:1000',
            
            // Photo upload - made optional and with better validation
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        // Handle photo upload with better error handling
        if ($request->hasFile('photo')) {
            try {
                $photo = $request->file('photo');
                
                // Validate the file is actually an image
                if (!$photo->isValid()) {
                    return response()->json([
                        'message' => 'El archivo de foto no es válido.',
                        'errors' => ['photo' => ['El archivo de foto está corrupto o no es válido.']]
                    ], 422);
                }
                
                // Create directory if it doesn't exist
                $directory = 'quick_registrations/photos';
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }
                
                // Generate unique filename
                $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
                
                // Store the file
                $path = $photo->storeAs($directory, $filename, 'public');
                
                if ($path) {
                    $validated['photo_path'] = $path;
                } else {
                    // If photo upload fails, log the error but continue without photo
                    \Log::warning('Photo upload failed for quick registration', [
                        'email' => $validated['email'],
                        'filename' => $filename
                    ]);
                }
            } catch (\Exception $e) {
                // Log the error but don't fail the registration
                \Log::error('Photo upload error in quick registration', [
                    'error' => $e->getMessage(),
                    'email' => $validated['email']
                ]);
                
                // Continue without photo - don't fail the entire registration
            }
        }

        // Remove photo from validated data as it's not a database field
        unset($validated['photo']);

        // Set default values
        $validated['country'] = $validated['country'] ?? 'Ecuador';

        // Add metadata
        $validated['metadata'] = [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'registration_source' => 'quick_registration_form',
            'timestamp' => now()->toISOString(),
        ];

        try {
            $registration = QuickRegistration::create($validated);

            return response()->json([
                'message' => 'Registro exitoso. Te contactaremos pronto.',
                'data' => $registration,
                'registration_id' => $registration->id,
                'registration_code' => $registration->registration_code,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error creating quick registration', [
                'error' => $e->getMessage(),
                'email' => $validated['email'] ?? 'unknown'
            ]);
            
            return response()->json([
                'message' => 'Error al crear el registro. Por favor intenta de nuevo.',
                'errors' => ['general' => ['Error interno del servidor.']]
            ], 500);
        }
    }

    /**
     * Get all quick registrations (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = QuickRegistration::query();

        // Apply filters
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('province') && $request->province) {
            $query->where('province', $request->province);
        }

        if ($request->has('city') && $request->city) {
            $query->where('city', $request->city);
        }

        if ($request->has('club_name') && $request->club_name) {
            $query->where('club_name', 'like', '%' . $request->club_name . '%');
        }

        if ($request->has('federation') && $request->federation) {
            $query->where('federation', 'like', '%' . $request->federation . '%');
        }

        if ($request->has('playing_side') && $request->playing_side) {
            $query->where('playing_side', $request->playing_side);
        }

        if ($request->has('playing_style') && $request->playing_style) {
            $query->where('playing_style', $request->playing_style);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('doc_id', 'like', "%{$search}%")
                  ->orWhere('registration_code', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $registrations = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($registrations);
    }

    /**
     * Show a specific registration.
     */
    public function show(QuickRegistration $quickRegistration): JsonResponse
    {
        return response()->json(['data' => $quickRegistration]);
    }

    /**
     * Update registration status.
     */
    public function updateStatus(Request $request, QuickRegistration $quickRegistration): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,contacted,approved,rejected',
            'notes' => 'nullable|string|max:1000',
        ]);

        $quickRegistration->update(['status' => $validated['status']]);

        // Update timestamps based on status
        switch ($validated['status']) {
            case 'contacted':
                $quickRegistration->markAsContacted();
                break;
            case 'approved':
                $quickRegistration->markAsApproved();
                break;
        }

        // Add admin notes to metadata if provided
        if (isset($validated['notes'])) {
            $metadata = $quickRegistration->metadata ?? [];
            $metadata['admin_notes'] = $validated['notes'];
            $metadata['status_updated_at'] = now()->toISOString();
            $quickRegistration->update(['metadata' => $metadata]);
        }

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'data' => $quickRegistration->fresh(),
        ]);
    }

    /**
     * Upload photo for registration.
     */
    public function uploadPhoto(Request $request, QuickRegistration $quickRegistration): JsonResponse
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max
        ]);

        // Delete old photo if exists
        if ($quickRegistration->photo_path && Storage::disk('public')->exists($quickRegistration->photo_path)) {
            Storage::disk('public')->delete($quickRegistration->photo_path);
        }

        $photo = $request->file('photo');
        $filename = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $path = $photo->storeAs('quick_registrations/photos', $filename, 'public');

        $quickRegistration->update(['photo_path' => $path]);

        return response()->json([
            'message' => 'Foto subida exitosamente',
            'photo_url' => Storage::url($path),
            'data' => $quickRegistration->fresh()
        ]);
    }

    /**
     * Get registration statistics.
     */
    public function getStatistics(): JsonResponse
    {
        $stats = [
            'total_registrations' => QuickRegistration::count(),
            'pending_registrations' => QuickRegistration::pending()->count(),
            'contacted_registrations' => QuickRegistration::contacted()->count(),
            'approved_registrations' => QuickRegistration::approved()->count(),
            'rejected_registrations' => QuickRegistration::where('status', 'rejected')->count(),

            'by_province' => QuickRegistration::select('province', DB::raw('count(*) as count'))
                ->groupBy('province')
                ->orderBy('count', 'desc')
                ->get(),

            'by_club' => QuickRegistration::select('club_name', DB::raw('count(*) as count'))
                ->whereNotNull('club_name')
                ->groupBy('club_name')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get(),

            'by_federation' => QuickRegistration::select('federation', DB::raw('count(*) as count'))
                ->whereNotNull('federation')
                ->groupBy('federation')
                ->orderBy('count', 'desc')
                ->get(),

            'by_playing_style' => QuickRegistration::select('playing_style', DB::raw('count(*) as count'))
                ->whereNotNull('playing_style')
                ->groupBy('playing_style')
                ->get(),

            'by_playing_side' => QuickRegistration::select('playing_side', DB::raw('count(*) as count'))
                ->whereNotNull('playing_side')
                ->groupBy('playing_side')
                ->get(),

            'by_gender' => QuickRegistration::select('gender', DB::raw('count(*) as count'))
                ->whereNotNull('gender')
                ->groupBy('gender')
                ->get(),

            'equipment_stats' => [
                'racket_brands' => QuickRegistration::select('racket_brand', DB::raw('count(*) as count'))
                    ->whereNotNull('racket_brand')
                    ->groupBy('racket_brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'drive_rubber_brands' => QuickRegistration::select('drive_rubber_brand', DB::raw('count(*) as count'))
                    ->whereNotNull('drive_rubber_brand')
                    ->groupBy('drive_rubber_brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
                'backhand_rubber_brands' => QuickRegistration::select('backhand_rubber_brand', DB::raw('count(*) as count'))
                    ->whereNotNull('backhand_rubber_brand')
                    ->groupBy('backhand_rubber_brand')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->get(),
            ],

            'recent_registrations' => QuickRegistration::where('created_at', '>=', now()->subDays(7))
                ->count(),

            'average_age' => QuickRegistration::whereNotNull('birth_date')
                ->selectRaw('AVG(YEAR(CURDATE()) - YEAR(birth_date)) as avg_age')
                ->value('avg_age'),

            'registrations_by_day' => QuickRegistration::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('count(*) as count')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Check if email is already registered.
     */
    public function checkEmail(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $exists = QuickRegistration::where('email', $request->email)->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'Este email ya está registrado' : 'Email disponible',
        ]);
    }

    /**
     * Get waiting room status for a specific email.
     */
    public function getWaitingRoomStatus(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $registration = QuickRegistration::where('email', $request->email)->first();

        if (!$registration) {
            return response()->json([
                'found' => false,
                'message' => 'No se encontró registro con este email',
            ], 404);
        }

        return response()->json([
            'found' => true,
            'data' => [
                'id' => $registration->id,
                'registration_code' => $registration->registration_code,
                'full_name' => $registration->full_name,
                'status' => $registration->status,
                'status_label' => $registration->status_label,
                'status_color' => $registration->status_color,
                'days_waiting' => $registration->days_waiting,
                'club_summary' => $registration->club_summary,
                'location_summary' => $registration->location_summary,
                'playing_side_label' => $registration->playing_side_label,
                'playing_style_label' => $registration->playing_style_label,
                'racket_summary' => $registration->racket_summary,
                'drive_rubber_summary' => $registration->drive_rubber_summary,
                'backhand_rubber_summary' => $registration->backhand_rubber_summary,
                'created_at' => $registration->created_at,
                'contacted_at' => $registration->contacted_at,
                'approved_at' => $registration->approved_at,
            ],
        ]);
    }

    /**
     * Delete a registration.
     */
    public function destroy(QuickRegistration $quickRegistration): JsonResponse
    {
        // Delete photo if exists
        if ($quickRegistration->photo_path && Storage::disk('public')->exists($quickRegistration->photo_path)) {
            Storage::disk('public')->delete($quickRegistration->photo_path);
        }

        $quickRegistration->delete();

        return response()->json([
            'message' => 'Registro eliminado exitosamente',
        ]);
    }
}