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
        <div class="col-xl-8 layout-spacing">
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
                                    <th>Pengajar</th>
                                    <th>Cabang</th>
                                    <th>Course Class</th>
                                    <th>Cara Hitung</th>
                                    <th style="min-width: 150px">Fee (Rp)</th>
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

        <div class="col-xl-4 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h6 class="mb-2">Cara sistem memilih tarif</h6>
                <p class="small text-muted">Kalau ada beberapa aturan yang cocok, yang <strong>paling spesifik</strong> dipakai:</p>
                <ol class="small ps-3 mb-3">
                    <li>Aturan yang menyebut <strong>nama pengajar</strong></li>
                    <li>lalu yang menyebut <strong>Course Class</strong></li>
                    <li>lalu yang menyebut <strong>cabang</strong></li>
                    <li>tidak ada yang cocok: <strong>fee umum Course Class</strong></li>
                </ol>
                <p class="small text-muted mb-2">Contoh:</p>
                <ul class="small ps-3 mb-0">
                    <li>Semua pengajar &middot; MOI &middot; Private &middot; Per Kelas &middot; 1.000.000</li>
                    <li>Semua pengajar &middot; Pluit &middot; Private &middot; Per Kelas &middot; 2.000.000</li>
                    <li>Pengajar A &middot; Semua cabang &middot; Semua kelas &middot; Per Jam &middot; 10.000</li>
                </ul>
                <p class="small text-muted mt-2 mb-0">Pengajar A selalu dibayar Rp 10.000/jam. Pengajar lain yang mengajar Private di Pluit dibayar Rp 2.000.000 per kelas.</p>
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
