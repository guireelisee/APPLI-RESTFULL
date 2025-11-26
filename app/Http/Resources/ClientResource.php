<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'nom_complet' => $this->nom_complet,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'nombre_reservations' => $this->whenLoaded('reservations', fn() => $this->reservations->count(), 0),
            'reservations' => ReservationResource::collection($this->whenLoaded('reservations')),
            'date_creation' => $this->created_at->format('d/m/Y H:i:s'),
            'date_modification' => $this->updated_at->format('d/m/Y H:i:s'),
        ];
    }
}
