<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'app' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'mailer' => config('mail.default'),
            'ai' => [
                'enabled' => (bool) config('services.openai.enabled'),
                'configured' => filled(config('services.openai.api_key')),
                'model' => config('services.openai.model'),
            ],
        ]);
    }
}
