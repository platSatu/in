<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $university->name }} - | INASTUDY | CHINA EDUCATION CONSULTANT</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand: #C8102E;
            --brand-dark: #a30d25;
        }

        * { box-sizing: border-box; }

        body {
            background: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
            overflow-x: hidden;
        }

        img { max-width: 100%; }

        /* ---------- HERO ---------- */
        .hero {
            position: relative;
            /* Background default sekarang pakai image/bg-uv.png, bukan gradient merah lagi.
            background-color di bawah ini cuma fallback kalau gambarnya gagal load. */
            background-image: url('{{ asset('image/bg-uv.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: var(--brand-dark);
            color: #2b2f38;
            padding: 60px 0 90px;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 15% 20%, rgba(255,255,255,.08) 0, transparent 45%),
                               radial-gradient(circle at 85% 75%, rgba(255,255,255,.08) 0, transparent 45%);
        }

        .hero.has-banner::before {
            background: linear-gradient(135deg, rgba(150,10,25,.88) 0%, rgba(30,10,20,.75) 100%);
        }

        .hero-content { position: relative; z-index: 1; }

        .breadcrumb-link {
            color:1F2937;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .breadcrumb-link:hover { color: #fff; }

        .university-logo {
            width: 100px;
            height: 100px;
            background: #fff;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            font-weight: 800;
            color: var(--brand);
            box-shadow: 0 10px 30px rgba(0,0,0,.2);
            flex-shrink: 0;
            overflow: hidden;
        }

        .university-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .hero h1 {
            color:#1F2937;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 6px;
            word-break: break-word;
        }

        .badge-pill {
            background: #f26078;
            border: 1px solid rgba(255,255,255,.3);
            color: #510505;
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* ---------- QUICK FACTS BAR ---------- */
        .quick-facts {
            margin-top: -45px;
            position: relative;
            z-index: 2;
        }

        .fact-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(20,30,60,.08);
            padding: 18px;
            height: 100%;
        }

        .fact-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: #fbe6ea;
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .fact-label {
            font-size: 11.5px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a90a2;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .fact-value {
            font-weight: 700;
            font-size: 14px;
            color: #1d2333;
            word-break: break-word;
        }

        .fact-value.muted {
            color: #a4a9b8;
            font-weight: 600;
            font-style: italic;
        }

        /* ---------- CARDS ---------- */
        .info-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 24px;
        }

        .info-card h4 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-card h4 i { color: var(--brand); }

        .tag {
            display: inline-block;
            background: #fbe6ea;
            color: var(--brand);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13.5px;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        /* ---------- PROGRAMS / MAJORS (fitur Apply Kampus) ---------- */
        .major-block { margin-bottom: 26px; }
        .major-block:last-child { margin-bottom: 0; }
        .major-block-divider {
            padding-bottom: 24px;
            border-bottom: 1px solid #eef1f8;
        }

        .btn-apply {
            background: var(--brand);
            color: #fff;
            padding: 10px 22px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
            transition: all .2s ease;
        }
        .btn-apply:hover {
            background: var(--brand-dark);
            color: #fff;
            transform: translateY(-1px);
        }

        .placeholder-note {
            background: #f8f9fc;
            border: 1px dashed #d7dcea;
            border-radius: 12px;
            padding: 16px 18px;
            color: #6b7186;
            font-size: 14px;
        }

        .why-item {
            display: flex;
            gap: 14px;
            margin-bottom: 18px;
        }
        .why-item:last-child { margin-bottom: 0; }

        .why-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #fbe6ea;
            color: var(--brand);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .why-item h6 { font-weight: 700; margin-bottom: 2px; font-size: 14.5px; }
        .why-item p { font-size: 13.5px; color: #6b7186; margin-bottom: 0; }

        /* ---------- ENTRY REQUIREMENTS ---------- */
        .requirement-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #4a4f5c;
            line-height: 1.5;
        }
        .requirement-item:last-child { margin-bottom: 0; }
        .requirement-item i { color: var(--brand); flex-shrink: 0; margin-top: 3px; }

        /* ---------- PAYMENT ---------- */
        .payment-group { margin-bottom: 20px; }
        .payment-group:last-child { margin-bottom: 0; }

        .payment-group-title {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--brand);
            margin-bottom: 8px;
        }

        .payment-row {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: baseline;
            gap: 4px 12px;
            padding: 10px 0;
            border-bottom: 1px solid #eef1f8;
        }
        .payment-row:last-child { border-bottom: none; }

        .payment-name {
            flex: 1 1 55%;
            min-width: 140px;
            font-size: 14px;
            color: #4a4f5c;
            word-break: break-word;
        }

        .payment-amount {
            flex: 0 0 auto;
            font-weight: 700;
            font-size: 14px;
            color: #1d2333;
        }

        /* ---------- GALLERY ---------- */
        .album-block { margin-bottom: 26px; }
        .album-block:last-child { margin-bottom: 0; }

        .album-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 2px;
            color: #1d2333;
        }

        .album-desc {
            font-size: 13px;
            color: #6b7186;
            margin-bottom: 12px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 10px;
        }

        .gallery-item {
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: 12px;
            overflow: hidden;
            cursor: pointer;
            background: #eef1f8;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .3s ease;
        }

        .gallery-item:hover img { transform: scale(1.08); }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: rgba(20,10,15,0);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 22px;
            opacity: 0;
            transition: all .25s ease;
        }

        .gallery-item:hover .gallery-overlay {
            background: rgba(20,10,15,.35);
            opacity: 1;
        }

        .gallery-caption {
            position: absolute;
            left: 0; right: 0; bottom: 0;
            background: linear-gradient(0deg, rgba(0,0,0,.6), transparent);
            color: #fff;
            font-size: 11px;
            padding: 14px 8px 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Lightbox */
        .lightbox-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 8, 12, .92);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 20px;
        }

        .lightbox-overlay.active { display: flex; }

        .lightbox-img-wrap {
            max-width: 90vw;
            max-height: 78vh;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-img {
            max-width: 90vw;
            max-height: 78vh;
            object-fit: contain;
            cursor: zoom-in;
            transition: transform .25s ease;
            border-radius: 6px;
        }

        .lightbox-img.zoomed {
            cursor: zoom-out;
            transform: scale(1.9);
        }

        .lightbox-caption {
            color: #fff;
            margin-top: 14px;
            font-size: 14px;
            text-align: center;
            max-width: 80vw;
            opacity: .85;
        }

        .lightbox-close {
            position: absolute;
            top: 18px;
            right: 24px;
            color: #fff;
            font-size: 34px;
            line-height: 1;
            cursor: pointer;
            background: rgba(255,255,255,.1);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,.12);
            border: none;
            color: #fff;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lightbox-prev { left: 18px; }
        .lightbox-next { right: 18px; }

        .lightbox-counter {
            position: absolute;
            top: 22px;
            left: 24px;
            color: #fff;
            font-size: 13px;
            opacity: .75;
        }

        /* ---------- SIDEBAR ---------- */
        .sticky-side { position: sticky; top: 24px; }

        .price-tag {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--brand);
            word-break: break-word;
        }

        .btn-whatsapp {
            background: #25D366;
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            transition: all .25s;
        }

        .btn-whatsapp:hover {
            background: #20BD5A;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 211, 102, .35);
        }

        .btn-download {
            background: #fff;
            color: var(--brand);
            border: 2px solid var(--brand);
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            transition: all .25s;
        }

        .btn-download:hover {
            background: var(--brand);
            color: #fff;
        }

        .btn-download.disabled {
            opacity: .5;
            pointer-events: none;
            border-color: #c9cddb;
            color: #8a90a2;
        }

        .back-link {
            color: #6b7186;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
        }
        .back-link:hover { color: var(--brand); }

        /* ---------- RESPONSIVE ---------- */
        @media (max-width: 991px) {
            .sticky-side { position: static; margin-top: 4px; }
        }

        @media (max-width: 767px) {
            .hero { padding: 36px 0 75px; text-align: center; }
            .hero .d-flex.align-items-center { flex-direction: column; align-items: center !important; }
            .hero p, .hero .d-flex.flex-wrap { justify-content: center; }
            .quick-facts { margin-top: -35px; }
            .info-card { padding: 20px; }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); }
            .lightbox-nav { width: 38px; height: 38px; font-size: 16px; }
            .lightbox-prev { left: 6px; }
            .lightbox-next { right: 6px; }
        }

        @media (max-width: 420px) {
            .fact-card { padding: 14px; }
            .hero h1 { font-size: 1.5rem; }
        }
    </style>
</head>

<body>

    @php
        $hasBanner = !empty($university->banner) && file_exists(public_path($university->banner));
        $hasLogo = !empty($university->logo) && file_exists(public_path($university->logo));
        $hasAttachment = !empty($university->attachment);
        $fields = ($profile && !empty($profile->field)) ? array_filter(array_map('trim', explode(',', $profile->field))) : [];
        // Degree/Intake TIDAK lagi datang dari kolom $profile->degree/$profile->intake
        // (kolom itu tidak ada lagi di tabel university_profiles) — sekarang diambil
        // dari relasi $profile->degrees (tabel anak university_profile_degrees, bisa
        // lebih dari satu baris, diisi admin lewat fitur "add row" di halaman create).
        $degreeIntakeRows = $profile ? $profile->degrees : collect();
        $degrees = $degreeIntakeRows->pluck('degree')->filter()->map('trim')->unique()->values()->all();
        $intakes = $degreeIntakeRows->pluck('intake')->filter()->map('trim')->unique()->values()->all();
        // Duration: field baru (nullable) di university_profile_degrees, satu
        // paket sama Degree/Intake per baris — ditampilkan sebagai daftar
        // ringkas (deduplikasi), sama persis polanya dengan $degrees/$intakes
        // di atas (jadi kalau kolom ini belum diisi admin, tampilannya balik
        // ke placeholder-note seperti biasa, tidak ada yang berubah).
        $durations = $degreeIntakeRows->pluck('duration')->filter()->map('trim')->unique()->values()->all();

        // Degree Title, Key Courses, Entry Requirements, Payment: DULU
        // dihitung di sini dari satu $profile (info-card terpisah per
        // jenis). Sekarang (fase 3, fitur Apply Kampus) satu university bisa
        // punya LEBIH DARI SATU Major/profile aktif, jadi field-field ini
        // dihitung ULANG PER-MAJOR di dalam loop "Programs / Majors
        // Available" di bawah (lihat variabel $majorKeyCourses dkk di sana),
        // bukan sekali di sini untuk satu profile saja.
        //
        // $splitFreeText tetap didefinisikan di sini (dipakai bareng di
        // dalam loop per-Major) -- teks bebas Key Courses/Entry Requirements
        // dipecah jadi list rapi kalau dipisah "/" atau baris baru, fallback
        // ke satu paragraf utuh kalau tidak ada pemisah.
        $splitFreeText = function (?string $text) {
            $text = trim((string) $text);
            if ($text === '') {
                return [];
            }

            $parts = str_contains($text, '/')
                ? explode('/', $text)
                : preg_split('/\r\n|\r|\n/', $text);

            return collect($parts)->map('trim')->filter()->values()->all();
        };

        $paymentLocationLabels = [
            'indonesia' => 'Payment in Indonesia',
            'china' => 'Payment in China',
            'other' => 'Other Payment',
        ];
        $paymentCurrencySymbols = [
            'indonesia' => 'Rp',
            'china' => '元',
            'other' => '',
        ];

        $albumsWithPhotos = isset($albums) ? $albums->filter(fn($a) => $a->photos && $a->photos->count() > 0) : collect();

        // $cityModel dikirim dari controller (relasi city() di-resolve eksplisit
        // di sana, bukan lewat magic property $university->city yang selalu
        // balikin nilai kolom mentah/UUID — lihat catatan di
        // FrontendController::universityProfile()). Kalau $cityModel null
        // (referensinya putus, atau ini university lama yang city-nya masih
        // teks bebas manual) tampilkan teks mentahnya, KECUALI kalau teks
        // mentah itu sendiri berbentuk UUID (berarti referensi City-nya sudah
        // tidak valid) — dalam kasus itu jangan tampilkan UUID-nya ke publik.
        $rawCityLooksLikeUuid = is_string($university->city)
            && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $university->city);
        $cityDisplay = $cityModel->name
            ?? (! $rawCityLooksLikeUuid ? $university->city : null);

        // Location sekarang ditampilkan sebagai "Country, City" saja (bukan lagi
        // City lalu Country) supaya urutannya konsisten di hero maupun Quick Facts.
        $locationParts = array_filter([$university->country, $cityDisplay], fn ($part) => filled($part));
        $locationDisplay = count($locationParts) ? implode(', ', $locationParts) : null;
    @endphp

    <!-- HERO -->
    <div class="hero {{ $hasBanner ? 'has-banner' : '' }}"
         @if($hasBanner) style="background-image: url('{{ asset($university->banner) }}');" @endif>
        <div class="container hero-content">
            <a href="{{ route('frontend.university.catalog') }}" class="breadcrumb-link">
                <i class="bi bi-arrow-left"></i> Back to University
            </a>

            <div class="d-flex align-items-center gap-4 mt-4">
                <div class="university-logo">
                    @if($hasLogo)
                        <img src="{{ asset($university->logo) }}" alt="{{ $university->name }}">
                    @else
                        {{ strtoupper(substr($university->name, 0, 1)) }}
                    @endif
                </div>
                <div>
                    <h1>{{ $university->name }}</h1>
                    <p class="mb-3 opacity-75">
                        <i class="bi bi-geo-alt-fill"></i>
                        {{ $locationDisplay ?? 'Location not specified yet' }}
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge-pill"><i class="bi bi-mortarboard"></i> Study in China</span>
                        @if($profile && $profile->scholarship_available)
                            <span class="badge-pill"><i class="bi bi-award"></i> Scholarship Available</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK FACTS -->
    <div class="container quick-facts">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-geo-alt"></i></div>
                    <div class="fact-label">Location</div>
                    <div class="fact-value {{ $locationDisplay ? '' : 'muted' }}">{{ $locationDisplay ?? 'N/A' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-translate"></i></div>
                    <div class="fact-label">Language</div>
                    <div class="fact-value {{ ($profile && $profile->language) ? '' : 'muted' }}">{{ ($profile && $profile->language) ? $profile->language : 'Contact us' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-mortarboard-fill"></i></div>
                    <div class="fact-label">Degree</div>
                    <div class="fact-value {{ count($degrees) ? '' : 'muted' }}">{{ count($degrees) ? implode(', ', $degrees) : 'Contact us' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-calendar-event"></i></div>
                    <div class="fact-label">Intake</div>
                    <div class="fact-value {{ count($intakes) ? '' : 'muted' }}">{{ count($intakes) ? implode(', ', $intakes) : 'Contact us' }}</div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-wallet2"></i></div>
                    <div class="fact-label">Budget</div>
                    <div class="fact-value {{ ($profile && ($profile->min_budget || $profile->max_budget)) ? '' : 'muted' }}">
                        @if($profile && $profile->min_budget && $profile->max_budget)
                            Rp {{ number_format($profile->min_budget, 0, ',', '.') }} - {{ number_format($profile->max_budget, 0, ',', '.') }}
                        @elseif($profile && $profile->min_budget)
                            From Rp {{ number_format($profile->min_budget, 0, ',', '.') }}
                        @elseif($profile && $profile->max_budget)
                            Up to Rp {{ number_format($profile->max_budget, 0, ',', '.') }}
                        @else
                            Contact us
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-award"></i></div>
                    <div class="fact-label">Scholarship</div>
                    <div class="fact-value {{ ($profile && $profile->scholarship_available) ? '' : 'muted' }}">
                        {{ $profile && $profile->scholarship_available ? 'Available' : 'Contact us' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-8">

                <div class="info-card">
                    <h4><i class="bi bi-info-circle"></i> About This University</h4>
                    @if(!empty($university->description))
                        <div style="line-height: 1.8; color:#4a4f5c;">{!! $university->description !!}</div>
                    @else
                        <div class="placeholder-note">
                            <i class="bi bi-hourglass-split me-1"></i>
                            A detailed profile for this university is being prepared. Reach out to our team for the latest information on programs, tuition, and admission requirements.
                        </div>
                    @endif
                </div>

                {{--
                    DIUBAH (fase 3, fitur Apply Kampus): 9 info-card terpisah
                    di atas (Degree Levels, Intake Periods, Program Duration,
                    Fields of Study, Degree Title, Language of Instruction,
                    Key Courses, Entry Requirements, Payment Details) dulu
                    SEMUANYA membaca dari satu $profile -- cukup untuk
                    university yang cuma punya 1 Major. Sekarang digabung jadi
                    SATU section "Programs / Majors Available" yang di-loop
                    per Major ($profiles, jamak), karena tiap Major bisa
                    punya Degree/Intake/Duration/Payment yang BEDA-BEDA, dan
                    tiap Major butuh tombol Apply-nya sendiri (supaya
                    ketahuan "kampus A, jurusan Teknik Informatika, di
                    apply" -- bukan cuma "kampus A di apply").

                    Untuk university yang cuma punya 1 profile aktif (kasus
                    lama/paling umum), loop ini otomatis cuma render SATU
                    blok Major -- isinya sama seperti gabungan 9 info-card
                    lama, ditambah 1 tombol Apply Now.
                --}}
                <div class="info-card">
                    <h4><i class="bi bi-mortarboard-fill"></i> Programs / Majors Available</h4>

                    @if($profiles->isEmpty())
                        <div class="placeholder-note">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Program details for this university are being finalized. Message us on WhatsApp and our team will share the latest list of available majors.
                        </div>
                    @else
                        @foreach($profiles as $majorProfile)
                            @php
                                $majorDegreeRows = $majorProfile->degrees;
                                $majorDegrees = $majorDegreeRows->pluck('degree')->filter()->map('trim')->unique()->values()->all();
                                $majorIntakes = $majorDegreeRows->pluck('intake')->filter()->map('trim')->unique()->values()->all();
                                $majorDurations = $majorDegreeRows->pluck('duration')->filter()->map('trim')->unique()->values()->all();
                                $majorKeyCourses = $splitFreeText($majorProfile->key_courses);
                                $majorEntryReq = $splitFreeText($majorProfile->entry_requirements);
                                $majorPaymentRows = $majorProfile->payments->filter(fn ($p) => filled($p->name) || filled($p->amount));
                                $majorPaymentsByLocation = $majorPaymentRows->groupBy(fn ($p) => $p->location ?: 'other');
                            @endphp

                            <div class="major-block {{ ! $loop->last ? 'major-block-divider' : '' }}">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                    <div>
                                        <h5 class="mb-1">{{ $majorProfile->field ?: 'Program' }}</h5>
                                        @if(filled($majorProfile->degree_title))
                                            <div class="text-muted" style="font-size:13.5px;">{{ $majorProfile->degree_title }}</div>
                                        @endif
                                    </div>
                                    <a href="{{ route('student-portal.apply.show', $majorProfile->id) }}" class="btn-apply">
                                        <i class="bi bi-send-check"></i> Apply Now
                                    </a>
                                </div>

                                @if(count($majorDegrees) || count($majorIntakes) || count($majorDurations) || $majorProfile->language || $majorProfile->scholarship_available)
                                    <div class="mb-3">
                                        @foreach($majorDegrees as $degree)<span class="tag">{{ $degree }}</span>@endforeach
                                        @foreach($majorIntakes as $intake)<span class="tag">{{ $intake }}</span>@endforeach
                                        @foreach($majorDurations as $duration)<span class="tag">{{ $duration }}</span>@endforeach
                                        @if($majorProfile->language)
                                            <span class="tag"><i class="bi bi-translate"></i> {{ $majorProfile->language }}</span>
                                        @endif
                                        @if($majorProfile->scholarship_available)
                                            <span class="tag"><i class="bi bi-award"></i> Scholarship Available</span>
                                        @endif
                                    </div>
                                @else
                                    <div class="placeholder-note mb-3">
                                        <i class="bi bi-hourglass-split me-1"></i>
                                        Degree, intake, and duration options for this program have not been added yet. Contact our team to find out what's available.
                                    </div>
                                @endif

                                @if($majorProfile->min_budget || $majorProfile->max_budget)
                                    <div class="mb-2" style="font-size:14px;">
                                        <strong>Budget:</strong>
                                        @if($majorProfile->min_budget && $majorProfile->max_budget)
                                            Rp {{ number_format($majorProfile->min_budget, 0, ',', '.') }} - Rp {{ number_format($majorProfile->max_budget, 0, ',', '.') }}
                                        @elseif($majorProfile->min_budget)
                                            From Rp {{ number_format($majorProfile->min_budget, 0, ',', '.') }}
                                        @else
                                            Up to Rp {{ number_format($majorProfile->max_budget, 0, ',', '.') }}
                                        @endif
                                    </div>
                                @endif

                                @if(count($majorKeyCourses))
                                    <div class="mb-2" style="font-size:14px;">
                                        <strong>Key Courses:</strong>
                                        <div class="mt-1">
                                            @foreach($majorKeyCourses as $course)<span class="tag">{{ $course }}</span>@endforeach
                                        </div>
                                    </div>
                                @endif

                                @if(count($majorEntryReq))
                                    <div class="mb-2" style="font-size:14px;">
                                        <strong>Entry Requirements:</strong>
                                        <div class="mt-1">
                                            @foreach($majorEntryReq as $requirement)
                                                <div class="requirement-item">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                    <span>{{ $requirement }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($majorPaymentsByLocation->isNotEmpty())
                                    <div class="mt-2">
                                        @foreach($majorPaymentsByLocation as $locationKey => $items)
                                            <div class="payment-group">
                                                <div class="payment-group-title">{{ $paymentLocationLabels[$locationKey] ?? ucfirst($locationKey) }}</div>
                                                @foreach($items as $item)
                                                    <div class="payment-row">
                                                        <span class="payment-name">{{ $item->name ?? '-' }}</span>
                                                        @if($item->amount !== null)
                                                            <span class="payment-amount">{{ $paymentCurrencySymbols[$locationKey] ?? '' }} {{ number_format($item->amount, 0, ',', '.') }}</span>
                                                        @else
                                                            <span class="payment-amount text-muted">Contact us</span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="info-card">
                    <h4><i class="bi bi-images"></i> Photo Gallery</h4>
                    @if($albumsWithPhotos->count())
                        @foreach($albumsWithPhotos as $album)
                            <div class="album-block">
                                <div class="album-title">{{ $album->name }}</div>
                                @if(!empty($album->description))
                                    <div class="album-desc">{{ $album->description }}</div>
                                @endif
                                <div class="gallery-grid">
                                    @foreach($album->photos as $photo)
                                        <div class="gallery-item"
                                             data-src="{{ asset($photo->photo) }}"
                                             data-caption="{{ $photo->title ?? $album->name }}">
                                            <img src="{{ asset($photo->photo) }}" alt="{{ $photo->title ?? $album->name }}" loading="lazy">
                                            <div class="gallery-overlay"><i class="bi bi-zoom-in"></i></div>
                                            @if(!empty($photo->title))
                                                <div class="gallery-caption">{{ $photo->title }}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="placeholder-note">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Photo gallery for this university is being prepared. Check back soon or ask our team for photos and campus tours.
                        </div>
                    @endif
                </div>

                <div class="info-card">
                    <h4><i class="bi bi-stars"></i> Why Study Here</h4>
                    <div class="why-item">
                        <div class="why-icon"><i class="bi bi-globe2"></i></div>
                        <div>
                            <h6>Globally Recognized Institution</h6>
                            <p>Study at a university with strong academic reputation and international student support.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon"><i class="bi bi-people"></i></div>
                        <div>
                            <h6>Dedicated Consultation</h6>
                            <p>Our team guides you from application to arrival, every step of the way.</p>
                        </div>
                    </div>
                    <div class="why-item">
                        <div class="why-icon"><i class="bi bi-cash-coin"></i></div>
                        <div>
                            <h6>Scholarship Opportunities</h6>
                            <p>We help you explore scholarship and funding options available for international students.</p>
                        </div>
                    </div>
                </div>

            </div>

            <div class="col-lg-4">
                <div class="sticky-side">
                    <div class="info-card">
                        <h4><i class="bi bi-cash-stack"></i> Estimated Cost</h4>
                        <div class="mb-3">
                            <div class="fact-label mb-1">Tuition Budget Range</div>
                            <div class="price-tag">
                                @if($profile && $profile->min_budget && $profile->max_budget)
                                    Rp {{ number_format($profile->min_budget, 0, ',', '.') }} - Rp {{ number_format($profile->max_budget, 0, ',', '.') }}
                                @elseif($profile && $profile->min_budget)
                                    Rp {{ number_format($profile->min_budget, 0, ',', '.') }}+
                                @elseif($profile && $profile->max_budget)
                                    Up to Rp {{ number_format($profile->max_budget, 0, ',', '.') }}
                                @else
                                    Contact Us
                                @endif
                            </div>
                        </div>
                        <div>
                            <div class="fact-label mb-1">Scholarship</div>
                            <strong>{{ $profile && $profile->scholarship_available ? 'Available' : 'Contact us for details' }}</strong>
                        </div>
                    </div>

                    <div class="info-card text-center">
                        <h4 class="justify-content-center"><i class="bi bi-chat-heart"></i> Interested?</h4>
                        <p class="text-muted mb-3" style="font-size: 14px;">Get free consultation with our team</p>

                        @php
                            $waMessage = "Hello InaStudy, I'm interested in studying at {$university->name}";
                            $waNumber = '6281287625661';
                        @endphp

                        <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}"
                           class="btn-whatsapp mb-2"
                           target="_blank">
                            <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                        </a>

                        @if($hasAttachment)
                            <a href="{{ route('frontend.handbook.download', $university->id) }}" class="btn-download mt-2">
                                <i class="bi bi-download"></i> Download Handbook
                            </a>
                        @else
                            <span class="btn-download disabled mt-2">
                                <i class="bi bi-download"></i> Handbook Not Available
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4 mb-5">
            <a href="{{ route('frontend.university.catalog') }}" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to University
            </a>
        </div>
    </div>

    <!-- LIGHTBOX -->
    <div class="lightbox-overlay" id="lightboxOverlay">
        <span class="lightbox-counter" id="lightboxCounter"></span>
        <span class="lightbox-close" id="lightboxClose"><i class="bi bi-x-lg"></i></span>
        <button type="button" class="lightbox-nav lightbox-prev" id="lightboxPrev"><i class="bi bi-chevron-left"></i></button>
        <div class="lightbox-img-wrap">
            <img class="lightbox-img" id="lightboxImg" src="" alt="">
        </div>
        <button type="button" class="lightbox-nav lightbox-next" id="lightboxNext"><i class="bi bi-chevron-right"></i></button>
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>

    <script>
        (function () {
            var items = Array.prototype.slice.call(document.querySelectorAll('.gallery-item'));
            if (!items.length) return;

            var overlay = document.getElementById('lightboxOverlay');
            var imgEl = document.getElementById('lightboxImg');
            var captionEl = document.getElementById('lightboxCaption');
            var counterEl = document.getElementById('lightboxCounter');
            var closeBtn = document.getElementById('lightboxClose');
            var prevBtn = document.getElementById('lightboxPrev');
            var nextBtn = document.getElementById('lightboxNext');
            var currentIndex = 0;

            function show(index) {
                currentIndex = (index + items.length) % items.length;
                var item = items[currentIndex];
                imgEl.src = item.getAttribute('data-src');
                imgEl.alt = item.getAttribute('data-caption') || '';
                imgEl.classList.remove('zoomed');
                captionEl.textContent = item.getAttribute('data-caption') || '';
                counterEl.textContent = (currentIndex + 1) + ' / ' + items.length;
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden';
            }

            function close() {
                overlay.classList.remove('active');
                imgEl.classList.remove('zoomed');
                document.body.style.overflow = '';
            }

            items.forEach(function (item, index) {
                item.addEventListener('click', function () { show(index); });
            });

            closeBtn.addEventListener('click', close);
            prevBtn.addEventListener('click', function (e) { e.stopPropagation(); show(currentIndex - 1); });
            nextBtn.addEventListener('click', function (e) { e.stopPropagation(); show(currentIndex + 1); });

            // click on the darkened backdrop (not the image) closes the lightbox
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) close();
            });

            // clicking the image itself toggles a zoom-in effect
            imgEl.addEventListener('click', function () {
                imgEl.classList.toggle('zoomed');
            });

            document.addEventListener('keydown', function (e) {
                if (!overlay.classList.contains('active')) return;
                if (e.key === 'Escape') close();
                if (e.key === 'ArrowLeft') show(currentIndex - 1);
                if (e.key === 'ArrowRight') show(currentIndex + 1);
            });
        })();
    </script>

</body>

</html>