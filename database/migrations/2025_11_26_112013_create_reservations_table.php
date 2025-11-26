<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('numero_reservation', 50)->unique();
            $table->string('service', 100);
            $table->date('date_reservation');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('montant', 10, 2);
            $table->enum('statut', ['en_attente', 'confirmee', 'annulee', 'terminee'])
                ->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->timestamps();

            // Index pour améliorer les performances
            $table->index('client_id');
            $table->index('numero_reservation');
            $table->index('statut');
            $table->index('date_reservation');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
