@extends('layouts.frontend')
@section('content')

{{--
    Halaman "InaYule" (modul kursus Mandarin: Buy Packages / History /
    Schedule).

    STEP 1 (15 September 2026): baru tampilan skeleton, 3 tab semua
    placeholder "Data not found".

    STEP 2 (16 September 2026, permintaan user): tab "Buy Packages" sekarang
    katalog CoursePackage sungguhan (filter Type/Class/Level + search, gaya
    "catalog product"), datanya dikirim dari
    App\Http\Controllers\StudentPortal\InaYulePackageController (lihat
    docblock-nya).

    STEP 4 (16 September 2026, permintaan user -- "knp angka nol disable
    juga ya button nya kan tidak ada pembayaran ya"): package dengan harga
    EFEKTIF Rp 0 (trial) sekarang tombolnya AKTIF ("Klaim Gratis") dan
    langsung klaim credit lewat InaYulePackageController::claimTrial() --
    lihat docblock method itu untuk pengamanannya. Package BERBAYAR tetap
    "Beli" disabled -- logic potong saldo Deposit belum dibangun, masih
    tahap diskusi konsep.

    Tab History SEKARANG diisi data sungguhan dari CoursePackagePurchase.
    Tab Schedule MASIH placeholder "Data not found" -- baru bisa diisi
    setelah jadwal kelas/booking sesi terhubung ke student (belum dibangun).

    Pola widget (widget-content-area br-8) & style tabel disamakan dengan
    resources/views/student-portal/inastudy/index.blade.php supaya
    konsisten satu payung produk InaStudy/InaYule.
--}}

<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3">InaYule</h4>

            {{--
                STEP 4 (16 September 2026): tampilkan sisa saldo credit
                student ($creditBalance dari CourseCredit::currentBalanceFor(),
                dikirim InaYulePackageController::index()) -- 0 untuk staff/
                admin yang tidak punya Student, atau student yang belum
                pernah klaim/beli apapun.
            --}}
            <div class="mb-3">
                <span class="badge bg-primary" style="font-size:14px;">
                    Sisa Credit: {{ rtrim(rtrim(number_format((float) $creditBalance, 2, ',', '.'), '0'), ',') }} Sesi
                </span>
            </div>

            <div style="overflow-x:auto;">
                <ul class="nav nav-tabs flex-nowrap text-nowrap" id="inayuleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inayule-buy-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-buy" type="button" role="tab"
                            aria-controls="inayule-buy" aria-selected="true">Buy Packages</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-history-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-history" type="button" role="tab"
                            aria-controls="inayule-history" aria-selected="false">History</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-schedule-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-schedule" type="button" role="tab"
                            aria-controls="inayule-schedule" aria-selected="false">Schedule</button>
                    </li>
                </ul>
            </div>

            <div class="tab-content pt-4" id="inayuleTabContent">
                {{-- Tab 1: Buy Packages --}}
                <div class="tab-pane fade show active" id="inayule-buy" role="tabpanel"
                    aria-labelledby="inayule-buy-tab">

                    {{-- Filter: Type / Class / Level + Search -- GET ke route yang
                         sama, jadi bisa di-bookmark/refresh & tetap kepake sama
                         withQueryString() di pagination-nya. --}}
                    <form method="GET" action="{{ route('inayule.index') }}" class="row g-2 mb-4">
                        <div class="col-6 col-md-3">
                            <select name="course_type_id" class="form-select">
                                <option value="">-- Semua Type --</option>
                                @foreach ($types as $type)
                                    <option value="{{ $type->id }}" @selected($filters['course_type_id'] == $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <select name="course_class_id" class="form-select">
                                <option value="">-- Semua Class --</option>
                                @foreach ($classes as $class)
                                    <option value="{{ $class->id }}" @selected($filters['course_class_id'] == $class->id)>{{ $class->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <select name="course_level_id" class="form-select">
                                <option value="">-- Semua Level --</option>
                                @foreach ($levels as $level)
                                    <option value="{{ $level->id }}" @selected($filters['course_level_id'] == $level->id)>{{ $level->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama package..."
                                value="{{ $filters['search'] }}">
                        </div>
                        <div class="col-12 col-md-1 d-grid">
                            <button class="btn btn-outline-primary">Cari</button>
                        </div>
                    </form>

                    @if($packages->isEmpty())
                        <div class="text-center text-muted py-5">
                            <div>Data not found</div>
                        </div>
                    @else
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
                            @foreach ($packages as $package)
                                <div class="col">
                                    <div class="card h-100 shadow-sm">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title fw-bold mb-2">{{ $package->name }}</h6>

                                            <div class="mb-2">
                                                <span class="badge bg-primary me-1">{{ optional($package->type)->name ?? '-' }}</span>
                                                <span class="badge bg-info text-dark me-1">{{ optional($package->courseClass)->name ?? '-' }}</span>
                                                <span class="badge bg-secondary">{{ optional($package->level)->name ?? '-' }}</span>
                                            </div>

                                            <div class="text-muted mb-2" style="font-size:13px;">
                                                {{ $package->duration_value }} {{ ucfirst($package->duration_unit) }}{{ $package->duration_value > 1 ? 's' : '' }}
                                                &middot;
                                                {{ rtrim(rtrim(number_format((float) $package->credits, 2, ',', '.'), '0'), ',') }} Sesi/Credit
                                            </div>

                                            @if($package->description)
                                                <p class="text-muted small mb-3" style="flex-grow:1;">{{ \Illuminate\Support\Str::limit($package->description, 100) }}</p>
                                            @else
                                                <div style="flex-grow:1;"></div>
                                            @endif

                                            <div class="mb-3">
                                                @if($package->promo_price !== null && (float) $package->promo_price < (float) $package->price)
                                                    <div class="text-muted text-decoration-line-through" style="font-size:13px;">
                                                        Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                                    </div>
                                                    <div class="fw-bold text-danger" style="font-size:18px;">
                                                        Rp {{ number_format((float) $package->promo_price, 0, ',', '.') }}
                                                    </div>
                                                @else
                                                    <div class="fw-bold" style="font-size:18px;">
                                                        Rp {{ number_format((float) $package->price, 0, ',', '.') }}
                                                    </div>
                                                @endif
                                            </div>

                                            @php
                                                $isFreeTrial = $package->effectivePrice() <= 0.0;
                                                $alreadyClaimedTrial = in_array($package->id, $claimedTrialPackageIds, true);
                                            @endphp

                                            @if($isFreeTrial && $alreadyClaimedTrial)
                                                <button type="button" class="btn btn-outline-secondary w-100" disabled>
                                                    Sudah Diklaim
                                                </button>
                                            @elseif($isFreeTrial)
                                                {{--
                                                    STEP 4 (16 September 2026, permintaan user -- "knp angka
                                                    nol disable juga ya button nya kan tidak ada pembayaran
                                                    ya"): package harga efektif Rp 0 (trial) -- tombol AKTIF,
                                                    langsung klaim credit lewat
                                                    InaYulePackageController::claimTrial() (TIDAK lewat
                                                    Deposit sama sekali, tidak ada uang berpindah). Harga
                                                    tetap dihitung ULANG di server (bukan percaya tombol ini
                                                    aktif di browser) -- lihat docblock claimTrial().
                                                --}}
                                                <form action="{{ route('inayule.claim-trial', $package->id) }}" method="POST" class="m-0" onsubmit="return confirm('Klaim trial package ini sekarang?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success w-100">
                                                        Klaim Gratis
                                                    </button>
                                                </form>
                                            @else
                                                {{-- Tombol Beli SENGAJA disabled -- logic pembelian
                                                     (potong saldo Deposit) belum dibangun, lihat docblock
                                                     di atas file ini. --}}
                                                <button type="button" class="btn btn-primary w-100" disabled
                                                    title="Fitur pembelian akan segera hadir">
                                                    Beli
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4">
                            {{ $packages->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>

                {{-- Tab 2: History --}}
                <div class="tab-pane fade" id="inayule-history" role="tabpanel"
                    aria-labelledby="inayule-history-tab">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Tanggal Beli</th>
                                    <th>Credits</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $purchase)
                                    <tr>
                                        <td>{{ optional($purchase->coursePackage)->name ?? '-' }}</td>
                                        <td>{{ $purchase->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                        <td>{{ rtrim(rtrim(number_format((float) $purchase->credits_granted, 2, ',', '.'), '0'), ',') }}</td>
                                        <td>
                                            @if($purchase->status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($purchase->status) }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">Data not found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab 3: Schedule --}}
                <div class="tab-pane fade" id="inayule-schedule" role="tabpanel"
                    aria-labelledby="inayule-schedule-tab">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Pengajar</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">Data not found</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
