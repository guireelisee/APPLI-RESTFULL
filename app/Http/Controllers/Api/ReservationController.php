<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Http\Resources\ReservationResource;
use App\Models\Reservation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    /**
     * Liste toutes les réservations
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Reservation::with('client');

        // Filtrer par client
        if ($request->has('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        // Filtrer par statut
        if ($request->has('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        // Filtrer par service
        if ($request->has('service')) {
            $query->where('service', 'like', "%{$request->input('service')}%");
        }

        // Filtrer par date de début
        if ($request->has('date_debut')) {
            $query->whereDate('date_debut', '>=', $request->input('date_debut'));
        }

        // Filtrer par date de fin
        if ($request->has('date_fin')) {
            $query->whereDate('date_debut', '<=', $request->input('date_fin'));
        }

        // Recherche par numéro de réservation
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('numero_reservation', 'like', "%{$search}%");
        }

        // Tri (les plus récentes en premier par défaut)
        $sortBy = $request->input('sort_by', 'date_reservation');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 15);
        $reservations = $query->paginate($perPage);

        return ReservationResource::collection($reservations);
    }

    /**
     * Créer une nouvelle réservation
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        try {
            $reservation = Reservation::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Réservation créée avec succès',
                'data' => new ReservationResource($reservation->load('client')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la réservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une réservation spécifique
     */
    public function show(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::with('client')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ReservationResource($reservation),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Réservation non trouvée',
            ], 404);
        }
    }

    /**
     * Mettre à jour une réservation
     */
    public function update(UpdateReservationRequest $request, string $id): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $reservation->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Réservation modifiée avec succès',
                'data' => new ReservationResource($reservation->load('client')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la réservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une réservation
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $reservation->delete();

            return response()->json([
                'success' => true,
                'message' => 'Réservation supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la réservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une réservation
     */
    public function cancel(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($id);

            if ($reservation->statut === 'annulee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réservation est déjà annulée',
                ], 400);
            }

            if ($reservation->statut === 'terminee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une réservation terminée',
                ], 400);
            }

            $reservation->update(['statut' => 'annulee']);

            return response()->json([
                'success' => true,
                'message' => 'Réservation annulée avec succès',
                'data' => new ReservationResource($reservation->load('client')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la réservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirmer une réservation
     */
    public function confirm(string $id): JsonResponse
    {
        try {
            $reservation = Reservation::findOrFail($id);

            if ($reservation->statut === 'confirmee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette réservation est déjà confirmée',
                ], 400);
            }

            if ($reservation->statut === 'annulee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de confirmer une réservation annulée',
                ], 400);
            }

            $reservation->update(['statut' => 'confirmee']);

            return response()->json([
                'success' => true,
                'message' => 'Réservation confirmée avec succès',
                'data' => new ReservationResource($reservation->load('client')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation de la réservation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Statistiques des réservations
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Reservation::count(),
                'en_attente' => Reservation::where('statut', 'en_attente')->count(),
                'confirmees' => Reservation::where('statut', 'confirmee')->count(),
                'annulees' => Reservation::where('statut', 'annulee')->count(),
                'terminees' => Reservation::where('statut', 'terminee')->count(),
                'montant_total' => Reservation::sum('montant'),
                'montant_moyen' => Reservation::avg('montant'),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
