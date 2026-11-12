{{-- Daftar kartu kelas InaYule; dipakai section InaYule (8 kelas) dan halaman Kelas InaYule (semua). --}}
@php
    $units = ['day' => 'hari', 'week' => 'minggu', 'month' => 'bulan', 'session' => 'sesi'];
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
@endphp

@if ($packages->isEmpty())
    <p class="empty-note">Kelas baru segera dibuka. Hubungi kami untuk info jadwal terdekat.</p>
@else
    <div class="class-grid">
        @foreach ($packages as $package)
            <article class="class-card">
                <div class="class-card__top">
                    @if ($package->level)
                        <span class="class-card__level">{{ $package->level->name }}</span>
                    @endif
                    @if ($package->hasActivePromo())
                        <span class="class-card__promo">Promo</span>
                    @endif
                </div>

                <h3 class="class-card__name">{{ $package->name }}</h3>
                <p class="class-card__meta">{{ collect([$package->type?->name, $package->courseClass?->name])->filter()->implode(' · ') }}</p>

                <ul class="class-card__facts">
                    <li>{{ $package->duration_value }} {{ $units[$package->duration_unit] ?? $package->duration_unit }}</li>
                    @if ((float) $package->credits > 0)
                        <li>{{ $number($package->credits) }} sesi</li>
                    @endif
                </ul>

                <div class="class-card__price">
                    @if ($package->hasActivePromo())
                        <s>Rp {{ number_format((float) $package->price, 0, ',', '.') }}</s>
                    @endif
                    <strong>Rp {{ number_format($package->effectivePrice(), 0, ',', '.') }}</strong>
                </div>

                {{-- Belum login: diarahkan ke halaman Login dulu, lalu otomatis kembali ke halaman beli kelas. --}}
                <a href="{{ route('inayule.index') }}" class="btn btn--primary btn--sm btn--block">Beli Kelas</a>
            </article>
        @endforeach
    </div>
@endif
