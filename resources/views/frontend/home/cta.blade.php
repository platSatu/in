{{-- Ajakan penutup sebelum Location. Ornamen: rute terbang Indonesia → China (perjalanan studi) dan watermark 留学 (liúxué = kuliah di luar negeri). --}}
<section class="cta" id="konsultasi" data-reveal>
    <div class="container">
        <div class="cta__box">
            <div class="cta__route" aria-hidden="true">
                <span class="cta__city">Indonesia</span>
                <svg viewBox="0 0 600 70" preserveAspectRatio="none" focusable="false">
                    <path id="ctaRoute" d="M6 60 C 160 -6, 440 -6, 594 40" />
                    <g class="cta__plane"><path d="M-9 -2 L9 0 L-9 2 L-6 0 Z M-3 0 L-7 -7 L-4 -7 L3 0 L-4 7 L-7 7 Z" />
                        <animateMotion dur="7s" repeatCount="indefinite" rotate="auto"><mpath href="#ctaRoute" /></animateMotion></g>
                </svg>
                <span class="cta__city"><b lang="zh">中国</b> China</span>
            </div>

            <h2 class="cta__title"><span>Kuliah di China</span><br> <mark>bukan lagi mimpi.</mark></h2>
            <p class="cta__lead">Ceritakan rencanamu, kami bantu pilih kampus, beasiswa, sampai kamu berangkat.</p>

            <div class="cta__actions">
                {{-- WhatsApp sudah ada di tombol melayang kanan bawah, jadi di sini cukup kampus & quiz. --}}
                <a href="{{ route('frontend.university.catalog') }}" class="btn cta__btn">Cari Kampus Impianmu <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('frontend.form.wizard') }}" class="btn cta__btn cta__btn--line">Ikuti Quiz Dulu</a>
            </div>

            <ul class="cta__facts">
                <li><i class="bi bi-award" aria-hidden="true"></i> 25+ tahun pengalaman</li>
                <li><i class="bi bi-people" aria-hidden="true"></i> 500+ peserta didik</li>
                <li><i class="bi bi-bank" aria-hidden="true"></i> Kampus partner di China</li>
            </ul>

            <span class="cta__mark" aria-hidden="true"><b lang="zh">留学</b><small>liúxué · kuliah di luar negeri</small></span>
        </div>
    </div>
</section>
