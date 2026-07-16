<?php

namespace App\Repositories;

use App\Repositories\Contracts\MetricsRepositoryInterface;
use Illuminate\Filesystem\Filesystem;

class FileMetricsRepository implements MetricsRepositoryInterface
{
    private string $metricsPath;

    public function __construct(private readonly Filesystem $files)
    {
        $this->metricsPath = storage_path('app/private/contact-metrics.json');
    }

    /**
     * @return array<string, mixed>
     */
    public function get(): array
    {
        $this->ensureExists();

        $content = $this->files->get($this->metricsPath);
        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : $this->defaultMetrics();
    }

    /**
     * @param array<string, mixed> $insights
     */
    public function increment(array $insights): void
    {
        $metrics = $this->get();

        $metrics['total_requests']++;
        $metrics['last_submission_at'] = now()->toIso8601String();

        $sentiment = (string) ($insights['sentiment'] ?? 'unknown');
        $category = (string) ($insights['category'] ?? 'general');
        $provider = (string) ($insights['ai_provider'] ?? 'heuristic');

        $metrics['sentiments'][$sentiment] = ($metrics['sentiments'][$sentiment] ?? 0) + 1;
        $metrics['categories'][$category] = ($metrics['categories'][$category] ?? 0) + 1;
        $metrics['ai_providers'][$provider] = ($metrics['ai_providers'][$provider] ?? 0) + 1;

        if (($insights['fallback_used'] ?? false) === true) {
            $metrics['fallback_uses']++;
        }

        $this->files->put(
            $this->metricsPath,
            json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function ensureExists(): void
    {
        $directory = dirname($this->metricsPath);

        if (!$this->files->exists($directory)) {
            $this->files->makeDirectory($directory, 0755, true);
        }

        if (!$this->files->exists($this->metricsPath)) {
            $this->files->put(
                $this->metricsPath,
                json_encode($this->defaultMetrics(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultMetrics(): array
    {
        return [
            'total_requests' => 0,
            'fallback_uses' => 0,
            'last_submission_at' => null,
            'sentiments' => [],
            'categories' => [],
            'ai_providers' => [],
        ];
    }
}
