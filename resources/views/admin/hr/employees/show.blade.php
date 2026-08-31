@extends('admin.layout')
@section('title', $employee->fullName())

@php
    $canEdit = auth()->user()->hasRole('rh');
    $canTerminate = auth()->user()->hasRole('direction');
@endphp

@section('content')
<div x-data="{ tab: 'infos' }" class="flex flex-col gap-5">
    <a href="{{ route('admin.hr.employees.index') }}" class="text-sm text-nuit-500 hover:text-terracotta-600">← Employés</a>

    <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-display text-2xl font-semibold text-nuit-900">{{ $employee->civility }} {{ $employee->fullName() }}</h2>
        <x-admin.badge :status="$employee->employment_status" />
        <span class="font-mono text-sm text-nuit-400">{{ $employee->matricule }}</span>
        <span class="text-sm text-nuit-500">{{ $employee->department->name ?? '—' }} · {{ $employee->position->title ?? '—' }}</span>
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-sable-200 pb-2 text-sm">
        @foreach (['infos' => 'Infos', 'remuneration' => 'Rémunération', 'conges' => 'Congés', 'pointage' => 'Pointage', 'paie' => 'Bulletins', 'evaluation' => 'Évaluation'] as $k => $label)
            <button x-on:click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'bg-nuit-900 text-white' : 'bg-sable-100 text-nuit-700'" class="rounded-full px-3.5 py-1.5 font-medium">{{ $label }}</button>
        @endforeach
    </nav>

    @if ($employee->isTerminated())
        <div class="rounded-xl border border-terracotta-300 bg-terracotta-50 px-4 py-3 text-sm text-terracotta-800">
            <strong>Sorti(e) des effectifs</strong> le {{ optional($employee->termination_date)->translatedFormat('d F Y') }}
            @if ($employee->terminationLabel()) — {{ $employee->terminationLabel() }}@endif.
            @if ($employee->notice_end_date) Fin de préavis : {{ $employee->notice_end_date->translatedFormat('d F Y') }}.@endif
            @if ($employee->termination_notes)<span class="block text-terracotta-700">{{ $employee->termination_notes }}</span>@endif
        </div>
    @endif

    {{-- INFOS + contrats + documents --}}
    <div x-show="tab === 'infos'" class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.hr.employees.update', $employee) }}" class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
            @csrf @method('PUT')
            <h3 class="font-display text-lg font-semibold text-nuit-900">Fiche</h3>
            <input type="hidden" name="matricule" value="{{ $employee->matricule }}">
            <div class="grid gap-3 sm:grid-cols-2">
                <input type="text" name="first_name" value="{{ $employee->first_name }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="text" name="last_name" value="{{ $employee->last_name }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <select name="department_id" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="">— service —</option>
                    @foreach ($departments as $d)<option value="{{ $d->id }}" @selected($employee->department_id == $d->id)>{{ $d->name }}</option>@endforeach
                </select>
                <select name="job_position_id" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <option value="">— fonction —</option>
                    @foreach ($departments as $d)<optgroup label="{{ $d->name }}">@foreach ($d->positions as $p)<option value="{{ $p->id }}" @selected($employee->job_position_id == $p->id)>{{ $p->title }}</option>@endforeach</optgroup>@endforeach
                </select>
                <input type="text" name="phone" value="{{ $employee->phone }}" placeholder="Téléphone" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="email" name="email" value="{{ $employee->email }}" placeholder="E-mail" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="date" name="hire_date" value="{{ optional($employee->hire_date)->toDateString() }}" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <select name="employment_status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    @foreach (config('hr.employment_statuses') as $k => $label)<option value="{{ $k }}" @selected($employee->employment_status === $k)>{{ $label }}</option>@endforeach
                </select>
                <select name="marital_status" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm" title="Situation familiale">
                    @foreach ($maritalStatuses as $k => $label)<option value="{{ $k }}" @selected($employee->marital_status === $k)>{{ $label }}</option>@endforeach
                </select>
                <input type="number" name="dependents_count" min="0" max="20" value="{{ $employee->dependents_count }}" placeholder="Personnes à charge" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
            </div>
            <label class="flex items-center gap-2 rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                <input type="hidden" name="tracks_attendance" value="0">
                <input type="checkbox" name="tracks_attendance" value="1" @checked($employee->tracks_attendance) @disabled(! $canEdit)
                       class="h-4 w-4 rounded border-nuit-300 text-terracotta-500 focus:ring-terracotta-400">
                <span>Soumis au pointage <span class="text-xs text-nuit-400">— entrée / sortie à la borne</span></span>
            </label>
            <p class="text-xs text-nuit-400">Quotient familial : <strong>{{ rtrim(rtrim(number_format(\App\Services\PayrollEngine::parts($employee), 1), '0'), '.') }} part(s)</strong> · Ancienneté : <strong>{{ $employee->seniorityYears() }} an(s)</strong></p>
            <p class="text-xs text-nuit-400">Solde de congés : <strong>{{ $employee->leave_balance_days }} j</strong> · Avances en cours : <strong>{{ money($employee->outstandingAdvances()) }}</strong></p>
            @if ($canEdit)<button class="self-start rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer</button>@endif
        </form>

        <div class="flex flex-col gap-4">
            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Contrats</h3>
                @foreach ($employee->contracts as $c)
                    <div class="flex items-center justify-between border-b border-sable-100 py-1.5 text-sm">
                        <span>{{ $c->typeLabel() }} · {{ money($c->base_salary) }} <span class="text-xs text-nuit-400">/ {{ $c->weekly_hours }}h</span></span>
                        <span class="text-xs {{ $c->status === 'active' ? 'text-emerald-700' : 'text-nuit-400' }}">{{ $c->start_date->format('d/m/y') }}{{ $c->end_date ? ' → '.$c->end_date->format('d/m/y') : '' }}</span>
                    </div>
                @endforeach
                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.hr.employees.contract', $employee) }}" class="mt-3 grid gap-2 sm:grid-cols-4">
                        @csrf
                        <select name="type" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">@foreach ($contractTypes as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        <input type="date" name="start_date" value="{{ now()->toDateString() }}" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                        <input type="number" name="base_salary" placeholder="Base" required class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                        <div class="flex gap-1">
                            <input type="number" name="weekly_hours" value="40" class="w-16 rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                            <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">+ Contrat</button>
                        </div>
                        <input type="date" name="end_date" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm sm:col-span-2" title="Fin (CDD)">
                    </form>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Documents</h3>
                @forelse ($employee->documents as $doc)
                    <div class="flex items-center justify-between border-b border-sable-100 py-1.5 text-sm">
                        <span>{{ $doc->title }} <span class="text-xs text-nuit-400">· {{ $doc->categoryLabel() }}</span></span>
                        <a href="{{ route('admin.hr.documents.download', $doc) }}" class="text-xs font-semibold text-terracotta-600 hover:underline">Télécharger</a>
                    </div>
                @empty
                    <p class="text-xs text-nuit-400">Aucun document.</p>
                @endforelse
                @if ($canEdit)
                    <form method="POST" action="{{ route('admin.hr.employees.document', $employee) }}" enctype="multipart/form-data" class="mt-3 grid gap-2 sm:grid-cols-3">
                        @csrf
                        <select name="category" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">@foreach ($documentCategories as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                        <input type="text" name="title" placeholder="Intitulé" required class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                        <div class="flex gap-1">
                            <input type="file" name="file" required class="flex-1 text-xs">
                            <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">+</button>
                        </div>
                    </form>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-4 shadow-card">
                <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Badge de pointage</h3>
                @unless ($employee->tracks_attendance)
                    <p class="mb-3 rounded-lg bg-sable-100 px-3 py-2 text-xs text-nuit-500">
                        Cet employé n’est <strong>pas soumis au pointage</strong> : le badge et le code de secours restent sans effet à la borne, et il n’apparaît pas sur l’écran de pointage quotidien.
                    </p>
                @endunless
                <div class="flex items-center gap-4">
                    <img src="{{ route('clock.photo', $employee->matricule) }}" alt="Photo" class="h-20 w-20 shrink-0 rounded-full border border-sable-200 object-cover">
                    <img src="{{ route('admin.hr.employees.qr', $employee) }}" alt="Badge QR" width="96" height="96" class="h-24 w-24 shrink-0 rounded-lg border border-sable-200">
                    <div class="text-sm">
                        <p class="text-xs text-nuit-400">Le scan du QR pointe directement l’entrée / la sortie. Le code personnel n’est demandé qu’en secours.</p>
                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                            <a href="{{ route('admin.hr.employees.card', $employee) }}" target="_blank"
                               class="inline-flex items-center gap-1 rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-terracotta-500">
                                Imprimer sa carte ↗
                            </a>
                            <a href="{{ route('admin.hr.badges') }}" target="_blank" class="text-xs font-semibold text-terracotta-600 hover:underline">Planche complète ↗</a>
                            <span class="text-xs {{ $employee->pin_hash ? 'text-emerald-700' : 'text-terracotta-700' }}">{{ $employee->pin_hash ? 'code défini' : 'code non défini' }}</span>
                        </div>
                    </div>
                </div>
                @if ($canEdit)
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <form method="POST" action="{{ route('admin.hr.employees.photo', $employee) }}" enctype="multipart/form-data" class="flex items-end gap-2">
                            @csrf
                            <label class="flex-1 text-xs font-semibold text-nuit-500">Photo de l’agent
                                <input type="file" name="photo" accept="image/*" required class="mt-1 w-full text-xs">
                            </label>
                            <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">Charger</button>
                        </form>
                        <form method="POST" action="{{ route('admin.hr.employees.pin', $employee) }}" class="flex items-end gap-2">
                            @csrf
                            <label class="flex-1 text-xs font-semibold text-nuit-500">Code de secours
                                <input type="text" name="pin" inputmode="numeric" placeholder="4 à 6 chiffres" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            </label>
                            <button class="rounded-full bg-nuit-900 px-3 py-1.5 text-xs font-semibold text-white">Définir</button>
                        </form>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl bg-white p-5 shadow-card">
                <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Administratif</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($attestationTypes as $k => $label)
                        <a href="{{ route('admin.hr.employees.attestation', [$employee, $k]) }}?print=1" target="_blank"
                           class="rounded-full border border-nuit-200 px-3 py-1.5 text-xs font-semibold text-nuit-700 hover:border-terracotta-400">{{ $label }} ↗</a>
                    @endforeach
                </div>

                @if ($canTerminate && ! $employee->isTerminated())
                    <form method="POST" action="{{ route('admin.hr.employees.terminate', $employee) }}"
                          x-data="{ open: false }" class="mt-4 border-t border-sable-100 pt-4">
                        @csrf
                        <button type="button" x-on:click="open = !open" class="text-sm font-semibold text-terracotta-700 hover:underline">
                            <span x-text="open ? 'Annuler' : 'Enregistrer une sortie de salarié'"></span>
                        </button>
                        <div x-show="open" x-cloak class="mt-3 grid gap-2 sm:grid-cols-2">
                            <label class="text-xs font-semibold text-nuit-500">Date de sortie
                                <input type="date" name="termination_date" required value="{{ now()->toDateString() }}" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-nuit-500">Motif
                                <select name="termination_type" required class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                                    @foreach ($terminationReasons as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-nuit-500">Fin de préavis (facultatif)
                                <input type="date" name="notice_end_date" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm">
                            </label>
                            <label class="text-xs font-semibold text-nuit-500 sm:col-span-2">Note
                                <textarea name="termination_notes" rows="2" class="mt-1 w-full rounded-lg border border-nuit-200 px-3 py-1.5 text-sm"></textarea>
                            </label>
                            <p class="text-xs text-nuit-400 sm:col-span-2">Clôt le contrat actif, annule les shifts et congés à venir, désactive le compte lié.</p>
                            <button class="justify-self-start rounded-full bg-terracotta-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-terracotta-700">Confirmer la sortie</button>
                        </div>
                    </form>
                @endif
            </section>
        </div>
    </div>

    {{-- RÉMUNÉRATION --}}
    <div x-show="tab === 'remuneration'" x-cloak class="flex flex-col gap-4">
        <form method="POST" action="{{ route('admin.hr.employees.components', $employee) }}" class="rounded-2xl bg-white p-5 shadow-card">
            @csrf
            <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Composants de rémunération</h3>
            <div class="flex flex-col gap-2">
                @foreach ($components->whereIn('system_role', [null, 'base']) as $c)
                    @php $esc = $employee->salaryComponents->firstWhere('salary_component_id', $c->id); @endphp
                    <div class="flex flex-wrap items-center gap-3 border-b border-sable-100 py-2 text-sm">
                        <label class="flex flex-1 items-center gap-2">
                            <input type="checkbox" name="components[{{ $c->id }}][enabled]" value="1" @checked($esc || $c->applies_to_all || $c->system_role === 'base') @disabled($c->applies_to_all || $c->system_role === 'base') class="rounded border-nuit-300 text-terracotta-500">
                            <span class="font-medium text-nuit-900">{{ $c->name }}</span>
                            <span class="text-xs text-nuit-400">{{ $c->kind === 'earning' ? 'gain' : 'retenue' }}{{ $c->is_taxable ? ' · imposable' : '' }}</span>
                        </label>
                        @if ($c->calc === 'fixed')
                            <input type="number" name="components[{{ $c->id }}][amount]" value="{{ $esc->amount ?? $c->default_amount }}" placeholder="Montant" class="w-32 rounded-lg border border-nuit-200 px-2 py-1 text-sm">
                        @elseif ($c->calc === 'percent_base')
                            <div class="flex items-center gap-1"><input type="number" step="0.01" name="components[{{ $c->id }}][rate]" value="{{ $esc->rate ?? $c->default_rate }}" class="w-20 rounded-lg border border-nuit-200 px-2 py-1 text-sm"> <span class="text-xs text-nuit-400">%</span></div>
                        @else
                            <span class="text-xs text-nuit-400">calculé au pointage</span>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($canEdit)<button class="mt-3 rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer</button>@endif
        </form>

        <section class="rounded-2xl bg-white p-5 shadow-card">
            <h3 class="mb-2 font-display text-lg font-semibold text-nuit-900">Avances sur salaire</h3>
            @forelse ($employee->advances as $a)
                <div class="flex items-center justify-between border-b border-sable-100 py-1.5 text-sm">
                    <span>{{ money($a->amount) }} <span class="text-xs text-nuit-400">· {{ $a->granted_on->format('d/m/Y') }}</span></span>
                    <span class="text-xs {{ $a->status === 'repaid' ? 'text-emerald-700' : 'text-terracotta-700' }}">{{ $a->status === 'repaid' ? 'Remboursée' : 'Reste '.money($a->balance()) }}</span>
                </div>
            @empty
                <p class="text-xs text-nuit-400">Aucune avance.</p>
            @endforelse
            @if ($canEdit)
                <form method="POST" action="{{ route('admin.hr.employees.advance', $employee) }}" class="mt-3 flex gap-2">
                    @csrf
                    <input type="number" name="amount" placeholder="Montant" required class="w-32 rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                    <input type="date" name="granted_on" value="{{ now()->toDateString() }}" class="rounded-lg border border-nuit-200 px-2 py-1.5 text-sm">
                    <button class="rounded-full bg-nuit-900 px-4 py-1.5 text-xs font-semibold text-white">Accorder</button>
                </form>
            @endif
        </section>
    </div>

    {{-- CONGÉS --}}
    <div x-show="tab === 'conges'" x-cloak class="rounded-2xl bg-white p-5 shadow-card">
        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Historique des congés — solde {{ $employee->leave_balance_days }} j</h3>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400"><tr><th class="py-2">Type</th><th class="py-2">Période</th><th class="py-2">Jours</th><th class="py-2">Statut</th></tr></thead>
            <tbody>
                @forelse ($employee->leaveRequests as $l)
                    <tr class="border-t border-sable-100"><td class="py-2">{{ $l->typeLabel() }}</td><td class="py-2 text-xs text-nuit-500">{{ $l->start_date->format('d/m/y') }} → {{ $l->end_date->format('d/m/y') }}</td><td class="py-2">{{ $l->days }}</td><td class="py-2"><x-admin.badge :status="$l->status" /></td></tr>
                @empty
                    <tr><td colspan="4" class="py-4 text-center text-nuit-400">Aucune demande.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- POINTAGE --}}
    <div x-show="tab === 'pointage'" x-cloak class="rounded-2xl bg-white p-5 shadow-card">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-display text-lg font-semibold text-nuit-900">30 derniers pointages</h3>
            <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $employee->tracks_attendance ? 'bg-emerald-50 text-emerald-700' : 'bg-sable-100 text-nuit-500' }}">
                {{ $employee->tracks_attendance ? 'Soumis au pointage' : 'Hors pointage' }}
            </span>
        </div>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400"><tr><th class="py-2">Date</th><th class="py-2">Entrée</th><th class="py-2">Sortie</th><th class="py-2">Travaillé</th><th class="py-2">Retard</th><th class="py-2">HS</th></tr></thead>
            <tbody>
                @forelse ($employee->attendance()->latest('work_date')->take(30)->get() as $a)
                    <tr class="border-t border-sable-100">
                        <td class="py-2 text-xs text-nuit-500">{{ $a->work_date->format('d/m/Y') }}</td>
                        <td class="py-2">{{ optional($a->clock_in)->format('H:i') ?? ($a->status === 'absent' ? 'Absent' : '—') }}</td>
                        <td class="py-2">{{ optional($a->clock_out)->format('H:i') ?? '—' }}</td>
                        <td class="py-2">{{ $a->workedHours() }} h</td>
                        <td class="py-2 {{ $a->late_minutes ? 'text-terracotta-700' : 'text-nuit-400' }}">{{ $a->late_minutes ? $a->late_minutes.' min' : '—' }}</td>
                        <td class="py-2 {{ $a->overtime_minutes ? 'text-emerald-700' : 'text-nuit-400' }}">{{ $a->overtime_minutes ? $a->overtimeHours().' h' : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-4 text-center text-nuit-400">Aucun pointage.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAIE --}}
    <div x-show="tab === 'paie'" x-cloak class="rounded-2xl bg-white p-5 shadow-card">
        <h3 class="mb-3 font-display text-lg font-semibold text-nuit-900">Bulletins</h3>
        <table class="w-full text-sm">
            <thead class="text-left text-xs uppercase tracking-wider text-nuit-400"><tr><th class="py-2">Période</th><th class="py-2">Brut</th><th class="py-2">Retenues</th><th class="py-2">Net</th><th class="py-2"></th></tr></thead>
            <tbody>
                @forelse ($employee->payslips->sortByDesc(fn($p) => $p->run->period) as $p)
                    <tr class="border-t border-sable-100">
                        <td class="py-2">{{ $p->run->periodLabel() }}</td>
                        <td class="py-2">{{ money($p->gross) }}</td>
                        <td class="py-2">{{ money($p->total_deductions) }}</td>
                        <td class="py-2 font-semibold">{{ money($p->net) }}</td>
                        <td class="py-2 text-right"><a href="{{ route('admin.hr.payroll.payslip', [$p->run, $p]) }}" target="_blank" class="text-xs font-semibold text-terracotta-600 hover:underline">Bulletin ↗</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-4 text-center text-nuit-400">Aucun bulletin.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ÉVALUATION --}}
    <div x-show="tab === 'evaluation'" x-cloak>
        @if ($canEdit)
            <form method="POST" action="{{ route('admin.hr.employees.evaluation', $employee) }}" class="flex flex-col gap-3 rounded-2xl bg-white p-5 shadow-card">
                @csrf
                <h3 class="font-display text-lg font-semibold text-nuit-900">Nouvelle évaluation</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    <input type="text" name="period_label" placeholder="Période (ex : 2026 S1)" required class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                    <input type="number" name="score" min="0" max="100" placeholder="Score / 100" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm">
                </div>
                <textarea name="strengths" rows="2" placeholder="Points forts" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                <textarea name="improvements" rows="2" placeholder="Axes d’amélioration" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                <textarea name="objectives" rows="2" placeholder="Objectifs" class="rounded-lg border border-nuit-200 px-3 py-2 text-sm"></textarea>
                <label class="flex items-center gap-2 text-xs text-nuit-600"><input type="checkbox" name="finalize" value="1" class="rounded border-nuit-300 text-terracotta-500"> Finaliser</label>
                <button class="self-start rounded-full bg-nuit-900 px-5 py-2 text-sm font-semibold text-white">Enregistrer</button>
            </form>
        @endif
    </div>
</div>
@endsection
