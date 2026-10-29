<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email notifikasi dokumen student (info upload ke admin, pengingat, dokumen lengkap).
 * Isinya teks yang sama dengan pesan WhatsApp, ditampilkan rapi dengan tombol opsional.
 */
class DocumentNotificationMail extends Mailable
{
    /** @param  array<int, string>  $lines */
    public function __construct(
        public string $subjectLine,
        public array $lines,
        public ?string $buttonUrl = null,
        public ?string $buttonLabel = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.document-notification');
    }
}
