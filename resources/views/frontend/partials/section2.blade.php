<style>
.section-2 {
    font-family: "Bodoni 72", "Bodoni 72 Smallcaps", serif;
    position: relative;
    padding: 6rem 1.5rem;
    color: #1F2937;
    text-align: center;
    overflow: hidden;
}

.section-2__bg {
    position: absolute;
    inset: 0;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    transform: scale(1.45);
    transition: transform 1.8s cubic-bezier(0.25, 0.8, 0.25, 1);
    z-index: 0;
}

.section-2__bg.is-visible {
    transform: scale(1);
}

.section-2__inner {
    position: relative;
    z-index: 1;
    max-width: 1100px;
    margin: 0 auto;
}

.section-2__main-stat {
    margin-bottom: 4rem;
    opacity: 0;
    transform: translateY(40px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.section-2__main-stat.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.section-2__number,
.section-2__plus {
    font-family: "Bodoni 72", "Bodoni 72 Smallcaps", serif;
    font-weight: 600;
    font-size: clamp(3.5rem, 9vw, 7rem);
    line-height: 1;
    color: var(--brand-red);
}

.section-2__desc {
    font-family: "Bodoni 72", "Bodoni 72 Smallcaps", serif;
    font-weight: 600;
    font-size: clamp(1.1rem, 2.5vw, 1.5rem);
    margin-top: 1rem;
    /* text-shadow: 0 2px 8px rgba(0, 0, 0, 0.55), 0 4px 20px rgba(0, 0, 0, 0.35); */
}

.section-2__stats-row {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 2.5rem;
}

.section-2__stat-item {
    flex: 1 1 180px;
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.section-2__stat-item.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.section-2__stat-item:nth-child(1) { transition-delay: 0.1s; }
.section-2__stat-item:nth-child(2) { transition-delay: 0.25s; }
.section-2__stat-item:nth-child(3) { transition-delay: 0.4s; }
.section-2__stat-item:nth-child(4) { transition-delay: 0.55s; }

.section-2__stat-number,
.section-2__stat-suffix,
.section-2__stat-static {
    font-family: 'Poppins', sans-serif;
    font-weight: 200;
    font-size: clamp(2rem, 4vw, 3rem);
    display: inline-block;
}

.section-2__stat-item p {
    font-weight: 300;
    font-size: 0.95rem;
    margin-top: 0.5rem;
    opacity: 0.85;
}

.section-2__number,
.section-2__plus,
.section-2__stat-number,
.section-2__stat-suffix,
.section-2__stat-static {
    /* text-shadow: 0 2px 8px rgba(0, 0, 0, 0.55), 0 4px 20px rgba(0, 0, 0, 0.35); */
    transition: transform 0.2s ease-out;
}

.section-2__desc,
.section-2__stat-item p {
    transition: transform 0.2s ease-out;
}
</style>
<script>
(function () {
    function initSection2() {
        const section2 = document.getElementById('section-2');
        if (!section2 || section2.dataset.effectsInit) return;
        section2.dataset.effectsInit = 'true';

        const bg = section2.querySelector('.section-2__bg');
        const animatedEls = section2.querySelectorAll('.section-2__main-stat, .section-2__stat-item');
        const counters = section2.querySelectorAll('[data-count]');

        // --- Fade in/out setiap kali masuk-keluar viewport ---
        const contentObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                entry.target.classList.toggle('is-visible', entry.isIntersecting);
            });
        }, { threshold: 0.3 });

        animatedEls.forEach(el => contentObserver.observe(el));

        // --- Counter, replay tiap kali masuk viewport ---
        const counterState = new Map();
        counters.forEach(el => counterState.set(el, { rafId: null }));

        function animateCounter(el) {
            const target = parseInt(el.dataset.count, 10);
            const duration = 1500;
            const start = performance.now();
            const state = counterState.get(el);

            if (state.rafId) cancelAnimationFrame(state.rafId);

            function step(now) {
                const progress = Math.min((now - start) / duration, 1);
                el.textContent = Math.floor(progress * target);
                if (progress < 1) {
                    state.rafId = requestAnimationFrame(step);
                } else {
                    el.textContent = target;
                    state.rafId = null;
                }
            }
            state.rafId = requestAnimationFrame(step);
        }

        const counterObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                } else {
                    entry.target.textContent = '0'; // reset biar keitung ulang lagi pas balik masuk
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(el => counterObserver.observe(el));

        // --- Zoom background, replay tiap kali masuk-keluar ---
        const bgObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (bg) bg.classList.toggle('is-visible', entry.isIntersecting);
            });
        }, { threshold: 0.1 });

        bgObserver.observe(section2);

        // --- Parallax mouse: angka + teks label ikut gerak ---
        const bigTargets = section2.querySelectorAll('.section-2__number, .section-2__plus, .section-2__desc');
        const smallTargets = section2.querySelectorAll('.section-2__stat-number, .section-2__stat-suffix, .section-2__stat-static, .section-2__stat-item p');

        section2.addEventListener('mousemove', (e) => {
            const rect = section2.getBoundingClientRect();
            const x = (e.clientX - rect.left) / rect.width - 0.5;
            const y = (e.clientY - rect.top) / rect.height - 0.5;

            bigTargets.forEach(el => {
                el.style.transform = `translate(${x * 45}px, ${y * 10}px)`;
            });
            smallTargets.forEach(el => {
                el.style.transform = `translate(${x * 30}px, ${y * 8}px)`;
            });
        });

        section2.addEventListener('mouseleave', () => {
            [...bigTargets, ...smallTargets].forEach(el => {
                el.style.transform = 'translate(0, 0)';
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSection2);
    } else {
        initSection2();
    }
})();
</script>

<section class="color-section section-2" id="section-2">
    <div class="section-2__bg" style="background-image: url('{{ asset('image/stats-bg.png') }}');"></div>

    <div class="section-2__inner">
        <div class="section-2__main-stat">
            <span class="section-2__number" data-count="3000">0</span><span class="section-2__plus">+</span>
            <p class="section-2__desc">Indonesia student<br>are already studying in China</p>
        </div>

        <div class="section-2__stats-row">
            <div class="section-2__stat-item">
                <span class="section-2__stat-number" data-count="100">0</span><span class="section-2__stat-suffix">+</span>
                <p>partners</p>
            </div>
            <div class="section-2__stat-item">
                <span class="section-2__stat-number" data-count="10">0</span><span class="section-2__stat-suffix">+</span>
                <p>years of experience</p>
            </div>
            <div class="section-2__stat-item">
                <span class="section-2__stat-number" data-count="98">0</span><span class="section-2__stat-suffix">%</span>
                <p>visa success rate</p>
            </div>
            <div class="section-2__stat-item">
                <span class="section-2__stat-static">24/7</span>
                <p>student support</p>
            </div>
        </div>
    </div>
</section>