<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PosInvoice extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order)
    {
        $this->order->loadMissing('items', 'venue', 'reservation.guest', 'payments');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Facture '.($this->order->invoice_number ?? $this->order->reference).' — '.config('hotel.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.pos-invoice', with: [
            'order' => $this->order,
            'url' => route('admin.pos.invoice', $this->order),
        ]);
    }
}
