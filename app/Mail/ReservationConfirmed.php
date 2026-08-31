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

class ReservationConfirmed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation)
    {
        $this->reservation->loadMissing('guest', 'roomCategory', 'ratePlan', 'room');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre réservation '.$this->reservation->reference
                .' — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        $r = $this->reservation;

        return new Content(view: 'emails.reservation-confirmed', with: [
            'reservation' => $r,
            'appUrl' => in_array($r->status, GuestApp::ACCESSIBLE, true) ? GuestApp::magicUrl($r) : null,
        ]);
    }
}
