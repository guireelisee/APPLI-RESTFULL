<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ReservationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::middleware('api')
->group(function () {
    Route::get('/test', fn () => response()->json(['ok' => true]));
});


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::middleware('api')->group(function () {

        // Route de test
        Route::get('/', function () {
            return response()->json([
                'success' => true,
                'message' => 'API Laravel - Gestion des Clients et Réservations',
                'version' => '1.0.0',
                'timestamp' => now()->toIso8601String(),
            ]);
        });

        // Routes pour les Clients
        Route::prefix('clients')->group(function () {
            Route::get('/', [ClientController::class, 'index']);
            Route::post('/', [ClientController::class, 'store']);
            Route::get('/{id}', [ClientController::class, 'show']);
            Route::put('/{id}', [ClientController::class, 'update']);
            Route::delete('/{id}', [ClientController::class, 'destroy']);
            Route::get('/{id}/reservations', [ClientController::class, 'reservations']);
        });

        // Routes pour les Réservations
        Route::prefix('reservations')->group(function () {
            Route::get('/', [ReservationController::class, 'index']);
            Route::post('/', [ReservationController::class, 'store']);
            Route::get('/stats', [ReservationController::class, 'stats']);
            Route::get('/{id}', [ReservationController::class, 'show']);
            Route::put('/{id}', [ReservationController::class, 'update']);
            Route::delete('/{id}', [ReservationController::class, 'destroy']);
            Route::post('/{id}/cancel', [ReservationController::class, 'cancel']);
            Route::post('/{id}/confirm', [ReservationController::class, 'confirm']);
        });

        // Route pour vérifier la synchronisation
        Route::get('/sync-stats', function () {
            try {
                $sourceClients = DB::connection('pgsql')->table('clients')->count();
                $targetClients = DB::connection('pgsql_second')->table('clients')->count();

                $sourceReservations = DB::connection('pgsql')->table('reservations')->count();
                $targetReservations = DB::connection('pgsql_second')->table('reservations')->count();

                return response()->json([
                    'success' => true,
                    'timestamp' => now()->toIso8601String(),
                    'data' => [
                        'clients' => [
                            'source' => $sourceClients,
                            'target' => $targetClients,
                            'synced' => $sourceClients === $targetClients,
                            'difference' => $sourceClients - $targetClients,
                        ],
                        'reservations' => [
                            'source' => $sourceReservations,
                            'target' => $targetReservations,
                            'synced' => $sourceReservations === $targetReservations,
                            'difference' => $sourceReservations - $targetReservations,
                        ],
                        'overall_status' => [
                            'synced' => ($sourceClients === $targetClients) &&
                            ($sourceReservations === $targetReservations),
                            'health' => 'healthy',
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error fetching sync stats',
                    'error' => $e->getMessage(),
                ], 500);
            }
        });

    });
});
