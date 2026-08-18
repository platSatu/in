<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>University | INASTUDY | CHINA EDUCATION CONSULTANT</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand: #e02424;
            --brand-dark: #8a0e0e;
        }

        body {
            background: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
        }

        /* ---------- HERO ---------- */
        .hero {
            position: relative;
            background-image: url('{{ asset('image/bg-uv.png') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #fdf1ee; /* fallback kalau gambar gagal load, senada dgn tone gambar */
            color: #1F2937;
            padding: 60px 0 55px;
            overflow: hidden;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle at 15% 20%, rgba(255,255,255,.08) 0, transparent 45%),
                            radial-gradient(circle at 85% 75%, rgba(255,255,255,.08) 0, transparent 45%);
        }

        .hero-content { position: relative; z-index: 1; }

        /* === FIX: badge sebelumnya bg-white/text-white -> nyaris tak kelihatan
           di atas background terang. Sekarang pill putih solid + teks gelap. === */
        .hero-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fff;
            color: #1F2937;
            border: 1px solid rgba(224,36,36,.15);
            padding: 8px 18px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 1.25rem;
        }

        .hero-eyebrow i { color: var(--brand); }

        .hero h1 {
            font-weight: 800;
            font-size: 2.6rem;
            margin-bottom: 14px;
            color: #1F2937;
        }

        .hero h1 .text-red { color: var(--brand); }

        .hero p.subtitle {
            color: #6b7186;
            font-size: 15.5px;
            max-width: 520px;
        }

        .search-box {
            background: #fff;
            border-radius: 14px;
            padding: 8px;
            box-shadow: 0 15px 40px rgba(0,0,0,.1);
            max-width: 560px;
        }

        .search-box input {
            border: none;
            outline: none;
            box-shadow: none !important;
        }

        .search-box .btn-search {
            background: var(--brand);
            color: #fff;
            border-radius: 10px;
            font-weight: 600;
            padding: 10px 22px;
            border: none;
        }

        .search-box .btn-search:hover { background: var(--brand-dark); color: #fff; }

        /* ---------- RESULT COUNT + FILTER DROPDOWN ROW ---------- */
        /* === FIX POSISI ===
           Sebelumnya filter chip ada di card terpisah yang melayang (margin-top
           negatif) di ATAS baris "X universities found". Sekarang keduanya jadi
           satu baris sejajar (kiri: jumlah hasil, kanan: dropdown + reset),
           mengikuti posisi di gambar referensi -- tidak ada lagi card melayang. */
        .catalog-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin: 2rem 0 1.5rem;
        }

        .result-count {
            font-size: 15px;
            color: #2b2f38;
            font-weight: 500;
        }

        .result-count strong {
            color: var(--brand);
            font-weight: 800;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        /* NOTE: dropdown ini sengaja belum difungsikan (placeholder tampilan
           saja dulu sesuai permintaan) -- tinggal disambungkan ke query filter
           city/major/type/scholarship kalau datanya sudah siap. */
        .filter-select {
            border: 1px solid #e6e8f0;
            background: #fff;
            color: #4a4f5c;
            font-size: 13.5px;
            font-weight: 600;
            padding: 9px 14px;
            border-radius: 10px;
            min-width: 150px;
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: none;
        }

        .filter-reset {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--brand);
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            white-space: nowrap;
        }

        .filter-reset:hover { color: var(--brand-dark); }

        /* ---------- CATALOG CARDS ---------- */
        .uni-card {
            background: #fff;
            border-radius: 18px;
            padding: 20px;
            box-shadow: 0 6px 20px rgba(20,30,60,.06);
            height: 100%;
            display: flex;
            flex-direction: column;
            transition: transform .25s, box-shadow .25s;
            position: relative;
        }

        .uni-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 18px 36px rgba(20,30,60,.14);
        }

        /* === FIX POSISI ===
           Sebelumnya logo di-absolute di atas "banner" berwarna yang menumpuk
           keluar dari card. Sekarang logo + badge scholarship sejajar dalam
           satu baris biasa di dalam card, seperti di gambar referensi. */
        .uni-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .uni-logo {
            width: 54px;
            height: 54px;
            background: #fff;
            border: 1px solid #f0f1f5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 800;
            color: var(--brand);
            box-shadow: 0 4px 10px rgba(0,0,0,.06);
            overflow: hidden;
            flex-shrink: 0;
        }

        .uni-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
        }

        .uni-badge-scholarship {
            background: #fde3e3;
            color: var(--brand);
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 999px;
            white-space: nowrap;
        }

        .uni-name {
            font-weight: 700;
            font-size: 16.5px;
            margin-bottom: 4px;
            color: #1d2333;
        }

        .uni-location {
            font-size: 13px;
            color: #8a90a2;
            margin-bottom: 16px;
        }

        /* === FIX: tags field-of-study dihilangkan supaya layout sama persis
           dengan gambar referensi (yang tidak menampilkan tags sama sekali). === */

        .uni-meta {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            border-top: 1px solid #f0f1f5;
            padding-top: 14px;
            margin-top: auto;
            margin-bottom: 14px;
        }

        .uni-meta .label {
            display: block;
            font-size: 10.5px;
            color: #8a90a2;
            text-transform: uppercase;
            letter-spacing: .03em;
            margin-bottom: 3px;
        }

        .uni-meta .value {
            font-weight: 700;
            font-size: 13px;
            color: #1d2333;
        }

        /* === FIX: "View Profile" sebelumnya tombol merah full-width, sekarang
           text-link kecil dengan panah, rata kiri, sesuai gambar referensi. === */
        .btn-view {
            color: var(--brand);
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-view:hover { color: var(--brand-dark); gap: 9px; }

        /* ---------- EMPTY STATE ---------- */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #8a90a2;
        }

        .empty-state i { font-size: 46px; color: #dcdfe8; margin-bottom: 16px; display: block; }

        /* ---------- CTA ---------- */
        .cta-card {
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            border-radius: 20px;
            color: #fff;
            padding: 40px;
            text-align: center;
        }

        .btn-cta {
            background: #fff;
            color: var(--brand);
            border: none;
            padding: 13px 30px;
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
            display: inline-block;
            margin-top: 16px;
        }

        .btn-cta:hover { background: #f5f5f5; color: var(--brand-dark); }

        @media (max-width: 767px) {
            .hero { padding: 40px 0 45px; text-align: center; }
            .search-box { margin: 0 auto; }
            .hero-eyebrow { margin-left: auto; margin-right: auto; }
            .catalog-toolbar { flex-direction: column; align-items: flex-start; }
            .filter-bar { width: 100%; }
            .filter-select { flex: 1 1 45%; }
        }
    </style>
</head>

<body>

    <!-- HERO -->
    <div class="hero">
        <div class="container hero-content">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <span class="hero-eyebrow">
                        <i class="bi bi-mortarboard"></i> Study in China
                    </span>
                    <h1>University <span class="text-red">Partner</span></h1>
                    <p class="subtitle mx-auto">
                        Explore our partner universities and find the best match for your major, budget, and goals.
                    </p>

                    <form method="GET" action="{{ url()->current() }}" class="search-box d-flex gap-2 mx-auto mt-4">
                        <input type="text" name="search" value="{{ $search ?? '' }}"
                               class="form-control border-0 ps-3"
                               placeholder="Search university, city, or country...">
                        <button type="submit" class="btn-search">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        <!-- RESULT COUNT + FILTER DROPDOWNS -->
        <div class="catalog-toolbar">
            <span class="result-count"><strong>{{ $universities->count() }}</strong> universities found</span>

            {{-- Dropdown di bawah ini masih placeholder tampilan saja (belum
                 difungsikan) sesuai permintaan -- sambungkan ke logic filter
                 city/major/type/scholarship begitu datanya siap. --}}
            <div class="filter-bar">
                <select class="filter-select" disabled>
                    <option>All Cities</option>
                </select>
                <select class="filter-select" disabled>
                    <option>All Majors</option>
                </select>
                <select class="filter-select" disabled>
                    <option>All Types</option>
                </select>
                <select class="filter-select" disabled>
                    <option>Scholarship Available</option>
                </select>
                <a href="{{ url()->current() }}" class="filter-reset">
                    <i class="bi bi-arrow-clockwise"></i> Reset
                </a>
            </div>
        </div>

        <!-- CATALOG GRID -->
        @if($universities->count())
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4 mb-5" id="catalogGrid">
            @foreach($universities as $uni)
                @php $profile = $profiles->get($uni->id); @endphp
                <div class="col uni-col">
                    <div class="uni-card">
                        <div class="uni-card-top">
                            <div class="uni-logo">
                                @if(!empty($uni->logo) && file_exists(public_path($uni->logo)))
                                    <img src="{{ asset($uni->logo) }}" alt="{{ $uni->name }}">
                                @else
                                    {{ substr($uni->name, 0, 1) }}
                                @endif
                            </div>

                            @if($profile && $profile->scholarship_available)
                                <span class="uni-badge-scholarship">Scholarship Available</span>
                            @endif
                        </div>

                        <div class="uni-name">{{ $uni->name }}</div>
                        <div class="uni-location">
                            <i class="bi bi-geo-alt"></i>
                            {{ $uni->city ?? 'City N/A' }}{{ $uni->country ? ', ' . $uni->country : '' }}
                        </div>

                        <div class="uni-meta">
                            <div>
                                {{-- QS Ranking belum ada di skema DB -- placeholder dulu --}}
                                <span class="label">QS Ranking</span>
                                <span class="value">N/A</span>
                            </div>
                            <div>
                                <span class="label">Est. Tuition / Year</span>
                                <span class="value">
                                    @if($profile && $profile->min_budget)
                                        Rp {{ number_format($profile->min_budget, 0, ',', '.') }}+
                                    @else
                                        Contact us
                                    @endif
                                </span>
                            </div>
                            <div>
                                <span class="label">Language</span>
                                <span class="value">
                                    {{ ($profile && $profile->language) ? $profile->language : 'Contact us' }}
                                </span>
                            </div>
                        </div>

                        <a href="{{ route('frontend.university.profile', $uni->id) }}" class="btn-view">
                            View Details <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="empty-state mb-5">
            <i class="bi bi-search"></i>
            <h5>No universities found</h5>
            <p>Try a different keyword or clear your search.</p>
            <a href="{{ url()->current() }}" class="btn-view d-inline-block px-4">Clear Search</a>
        </div>
        @endif

        <!-- CTA -->
        <div class="cta-card mb-5">
            <h3 class="fw-bold mb-2">Not sure which university fits you?</h3>
            <p class="mb-0 opacity-75">Take our quick quiz and get personalized university recommendations.</p>
            <a href="{{ route('frontend.form.wizard') }}" class="btn-cta">
                <i class="bi bi-lightning-charge"></i> Take the Quiz
            </a>
        </div>

    </div>

</body>

</html>
