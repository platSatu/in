<style>
.section-5-pin-wrapper {
    position: relative;
    height: 500vh; /* scroll distance = controls "slowness" of the animation */
    background: #000;
}

.section-5-sticky {
    position: sticky;
    top: 0;
    height: 100vh;
    overflow: hidden;
    color: #fff;
}

.section-5__canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    display: block;
}

.section-5__loader {
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
.section-5__loader.is-hidden {
    opacity: 0;
    pointer-events: none;
}

.section-5__content {
    position: absolute;
    inset: 0;
    z-index: 2;
    max-width: 780px;
    margin: 0 auto;
    /* === FIX === padding & gap dikecilin lagi supaya seluruh blok teks
       (eyebrow + judul + subtitle) muat di atas panel roadmap, tidak
       nimpa gambar */
    padding: clamp(0.5rem, 2vh, 1.5rem) 1.5rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    text-align: center;
    gap: 0.45rem;
    /* === FIX === teks sekarang selalu terlihat, tidak lagi fade/geser saat scroll */
    opacity: 1;
    pointer-events: auto;
}

.section-5__eyebrow {
    font-family: "Bodoni 72", "Bodoni 72 Smallcaps", serif;
    font-size: clamp(0.8rem, 1.5vw, 0.95rem);
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--brand-red);
    font-weight: 700;
    text-shadow: 0 2px 10px rgba(0,0,0,0.4);
    margin: 0;
}

.section-5__title {
    font-family: 'Cormorant Garamond', serif;
    /* === FIX === ukuran dikecilin sedikit biar seluruh blok teks lebih ringkas ke atas */
    font-size: clamp(1.75rem, 4.5vw, 3.15rem);
    font-weight: 700;
    line-height: 1.15;
    margin: 0;
    /* === FIX === teks jadi hitam/gelap (bukan putih lagi), text-shadow
       dibalik jadi halo terang tipis supaya tetap kebaca di atas foto gelap */
    color: #1F2937;
    text-shadow: 0 2px 14px rgba(255,255,255,0.55);
}

.section-5__subtitle {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: clamp(0.95rem, 1.6vw, 1.1rem);
    font-weight: 500;
    line-height: 1.6;
    color: #4b5563;
    text-shadow: 0 2px 14px rgba(255,255,255,0.6);
    max-width: 34rem;
    margin: 0;
}

@media (max-width: 576px) {
    .section-5-pin-wrapper {
        height: 350vh;
    }
}
</style>

<section class="section-5-pin-wrapper" id="section-5">

    <div class="section-5-sticky">

        <canvas class="section-5__canvas" id="section5Canvas"></canvas>

        <div class="section-5__loader" id="section5Loader">Inastudy</div>

        <div class="section-5__content" id="section5Content">
            <p class="section-5__eyebrow">YOUR JOURNEY, OUR GUIDANCE</p>
            <h1 class="section-5__title" id="section5Title">From a dream today,<br>to a diploma in hand.</h1>
            <p class="section-5__subtitle">We're with you at every step &mdash; from choosing your university to building your future in China.</p>
        </div>

    </div>

</section>

<script>
(function () {
    const basePath    = "{{ asset('image/ina5') }}";
    const totalFrames = 300;
    const startFrame  = 0;
    const FRAME_STEP  = 6; // ambil 1 dari tiap 6 file -> lebih ringan buat di-load

    const wrapper    = document.getElementById('section-5');
    const canvas     = document.getElementById('section5Canvas');
    const ctx        = canvas.getContext('2d');
    const loaderEl   = document.getElementById('section5Loader');

    function frameSrc(i) {
        const padded = String(i).padStart(5, '0');
        return `${basePath}/${encodeURIComponent('Comp 5_' + padded + '.jpg')}`;
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

        // === FIX ===
        // Progress bar dan fade-out teks (contentEl.style.opacity/transform)
        // yang sebelumnya ada di sini sudah dihapus. Teks sekarang diam di
        // tempat & selalu terlihat, cuma gambar di canvas yang berubah
        // mengikuti scroll.
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

        const cw = canvas.width;
        const ch = canvas.height;
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
    }
    function stopLoop() {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    }

    const observer = new IntersectionObserver(function (entries) {
        if (entries[0].isIntersecting) startLoop();
        else stopLoop();
    });
    observer.observe(wrapper);

    function resizeCanvas() {
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
        renderFrame();
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

    window.addEventListener('resize', resizeCanvas);

    resizeCanvas();
    preloadFrames();
    startLoop();
})();
</script>