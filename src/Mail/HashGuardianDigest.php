<?php

namespace Hashcrypttech\HashGuardian\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class HashGuardianDigest extends Mailable
{
    public function __construct(public array $reportData) {}

    public function envelope(): Envelope
    {
        $period = ucfirst($this->reportData['period'] ?? 'Daily');
        return new Envelope(subject: "HashGuardian {$period} Digest");
    }

    public function content(): Content
    {
        return new Content(view: 'hashguardian::emails.digest');
    }
}
