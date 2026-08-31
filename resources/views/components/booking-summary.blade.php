@props(['booking', 'quote', 'editable' => true])

<aside class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-card">
    <h2 class="font-display text-xl font-semibold text-nuit-900">Votre séjour</h2>

    <dl class="flex flex-col gap-2 text-sm">
        <div class="flex justify-between gap-4">
            <dt class="text-nuit-500">Arrivée</dt>
            <dd class="font-medium text-nuit-900">{{ pretty_date($booking['check_in']) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-nuit-500">Départ</dt>
            <dd class="font-medium text-nuit-900">{{ pretty_date($booking['check_out']) }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-nuit-500">Durée</dt>
            <dd class="font-medium text-nuit-900">{{ $quote['nights'] }} nuit{{ $quote['nights'] > 1 ? 's' : '' }}</dd>
        </div>
        <div class="flex justify-between gap-4">
            <dt class="text-nuit-500">Voyageurs</dt>
            <dd class="font-medium text-nuit-900">
                {{ $quote['adults'] }} adulte{{ $quote['adults'] > 1 ? 's' : '' }}@if ($quote['children'] > 0), {{ $quote['children'] }} enfant{{ $quote['children'] > 1 ? 's' : '' }}@endif · {{ $quote['rooms'] }} ch.
            </dd>
        </div>
        @if ($editable)
            <a href="{{ route('booking.start') }}" class="mt-1 text-xs font-semibold text-terracotta-600 hover:underline">Modifier les dates</a>
        @endif
    </dl>

    @if (! empty($quote['room_lines']))
        <div class="flex flex-col gap-2 border-t border-sable-200 pt-4">
            <div class="flex items-center justify-between">
                <p class="text-sm font-semibold text-nuit-900">Chambres · {{ $quote['rate_plan']['label'] }}</p>
                @if ($editable)
                    <a href="{{ route('booking.rooms') }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Modifier</a>
                @endif
            </div>
            @foreach ($quote['room_lines'] as $line)
                <div class="flex items-center gap-2 text-sm">
                    <span class="grid h-6 min-w-6 place-items-center rounded-md bg-sable-100 px-1 text-xs font-bold text-nuit-700">{{ $line['qty'] }}</span>
                    <span class="text-nuit-800">{{ $line['name'] }}</span>
                </div>
            @endforeach
        </div>

        <dl class="flex flex-col gap-2 border-t border-sable-200 pt-4 text-sm">
            @foreach ($quote['room_lines'] as $line)
                <div class="flex justify-between gap-4">
                    <dt class="text-nuit-600">{{ $line['name'] }} <span class="text-nuit-400">×{{ $line['qty'] }}</span>
                        <span class="block text-xs text-nuit-400">{{ money($line['nightly']) }} × {{ $line['nights'] }} nuit{{ $line['nights'] > 1 ? 's' : '' }}</span></dt>
                    <dd class="font-medium text-nuit-900">{{ money($line['line_total']) }}</dd>
                </div>
            @endforeach

            @foreach ($quote['extra_lines'] as $line)
                <div class="flex justify-between gap-4">
                    <dt class="text-nuit-600">{{ $line['name'] }} <span class="text-nuit-400">({{ $line['detail'] }})</span></dt>
                    <dd class="font-medium text-nuit-900">{{ money($line['amount']) }}</dd>
                </div>
            @endforeach

            @if ($quote['discount'])
                <div class="flex justify-between gap-4 text-terracotta-700">
                    <dt>{{ $quote['discount']['label'] }}</dt>
                    <dd class="font-medium">−{{ money($quote['discount_amount']) }}</dd>
                </div>
            @endif

            <div class="flex justify-between gap-4">
                <dt class="text-nuit-500">Taxes & services ({{ (int) round($quote['tax_rate'] * 100) }} %)</dt>
                <dd class="text-nuit-700">{{ money($quote['tax']) }}</dd>
            </div>
            <div class="flex justify-between gap-4">
                <dt class="text-nuit-500">Taxe de séjour</dt>
                <dd class="text-nuit-700">{{ money($quote['tourist_tax']) }}</dd>
            </div>

            <div class="mt-2 flex items-baseline justify-between gap-4 border-t border-sable-200 pt-3">
                <dt class="font-display text-base font-semibold text-nuit-900">Total</dt>
                <dd class="font-display text-xl font-semibold text-nuit-900">{{ money($quote['total']) }}</dd>
            </div>
            <p class="text-xs text-nuit-400">Dépôt à la réservation : {{ money($quote['deposit']) }} (30 %). Solde à l'hôtel.</p>
        </dl>
    @else
        <p class="border-t border-sable-200 pt-4 text-sm text-nuit-500">
            Sélectionnez une chambre pour voir le détail du tarif.
        </p>
    @endif
</aside>
