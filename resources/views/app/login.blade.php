@extends('app.layout')
@section('title', 'Connexion')

@section('bare')
<div class="flex min-h-screen flex-col justify-center px-6 py-10">
    <div class="mx-auto mb-6 flex flex-col items-center gap-2 text-center">
        <img src="{{ \App\Support\Branding::logo() }}" alt="" class="h-14 w-14">
        <h1 class="font-display text-2xl font-semibold text-nuit-900">{{ config('guestapp.name') }}</h1>
        <p class="text-sm text-nuit-500">Accédez à votre séjour, votre folio, le room service et vos demandes.</p>
    </div>

    @if ($errors->any())
        <div class="mb-3 rounded-xl border border-terracotta-300 bg-terracotta-50 px-3 py-2 text-sm text-terracotta-800">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('guest.login.submit') }}" class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
            Référence de réservation
            <input name="reference" value="{{ old('reference', $reference) }}" required autocapitalize="characters" autocomplete="off"
                   placeholder="HRK-XXXXXX"
                   class="rounded-xl border border-nuit-200 px-4 py-3 text-lg tracking-wider uppercase focus:border-terracotta-500 focus:outline-none">
        </label>
        <label class="flex flex-col gap-1 text-sm font-semibold text-nuit-800">
            Nom de famille
            <input name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name"
                   class="rounded-xl border border-nuit-200 px-4 py-3 text-lg focus:border-terracotta-500 focus:outline-none">
        </label>
        <button class="mt-1 rounded-full bg-nuit-900 px-6 py-3.5 text-base font-semibold text-white hover:bg-terracotta-500">
            Accéder à mon séjour
        </button>
    </form>

    <p class="mt-4 text-center text-xs text-nuit-400">
        La référence figure sur votre confirmation. En cas de doute, contactez la réception.
    </p>
</div>
@endsection
