<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReservationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_reservation' => $this->numero_reservation,
            'service' => $this->service,
            'date_reservation' => $this->date_reservation->format('d/m/Y'),
            'date_debut' => $this->date_debut->format('d/m/Y'),
            'date_fin' => $this->date_fin?->format('d/m/Y'),
            'montant' => (float) $this->montant,
            'montant_formatte' => number_format($this->montant, 0, ',', ' ') . ' FCFA',
            'statut' => $this->statut,
            'statut_label' => $this->getStatutLabel(),
            'commentaire' => $this->commentaire,
            'client' => new ClientResource($this->whenLoaded('client')),
            'date_creation' => $this->created_at->format('d/m/Y H:i:s'),
            'date_modification' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }

    private function getStatutLabel(): string
    {
        return match($this->statut) {
            'en_attente' => 'En attente',
            'confirmee' => 'Confirmée',
            'annulee' => 'Annulée',
            'terminee' => 'Terminée',
            default => $this->statut,
        };
    }
}
