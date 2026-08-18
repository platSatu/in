<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INASTUDY | CHINA EDUCATION CONSULTANT</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,500;6..96,700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hanken+Grotesk:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&family=Bodoni+Moda:ital,opsz,wght@0,6..96,400..900;1,6..96,400..900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            /* font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; */
            scroll-behavior: smooth;
            scroll-snap-type: y proximity; /* 'proximity' lebih halus, 'mandatory' lebih tegas/kaku */
            scroll-behavior: smooth;
        }

        .section-1, .section-2, .section-3, .section-4, .section-5, .section-6, .section-7 {
            scroll-snap-align: start;
            min-height: 100vh;
        }

        /* ============================================================
           MENU (bukan termasuk section)
           Layout: Inastudy (kiri) - Menu (tengah) - ID | EN | 中文 (kanan)
        ============================================================ */
        .site-header {
            background: transparent; /* kondisi awal di section-1: transparan, ngambang di atas hero */
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 100; /* pastikan di atas konten section 1 */
            /* === FIX ===
               Transisi halus buat perubahan background & shadow begitu
               header masuk state "is-scrolled" (lihat CSS + JS di bawah). */
            transition: background-color 0.35s ease, box-shadow 0.35s ease;
        }

        /* === FIX: header dapat background begitu discroll =====
           Class ini ditambahkan lewat JS (window scroll listener) begitu
           scrollY sudah lewat sedikit dari 0 (menandakan sudah keluar dari
           hero section-1 paling atas). Warnanya krem senada dengan
           section-6 (bg2.png / #f1ebe1) + garis stripe tipis diagonal biar
           konsisten sama nuansa desain lain, lalu dikasih shadow tipis di
           bawah supaya kelihatan "mengambang" di atas konten yang discroll. */
        .site-header.is-scrolled {
            background-color: rgba(245, 238, 227, 0.96);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
            box-shadow: 0 2px 18px rgba(31, 41, 55, 0.08);
        }

        .site-header__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* === FIX RESPONSIVE DESKTOP ===
               Sebelumnya "max-width:1320px; margin:0 auto" bikin header
               di-CENTER di tengah layar, sedangkan konten hero (section-1)
               nempel ke tepi kiri viewport lewat padding clamp(). Di layar
               lebar (Mac 17"/21"), dua sistem beda ini bikin logo INASTUDY
               kelihatan bergeser jauh dari judul hero di bawahnya -> terlihat
               berantakan.
               Sekarang header dibuat pakai inset kiri-kanan yang SAMA PERSIS
               dengan hero (clamp(1.5rem, 6vw, 6rem)), jadi di layar berapa
               pun lebarnya, logo & judul hero selalu sejajar. */
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0.85rem clamp(1.5rem, 6vw, 6rem);
        }

        .site-header__brand {
            flex: 1 1 0;
            text-align: left;
            font-size: 1.35rem;
            font-weight: 700;
            color: #212529;
            text-decoration: none;
            /* letter-spacing: -0.01em; */
            letter-spacing: 0.08em;
        }

        .site-header__brand small {
            display: block;
            font-size: 0.50rem;
            font-weight: 400;
            letter-spacing: 0.15em;
            margin-top: 4px;
            color: #6b7280;
        }

        .site-header__lang {
            flex: 1 1 0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 0.5rem;
        }

        .site-header__lang a {
            font-weight: 500;
            font-size: 0.9rem;
            color: #212529;
            text-decoration: none;
        }

        .site-header__lang a:hover {
            color: #6c757d;
        }

        .site-header__lang a.is-active {
            color: #6c5ce7;
            font-weight: 700;
        }

        .site-header__lang span {
            color: #ced4da;
            font-size: 0.85rem;
        }

        .site-header__menu {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
            justify-content: center;
        }

        .site-header__menu a {
            font-family: "Bodoni 72", "Bodoni 72 Smallcaps", serif;
            color: #212529;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .site-header__menu a:hover {
            color: #6c757d;
        }

        .site-header__toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            cursor: pointer;
            color: #212529;
            padding: 0;
            width: 2rem;
        }

        .site-header__spacer {
            display: none;
            width: 2rem;
        }

        /* ---------- Off-canvas menu (mobile) ---------- */
        .offcanvas-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            z-index: 1040;
        }

        .offcanvas-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 78vw;
            max-width: 320px;
            background: #fff;
            box-shadow: 2px 0 16px rgba(0, 0, 0, 0.15);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1050;
            padding: 1.25rem 1.5rem;
            overflow-y: auto;
        }

        .offcanvas-menu__close {
            background: none;
            border: none;
            font-size: 1.75rem;
            line-height: 1;
            color: #212529;
            cursor: pointer;
            margin-bottom: 1.5rem;
            padding: 0;
        }

        .offcanvas-menu__list {
            list-style: none;
            margin: 0 0 2rem;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 1.1rem;
        }

        .offcanvas-menu__list a {
            color: #212529;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 500;
        }

        .offcanvas-menu__lang {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            padding-top: 1.25rem;
            border-top: 1px solid #eee;
        }

        .offcanvas-menu__lang a {
            font-weight: 500;
            font-size: 0.95rem;
            color: #212529;
            text-decoration: none;
        }

        .offcanvas-menu__lang a.is-active {
            color: #6c5ce7;
            font-weight: 700;
        }

        .offcanvas-menu__lang span {
            color: #ced4da;
        }

        @media (max-width: 768px) {
            .site-header__inner {
                justify-content: space-between;
            }

            .site-header__brand {
                order: 2;
                flex: 1 1 auto;
                text-align: center;
            }

            .site-header__toggle {
                order: 1;
                display: block;
                flex: 0 0 auto;
                text-align: left;
            }

            .site-header__spacer {
                order: 3;
                display: block;
                flex: 0 0 auto;
            }

            /* Menu horizontal & lang bawaan disembunyikan, digantikan off-canvas */
            .site-header__menu,
            .site-header > .site-header__inner > .site-header__lang {
                display: none;
            }

            .offcanvas-overlay,
            .offcanvas-menu {
                display: block;
            }

            body.menu-open {
                overflow: hidden;
            }

            body.menu-open .offcanvas-overlay {
                opacity: 1;
                pointer-events: auto;
            }

            body.menu-open .offcanvas-menu {
                transform: translateX(0);
            }
        }

        /* ============================================================
           SECTIONS (warna saja)
        ============================================================ */
        .color-section {
            min-height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @media (min-width: 769px) {
            .color-section {
                min-height: 100vh;
            }
        }

        .section-1 { background: #FF6B6B; }
        .section-2 { background: #4ECDC4; }
        .section-3 { background: #FFD93D; }
        .section-4 { background: #6C5CE7; }
        .section-5 { background: #1A936F; }

        /* ============================================================
           FOOTER
        ============================================================ */
        .site-footer {
            background: #212529;
            color: rgba(255, 255, 255, 0.75);
            padding: 2.5rem 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .site-footer strong {
            color: #fff;
        }
    </style>
</head>
<body>

    {{-- ================= MENU ================= --}}
    <header class="site-header" id="site-header">
        <div class="site-header__inner">
            <button class="site-header__toggle" id="menu-toggle" aria-label="Buka menu">&#9776;</button>

            <a href="#" class="site-header__brand">INASTUDY<br>
                <small>China Education Consultant</small>
            </a>

            <nav>
                <ul class="site-header__menu">
                    <li><a href="#section-1">Home</a></li>
                    <li><a href="#section-2">Journey</a></li>
                    <li><a href="#section-3">Services</a></li>
                    <li><a href="#section-4">Insights</a></li>
                    <li><a href="#section-5">About us</a></li>
                </ul>
            </nav>

            <div class="site-header__lang">
                <a href="#" class="is-active">ID</a>
                <span>|</span>
                <a href="#">EN</a>
                <span>|</span>
                <a href="#">中文</a>
            </div>

            {{-- spacer agar brand tetap center di mobile, seimbang dgn lebar tombol hamburger --}}
            <div class="site-header__spacer"></div>
        </div>
    </header>

    {{-- ================= OFF-CANVAS MENU (mobile) ================= --}}
    <div class="offcanvas-overlay" id="offcanvas-overlay"></div>
    <nav class="offcanvas-menu" id="offcanvas-menu" aria-label="Menu utama">
        <button class="offcanvas-menu__close" id="menu-close" aria-label="Tutup menu">&times;</button>

        <ul class="offcanvas-menu__list">
            <li><a href="#section-1">Home</a></li>
            <li><a href="#section-2">Journey</a></li>
            <li><a href="#section-3">Services</a></li>
            <li><a href="#section-4">Insights</a></li>
            <li><a href="#section-5">About us</a></li>
        </ul>

        <div class="offcanvas-menu__lang">
            <a href="#" class="is-active">ID</a>
            <span>|</span>
            <a href="#">EN</a>
            <span>|</span>
            <a href="#">中文</a>
        </div>
    </nav>

    {{-- ================= SECTIONS ================= --}}
    @include('frontend.partials.section1')
    @include('frontend.partials.section2')
    @include('frontend.partials.section7')
    @include('frontend.partials.section3')
    @include('frontend.partials.section4')
    @include('frontend.partials.section6')
    @include('frontend.partials.section5')


    {{-- ================= FOOTER ================= --}}
    @include('frontend.partials.footer')
    <!-- <footer class="site-footer">
        <p><strong>Inastudy</strong> &mdash; &copy; {{ date('Y') }}. All rights reserved.</p>
    </footer> -->

    <script>
        var toggle  = document.getElementById('menu-toggle');
        var close   = document.getElementById('menu-close');
        var overlay = document.getElementById('offcanvas-overlay');
        var menu    = document.getElementById('offcanvas-menu');

        function openMenu() {
            document.body.classList.add('menu-open');
        }

        function closeMenu() {
            document.body.classList.remove('menu-open');
        }

        if (toggle) toggle.addEventListener('click', openMenu);
        if (close) close.addEventListener('click', closeMenu);
        if (overlay) overlay.addEventListener('click', closeMenu);

        // Tutup menu saat salah satu link diklik
        if (menu) {
            menu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });
        }

        // Tutup menu otomatis jika layar di-resize ke ukuran desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeMenu();
        });

        // === FIX: header baru dapat background SETELAH hero (section-1)
        // hampir habis discroll, bukan langsung begitu digeser sedikit.
        // Threshold sebelumnya (24px) kepicu hampir instan begitu discroll
        // dikit -> makanya keliatan "langsung" pakai background.
        // Sekarang threshold dihitung dari tinggi section-1 sendiri, jadi
        // header tetap transparan selama section-1 (hero) masih dominan di
        // layar, dan baru berubah pas mendekati section-2.
        var siteHeader = document.getElementById('site-header');
        var heroSection = document.getElementById('section-1');

        function getScrollThreshold() {
            // Kalau section-1 ketemu, pakai tinggi asli section-1 (dikurangi
            // dikit) sebagai batas. Kalau tidak ada, fallback ke tinggi layar.
            var base = heroSection ? heroSection.offsetHeight : window.innerHeight;
            return base * 0.85;
        }

        function updateHeaderBg() {
            if (!siteHeader) return;
            if (window.scrollY > getScrollThreshold()) {
                siteHeader.classList.add('is-scrolled');
            } else {
                siteHeader.classList.remove('is-scrolled');
            }
        }

        window.addEventListener('scroll', updateHeaderBg, { passive: true });
        window.addEventListener('resize', updateHeaderBg);
        updateHeaderBg(); // jaga-jaga kalau halaman di-reload sambil posisi scroll sudah turun
    </script>

</body>
</html>
