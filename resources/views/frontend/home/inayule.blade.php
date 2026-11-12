<section class="section inayule" id="inayule">
    <div class="container">
        {{-- Pencapaian InaYule. Angka ditulis lengkap di HTML; home.js hanya menganimasikan hitungannya saat terlihat. --}}
        <div class="stats">
            <h2 class="stats__title">Keberhasilan INA STUDY Chinese Course <em>Mewujudkan Impian Anda</em></h2>
            <ul class="stats__grid">
                <li><strong data-count="98" data-suffix="%">98%</strong><span>Siswa mencapai kompetensi yang diharapkan</span></li>
                <li><strong data-count="500" data-suffix="+">500+</strong><span>Jumlah peserta didik</span></li>
                <li><strong data-count="92" data-suffix="%">92%</strong><span>Lulus ujian sertifikasi bahasa</span></li>
                <li><strong data-count="4.9" data-decimals="1" data-suffix="<small>/5,0</small>">4,9<small>/5,0</small></strong><span>Penilaian dari siswa kami</span></li>
                <li><strong data-count="25" data-suffix="+">25+</strong><span>Tahun telah mengajar</span></li>
                <li><strong data-count="20" data-suffix="+">20+</strong><span>Guru berkualitas</span></li>
            </ul>
        </div>

        <div class="section-head">
            <div>
                <span class="eyebrow">InaYule &middot; Kursus Mandarin</span>
                <h2 class="section-title">Learning <em>Chinese</em></h2>
                <p class="section-lead">Pilih kelas sesuai level dan waktumu, belajar langsung bersama pengajar InaYule.</p>
            </div>
            <a href="{{ route('frontend.inayule.classes') }}" class="link-arrow">Lihat semua kelas</a>
        </div>

        @include('frontend.home._packages', ['packages' => $packages, 'limit' => 8])
    </div>
</section>
