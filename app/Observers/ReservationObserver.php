<?php

namespace App\Observers;

use App\Models\Reservation;
use App\Services\KafkaProducerService;

class ReservationObserver
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    public function created(Reservation $reservation): void
    {
        $this->syncViaKafka('created', $reservation);
    }

    public function updated(Reservation $reservation): void
    {
        if ($reservation->wasChanged()) {
            $this->syncViaKafka('updated', $reservation);
        }
    }

    public function deleted(Reservation $reservation): void
    {
        $this->syncViaKafka('deleted', $reservation);
    }

    private function syncViaKafka(string $action, Reservation $reservation): void
    {
        $this->kafkaProducer->publishReservation($action, $reservation->toArray(), $reservation->id);
    }
}
