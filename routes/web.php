<?php

use App\Http\Controllers\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Endpoint Prometheus
Route::get('/metrics', [MetricsController::class, 'index']);
