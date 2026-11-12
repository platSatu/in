{{--
    Redesign section peta ala "Where will your story begin?" (dark theme, 2 kolom).
    Data universitas per kota tetap AMBIL DARI DATABASE lewat endpoint yang sama
    (FrontendController@universitiesByCity). Yang di bawah ini MASIH PLACEHOLDER
    dan perlu kamu edit/isi manual karena tidak ada di skema DB saat ini:
      - Tagline tiap kota ("Where deep tech meets timeless beauty.")
      - Nama kota dalam huruf Mandarin (杭州, dst)
      - Angka "210+ INA Study students studying here"
      - Foto "cinematic" tiap kota (masih placeholder gradient)
    Semua itu ada di object JS `cityMeta` di bagian bawah, tinggal edit di situ.
    Titik kota selain Nanjing/Shanghai/Hangzhou (Beijing, Xi'an, Harbin, Guangzhou)
    saya taruh cuma sebagai DEKORASI (tidak bisa diklik) biar petanya nggak kosong,
    karena baru 3 kota itu yang ada endpoint universitasnya.

    === FIX POSISI PIN DI PETA ===
    Peta sekarang di-render pakai <img> asli (bukan CSS background-image +
    background-size:cover) supaya gambar tidak pernah ke-crop. Sebelumnya,
    aspect-ratio:4/3.2 yang dipaksa + background-size:cover bikin gambar
    map2.png terpotong kalau rasio aslinya beda, jadi persentase top/left
    pin jadi meleset dari kota aslinya di gambar. Dengan <img> + height:auto,
    container otomatis mengikuti rasio ASLI gambar -> persentase posisi pin
    selalu pas di ukuran layar berapa pun.

    Ada juga "mode kalibrasi" (tombol kuning di pojok kanan atas peta) buat
    bantu cari angka top/left yang pas per kota: nyalakan, klik di titik kota
    pada gambar, nanti muncul angka persentasenya (otomatis ke-copy ke
    clipboard juga) tinggal ditempel ke style pin yang bersangkutan. Hapus
    tombol & scriptnya (bagian "MODE KALIBRASI") kalau semua kota sudah beres.
--}}
<style>
.section-map-dark {
    position: relative;
    background: #0b1120;
    padding: 5rem 1.5rem 5.5rem;
    color: #fff;
    overflow: hidden;
}

.section-map-dark__topline {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    max-width: 1180px;
    margin: 0 auto 1.5rem;
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 0.8rem;
    color: rgba(255,255,255,.55);
}

.section-map-dark__topline .dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--map-red, #E4032B);
    box-shadow: 0 0 10px var(--map-red, #E4032B);
}

.section-map-dark__header {
    max-width: 1180px;
    margin: 0 auto 3rem;
    text-align: left;
}

.section-map-dark__title {
    font-family: "Playfair Display", "Bodoni Moda", serif;
    font-weight: 500;
    font-size: clamp(2.2rem, 5vw, 3.4rem);
    line-height: 1.2;
    margin: 0 0 1rem;
    color: #fff;
}

.section-map-dark__desc {
    font-family: "Poppins", "Segoe UI", sans-serif;
    font-size: 1rem;
    line-height: 1.6;
    color: rgba(255,255,255,.6);
    max-width: 32rem;
    margin: 0;
}

.section-map-dark__grid {
    max-width: 1180px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 1.75rem;
    align-items: stretch;
}

/* ===== Kiri: peta ===== */

.section-map-dark__map {
    position: relative;
    border-radius: 20px;
    border: 1px solid rgba(255,255,255,.06);
    overflow: hidden;
    background-color: #111a2e; /* fallback warna sebelum gambar selesai load */
}

.section-map-dark__map-img {
    display: block;
    width: 100%;
    height: auto; /* kunci fix: ikut rasio asli gambar, tidak pernah di-crop */
}

.section-map-dark__map-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(11,17,32,.1) 0%, rgba(11,17,32,.6) 100%);
    pointer-events: none;
}

.section-map-dark__map-label {
    position: absolute;
    left: 24px;
    bottom: 20px;
    font-family: "Playfair Display", serif;
    font-size: 0.95rem;
    color: rgba(255,255,255,.35);
    letter-spacing: 0.05em;
}

.section-map-dark__map-label small {
    display: block;
    font-family: "Poppins", sans-serif;
    font-size: 0.65rem;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: rgba(255,255,255,.25);
    margin-top: 2px;
}

/* dekorasi titik kota yang belum ada endpoint-nya */
.section-map-dark__deco-dot {
    position: absolute;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(230, 200, 140, .8);
    box-shadow: 0 0 8px rgba(230, 200, 140, .6);
    transform: translate(-50%, -50%);
}

.section-map-dark__deco-label {
    position: absolute;
    transform: translate(-50%, -170%);
    font-family: "Poppins", sans-serif;
    font-size: 0.72rem;
    color: rgba(255,255,255,.55);
    white-space: nowrap;
}

/* pin kota yang beneran interaktif */
.section-map-dark__pin {
    position: absolute;
    transform: translate(-50%, -50%);
    display: flex;
    align-items: center;
    gap: 8px;
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
}

.section-map-dark__pin-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: rgba(230, 200, 140, .9);
    box-shadow: 0 0 10px rgba(230, 200, 140, .7);
    transition: all .2s ease;
    flex-shrink: 0;
}

.section-map-dark__pin-label {
    font-family: "Poppins", sans-serif;
    font-size: 0.78rem;
    font-weight: 600;
    color: rgba(255,255,255,.75);
    white-space: nowrap;
    padding: 3px 0;
    transition: all .2s ease;
}

.section-map-dark__pin:hover .section-map-dark__pin-dot,
.section-map-dark__pin.is-active .section-map-dark__pin-dot {
    background: #fff;
    box-shadow: 0 0 14px #fff;
}

.section-map-dark__pin.is-active .section-map-dark__pin-label {
    background: var(--map-red, #E4032B);
    color: #fff;
    padding: 4px 12px;
    border-radius: 999px;
}

/* ===== Kanan: detail kota ===== */

.section-map-dark__detail {
    position: relative;
    background: #111a2e;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 20px;
    padding: 1.25rem 1.25rem 1.5rem;
    display: flex;
    flex-direction: column;
}

.section-map-dark__photo {
    aspect-ratio: 16 / 9;
    border-radius: 14px;
    background: linear-gradient(135deg, #2f6f5e 0%, #c9a24a 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    color: rgba(255,255,255,.85);
    margin-bottom: 1.25rem;
}

.section-map-dark__photo i { font-size: 1.4rem; }
.section-map-dark__photo span {
    font-family: "Poppins", sans-serif;
    font-size: 0.75rem;
}

.section-map-dark__cn {
    font-family: "Noto Serif SC", "Playfair Display", serif;
    font-size: 1.1rem;
    color: #d6b56a;
    margin-bottom: 2px;
}

.section-map-dark__city-name {
    font-family: "Playfair Display", serif;
    font-size: 2rem;
    color: #fff;
    margin-bottom: 0.6rem;
}

.section-map-dark__tagline {
    font-family: "Poppins", sans-serif;
    font-size: 0.9rem;
    color: rgba(255,255,255,.6);
    margin-bottom: 1.25rem;
}

.section-map-dark__label {
    font-family: "Poppins", sans-serif;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    color: #d6b56a;
    border-top: 1px solid rgba(255,255,255,.08);
    padding-top: 1rem;
    margin-bottom: 0.9rem;
}

.section-map-dark__uni-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 1.25rem;
    min-height: 44px;
}

.section-map-dark__uni-item {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.section-map-dark__uni-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(214,181,106,.4);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #d6b56a;
    font-size: 0.95rem;
    flex-shrink: 0;
    overflow: hidden;
}

.section-map-dark__uni-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.section-map-dark__uni-name {
    font-family: "Poppins", sans-serif;
    font-size: 0.9rem;
    font-weight: 600;
    color: #fff;
}

.section-map-dark__uni-empty {
    font-family: "Poppins", sans-serif;
    font-size: 0.85rem;
    color: rgba(255,255,255,.4);
}

.section-map-dark__footer {
    margin-top: auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
}

.section-map-dark__stat-number {
    font-family: "Playfair Display", serif;
    font-size: 1.6rem;
    color: #d6b56a;
    line-height: 1;
}

.section-map-dark__stat-label {
    font-family: "Poppins", sans-serif;
    font-size: 0.78rem;
    color: rgba(255,255,255,.5);
    max-width: 9rem;
    line-height: 1.35;
}

.section-map-dark__btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--map-red, #E4032B);
    color: #fff;
    font-family: "Poppins", sans-serif;
    font-weight: 600;
    font-size: 0.85rem;
    padding: 0.7rem 1.2rem;
    border-radius: 999px;
    text-decoration: none;
    white-space: nowrap;
    transition: all .2s ease;
}

.section-map-dark__btn:hover {
    background: #b90c26;
    color: #fff;
}

/* ===== Dot pagination kanan luar ===== */

.section-map-dark__pagination {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.section-map-dark__pagination button {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: rgba(255,255,255,.25);
    border: none;
    padding: 0;
    cursor: pointer;
}

.section-map-dark__pagination button.is-active {
    background: var(--map-red, #E4032B);
    transform: scale(1.4);
}

@media (max-width: 900px) {
    .section-map-dark__grid {
        grid-template-columns: 1fr;
    }
    .section-map-dark__pagination {
        display: none;
    }
}

/* ===== MODE KALIBRASI (hapus blok ini kalau sudah selesai) ===== */
.map-calibrate-toggle {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 20;
    background: #fbbf24;
    color: #111;
    font-family: "Poppins", sans-serif;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 6px 12px;
    border: none;
    border-radius: 999px;
    cursor: pointer;
}

.map-calibrate-crosshair {
    position: absolute;
    width: 14px;
    height: 14px;
    border: 2px solid #fbbf24;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
    z-index: 19;
}

.map-calibrate-tooltip {
    position: absolute;
    z-index: 21;
    background: #111;
    color: #fbbf24;
    font-family: monospace;
    font-size: 0.75rem;
    padding: 4px 8px;
    border-radius: 6px;
    transform: translate(-50%, -140%);
    pointer-events: none;
    white-space: nowrap;
}
</style>

<section class="section-map-dark" id="sectionMapDark" data-endpoint="{{ url('/frontend/universities-by-city') }}" style="--map-red: #E4032B;">

    <div class="section-map-dark__topline">
        <span class="dot"></span> Select a city to explore
    </div>

    <div class="section-map-dark__header">
        <h2 class="section-map-dark__title">Where will your<br>story begin?</h2>
        <p class="section-map-dark__desc">Every city offers a different future.<br>Discover where yours begins.</p>
    </div>

    <div class="section-map-dark__grid" style="position: relative;">

        <div class="section-map-dark__map" id="sectionMapImgWrap">

            <img src="{{ asset('image/map2.png') }}" alt="Peta China" class="section-map-dark__map-img">
            <div class="section-map-dark__map-overlay"></div>

            <button type="button" class="map-calibrate-toggle" id="mapCalibrateToggle">
                Kalibrasi: OFF
            </button>

            <div class="section-map-dark__map-label">
                中国 &middot; China
            </div>

            {{-- Dekorasi saja, tidak bisa diklik --}}
            <span class="section-map-dark__deco-dot" style="top: 22%; left: 78%;"></span>
            <span class="section-map-dark__deco-label" style="top: 22%; left: 78%;">Harbin</span>

            <span class="section-map-dark__deco-dot" style="top: 40%; left: 62%;"></span>
            <span class="section-map-dark__deco-label" style="top: 40%; left: 62%;">Beijing</span>

            <span class="section-map-dark__deco-dot" style="top: 56%; left: 45%;"></span>
            <span class="section-map-dark__deco-label" style="top: 56%; left: 45%;">Xi'an</span>

            <span class="section-map-dark__deco-dot" style="top: 84%; left: 60%;"></span>
            <span class="section-map-dark__deco-label" style="top: 84%; left: 60%;">Guangzhou</span>

            {{-- 3 kota beneran, punya data universitas --}}
            <button type="button" class="section-map-dark__pin" style="top: 62%; left: 60%;" data-city="Nanjing">
                <span class="section-map-dark__pin-dot"></span>
                <span class="section-map-dark__pin-label">Nanjing</span>
            </button>

            <button type="button" class="section-map-dark__pin" style="top: 68%; left: 58%;" data-city="Shanghai">
                <span class="section-map-dark__pin-dot"></span>
                <span class="section-map-dark__pin-label">Shanghai</span>
            </button>

            <button type="button" class="section-map-dark__pin is-active" style="top: 73%; left: 65%;" data-city="Hangzhou">
                <span class="section-map-dark__pin-dot"></span>
                <span class="section-map-dark__pin-label">Hangzhou</span>
            </button>
        </div>

        <div class="section-map-dark__detail" id="sectionMapDarkDetail">
            <div class="section-map-dark__photo" id="sectionMapDarkPhoto">
                <i class="bi bi-image"></i>
                <span>Hangzhou &mdash; drop a cinematic photo</span>
            </div>

            <div class="section-map-dark__cn" id="sectionMapDarkCn">杭州</div>
            <div class="section-map-dark__city-name" id="sectionMapDarkName">Hangzhou</div>
            <div class="section-map-dark__tagline" id="sectionMapDarkTagline">Where deep tech meets timeless beauty.</div>

            <div class="section-map-dark__label">Top Universities</div>
            <div class="section-map-dark__uni-list" id="sectionMapDarkUniList">
                <span class="section-map-dark__uni-empty">Loading...</span>
            </div>

            <div class="section-map-dark__footer">
                <div>
                    <div class="section-map-dark__stat-number" id="sectionMapDarkStat">210+</div>
                    <div class="section-map-dark__stat-label">INA Study students studying here</div>
                </div>
                <a href="#" class="section-map-dark__btn" id="sectionMapDarkExplore">
                    Explore Universities <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>

        <div class="section-map-dark__pagination" id="sectionMapDarkPagination"></div>

    </div>

</section>

<script>
(function () {
    const section  = document.getElementById('sectionMapDark');
    const endpoint = section.dataset.endpoint;
    const pins     = Array.from(section.querySelectorAll('.section-map-dark__pin'));

    const photoEl    = document.getElementById('sectionMapDarkPhoto');
    const cnEl       = document.getElementById('sectionMapDarkCn');
    const nameEl     = document.getElementById('sectionMapDarkName');
    const taglineEl  = document.getElementById('sectionMapDarkTagline');
    const uniListEl  = document.getElementById('sectionMapDarkUniList');
    const statEl     = document.getElementById('sectionMapDarkStat');
    const exploreEl  = document.getElementById('sectionMapDarkExplore');
    const paginationEl = document.getElementById('sectionMapDarkPagination');

    // ISI MANUAL per kota — belum ada di database, jadi tetap statis di sini.
    // Tinggal edit teks/angkanya sesuai konten aslinya.
    const cityMeta = {
        'Nanjing':  { cn: '南京', tagline: 'Where history meets modern ambition.', students: '150+' },
        'Shanghai': { cn: '上海', tagline: 'The financial heartbeat of modern China.', students: '320+' },
        'Hangzhou': { cn: '杭州', tagline: 'Where deep tech meets timeless beauty.', students: '210+' },
    };

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderCity(city) {
        pins.forEach(function (p) { p.classList.toggle('is-active', p.dataset.city === city); });

        const meta = cityMeta[city] || { cn: '', tagline: '', students: '-' };

        photoEl.querySelector('span').textContent = `${city} — drop a cinematic photo`;
        cnEl.textContent = meta.cn;
        nameEl.textContent = city;
        taglineEl.textContent = meta.tagline;
        statEl.textContent = meta.students;

        uniListEl.innerHTML = '<span class="section-map-dark__uni-empty">Loading...</span>';

        fetch(`${endpoint}/${encodeURIComponent(city)}`)
            .then(function (res) {
                if (!res.ok) throw new Error('Request failed');
                return res.json();
            })
            .then(function (data) {
                const universities = (data.universities || []).slice(0, 3);

                if (!universities.length) {
                    uniListEl.innerHTML = '<span class="section-map-dark__uni-empty">No partner universities listed yet.</span>';
                    exploreEl.removeAttribute('href');
                    return;
                }

                uniListEl.innerHTML = universities.map(function (u) {
                    const icon = u.logo
                        ? `<img src="${u.logo}" alt="${escapeHtml(u.name)}">`
                        : `<i class="bi bi-mortarboard"></i>`;

                    return `<a href="${u.detail_url}" class="section-map-dark__uni-item">
                                <span class="section-map-dark__uni-icon">${icon}</span>
                                <span class="section-map-dark__uni-name">${escapeHtml(u.name)}</span>
                            </a>`;
                }).join('');

                exploreEl.setAttribute('href', universities[0].detail_url);
            })
            .catch(function () {
                uniListEl.innerHTML = '<span class="section-map-dark__uni-empty">Couldn\'t load universities right now.</span>';
            });
    }

    function renderPagination(activeCity) {
        paginationEl.innerHTML = '';
        Object.keys(cityMeta).forEach(function (city) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = city === activeCity ? 'is-active' : '';
            btn.setAttribute('aria-label', 'Go to ' + city);
            btn.addEventListener('click', function () {
                renderCity(city);
                renderPagination(city);
            });
            paginationEl.appendChild(btn);
        });
    }

    pins.forEach(function (pin) {
        pin.addEventListener('click', function () {
            const city = pin.dataset.city;
            renderCity(city);
            renderPagination(city);
        });
    });

    // Kota aktif default = yang punya class is-active di HTML (Hangzhou)
    const initialCity = (pins.find(function (p) { return p.classList.contains('is-active'); }) || pins[0]).dataset.city;
    renderCity(initialCity);
    renderPagination(initialCity);
})();
</script>

<script>
(function () {
    // === MODE KALIBRASI (hapus blok script ini juga kalau sudah selesai) ===
    // Klik "Kalibrasi: OFF" buat nyalain, lalu klik di manapun pada peta —
    // nanti muncul angka persentase top/left di titik itu (otomatis ke-copy
    // ke clipboard juga). Tinggal tempel ke style="top:...; left:...;" pin
    // yang mau kamu benerkan posisinya.
    const wrap   = document.getElementById('sectionMapImgWrap');
    const toggle = document.getElementById('mapCalibrateToggle');
    if (!wrap || !toggle) return;

    let calibrating = false;
    let crosshair = null;
    let tooltip = null;

    function clearMarkers() {
        if (crosshair) { crosshair.remove(); crosshair = null; }
        if (tooltip) { tooltip.remove(); tooltip = null; }
    }

    toggle.addEventListener('click', function () {
        calibrating = !calibrating;
        toggle.textContent = 'Kalibrasi: ' + (calibrating ? 'ON' : 'OFF');
        clearMarkers();
    });

    wrap.addEventListener('click', function (e) {
        if (!calibrating) return;
        if (e.target === toggle) return;

        const rect = wrap.getBoundingClientRect();
        const xPct = ((e.clientX - rect.left) / rect.width) * 100;
        const yPct = ((e.clientY - rect.top) / rect.height) * 100;
        const text = `top: ${yPct.toFixed(1)}%; left: ${xPct.toFixed(1)}%;`;

        clearMarkers();

        crosshair = document.createElement('div');
        crosshair.className = 'map-calibrate-crosshair';
        crosshair.style.top = yPct + '%';
        crosshair.style.left = xPct + '%';
        wrap.appendChild(crosshair);

        tooltip = document.createElement('div');
        tooltip.className = 'map-calibrate-tooltip';
        tooltip.style.top = yPct + '%';
        tooltip.style.left = xPct + '%';
        tooltip.textContent = text;
        wrap.appendChild(tooltip);

        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).catch(function () {});
        }

        console.log('[Kalibrasi Peta]', text);
    });
})();
</script>
