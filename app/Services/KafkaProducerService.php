<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KafkaProducerService
{
    private string $producerUrl;

    public function __construct()
    {
        $this->producerUrl = env('KAFKA_PRODUCER_URL', 'http://localhost:5001');
    }

    /**
     * Publier un message vers Kafka via HTTP
     */
    private function publishToKafka(string $topic, array $payload): bool
    {
        try {
            $response = Http::timeout(10)->post("{$this->producerUrl}/publish", [
                'topic' => $topic,
                'message' => $payload
            ]);

            if ($response->successful()) {
                Log::info("✓ Published to Kafka", [
                    'topic' => $topic,
                    'response' => $response->json()
                ]);
                return true;
            }

            Log::error("✗ Kafka publish failed", [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error("✗ Kafka producer error", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function publishClient(string $action, array $clientData, int $clientId): bool
    {
        $payload = [
            'action' => $action,
            'entity' => 'client',
            'timestamp' => now()->toIso8601String(),
            'data' => $clientData
        ];

        return $this->publishToKafka('clients-sync', $payload);
    }

    public function publishReservation(string $action, array $reservationData, int $reservationId): bool
    {
        $payload = [
            'action' => $action,
            'entity' => 'reservation',
            'timestamp' => now()->toIso8601String(),
            'data' => $reservationData
        ];

        return $this->publishToKafka('reservations-sync', $payload);
    }

    public function testConnection(): array
    {
        try {
            $response = Http::timeout(5)->get("{$this->producerUrl}/health");

            return [
                'success' => $response->successful(),
                'message' => 'Kafka Producer is reachable'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Kafka Producer is not reachable',
                'error' => $e->getMessage()
            ];
        }
    }
}
