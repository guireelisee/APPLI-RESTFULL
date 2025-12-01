<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\KafkaProducerService;

class ClientObserver
{
    public function __construct(
        private readonly KafkaProducerService $kafkaProducer
    ) {}

    public function created(Client $client): void
    {
        $this->syncViaKafka('created', $client);
    }

    public function updated(Client $client): void
    {
        if ($client->wasChanged()) {
            $this->syncViaKafka('updated', $client);
        }
    }

    public function deleted(Client $client): void
    {
        $this->syncViaKafka('deleted', $client);
    }

    private function syncViaKafka(string $action, Client $client): void
    {
        $this->kafkaProducer->publishClient($action, $client->toArray(), $client->id);
    }
}
