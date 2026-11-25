{{--
    Kartu lokasi belajar InaYule (25 November 2026): langkah pertama pengunjung.
    Setiap kartu membawa ke halaman cabang (Course Type -> Course Class -> katalog).
    $branches: hasil FrontendController::publicBranches(), sudah berisi paket aktifnya.
--}}
@if ($branches->isEmpty())
    <p class="empty-note">Kelas baru segera dibuka. Hubungi kami untuk info jadwal terdekat.</p>
@else
    <div class="branch-grid">
        @foreach ($branches as $branch)
            @php
                $packages = $branch->coursePackages;
                $online = $branch->isOnline();
                $priceFrom = $packages->map(fn ($package) => $package->effectivePrice())->filter(fn ($price) => $price > 0)->min();
                $classNames = $packages->map(fn ($package) => $package->courseClass?->name)->filter()->unique()->sort()->values();
                $typeNames = $packages->map(fn ($package) => $package->type?->name)->filter()->unique()->sort()->values();
            @endphp
            <a href="{{ route('frontend.inayule.branch', $branch->slug()) }}" class="branch-card {{ $online ? 'branch-card--online' : '' }}"
                data-hanzi="{{ $online ? '家' : '学' }}">
                <span class="branch-card__badge">
                    <i class="bi bi-{{ $online ? 'laptop' : 'geo-alt-fill' }}" aria-hidden="true"></i>
                    {{ $online ? 'Belajar dari rumah' : 'Kelas tatap muka' }}
                </span>

                <h3 class="branch-card__name">{{ $branch->name }}</h3>
                <p class="branch-card__place">
                    @if ($online)
                        Bertemu pengajar lewat video call, dari mana saja kamu berada.
                    @else
                        {{ \Illuminate\Support\Str::limit($branch->address ?: 'Belajar langsung bersama pengajar di kelas.', 90) }}
                    @endif
                </p>

                @if ($typeNames->isNotEmpty() || $classNames->isNotEmpty())
                    <ul class="branch-card__tags" aria-label="Program tersedia">
                        @foreach ($typeNames->merge($classNames)->take(5) as $name)
                            <li>{{ $name }}</li>
                        @endforeach
                    </ul>
                @endif

                <span class="branch-card__foot">
                    <span>
                        <strong>{{ $packages->count() }} kelas</strong>
                        @if ($priceFrom)
                            <small>mulai Rp {{ number_format($priceFrom, 0, ',', '.') }}</small>
                        @endif
                    </span>
                    <span class="branch-card__go" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                </span>
            </a>
        @endforeach
    </div>
@endif
