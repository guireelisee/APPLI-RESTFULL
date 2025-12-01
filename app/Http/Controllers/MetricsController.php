<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    /**
     * Endpoint pour Prometheus
     */
    public function index()
    {
        $metrics = $this->generateMetrics();

        return response($metrics, 200)
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
    }

    /**
     * Générer les métriques au format Prometheus
     */
    private function generateMetrics(): string
    {
        $lines = [];

        // ============================================
        // MÉTRIQUES DE SYNCHRONISATION
        // ============================================

        try {
            // Compter les clients
            $clientsSource = (int) DB::connection('pgsql')->table('clients')->count();
            $clientsTarget = (int) DB::connection('pgsql_second')->table('clients')->count();

            $lines[] = '# HELP laravel_clients_source_total Total clients in source database';
            $lines[] = '# TYPE laravel_clients_source_total gauge';
            $lines[] = 'laravel_clients_source_total ' . $clientsSource;

            $lines[] = '# HELP laravel_clients_target_total Total clients in target database';
            $lines[] = '# TYPE laravel_clients_target_total gauge';
            $lines[] = 'laravel_clients_target_total ' . $clientsTarget;

            $lines[] = '# HELP laravel_clients_sync_diff Difference between source and target';
            $lines[] = '# TYPE laravel_clients_sync_diff gauge';
            $lines[] = 'laravel_clients_sync_diff ' . abs($clientsSource - $clientsTarget);

            // Compter les réservations
            $reservationsSource = (int) DB::connection('pgsql')->table('reservations')->count();
            $reservationsTarget = (int) DB::connection('pgsql_second')->table('reservations')->count();

            $lines[] = '# HELP laravel_reservations_source_total Total reservations in source database';
            $lines[] = '# TYPE laravel_reservations_source_total gauge';
            $lines[] = 'laravel_reservations_source_total ' . $reservationsSource;

            $lines[] = '# HELP laravel_reservations_target_total Total reservations in target database';
            $lines[] = '# TYPE laravel_reservations_target_total gauge';
            $lines[] = 'laravel_reservations_target_total ' . $reservationsTarget;

            $lines[] = '# HELP laravel_reservations_sync_diff Difference between source and target';
            $lines[] = '# TYPE laravel_reservations_sync_diff gauge';
            $lines[] = 'laravel_reservations_sync_diff ' . abs($reservationsSource - $reservationsTarget);

            // Statut de synchronisation (1 = synced, 0 = not synced)
            $syncStatus = ($clientsSource === $clientsTarget && $reservationsSource === $reservationsTarget) ? 1 : 0;
            $lines[] = '# HELP laravel_sync_status Global sync status (1=synced, 0=not synced)';
            $lines[] = '# TYPE laravel_sync_status gauge';
            $lines[] = 'laravel_sync_status ' . $syncStatus;

        } catch (\Exception $e) {
            // En cas d'erreur, continuer avec des valeurs 0
            $lines[] = 'laravel_clients_source_total 0';
            $lines[] = 'laravel_clients_target_total 0';
        }

        // ============================================
        // MÉTRIQUES DE RÉSERVATIONS PAR STATUT
        // ============================================

        try {
            $lines[] = '# HELP laravel_reservations_by_status Reservations count by status';
            $lines[] = '# TYPE laravel_reservations_by_status gauge';

            $statuts = ['en_attente', 'confirmee', 'annulee', 'terminee'];

            foreach ($statuts as $statut) {
                $count = (int) DB::connection('pgsql')
                    ->table('reservations')
                    ->where('statut', $statut)
                    ->count();

                $lines[] = 'laravel_reservations_by_status{statut="' . $statut . '"} ' . $count;
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs
        }

        // ============================================
        // MÉTRIQUES DE CONNEXION DATABASE
        // ============================================

        $lines[] = '# HELP laravel_db_connection_status Database connection status (1=up, 0=down)';
        $lines[] = '# TYPE laravel_db_connection_status gauge';

        // Test connexion source
        try {
            DB::connection('pgsql')->select('SELECT 1');
            $lines[] = 'laravel_db_connection_status{database="source"} 1';
        } catch (\Exception $e) {
            $lines[] = 'laravel_db_connection_status{database="source"} 0';
        }

        // Test connexion target
        try {
            DB::connection('pgsql_second')->select('SELECT 1');
            $lines[] = 'laravel_db_connection_status{database="target"} 1';
        } catch (\Exception $e) {
            $lines[] = 'laravel_db_connection_status{database="target"} 0';
        }

        // ============================================
        // MÉTRIQUES SYSTÈME
        // ============================================

        $lines[] = '# HELP laravel_app_info Laravel application information';
        $lines[] = '# TYPE laravel_app_info gauge';
        $lines[] = 'laravel_app_info{version="' . app()->version() . '",environment="' . app()->environment() . '"} 1';

        $uptime = defined('LARAVEL_START') ? (int)(microtime(true) - LARAVEL_START) : 0;
        $lines[] = '# HELP laravel_app_uptime_seconds Application uptime in seconds';
        $lines[] = '# TYPE laravel_app_uptime_seconds counter';
        $lines[] = 'laravel_app_uptime_seconds ' . $uptime;

        // Joindre toutes les lignes avec un retour à la ligne Unix
        return implode("\n", $lines) . "\n";
    }
}
