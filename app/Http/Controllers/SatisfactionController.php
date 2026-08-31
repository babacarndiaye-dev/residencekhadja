<?php

namespace App\Http\Controllers;

use App\Models\SatisfactionSurvey;
use App\Services\Satisfaction;
use Illuminate\Http\Request;

/**
 * Enquête de satisfaction publique — accès par jeton, sans compte (/avis/{token}).
 */
class SatisfactionController extends Controller
{
    public function show(string $token)
    {
        $survey = SatisfactionSurvey::with('reservation.roomCategory', 'guest')
            ->where('token', $token)->firstOrFail();

        if ($survey->completed_at) {
            return redirect()->route('satisfaction.done', $survey->token);
        }

        abort_unless($survey->isOpen(), 410, 'Ce lien d’enquête a expiré.');

        return view('pages.satisfaction.form', [
            'survey' => $survey,
            'categories' => config('satisfaction.categories'),
            'npsQuestion' => config('satisfaction.nps_question'),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $survey = SatisfactionSurvey::where('token', $token)->firstOrFail();

        if ($survey->completed_at) {
            return redirect()->route('satisfaction.done', $survey->token);
        }
        abort_unless($survey->isOpen(), 410, 'Ce lien d’enquête a expiré.');

        $data = $request->validate([
            'rating_overall' => ['required', 'integer', 'between:1,5'],
            'nps_score' => ['nullable', 'integer', 'between:0,10'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
            'consent_publish' => ['nullable', 'boolean'],
            'author_label' => ['nullable', 'string', 'max:120'],
        ], [], [
            'rating_overall' => 'note globale',
        ]);

        Satisfaction::record($survey, $data, $request->ip());

        return redirect()->route('satisfaction.done', $survey->token);
    }

    public function done(string $token)
    {
        $survey = SatisfactionSurvey::where('token', $token)->firstOrFail();
        abort_unless((bool) $survey->completed_at, 404);

        return view('pages.satisfaction.thanks', [
            'survey' => $survey,
            'promoter' => $survey->isPromoter(),
            'googleUrl' => config('satisfaction.google_review_url'),
            'tripadvisorUrl' => config('satisfaction.tripadvisor_url'),
        ]);
    }
}
