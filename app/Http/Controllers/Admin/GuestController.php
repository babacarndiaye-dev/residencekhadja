<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Hotel;
use App\Services\LoyaltyProgram;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $term = $request->query('q');
        $filter = $request->query('filter');

        $guests = Guest::query()
            ->with('loyaltyAccount.tier')
            ->withCount('reservations')
            ->when($term, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('last_name', 'like', "%{$term}%")
                ->orWhere('first_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('company', 'like', "%{$term}%")))
            ->when($filter === 'members', fn ($q) => $q->whereHas('loyaltyAccount'))
            ->when($filter === 'opted_in', fn ($q) => $q->where('marketing_opt_in', true))
            ->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.guests.index', compact('guests', 'term', 'filter'));
    }

    public function show(Guest $guest)
    {
        $guest->load([
            'reservations' => fn ($q) => $q->with('roomCategory')->latest('check_in'),
            'loyaltyAccount.tier',
            'loyaltyAccount.transactions' => fn ($q) => $q->limit(20),
            'interactions.user',
        ]);

        return view('admin.guests.show', [
            'guest' => $guest,
            'interactionTypes' => config('crm.interaction_types'),
            'sources' => config('crm.acquisition_sources'),
            'tagSuggestions' => config('crm.tag_suggestions'),
        ]);
    }

    public function update(Request $request, Guest $guest)
    {
        $data = $request->validate([
            'civility' => ['nullable', 'string', 'max:10'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:60'],
            'company' => ['nullable', 'string', 'max:120'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'acquisition_source' => ['nullable', Rule::in(array_keys(config('crm.acquisition_sources')))],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['tags'] = array_values(array_unique($data['tags'] ?? []));

        $guest->update($data);
        AuditLog::record('crm.guest.updated', $guest);

        return back()->with('status', 'Fiche client mise à jour.');
    }

    public function consent(Request $request, Guest $guest)
    {
        $optIn = $request->boolean('marketing_opt_in');
        $guest->update([
            'marketing_opt_in' => $optIn,
            'consent_updated_at' => now(),
        ]);
        AuditLog::record($optIn ? 'crm.consent.granted' : 'crm.consent.withdrawn', $guest);

        return back()->with('status', $optIn
            ? 'Consentement marketing enregistré.'
            : 'Consentement marketing retiré.');
    }

    public function enroll(Guest $guest)
    {
        $account = LoyaltyProgram::enroll($guest);
        AuditLog::record('loyalty.enrolled', $guest, ['member_no' => $account->member_no]);

        return back()->with('status', "Client inscrit — carte {$account->member_no}.");
    }

    public function adjustPoints(Request $request, Guest $guest)
    {
        abort_unless($guest->loyaltyAccount, 404);

        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'between:-100000,100000'],
            'reason' => ['required', 'string', 'max:160'],
        ]);

        LoyaltyProgram::adjust($guest->loyaltyAccount, (int) $data['points'], $data['reason'], $request->user()->id);
        AuditLog::record('loyalty.adjusted', $guest, $data);

        return back()->with('status', 'Solde de points ajusté.');
    }

    public function redeemPoints(Request $request, Guest $guest)
    {
        abort_unless($guest->loyaltyAccount, 404);

        $data = $request->validate([
            'points' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:160'],
        ]);

        $tx = LoyaltyProgram::redeem(
            $guest->loyaltyAccount,
            (int) $data['points'],
            $data['reason'] ?: 'Remise réception',
            null
        );
        AuditLog::record('loyalty.redeemed', $guest, ['points' => $data['points']]);

        return back()->with('status', 'Points utilisés — remise de '
            .money(LoyaltyProgram::pointsValueFcfa(abs($tx->points))).'.');
    }

    public function storeInteraction(Request $request, Guest $guest)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('crm.interaction_types')))],
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:2000'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $guest->interactions()->create([
            'hotel_id' => $guest->reservations()->value('hotel_id') ?? Hotel::current()->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'body' => $data['body'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return back()->with('status', 'Interaction ajoutée à l’historique.');
    }
}
