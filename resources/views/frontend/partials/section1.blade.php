<style>
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

:root {
    --brand-red: #E4032B;
}

/* ===== Section 1: Scroll-scrubbed exploded view ===== */

.section-1-pin-wrapper {
    position: relative;
    height: 500vh; /* scroll distance = controls "slowness" of the explode animation */
    background: #000;
}

.section-1-sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    /* === FIX RESPONSIVE ===
       100vh di banyak browser mobile dihitung dari tinggi layar TERMASUK area
       yang ditutupi address bar, jadi begitu address bar collapse/expand saat
       discroll, tinggi efektifnya "meloncat". 100dvh mengikuti tinggi viewport
       yang BENERAN kelihatan saat itu. Browser yang belum support dvh otomatis
       pakai fallback 100vh di atas (declaration kedua menang kalau didukung). */
    height: 100dvh;
    overflow: hidden;
    color: #fff;
}

.section-1__canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
    z-index: 0;
}

/* === SAKURA === Efek kelopak jatuh (canvas, bukan video), jalan terus,
   TIDAK terikat scroll, dan dibatasi cuma di dalam section-1-sticky ini
   (absolute relatif ke section, bukan fixed ke viewport). */
.section-1__petals {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
    pointer-events: none;
}

.section-1__loader {
    position: absolute;
    inset: 0;
    z-index: 3;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
    color: rgba(255,255,255,0.7);
    font-size: 0.85rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    transition: opacity .4s ease;
}
.section-1__loader.is-hidden {
    opacity: 0;
    pointer-events: none;
}

.section-1__content {
    position: absolute;
    inset: 0;
    z-index: 2;
    max-width: 720px;
    padding: 1.5rem 1.5rem 1.5rem clamp(1.5rem, 6vw, 6rem);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 1rem;
    /* teks selalu terlihat, tidak fade saat scroll */
    opacity: 1;
    pointer-events: auto;
}

.section-1__eyebrow {
    display: flex;
    align-items: center;
    gap: 12px;
    /* === FIX FONT ===
       Sebelumnya ga ada font-family sendiri (ikut default browser), jadi
       ga match sama gambar referensi yang pakai sans-serif tegas + bold +
       letter-spacing lebar. "Hanken Grotesk" sudah di-load di <head>,
       tinggal dipakai di sini. */
    font-family: "Hanken Grotesk", "Segoe UI", sans-serif;
    font-weight: 700;
    font-size: 0.9rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #C8102E;
}

.section-1__eyebrow::before {
    content: "";
    width: 40px;
    height: 3px;
    background: #C8102E;
    display: block;
}

.section-1__title {
    /* Sudah paling mendekati gambar (serif elegan dengan kontras
       tebal-tipis khas headline) -> dipertahankan, tidak diubah. */
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-size: clamp(2.9rem, 8vw, 6rem);
    font-weight: normal;
    line-height: 1.15;
    margin: 0;
    color:#1F2937;
    text-shadow: 0 2px 12px rgba(0,0,0,0.4);
}

.section-1__title .text-red {
    color: var(--brand-red);
}

.section-1__desc {
    /* === FIX FONT ===
       Sebelumnya "Bodoni 72" (serif) -> di gambar referensi paragraf
       deskripsinya jelas sans-serif yang bersih/modern, bukan serif.
       Diganti ke "Hanken Grotesk" biar sesuai. */
    font-family: "Hanken Grotesk", "Segoe UI", sans-serif;
    font-size: clamp(1rem, 2vw, 1.15rem);
    max-width: 34rem;
    margin: 0;
    color:#1F2937;
    text-shadow: 0 2px 20px rgba(0,0,0,0.9);
}

.section-1__buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 1rem;
    margin-top: 0.75rem;
}

.btn-primary-cta {
    /* === FIX FONT === samain sama .section-1__desc, sans-serif "Hanken Grotesk" */
    font-family: "Hanken Grotesk", "Segoe UI", sans-serif;
    background: var(--brand-red);
    color: #fff;
    border: 2px solid var(--brand-red);
    padding: 0.75rem 1.75rem;
    border-radius: 999px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.95rem;
    transition: background .25s ease, color .25s ease;
}

.btn-primary-cta:hover {
    background: transparent;
    color: var(--brand-red);
}

.btn-outline-cta {
    /* === FIX FONT === samain sama .btn-primary-cta */
    font-family: "Hanken Grotesk", "Segoe UI", sans-serif;
    background: transparent;
    color: #fff;
    border: 2px solid rgba(255,255,255,0.7);
    padding: 0.75rem 1.75rem;
    border-radius: 999px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.95rem;
    transition: background .25s ease, border-color .25s ease;
}

.btn-outline-cta:hover {
    background: rgba(255,255,255,0.15);
    border-color: #fff;
}

@media (max-width: 576px) {
    .section-1__buttons {
        flex-direction: column;
        width: 100%;
    }
    .btn-primary-cta,
    .btn-outline-cta {
        width: 100%;
    }
    .section-1-pin-wrapper {
        height: 350vh;
    }
}
</style>

<section class="section-1-pin-wrapper" id="section-1">

    <div class="section-1-sticky">

        <canvas class="section-1__canvas" id="section1Canvas"></canvas>

        <!-- === SAKURA === kelopak jatuh, cuma tampil selama section ini di layar -->
        <canvas class="section-1__petals" id="section1Petals"></canvas>

        <div class="section-1__loader" id="section1Loader">Loading experience… 0%</div>

        <div class="section-1__content" id="section1Content">
            <span class="section-1__eyebrow">Study in China</span>
            <h1 class="section-1__title">Your Future,<br>Starts in <span class="text-red">China</span></h1>
            <p class="section-1__desc">Discover world-class universities, scholarships and a future full of endless possibilities</p>

            <div class="section-1__buttons">
                <a href="#" class="btn-primary-cta">Find your perfect University</a>
                <a href="#" class="btn-primary-cta">Explore China</a>
            </div>
        </div>

    </div>

</section>

<script>
(function () {
    const basePath    = "{{ asset('image/ina6') }}";
    const totalFrames = 300;
    const startFrame  = 0;
    const FRAME_STEP  = 6; // ambil 1 dari tiap 6 file -> ~50 gambar unik saja yang dimuat

    const wrapper       = document.getElementById('section-1');
    const canvas        = document.getElementById('section1Canvas');
    const ctx           = canvas.getContext('2d');
    const loaderEl      = document.getElementById('section1Loader');
    const petalsCanvas  = document.getElementById('section1Petals');
    const petalsCtx     = petalsCanvas ? petalsCanvas.getContext('2d') : null;

    // === FIX RESPONSIVE ===
    // Dibatasi max 2 supaya HP dengan devicePixelRatio tinggi (3, bahkan 4)
    // tidak bikin canvas raksasa yang berat di-render, tapi tetap tajam.
    const DPR = Math.min(window.devicePixelRatio || 1, 2);

    // Ukuran viewport dalam CSS pixel (bukan physical pixel). Semua logic
    // posisi/skala pakai ini, BUKAN canvas.width/canvas.height langsung,
    // supaya konsisten walau backing store canvas dikalikan DPR.
    let viewW = window.innerWidth;
    let viewH = window.innerHeight;

    function frameSrc(i) {
        const padded = String(i).padStart(5, '0');
        return `${basePath}/${encodeURIComponent('Comp 6_' + padded + '.jpg')}`;
    }

    const images    = new Array(totalFrames + 1);
    const requested = new Array(totalFrames + 1).fill(false);
    let loadedCount = 0;
    let firstFrameReady = false;

    const CONCURRENCY = 6;
    let activeLoads = 0;
    const queue = [];

    function processQueue() {
        while (activeLoads < CONCURRENCY && queue.length) {
            const i = queue.shift();
            if (requested[i]) continue;
            requested[i] = true;
            activeLoads++;

            const img = new Image();
            img.onload = img.onerror = function () {
                activeLoads--;
                loadedCount++;
                const pct = Math.round((loadedCount / (Math.floor(totalFrames / FRAME_STEP) + 1)) * 100);
                if (loaderEl) loaderEl.textContent = `Loading experience… ${pct}%`;

                if (i === startFrame && !firstFrameReady) {
                    firstFrameReady = true;
                    if (loaderEl) loaderEl.classList.add('is-hidden');
                }
                processQueue();
            };
            img.src = frameSrc(i);
            images[i] = img;
        }
    }

    function requestFrame(i, priority) {
        if (i < startFrame || i > totalFrames || requested[i]) return;
        if (priority) queue.unshift(i); else queue.push(i);
        processQueue();
    }

    // === SAKURA === kelopak jatuh, dibatasi cuma di dalam section ini
    const PETAL_COUNT = 30;
    let petals = [];
    let petalsRafId = null;

    function randomBetween(min, max) {
        return Math.random() * (max - min) + min;
    }

    function createPetal(isInitial) {
        return {
            x: randomBetween(0, viewW),
            y: isInitial ? randomBetween(-viewH, viewH) : randomBetween(-60, -10),
            size: randomBetween(9, 18),
            fallSpeed: randomBetween(0.5, 1.6),
            swayAmp: randomBetween(20, 60),
            swayFreq: randomBetween(0.4, 1.2),
            swayPhase: randomBetween(0, Math.PI * 2),
            baseX: 0,
            rotation: randomBetween(0, Math.PI * 2),
            rotationSpeed: randomBetween(-0.02, 0.02),
            opacity: randomBetween(0.6, 0.95),
            hue: randomBetween(335, 350), // rentang pink sakura
        };
    }

    function initPetals() {
        if (!petalsCanvas) return;
        petals = [];
        for (let i = 0; i < PETAL_COUNT; i++) {
            const p = createPetal(true);
            p.baseX = p.x;
            petals.push(p);
        }
    }

    // === FIX BENTUK ===
    // Sebelumnya dua kurva bezier sama-sama menonjol ke arah pangkal (0,0),
    // hasilnya dua "bukit" di ujung atas -> kebaca sebagai bentuk hati.
    // Sekarang: bentuk oval memanjang dengan pangkal lancip di (0,0) dan
    // sedikit lekukan/notch kecil di ujung -> lebih mirip kelopak sakura asli.
    function drawPetal(p, time) {
        const swayX = Math.sin(time * 0.001 * p.swayFreq + p.swayPhase) * p.swayAmp;
        const s = p.size;

        petalsCtx.save();
        petalsCtx.translate(p.baseX + swayX, p.y);
        petalsCtx.rotate(p.rotation);
        petalsCtx.globalAlpha = p.opacity;

        const gradient = petalsCtx.createLinearGradient(0, 0, 0, s);
        gradient.addColorStop(0, `hsl(${p.hue}, 70%, 92%)`);
        gradient.addColorStop(1, `hsl(${p.hue}, 80%, 78%)`);
        petalsCtx.fillStyle = gradient;

        petalsCtx.beginPath();
        petalsCtx.moveTo(0, 0); // pangkal petal (lancip)
        // sisi kanan melebar lalu menyempit menuju ujung
        petalsCtx.bezierCurveTo(s * 0.55, s * 0.05, s * 0.62, s * 0.55, s * 0.16, s * 0.85);
        // lekukan kecil di ujung (ciri khas kelopak sakura)
        petalsCtx.quadraticCurveTo(s * 0.05, s * 0.95, 0, s * 0.78);
        petalsCtx.quadraticCurveTo(-s * 0.05, s * 0.95, -s * 0.16, s * 0.85);
        // sisi kiri kembali ke pangkal
        petalsCtx.bezierCurveTo(-s * 0.62, s * 0.55, -s * 0.55, s * 0.05, 0, 0);
        petalsCtx.closePath();
        petalsCtx.fill();
        petalsCtx.restore();
    }

    function animatePetals(time) {
        if (!petalsCanvas) return;
        petalsCtx.clearRect(0, 0, viewW, viewH);

        petals.forEach(function (p) {
            p.y += p.fallSpeed;
            p.rotation += p.rotationSpeed;

            drawPetal(p, time);

            if (p.y - p.size > viewH) {
                const fresh = createPetal(false);
                Object.assign(p, fresh);
                p.baseX = fresh.x;
            }
        });

        petalsRafId = requestAnimationFrame(animatePetals);
    }

    function startPetals() {
        if (!petalsCanvas || petalsRafId !== null) return;
        petalsRafId = requestAnimationFrame(animatePetals);
    }

    function stopPetals() {
        if (petalsRafId !== null) {
            cancelAnimationFrame(petalsRafId);
            petalsRafId = null;
        }
    }

    function snapToStep(i) {
        const snapped = Math.round(i / FRAME_STEP) * FRAME_STEP;
        return Math.min(totalFrames, Math.max(startFrame, snapped));
    }

    let targetFrame = startFrame;

    function computeTargetFrame() {
        const rect = wrapper.getBoundingClientRect();
        const wrapperHeight = wrapper.offsetHeight;
        const viewportHeight = window.innerHeight;
        const scrollableDistance = wrapperHeight - viewportHeight;

        let progress = -rect.top / scrollableDistance;
        progress = Math.min(1, Math.max(0, progress));

        const rawFrame = Math.round(startFrame + progress * (totalFrames - startFrame));
        targetFrame = snapToStep(rawFrame);
    }

    function renderFrame() {
        if (!requested[targetFrame]) requestFrame(targetFrame, true);

        let img = images[targetFrame];
        if (!img || !img.complete || !img.naturalWidth) {
            let found = null;
            for (let d = FRAME_STEP; d < totalFrames; d += FRAME_STEP) {
                const before = targetFrame - d;
                const after  = targetFrame + d;
                if (before >= startFrame && images[before] && images[before].complete && images[before].naturalWidth) {
                    found = images[before];
                    break;
                }
                if (after <= totalFrames && images[after] && images[after].complete && images[after].naturalWidth) {
                    found = images[after];
                    break;
                }
            }
            img = found;
        }
        if (!img) return;

        // === FIX RESPONSIVE ===
        // Pakai viewW/viewH (CSS pixel), BUKAN canvas.width/canvas.height,
        // karena ctx sudah di-scale oleh DPR lewat setTransform di resizeCanvas().
        const cw = viewW;
        const ch = viewH;
        const iw = img.naturalWidth;
        const ih = img.naturalHeight;

        const scale = Math.max(cw / iw, ch / ih);
        const dw = iw * scale;
        const dh = ih * scale;
        const dx = (cw - dw) / 2;
        const dy = (ch - dh) / 2;

        ctx.clearRect(0, 0, cw, ch);
        ctx.drawImage(img, dx, dy, dw, dh);
    }

    let rafId = null;
    function tick() {
        computeTargetFrame();
        renderFrame();
        rafId = requestAnimationFrame(tick);
    }

    function startLoop() {
        if (rafId === null) rafId = requestAnimationFrame(tick);
        startPetals();
    }
    function stopLoop() {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
        stopPetals();
    }

    const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) startLoop();
        else stopLoop();
    });
    observer.observe(wrapper);

    // === FIX RESPONSIVE ===
    // - Ukuran backing store canvas dikalikan DPR biar tajam di layar HiDPI/retina.
    // - ctx.setTransform(DPR,...) supaya semua koordinat gambar tetap dihitung
    //   dalam satuan CSS pixel (viewW/viewH), tidak perlu ubah logic lain.
    function resizeCanvas() {
        viewW = window.innerWidth;
        viewH = window.innerHeight;

        canvas.width  = Math.round(viewW * DPR);
        canvas.height = Math.round(viewH * DPR);
        canvas.style.width  = viewW + 'px';
        canvas.style.height = viewH + 'px';
        ctx.setTransform(DPR, 0, 0, DPR, 0, 0);

        renderFrame();

        if (petalsCanvas) {
            petalsCanvas.width  = Math.round(viewW * DPR);
            petalsCanvas.height = Math.round(viewH * DPR);
            petalsCanvas.style.width  = viewW + 'px';
            petalsCanvas.style.height = viewH + 'px';
            petalsCtx.setTransform(DPR, 0, 0, DPR, 0, 0);
            initPetals();
        }
    }

    function preloadFrames() {
        requestFrame(startFrame, true);

        const idle = window.requestIdleCallback || function (cb) { return setTimeout(cb, 200); };
        let i = startFrame + FRAME_STEP;
        function fillBackground() {
            let count = 0;
            while (i <= totalFrames && count < 10) {
                requestFrame(i, false);
                i += FRAME_STEP;
                count++;
            }
            if (i <= totalFrames) idle(fillBackground);
        }
        idle(fillBackground);
    }

    // === FIX RESPONSIVE ===
    // Debounce resize (mencegah resizeCanvas dipanggil puluhan kali saat drag
    // window/orientation berubah) + dengarkan orientationchange juga, karena
    // sebagian browser mobile lama tidak selalu memicu 'resize' saat rotasi.
    let resizeTimer = null;
    function onViewportChange() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(resizeCanvas, 120);
    }
    window.addEventListener('resize', onViewportChange);
    window.addEventListener('orientationchange', onViewportChange);

    resizeCanvas();
    preloadFrames();
    startLoop();
})();
</script>




<!-- <script>
(function () {
    const basePath   = "{{ asset('image/ina2') }}";
    const totalFrames = 2249;
    const startFrame  = 0;

    const wrapper   = document.getElementById('section-1');
    const canvas    = document.getElementById('section1Canvas');
    const ctx       = canvas.getContext('2d');
    const loaderEl  = document.getElementById('section1Loader');
    const contentEl = document.getElementById('section1Content');
    const progressEl= document.getElementById('section1Progress');

    // function frameSrc(i) {
    //     const padded = String(i).padStart(3, '0');
    //     return `${basePath}/ezgif-frame-${padded}.jpg`;
    // }
    function frameSrc(i) {
    const padded = String(i).padStart(5, '0');   // dari 3 jadi 5 digit
    return `${basePath}/${encodeURIComponent('Comp 1_' + padded + '.jpg')}`;
    }

    const images = new Array(totalFrames + 1); // 1-indexed
    let loadedCount = 0;
    let firstFrameReady = false;
    let currentDrawnFrame = -1;

    function resizeCanvas() {
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
        drawFrame(currentDrawnFrame > 0 ? currentDrawnFrame : startFrame, true);
    }

    function drawFrame(frameIndex, force) {
        frameIndex = Math.min(totalFrames, Math.max(startFrame, frameIndex));
        if (!force && frameIndex === currentDrawnFrame) return;

        // fall back to nearest already-loaded frame if this one isn't ready yet
        let img = images[frameIndex];
        if (!img || !img.complete || !img.naturalWidth) {
            let fallback = frameIndex;
            let found = null;
            for (let d = 1; d < totalFrames; d++) {
                const before = frameIndex - d;
                const after  = frameIndex + d;
                if (before >= startFrame && images[before] && images[before].complete && images[before].naturalWidth) {
                    found = images[before];
                    break;
                }
                if (after <= totalFrames && images[after] && images[after].complete && images[after].naturalWidth) {
                    found = images[after];
                    break;
                }
            }
            img = found;
        }
        if (!img) return;

        currentDrawnFrame = frameIndex;

        const cw = canvas.width;
        const ch = canvas.height;
        const iw = img.naturalWidth;
        const ih = img.naturalHeight;

        // cover-fit draw (like object-fit: cover)
        const scale = Math.max(cw / iw, ch / ih);
        const dw = iw * scale;
        const dh = ih * scale;
        const dx = (cw - dw) / 2;
        const dy = (ch - dh) / 2;

        ctx.clearRect(0, 0, cw, ch);
        ctx.drawImage(img, dx, dy, dw, dh);
    }

    function updateOnScroll() {
        const rect = wrapper.getBoundingClientRect();
        const wrapperHeight = wrapper.offsetHeight;
        const viewportHeight = window.innerHeight;
        const scrollableDistance = wrapperHeight - viewportHeight;

        let progress = -rect.top / scrollableDistance;
        progress = Math.min(1, Math.max(0, progress));

        const frame = Math.round(startFrame + progress * (totalFrames - startFrame));
        drawFrame(frame, false);

        if (progressEl) progressEl.style.width = (progress * 100).toFixed(1) + '%';

        // fade the intro copy out over the first 12% of the scrub
        const fadeEnd = 0.12;
        const contentOpacity = progress <= 0 ? 1 : Math.max(0, 1 - (progress / fadeEnd));
        contentEl.style.opacity = contentOpacity;
        contentEl.style.transform = `translateY(${(1 - contentOpacity) * -30}px)`;
        contentEl.style.pointerEvents = contentOpacity < 0.05 ? 'none' : 'auto';
    }

    let ticking = false;
    function onScroll() {
        if (!ticking) {
            window.requestAnimationFrame(function () {
                updateOnScroll();
                ticking = false;
            });
            ticking = true;
        }
    } -->

    <!-- // function preloadFrames() {
    //     for (let i = startFrame; i <= totalFrames; i++) {
    //         const img = new Image();
    //         img.onload = img.onerror = function () {
    //             loadedCount++;
    //             const pct = Math.round((loadedCount / totalFrames) * 100);
    //             if (loaderEl) loaderEl.textContent = `Loading experience… ${pct}%`;

    //             if (i === startFrame && !firstFrameReady) {
    //                 firstFrameReady = true;
    //                 drawFrame(startFrame, true);
    //                 if (loaderEl) loaderEl.classList.add('is-hidden');
    //             }
    //             if (loadedCount === totalFrames && loaderEl) {
    //                 loaderEl.classList.add('is-hidden');
    //             }
    //         };
    //         img.src = frameSrc(i);
    //         images[i] = img;
    //     }
    // } -->
<!-- //     function preloadFrames() {
//     const CONCURRENCY = 6; // jumlah request paralel, sesuaikan (4-10) tergantung server
//     let nextIndex = startFrame;

//     function loadNext() {
//         if (nextIndex > totalFrames) return;
//         const i = nextIndex++;

//         const img = new Image();
//         img.onload = img.onerror = function () {
//             loadedCount++;
//             const pct = Math.round((loadedCount / totalFrames) * 100);
//             if (loaderEl) loaderEl.textContent = `Loading experience… ${pct}%`;

//             if (i === startFrame && !firstFrameReady) {
//                 firstFrameReady = true;
//                 drawFrame(startFrame, true);
//                 if (loaderEl) loaderEl.classList.add('is-hidden');
//             }
//             if (loadedCount === totalFrames && loaderEl) {
//                 loaderEl.classList.add('is-hidden');
//             }

//             loadNext(); // slot ini kosong, langsung isi dengan frame berikutnya
//         };
//         img.src = frameSrc(i);
//         images[i] = img;
//     }

//     // buka N "jalur" paralel sesuai CONCURRENCY, masing-masing jalan berurutan
//     for (let w = 0; w < CONCURRENCY; w++) {
//         loadNext();
//     }
// }

//     window.addEventListener('resize', resizeCanvas);
//     window.addEventListener('scroll', onScroll, { passive: true });

//     resizeCanvas();
//     preloadFrames();
//     updateOnScroll();
// })();
// </script> -->

<!-- <section class="color-section section-1" id="section-1">

    <video class="section-1__video" src="{{ asset('video/ina2.mp4') }}" autoplay muted loop playsinline></video>

    <div class="section-1__content">
        <span class="section-1__eyebrow">Study in China</span>
        <h1 class="section-1__title">Your Future,<br>Starts in <span class="text-red">China</span></h1>
        <p class="section-1__desc">Discover world-class universities, scholarships and a future full of endless possibilities</p>

        <div class="section-1__buttons">
            <a href="#" class="btn-primary-cta">Find your perfect University</a>
            <a href="#" class="btn-outline-cta">Explore China</a>
        </div>
    </div>

</section> -->