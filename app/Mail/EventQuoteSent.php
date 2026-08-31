<?php

namespace App\Mail;

use App\Models\EventQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventQuoteSent extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public EventQuote $quote)
    {
        $this->quote->loadMissing('items', 'lead');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre devis '.$this->quote->reference.' — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.event-quote', with: ['quote' => $this->quote]);
    }
}
