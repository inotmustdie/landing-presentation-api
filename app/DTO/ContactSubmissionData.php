<?php

namespace App\DTO;

class ContactSubmissionData
{
    public function __construct(
        public readonly string $contactId,
        public readonly string $name,
        public readonly string $phone,
        public readonly string $email,
        public readonly string $comment,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
    ) {
    }

    /**
     * @param array<string, mixed> $validated
     */
    public static function fromArray(array $validated, string $contactId, string $ipAddress, ?string $userAgent): self
    {
        return new self(
            contactId: $contactId,
            name: (string) $validated['name'],
            phone: (string) $validated['phone'],
            email: (string) $validated['email'],
            comment: (string) $validated['comment'],
            ipAddress: $ipAddress,
            userAgent: $userAgent,
        );
    }

    /**
     * @return array<string, string|null>
     */
    public function toArray(): array
    {
        return [
            'contact_id' => $this->contactId,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'comment' => $this->comment,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }
}
