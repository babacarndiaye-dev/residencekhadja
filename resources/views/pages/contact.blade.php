@extends('layouts.app')

@section('title', 'Contact & accès')
@section('description', "Contactez l'Hôtel Résidence Khadija à Thiès : téléphone, e-mail, WhatsApp, adresse et itinéraire. Réception ouverte 24h/24.")

@php $hotel = config('hotel'); @endphp

@section('content')

    <x-page-hero eyebrow="Contact & accès" title="Parlons de votre séjour"
                 :image="asset('img/hotel/entree.jpg')">
        Une question, une demande particulière, un projet d'événement ? Notre équipe vous répond sous 24 heures.
    </x-page-hero>

    <section class="py-16 sm:py-24">
        <x-container class="grid gap-12 lg:grid-cols-[1fr_1.1fr]">
            {{-- Coordonnées --}}
            <div class="flex flex-col gap-8">
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach ([
                        ['Adresse', $hotel['contact']['address_line'].', '.$hotel['contact']['address_area'], $hotel['geo']['directions_url'], 'Itinéraire'],
                        ['Téléphone', $hotel['contact']['phone'], 'tel:'.$hotel['contact']['phone_href'], 'Appeler'],
                        ['E-mail', $hotel['contact']['email'], 'mailto:'.$hotel['contact']['email'], 'Écrire'],
                        ['WhatsApp', $hotel['contact']['whatsapp'], 'https://wa.me/'.$hotel['contact']['whatsapp_href'], 'Ouvrir'],
                    ] as [$label, $value, $link, $action])
                        <div class="rounded-2xl bg-white p-5 shadow-card">
                            <dt class="text-xs uppercase tracking-wider text-nuit-400">{{ $label }}</dt>
                            <dd class="mt-1 text-sm font-medium text-nuit-900">{{ $value }}</dd>
                            <a href="{{ $link }}" class="mt-2 inline-block text-xs font-semibold text-terracotta-600 hover:underline">{{ $action }} &rarr;</a>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-2xl bg-sable-100 p-5 text-sm text-nuit-700">
                    <p class="font-semibold text-nuit-900">Horaires</p>
                    <ul class="mt-2 space-y-1">
                        <li>{{ $hotel['hours']['reception'] }}</li>
                        <li>{{ $hotel['hours']['restaurant'] }}</li>
                        <li>{{ $hotel['hours']['rooftop'] }}</li>
                    </ul>
                </div>

                <div class="overflow-hidden rounded-2xl shadow-card">
                    <iframe title="Localisation de l'hôtel" src="{{ $hotel['geo']['map_embed'] }}"
                            width="100%" height="320" style="border:0" loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

            {{-- Formulaire --}}
            <form method="POST" action="{{ route('contact.send') }}" class="flex h-fit flex-col gap-4 rounded-2xl bg-white p-6 shadow-luxe sm:p-8">
                @csrf
                <h2 class="text-2xl text-nuit-900">Nous écrire</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="name" label="Nom" required />
                    <x-field name="email" label="E-mail" type="email" required />
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field name="phone" label="Téléphone" type="tel" />
                    <x-field name="subject" label="Objet" required value="{{ old('subject', 'Demande d\'information') }}" />
                </div>
                <x-field name="message" label="Message" type="textarea" required />

                <label class="flex items-start gap-2 text-xs text-nuit-500">
                    <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-500">
                    J'accepte que mes informations soient utilisées pour traiter ma demande, conformément à la politique de confidentialité.
                </label>

                <x-button type="submit" variant="primary" size="lg">Envoyer le message</x-button>

                <p class="text-xs text-nuit-400">Champs obligatoires marqués d'un astérisque (*).</p>
            </form>
        </x-container>
    </section>

@endsection
