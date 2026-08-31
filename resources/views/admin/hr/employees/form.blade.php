@extends('admin.layout')
@section('title', 'Nouvel employé')

@section('content')
<div class="mx-auto max-w-2xl">
    <a href="{{ route('admin.hr.employees.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Employés</a>

    <form method="POST" action="{{ route('admin.hr.employees.store') }}" class="mt-4 flex flex-col gap-4 rounded-2xl bg-white p-5 shadow-card">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <label class="text-sm font-semibold text-nuit-800">Civilité
                <select name="civility" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option>Mme</option><option>M.</option><option>Autre</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Prénom
                <input type="text" name="first_name" value="{{ old('first_name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Nom
                <input type="text" name="last_name" value="{{ old('last_name') }}" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Matricule
                <input type="text" name="matricule" value="{{ old('matricule') }}" placeholder="auto" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Service
                <select name="department_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="">—</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Fonction
                <select name="job_position_id" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
                    <option value="">—</option>
                    @foreach ($departments as $d)<optgroup label="{{ $d->name }}">
                        @foreach ($d->positions as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach
                    </optgroup>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-nuit-800">Téléphone
                <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">E-mail
                <input type="email" name="email" value="{{ old('email') }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
            <label class="text-sm font-semibold text-nuit-800">Date d’embauche
                <input type="date" name="hire_date" value="{{ old('hire_date', now()->toDateString()) }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-2 text-sm font-normal">
            </label>
        </div>

        <input type="hidden" name="employment_status" value="active">

        <fieldset class="rounded-xl border border-sable-200 p-4">
            <legend class="px-1 text-sm font-semibold text-nuit-800">Contrat initial (facultatif)</legend>
            <div class="grid gap-3 sm:grid-cols-4">
                <select name="contract_type" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach ($contractTypes as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                </select>
                <input type="date" name="contract_start" value="{{ now()->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="number" name="base_salary" placeholder="Salaire de base" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="number" name="weekly_hours" value="40" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </div>
        </fieldset>

        <button class="rounded-full bg-terracotta-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-terracotta-600">Créer l’employé</button>
    </form>
</div>
@endsection
