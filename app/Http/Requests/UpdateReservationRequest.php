<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => 'sometimes|required|exists:clients,id',
            'service' => 'sometimes|required|string|max:100',
            'date_reservation' => 'sometimes|required|date',
            'date_debut' => 'sometimes|required|date|after_or_equal:date_reservation',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'montant' => 'sometimes|required|numeric|min:0',
            'statut' => 'sometimes|in:en_attente,confirmee,annulee,terminee',
            'commentaire' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Le client est obligatoire',
            'client_id.exists' => 'Ce client n\'existe pas',
            'service.required' => 'Le service est obligatoire',
            'date_reservation.required' => 'La date de réservation est obligatoire',
            'date_debut.required' => 'La date de début est obligatoire',
            'date_debut.after_or_equal' => 'La date de début doit être après ou égale à la date de réservation',
            'date_fin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début',
            'montant.required' => 'Le montant est obligatoire',
            'montant.numeric' => 'Le montant doit être un nombre',
            'montant.min' => 'Le montant doit être supérieur ou égal à 0',
        ];
    }
}
