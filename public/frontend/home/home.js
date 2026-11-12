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
