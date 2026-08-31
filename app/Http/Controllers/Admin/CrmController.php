<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Guest;
use App\Models\Hotel;
use App\Models\LoyaltyAccount;
use App\Models\LoyaltyTier;
use App\Models\MarketingCampaign;
use App\Models\MarketingSegment;
use App\Models\PromoCode;
use App\Services\Segmentation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrmController extends Controller
{
    public function dashboard()
    {
        $month = now()->month;

        return view('admin.crm.dashboard', [
            'guests' => Guest::count(),
            'members' => LoyaltyAccount::count(),
            'optedIn' => Guest::where('marketing_opt_in', true)->count(),
            'birthdays' => Guest::whereNotNull('birthdate')->whereMonth('birthdate', $month)
                ->get()->sortBy(fn (Guest $g) => (int) $g->birthdate->format('d'))->take(12)->values(),
            'tiers' => LoyaltyTier::withCount('accounts')->orderBy('min_points')->get(),
            'topMembers' => LoyaltyAccount::with('guest', 'tier')->orderByDesc('lifetime_points')->limit(8)->get(),
            'segments' => MarketingSegment::withCount('campaigns')->latest()->get(),
            'lastCampaigns' => MarketingCampaign::with('segment')->latest()->limit(5)->get(),
        ]);
    }

    /* ------------------------------ Fidélité ----------------------------- */

    public function loyalty(Request $request)
    {
        $members = LoyaltyAccount::query()
            ->with('guest', 'tier')
            ->when($request->query('tier'), fn ($q, $code) => $q->whereHas('tier', fn ($t) => $t->where('code', $code)))
            ->orderByDesc('lifetime_points')
            ->paginate(30)
            ->withQueryString();

        return view('admin.crm.loyalty', [
            'members' => $members,
            'tiers' => LoyaltyTier::withCount('accounts')->orderBy('min_points')->get(),
            'activeTier' => $request->query('tier'),
            'pointValue' => (int) config('loyalty.point_value_fcfa', 5),
        ]);
    }

    /* ------------------------------ Segments ---------------------------- */

    public function segments()
    {
        return view('admin.crm.segments', [
            'segments' => MarketingSegment::withCount('campaigns')->latest()->get(),
            'rules' => config('marketing.segment_rules'),
            'tiers' => LoyaltyTier::orderBy('min_points')->pluck('name', 'code'),
        ]);
    }

    public function storeSegment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:200'],
            'rule' => ['required', 'array'],
        ]);

        $definition = $this->cleanDefinition($data['rule']);

        MarketingSegment::create([
            'hotel_id' => Hotel::current()->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'definition' => $definition,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Segment enregistré ('.Segmentation::count($definition).' clients).');
    }

    public function previewSegment(Request $request)
    {
        $definition = $this->cleanDefinition($request->input('rule', []));

        return response()->json([
            'count' => Segmentation::count($definition),
            'sample' => Segmentation::preview($definition, 8)
                ->map(fn (Guest $g) => $g->fullName().($g->country ? " · {$g->country}" : ''))
                ->values(),
        ]);
    }

    public function destroySegment(MarketingSegment $segment)
    {
        abort_if($segment->campaigns()->exists(), 422, 'Segment utilisé par une campagne.');
        $segment->delete();

        return back()->with('status', 'Segment supprimé.');
    }

    /* ---------------------------- Codes promo -------------------------- */

    public function promos()
    {
        return view('admin.crm.promos', [
            'promos' => PromoCode::withCount('campaigns')->latest()->get(),
        ]);
    }

    public function storePromo(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash',
                Rule::unique('promo_codes', 'code')->where('hotel_id', Hotel::current()->id)],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'integer', 'min:1'],
            'label' => ['required', 'string', 'max:120'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'max_redemptions' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($data['type'] === 'percent' && $data['value'] > 100) {
            return back()->withErrors(['value' => 'Un pourcentage ne peut dépasser 100.']);
        }

        PromoCode::create($data + [
            'hotel_id' => Hotel::current()->id,
            'code' => strtoupper($data['code']),
            'active' => true,
        ]);

        return back()->with('status', 'Code promo créé.');
    }

    public function togglePromo(PromoCode $promo)
    {
        $promo->update(['active' => ! $promo->active]);

        return back()->with('status', $promo->active ? 'Code activé.' : 'Code désactivé.');
    }

    /* ------------------------------------------------------------------- */

    /** @return array<string,mixed> */
    private function cleanDefinition(array $rule): array
    {
        $allowed = array_keys(config('marketing.segment_rules'));
        $out = [];

        foreach ($rule as $key => $value) {
            if (! in_array($key, $allowed, true)) {
                continue;
            }
            if ($value === '' || $value === null || $value === false || $value === '0') {
                continue;
            }
            $out[$key] = in_array($key, ['opted_in', 'never_stayed'], true)
                ? true
                : (is_numeric($value) ? (int) $value : $value);
        }

        return $out;
    }
}
