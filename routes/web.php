<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'docs' => url('/docs'),
        'openapi' => url('/openapi.json'),
        'health' => url('/api/health'),
    ]);
});

Route::view('/docs', 'docs');
Route::get('/openapi.json', fn () => response()->file(public_path('docs/openapi.json'), [
    'Content-Type' => 'application/json',
]));
