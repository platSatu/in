{{--
    Halaman turunan InaYule (Ajukan Credit, Checkout, Upgrade, dst.) di HP
    supaya senada dengan halaman InaYule ala aplikasi (14 Oktober 2026).
    Pakai: tambah class "inayule-sub" di pembungkus halaman lalu
    @include('student-portal.inayule._subpage', ['backTab' => 'kelas']).
    Semua style di-scope ke .inayule-sub; desktop tidak berubah selain warna tombol.
--}}
<a href="{{ route('inayule.index') }}#{{ $backTab ?? 'beli' }}" class="iy-back d-md-none">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
    InaYule
</a>
<style>
    .inayule-sub { --ina-red: #d71920; --ina-red-dark: #b3121a; }
    .inayule-sub .btn { border-radius: 10px; font-weight: 700; }
    .inayule-sub .btn-primary { background: linear-gradient(135deg, var(--ina-red), var(--ina-red-dark)); border-color: var(--ina-red); color: #fff; }
    .inayule-sub .btn-primary:hover, .inayule-sub .btn-primary:focus { background: var(--ina-red-dark); border-color: var(--ina-red-dark); color: #fff; }
    .inayule-sub .btn-outline-primary { color: var(--ina-red); border-color: var(--ina-red); }
    .inayule-sub .btn-outline-primary:hover { background: var(--ina-red); color: #fff; }
    .iy-back { display: inline-flex; align-items: center; gap: 4px; font-weight: 700; color: var(--ina-red, #d71920); text-decoration: none; margin: 4px 0 12px; }

    @media (max-width: 767.98px) {
        .inayule-sub { padding: 0; }
        .inayule-sub .widget-content-area { border-radius: 20px; padding: 18px; box-shadow: 0 8px 24px rgba(31, 35, 64, .06); }
        .inayule-sub .form-control, .inayule-sub .form-select { min-height: 46px; font-size: 16px; }
        /* Tombol aksi selebar layar, tombol utama di atas (mudah dijangkau jempol). */
        .inayule-sub form > .d-flex.gap-2, .inayule-sub .widget-content-area > .d-flex.gap-2 { flex-direction: column; }
        .inayule-sub .btn:not(.input-group .btn):not(.btn-sm) { width: 100%; min-height: 48px; display: inline-flex; align-items: center; justify-content: center; }
        .inayule-sub .list-group-item { padding: 14px 12px; }
    }
</style>
