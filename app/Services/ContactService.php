<?php

namespace App\Services;

use App\DTO\ContactSubmissionData;
use App\Exceptions\ApiException;
use App\Mail\ContactOwnerNotification;
use App\Mail\ContactUserCopy;
use App\Repositories\Contracts\MetricsRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ContactService
{
    public function __construct(
        private readonly AiContactInsightsService $aiContactInsightsService,
        private readonly MetricsRepositoryInterface $metricsRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    public function submit(array $validated, ?string $ipAddress, ?string $userAgent): array
    {
        $contact = ContactSubmissionData::fromArray(
            validated: $validated,
            contactId: (string) Str::uuid(),
            ipAddress: $ipAddress ?? 'unknown',
            userAgent: $userAgent,
        );

        $insights = $this->aiContactInsightsService->analyze($contact->toArray());

        try {
            Mail::to((string) config('services.contact.owner_email'))
                ->send(new ContactOwnerNotification($contact, $insights));

            Mail::to($contact->email)
                ->send(new ContactUserCopy($contact, $insights));
        } catch (Throwable $exception) {
            throw new ApiException('Unable to send notification emails.', 502, [
                'reason' => $exception->getMessage(),
            ]);
        }

        $this->metricsRepository->increment($insights);

        return [
            'contact_id' => $contact->contactId,
            'sentiment' => $insights['sentiment'],
            'category' => $insights['category'],
            'summary' => $insights['summary'],
            'ai_provider' => $insights['ai_provider'],
            'fallback_used' => $insights['fallback_used'],
            'fallback_reason' => $insights['fallback_reason'],
        ];
    }
}
