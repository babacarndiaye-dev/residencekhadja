@props([
    'title' => null,
    'text' => null,
])
@php
    $title ??= config('vitrine.cta_title', 'Prêt à vivre la Teranga ?');
    $text ??= config('vitrine.cta_text', 'Réservez en direct sur ce site : meilleur tarif garanti, annulation gratuite sur le tarif flexible et attentions Khadija Privilege.');
@endphp

<section class="py-16">
    <x-container>
        <div class="reveal relative isolate overflow-hidden rounded-3xl bg-nuit-900 px-6 py-14 text-center sm:px-12">
            <div class="absolute inset-x-0 top-0 rule-teranga opacity-60"></div>
            <h2 class="mx-auto max-w-2xl text-3xl text-white sm:text-4xl">{{ $title }}</h2>
            <p class="mx-auto mt-4 max-w-xl text-white/70">{{ $text }}</p>
            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <x-button :href="route('booking.start')" variant="primary" size="lg">Réserver maintenant</x-button>
                <x-button :href="'https://wa.me/'.config('hotel.contact.whatsapp_href')" variant="ghost" size="lg" class="text-white hover:text-terracotta-200">
                    Nous joindre sur WhatsApp
                </x-button>
            </div>
        </div>
    </x-container>
</section>
