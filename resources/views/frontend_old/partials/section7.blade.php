{{--
    Section 7 - "Find your best path". Background pakai image/bg5.png (foto skyline
    China + kabut, sudah termasuk elemen visual kanan). Kartu-kartu foto yang
    ditumpuk miring di belakang phone-card itu MASIH PLACEHOLDER (kotak polos +
    icon) karena belum ada foto spesifiknya — ganti .section-7__stack-photo jadi
    <img> begitu ada asetnya. Avatar 1000+ students juga masih placeholder icon,
    ganti jadi <img> kalau sudah ada foto testimonial.

    === FIX "pas 1 layar" ===
    Section-2 selalu tinggi 1 layar penuh karena pakai class "color-section"
    (utility class dari layout utama: min-height:100vh di desktop >=769px,
    60vh di mobile, plus flex align-items/justify-content:center).
    Section-7 sebelumnya tidak pakai class itu, jadi tingginya cuma
    ngikutin konten. Sekarang ditambahkan "color-section" di tag <section>
    supaya perilakunya identik dengan section-2, dan tetap responsive karena
    ikut breakpoint yang sama (60vh di HP, 100vh di desktop).

    === FIX RESPONSIVE (baru) ===
    Sebelumnya ukuran-ukuran di kolom visual (ring, stack-photo, card) itu
    FIXED PX dan cuma berubah di 2 titik breakpoint (900px & 480px). Di
    antara dua titik itu (misal tablet/landscape ~600-850px, atau layar HP
    besar ~480-600px), ukurannya masih sama persis kayak desktop (ring 480px,
    dsb) padahal lebar kolomnya sudah menyempit jadi 1 kolom penuh -> ring
    kelihatan kegedean/mepet ke tepi.
    Fix-nya: ukuran-ukuran itu diganti ke clamp(min, preferred-vw, max) jadi
    scale-nya HALUS mengikuti lebar layar berapa pun, bukan cuma "loncat" di
    2 titik. Breakpoint 900px & 480px yang lama TETAP dipertahankan karena
    itu buat perubahan STRUKTUR (grid 2 kolom -> 1 kolom, features row -> kolom),
    bukan buat ukuran, jadi tidak dihapus.
--}}
<style>
.section-7 {
    position: relative;
    background-color: #faf3ec;
    background-image:
        linear-gradient(90deg, rgba(250,243,236,.97) 0%, rgba(250,243,236,.85) 38%, rgba(250,243,236,.35) 62%, rgba(250,243,236,0) 100%),
        url('{{ asset('image/bg5.png') }}');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    /* === FIX RESPONSIVE === padding vertikal ikut menyempit di layar kecil,
       bukan tetap 5.5rem di semua ukuran */
    padding: clamp(3rem, 8vw, 5.5rem) 1.5rem;
    overflow: hidden;
}

.section-7__grid {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    align-items: center;
    width: 100%;
}

/* ===== Kolom kiri ===== */

.section-7__eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    color: var(--s7-red, #C8102E);
    margin-bottom: 1.1rem;
}

.section-7__eyebrow::before {
    content: "";
    width: 32px;
    height: 2px;
    background: var(--s7-red, #C8102E);
    display: block;
}

.section-7__title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 500;
    font-size: clamp(2rem, 4.2vw, 3rem);
    line-height: 1.25;
    color: #1F2937;
    margin: 0 0 1.25rem;
}

.section-7__title .text-red {
    color: var(--s7-red, #C8102E);
}

.section-7__desc {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 1rem;
    line-height: 1.7;
    color: #6b7280;
    max-width: 30rem;
    margin: 0 0 2rem;
}

.section-7__features {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.section-7__feature {
    display: flex;
    align-items: center;
    gap: 12px;
}

.section-7__feature-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1.5px solid rgba(200, 16, 46, .35);
    color: var(--s7-red, #C8102E);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.section-7__feature-text {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.85rem;
    font-weight: 600;
    color: #1F2937;
    line-height: 1.35;
}

.section-7__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: var(--s7-red, #C8102E);
    color: #fff;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 700;
    font-size: 1rem;
    padding: 1rem 1.75rem;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 12px 28px rgba(200, 16, 46, .25);
    transition: all .2s ease;
    margin-bottom: 1.5rem;
}

.section-7__btn:hover {
    background: #a30d25;
    color: #fff;
    transform: translateY(-2px);
}

.section-7__social-proof {
    display: flex;
    align-items: center;
    gap: 14px;
}

.section-7__avatars {
    display: flex;
}

.section-7__avatar {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #e5ddcf;
    border: 2px solid #faf3ec;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9a9186;
    font-size: 0.9rem;
    margin-left: -10px;
}

.section-7__avatar:first-child { margin-left: 0; }

.section-7__social-text {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.85rem;
    color: #4b5563;
    line-height: 1.35;
}

/* ===== Kolom kanan: visual ===== */

.section-7__visual {
    position: relative;
    /* === FIX RESPONSIVE === scale halus, bukan cuma 420px lalu loncat ke 360px */
    min-height: clamp(300px, 40vw, 420px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.section-7__ring {
    position: absolute;
    border: 1px dashed rgba(200, 16, 46, .25);
    border-radius: 50%;
}

.section-7__ring--outer {
    /* === FIX RESPONSIVE === dulu fixed 480px, sekarang scale ikut lebar layar */
    width: clamp(230px, 42vw, 480px);
    height: clamp(230px, 42vw, 480px);
}

.section-7__ring--inner {
    /* === FIX RESPONSIVE === dulu fixed 340px */
    width: clamp(170px, 30vw, 340px);
    height: clamp(170px, 30vw, 340px);
}

.section-7__dot {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--s7-red, #C8102E);
}

.section-7__stack-photo {
    position: absolute;
    /* === FIX RESPONSIVE === dulu fixed 190x280, sekarang scale halus */
    width: clamp(115px, 16vw, 190px);
    height: clamp(170px, 24vw, 280px);
    border-radius: 18px;
    background: #d9cfbd;
    box-shadow: 0 20px 40px rgba(31, 41, 55, .18);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9a9186;
    font-size: 1.6rem;
}

.section-7__stack-photo--1 {
    /* === FIX RESPONSIVE === jarak translate ikut menyempit di layar kecil,
       biar foto ga kepental jauh keluar area visual di HP */
    transform: rotate(-12deg) translate(clamp(-70px, -9vw, -38px), 10px);
}

.section-7__stack-photo--2 {
    transform: rotate(10deg) translate(clamp(38px, 9vw, 70px), 20px);
}

.section-7__card {
    position: relative;
    z-index: 2;
    /* === FIX RESPONSIVE === dulu fixed 260px */
    width: clamp(210px, 30vw, 260px);
    background: #fbf6ee;
    border-radius: 22px;
    padding: 1.5rem 1.4rem 1.75rem;
    box-shadow: 0 30px 60px rgba(31, 41, 55, .22);
}

.section-7__card-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--s7-red, #C8102E);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 1.1rem;
}

.section-7__card-title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-size: 1.3rem;
    line-height: 1.35;
    color: #1F2937;
    margin: 0 0 1.1rem;
}

.section-7__card-title .text-red {
    color: var(--s7-red, #C8102E);
}

.section-7__card-divider {
    border: none;
    border-top: 1px solid rgba(31,41,55,.1);
    margin: 0 0 1.1rem;
}

.section-7__card-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0.9rem;
}

.section-7__card-row:last-child { margin-bottom: 0; }

.section-7__card-row i {
    color: var(--s7-red, #C8102E);
    font-size: 0.95rem;
    flex-shrink: 0;
    width: 18px;
    text-align: center;
}

.section-7__card-row-body {
    flex: 1;
}

.section-7__card-row-label {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    color: #1F2937;
    margin-bottom: 5px;
}

.section-7__card-row-bar {
    height: 5px;
    border-radius: 999px;
    background: #e9e2d4;
    overflow: hidden;
}

.section-7__card-row-bar span {
    display: block;
    height: 100%;
    background: var(--s7-red, #C8102E);
    border-radius: 999px;
}

.section-7__badge {
    position: absolute;
    right: -14px;
    bottom: 14px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: var(--s7-red, #C8102E);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 10px 22px rgba(200, 16, 46, .35);
    z-index: 3;
}

@media (max-width: 900px) {
    .section-7__grid {
        grid-template-columns: 1fr;
    }
    .section-7__visual {
        /* min-height sekarang sudah fluid lewat clamp() di atas, tidak perlu
           di-override lagi di sini */
        margin-top: 2rem;
    }
    .section-7 {
        background-image:
            linear-gradient(180deg, rgba(250,243,236,.95) 0%, rgba(250,243,236,.6) 100%),
            url('{{ asset('image/bg5.png') }}');
    }
}

@media (max-width: 480px) {
    .section-7__features {
        flex-direction: column;
        gap: 1rem;
    }
    /* Ukuran ring/stack-photo di HP kecil sudah otomatis ikut mengecil lewat
       clamp() di rule dasarnya (berdasarkan vw), jadi override manual yang
       dulu ada di sini ("width:140px" dst) sudah tidak diperlukan lagi. */
}
</style>

<section class="color-section section-7" id="section-7" style="--s7-red: #C8102E;">
    <div class="section-7__grid">

        <div class="section-7__col-text">
            <div class="section-7__eyebrow">Find Your Best Path</div>

            <h2 class="section-7__title">Find the scholarship<br>that fits <span class="text-red">you.</span></h2>

            <p class="section-7__desc">
                Answer a few quick questions and get a personalized recommendation
                for universities and scholarships that match your profile.
            </p>

            <div class="section-7__features">
                <div class="section-7__feature">
                    <span class="section-7__feature-icon"><i class="bi bi-clock"></i></span>
                    <span class="section-7__feature-text">Takes less<br>than 1 minute</span>
                </div>
                <div class="section-7__feature">
                    <span class="section-7__feature-icon"><i class="bi bi-person"></i></span>
                    <span class="section-7__feature-text">Personalized<br>just for you</span>
                </div>
                <div class="section-7__feature">
                    <span class="section-7__feature-icon"><i class="bi bi-shield-check"></i></span>
                    <span class="section-7__feature-text">100% free<br>&amp; no sign up</span>
                </div>
            </div>

            <a href="{{ route('frontend.form.wizard') }}" class="section-7__btn">
                Start Assessment <i class="bi bi-arrow-right"></i>
            </a>

            <div class="section-7__social-proof">
                <div class="section-7__avatars">
                    <span class="section-7__avatar"><i class="bi bi-person-fill"></i></span>
                    <span class="section-7__avatar"><i class="bi bi-person-fill"></i></span>
                    <span class="section-7__avatar"><i class="bi bi-person-fill"></i></span>
                    <span class="section-7__avatar"><i class="bi bi-person-fill"></i></span>
                </div>
                <span class="section-7__social-text">1,000+ students already<br>found their best match</span>
            </div>
        </div>

        <div class="section-7__visual">
            <span class="section-7__ring section-7__ring--outer"></span>
            <span class="section-7__ring section-7__ring--inner"></span>
            <span class="section-7__dot" style="top: 22%; left: 12%;"></span>
            <span class="section-7__dot" style="top: 58%; left: 6%;"></span>

            <div class="section-7__stack-photo section-7__stack-photo--1"><i class="bi bi-image"></i></div>
            <div class="section-7__stack-photo section-7__stack-photo--2"><i class="bi bi-image"></i></div>

            <div class="section-7__card">
                <div class="section-7__card-icon">学</div>
                <h3 class="section-7__card-title">Your future.<br>Our guidance.<br>The <span class="text-red">right match.</span></h3>
                <hr class="section-7__card-divider">

                <div class="section-7__card-row">
                    <div class="section-7__card-row-body">
                        <div class="section-7__card-row-label">Personalized Recommendation</div>
                        <div class="section-7__card-row-bar"><span style="width: 90%;"></span></div>
                    </div>
                </div>

                <div class="section-7__card-row">
                    <i class="bi bi-bank"></i>
                    <div class="section-7__card-row-body">
                        <div class="section-7__card-row-label">Best-fit Universities</div>
                        <div class="section-7__card-row-bar"><span style="width: 75%;"></span></div>
                    </div>
                </div>

                <div class="section-7__card-row">
                    <i class="bi bi-award"></i>
                    <div class="section-7__card-row-body">
                        <div class="section-7__card-row-label">Available Scholarships</div>
                        <div class="section-7__card-row-bar"><span style="width: 60%;"></span></div>
                    </div>
                </div>

                <span class="section-7__badge"><i class="bi bi-check-lg"></i></span>
            </div>
        </div>

    </div>
</section>
