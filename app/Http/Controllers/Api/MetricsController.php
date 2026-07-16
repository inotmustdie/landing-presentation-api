<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\MetricsRepositoryInterface;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __construct(private readonly MetricsRepositoryInterface $metricsRepository)
    {
    }

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->metricsRepository->get(),
        ]);
    }
}
