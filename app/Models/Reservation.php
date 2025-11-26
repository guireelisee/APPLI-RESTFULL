<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'numero_reservation',
        'service',
        'date_reservation',
        'date_debut',
        'date_fin',
        'montant',
        'statut',
        'commentaire',
    ];

    protected $casts = [
        'date_reservation' => 'date',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'montant' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le client
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Boot method pour générer le numéro de réservation
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->numero_reservation)) {
                $reservation->numero_reservation = 'RES-' . strtoupper(uniqid());
            }
        });
    }

    /**
     * Scopes
     */
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeConfirmee($query)
    {
        return $query->where('statut', 'confirmee');
    }

    public function scopeAnnulee($query)
    {
        return $query->where('statut', 'annulee');
    }

    public function scopeTerminee($query)
    {
        return $query->where('statut', 'terminee');
    }
}
