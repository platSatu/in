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

// Tombol WhatsApp: tetap di pojok kanan bawah, naik sebanyak bagian footer yang terlihat supaya ikon sosmed tidak tertutup.
(function () {
    var wa = document.querySelector('.wa-float');
    var footer = document.querySelector('.site-footer');
    if (!wa || !footer) return;

    function place() {
        var overlap = window.innerHeight - footer.getBoundingClientRect().top;
        wa.style.marginBottom = overlap > 0 ? overlap + 'px' : '';
    }
    window.addEventListener('scroll', place, { passive: true });
    window.addEventListener('resize', place);
    place();
})();

// Alur perjalanan di hero: garis lengkung digambar melewati tengah tiap ikon langkah, dihitung ulang saat ukuran berubah.
(function () {
    var journey = document.querySelector('[data-journey]');
    if (!journey) return;
    var track = journey.querySelector('.journey__track');
    var svg = journey.querySelector('.journey__route');
    var paths = journey.querySelectorAll('.journey__line, .journey__flow');
    var icons = journey.querySelectorAll('.journey__icon');

    function draw() {
        var box = track.getBoundingClientRect();
        var pts = Array.prototype.map.call(icons, function (icon) {
            var r = icon.getBoundingClientRect();
            return [r.left - box.left + r.width / 2, r.top - box.top + r.height / 2];
        });
        if (pts.length < 2) return;
        var d = 'M' + pts[0][0] + ' ' + pts[0][1];
        for (var i = 1; i < pts.length; i++) {
            var a = pts[i - 1], b = pts[i], mid = (b[1] - a[1]) / 2;
            d += ' C' + a[0] + ' ' + (a[1] + mid) + ' ' + b[0] + ' ' + (b[1] - mid) + ' ' + b[0] + ' ' + b[1];
        }
        svg.setAttribute('viewBox', '0 0 ' + box.width + ' ' + box.height);
        paths.forEach(function (p) { p.setAttribute('d', d); });
        journey.classList.add('is-ready');
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches && svg.pauseAnimations) svg.pauseAnimations();
    if ('ResizeObserver' in window) new ResizeObserver(draw).observe(track);
    else window.addEventListener('resize', draw);
    window.addEventListener('load', draw);
    draw();
})();

// Kampus partner di hero bergiliran ke atas tiap 2,6 detik; item pertama disalin ke akhir supaya putarannya mulus.
(function () {
    var ticker = document.querySelector('[data-ticker]');
    if (!ticker || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var list = ticker.querySelector('.uni-ticker__list');
    var items = list.children;
    if (items.length < 2) return;

    var win = ticker.querySelector('.uni-ticker__window');
    list.appendChild(items[0].cloneNode(true));
    var index = 0;

    // Lebar jendela mengikuti nama yang sedang tampil, jadi kapsulnya tidak menyisakan ruang kosong.
    function fit() {
        var item = items[index];
        var logo = item.firstElementChild, name = item.lastElementChild;
        win.style.width = Math.ceil(logo.offsetWidth + 8 + name.scrollWidth + 4) + 'px'; // logo + jarak .5rem + nama
    }
    fit();
    window.addEventListener('resize', fit);

    setInterval(function () {
        index++;
        list.style.transition = '';
        list.style.transform = 'translateY(' + (-index * items[0].offsetHeight) + 'px)';
        fit();
        if (index === items.length - 1) {
            setTimeout(function () {
                list.style.transition = 'none';
                list.style.transform = 'translateY(0)';
                index = 0;
            }, 600);
        }
    }, 2600);
})();
