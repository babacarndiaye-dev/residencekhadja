<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationCancelled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing('guest', 'roomCategory');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Annulation de votre réservation '.$this->reservation->reference.' — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reservation-cancelled');
    }
}
