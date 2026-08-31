<?php

namespace App\Mail;

use App\Models\Guest;
use App\Models\MarketingCampaign;
use App\Services\CampaignDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMessage extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public MarketingCampaign $campaign, public Guest $guest) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('marketing.sender.email_from'),
                config('marketing.sender.email_name'),
            ),
            subject: CampaignDispatcher::renderString($this->campaign->subject ?: $this->campaign->name, $this->campaign, $this->guest),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.campaign', with: [
            'campaign' => $this->campaign,
            'bodyText' => CampaignDispatcher::render($this->campaign, $this->guest),
        ]);
    }
}
