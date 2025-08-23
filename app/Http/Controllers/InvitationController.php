<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\League;
use App\Models\Club;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InvitationController extends Controller
{
    /**
     * Display a listing of invitations for the authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $type = $request->get('type', 'all'); // 'sent', 'received', 'all'
            $status = $request->get('status', 'all'); // 'pending', 'accepted', 'rejected', 'all'
            
            // Get the user's entity (League, Club, etc.)
            $userEntity = $this->getUserEntity($user);
            
            if (!$userEntity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User entity not found'
                ], 404);
            }

            $query = Invitation::with(['sender', 'receiver']);

            // Filter by type (sent/received)
            if ($type === 'sent') {
                $query->sentBy($userEntity->id, get_class($userEntity));
            } elseif ($type === 'received') {
                $query->receivedBy($userEntity->id, get_class($userEntity));
            } else {
                // Get both sent and received
                $query->where(function ($q) use ($userEntity) {
                    $q->where(function ($subQ) use ($userEntity) {
                        $subQ->where('sender_id', $userEntity->id)
                             ->where('sender_type', get_class($userEntity));
                    })->orWhere(function ($subQ) use ($userEntity) {
                        $subQ->where('receiver_id', $userEntity->id)
                             ->where('receiver_type', get_class($userEntity));
                    });
                });
            }

            // Filter by status
            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $invitations = $query->orderBy('created_at', 'desc')->paginate(15);

            // Add additional information to each invitation
            $invitations->getCollection()->transform(function ($invitation) use ($userEntity) {
                $invitation->is_sender = $invitation->sender_id === $userEntity->id && 
                                       $invitation->sender_type === get_class($userEntity);
                $invitation->sender_name = $invitation->sender ? $invitation->sender->name : 'Unknown';
                $invitation->receiver_name = $invitation->receiver ? $invitation->receiver->name : 'Unknown';
                
                // Add sender/receiver details
                if ($invitation->sender) {
                    $invitation->sender_details = [
                        'id' => $invitation->sender->id,
                        'name' => $invitation->sender->name,
                        'type' => class_basename($invitation->sender_type),
                    ];
                    
                    // Add additional details based on type
                    if ($invitation->sender instanceof League) {
                        $invitation->sender_details['province'] = $invitation->sender->province;
                    } elseif ($invitation->sender instanceof Club) {
                        $invitation->sender_details['city'] = $invitation->sender->city;
                    }
                }
                
                if ($invitation->receiver) {
                    $invitation->receiver_details = [
                        'id' => $invitation->receiver->id,
                        'name' => $invitation->receiver->name,
                        'type' => class_basename($invitation->receiver_type),
                    ];
                    
                    // Add additional details based on type
                    if ($invitation->receiver instanceof League) {
                        $invitation->receiver_details['province'] = $invitation->receiver->province;
                    } elseif ($invitation->receiver instanceof Club) {
                        $invitation->receiver_details['city'] = $invitation->receiver->city;
                    }
                }
                
                return $invitation;
            });

            return response()->json([
                'status' => 'success',
                'data' => $invitations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching invitations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created invitation
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'receiver_id' => 'required|integer',
                'receiver_type' => 'required|string|in:App\Models\Club,App\Models\League',
                'message' => 'nullable|string|max:1000',
                'type' => 'required|string|in:league_to_club,club_to_league',
                'expires_at' => 'nullable|date|after:now',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();
            $senderEntity = $this->getUserEntity($user);
            
            if (!$senderEntity) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sender entity not found'
                ], 404);
            }

            // Verify receiver exists
            $receiverClass = $request->receiver_type;
            $receiver = $receiverClass::find($request->receiver_id);
            
            if (!$receiver) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Receiver not found'
                ], 404);
            }

            // Check if invitation already exists
            $existingInvitation = Invitation::where('sender_id', $senderEntity->id)
                ->where('sender_type', get_class($senderEntity))
                ->where('receiver_id', $receiver->id)
                ->where('receiver_type', get_class($receiver))
                ->where('status', 'pending')
                ->first();

            if ($existingInvitation) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'A pending invitation already exists for this recipient'
                ], 409);
            }

            // Create the invitation
            $invitation = Invitation::create([
                'sender_id' => $senderEntity->id,
                'sender_type' => get_class($senderEntity),
                'receiver_id' => $receiver->id,
                'receiver_type' => get_class($receiver),
                'message' => $request->message,
                'type' => $request->type,
                'expires_at' => $request->expires_at,
            ]);

            $invitation->load(['sender', 'receiver']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation sent successfully',
                'data' => $invitation
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error creating invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified invitation
     */
    public function show(Invitation $invitation): JsonResponse
    {
        try {
            $user = Auth::user();
            $userEntity = $this->getUserEntity($user);
            
            // Check if user is authorized to view this invitation
            if (!$this->canAccessInvitation($invitation, $userEntity)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to view this invitation'
                ], 403);
            }

            $invitation->load(['sender', 'receiver']);

            return response()->json([
                'status' => 'success',
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Accept an invitation
     */
    public function accept(Invitation $invitation): JsonResponse
    {
        try {
            $user = Auth::user();
            $userEntity = $this->getUserEntity($user);
            
            // Check if user is the receiver of this invitation
            if ($invitation->receiver_id !== $userEntity->id || 
                $invitation->receiver_type !== get_class($userEntity)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to accept this invitation'
                ], 403);
            }

            if (!$invitation->accept()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot accept this invitation. It may be expired or already processed.'
                ], 400);
            }

            $invitation->load(['sender', 'receiver']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation accepted successfully',
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error accepting invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reject an invitation
     */
    public function reject(Invitation $invitation): JsonResponse
    {
        try {
            $user = Auth::user();
            $userEntity = $this->getUserEntity($user);
            
            // Check if user is the receiver of this invitation
            if ($invitation->receiver_id !== $userEntity->id || 
                $invitation->receiver_type !== get_class($userEntity)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to reject this invitation'
                ], 403);
            }

            if (!$invitation->reject()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot reject this invitation. It may be expired or already processed.'
                ], 400);
            }

            $invitation->load(['sender', 'receiver']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation rejected successfully',
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error rejecting invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an invitation (by sender)
     */
    public function cancel(Invitation $invitation): JsonResponse
    {
        try {
            $user = Auth::user();
            $userEntity = $this->getUserEntity($user);
            
            // Check if user is the sender of this invitation
            if ($invitation->sender_id !== $userEntity->id || 
                $invitation->sender_type !== get_class($userEntity)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized to cancel this invitation'
                ], 403);
            }

            if (!$invitation->cancel()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel this invitation. It may be already processed.'
                ], 400);
            }

            $invitation->load(['sender', 'receiver']);

            return response()->json([
                'status' => 'success',
                'message' => 'Invitation cancelled successfully',
                'data' => $invitation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error cancelling invitation: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available clubs to invite (for leagues)
     */
    public function getAvailableClubs(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'liga' && $user->role !== 'super_admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $search = $request->get('search', '');
            $userEntity = $this->getUserEntity($user);
            
            // Get ALL clubs, but exclude clubs that already belong to the current league
            $query = Club::query();
            
            // If user is a league, exclude clubs that already belong to this league
            if ($user->role === 'liga' && $userEntity) {
                $query->where(function ($q) use ($userEntity) {
                    $q->where('league_id', '!=', $userEntity->id)
                      ->orWhereNull('league_id');
                });
            }
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%");
                });
            }

            $clubs = $query->with(['user', 'league'])->orderBy('name')->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $clubs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching available clubs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available leagues to invite (for clubs)
     */
    public function getAvailableLeagues(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if ($user->role !== 'club' && $user->role !== 'super_admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $search = $request->get('search', '');
            
            // Get all leagues
            $query = League::query();
            
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('province', 'like', "%{$search}%");
                });
            }

            $leagues = $query->with('user')->orderBy('name')->paginate(20);

            return response()->json([
                'status' => 'success',
                'data' => $leagues
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching available leagues: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available entities to invite based on user role
     */
    public function getAvailableEntities(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $search = $request->get('search', '');
            
            if ($user->role === 'liga' || $user->role === 'super_admin') {
                // Liga can invite ALL clubs (except those already in their league)
                $userEntity = $this->getUserEntity($user);
                $query = Club::query();
                
                // If user is a league, exclude clubs that already belong to this league
                if ($user->role === 'liga' && $userEntity) {
                    $query->where(function ($q) use ($userEntity) {
                        $q->where('league_id', '!=', $userEntity->id)
                          ->orWhereNull('league_id');
                    });
                }
                
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('city', 'like', "%{$search}%");
                    });
                }

                $entities = $query->with(['user', 'league'])->orderBy('name')->paginate(20);
                
                return response()->json([
                    'status' => 'success',
                    'data' => $entities,
                    'entity_type' => 'clubs'
                ]);
                
            } elseif ($user->role === 'club') {
                // Club can request to join leagues
                $query = League::query();
                
                if ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('province', 'like', "%{$search}%");
                    });
                }

                $entities = $query->with('user')->orderBy('name')->paginate(20);
                
                return response()->json([
                    'status' => 'success',
                    'data' => $entities,
                    'entity_type' => 'leagues'
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or unsupported role'
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error fetching available entities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's entity (League, Club, etc.)
     */
    private function getUserEntity($user)
    {
        switch ($user->role) {
            case 'liga':
                return League::where('user_id', $user->id)->first();
            case 'club':
                return Club::where('user_id', $user->id)->first();
            default:
                return null;
        }
    }

    /**
     * Check if user can access invitation
     */
    private function canAccessInvitation(Invitation $invitation, $userEntity): bool
    {
        if (!$userEntity) {
            return false;
        }

        return ($invitation->sender_id === $userEntity->id && $invitation->sender_type === get_class($userEntity)) ||
               ($invitation->receiver_id === $userEntity->id && $invitation->receiver_type === get_class($userEntity));
    }
}