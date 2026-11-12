{{-- Daftar kartu kelas InaYule + filter tanpa muat ulang halaman (lihat home.js).
     $limit: jumlah kartu yang tampil (section beranda = 8); kosong = semua (halaman Kelas InaYule). --}}
@php
    $units = ['day' => 'hari', 'week' => 'minggu', 'month' => 'bulan', 'session' => 'sesi'];
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $limit = $limit ?? 0;
    $options = fn ($names) => $names->filter()->unique()->sort()->values();
@endphp

@if ($packages->isEmpty())
    <p class="empty-note">Kelas baru segera dibuka. Hubungi kami untuk info jadwal terdekat.</p>
@else
    <div class="class-browser" data-class-browser data-limit="{{ $limit }}">
        <form class="class-filter" role="search" onsubmit="return false">
            <select name="type" aria-label="Filter type">
                <option value="">Semua Type</option>
                @foreach ($options($packages->map(fn ($p) => $p->type?->name)) as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>
            <select name="class" aria-label="Filter class">
                <option value="">Semua Class</option>
                @foreach ($options($packages->map(fn ($p) => $p->courseClass?->name)) as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>
            <select name="level" aria-label="Filter level">
                <option value="">Semua Level</option>
                @foreach ($options($packages->map(fn ($p) => $p->level?->name)) as $name)
                    <option value="{{ $name }}">{{ $name }}</option>
                @endforeach
            </select>
            <label class="class-filter__search">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="q" placeholder="Cari nama kelas..." aria-label="Cari nama kelas" autocomplete="off">
            </label>
            <button type="reset" class="class-filter__reset">Reset</button>
        </form>

        <p class="class-count" aria-live="polite"><strong data-class-count>{{ $packages->count() }}</strong> kelas tersedia</p>

        <div class="class-grid">
            @foreach ($packages as $package)
                <article class="class-card" data-type="{{ $package->type?->name }}" data-class="{{ $package->courseClass?->name }}"
                    data-level="{{ $package->level?->name }}" data-name="{{ \Illuminate\Support\Str::lower($package->name) }}"
                    @if ($limit && $loop->iteration > $limit) hidden @endif>
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

        <p class="empty-note" data-class-empty hidden>Belum ada kelas yang cocok. Coba ubah atau reset filternya.</p>

        @if ($limit)
            {{-- Beranda: ajakan ke halaman Semua Kelas dan Placement Test (quiz), berdampingan di tengah bawah kartu. --}}
            <div class="class-more">
                <div class="class-more__actions">
                    <a href="{{ route('frontend.inayule.classes') }}" class="btn-more">
                        Lihat Selengkapnya
                        <span class="btn-more__icon" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                    </a>
                    <a href="{{ route('frontend.form.wizard') }}" class="btn-more btn-more--solid">
                        Placement Test
                        <span class="btn-more__icon" aria-hidden="true"><i class="bi bi-pencil-square"></i></span>
                    </a>
                </div>
                <small>{{ $packages->count() }} kelas Mandarin tersedia</small>
            </div>
        @endif
    </div>
@endif
