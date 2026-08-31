@extends('admin.layout')
@section('title', 'Services & fonctions')

@php $canEdit = auth()->user()->hasRole('rh'); @endphp

@section('content')
<div class="flex flex-col gap-6">
    <a href="{{ route('admin.hr.dashboard') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← RH</a>

    @if ($canEdit)
        <div class="grid gap-4 sm:grid-cols-2">
            <form method="POST" action="{{ route('admin.hr.departments.store') }}" class="flex gap-2 rounded-2xl bg-white p-4 shadow-card">
                @csrf
                <input type="text" name="name" placeholder="Nouveau service" required class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="text" name="code" placeholder="Code" class="w-20 rounded-lg border border-nuit-200 px-2 py-2 text-sm">
                <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Ajouter</button>
            </form>
            <form method="POST" action="{{ route('admin.hr.positions.store') }}" class="flex gap-2 rounded-2xl bg-white p-4 shadow-card">
                @csrf
                <select name="department_id" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
                <input type="text" name="title" placeholder="Nouvelle fonction" required class="flex-1 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Ajouter</button>
            </form>
        </div>
    @endif

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($departments as $d)
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-display text-lg font-semibold text-nuit-900">{{ $d->name }}</h2>
                    <span class="text-xs text-nuit-400">{{ $d->employees_count }} agents</span>
                </div>
                <ul class="mt-2 flex flex-col gap-0.5 text-sm text-nuit-600">
                    @foreach ($d->positions as $p)<li>· {{ $p->title }}</li>@endforeach
                </ul>
            </section>
        @endforeach
    </div>

    {{-- Jours fériés (fêtes mobiles ; les fixes viennent de config/hr.php) --}}
    <section class="rounded-2xl bg-white p-5 shadow-card">
        <h2 class="mb-1 font-display text-lg font-semibold text-nuit-900">Jours fériés</h2>
        <p class="mb-3 text-xs text-nuit-400">Décomptés dans le calcul des congés. Les fêtes fixes (1ᵉʳ janvier, 4 avril, 1ᵉʳ mai…) sont automatiques ; saisissez ici les fêtes mobiles (Korité, Tabaski, Ascension, Pentecôte…).</p>

        <div class="flex flex-wrap gap-2">
            @forelse ($holidays as $hol)
                <span class="inline-flex items-center gap-2 rounded-full bg-sable-100 px-3 py-1 text-xs text-nuit-700">
                    {{ $hol->date->format('d/m/Y') }} · {{ $hol->name }}
                    @if ($canEdit)
                        <form method="POST" action="{{ route('admin.hr.holidays.destroy', $hol) }}">@csrf @method('DELETE')<button class="text-terracotta-600 hover:text-terracotta-800">×</button></form>
                    @endif
                </span>
            @empty
                <span class="text-xs text-nuit-400">Aucune fête mobile saisie.</span>
            @endforelse
        </div>

        @if ($canEdit)
            <form method="POST" action="{{ route('admin.hr.holidays.store') }}" class="mt-4 flex flex-wrap gap-2">
                @csrf
                <input type="date" name="date" required class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                <input type="text" name="name" placeholder="Nom du jour férié" required class="flex-1 rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                <button class="rounded-full bg-nuit-900 px-4 py-1.5 text-sm font-semibold text-white">Ajouter</button>
            </form>
        @endif
    </section>
</div>
@endsection
