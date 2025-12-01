<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Client;
use App\Models\Reservation;
use App\Observers\ClientObserver;
use App\Observers\ReservationObserver;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\KafkaProducerService::class);
    }

    public function boot(): void
    {
        Client::observe(ClientObserver::class);
        Reservation::observe(ReservationObserver::class);
    }
}
