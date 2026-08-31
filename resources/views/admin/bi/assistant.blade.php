@extends('admin.layout')
@section('title', 'KHADJA AI')

@section('content')
<div class="mx-auto flex max-w-2xl flex-col gap-5">
    <div>
        <h1 class="font-display text-lg font-semibold text-nuit-900">KHADJA AI — assistant de gestion</h1>
        <p class="text-sm text-nuit-500">Posez une question sur vos indicateurs ; l’assistant répond à partir des données de l’hôtel (lecture seule).</p>
    </div>

    @unless ($configured)
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            L’assistant n’est pas activé : ajoutez <code>ANTHROPIC_API_KEY</code> dans le fichier <code>.env</code> puis <code>php artisan config:clear</code>.
        </div>
    @endunless

    @isset($answer)
        <div class="rounded-2xl bg-white p-5 shadow-card">
            <p class="text-xs font-semibold uppercase tracking-wider text-nuit-400">Question</p>
            <p class="mt-1 text-sm text-nuit-700">{{ $question }}</p>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-nuit-400">Réponse</p>
            <div class="mt-1 whitespace-pre-line text-sm {{ ($ok ?? true) ? 'text-nuit-900' : 'text-terracotta-700' }}">{{ $answer }}</div>
        </div>
    @endisset

    <form method="POST" action="{{ route('admin.bi.assistant.ask') }}" class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <textarea name="question" rows="3" required maxlength="500" placeholder="Ex. Quel est mon CA restaurant aujourd’hui ?"
                  class="w-full rounded-lg border border-nuit-200 px-3 py-2.5 text-sm focus:border-terracotta-400 focus:outline-none">{{ old('question') }}</textarea>
        <div class="flex flex-wrap gap-1.5">
            @foreach ($samples as $s)
                <button type="button" onclick="this.closest('form').querySelector('textarea').value = this.textContent"
                        class="rounded-full bg-sable-100 px-3 py-1 text-xs font-semibold text-nuit-600 hover:bg-sable-200">{{ $s }}</button>
            @endforeach
        </div>
        <button @disabled(! $configured) class="self-start rounded-full bg-nuit-900 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-500 disabled:opacity-40">Demander</button>
    </form>
</div>
@endsection
