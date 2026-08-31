<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hotel;
use App\Models\MarketingCampaign;
use App\Models\MarketingSegment;
use App\Models\PromoCode;
use App\Services\CampaignDispatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarketingController extends Controller
{
    public function index()
    {
        return view('admin.marketing.index', [
            'campaigns' => MarketingCampaign::with('segment', 'promoCode')
                ->withCount('recipients')->latest()->get(),
            'segments' => MarketingSegment::orderBy('name')->get(),
            'promos' => PromoCode::where('active', true)->orderBy('code')->get(),
            'channels' => config('marketing.channels'),
            'templates' => config('marketing.templates'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'channel' => ['required', Rule::in(array_keys(config('marketing.channels')))],
            'segment_id' => ['nullable', 'exists:marketing_segments,id'],
            'promo_code_id' => ['nullable', 'exists:promo_codes,id'],
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:4000'],
            'scheduled_for' => ['nullable', 'date', 'after:now'],
        ]);

        if ($data['channel'] === 'email' && empty($data['subject'])) {
            return back()->withInput()->withErrors(['subject' => 'Objet requis pour une campagne e-mail.']);
        }

        $campaign = MarketingCampaign::create($data + [
            'hotel_id' => Hotel::current()->id,
            'status' => ! empty($data['scheduled_for']) ? 'scheduled' : 'draft',
            'created_by' => $request->user()->id,
        ]);

        CampaignDispatcher::build($campaign);

        return redirect()
            ->route('admin.marketing.show', $campaign)
            ->with('status', 'Campagne créée — destinataires calculés.');
    }

    public function show(MarketingCampaign $campaign)
    {
        $campaign->load('segment', 'promoCode', 'createdBy');

        return view('admin.marketing.show', [
            'campaign' => $campaign,
            'recipients' => $campaign->recipients()->with('guest')->orderBy('status')->paginate(40),
            'queued' => $campaign->recipients()->where('status', 'queued')->count(),
            'skipped' => $campaign->recipients()->where('status', 'skipped')->count(),
            'sampleGuest' => $campaign->recipients()->with('guest')->where('status', '!=', 'skipped')->first()?->guest,
        ]);
    }

    public function rebuild(MarketingCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 422);
        $kept = CampaignDispatcher::build($campaign);

        return back()->with('status', "Liste régénérée — {$kept} destinataire(s) prêt(s).");
    }

    public function send(MarketingCampaign $campaign)
    {
        CampaignDispatcher::send($campaign);

        return back()->with('status', 'Campagne envoyée.');
    }

    public function cancel(MarketingCampaign $campaign)
    {
        abort_unless($campaign->isEditable(), 422);
        $campaign->update(['status' => 'cancelled']);

        return back()->with('status', 'Campagne annulée.');
    }
}
