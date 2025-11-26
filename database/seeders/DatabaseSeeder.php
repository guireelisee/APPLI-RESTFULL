<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer 10 clients
        $clients = [];
        $noms = ['Ouedraogo', 'Sawadogo', 'Kabore', 'Traore', 'Zongo', 'Compaore', 'Sankara', 'Yameogo', 'Nacro', 'Bambara'];
        $prenoms = ['Abdoul', 'Fatimata', 'Ibrahim', 'Aminata', 'Moussa', 'Mariam', 'Seydou', 'Aissata', 'Yacouba', 'Kadiatou'];
        $villes = ['Ouagadougou', 'Bobo-Dioulasso', 'Koudougou', 'Ouahigouya', 'Banfora'];

        for ($i = 0; $i < 10; $i++) {
            $clients[] = Client::create([
                'nom' => $noms[$i],
                'prenom' => $prenoms[$i],
                'email' => strtolower($prenoms[$i] . '.' . $noms[$i]) . '@test.bf',
                'telephone' => '+2267' . rand(0, 9) . rand(100000, 999999),
                'adresse' => 'Secteur ' . rand(1, 50) . ', ' . $villes[array_rand($villes)],
                'ville' => $villes[array_rand($villes)],
                'pays' => 'Burkina Faso',
            ]);
        }

        // Créer des réservations pour chaque client
        $services = ['Hotel', 'Restaurant', 'Transport', 'Événement', 'Location de salle', 'Traiteur'];
        $statuts = ['en_attente', 'confirmee', 'annulee', 'terminee'];

        $totalReservations = 0;
        foreach ($clients as $client) {
            // Chaque client a entre 1 et 5 réservations
            $nbReservations = rand(1, 5);

            for ($j = 0; $j < $nbReservations; $j++) {
                Reservation::create([
                    'client_id' => $client->id,
                    'service' => $services[array_rand($services)],
                    'date_reservation' => now()->subDays(rand(1, 60)),
                    'date_debut' => now()->addDays(rand(1, 90)),
                    'date_fin' => now()->addDays(rand(91, 180)),
                    'montant' => rand(5000, 500000),
                    'statut' => $statuts[array_rand($statuts)],
                    'commentaire' => 'Réservation de test numéro ' . ($totalReservations + 1),
                ]);
                $totalReservations++;
            }
        }
    }
}
