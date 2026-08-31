<?php

namespace App\Services;

use App\Mail\CampaignMessage;
use App\Models\CampaignRecipient;
use App\Models\Guest;
use App\Models\MarketingCampaign;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Construit la liste de destinataires d'une campagne puis l'envoie (§20, §55).
 * E-mail → Mailable en file d'attente ; SMS → App\Services\Sms (driver « log »
 * par défaut). Le consentement marketing est obligatoire ; les clients sans
 * adresse pour le canal sont ignorés (statut « skipped »).
 */
class CampaignDispatcher
{
    /** (Re)construit la liste des destinataires. Renvoie le nombre retenu. */
    public static function build(MarketingCampaign $campaign): int
    {
        $definition = $campaign->segment?->definition ?? ['opted_in' => true];
        // Le consentement marketing est toujours imposé, même si le segment ne le précise pas.
        $definition['opted_in'] = true;

        $guests = Segmentation::query($definition)->get();

        DB::transaction(function () use ($campaign, $guests) {
            $campaign->recipients()->delete();

            $rows = $guests->map(function (Guest $g) use ($campaign) {
                $address = $campaign->channel === 'sms' ? $g->phone : $g->email;

                return [
                    'campaign_id' => $campaign->id,
                    'guest_id' => $g->id,
                    'address' => $address,
                    'status' => $address ? 'queued' : 'skipped',
                    'reason' => $address ? null : 'Aucune adresse '.$campaign->channel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            foreach (array_chunk($rows, 500) as $chunk) {
                CampaignRecipient::insert($chunk);
            }
        });

        return $campaign->recipients()->where('status', 'queued')->count();
    }

    /** Envoie la campagne : marque les destinataires et fige les statistiques. */
    public static function send(MarketingCampaign $campaign): MarketingCampaign
    {
        if ($campaign->status === 'sent') {
            throw ValidationException::withMessages(['campaign' => 'Campagne déjà envoyée.']);
        }
        if ($campaign->status === 'cancelled') {
            throw ValidationException::withMessages(['campaign' => 'Campagne annulée.']);
        }

        if ($campaign->recipients()->count() === 0) {
            self::build($campaign);
        }

        $sent = 0;
        $campaign->recipients()->with('guest')->where('status', 'queued')->chunkById(500, function ($chunk) use ($campaign, &$sent) {
            foreach ($chunk as $recipient) {
                if (! $recipient->guest) {
                    $recipient->update(['status' => 'skipped', 'reason' => 'Client introuvable']);

                    continue;
                }
                self::deliver($campaign, $recipient);
                $recipient->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            }
        });

        $stats = [
            'recipients' => $campaign->recipients()->count(),
            'sent' => $campaign->recipients()->where('status', 'sent')->count(),
            'skipped' => $campaign->recipients()->where('status', 'skipped')->count(),
        ];

        $campaign->update(['status' => 'sent', 'sent_at' => now(), 'stats' => $stats]);

        return $campaign->refresh();
    }

    /** Rendu du corps du message pour un client (jetons {prenom} {nom} {code}). */
    public static function render(MarketingCampaign $campaign, Guest $guest): string
    {
        return self::renderString($campaign->body, $campaign, $guest);
    }

    /** Substitue les jetons {prenom} {nom} {code} dans un texte arbitraire (corps ou objet). */
    public static function renderString(string $text, MarketingCampaign $campaign, Guest $guest): string
    {
        return strtr($text, [
            '{prenom}' => $guest->first_name,
            '{nom}' => $guest->last_name,
            '{code}' => $campaign->promoCode?->code ?? '',
        ]);
    }

    /** Transport réel : e-mail via Mailable en file, SMS via App\Services\Sms. */
    protected static function deliver(MarketingCampaign $campaign, CampaignRecipient $recipient): void
    {
        if ($campaign->channel === 'sms') {
            Sms::send($recipient->address, self::render($campaign, $recipient->guest));

            return;
        }

        Mail::to($recipient->address)->queue(new CampaignMessage($campaign, $recipient->guest));
    }
}
