<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    /**
     * Liste tous les clients
     */
    public function index(Request $request): JsonResponse
    {
        $clients = Client::with('reservations')->get();

        try {
            return response()->json([
                'success' => true,
                'data' => ClientResource::collection($clients),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des clients',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Créer un nouveau client
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        try {
            $client = Client::create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => new ClientResource($client->load('reservations')),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher un client spécifique
     */
    public function show(string $id): JsonResponse
    {
        try {
            $client = Client::with('reservations')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new ClientResource($client),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
            ], 404);
        }
    }

    /**
     * Mettre à jour un client
     */
    public function update(UpdateClientRequest $request, string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);
            $client->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Client modifié avec succès',
                'data' => new ClientResource($client->load('reservations')),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un client
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $client = Client::findOrFail($id);

            // Vérifier s'il a des réservations
            if ($client->reservations()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce client car il a des réservations',
                ], 400);
            }

            $client->delete();

            return response()->json([
                'success' => true,
                'message' => 'Client supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Récupérer les réservations d'un client
     */
    public function reservations(string $id): JsonResponse
    {
        try {
            $client = Client::with('reservations')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'client' => new ClientResource($client),
                    'total_reservations' => $client->reservations->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Client non trouvé',
            ], 404);
        }
    }
}
