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

        {{-- Ornamen tanpa gambar: gerbang lengkung merah dengan tulisan 留学中国 ("kuliah di China"). --}}
        <div class="hero__art" aria-hidden="true">
            <div class="hero__arch">
                <span class="hero__hanzi"><span>留</span><span>学</span><span>中</span><span>国</span></span>
            </div>
            <span class="hero__seal"><span>印</span><span>留</span></span>
        </div>
    </div>
</section>
