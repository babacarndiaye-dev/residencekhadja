@props([
    'booking' => [],
    'tone' => 'light',   // 'light' = carte blanche (hero) · 'plain' = sur fond clair
])

@php
    $tomorrow = \Illuminate\Support\Carbon::tomorrow()->toDateString();
    $in  = $booking['check_in']  ?? $tomorrow;
    $out = $booking['check_out'] ?? \Illuminate\Support\Carbon::parse($in)->addDays(2)->toDateString();
    $adults   = (int) ($booking['adults'] ?? 2);
    $children = (int) ($booking['children'] ?? 0);
    $rooms    = (int) ($booking['rooms'] ?? 1);
    $promo    = $booking['promo'] ?? '';
    $maxRooms = config('booking.max_rooms', 5);

    $wrap = $tone === 'light'
        ? 'bg-white border border-sable-200 shadow-luxe'
        : 'bg-white border border-sable-200';
    // text-base sur mobile = pas de zoom auto iOS ; min-w-0 + max-w-full = les champs
    // (surtout `type=date` sur WebKit) ne débordent plus de leur cellule de grille.
    $ctl = 'block w-full min-w-0 max-w-full rounded-lg border border-nuit-200 '
         . 'bg-white px-3 py-3 text-base text-nuit-900 focus:border-terracotta-500 focus:outline-none '
         . 'sm:text-sm';
    // Les inputs date : on neutralise en plus le dimensionnement natif WebKit.
    $ctlDate = $ctl.' appearance-none [&::-webkit-date-and-time-value]:text-left';
    $lab = 'mb-1 block text-[0.7rem] font-semibold uppercase tracking-wider text-nuit-400';
@endphp

<form
    method="POST"
    action="{{ route('booking.search') }}"
    x-data="{ min: '{{ $tomorrow }}' }"
    {{ $attributes->class("w-full max-w-full overflow-hidden rounded-2xl p-4 sm:p-5 $wrap") }}
>
    @csrf
    <div class="grid grid-cols-2 gap-2.5 sm:gap-3 lg:grid-cols-[minmax(9rem,1.2fr)_minmax(9rem,1.2fr)_auto_auto_auto_minmax(7rem,1fr)_auto] lg:items-end">
        <div class="col-span-2 min-w-0 sm:col-span-1">
            <label for="bw_in" class="{{ $lab }}">Arrivée</label>
            <input type="date" id="bw_in" name="check_in" value="{{ old('check_in', $in) }}"
                   :min="min" x-on:change="if ($refs.out.value <= $el.value) { $refs.out.value = '' }"
                   required class="{{ $ctlDate }}">
        </div>

        <div class="col-span-2 min-w-0 sm:col-span-1">
            <label for="bw_out" class="{{ $lab }}">Départ</label>
            <input type="date" id="bw_out" name="check_out" x-ref="out" value="{{ old('check_out', $out) }}"
                   :min="$refs.in?.value || min" required class="{{ $ctlDate }}">
        </div>

        {{-- Sur mobile : les 3 sélecteurs tiennent sur une ligne (grille 3 col).
             À partir de sm : le conteneur se dissout (`display:contents`) et les
             sélecteurs rejoignent la grille parente. --}}
        <div class="col-span-2 grid grid-cols-3 gap-2.5 sm:contents">
            <div class="min-w-0">
                <label for="bw_adults" class="{{ $lab }}">Adultes</label>
                <select id="bw_adults" name="adults" class="{{ $ctl }}">
                    @for ($i = 1; $i <= 8; $i++)
                        <option value="{{ $i }}" @selected(old('adults', $adults) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="min-w-0">
                <label for="bw_children" class="{{ $lab }}">Enfants</label>
                <select id="bw_children" name="children" class="{{ $ctl }}">
                    @for ($i = 0; $i <= 6; $i++)
                        <option value="{{ $i }}" @selected(old('children', $children) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            <div class="min-w-0">
                <label for="bw_rooms" class="{{ $lab }}">Chambres</label>
                <select id="bw_rooms" name="rooms" class="{{ $ctl }}">
                    @for ($i = 1; $i <= $maxRooms; $i++)
                        <option value="{{ $i }}" @selected(old('rooms', $rooms) == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>

        <div class="col-span-2 min-w-0 sm:col-span-1">
            <label for="bw_promo" class="{{ $lab }}">Code promo</label>
            <input type="text" id="bw_promo" name="promo" value="{{ old('promo', $promo) }}"
                   placeholder="Facultatif" autocomplete="off" class="{{ $ctl }}">
        </div>

        <div class="col-span-2 lg:col-span-1">
            <button type="submit"
                    class="mt-1 inline-flex w-full items-center justify-center gap-2 bg-terracotta-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-terracotta-600 lg:w-auto">
                <x-icon name="search" size="18" />
                Vérifier les disponibilités
            </button>
        </div>
    </div>

    @if ($tone === 'light')
        <p class="mt-3 text-center text-xs text-nuit-400 lg:text-left">
            Meilleur tarif garanti en réservant directement · Annulation gratuite sur le tarif flexible
        </p>
    @endif
</form>
