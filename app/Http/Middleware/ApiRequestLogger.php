<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        Log::channel('request_audit')->info('API request handled', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'payload' => $this->sanitizePayload($request),
        ]);

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizePayload(Request $request): array
    {
        if (!$request->isJson()) {
            return [];
        }

        /** @var array<string, mixed> $payload */
        $payload = $request->all();

        if (array_key_exists('comment', $payload)) {
            $payload['comment_length'] = mb_strlen((string) $payload['comment']);
            unset($payload['comment']);
        }

        return $payload;
    }
}
