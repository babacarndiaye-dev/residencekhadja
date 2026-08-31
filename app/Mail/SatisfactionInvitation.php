<?php

namespace App\Mail;

use App\Models\SatisfactionSurvey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SatisfactionInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public SatisfactionSurvey $survey,
        public bool $reminder = false,
    ) {
        $this->survey->loadMissing('guest', 'reservation.roomCategory');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->reminder
                ? 'Votre avis compte pour nous — '.config('hotel.name')
                : 'Comment s’est passé votre séjour à l’'.config('hotel.name').' ?',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.satisfaction-invitation', with: [
            'url' => qr_link('avis/'.$this->survey->token),
            'firstName' => $this->survey->guest?->first_name
                ?? $this->survey->reservation?->guest?->first_name
                ?? 'cher client',
        ]);
    }
}
