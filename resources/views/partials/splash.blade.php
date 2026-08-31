@if (\App\Support\Splash::enabled())
    @php($hs = \App\Support\Splash::view())

    {{-- Synchrone, tout premier élément du <body> : si l'intro a déjà été vue
         dans cette session de navigation, on la neutralise avant même que le
         nœud n'existe (aucun flash au retour sur le site). --}}
    <script>try { if (sessionStorage.getItem('hrk:splash')) document.documentElement.classList.add('has-seen-splash'); } catch (e) {}</script>
    <style>html.has-seen-splash #hrk-splash { display: none !important; }</style>
    <link rel="preload" as="image" href="{{ $hs['logo_url'] }}">

    @include('partials.splash-visual', ['hs' => $hs])
    @include('partials.splash-script', ['persist' => true])
@endif
