@php
    // Ganti/tambah program di sini — carousel & dots otomatis menyesuaikan jumlahnya.
    $mandarinPrograms = [
        ['title' => 'Online Class', 'desc' => 'Learn anytime, anywhere with our flexible online Mandarin classes.'],
        ['title' => 'Kids Class', 'desc' => 'Fun Mandarin learning for young learners.'],
        ['title' => 'Adult Class', 'desc' => 'Practical Mandarin for everyday communication.'],
        ['title' => 'Business Mandarin', 'desc' => 'Mandarin for your professional career growth.'],
        ['title' => 'HSK Preparation', 'desc' => 'Focused training to help you pass the HSK exam with confidence.'],
        ['title' => 'Group Class', 'desc' => 'Learn together with friends in a fun, collaborative setting.'],
        ['title' => 'Private Tutoring', 'desc' => 'One-on-one sessions tailored to your personal learning pace.'],
        ['title' => 'Intensive Course', 'desc' => 'Accelerated Mandarin training for fast, focused progress.'],
        ['title' => 'Conversation Club', 'desc' => 'Practice real-life conversation in a relaxed community setting.'],
    ];
    $activeProgramIndex = 2; // index "Adult Class" jadi kartu aktif di tengah, sesuai contoh gambar
@endphp

<style>
.section-4 {
    position: relative;
    background-color: #f1e9dc;
    padding: 5rem 1.5rem 5rem;
    overflow: hidden;
}

.section-4__inner {
    max-width: 780px;
    margin: 0 auto;
    text-align: center;
}

.section-4__eyebrow {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: var(--s4-red, #C8102E);
    margin-bottom: 0.9rem;
}

.section-4__title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 500;
    font-size: clamp(1.6rem, 3.4vw, 2.3rem);
    line-height: 1.3;
    color: #1F2937;
    margin: 0 0 1rem;
}

.section-4__desc {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.9rem;
    line-height: 1.7;
    color: #6b7280;
    max-width: 32rem;
    margin: 0 auto 3rem;
}

/* ===== Video placeholder (ganti dengan <video> begitu sudah ada filenya) ===== */

.section-4__video-wrap {
    max-width: 620px;
    margin: 0 auto 3.5rem;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 25px 60px rgba(31, 41, 55, .12);
}

.section-4__video-placeholder {
    aspect-ratio: 16 / 9;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #e8dfd0;
    background-image: repeating-linear-gradient(
        45deg,
        rgba(31,41,55,.05) 0px,
        rgba(31,41,55,.05) 2px,
        transparent 2px,
        transparent 14px
    );
}

.section-4__video-placeholder span {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.68rem;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: #9a9186;
}

.section-4__video {
    display: block;
    width: 100%;
    aspect-ratio: 16 / 9;
    object-fit: cover;
}

/* ===== Coverflow carousel ===== */

.section-4__carousel {
    position: relative;
    /* === FIX BESAR & JARAK ===
       Tinggi & lebar area ditambah supaya kartu yang diperbesar (lihat
       .section-4__card di bawah) tetap muat dengan nyaman. */
    height: 380px;
    max-width: 1200px;
    margin: 0 auto;
    /* === DRAG ===
       "grab" jadi affordance visual: user tahu area ini bisa digeser tangan. */
    cursor: grab;
    touch-action: pan-y; /* biar scroll vertikal HP tetap jalan, drag carousel cuma horizontal */
    -webkit-user-select: none;
    user-select: none;
}

.section-4__carousel.is-dragging {
    cursor: grabbing;
}

.section-4__track {
    position: relative;
    width: 100%;
    height: 100%;
}

.section-4__card {
    position: absolute;
    top: 0;
    left: 50%;
    /* === FIX BESAR ===
       Kartu diperbesar dari 264px -> 320px biar kesannya lebih "besar" seperti
       contoh referensi. */
    width: 320px;
    margin-left: -160px;
    background: #e5ddcf;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(31, 41, 55, .08);
    text-align: left;
    cursor: pointer;
    /* === ASSEMBLE EFFECT ===
       Transform final kartu disusun dari beberapa custom property:
       - --scatter-*  : posisi "pecah/berantakan" awal (di-reset ke 0 saat masuk viewport)
       - --drag-x     : offset sementara saat lagi digeser tangan
       - --cf-x/scale : posisi & skala coverflow yang dihitung JS (render())
       Semua digabung di satu baris transform supaya animasinya nyambung mulus. */
    --scatter-x: 0px;
    --scatter-y: 0px;
    --scatter-rot: 0deg;
    --drag-x: 0px;
    --cf-x: 0px;
    --cf-scale: 1;
    transform:
        translateX(calc(var(--drag-x) + var(--scatter-x) + var(--cf-x)))
        translateY(var(--scatter-y))
        rotate(var(--scatter-rot))
        scale(var(--cf-scale));
    transition: transform .55s cubic-bezier(.22, .61, .36, 1), opacity .45s ease;
}

.section-4__carousel.is-dragging .section-4__card {
    /* selama drag aktif, ikuti jari/mouse tanpa delay easing supaya terasa "nempel" */
    transition: opacity .45s ease;
}

.section-4__card.is-active {
    background: #fff;
    box-shadow: 0 25px 45px rgba(31, 41, 55, .16);
}

.section-4__card-media {
    aspect-ratio: 16 / 11;
    background: #e5ddcf;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: #9a9186;
}

/* Ganti .section-4__card-media dengan <img> (object-fit: cover) begitu ada gambar programnya. */

.section-4__card-media i {
    font-size: 1.2rem;
}

.section-4__card-media span {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.68rem;
}

.section-4__card-body {
    padding: 1.1rem 1.2rem 1.3rem;
}

.section-4__card-body h3 {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 700;
    font-size: 1rem;
    color: #1F2937;
    margin: 0 0 0.35rem;
}

.section-4__card-body p {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.78rem;
    line-height: 1.55;
    color: #6b7280;
    margin: 0 0 0.9rem;
}

.section-4__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: var(--s4-red, #C8102E);
    color: #fff;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-weight: 600;
    font-size: 0.76rem;
    padding: 0.5rem 0.95rem;
    border-radius: 999px;
    text-decoration: none;
    transition: all .2s ease;
}

.section-4__btn:hover {
    background: #a30d25;
    color: #fff;
}

.section-4__card:not(.is-active) .section-4__btn {
    pointer-events: none;
    opacity: .85;
}

/* ===== Controls: prev/next + dots ===== */

.section-4__controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    margin-top: 2rem;
}

.section-4__nav-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid #e5ddcf;
    background: #fff;
    color: #1F2937;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all .2s ease;
    flex-shrink: 0;
}

.section-4__nav-btn:hover {
    background: var(--s4-red, #C8102E);
    color: #fff;
    border-color: var(--s4-red, #C8102E);
}

.section-4__dots {
    display: flex;
    align-items: center;
    gap: 8px;
}

.section-4__dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #d8cfc0;
    border: none;
    padding: 0;
    cursor: pointer;
    transition: all .2s ease;
}

.section-4__dot.is-active {
    background: var(--s4-red, #C8102E);
    transform: scale(1.3);
}

@media (max-width: 640px) {
    .section-4__carousel {
        height: 340px;
    }
    .section-4__card {
        width: 240px;
        margin-left: -120px;
    }
}
</style>

<section class="section-4" id="section-4" style="--s4-red: #C8102E;">
    <div class="section-4__inner">

        <div class="section-4__eyebrow">INA YULE &middot; MANDARIN PROGRAMS</div>

        <h2 class="section-4__title">Find the Mandarin program that fits you.</h2>

        <p class="section-4__desc">
            Whether you're preparing for HSK, studying abroad, or advancing your career,
            explore our Mandarin programs designed for every learning goal.
        </p>

        <div class="section-4__video-wrap">
            {{-- Ganti blok di bawah ini dengan <video autoplay muted loop playsinline><source src="{{ asset('image/video_ina.mp4') }}" type="video/mp4"></video>
                 begitu sudah ada file video intro-nya. --}}
            <div class="section-4__video-placeholder">
                <!-- <span>INA YULE INTRO VIDEO &middot; 16:9</span> -->
            </div>
        </div>

    </div>

    <div class="section-4__carousel" id="section4Carousel">
        <div class="section-4__track" id="section4Track">
            @foreach($mandarinPrograms as $index => $program)
                <div class="section-4__card" data-index="{{ $index }}">
                    <div class="section-4__card-media">
                        <i class="bi bi-image"></i>
                        <span>Program image</span>
                    </div>
                    <div class="section-4__card-body">
                        <h3>{{ $program['title'] }}</h3>
                        <p>{{ $program['desc'] }}</p>
                        <a href="{{ $program['href'] ?? '#' }}" class="section-4__btn">Explore Program <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="section-4__controls">
        <button type="button" class="section-4__nav-btn" id="section4Prev" aria-label="Previous program">
            <i class="bi bi-chevron-left"></i>
        </button>

        <div class="section-4__dots" id="section4Dots">
            @foreach($mandarinPrograms as $index => $program)
                <button type="button" class="section-4__dot" data-index="{{ $index }}" aria-label="Go to {{ $program['title'] }}"></button>
            @endforeach
        </div>

        <button type="button" class="section-4__nav-btn" id="section4Next" aria-label="Next program">
            <i class="bi bi-chevron-right"></i>
        </button>
    </div>
</section>

<script>
(function () {
    const section    = document.getElementById('section-4');
    const carouselEl = document.getElementById('section4Carousel');
    const cards      = Array.from(document.querySelectorAll('#section4Track .section-4__card'));
    const dots       = Array.from(document.querySelectorAll('#section4Dots .section-4__dot'));
    const prevBtn    = document.getElementById('section4Prev');
    const nextBtn    = document.getElementById('section4Next');
    const totalCards = cards.length;

    let activeIndex = {{ $activeProgramIndex }};

    // === FIX BESAR & JARAK ===
    // Jarak antar kartu & skala/opacity mengecil dibikin lebih terasa, biar
    // ada pemisahan jelas kayak referensi (bukan numpuk rapat kayak sebelumnya).
    const CARD_GAP           = 360; // jarak antar kartu (px)
    const SCALE_STEP         = 0.18;
    const OPACITY_STEP       = 0.35;
    const MAX_VISIBLE_OFFSET = 2; // kartu lebih jauh dari ini disembunyikan

    function computeCoverflow() {
        cards.forEach(function (card) {
            const index = parseInt(card.dataset.index, 10);
            let offset = index - activeIndex;

            // wraparound biar carousel-nya terasa muter terus (looping)
            if (offset > totalCards / 2) offset -= totalCards;
            if (offset < -totalCards / 2) offset += totalCards;

            const abs = Math.abs(offset);
            const scale = Math.max(0.62, 1 - abs * SCALE_STEP);
            const opacity = Math.max(0, 1 - abs * OPACITY_STEP);

            card.style.setProperty('--cf-x', (offset * CARD_GAP) + 'px');
            card.style.setProperty('--cf-scale', scale);
            card.style.opacity = abs > MAX_VISIBLE_OFFSET ? 0 : opacity;
            card.style.zIndex = 100 - abs;
            card.style.pointerEvents = abs > MAX_VISIBLE_OFFSET ? 'none' : 'auto';
            card.classList.toggle('is-active', offset === 0);
        });

        dots.forEach(function (dot) {
            const index = parseInt(dot.dataset.index, 10);
            dot.classList.toggle('is-active', index === activeIndex);
        });
    }

    function goTo(index) {
        activeIndex = ((index % totalCards) + totalCards) % totalCards;
        computeCoverflow();
    }

    prevBtn.addEventListener('click', function () { goTo(activeIndex - 1); });
    nextBtn.addEventListener('click', function () { goTo(activeIndex + 1); });

    dots.forEach(function (dot) {
        dot.addEventListener('click', function () {
            goTo(parseInt(dot.dataset.index, 10));
        });
    });

    // === DRAG (mouse & touch) ===
    // Geser kartu pakai tangan/mouse: kartu ikut mengikuti jari selama drag
    // (lewat --drag-x), lalu begitu dilepas, kalau geseran cukup jauh pindah
    // ke kartu sebelumnya/berikutnya; kalau kurang jauh, balik lagi ke posisi semula.
    let isDragging  = false;
    let dragStartX  = 0;
    let dragDeltaX  = 0;
    const DRAG_THRESHOLD = 60; // px, minimal geseran biar dianggap "pindah kartu"

    function setDragOffset(px) {
        cards.forEach(function (card) {
            card.style.setProperty('--drag-x', px + 'px');
        });
    }

    function onDragStart(clientX) {
        isDragging = true;
        dragStartX = clientX;
        dragDeltaX = 0;
        carouselEl.classList.add('is-dragging');
    }

    function onDragMove(clientX) {
        if (!isDragging) return;
        dragDeltaX = clientX - dragStartX;
        setDragOffset(dragDeltaX);
    }

    function onDragEnd() {
        if (!isDragging) return;
        isDragging = false;
        carouselEl.classList.remove('is-dragging');
        setDragOffset(0);

        if (dragDeltaX <= -DRAG_THRESHOLD) {
            goTo(activeIndex + 1); // geser ke kiri -> kartu berikutnya
        } else if (dragDeltaX >= DRAG_THRESHOLD) {
            goTo(activeIndex - 1); // geser ke kanan -> kartu sebelumnya
        } else {
            computeCoverflow(); // geseran kurang jauh, balik ke posisi semula
        }
        dragDeltaX = 0;
    }

    // Mouse
    carouselEl.addEventListener('mousedown', function (e) {
        e.preventDefault();
        onDragStart(e.clientX);
    });
    window.addEventListener('mousemove', function (e) { onDragMove(e.clientX); });
    window.addEventListener('mouseup', onDragEnd);

    // Touch
    carouselEl.addEventListener('touchstart', function (e) {
        onDragStart(e.touches[0].clientX);
    }, { passive: true });
    carouselEl.addEventListener('touchmove', function (e) {
        onDragMove(e.touches[0].clientX);
    }, { passive: true });
    carouselEl.addEventListener('touchend', onDragEnd);

    // Klik langsung ke kartu non-drag tetap bisa pindah fokus ke kartu itu
    let mouseDownX = 0;
    carouselEl.addEventListener('mousedown', function (e) { mouseDownX = e.clientX; });
    cards.forEach(function (card) {
        card.addEventListener('click', function (e) {
            // kalau ini akhir dari sebuah drag (bukan klik murni), jangan pindah kartu
            if (Math.abs(e.clientX - mouseDownX) > 8) return;
            goTo(parseInt(card.dataset.index, 10));
        });
    });

    // === ASSEMBLE EFFECT ===
    // Begitu section-4 masuk viewport, kartu yang tadinya "dipecar" (posisi
    // acak + rotasi) animasi merapikan diri ke posisi coverflow yang benar.
    function scatterCards() {
        cards.forEach(function (card) {
            const scatterX = (Math.random() - 0.5) * 900; // -450..450 px
            const scatterY = (Math.random() - 0.5) * 500 - 150; // condong ke atas
            const scatterRot = (Math.random() - 0.5) * 70; // -35..35 deg

            card.style.transition = 'none'; // set posisi awal instan, tanpa animasi
            card.style.setProperty('--scatter-x', scatterX + 'px');
            card.style.setProperty('--scatter-y', scatterY + 'px');
            card.style.setProperty('--scatter-rot', scatterRot + 'deg');
            card.style.opacity = 0;

            // paksa reflow supaya transition:none di atas kepakai dulu sebelum
            // transition dinyalakan lagi di assembleCards()
            void card.offsetWidth;
        });
    }

    function assembleCards() {
        cards.forEach(function (card, i) {
            card.style.transition = `transform .7s cubic-bezier(.22, .61, .36, 1) ${i * 0.05}s, opacity .6s ease ${i * 0.05}s`;
            card.style.setProperty('--scatter-x', '0px');
            card.style.setProperty('--scatter-y', '0px');
            card.style.setProperty('--scatter-rot', '0deg');
        });
        computeCoverflow();

        // balikin transition ke default (tanpa delay bertahap) setelah animasi awal selesai
        setTimeout(function () {
            cards.forEach(function (card) {
                card.style.transition = '';
            });
        }, 900 + totalCards * 50);
    }

    let hasAssembled = false;
    const revealObserver = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && !hasAssembled) {
                hasAssembled = true;
                assembleCards();
                revealObserver.disconnect();
            }
        });
    }, { threshold: 0.25 });

    scatterCards();
    revealObserver.observe(section);

    window.addEventListener('resize', computeCoverflow);
})();
</script>
