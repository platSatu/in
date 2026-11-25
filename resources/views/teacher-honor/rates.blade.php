@extends('layouts.frontend')
@section('content')

{{--
    Honor Pengajar > Tarif Pengajar (25 November 2026). Satu baris = satu aturan fee.
    Kolom "Semua ..." berarti berlaku untuk semuanya. Aturan paling spesifik yang dipakai,
    lihat App\Models\TeacherRate::bestFor().
--}}

<div class="middle-content container-xxl p-0">
    <p class="text-muted mb-3">Atur fee honor per pengajar, per cabang, atau per jenis kelas. Kalau tidak ada aturan yang cocok, sistem memakai fee umum di Course Class.</p>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->unique() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            {{-- Penjelasan singkat di atas tabel (sebelumnya kolom kanan, membuat tabel terlalu sempit). --}}
            <div class="alert alert-light border d-flex flex-wrap gap-3 align-items-start mb-3">
                <div class="flex-grow-1" style="min-width: 260px">
                    <strong class="d-block mb-1">Cara sistem memilih tarif</strong>
                    <span class="small text-muted">Kalau beberapa aturan cocok, yang paling spesifik dipakai:
                        <strong>nama pengajar</strong> &rarr; <strong>Course Class</strong> &rarr; <strong>cabang</strong> &rarr; tidak ada yang cocok: <strong>fee umum Course Class</strong>.
                        Tarif khusus <strong>menggantikan</strong> fee Course Class, tidak ditambahkan.</span>
                </div>
                <div class="small text-muted" style="min-width: 260px">
                    <strong class="d-block mb-1 text-body">Contoh</strong>
                    Pengajar A &middot; Semua cabang &middot; Semua kelas &middot; Per Jam &middot; 10.000<br>
                    = Pengajar A selalu dibayar Rp 10.000/jam, pengajar lain ikut Course Class.
                </div>
            </div>

            <div class="widget-content widget-content-area br-8">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="mb-0">Aturan Tarif</h6>
                    <a href="{{ route('teacher-honor.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Honor Pengajar</a>
                </div>

                <form method="POST" action="{{ route('teacher-honor.rates.save') }}">
                    @csrf
                    @method('PUT')

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th style="min-width: 200px">Pengajar</th>
                                    <th style="min-width: 180px">Cabang</th>
                                    <th style="min-width: 180px">Course Class</th>
                                    <th style="min-width: 150px">Cara Hitung</th>
                                    <th style="min-width: 160px">Fee (Rp)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="rateRows">
                                @foreach ($rows as $i => $row)
                                    @include('teacher-honor._rate-row', ['i' => $i, 'row' => $row])
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="text-muted small" id="rateEmpty" @if (count($rows)) hidden @endif>
                        Belum ada aturan. Semua honor memakai fee umum di Course Class. Klik "Tambah Baris" untuk membuat aturan.
                    </p>

                    <button type="button" class="btn btn-outline-primary btn-sm mb-4" id="addRate">+ Tambah Baris</button>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Tarif</button>
                    </div>
                    <small class="text-muted d-block mt-2">Tarif baru langsung dipakai periode yang masih berjalan. Periode yang sudah ditutup tidak berubah.</small>
                </form>
            </div>
        </div>

    </div>
</div>

<template id="rateTemplate">
    @include('teacher-honor._rate-row', ['i' => '__INDEX__', 'row' => ['honor_mode' => \App\Models\CourseClass::HONOR_PER_CLASS]])
</template>

<script>
(function () {
    var body = document.getElementById('rateRows');
    var template = document.getElementById('rateTemplate').innerHTML;
    var empty = document.getElementById('rateEmpty');
    var nextIndex = {{ count($rows) }};

    function refresh() { empty.hidden = body.children.length > 0; }

    document.getElementById('addRate').addEventListener('click', function () {
        body.insertAdjacentHTML('beforeend', template.replace(/__INDEX__/g, nextIndex++));
        refresh();
    });

    body.addEventListener('click', function (event) {
        if (event.target.matches('[data-remove-row]')) {
            event.target.closest('tr').remove();
            refresh();
        }
    });
})();
</script>
@endsection
