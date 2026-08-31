@extends('layouts.app')

@section('title', 'Réserver — options & services')
@push('head')<meta name="robots" content="noindex,follow">@endpush

@section('content')
<section class="bg-sable-100 py-10 sm:py-14">
    <x-container>
        <x-booking-steps current="extras" class="mb-8" />

        <div class="grid gap-8 lg:grid-cols-[1.6fr_1fr] lg:items-start">
            <form method="POST" action="{{ route('booking.extras.store') }}" class="flex flex-col gap-6">
                @csrf

                <div>
                    <h1 class="font-display text-3xl font-semibold text-nuit-900 sm:text-4xl">Personnalisez votre séjour</h1>
                    <p class="mt-2 text-ink-soft">Ajoutez des services (facultatif) et indiquez vos préférences.</p>
                </div>

                <fieldset class="flex flex-col gap-3">
                    <legend class="mb-1 text-sm font-semibold uppercase tracking-wider text-nuit-500">Services additionnels</legend>
                    @foreach ($extras as $extra)
                        @php $checked = in_array($extra['key'], $booking['extras'] ?? [], true); @endphp
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl bg-white p-4 shadow-card has-[:checked]:ring-2 has-[:checked]:ring-terracotta-500">
                            <input type="checkbox" name="extras[]" value="{{ $extra['key'] }}" @checked($checked)
                                   class="mt-1 h-5 w-5 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                            <span class="flex-1">
                                <span class="flex flex-wrap items-baseline justify-between gap-2">
                                    <span class="font-semibold text-nuit-900">{{ $extra['name'] }}</span>
                                    <span class="text-sm font-semibold text-terracotta-600">{{ money($extra['price']) }} <span class="font-normal text-nuit-400">/ {{ $extra['unit'] }}</span></span>
                                </span>
                                <span class="mt-0.5 block text-sm text-ink-soft">{{ $extra['desc'] }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <fieldset class="rounded-2xl bg-white p-5 shadow-card">
                    <legend class="px-1 text-sm font-semibold uppercase tracking-wider text-nuit-500">Demandes spéciales</legend>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ($specialRequests as $req)
                            <label class="flex items-center gap-2 text-sm text-nuit-800">
                                <input type="checkbox" name="special_requests[]" value="{{ $req }}"
                                       @checked(in_array($req, $booking['special_requests'] ?? [], true))
                                       class="rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                                {{ $req }}
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs text-nuit-400">Les demandes spéciales sont satisfaites selon les disponibilités, sans garantie.</p>
                </fieldset>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('booking.rooms') }}" class="text-sm font-semibold text-nuit-600 hover:text-terracotta-600">&larr; Retour aux chambres</a>
                    <button type="submit" class="inline-flex items-center gap-2 rounded-full bg-terracotta-500 px-7 py-3 text-sm font-semibold text-white transition hover:bg-terracotta-600">
                        Voir le récapitulatif
                    </button>
                </div>
            </form>

            <div class="lg:sticky lg:top-28">
                <x-booking-summary :booking="$booking" :quote="$quote" />
            </div>
        </div>
    </x-container>
</section>
@endsection
