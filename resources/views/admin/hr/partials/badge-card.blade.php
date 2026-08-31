<div class="card">
    <div class="card__in">
        <div class="brand">
            <img src="{{ \App\Support\Branding::logoMono() }}" alt="">
            <span>{{ config('hotel.name') }}</span>
        </div>

        <div class="body">
            <div class="identity">
                <div class="name">{{ mb_strtoupper($e->last_name) }} {{ $e->first_name }}</div>
                <div class="role">{{ $e->position->title ?? 'Personnel' }}</div>
                <div class="service">
                    Service
                    <b>{{ $e->department->name ?? '—' }}</b>
                </div>
                <div class="mat">{{ $e->matricule }}</div>
            </div>

            <div class="qrwrap">
                <div class="qr">
                    <img src="{{ route('admin.hr.employees.qr', $e) }}" alt="QR pointage {{ $e->matricule }}">
                </div>
                <div class="qr__cap">Pointage</div>
            </div>
        </div>

        <div class="foot">Badge nominatif — usage strictement personnel · {{ config('hotel.city') }}</div>
    </div>
</div>
