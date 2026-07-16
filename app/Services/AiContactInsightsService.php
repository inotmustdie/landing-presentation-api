<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AiContactInsightsService
{
    public function __construct(private readonly HeuristicContactInsightsService $heuristicService)
    {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function analyze(array $payload): array
    {
        $fallback = $this->heuristicService->analyze($payload);

        if (!(bool) config('services.openai.enabled') || blank(config('services.openai.api_key'))) {
            return $fallback + [
                'ai_provider' => 'heuristic',
                'fallback_used' => true,
                'fallback_reason' => 'ai_not_configured',
            ];
        }

        try {
            $response = Http::baseUrl(rtrim((string) config('services.openai.base_url'), '/'))
                ->timeout((int) config('services.openai.timeout', 12))
                ->withToken((string) config('services.openai.api_key'))
                ->acceptJson()
                ->post('/chat/completions', [
                    'model' => (string) config('services.openai.model'),
                    'temperature' => 0.2,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You analyze website contact requests. Return valid JSON only with keys sentiment, category, summary, reply. Sentiment must be one of: positive, neutral, negative. Category must be one of: integration, support, partnership, hiring, general.',
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'name' => $payload['name'] ?? '',
                                'email' => $payload['email'] ?? '',
                                'comment' => $payload['comment'] ?? '',
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                ]);

            if ($response->failed()) {
                throw new RuntimeException('AI provider returned HTTP '.$response->status());
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (!is_string($content) || $content === '') {
                throw new RuntimeException('AI provider returned an empty response.');
            }

            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($decoded)) {
                throw new RuntimeException('AI provider returned invalid JSON structure.');
            }

            return [
                'sentiment' => $this->normalizeSentiment((string) ($decoded['sentiment'] ?? 'neutral')),
                'category' => $this->normalizeCategory((string) ($decoded['category'] ?? 'general')),
                'summary' => (string) ($decoded['summary'] ?? $fallback['summary']),
                'reply' => (string) ($decoded['reply'] ?? $fallback['reply']),
                'ai_provider' => 'openai',
                'fallback_used' => false,
                'fallback_reason' => null,
            ];
        } catch (Throwable $exception) {
            Log::channel('ai_fallback')->warning('AI fallback activated', [
                'reason' => $exception->getMessage(),
                'email' => $payload['email'] ?? null,
            ]);

            return $fallback + [
                'ai_provider' => 'heuristic',
                'fallback_used' => true,
                'fallback_reason' => $exception->getMessage(),
            ];
        }
    }

    private function normalizeSentiment(string $sentiment): string
    {
        return in_array($sentiment, ['positive', 'neutral', 'negative'], true) ? $sentiment : 'neutral';
    }

    private function normalizeCategory(string $category): string
    {
        return in_array($category, ['integration', 'support', 'partnership', 'hiring', 'general'], true)
            ? $category
            : 'general';
    }
}
