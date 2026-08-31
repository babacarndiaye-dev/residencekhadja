@extends('app.layout')
@section('title', 'Hors ligne')

@section('bare')
<div class="flex min-h-screen flex-col items-center justify-center gap-3 px-6 text-center">
    <img src="{{ \App\Support\Branding::logo() }}" alt="" class="h-12 w-12 opacity-60">
    <h1 class="font-display text-xl font-semibold text-nuit-900">Vous êtes hors ligne</h1>
    <p class="text-sm text-nuit-500">Reconnectez-vous au Wi-Fi de l’hôtel pour retrouver votre séjour, votre folio et vos demandes.</p>
    <button onclick="recover()" class="mt-2 rounded-full bg-nuit-900 px-6 py-3 text-sm font-semibold text-white">Réessayer</button>
    <script>
        async function recover() {
            try {
                if ('serviceWorker' in navigator) {
                    const rs = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(rs.map((r) => r.unregister()));
                }
                if (window.caches) {
                    const ks = await caches.keys();
                    await Promise.all(ks.map((k) => caches.delete(k)));
                }
            } catch (e) {}
            location.replace('{{ route('guest.home') }}');
        }
    </script>
</div>
@endsection
