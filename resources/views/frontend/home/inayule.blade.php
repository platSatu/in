<section class="section inayule" id="inayule">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="eyebrow">InaYule &middot; Kursus Mandarin</span>
                <h2 class="section-title">Learning <em>Chinese</em></h2>
                <p class="section-lead">Pilih kelas sesuai level dan waktumu, belajar langsung bersama pengajar InaYule.</p>
            </div>
            <a href="{{ route('frontend.inayule.classes') }}" class="link-arrow">Lihat semua kelas</a>
        </div>

        @include('frontend.home._packages', ['packages' => $packages])
    </div>
</section>
