@extends('layouts.app')

@section('title', 'Réserver — récapitulatif')
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')
<section class="bg-sable-100 py-10 sm:py-14">
    <x-container>
        <x-booking-steps current="summary" class="mb-8" />

        <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
            <form method="POST" action="{{ route('booking.confirm') }}" class="flex flex-col gap-6">
                @csrf

                <div>
                    <h1 class="font-display text-3xl font-semibold text-nuit-900 sm:text-4xl">Vos coordonnées</h1>
                    <p class="mt-2 text-ink-soft">Dernière étape : renseignez le voyageur principal. Nous confirmons votre demande sous 24 h.</p>
                </div>

                @if ($errors->any())
                    <div class="rounded-xl border border-terracotta-300 bg-terracotta-50 p-4 text-sm text-terracotta-800">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex flex-col gap-4 rounded-2xl bg-white p-6 shadow-card">
                    <div class="grid gap-4 sm:grid-cols-[120px_1fr_1fr]">
                        <x-field name="civility" label="Civilité" type="select" required :options="true">
                            @foreach (['Mme', 'M.', 'Autre'] as $c)
                                <option value="{{ $c }}" @selected(old('civility') === $c)>{{ $c }}</option>
                            @endforeach
                        </x-field>
                        <x-field name="first_name" label="Prénom" required />
                        <x-field name="last_name" label="Nom" required />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="email" label="E-mail" type="email" required />
                        <x-field name="phone" label="Téléphone" type="tel" required />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field name="country" label="Pays de résidence" required value="{{ old('country', 'Sénégal') }}" />
                        <x-field name="arrival_time" label="Heure d'arrivée estimée" type="time" />
                    </div>
                    <x-field name="company" label="Société (facturation entreprise)" hint="Facultatif" />
                    <x-field name="notes" label="Message pour l'hôtel" type="textarea" hint="Facultatif — occasion spéciale, précisions…" />
                </div>

                <fieldset class="flex flex-col gap-3 rounded-2xl bg-white p-6 shadow-card">
                    <legend class="px-1 text-sm font-semibold text-nuit-900">Règlement</legend>
                    @foreach ([
                        'sur_place' => "À l'hôtel, à l'arrivée (carte ou espèces)",
                        'virement'  => 'Par virement bancaire (coordonnées transmises avec la confirmation)',
                        'a_definir' => "À définir avec le service réservation",
                    ] as $key => $label)
                        <label class="flex items-center gap-2 text-sm text-nuit-800">
                            <input type="radio" name="payment" value="{{ $key }}" @checked(old('payment', 'sur_place') === $key)
                                   class="text-terracotta-500 focus:ring-terracotta-500">
                            {{ $label }}
                        </label>
                    @endforeach
                    <p class="text-xs text-nuit-400">Le paiement en ligne sécurisé sera activé dans une prochaine version du site.</p>
                </fieldset>

                <label class="flex items-start gap-2 text-sm text-nuit-600">
                    <input type="checkbox" name="terms" value="1" required
                           class="mt-0.5 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                    J'ai lu et j'accepte les conditions de réservation et la politique d'annulation ({{ $quote['rate_plan']['label'] }}).
                </label>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('booking.extras') }}" class="text-sm font-semibold text-nuit-600 hover:text-terracotta-600">&larr; Retour aux options</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-terracotta-500 px-8 py-4 text-base font-semibold text-white transition hover:bg-terracotta-600">
                        Confirmer la demande de réservation
                    </button>
                </div>
            </form>

            <div class="lg:sticky lg:top-28">
                <x-booking-summary :booking="$booking" :quote="$quote" />
                @if (! empty($booking['special_requests']))
                    <div class="mt-4 rounded-2xl bg-white p-5 text-sm shadow-card">
                        <p class="font-semibold text-nuit-900">Demandes spéciales</p>
                        <ul class="mt-2 flex flex-wrap gap-2">
                            @foreach ($booking['special_requests'] as $req)
                                <li class="rounded-full bg-sable-100 px-2.5 py-1 text-xs text-nuit-700">{{ $req }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </x-container>
</section>
@endsection
