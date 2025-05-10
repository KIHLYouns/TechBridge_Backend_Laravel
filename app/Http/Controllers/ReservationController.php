<?php

namespace App\Http\Controllers;


use App\Models\Reservation;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\ClientInfoMail;
use App\Mail\PartnerInfoMail;
use App\Mail\ReservationCanceledMail;

class ReservationController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'listing_id' => 'required|exists:listing,id',
                'client_id' => 'required|exists:user,id',
                'delivery_option' => 'boolean'
            ]);
    
            $listing = Listing::find($validated['listing_id']);
    
            if (!$listing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Listing not found.',
                    'available' => false
                ], 404);
            }
    
            $existingReservations = Reservation::where('listing_id', $validated['listing_id'])
                ->where(function($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                          ->orWhere(function($q) use ($validated) {
                              $q->where('start_date', '<', $validated['start_date'])
                                ->where('end_date', '>', $validated['end_date']);
                          });
                })
                ->whereIn('status', ['pending', 'confirmed', 'ongoing']);
    
            $existingReservations = $existingReservations->exists();

    
            if ($existingReservations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet objet est déjà réservé pour la période demandée',
                    'available' => false
                ], 409); 
            }

            $reservation = Reservation::create([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending',
                'delivery_option' => $validated['delivery_option'] ?? false,
                'client_id' => $validated['client_id'], 
                'listing_id' => $validated['listing_id'], 
                'partner_id' => $listing->partner_id, 
                'created_at' => now()
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'data' => $reservation,
                'available' => true
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec de la création de la réservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function index(): JsonResponse
    {
        try {
            $reservations = Reservation::all();
            
            return response()->json([
                'success' => true,
                'message' => count($reservations) . ' reservations retrieved',
                'data' => $reservations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reservations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reservation found',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'sometimes|in:pending,confirmed,canceled,completed',
                'delivery_option' => 'sometimes|boolean'
            ]);

            $reservation->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Reservation updated',
                'data' => $reservation
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
            
            if (!$reservation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Reservation not found'
                ], 404);
            }

            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Reservation deleted'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getByClient($id): JsonResponse
    {
        try {
            $client = User::find($id);
    
            if (!$client) {
                \Log::error("Client not found with ID: $id");
                return response()->json(['error' => 'Client not found'], 404);
            }
    
            // Fetch reservations related to the client
            $reservations = Reservation::with([
                    'listing:id,title', 
                    'partner:id,username,email,phone_number,avatar_url', 
                    'client:id,username,email,phone_number,avatar_url'
                ])
                ->where('client_id', $id)
                ->get()
                ->each(function ($reservation) {
                    $reservation->makeHidden(['client_id', 'partner_id', 'listing_id']);
                });
    
            if ($reservations->isEmpty()) {
                return response()->json(['message' => 'No reservations found for this client'], 404);
            }
    
            return response()->json($reservations, 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
    public function getByPartner($id): JsonResponse
    {
        try {

            $partnerExists = User::where('id', $id)
                                 ->where('role', 'USER')
                                 ->where('is_partner', true)
                                 ->exists();
    
            if (!$partnerExists) {
                return response()->json(['error' => 'Partner not found'], 404);
            }
    

            $reservations = Reservation::with([
                    'listing:id,title',
                    'partner:id,username,email,phone_number,avatar_url',
                    'client:id,username,email,phone_number,avatar_url'
                ])
                ->whereHas('listing', function ($query) use ($id) {
                    $query->where('partner_id', $id); 
                })
                ->get()
                ->each(function ($reservation) {
                    $reservation->makeHidden(['partner_id', 'client_id', 'listing_id']);
                });
    
            return response()->json($reservations, 200);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }

    
    public function cancelReservation($id): JsonResponse
    {
        try {
            $reservation = Reservation::find($id);
    
            if (!$reservation) {
                return response()->json(['error' => 'Reservation not found'], 404);
            }
    
            $reservation->status = 'canceled';
            $reservation->save();
    
            $partner = $reservation->partner; 
            if ($partner && $partner->email) {
                Mail::to($partner->email)->send(new ReservationCanceledMail($reservation));
            }
    
            return response()->json([
                'message' => 'Reservation canceled successfully.',
                'reservation' => $reservation
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error'], 500);
        }
    }
    

public function acceptReservation($id): JsonResponse
{
    try {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            \Log::warning("Reservation not found with ID: $id");
            return response()->json(['error' => 'Reservation not found'], 404);
        }

        if ($reservation->status === 'canceled') {
            return response()->json(['error' => 'Cannot confirm a canceled reservation.'], 400);
        }

        if ($reservation->status === 'confirmed') {
            $client = $reservation->client;
            $partner = $reservation->partner;

            if (!$client || !$partner) {
                return response()->json(['error' => 'Client or Partner data is missing.'], 500);
            }

            try {
                Mail::to($partner->email)->send(new ClientInfoMail($client));
                Mail::to($client->email)->send(new PartnerInfoMail($partner));
            } catch (\Exception $mailException) {
                \Log::error("Failed to send emails for confirmed reservation ID: $id", [
                    'error' => $mailException->getMessage(),
                ]);
                return response()->json(['error' => 'Failed to send emails.'], 500);
            }

            return response()->json(['message' => 'Reservation already confirmed. Emails sent.'], 200);
        }

        $reservation->status = 'confirmed';
        $reservation->save();

        return response()->json([
            'message' => 'Reservation confirmed successfully.',
            'reservation' => $reservation
        ], 200);

    } catch (\Exception $e) {
        \Log::error("Error confirming reservation with ID $id", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json(['error' => 'Server error'], 500);
    }
}
public function declineReservation($id): JsonResponse
{
    try {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return response()->json(['error' => 'Reservation not found'], 404);
        }
        if (in_array($reservation->status, ['canceled', 'confirmed', 'completed'])) {
            return response()->json([
                'error' => 'This reservation cannot be declined because it is already ' . $reservation->status . '.'
            ], 400);
        }

        $reservation->status = 'declined';
        $reservation->save();
        return response()->json([
            'message' => 'Reservation declined successfully.',
            'reservation' => $reservation
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['error' => 'Server error'], 500);
    }
}

    }