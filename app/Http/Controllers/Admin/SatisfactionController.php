<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Reservation;
use App\Models\SatisfactionSurvey;
use App\Services\Analytics;
use App\Services\Satisfaction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SatisfactionController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = Analytics::resolveRange(
            $request->query('period', '90d'),
            $request->query('from'),
            $request->query('to'),
        );

        $stats = Satisfaction::stats($from, $to);
        $trendSvg = Analytics::sparkline(array_column($stats['trend'], 'value'));

        $filter = $request->query('filter', 'all');

        $responses = SatisfactionSurvey::query()
            ->with(['guest', 'reservation:id,reference', 'handledBy:id,name'])
            ->completed()
            ->when($filter === 'attention', fn ($q) => $q->where(fn ($w) => $w
                ->where('rating_overall', '<=', (int) config('satisfaction.alert_at_or_below', 3))
                ->orWhere('nps_score', '<=', 6)))
            ->when($filter === 'published', fn ($q) => $q->where('is_published', true))
            ->when($filter === 'to_publish', fn ($q) => $q->where('consent_publish', true)
                ->where('is_published', false)->whereNotNull('comment'))
            ->when($filter === 'open', fn ($q) => $q->where('status', '!=', 'triaged'))
            ->latest('completed_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.satisfaction.index', [
            'stats' => $stats,
            'trendSvg' => $trendSvg,
            'responses' => $responses,
            'categories' => config('satisfaction.categories'),
            'from' => $from,
            'to' => $to,
            'period' => $request->query('period', '90d'),
            'filter' => $filter,
        ]);
    }

    public function show(SatisfactionSurvey $survey)
    {
        $survey->load(['guest', 'reservation.roomCategory', 'handledBy']);

        return view('admin.satisfaction.show', [
            'survey' => $survey,
            'categories' => config('satisfaction.categories'),
        ]);
    }

    public function update(Request $request, SatisfactionSurvey $survey)
    {
        $data = $request->validate([
            'staff_note' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['received', 'triaged'])],
        ]);

        $survey->update([
            'staff_note' => $data['staff_note'] ?? null,
            'status' => $data['status'],
            'handled_by' => $request->user()->id,
            'handled_at' => now(),
        ]);
        AuditLog::record('satisfaction.updated', $survey, ['status' => $data['status']]);

        return back()->with('status', 'Avis mis à jour.');
    }

    public function togglePublish(Request $request, SatisfactionSurvey $survey)
    {
        if (! $survey->is_published) {
            abort_unless($survey->consent_publish && filled($survey->comment), 422,
                'Publication impossible : pas d’accord client ou pas de commentaire.');

            $survey->update(['is_published' => true, 'published_at' => now()]);
            $msg = 'Avis publié sur le site.';
        } else {
            $survey->update(['is_published' => false, 'published_at' => null]);
            $msg = 'Avis retiré du site.';
        }
        AuditLog::record('satisfaction.publish', $survey, ['published' => $survey->is_published]);

        return back()->with('status', $msg);
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'reference' => ['required', 'string', 'max:40'],
        ]);

        $reservation = Reservation::with('guest')
            ->where('reference', trim($data['reference']))
            ->first();

        if (! $reservation) {
            return back()->withErrors(['reference' => 'Réservation introuvable.']);
        }

        $survey = Satisfaction::inviteForStay($reservation, 'manual');

        if (! $survey) {
            return back()->withErrors(['reference' => 'Invitation impossible : le séjour doit être terminé, avoir un e-mail client, et ne pas déjà avoir été invité.']);
        }

        AuditLog::record('satisfaction.invited', $survey, ['reference' => $reservation->reference]);

        return back()->with('status', "Enquête envoyée à {$reservation->guest->email}.");
    }
}
