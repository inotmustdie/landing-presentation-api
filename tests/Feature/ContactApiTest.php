<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'file');
        Config::set('services.contact.owner_email', 'owner@example.com');
        @unlink(storage_path('app/private/contact-metrics.json'));
    }

    public function test_contact_request_is_processed_successfully(): void
    {
        Mail::fake();

        Config::set('services.openai.enabled', true);
        Config::set('services.openai.api_key', 'test-key');

        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'sentiment' => 'positive',
                                'category' => 'integration',
                                'summary' => 'AI tagged this as an integration request.',
                                'reply' => 'Thanks for your interest. We will contact you shortly.',
                            ], JSON_UNESCAPED_UNICODE),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/contact', $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('data.sentiment', 'positive')
            ->assertJsonPath('data.category', 'integration')
            ->assertJsonPath('data.ai_provider', 'openai')
            ->assertJsonPath('data.fallback_used', false);

        Mail::assertSentCount(2);
    }

    public function test_contact_request_returns_validation_error(): void
    {
        $response = $this->postJson('/api/contact', [
            'name' => 'A',
            'phone' => '123',
            'email' => 'wrong-email',
            'comment' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'phone', 'email', 'comment']);
    }

    public function test_contact_request_uses_fallback_when_ai_is_unavailable(): void
    {
        Mail::fake();

        Config::set('services.openai.enabled', true);
        Config::set('services.openai.api_key', 'test-key');

        Http::fake(function () {
            throw new \RuntimeException('AI timeout');
        });

        $response = $this->postJson('/api/contact', $this->validPayload([
            'comment' => 'Добрый день! Хочу обсудить AI интеграцию для вашего backend API.',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.ai_provider', 'heuristic')
            ->assertJsonPath('data.fallback_used', true)
            ->assertJsonPath('data.category', 'integration');
    }

    public function test_contact_request_is_rate_limited(): void
    {
        Mail::fake();

        Config::set('services.openai.enabled', false);
        Config::set('services.contact.rate_limit_max_attempts', 2);
        Config::set('services.contact.rate_limit_decay_seconds', 600);

        $payload = $this->validPayload(['email' => 'limit@example.com']);

        $this->postJson('/api/contact', $payload)->assertCreated();
        $this->postJson('/api/contact', $payload)->assertCreated();
        $this->postJson('/api/contact', $payload)->assertStatus(429);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ivan Petrov',
            'phone' => '+79991234567',
            'email' => 'ivan@example.com',
            'comment' => 'Hello! I would like to discuss AI integration for a backend service.',
        ], $overrides);
    }
}
