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
use App\Mail\ReservationDeclined;
use App\Http\Controllers\Api\ListingController;
use Carbon\Carbon;
use App\Models\Payment;


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
                ->where(function ($query) use ($validated) {
                    $query->whereBetween('start_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhereBetween('end_date', [$validated['start_date'], $validated['end_date']])
                        ->orWhere(function ($q) use ($validated) {
                            $q->where('start_date', '<', $validated['start_date'])
                                ->where('end_date', '>', $validated['end_date']);
                        });
                })
                ->whereIn('status', ['pending', 'confirmed', 'ongoing'])
                ->exists();

            if ($existingReservations) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cet objet est déjà réservé pour la période demandée',
                    'available' => false
                ], 409);
            }

            // Calcul du coût total
            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $days = $startDate->diffInDays($endDate) + 1; // inclut le jour de début
            $totalCost = $days * $listing->price_per_day;

            $reservation = Reservation::create([
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => 'pending',
                'delivery_option' => $validated['delivery_option'] ?? false,
                'client_id' => $validated['client_id'],
                'listing_id' => $validated['listing_id'],
                'partner_id' => $listing->partner_id,
                'total_cost' => $totalCost,
                'created_at' => now()
            ]);

            // Calcul des frais
            $commissionFee = $totalCost * 0.15;
            $amount = $totalCost;
            $partner_payout = $amount - $commissionFee;
            $paymentDate = now();

            $payment = Payment::create([
                'amount' => $amount,
                'commission_fee' => $commissionFee,
                'partner_payout' => $partner_payout,
                'payment_date' => $paymentDate,
                'status' => 'pending',
                'payment_method' => 'credit_card',
                // 'transaction_id' est généré automatiquement dans le modèle
                'client_id' => $validated['client_id'],
                'reservation_id' => $reservation->id,
                'partner_id' => $listing->partner_id,
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

    private function updateReservationStatuses($reservations)
    {
        $today = Carbon::today();

        foreach ($reservations as $reservation) {
            // Parse des dates pour comparaison
            $start = Carbon::parse($reservation->start_date);
            $end = Carbon::parse($reservation->end_date);

            // Si la réservation est confirmée ou en cours
            if (in_array($reservation->status, ['confirmed', 'ongoing'])) {
                if ($today->between($start, $end)) {
                    // On est dans la période de la réservation
                    if ($reservation->status !== 'ongoing') {
                        $reservation->status = 'ongoing';
                        // Mettre à jour le statut du paiement à "refunded"
                        $payment = Payment::where('reservation_id', $reservation->id)->first();
                        if ($payment) {
                            $payment->status = 'completed';
                            $payment->save();
                        }
                        $reservation->save();
                    }
                } elseif ($today->gt($end)) {
                    // Dépassé la date de fin, la réservation est terminée
                    if ($reservation->status !== 'completed') {
                        $reservation->status = 'completed';
                        $reservation->save();
                    }
                }
                // Si aujourd'hui est avant la date de début, elle reste confirmée
            }
        }

        return $reservations;
    }

    public function index(): JsonResponse
    {
        try {
            $reservations = Reservation::all();
            $reservations = $this->updateReservationStatuses($reservations);

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

            // Fetch reservations
            $reservations = Reservation::with(['listing', 'partner', 'client'])
                ->where('client_id', $id)
                ->get();

            // Mettre à jour les statuts des réservations
            $reservations = $this->updateReservationStatuses($reservations);

            $listingController = new ListingController();
            $enhancedReservations = $reservations->map(function ($reservation) use ($listingController) {
                $listingDetails = $listingController->show($reservation->listing_id);
                $listingData = json_decode($listingDetails->getContent(), true);

                return [
                    'reservation_id' => $reservation->id,
                    'start_date' => $reservation->start_date,
                    'end_date' => $reservation->end_date,
                    'status' => $reservation->status,
                    'listing' => $listingData,
                    'partner' => $reservation->partner,
                    'client' => $reservation->client
                ];
            });

            return response()->json($enhancedReservations, 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
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
                'listing' => function ($query) {
                    $query->select('id', 'title')
                        ->with('images');
                },
                'partner:id,username,email,phone_number,avatar_url',
                'client:id,username,email,phone_number,avatar_url'
            ])
                ->whereHas('listing', function ($query) use ($id) {
                    $query->where('partner_id', $id);
                })
                ->get();

            // Mettre à jour les statuts des réservations
            $reservations = $this->updateReservationStatuses($reservations);

            $reservations->each(function ($reservation) {
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

            // Check if reservation is already ongoing or completed
            if ($reservation->status === 'ongoing') {
                return response()->json([
                    'error' => 'Reservation cannot be canceled because it is already ongoing.'
                ], 400);
            }

            if ($reservation->status === 'completed') {
                return response()->json([
                    'error' => 'Reservation cannot be canceled because it is already completed.'
                ], 400);
            }

            // Check if reservation is already canceled
            if ($reservation->status === 'canceled') {
                return response()->json([
                    'error' => 'Reservation is already canceled.'
                ], 400);
            }

            $reservation->status = 'canceled';
            $reservation->save();

            // Mise à jour du statut du paiement en 'refunded'
            $payment = $reservation->payment;
            if ($payment) {
                $payment->status = 'refunded';
                $payment->save();
            }

            $partner = $reservation->partner;
            if ($partner && $partner->email) {
                Mail::to($partner->email)->send(new ReservationCanceledMail($reservation));
            }

            return response()->json([
                'message' => 'Reservation canceled successfully.',
                'reservation' => $reservation
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
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

            // Check if already confirmed first
            if ($reservation->status === 'confirmed') {
                return response()->json(['message' => 'Reservation is already confirmed.'], 200);
            }

            $client = $reservation->client;
            $partner = $reservation->partner;

            if (!$client || !$partner) {
                return response()->json(['error' => 'Client or Partner data is missing.'], 500);
            }

            // Update status first
            $reservation->status = 'confirmed';
            $reservation->save();
            
            try {
                // Send emails after status is confirmed
                Mail::to($partner->email)->send(new ClientInfoMail($client));
                Mail::to($client->email)->send(new PartnerInfoMail($partner));
            } catch (\Exception $mailException) {
                \Log::error("Failed to send emails for reservation ID: $id", [
                    'error' => $mailException->getMessage(),
                ]);
                // Note: Status is already saved as confirmed even if emails fail
                return response()->json([
                    'error' => 'Reservation confirmed but failed to send emails.',
                    'reservation' => $reservation
                ], 500);
            }

            return response()->json([
                'message' => 'Reservation confirmed successfully and emails sent.',
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

            if (in_array($reservation->status, ['canceled', 'ongoing'])) {
                return response()->json([
                    'error' => 'This reservation cannot be declined because it is already ' . $reservation->status . '.'
                ], 400);
            }

            $reservation->status = 'declined';
            $reservation->save();

            // Mise à jour du statut du paiement en 'refunded'
            $payment = $reservation->payment;
            if ($payment) {
                $payment->status = 'refunded';
                $payment->save();
            }

            // Envoyer l'email de notification au client
            $client = $reservation->client; // Supposant qu'il y a une relation 'client' dans le modèle Reservation
            $partner = $reservation->partner; // Supposant qu'il y a une relation 'partner'

            if ($client && $client->email) {
                Mail::to($client->email)->send(new ReservationDeclined(
                    $reservation,
                    $partner->name ?? 'le partenaire'
                ));
            }

            return response()->json([
                'message' => 'Reservation declined successfully.',
                'reservation' => $reservation
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error declining reservation: ' . $e->getMessage());
            return response()->json(['error' => 'Server error'], 500);
        }
    }
}