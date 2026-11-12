{{-- Daftar kartu kelas InaYule + filter tanpa muat ulang halaman (lihat home.js).
     $limit: jumlah kartu yang tampil (section beranda = 8); kosong = semua (halaman Kelas InaYule). --}}
@php
    $units = ['day' => 'hari', 'week' => 'minggu', 'month' => 'bulan', 'session' => 'sesi'];
    $number = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $limit = $limit ?? 0;
    $options = fn ($names) => $names->filter()->unique()->sort()->values();
    // Beranda: tingkat kelas (1-4) ditebak dari nama level, untuk ornamen tangga level & hanzi bermakna. 0 = tidak dikenali.
    $steps = [1 => '/begin|pemula|basic|hsk ?1\b/', 2 => '/element|dasar|hsk ?2\b/', 3 => '/inter|menengah|hsk ?[34]\b/', 4 => '/advan|mahir|lanjut|hsk ?[56]\b/'];
    $levelStep = fn ($name) => collect($steps)->search(fn ($re) => preg_match($re, \Illuminate\Support\Str::lower((string) $name))) ?: 0;
    $hanzi = [1 => ['初', 'chū', 'awal'], 2 => ['基', 'jī', 'dasar'], 3 => ['中', 'zhōng', 'menengah'], 4 => ['高', 'gāo', 'mahir']];
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

        {{-- Beranda ($limit): susunan bento berwarna; data-slot menentukan ukuran & warna kotak (diatur ulang home.js saat difilter). --}}
        <div class="class-grid {{ $limit ? 'class-grid--bento' : '' }}">
            @foreach ($packages as $package)
                @php $step = $limit ? $levelStep($package->level?->name) : 0; @endphp
                <article class="class-card" data-type="{{ $package->type?->name }}" data-class="{{ $package->courseClass?->name }}"
                    data-level="{{ $package->level?->name }}" data-name="{{ \Illuminate\Support\Str::lower($package->name) }}"
                    @if ($limit) data-hanzi="{{ $hanzi[$step][0] ?? '学' }}" @if ($loop->iteration > $limit) hidden @else data-slot="{{ $loop->index }}" @endif @endif>
                    <div class="class-card__top">
                        @if ($package->level)
                            <span class="class-card__level">{{ $package->level->name }}</span>
                        @endif
                        @if ($package->hasActivePromo())
                            <span class="class-card__promo">Promo</span>
                        @endif
                        @if ($step)
                            {{-- Cap hanzi: arti level dalam bahasa Mandarin, sekalian belajar satu kata. --}}
                            <span class="class-card__seal" title="{{ $hanzi[$step][0] }} ({{ $hanzi[$step][1] }}) artinya {{ $hanzi[$step][2] }}">
                                <b lang="zh">{{ $hanzi[$step][0] }}</b><small>{{ $hanzi[$step][2] }}</small>
                            </span>
                        @endif
                    </div>

                    <h3 class="class-card__name">{{ $package->name }}</h3>
                    <p class="class-card__meta">@if ($limit && $package->type)<i class="bi bi-{{ \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower($package->type->name), 'off') ? 'geo-alt' : 'laptop' }}" aria-hidden="true"></i> @endif{{ collect([$package->type?->name, $package->courseClass?->name])->filter()->implode(' · ') }}</p>

                    <ul class="class-card__facts">
                        <li>@if ($limit)<i class="bi bi-calendar3" aria-hidden="true"></i> @endif{{ $package->duration_value }} {{ $units[$package->duration_unit] ?? $package->duration_unit }}</li>
                        @if ((float) $package->credits > 0)
                            <li>@if ($limit)<i class="bi bi-journal-text" aria-hidden="true"></i> @endif{{ $number($package->credits) }} sesi</li>
                        @endif
                    </ul>

                    @if ($step)
                        {{-- Tangga level: posisi kelas dari Pemula sampai Mahir (kartu besar menampilkan namanya). --}}
                        <div class="level-path" aria-label="Tingkat {{ $step }} dari 4">
                            <span class="level-path__label">Tingkat kesulitan <b>{{ $step }}/4</b></span>
                            <ol>
                                @foreach (['Pemula', 'Dasar', 'Menengah', 'Mahir'] as $i => $stage)
                                    <li @class(['is-on' => $i < $step, 'is-here' => $i + 1 === $step])><span>{{ $stage }}</span></li>
                                @endforeach
                            </ol>
                        </div>
                    @endif

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
                <ul class="test-perks">
                    <li><i class="bi bi-globe2" aria-hidden="true"></i> Bisa dikerjakan dari mana saja</li>
                    <li><i class="bi bi-whatsapp" aria-hidden="true"></i> Hasil langsung dikirim via WhatsApp</li>
                    <li><i class="bi bi-arrow-repeat" aria-hidden="true"></i> Materi soal selalu update</li>
                    <li><i class="bi bi-bullseye" aria-hidden="true"></i> Rekomendasi kelas sesuai level</li>
                </ul>
            </div>
        @endif
    </div>
@endif
