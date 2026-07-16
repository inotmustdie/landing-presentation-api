<?php

namespace App\Mail;

use App\DTO\ContactSubmissionData;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContactOwnerNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<string, mixed> $insights
     */
    public function __construct(
        public readonly ContactSubmissionData $contact,
        public readonly array $insights,
    ) {
    }

    public function build(): self
    {
        return $this->subject('New contact request: '.$this->contact->name)
            ->view('emails.contact-owner');
    }
}
