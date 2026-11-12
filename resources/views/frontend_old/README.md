# Halaman depan lama (arsip)

Arsip halaman depan sebelum desain ulang November 2026. Tidak dipakai route mana pun.

- `index.blade.php`: halaman lengkap (menu, section, footer). Include sudah diarahkan ke `frontend_old.partials.*`.
- `partials/section1` s.d. `section7`, `partials/footer`: isi per section. Urutan tampil: 1, 2, 7, 3, 4, 6, 5, footer.
- Gambar/video yang dipakai masih di `public/image/` dan `public/video/` (tidak dipindah).

Cara memanggil lagi sementara: ubah `FrontendController::index()` menjadi `return view('frontend_old.index');`.

`frontend/partials/question-card.blade.php` TIDAK ikut dipindah karena masih dipakai form pendaftaran (form-wizard) dan Quiz › Form.

Halaman depan baru ada di `resources/views/frontend/home/` dengan CSS/JS di `public/frontend/home/`.
