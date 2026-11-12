{{-- Gambar latar nanti cukup diisi di sini, mis. style="--hero-image: url('{{ asset('image/home/inastudy.jpg') }}')". Tanpa itu, latar memakai warna. --}}
<section class="section hero" id="inastudy">
    <div class="container hero__inner">
        <div class="hero__text">
            <span class="eyebrow">China Education Consultant</span>
            <h1 class="hero__title">Study in <em>China</em></h1>
            <p class="hero__lead">Kami dampingi dari memilih kampus dan beasiswa, mengurus dokumen dan visa, sampai kamu tiba dan mulai kuliah di China.</p>

            <div class="hero__actions">
                <a href="{{ route('frontend.university.catalog') }}" class="btn btn--primary">Cari Universitas</a>
                <a href="{{ route('frontend.form.wizard') }}" class="btn btn--ghost">Ikuti Quiz</a>
            </div>

            <ul class="hero__points">
                <li>Pendampingan beasiswa</li>
                <li>Dokumen &amp; visa</li>
                <li>Persiapan bahasa Mandarin</li>
            </ul>
        </div>

        {{-- Alur perjalanan kuliah ke China. Garis & titik berjalan digambar home.js mengikuti posisi ikon, jadi tetap pas di semua layar. --}}
        <div class="journey" data-journey>
            <p class="journey__label">Perjalananmu, kami dampingi</p>
            <div class="journey__track">
                <svg class="journey__route" aria-hidden="true" focusable="false">
                    <path class="journey__line" id="journeyRoute" d="" />
                    <path class="journey__flow" d="" />
                    <circle class="journey__halo" r="13"><animateMotion dur="10.5s" repeatCount="indefinite"><mpath href="#journeyRoute" /></animateMotion></circle>
                    <circle class="journey__dot" r="6"><animateMotion dur="10.5s" repeatCount="indefinite"><mpath href="#journeyRoute" /></animateMotion></circle>
                </svg>
                <ol class="journey__steps">
                    <li class="journey__step" style="--i: 0">
                        <span class="journey__icon"><i class="bi bi-bank" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Pilih Kampus</strong><small>Bandingkan kampus &amp; jurusan</small></span>
                    </li>
                    <li class="journey__step" style="--i: 1">
                        <span class="journey__icon"><i class="bi bi-file-earmark-text" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Siapkan Dokumen</strong><small>Kami bantu cek &amp; lengkapi</small></span>
                    </li>
                    <li class="journey__step" style="--i: 2">
                        <span class="journey__icon"><i class="bi bi-pencil-square" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Daftar &amp; Tes</strong><small>Pendaftaran dan tes masuk</small></span>
                    </li>
                    <li class="journey__step" style="--i: 3">
                        <span class="journey__icon"><i class="bi bi-patch-check" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Diterima</strong><small>Letter of Admission terbit</small></span>
                    </li>
                    <li class="journey__step" style="--i: 4">
                        <span class="journey__icon"><i class="bi bi-airplane" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Tiba di China</strong><small>Visa &amp; keberangkatan</small></span>
                    </li>
                    <li class="journey__step" style="--i: 5">
                        <span class="journey__icon"><i class="bi bi-people" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Kehidupan Kampus</strong><small>Adaptasi &amp; pendampingan</small></span>
                    </li>
                    <li class="journey__step" style="--i: 6">
                        <span class="journey__icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span>
                        <span class="journey__text"><strong>Lulus</strong><small>Ijazah di tangan</small></span>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</section>
