// Halaman depan InaStudy: header berlatar saat discroll + menu HP.
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
