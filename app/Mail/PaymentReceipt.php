<?php

namespace App\Mail;

use App\Models\PaymentIntent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentIntent $intent)
    {
        $this->intent->loadMissing('payable');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reçu de paiement '.$this->intent->reference.' — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-receipt', with: [
            'intent' => $this->intent,
            'receiptUrl' => route('pay.receipt', $this->intent->reference),
        ]);
    }
}
