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

<div class="row inayule-page">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3 ina-title d-none d-md-block">InaYule</h4>

            {{-- Tampilan HP: kartu ringkasan ala aplikasi (desktop tetap memakai judul & badge di bawah). --}}
            <div class="iy-hero d-md-none">
                <div class="iy-hero-title">InaYule</div>
                <div class="iy-hero-hello">Halo, {{ auth()->user()->name ?? 'Siswa' }}</div>
                <div class="iy-hero-stats">
                    <div class="iy-hero-stat">
                        <span>Sisa Credit</span>
                        <strong>{{ rtrim(rtrim(number_format((float) $creditBalance, 2, ',', '.'), '0'), ',') }}</strong>
                    </div>
                    <div class="iy-hero-stat">
                        <span>Saldo</span>
                        <strong>Rp {{ number_format((float) $depositBalance, 0, ',', '.') }}</strong>
                    </div>
                </div>
            </div>

            {{--
                STEP 4 (16 September 2026): tampilkan sisa saldo credit
                student ($creditBalance dari CourseCredit::currentBalanceFor(),
                dikirim InaYulePackageController::index()) -- 0 untuk staff/
                admin yang tidak punya Student, atau student yang belum
                pernah klaim/beli apapun.
            --}}
            <div class="mb-3 d-none d-md-block">
                <span class="badge ina-credit">
                    Sisa Credit: {{ rtrim(rtrim(number_format((float) $creditBalance, 2, ',', '.'), '0'), ',') }} Sesi
                </span>
            </div>

            {{-- Tema InaYule: nuansa merah logo InaStudy (senada frontend Quiz &
                 University Partner). Semua gaya di-scope ke .inayule-page supaya
                 halaman lain tidak ikut berubah. --}}
            <style>
                .inayule-page { --ina-red: #d71920; --ina-red-dark: #b3121a; --ina-red-soft: #fdecec; --ina-ink: #1f2340; }
                .inayule-page .ina-title { color: var(--ina-ink); font-weight: 800; }

                /* Tab: garis bawah + ikon */
                .inayule-tabs .nav-tabs { border-bottom: 1px solid #e0e6ed; flex-wrap: nowrap; overflow-x: auto; overflow-y: hidden; }
                .inayule-tabs .nav-tabs .nav-link { display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;
                    border: 0; border-bottom: 2px solid transparent; background: transparent; color: #515365;
                    font-weight: 600; padding: 10px 16px; margin-bottom: -1px; }
                .inayule-tabs .nav-tabs .nav-link:hover { color: var(--ina-red); }
                .inayule-tabs .nav-tabs .nav-link.active { color: var(--ina-red); border-bottom-color: var(--ina-red); background: transparent; }
                .inayule-tabs .nav-tabs .nav-link svg { flex-shrink: 0; }

                /* Tombol */
                .inayule-page .btn { border-radius: 10px; font-weight: 700; }
                .inayule-page .btn-primary { background: linear-gradient(135deg, var(--ina-red), var(--ina-red-dark)); border-color: var(--ina-red);
                    color: #fff; box-shadow: 0 6px 16px rgba(215, 25, 32, .25); }
                .inayule-page .btn-primary:hover, .inayule-page .btn-primary:focus { background: var(--ina-red-dark); border-color: var(--ina-red-dark); color: #fff; }
                .inayule-page .btn-outline-primary { color: var(--ina-red); border-color: var(--ina-red); background: #fff; box-shadow: none; }
                .inayule-page .btn-outline-primary:hover, .inayule-page .btn-outline-primary:focus { background: var(--ina-red); border-color: var(--ina-red); color: #fff; }
                .inayule-page .btn-success { background: linear-gradient(135deg, var(--ina-red), var(--ina-red-dark)); border-color: var(--ina-red); color: #fff; }

                /* Badge */
                .inayule-page .ina-credit { background: var(--ina-red-soft); color: var(--ina-red); font-weight: 700; font-size: 14px;
                    border-radius: 999px; padding: 8px 16px; }
                .inayule-page .ina-badge { display: inline-block; border-radius: 999px; padding: 4px 12px; font-size: 12px; font-weight: 700; }
                .inayule-page .ina-badge--type { background: var(--ina-red); color: #fff; }
                .inayule-page .ina-badge--class { background: var(--ina-red-soft); color: var(--ina-red); }
                .inayule-page .ina-badge--level { background: #f1f2f6; color: var(--ina-ink); }

                /* Kartu paket */
                .inayule-page .ina-card { border: 1px solid #eef0f5; border-radius: 16px; box-shadow: 0 8px 24px rgba(31, 35, 64, .06);
                    transition: transform .2s ease, box-shadow .2s ease; }
                .inayule-page .ina-card:hover { transform: translateY(-4px); box-shadow: 0 14px 30px rgba(31, 35, 64, .1); }
                .inayule-page .ina-card .card-title { color: var(--ina-ink); font-weight: 800; }
                .inayule-page .ina-price { color: var(--ina-ink); font-weight: 800; font-size: 20px; }
                .inayule-page .ina-price--promo { color: var(--ina-red); }

                /* ===== Tampilan HP ala aplikasi (12 Oktober 2026) =====
                   Semua di-scope ke .inayule-page, class baru berawalan iy-,
                   dan aturan tata letak hanya aktif di layar < 768px. Desktop
                   tidak berubah. */
                .inayule-page .iy-label-short { display: none; }
                .inayule-page .iy-hero { background: linear-gradient(135deg, var(--ina-red), var(--ina-red-dark)); color: #fff;
                    border-radius: 20px; padding: 18px; margin-bottom: 16px; box-shadow: 0 10px 24px rgba(215, 25, 32, .25); }
                .inayule-page .iy-hero-title { font-size: 20px; font-weight: 800; line-height: 1.2; }
                .inayule-page .iy-hero-hello { font-size: 13px; opacity: .9; margin-bottom: 14px; }
                .inayule-page .iy-hero-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
                .inayule-page .iy-hero-stat { background: rgba(255, 255, 255, .16); border-radius: 14px; padding: 10px 12px; }
                .inayule-page .iy-hero-stat span { display: block; font-size: 12px; opacity: .9; }
                .inayule-page .iy-hero-stat strong { display: block; font-size: 18px; font-weight: 800; word-break: break-word; }
                .inayule-page .iy-progress { height: 6px; border-radius: 999px; background: #f1f2f6; overflow: hidden; margin-top: 10px; }
                .inayule-page .iy-progress > span { display: block; height: 100%; background: var(--ina-red); border-radius: 999px; }
                .inayule-page .iy-fab { display: none; }

                @media (max-width: 767.98px) {
                    .inayule-page { padding-bottom: calc(88px + env(safe-area-inset-bottom)); }
                    .inayule-page > .col-12 > .widget-content-area { background: transparent; box-shadow: none; border: 0; padding: 4px 0; }
                    .inayule-page .tab-content { padding-top: 4px !important; }
                    .inayule-page .ina-card:hover { transform: none; }
                    .inayule-page .btn { min-height: 44px; display: inline-flex; align-items: center; justify-content: center; }

                    /* Tab jadi menu bawah (ikon + label pendek) */
                    .inayule-page .iy-label-full { display: none; }
                    .inayule-page .iy-label-short { display: inline; }
                    .inayule-page .inayule-tabs { position: fixed; left: 0; right: 0; bottom: 0; z-index: 1020; margin: 0;
                        background: #fff; border-top: 1px solid #eef0f5; box-shadow: 0 -6px 20px rgba(31, 35, 64, .08);
                        padding-bottom: env(safe-area-inset-bottom); }
                    .inayule-page .inayule-tabs .nav-tabs { border: 0; overflow: visible; justify-content: space-around; padding: 0 4px; }
                    .inayule-page .inayule-tabs .nav-tabs .nav-item { flex: 1 1 0; min-width: 0; }
                    .inayule-page .inayule-tabs .nav-tabs .nav-link { width: 100%; flex-direction: column; justify-content: center; gap: 3px;
                        min-height: 60px; padding: 8px 2px; margin: 0; font-size: 11px; font-weight: 700; color: #888ea8;
                        border: 0; border-top: 3px solid transparent; border-radius: 0; }
                    .inayule-page .inayule-tabs .nav-tabs .nav-link svg { width: 22px; height: 22px; }
                    .inayule-page .inayule-tabs .nav-tabs .nav-link.active { color: var(--ina-red); border-top-color: var(--ina-red); }
                    .inayule-page .inayule-tabs .nav-tabs .nav-link.active svg { stroke-width: 2.6; }

                    /* Tombol bulat "Ajukan" (hanya terlihat saat tab Kelas aktif) */
                    .inayule-page .iy-fab { display: flex; position: fixed; right: 16px; bottom: calc(76px + env(safe-area-inset-bottom));
                        z-index: 1021; width: 56px; height: 56px; border-radius: 50%; align-items: center; justify-content: center;
                        background: linear-gradient(135deg, var(--ina-red), var(--ina-red-dark)); color: #fff; font-size: 30px;
                        line-height: 1; text-decoration: none; box-shadow: 0 10px 24px rgba(215, 25, 32, .35); }

                    /* Tabel jadi kartu */
                    .inayule-page .table-responsive { overflow: visible; }
                    .inayule-page .iy-table, .inayule-page #inayule-credit table { border-collapse: separate; }
                    .inayule-page .iy-table thead, .inayule-page #inayule-credit table thead { display: none; }
                    .inayule-page .iy-table > tbody, .inayule-page #inayule-credit table > tbody { display: block; }
                    .inayule-page .iy-table > tbody > tr, .inayule-page #inayule-credit table > tbody > tr { display: flex; flex-direction: column;
                        background: #fff; border: 1px solid #eef0f5; border-radius: 16px; box-shadow: 0 6px 18px rgba(31, 35, 64, .05);
                        padding: 12px 16px; margin-bottom: 12px; }
                    .inayule-page .iy-table > tbody > tr > td, .inayule-page #inayule-credit table > tbody > tr > td { display: flex;
                        justify-content: space-between; align-items: center; gap: 12px; padding: 5px 0; border: 0; background: transparent;
                        text-align: right; white-space: normal; }
                    .inayule-page .iy-table > tbody > tr > td::before, .inayule-page #inayule-credit table > tbody > tr > td::before {
                        color: #888ea8; font-size: 12px; font-weight: 600; text-align: left; flex-shrink: 0; }
                    .inayule-page .iy-table > tbody > tr > td::before { content: attr(data-label); }
                    .inayule-page .iy-table > tbody > tr > td.iy-td-main { order: -1; display: block; text-align: left;
                        padding-bottom: 10px; margin-bottom: 4px; border-bottom: 1px dashed #eef0f5; }
                    .inayule-page .iy-table > tbody > tr > td.iy-td-action { display: block; padding-top: 10px; }
                    .inayule-page .iy-table > tbody > tr > td.iy-td-action .btn { width: 100%; }
                    .inayule-page .iy-table > tbody > tr > td.iy-td-main::before,
                    .inayule-page .iy-table > tbody > tr > td.iy-td-action::before,
                    .inayule-page .iy-table > tbody > tr > td[colspan]::before { content: none; }
                    .inayule-page .iy-table > tbody > tr > td[colspan], .inayule-page #inayule-credit table > tbody > tr > td[colspan] {
                        display: block; text-align: center; }
                    /* Riwayat Credit memakai tabel bersama (course-credit._ledger), labelnya dari urutan kolom. */
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(1)::before { content: 'Tanggal'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(2)::before { content: 'Jenis'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(3)::before { content: 'Keterangan'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(4)::before { content: 'Paket'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(5)::before { content: 'Masuk'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(6)::before { content: 'Keluar'; }
                    .inayule-page #inayule-credit table > tbody > tr > td:nth-child(7)::before { content: 'Sisa'; }
                    .inayule-page #inayule-credit table > tbody > tr > td[colspan]::before { content: none; }
                }
            </style>
            <div class="simple-tab inayule-tabs">
                <ul class="nav nav-tabs" id="inayuleTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="inayule-buy-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-buy" type="button" role="tab"
                            aria-controls="inayule-buy" aria-selected="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
                            <span class="iy-label-full">Buy Packages</span><span class="iy-label-short">Beli</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-history-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-history" type="button" role="tab"
                            aria-controls="inayule-history" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                            <span class="iy-label-full">Status</span><span class="iy-label-short">Paket</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-schedule-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-schedule" type="button" role="tab"
                            aria-controls="inayule-schedule" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <span class="iy-label-full">History</span><span class="iy-label-short">Kelas</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-credit-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-credit" type="button" role="tab"
                            aria-controls="inayule-credit" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                            <span class="iy-label-full">Riwayat Credit</span><span class="iy-label-short">Credit</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inayule-saldo-tab" data-bs-toggle="tab"
                            data-bs-target="#inayule-saldo" type="button" role="tab"
                            aria-controls="inayule-saldo" aria-selected="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
                            <span class="iy-label-full">Saldo Saya</span><span class="iy-label-short">Saldo</span>
                        </button>
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
                                    <div class="card h-100 ina-card">
                                        <div class="card-body d-flex flex-column">
                                            <h6 class="card-title fw-bold mb-2">{{ $package->name }}</h6>

                                            <div class="mb-2">
                                                <span class="ina-badge ina-badge--type me-1">{{ optional($package->type)->name ?? '-' }}</span>
                                                <span class="ina-badge ina-badge--class me-1">{{ optional($package->courseClass)->name ?? '-' }}</span>
                                                <span class="ina-badge ina-badge--level">{{ optional($package->level)->name ?? '-' }}</span>
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
                                                    <div class="ina-price ina-price--promo">
                                                        Rp {{ number_format((float) $package->promo_price, 0, ',', '.') }}
                                                    </div>
                                                @else
                                                    <div class="ina-price">
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
                                                {{--
                                                    STEP 6 (16 September 2026, permintaan user --
                                                    checkout package berbayar): tombol Beli SEKARANG
                                                    aktif, mengarah ke halaman checkout terpisah
                                                    (bukan submit langsung dari sini) -- harga & split
                                                    saldo/gateway dihitung ULANG di server pada halaman
                                                    checkout itu, lihat docblock
                                                    InaYulePackageCheckoutController.
                                                --}}
                                                <a href="{{ route('inayule.checkout.show', $package->id) }}" class="btn btn-primary w-100">
                                                    Beli
                                                </a>

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

                {{-- Tab 2: Status -- 1 baris per paket (pembelian paket yang sama digabung,
                     rincian per pembelian ada di tab Riwayat Credit). --}}
                <div class="tab-pane fade" id="inayule-history" role="tabpanel"
                    aria-labelledby="inayule-history-tab">
                    @php
                        $creditNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
                    @endphp
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 iy-table">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Credits</th>
                                    <th>Terpakai</th>
                                    <th>Sisa</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($packageStatuses as $status)
                                    <tr>
                                        <td class="iy-td-main">
                                            @include('student-portal.inayule._package-info', ['package' => $status->package])
                                            @if ($status->purchase_count > 1)
                                                <div class="text-muted" style="font-size:12px;">dibeli {{ $status->purchase_count }}x</div>
                                            @endif
                                            @if ($status->credits_granted > 0)
                                                {{-- HP saja: bar sisa credit. --}}
                                                <div class="iy-progress d-md-none"><span style="width: {{ round(min(100, max(0, $status->credits_remaining / $status->credits_granted * 100))) }}%;"></span></div>
                                            @endif
                                        </td>
                                        <td data-label="Credits">{{ $creditNum($status->credits_granted) }}</td>
                                        <td data-label="Terpakai">{{ $creditNum($status->credits_used) }}</td>
                                        <td data-label="Sisa">{{ $creditNum($status->credits_remaining) }}</td>
                                        <td class="iy-td-action">
                                            {{-- Upgrade / convert gabungan sisa credit semua pembelian paket ini. --}}
                                            @if ($status->credits_remaining > 0)
                                                <a href="{{ route('inayule.upgrade.show', $status->first_purchase_id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                                    Upgrade / Convert
                                                </a>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                                    onclick="document.getElementById('inayule-buy-tab').click()">
                                                    Beli Lagi
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Data not found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{--
                    Tab 3: History (FASE 2 bagian 3 "Jadwal", 16 September
                    2026) -- diisi dari App\Models\ClassSession, lihat
                    docblock App\Http\Controllers\StudentPortal\
                    InaYulePackageController untuk alasan kenapa ini BUKAN
                    sistem booking ke depan, murni laporan dari data
                    pengajuan pemakaian credit yang sudah ada.
                --}}
                <div class="tab-pane fade" id="inayule-schedule" role="tabpanel"
                    aria-labelledby="inayule-schedule-tab">
                    <div class="mb-3 d-none d-md-block">
                        <a href="{{ route('inayule.class-sessions.create') }}" class="btn btn-primary btn-sm">+ Ajukan Pemakaian Credit</a>
                    </div>
                    {{-- HP: tombol bulat mengambang (ikut tersembunyi saat tab lain aktif). --}}
                    <a href="{{ route('inayule.class-sessions.create') }}" class="iy-fab" aria-label="Ajukan Pemakaian Credit" title="Ajukan Pemakaian Credit">+</a>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 iy-table">
                            <thead>
                                <tr>
                                    <th>Diajukan</th>
                                    <th>Package</th>
                                    <th>Pengajar</th>
                                    <th>Credit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($scheduleSessions as $session)
                                    <tr>
                                        {{-- Waktu siswa mengajukan (requested_at), bukan jam/lama kelas. --}}
                                        <td class="text-nowrap" data-label="Diajukan">{{ $session->requested_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                        <td class="iy-td-main">@include('student-portal.inayule._package-info', ['package' => $session->coursePackage])</td>
                                        <td data-label="Pengajar">{{ optional($session->teacher)->name ?? '-' }}</td>
                                        <td data-label="Credit">{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                        <td data-label="Status">
                                            @php
                                                $scheduleStatusLabel = [
                                                    'menunggu_guru' => ['Menunggu Pengajar', 'badge-warning'],
                                                    'ditolak_guru' => ['Ditolak Pengajar', 'badge-danger'],
                                                    'menunggu_admin' => ['Menunggu Admin', 'badge-warning'],
                                                    'disetujui' => ['Disetujui', 'badge-success'],
                                                    'direfund' => ['Direfund', 'badge-secondary'],
                                                    'ditolak_admin' => ['Ditolak Admin', 'badge-danger'],
                                                ][$session->status] ?? [$session->status, 'badge-secondary'];
                                            @endphp
                                            <span class="badge {{ $scheduleStatusLabel[1] }}">{{ $scheduleStatusLabel[0] }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada jadwal/pengajuan pemakaian credit.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Tab: Riwayat Credit -- semua mutasi credit (beli, kelas, refund, potong admin, tukar). --}}
                <div class="tab-pane fade" id="inayule-credit" role="tabpanel" aria-labelledby="inayule-credit-tab">
                    @if ($creditLedger)
                        @include('course-credit._ledger', ['ledger' => $creditLedger])
                    @else
                        <p class="text-center text-muted py-4">Belum ada riwayat credit.</p>
                    @endif
                </div>

                {{--
                    Tab 4: Saldo Saya (STEP 6, 16 September 2026, permintaan
                    user -- "ada 1 tab lagi untuk history pembelian, sisa
                    saldo, saldo awal"): ini saldo Deposit (UANG, key-nya
                    user_id), BEDA dari "Sisa Credit" (SESI kursus, key-nya
                    student_id) yang badge-nya di atas tab-tab ini. Ledger di
                    bawah SEKALIGUS menjawab ketiga poin permintaan: setiap
                    baris = 1 transaksi ("history pembelian" -- topup maupun
                    potongan beli package), kolom Saldo tiap baris = running
                    balance ("saldo awal" ada di baris paling lama, kalau
                    scroll ke halaman terakhir), dan card di atas = "sisa
                    saldo" (saldo terkini).
                --}}
                <div class="tab-pane fade" id="inayule-saldo" role="tabpanel"
                    aria-labelledby="inayule-saldo-tab">

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div>
                            <div class="text-muted" style="font-size:13px;">Sisa Saldo</div>
                            <div class="fw-bold" style="font-size:24px;">
                                Rp {{ number_format((float) $depositBalance, 0, ',', '.') }}
                            </div>
                        </div>
                        <a href="{{ route('dashboard.deposit.create') }}" class="btn btn-outline-primary">
                            + Topup Saldo
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0 iy-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th>Debit</th>
                                    <th>Kredit</th>
                                    <th>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($depositLedger as $ledger)
                                    <tr>
                                        <td data-label="Tanggal">{{ ($ledger->payment_date ?? $ledger->created_at)?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                                        <td class="iy-td-main">{{ $ledger->description ?? '-' }}</td>
                                        <td data-label="Debit">{{ (float) $ledger->debit > 0 ? 'Rp ' . number_format((float) $ledger->debit, 0, ',', '.') : '-' }}</td>
                                        <td data-label="Kredit">{{ (float) $ledger->kredit > 0 ? 'Rp ' . number_format((float) $ledger->kredit, 0, ',', '.') : '-' }}</td>
                                        <td data-label="Saldo">Rp {{ number_format((float) $ledger->balance, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Data not found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        {{ $depositLedger->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Pindah halaman riwayat credit/saldo tetap membuka tab yang sama, dan
    // tab terakhir diingat lewat #beli/#paket/#kelas/#credit/#saldo di URL
    // (refresh / kembali ke halaman ini tetap di tab yang sama).
    document.addEventListener('DOMContentLoaded', function () {
        var hashToTab = { beli: 'inayule-buy-tab', paket: 'inayule-history-tab', kelas: 'inayule-schedule-tab', credit: 'inayule-credit-tab', saldo: 'inayule-saldo-tab' };
        var params = new URLSearchParams(window.location.search);
        var tabId = params.has('credit_page') ? 'inayule-credit-tab'
            : (params.has('saldo_page') ? 'inayule-saldo-tab' : hashToTab[window.location.hash.slice(1)]);
        var tab = tabId && document.getElementById(tabId);
        if (tab && window.bootstrap) {
            bootstrap.Tab.getOrCreateInstance(tab).show();
        }

        Object.keys(hashToTab).forEach(function (hash) {
            var button = document.getElementById(hashToTab[hash]);
            if (button) {
                button.addEventListener('shown.bs.tab', function () {
                    history.replaceState(null, '', window.location.pathname + window.location.search + '#' + hash);
                });
            }
        });
    });
</script>

@endsection
