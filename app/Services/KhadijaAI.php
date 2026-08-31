<?php

namespace App\Services;

use App\Models\StockItem;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Assistant KHADJA AI : questions/réponses en langage naturel sur les indicateurs
 * de gestion. Lecture seule. Appel HTTP direct à l'API Claude (même approche que
 * les intégrations Sms / paiements du projet) ; inactif tant qu'`ANTHROPIC_API_KEY`
 * n'est pas défini.
 */
class KhadijaAI
{
    public static function configured(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * @return array{ok:bool, answer:string}
     */
    public static function ask(string $question, User $user): array
    {
        if (! self::configured()) {
            return ['ok' => false, 'answer' => "L'assistant n'est pas configuré (clé ANTHROPIC_API_KEY manquante)."];
        }

        $question = trim($question);
        if ($question === '') {
            return ['ok' => false, 'answer' => 'Posez une question.'];
        }

        $system = "Tu es l'assistant de gestion de l'Hôtel Résidence Khadija (Thiès, Sénégal). "
            ."Réponds en français, de façon concise et chiffrée, en t'appuyant UNIQUEMENT sur les données ci-dessous. "
            ."Si l'information demandée n'y figure pas, dis-le clairement. Montants en FCFA. "
            .'Ne donne pas de conseil hors du périmètre hôtelier / restauration.'
            ."\n\n=== DONNÉES ACTUELLES ===\n".self::context();

        try {
            $response = Http::withHeaders([
                'x-api-key' => config('services.anthropic.key'),
                'anthropic-version' => '2023-06-01',
            ])->timeout(20)->post(rtrim((string) config('services.anthropic.base_url'), '/').'/v1/messages', [
                'model' => config('services.anthropic.model', 'claude-sonnet-5'),
                'max_tokens' => 1024,
                'system' => $system,
                'messages' => [['role' => 'user', 'content' => $question]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('KhadijaAI: appel échoué', ['error' => $e->getMessage()]);

            return ['ok' => false, 'answer' => "L'assistant est momentanément indisponible."];
        }

        if ($response->failed()) {
            Log::warning('KhadijaAI: réponse API en erreur', ['status' => $response->status(), 'body' => $response->body()]);

            return ['ok' => false, 'answer' => "L'assistant est momentanément indisponible."];
        }

        if ($response->json('stop_reason') === 'refusal') {
            return ['ok' => false, 'answer' => 'Je ne peux pas répondre à cette demande.'];
        }

        $text = collect($response->json('content', []))
            ->where('type', 'text')->pluck('text')->implode("\n");

        return ['ok' => filled($text), 'answer' => $text ?: "Pas de réponse de l'assistant."];
    }

    /** Bloc de données compact injecté dans le prompt. */
    private static function context(): string
    {
        $today = Carbon::today();
        $d7 = $today->copy()->subDays(6);
        $d30 = $today->copy()->subDays(29);

        $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';

        $s0 = Analytics::posSales($today, $today);
        $s7 = Analytics::posSales($d7, $today);
        $s30 = Analytics::posSales($d30, $today);
        $occ = Analytics::occupancy($today, $today);
        $pay = Analytics::payments($d30, $today);
        $best = collect(Analytics::posBestsellers($d30, $today))->take(8);

        $lowStock = StockItem::active()->get()->filter->isBelowThreshold()
            ->map(fn ($i) => "- {$i->name} : ".rtrim(rtrim(number_format($i->onHand(), 2, ',', ' '), '0'), ',')." (seuil {$i->min_qty})")
            ->implode("\n");

        $line = fn ($label, $s) => "{$label} : CA {$money($s['gross'])}, {$s['orders']} tickets, "
            ."panier moyen {$money($s['avg_check'])}, remises {$money($s['discounts'])}, remboursements {$money($s['refunds'])}.";

        $byType = collect($s30['by_type'] ?? [])->map(fn ($v, $k) => "{$k} {$money($v['total'])}")->implode(' · ');
        $byMethod = collect($s30['by_method'] ?? [])->map(fn ($v, $k) => "{$k} {$money($v)}")->implode(' · ');

        $bestLines = $best->map(fn ($b) => "- {$b['name']} : {$b['qty']} vendus, CA {$money($b['revenue'])}, "
            .'marge '.($b['margin_pct'] === null ? 'n/d' : "{$b['margin_pct']} %").' ('.$money($b['margin']).')')
            ->implode("\n");

        return implode("\n", array_filter([
            'Date : '.$today->translatedFormat('l d F Y'),
            $line("Aujourd'hui (POS)", $s0),
            $line('7 derniers jours (POS)', $s7),
            $line('30 derniers jours (POS)', $s30),
            "Ventilation 30 j par type : {$byType}",
            "Ventilation 30 j par moyen : {$byMethod}",
            "Occupation du jour : {$occ['occupancy']} % · ADR {$money($occ['adr'])} · RevPAR {$money($occ['revpar'])}.",
            "Paiements en ligne 30 j : {$money($pay['online_amount'])} ({$pay['success_rate']} % de succès), remboursés {$money($pay['refunded'])}.",
            "Meilleures ventes 30 j (marge d'après recette) :\n{$bestLines}",
            $lowStock ? "Stock sous le seuil :\n{$lowStock}" : 'Stock : aucun article sous le seuil.',
        ]));
    }
}
