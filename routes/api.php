<?php

use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);
Route::get('/metrics', MetricsController::class);
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact-form');
