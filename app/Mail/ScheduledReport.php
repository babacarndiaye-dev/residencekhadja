<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $label,
        public string $periodStart,
        public string $periodEnd,
        public int $rowCount,
        public string $csv,
        public string $filename,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Rapport « '.$this->label.' » — '.$this->periodStart.' → '.$this->periodEnd,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.scheduled-report');
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => "\xEF\xBB\xBF".$this->csv, $this->filename)
                ->withMime('text/csv'),
        ];
    }
}
