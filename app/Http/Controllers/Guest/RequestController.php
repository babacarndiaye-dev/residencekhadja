<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\GuestRequest;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $stay = $request->attributes->get('guest_reservation');
        $categories = config('guestapp.service_categories');
        $focus = $request->query('cat');

        return view('app.requests', [
            'requests' => $stay->guestRequests()->with('charge')->latest()->get(),
            'types' => config('guestapp.request_types'),
            'prefill' => $request->query('type'),
            'serviceCategories' => $categories,
            'categoryIcons' => config('guestapp.service_category_icons'),
            'services' => collect(config('guestapp.services'))->groupBy('category'),
            'focusCat' => array_key_exists($focus, $categories) ? $focus : null,
        ]);
    }

    /** Demande libre (dropdown de types) — inchangé. */
    public function store(Request $request)
    {
        $stay = $request->attributes->get('guest_reservation');

        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(config('guestapp.request_types')))],
            'note' => ['nullable', 'string', 'max:400'],
        ]);

        $routedTo = match (true) {
            in_array($data['type'], config('guestapp.maintenance_types', []), true) => 'maintenance',
            in_array($data['type'], ['menage', 'linge', 'articles'], true) => 'housekeeping',
            default => 'reception',
        };

        $guestRequest = $stay->guestRequests()->create([
            'hotel_id' => $stay->hotel_id,
            'room_id' => $stay->room_id,
            'type' => $data['type'],
            'note' => $data['note'] ?? null,
            'routed_to' => $routedTo,
            'status' => 'open',
        ]);

        $this->notify($stay, $guestRequest, $routedTo);

        return redirect()->route('guest.requests')->with('status', 'Votre demande a été transmise.');
    }

    /** Demande depuis le catalogue de services — impute au folio si payant. */
    public function requestService(Request $request)
    {
        $stay = $request->attributes->get('guest_reservation');
        $slugs = collect(config('guestapp.services'))->pluck('slug')->all();

        $data = $request->validate([
            'slug' => ['required', Rule::in($slugs)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:5'],
            'note' => ['nullable', 'string', 'max:400'],
        ]);

        $service = collect(config('guestapp.services'))->firstWhere('slug', $data['slug']);
        $qty = ($service['qty'] ?? false) ? (int) ($data['quantity'] ?? 1) : 1;
        $unit = (int) ($service['price'] ?? 0);
        $amount = $unit * $qty;
        $routedTo = $service['routed_to'] ?? 'reception';

        $guestRequest = DB::transaction(function () use ($stay, $service, $data, $qty, $unit, $amount, $routedTo) {
            $gr = $stay->guestRequests()->create([
                'hotel_id' => $stay->hotel_id,
                'room_id' => $stay->room_id,
                'type' => 'service',
                'service_slug' => $service['slug'],
                'quantity' => $qty,
                'price' => $unit,
                'note' => $data['note'] ?? null,
                'routed_to' => $routedTo,
                'status' => 'open',
            ]);

            if ($amount > 0) {
                $charge = $stay->charges()->create([
                    'label' => $service['label'].($qty > 1 ? " ×{$qty}" : '').' — demande via l’app',
                    'amount' => $amount,
                ]);
                $charge->source()->associate($gr)->save();
                $gr->update(['charge_id' => $charge->id]);
            }

            return $gr;
        });

        $this->notify($stay, $guestRequest, $routedTo);

        $msg = 'Votre demande a été transmise.';
        if ($amount > 0) {
            $msg .= ' '.money($amount).' ont été imputés à votre folio.';
        }

        return redirect()->route('guest.requests')->with('status', $msg);
    }

    public function cancel(Request $request, GuestRequest $guestRequest)
    {
        $stay = $request->attributes->get('guest_reservation');
        abort_unless($guestRequest->reservation_id === $stay->id, 404);
        abort_unless($guestRequest->status === 'open', 422);

        DB::transaction(function () use ($guestRequest) {
            // Contre-passation : la demande n'a pas encore été prise en compte.
            $guestRequest->charge?->delete();
            $guestRequest->update(['status' => 'cancelled', 'charge_id' => null]);
        });

        return back()->with('status', 'Demande annulée.');
    }

    private function notify($stay, GuestRequest $guestRequest, string $routedTo): void
    {
        $note = $guestRequest->note;
        $amount = $guestRequest->chargeAmount();

        Notify::roles(
            array_unique([$routedTo, 'reception']),
            'Demande client — '.($stay->room?->number ? 'chambre '.$stay->room->number : $stay->reference),
            $guestRequest->typeLabel()
                .($amount > 0 ? ' · '.money($amount).' au folio' : '')
                .($note ? ' · '.$note : ''),
            route('admin.guest_requests.index', ['routed' => $routedTo], false),
            level: 'warning',
            icon: '🛎️',
        );
    }
}
