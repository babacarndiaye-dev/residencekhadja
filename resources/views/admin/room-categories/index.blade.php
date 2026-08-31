@extends('admin.layout')
@section('title', 'Catégories de chambre')

@section('content')
<div class="flex flex-col gap-4">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.settings.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Paramètres</a>
        <a href="{{ route('admin.room_categories.create') }}" class="rounded-full bg-terracotta-500 px-5 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">+ Nouvelle catégorie</a>
    </div>

    <p class="text-sm text-nuit-500">Ces catégories alimentent directement la vitrine (liste et fiches « Chambres &amp; Suites ») et le tunnel de réservation.</p>

    <div class="overflow-x-auto rounded-2xl bg-white shadow-card">
        <table class="w-full text-sm">
            <thead class="bg-sable-100 text-left text-xs uppercase tracking-wider text-nuit-500">
                <tr>
                    <th class="px-4 py-3">Catégorie</th>
                    <th class="px-4 py-3">Tarif / nuit</th>
                    <th class="px-4 py-3">Capacité</th>
                    <th class="px-4 py-3">Chambres</th>
                    <th class="px-4 py-3">État</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $c)
                    <tr class="border-t border-sable-200 align-middle">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @php $cover = ($c->images[0] ?? null); @endphp
                                @if ($cover)
                                    <img src="{{ \Illuminate\Support\Str::startsWith($cover, ['http', '/']) ? $cover : \Illuminate\Support\Facades\Storage::disk('public')->url($cover) }}" alt="" class="h-11 w-14 shrink-0 rounded-lg object-cover">
                                @endif
                                <div>
                                    <p class="font-medium text-nuit-900">{{ $c->name }}</p>
                                    <p class="text-xs text-nuit-400">{{ ucfirst($c->category) }} · {{ $c->slug }}@if ($c->featured) · ★ mise en avant @endif</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 tabular-nums">{{ number_format($c->price, 0, ',', ' ') }}</td>
                        <td class="px-4 py-3">{{ $c->capacity }} pers.</td>
                        <td class="px-4 py-3 text-nuit-500">{{ $c->rooms_count }} · {{ $c->sellableRoomsCount() }} vendables</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $c->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-sable-200 text-nuit-500' }}">
                                {{ $c->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.room_categories.edit', $c) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Éditer</a>
                            @unless ($c->rooms_count || $c->reservations()->exists())
                                <form method="POST" action="{{ route('admin.room_categories.destroy', $c) }}" class="ml-2 inline" onsubmit="return confirm('Supprimer la catégorie « {{ $c->name }} » ?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-red-600 hover:underline">Supprimer</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-nuit-400">Aucune catégorie.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
