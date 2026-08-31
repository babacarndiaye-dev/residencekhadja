<?php

namespace App\Services;

use App\Mail\SatisfactionInvitation;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\SatisfactionSurvey;
use App\Support\Notify;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Enquête de satisfaction post-séjour + agrégats e-réputation.
 *
 *  - inviteForStay()  : crée l'invitation + met l'e-mail en file (idempotent par séjour)
 *  - record()         : enregistre la réponse, alerte l'équipe si détracteur / note basse
 *  - stats()          : NPS, note moyenne, taux de réponse, répartition, tendance
 *  - vitrineReviews() : avis publiés au format config/reviews.php (repli sur le config)
 */
class Satisfaction
{
    /**
     * Crée (une seule fois) l'invitation d'enquête d'un séjour terminé et met
     * l'e-mail en file. Renvoie null si impossible (pas d'e-mail, déjà invité,
     * séjour non terminé).
     */
    public static function inviteForStay(Reservation $reservation, string $channel = 'post_stay'): ?SatisfactionSurvey
    {
        $reservation->loadMissing('guest');

        if ($reservation->status !== 'checked_out' || blank($reservation->guest?->email)) {
            return null;
        }

        if (SatisfactionSurvey::where('reservation_id', $reservation->id)->exists()) {
            return null;
        }

        $survey = SatisfactionSurvey::create([
            'hotel_id' => $reservation->hotel_id ?? Hotel::current()->id,
            'reservation_id' => $reservation->id,
            'guest_id' => $reservation->guest_id,
            'token' => self::freshToken(),
            'channel' => $channel,
            'sent_at' => now(),
            'expires_on' => now()->addDays((int) config('satisfaction.link_ttl_days', 30))->toDateString(),
            'status' => 'pending',
        ]);

        Mail::to($reservation->guest->email)->queue(new SatisfactionInvitation($survey));

        return $survey;
    }

    /** Relance unique des invitations sans réponse. Renvoie le nombre d'e-mails remis en file. */
    public static function remindStale(?Carbon $now = null): int
    {
        $now ??= now();
        $after = (int) config('satisfaction.reminder_after_days', 5);
        if ($after <= 0) {
            return 0;
        }

        $due = SatisfactionSurvey::awaitingResponse()
            ->whereNull('reminded_at')
            ->whereNotNull('sent_at')
            ->where('sent_at', '<=', $now->copy()->subDays($after))
            ->where(fn ($q) => $q->whereNull('expires_on')->orWhereDate('expires_on', '>=', $now->toDateString()))
            ->with('guest')
            ->get();

        $sent = 0;
        foreach ($due as $survey) {
            if (blank($survey->guest?->email)) {
                continue;
            }
            Mail::to($survey->guest->email)->queue(new SatisfactionInvitation($survey, reminder: true));
            $survey->forceFill(['reminded_at' => $now])->saveQuietly();
            $sent++;
        }

        return $sent;
    }

    /** Passe en « expiré » les invitations sans réponse dont le lien est périmé. */
    public static function expireStale(?Carbon $now = null): int
    {
        $now ??= now();

        return SatisfactionSurvey::awaitingResponse()
            ->whereNotNull('expires_on')
            ->whereDate('expires_on', '<', $now->toDateString())
            ->update(['status' => 'expired']);
    }

    /** Enregistre la réponse du client. */
    public static function record(SatisfactionSurvey $survey, array $data, ?string $ip = null): SatisfactionSurvey
    {
        $categories = [];
        foreach (array_keys(config('satisfaction.categories', [])) as $key) {
            if (isset($data['categories'][$key]) && $data['categories'][$key] !== null && $data['categories'][$key] !== '') {
                $categories[$key] = max(1, min(5, (int) $data['categories'][$key]));
            }
        }

        $consent = (bool) ($data['consent_publish'] ?? false);

        $survey->forceFill([
            'rating_overall' => max(1, min(5, (int) $data['rating_overall'])),
            'nps_score' => isset($data['nps_score']) && $data['nps_score'] !== null && $data['nps_score'] !== ''
                ? max(0, min(10, (int) $data['nps_score']))
                : null,
            'category_ratings' => $categories ?: null,
            'comment' => filled($data['comment'] ?? null) ? trim($data['comment']) : null,
            'consent_publish' => $consent,
            'author_label' => $consent && filled($data['author_label'] ?? null) ? trim($data['author_label']) : null,
            'completed_at' => now(),
            'status' => 'received',
            'source_ip' => $ip,
        ])->save();

        if ($survey->needsAttention()) {
            Notify::roles(
                ['reception', 'direction'],
                'Avis client à traiter',
                $survey->displayAuthor().' — note '.$survey->rating_overall.'/5'
                    .($survey->isDetractor() ? ' · détracteur (NPS '.$survey->nps_score.')' : ''),
                route('admin.satisfaction.show', $survey, false),
                level: 'warning',
                icon: '⚠️',
            );
        } else {
            Notify::roles(
                ['reception', 'direction', 'marketing'],
                'Nouvel avis client',
                $survey->displayAuthor().' — note '.$survey->rating_overall.'/5',
                route('admin.satisfaction.show', $survey, false),
                level: 'success',
                icon: '⭐',
            );
        }

        return $survey;
    }

    /**
     * Agrégats sur la période (bornes sur completed_at pour les réponses,
     * sur sent_at pour le taux de réponse).
     *
     * @return array<string,mixed>
     */
    public static function stats(Carbon $from, Carbon $to): array
    {
        $hotelId = Hotel::current()->id;
        $fromTs = $from->copy()->startOfDay();
        $toTs = $to->copy()->endOfDay();

        $sent = SatisfactionSurvey::where('hotel_id', $hotelId)
            ->whereNotNull('sent_at')->whereBetween('sent_at', [$fromTs, $toTs])->count();

        $done = SatisfactionSurvey::where('hotel_id', $hotelId)
            ->whereNotNull('completed_at')->whereBetween('completed_at', [$fromTs, $toTs])->get();

        $withNps = $done->whereNotNull('nps_score');
        $promoters = $withNps->where('nps_score', '>=', 9)->count();
        $detractors = $withNps->where('nps_score', '<=', 6)->count();
        $npsBase = $withNps->count();

        $categories = [];
        foreach (config('satisfaction.categories', []) as $key => $label) {
            $vals = $done->map(fn ($s) => $s->categoryRating($key))->filter(fn ($v) => $v !== null);
            $categories[$key] = [
                'label' => $label,
                'avg' => $vals->isNotEmpty() ? round($vals->avg(), 2) : null,
                'count' => $vals->count(),
            ];
        }

        $distribution = [];
        for ($i = 5; $i >= 1; $i--) {
            $distribution[$i] = $done->where('rating_overall', $i)->count();
        }

        // Tendance : moyenne hebdomadaire de la note globale (8 derniers points).
        $trend = $done
            ->groupBy(fn ($s) => $s->completed_at->startOfWeek()->toDateString())
            ->map(fn ($rows, $week) => [
                'label' => Carbon::parse($week)->translatedFormat('d M'),
                'value' => round($rows->avg('rating_overall'), 2),
            ])
            ->sortKeys()
            ->values()
            ->take(-8)
            ->all();

        return [
            'sent' => $sent,
            'completed' => $done->count(),
            'response_rate' => $sent > 0 ? round($done->count() / $sent * 100) : null,
            'avg_overall' => $done->whereNotNull('rating_overall')->isNotEmpty()
                ? round($done->avg('rating_overall'), 2) : null,
            'nps' => $npsBase > 0 ? (int) round(($promoters - $detractors) / $npsBase * 100) : null,
            'nps_base' => $npsBase,
            'promoters' => $promoters,
            'passives' => $npsBase - $promoters - $detractors,
            'detractors' => $detractors,
            'categories' => $categories,
            'distribution' => $distribution,
            'trend' => $trend,
            'pending' => SatisfactionSurvey::where('hotel_id', $hotelId)->awaitingResponse()->count(),
            'to_handle' => $done->filter(fn ($s) => $s->needsAttention() && $s->status !== 'triaged')->count(),
            'published' => SatisfactionSurvey::where('hotel_id', $hotelId)->published()->count(),
        ];
    }

    /**
     * Avis pour la vitrine, au format attendu par la vue d'accueil
     * (config/reviews.php). Repli sur le config tant qu'il y a peu d'avis publiés.
     *
     * @return array{summary:array,items:array<int,array>}
     */
    public static function vitrineReviews(): array
    {
        $limit = (int) config('satisfaction.public_reviews_count', 8);

        $published = SatisfactionSurvey::published()
            ->with(['guest', 'reservation.guest'])
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();

        $items = $published->map(fn (SatisfactionSurvey $s) => [
            'name' => $s->displayAuthor(),
            'origin' => optional($s->guest ?? $s->reservation?->guest)->city ?: 'Séjour vérifié',
            'trip' => 'Séjour vérifié',
            'rating' => $s->rating_overall ?? 5,
            'text' => $s->comment,
        ])->filter(fn ($r) => filled($r['text']))->values()->all();

        $fallback = config('reviews.items', []);
        if (count($items) < 4) {
            $items = array_merge($items, array_slice($fallback, 0, 4 - count($items)));
        }

        $allCompleted = SatisfactionSurvey::completed()->get(['rating_overall']);
        $summary = $allCompleted->count() >= 10
            ? [
                'rating' => round($allCompleted->avg('rating_overall'), 1),
                'count' => $allCompleted->count(),
                'source' => 'Séjours vérifiés',
            ]
            : config('reviews.summary');

        return ['summary' => $summary, 'items' => array_slice($items, 0, $limit)];
    }

    private static function freshToken(): string
    {
        do {
            $token = Str::lower(Str::random(40));
        } while (SatisfactionSurvey::where('token', $token)->exists());

        return $token;
    }
}
