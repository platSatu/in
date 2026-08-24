@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h4>Add Whatsapp Template</h4>
    </div>


    <div class="widget-content widget-content-area">


        <form action="{{ route('quiz.whatsapp-template.store') }}"
            method="POST">

            @csrf


            <div class="mb-3">

                <label class="form-label">
                    Template Name
                </label>


                <input
                    type="text"
                    name="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name') }}">


                @error('name')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-3">

                <label class="form-label">
                    Content
                </label>


                <textarea
                    name="content"
                    rows="6"
                    class="form-control @error('content') is-invalid @enderror"
                    placeholder="Example: Halo, terima kasih sudah mengikuti quiz.">{{ old('content') }}</textarea>


                @error('content')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror

                <div class="form-text" style="color:#6c757d;">
                    {{-- Sama seperti bug di edit.blade.php: pakai @{{ nama }} (Blade raw-echo
                         escape), BUKAN {{ 'dua kurung kurawal' }} — kalau tidak, halaman Add
                         Template ini juga akan ParseError begitu dibuka.

                         Warna dipaksa eksplisit (bukan cuma andalkan .form-text) karena kelas
                         itu di tema admin ini renders nyaris putih di atas background putih —
                         akibatnya teks "dan" & keterangan callback_link jadi tidak kelihatan
                         sama sekali, cuma <code> yang tetap kebaca (warnanya sendiri, bukan
                         ikut .form-text).

                         {{hasil}} & {{pilih_kelas_link}} ditambahkan ke daftar ini (sebelumnya
                         tidak didokumentasikan sama sekali) — dua placeholder itu SUDAH lama
                         ada & otomatis terisi di FrontendController/FormController, cuma admin
                         tidak tahu cara pakainya karena tidak pernah disebut di sini. --}}
                    Placeholder yang bisa dipakai: <code>@{{name}}</code>, <code>@{{form_name}}</code>,
                    <code>@{{ringkasan_jawaban}}</code>, <code>@{{universitas_major}}</code>,
                    <code>@{{hasil}}</code> (skor/hasil test — otomatis terisi sesuai mode penilaian
                    form: Auto = skor, Manual = teks yang admin isi, Section Threshold = nama Section
                    hasil peserta), <code>@{{pilih_kelas_link}}</code> (link pilih jadwal kelas, hanya
                    terisi kalau ada jadwal kelas aktif untuk cabang form ini dan hasil sudah keluar), dan
                    <code>@{{callback_link}}</code> (link callback form, mis. link Zoom — hanya terisi kalau
                    form-nya diaktifkan sebagai callback dan sudah lolos verifikasi pembayaran/submit).
                </div>

            </div>





            <div class="mb-3">

                <label class="form-label">
                    Description
                </label>


                <textarea
                    name="description"
                    rows="4"
                    class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>


                @error('description')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <div class="mb-4">

                <label for="status">
                    Status
                </label>


                <select
                    class="form-select @error('status') is-invalid @enderror"
                    id="status"
                    name="status">


                    <option value="">
                        Choose...
                    </option>


                    <option value="active"
                        {{ old('status') === 'active' ? 'selected' : '' }}>

                        Active

                    </option>


                    <option value="inactive"
                        {{ old('status') === 'inactive' ? 'selected' : '' }}>

                        Inactive

                    </option>


                </select>


                @error('status')

                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>

                @enderror


            </div>





            <button class="btn btn-primary">
                Save
            </button>


            <a href="{{ route('quiz.whatsapp-template.index') }}"
                class="btn btn-secondary">

                Back

            </a>



        </form>


    </div>


</div>


@endsection