<?php

namespace App\Services;

class HeuristicContactInsightsService
{
    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function analyze(array $payload): array
    {
        $comment = mb_strtolower((string) ($payload['comment'] ?? ''));

        $positiveWords = ['спасибо', 'отлично', 'интересует', 'хочу', 'добрый', 'great', 'thanks'];
        $negativeWords = ['проблема', 'ошибка', 'срочно', 'плохо', 'недоволен', 'bug', 'issue'];

        $positiveHits = $this->countMatches($comment, $positiveWords);
        $negativeHits = $this->countMatches($comment, $negativeWords);

        $sentiment = 'neutral';
        if ($positiveHits > $negativeHits) {
            $sentiment = 'positive';
        } elseif ($negativeHits > $positiveHits) {
            $sentiment = 'negative';
        }

        $category = 'general';
        if ($this->containsAny($comment, ['ai', 'openai', 'anthropic', 'интеграц', 'api'])) {
            $category = 'integration';
        } elseif ($this->containsAny($comment, ['ошибка', 'bug', 'support', 'поддерж', 'не работает'])) {
            $category = 'support';
        } elseif ($this->containsAny($comment, ['работа', 'ваканс', 'hire', 'job'])) {
            $category = 'hiring';
        } elseif ($this->containsAny($comment, ['партнер', 'сотруднич', 'partnership'])) {
            $category = 'partnership';
        }

        return [
            'sentiment' => $sentiment,
            'category' => $category,
            'summary' => $this->buildSummary($category, $sentiment),
            'reply' => $this->buildReply((string) ($payload['name'] ?? 'there'), $category),
        ];
    }

    /**
     * @param list<string> $needles
     */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $needles
     */
    private function countMatches(string $haystack, array $needles): int
    {
        $count = 0;

        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                $count++;
            }
        }

        return $count;
    }

    private function buildSummary(string $category, string $sentiment): string
    {
        return sprintf('Request categorized as %s with %s sentiment.', $category, $sentiment);
    }

    private function buildReply(string $name, string $category): string
    {
        $topics = [
            'integration' => 'AI and backend integration',
            'support' => 'the technical issue',
            'hiring' => 'the opportunity',
            'partnership' => 'potential collaboration',
            'general' => 'your request',
        ];

        return sprintf(
            'Hello, %s! Thank you for your message. We received your request about %s and will get back to you soon.',
            $name,
            $topics[$category] ?? $topics['general']
        );
    }
}
