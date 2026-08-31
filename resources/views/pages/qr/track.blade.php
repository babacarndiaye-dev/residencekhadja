<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#de6443">
    <meta http-equiv="refresh" content="20">
    <title>Commande {{ $order->reference }}</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @include('partials.fonts')
    @vite(['resources/css/app.css'])
</head>
@php
    $steps = ['new' => 'Reçue', 'preparing' => 'En préparation', 'ready' => 'Prête', 'served' => 'Servie'];
    $order_index = array_search($order->status, array_keys($steps), true);
    if ($order->status === 'completed') $order_index = 3;
@endphp
<body class="bg-sable-50 text-ink">
    <div class="mx-auto max-w-lg px-4 py-8">
        <div class="text-center">
            <img src="{{ \App\Support\Branding::logo() }}" alt="" width="48" height="48" class="mx-auto h-12 w-12">
            @if ($order->status === 'cancelled')
                <h1 class="mt-4 font-display text-2xl font-semibold text-nuit-900">Commande annulée</h1>
                <p class="mt-1 text-sm text-ink-soft">Contactez un serveur si besoin.</p>
            @else
                <h1 class="mt-4 font-display text-2xl font-semibold text-nuit-900">Merci !</h1>
                <p class="mt-1 text-sm text-ink-soft">Commande <strong>{{ $order->reference }}</strong> · {{ $order->venue?->name }}</p>
            @endif
        </div>

        @if ($order->status !== 'cancelled')
            <ol class="mt-8 flex flex-col gap-3">
                @foreach ($steps as $key => $label)
                    @php $done = $loop->index <= $order_index; @endphp
                    <li class="flex items-center gap-3">
                        <span @class([
                            'flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold',
                            'border-terracotta-500 bg-terracotta-500 text-white' => $done,
                            'border-nuit-200 bg-white text-nuit-300' => ! $done,
                        ])>{{ $loop->iteration }}</span>
                        <span class="{{ $done ? 'font-semibold text-nuit-900' : 'text-nuit-400' }}">{{ $label }}</span>
                    </li>
                @endforeach
            </ol>
            <p class="mt-4 text-center text-xs text-nuit-400">Cette page se rafraîchit automatiquement.</p>
        @endif

        <div class="mt-8 rounded-2xl bg-white p-4 shadow-card">
            <h2 class="text-sm font-semibold text-nuit-900">Détail</h2>
            <ul class="mt-2 flex flex-col gap-2 text-sm">
                @foreach ($order->items as $line)
                    <li class="flex justify-between gap-3">
                        <span>{{ $line->quantity }}× {{ $line->name }}
                            @if ($line->options)<span class="block text-xs text-nuit-400">{{ collect($line->options)->pluck('name')->join(', ') }}</span>@endif
                        </span>
                        <span>{{ money($line->line_total) }}</span>
                    </li>
                @endforeach
            </ul>
            <div class="mt-3 flex justify-between border-t border-sable-200 pt-3 text-sm font-semibold text-nuit-900">
                <span>Total</span><span>{{ money($order->total) }}</span>
            </div>
            <p class="mt-1 text-xs text-nuit-400">
                @switch($order->payment_status)
                    @case('charged_to_room') Imputé sur la chambre. @break
                    @case('paid') Réglé. @break
                    @default À régler au service ou en ligne.
                @endswitch
            </p>

            @if ($order->payment_status === 'unpaid' && $order->status !== 'cancelled')
                <form method="POST" action="{{ route('qr.pay', [$order->location->code, $order->reference]) }}" class="mt-3">
                    @csrf
                    <button class="w-full rounded-full bg-terracotta-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">
                        Payer en ligne — {{ money($order->total) }}
                    </button>
                </form>
            @endif
        </div>

        <div class="mt-6 flex gap-2">
            <a href="{{ route('qr.show', $order->location->code) }}" class="flex-1 rounded-full border border-nuit-200 px-4 py-2.5 text-center text-sm font-semibold text-nuit-700">Retour à la carte</a>
            <form method="POST" action="{{ route('qr.bill', $order->location->code) }}" class="flex-1">
                @csrf
                <button class="w-full rounded-full bg-nuit-900 px-4 py-2.5 text-sm font-semibold text-white">Demander l’addition</button>
            </form>
        </div>
    </div>

    <script>try { localStorage.removeItem('qr_cart_' + @json($order->location->code)); } catch (e) {}</script>
</body>
</html>
