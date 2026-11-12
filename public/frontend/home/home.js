// Halaman publik InaStudy: header berlatar saat discroll + menu HP, dan filter kelas InaYule.
(function () {
    var header = document.getElementById('siteHeader');
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (!header || !toggle || !nav) return;

    function onScroll() {
        header.classList.toggle('is-scrolled', window.scrollY > 10);
    }

    function setOpen(open) {
        header.classList.toggle('is-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    }

    toggle.addEventListener('click', function () {
        setOpen(!header.classList.contains('is-open'));
    });
    nav.addEventListener('click', function (e) {
        if (e.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });
    window.matchMedia('(min-width: 992px)').addEventListener('change', function () {
        setOpen(false);
    });

    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();

// Filter kelas InaYule tanpa muat ulang halaman. Kartu yang cocok ditampilkan sampai batas data-limit (0 = semua).
(function () {
    document.querySelectorAll('[data-class-browser]').forEach(function (browser) {
        var form = browser.querySelector('.class-filter');
        var cards = Array.prototype.slice.call(browser.querySelectorAll('.class-card'));
        var empty = browser.querySelector('[data-class-empty]');
        var grid = browser.querySelector('.class-grid');
        var count = browser.querySelector('[data-class-count]');
        var limit = parseInt(browser.getAttribute('data-limit'), 10) || 0;
        if (!form) return;

        function apply() {
            var type = form.elements.type.value;
            var kelas = form.elements['class'].value;
            var level = form.elements.level.value;
            var q = form.elements.q.value.trim().toLowerCase();
            var shown = 0, matched = 0;

            cards.forEach(function (card) {
                var match = (!type || card.dataset.type === type)
                    && (!kelas || card.dataset['class'] === kelas)
                    && (!level || card.dataset.level === level)
                    && (!q || card.dataset.name.indexOf(q) !== -1);
                if (match) matched++;
                var visible = match && (!limit || shown < limit);
                card.hidden = !visible;
                if (visible) shown++;
            });
            if (empty) empty.hidden = shown > 0;
            if (count) count.textContent = matched;
            if (grid) grid.scrollLeft = 0; // HP: kembali ke kartu pertama setelah filter berubah
        }

        form.addEventListener('input', apply);
        form.addEventListener('change', apply);
        form.addEventListener('reset', function () { setTimeout(apply, 0); });
    });
})();

// Angka pencapaian InaYule dihitung naik saat pertama terlihat. Tanpa JS / mode kurangi animasi, angka asli tetap tampil.
(function () {
    var items = document.querySelectorAll('[data-count]');
    if (!items.length || !('IntersectionObserver' in window)) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    function run(el) {
        var to = parseFloat(el.dataset.count);
        var decimals = parseInt(el.dataset.decimals || '0', 10);
        var suffix = el.dataset.suffix || '';
        var start = null;
        function frame(t) {
            if (start === null) start = t;
            var p = Math.min((t - start) / 1400, 1);
            var value = to * (1 - Math.pow(1 - p, 3));
            el.innerHTML = value.toFixed(decimals).replace('.', ',') + suffix;
            if (p < 1) requestAnimationFrame(frame);
        }
        requestAnimationFrame(frame);
    }

    var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (!entry.isIntersecting) return;
            observer.unobserve(entry.target);
            run(entry.target);
        });
    }, { threshold: 0.6 });
    items.forEach(function (el) { observer.observe(el); });
})();
