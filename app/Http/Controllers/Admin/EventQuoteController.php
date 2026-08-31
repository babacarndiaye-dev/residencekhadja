<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EventQuoteSent;
use App\Models\AuditLog;
use App\Models\EventLead;
use App\Models\EventQuote;
use App\Services\EventQuoteBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class EventQuoteController extends Controller
{
    public function create(Request $request)
    {
        $lead = $request->filled('lead') ? EventLead::findOrFail($request->integer('lead')) : null;

        $quote = new EventQuote(EventQuoteBuilder::defaults() + [
            'title' => $lead ? ($lead->company ?: $lead->contact_name).' — '.$lead->typeLabel() : 'Nouveau devis',
            'pax' => $lead?->pax ?? 0,
            'event_lead_id' => $lead?->id,
        ]);

        return view('admin.events.quote-form', [
            'quote' => $quote,
            'lead' => $lead,
            'categories' => config('events.quote_item_categories'),
            'catering' => config('events.catering_packages'),
            'equipment' => config('events.av_equipment'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $quote = EventQuote::create(EventQuoteBuilder::defaults() + [
            'event_lead_id' => $data['event_lead_id'] ?? null,
            'title' => $data['title'],
            'pax' => $data['pax'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'terms' => $data['terms'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $this->syncItems($quote, $data['items'] ?? []);
        EventQuoteBuilder::recalculate($quote);
        AuditLog::record('event.quote.created', $quote);

        return redirect()->route('admin.events.quotes.show', $quote)->with('status', 'Devis créé.');
    }

    public function edit(EventQuote $quote)
    {
        abort_unless($quote->isEditable(), 422, 'Devis non modifiable.');
        $quote->load('items', 'lead');

        return view('admin.events.quote-form', [
            'quote' => $quote,
            'lead' => $quote->lead,
            'categories' => config('events.quote_item_categories'),
            'catering' => config('events.catering_packages'),
            'equipment' => config('events.av_equipment'),
        ]);
    }

    public function show(EventQuote $quote)
    {
        $quote->load('items', 'lead', 'event');

        return view('admin.events.quote-show', [
            'quote' => $quote,
            'categories' => config('events.quote_item_categories'),
            'catering' => config('events.catering_packages'),
            'equipment' => config('events.av_equipment'),
        ]);
    }

    public function update(Request $request, EventQuote $quote)
    {
        abort_unless($quote->isEditable(), 422, 'Devis non modifiable.');
        $data = $this->validated($request);

        $quote->update([
            'title' => $data['title'],
            'pax' => $data['pax'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'tax_rate' => $data['tax_rate'] ?? $quote->tax_rate,
            'deposit_rate' => $data['deposit_rate'] ?? $quote->deposit_rate,
            'valid_until' => $data['valid_until'] ?? $quote->valid_until,
            'terms' => $data['terms'] ?? null,
        ]);

        $this->syncItems($quote, $data['items'] ?? []);
        EventQuoteBuilder::recalculate($quote);

        return back()->with('status', 'Devis mis à jour.');
    }

    public function send(EventQuote $quote)
    {
        abort_unless($quote->status === 'draft', 422);

        $quote->update(['status' => 'sent', 'sent_at' => now()]);
        $quote->lead?->update(['status' => 'devis']);

        $email = $quote->lead?->contact_email;
        if ($email) {
            Mail::to($email)->queue(new EventQuoteSent($quote));
            AuditLog::record('event_quote.sent', $quote, ['to' => $email]);

            return back()->with('status', "Devis envoyé à {$email}.");
        }

        return back()->with('status', 'Devis marqué comme envoyé (le contact n’a pas d’adresse e-mail).');
    }

    public function accept(EventQuote $quote)
    {
        $event = EventQuoteBuilder::accept($quote);
        AuditLog::record('event.quote.accepted', $quote, ['event' => $event->reference]);

        return redirect()->route('admin.events.show', $event)
            ->with('status', "Devis accepté — événement {$event->reference} créé en option.");
    }

    public function decline(EventQuote $quote)
    {
        abort_unless($quote->isEditable(), 422);
        $quote->update(['status' => 'declined']);

        return back()->with('status', 'Devis refusé.');
    }

    public function print(EventQuote $quote)
    {
        $quote->load('items', 'lead');

        return view('admin.events.quote-print', ['quote' => $quote]);
    }

    public function destroy(EventQuote $quote)
    {
        abort_if($quote->event()->exists(), 422, 'Devis lié à un événement.');
        $quote->delete();

        return redirect()->route('admin.events.pipeline')->with('status', 'Devis supprimé.');
    }

    /* ------------------------------------------------------------------ */

    private function syncItems(EventQuote $quote, array $items): void
    {
        $quote->items()->delete();
        $order = 0;

        foreach ($items as $item) {
            if (empty($item['label']) || ! isset($item['unit_price'])) {
                continue;
            }
            $qty = max(0, (float) ($item['quantity'] ?? 1));
            $price = max(0, (int) $item['unit_price']);

            $quote->items()->create([
                'category' => in_array($item['category'] ?? '', array_keys(config('events.quote_item_categories')), true)
                    ? $item['category'] : 'divers',
                'label' => $item['label'],
                'quantity' => $qty,
                'unit' => $item['unit'] ?? null,
                'unit_price' => $price,
                'total' => EventQuoteBuilder::lineTotal($qty, $price),
                'sort_order' => $order++,
            ]);
        }
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'event_lead_id' => ['nullable', 'exists:event_leads,id'],
            'title' => ['required', 'string', 'max:160'],
            'pax' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'between:0,1'],
            'deposit_rate' => ['nullable', 'numeric', 'between:0,1'],
            'valid_until' => ['nullable', 'date'],
            'terms' => ['nullable', 'string', 'max:3000'],
            'items' => ['nullable', 'array'],
            'items.*.category' => ['nullable', 'string'],
            'items.*.label' => ['nullable', 'string', 'max:180'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['nullable', 'string', 'max:20'],
            'items.*.unit_price' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}
