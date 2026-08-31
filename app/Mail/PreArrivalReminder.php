<?php

namespace App\Mail;

use App\Models\Reservation;
use App\Services\GuestApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreArrivalReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing('guest', 'roomCategory');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre arrivée approche — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pre-arrival', with: [
            'reservation' => $this->reservation,
            'appUrl' => in_array($this->reservation->status, GuestApp::ACCESSIBLE, true)
                ? GuestApp::magicUrl($this->reservation)
                : null,
        ]);
    }
}
