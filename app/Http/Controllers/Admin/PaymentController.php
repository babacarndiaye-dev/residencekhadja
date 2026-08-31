<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\PaymentIntent;
use App\Models\Reservation;
use App\Services\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $intents = PaymentIntent::query()
            ->with('payable', 'createdBy')
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('purpose'), fn ($q, $p) => $q->where('purpose', $p))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($sub) => $sub
                ->where('reference', 'like', "%{$term}%")
                ->orWhere('payer_name', 'like', "%{$term}%")
                ->orWhere('payer_email', 'like', "%{$term}%")
                ->orWhere('provider_ref', 'like', "%{$term}%")))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.payments.index', [
            'intents' => $intents,
            'statuses' => PaymentIntent::STATUSES,
            'purposes' => PaymentIntent::PURPOSES,
            'filters' => $request->only('status', 'purpose', 'q'),
            'totalPaid' => PaymentIntent::where('status', 'paid')->sum('amount'),
            'pendingCount' => PaymentIntent::pending()->count(),
        ]);
    }

    public function show(PaymentIntent $intent)
    {
        return view('admin.payments.show', ['intent' => $intent->load('payable', 'createdBy')]);
    }

    public function markPaid(Request $request, PaymentIntent $intent)
    {
        $data = $request->validate([
            'method' => ['required', Rule::in(array_keys(config('payments.methods')))],
        ]);

        PaymentGateway::capture($intent, $data['method'], 'MANUAL-'.now()->format('YmdHis'));
        AuditLog::record('payment.captured_manually', $intent);

        return back()->with('status', 'Paiement marqué encaissé.');
    }

    public function refund(Request $request, PaymentIntent $intent)
    {
        $amount = $request->integer('amount') ?: null;
        PaymentGateway::refund($intent, $amount);
        AuditLog::record('payment.refunded', $intent, ['amount' => $amount ?? $intent->amount]);

        return back()->with('status', 'Remboursement enregistré.');
    }

    public function cancel(PaymentIntent $intent)
    {
        abort_unless($intent->isOpen(), 422);
        $intent->update(['status' => 'cancelled']);

        return back()->with('status', 'Intention annulée.');
    }

    public function linkForReservation(Request $request, Reservation $reservation)
    {
        $purpose = $request->input('purpose') === 'balance' ? 'reservation_balance' : 'reservation_deposit';

        // Séjour multi-chambres : le lien couvre l'acompte / le solde consolidé,
        // encaissé sur la 1re chambre (folio du séjour).
        $reservation = $reservation->groupPrimary();
        $amount = $reservation->isGrouped()
            ? ($purpose === 'reservation_balance'
                ? (int) $reservation->groupSiblings()->sum(fn ($m) => $m->balance())
                : (int) $reservation->groupSiblings()->sum('deposit'))
            : null;

        $intent = PaymentGateway::open($reservation, $purpose, $amount, [
            'hotel_id' => $reservation->hotel_id,
            'payer_name' => $reservation->guest->fullName(),
            'payer_email' => $reservation->guest->email,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Lien de paiement : '.PaymentGateway::checkoutUrl($intent));
    }

    public function linkForEvent(Request $request, Event $event)
    {
        abort_if($event->quote === null, 422, 'Aucun devis rattaché à l’événement.');
        $purpose = $request->input('purpose') === 'balance' ? 'event_balance' : 'event_deposit';

        $intent = PaymentGateway::open($event, $purpose, null, [
            'hotel_id' => $event->hotel_id,
            'payer_name' => $event->client_name,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Lien de paiement : '.PaymentGateway::checkoutUrl($intent));
    }
}
