@extends('admin.layout')
@section('title', 'Plans préventifs')

@section('content')
<div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.maintenance.tickets') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Maintenance</a>
        <form method="POST" action="{{ route('admin.maintenance.plans.run') }}">
            @csrf
            <button class="rounded-full bg-terracotta-500 px-4 py-2 text-sm font-semibold text-white hover:bg-terracotta-600">
                Générer les interventions dues ({{ $dueCount }})
            </button>
        </form>
    </div>

    <details class="rounded-2xl bg-white p-4 shadow-card">
        <summary class="cursor-pointer text-sm font-semibold text-nuit-900">+ Nouveau plan</summary>
        <form method="POST" action="{{ route('admin.maintenance.plans.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">
            @csrf
            <input type="text" name="name" placeholder="Nom du plan" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="equipment_category" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">— catégorie ciblée —</option>
                @foreach ($categories as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
            </select>
            <input type="number" name="interval_days" placeholder="Périodicité (jours)" required min="1" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            <select name="priority" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                @foreach (config('maintenance.ticket_priorities') as $k => $label)<option value="{{ $k }}" @selected($k==='normal')>{{ $label }}</option>@endforeach
            </select>
            <select name="assigned_to" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <option value="">— technicien par défaut —</option>
                @foreach ($technicians as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
            </select>
            <input type="date" name="next_due_on" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm" title="Prochaine échéance (facultatif)">
            <textarea name="checklist" rows="3" placeholder="Un point de contrôle par ligne" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm sm:col-span-2"></textarea>
            <label class="flex items-center gap-2 text-sm text-nuit-600"><input type="checkbox" name="is_active" value="1" checked class="rounded border-nuit-300 text-terracotta-500"> Actif</label>
            <div class="sm:col-span-2"><button class="rounded-full bg-nuit-900 px-4 py-2 text-sm font-semibold text-white">Créer</button></div>
        </form>
    </details>

    <div class="flex flex-col gap-3">
        @foreach ($plans as $plan)
            @php $overdue = $plan->next_due_on->isPast(); @endphp
            <form method="POST" action="{{ route('admin.maintenance.plans.update', $plan) }}"
                  class="rounded-2xl bg-white p-4 shadow-card {{ $overdue && $plan->is_active ? 'ring-1 ring-terracotta-300' : '' }}">
                @csrf @method('PUT')
                <div class="grid items-center gap-3 sm:grid-cols-[1.6fr_1fr_1fr_1fr_auto]">
                    <input type="text" name="name" value="{{ $plan->name }}" class="rounded-lg border border-nuit-200 px-3 py-1.5 text-sm font-semibold">
                    <select name="equipment_category" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                        <option value="">—</option>
                        @foreach ($categories as $k => $label)<option value="{{ $k }}" @selected($plan->equipment_category === $k)>{{ $label }}</option>@endforeach
                    </select>
                    <label class="text-xs text-nuit-500">Tous les
                        <input type="number" name="interval_days" value="{{ $plan->interval_days }}" min="1" class="ml-1 w-16 rounded-lg border border-nuit-200 px-2 py-1 text-sm"> j
                    </label>
                    <span class="text-xs {{ $overdue ? 'font-semibold text-terracotta-600' : 'text-nuit-500' }}">
                        Échéance {{ $plan->next_due_on->format('d/m/Y') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <label class="flex items-center gap-1 text-xs text-nuit-600"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="rounded border-nuit-300 text-terracotta-500"> Actif</label>
                        <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">OK</button>
                        <button form="del-plan-{{ $plan->id }}" class="text-xs text-red-600">Suppr.</button>
                    </div>
                </div>
                <input type="hidden" name="priority" value="{{ $plan->priority }}">
                <input type="hidden" name="assigned_to" value="{{ $plan->assigned_to }}">
                <textarea name="checklist" rows="2" class="mt-2 w-full rounded-lg border border-nuit-200 px-3 py-2 text-xs">{{ implode("\n", $plan->checklist ?? []) }}</textarea>
            </form>
            <form method="POST" action="{{ route('admin.maintenance.plans.destroy', $plan) }}" id="del-plan-{{ $plan->id }}" onsubmit="return confirm('Supprimer ce plan ?')">@csrf @method('DELETE')</form>
        @endforeach
    </div>
</div>
@endsection
