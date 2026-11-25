@extends('layouts.frontend')
@section('content')

{{--
    Tambah Student INA YULE (25 November 2026). Murid yang ditambah di sini langsung tampil di
    Student INA YULE dan juga di menu Students (satu data yang sama, tidak dibuat ganda).
    Lihat CourseStudentController::store() & StudentImportService::addOne().
--}}

<div class="middle-content container-xxl p-0">
    <div class="row layout-top-spacing">
        <div class="col-lg-7 mx-auto layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h5 class="mb-1">Tambah Student INA YULE</h5>
                <p class="text-muted mb-4">Murid yang ditambahkan di sini langsung masuk ke Student INA YULE, dan datanya juga ada di menu Students.</p>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('course.student.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Nama lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="mis. Budi Santoso" required autofocus>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="budi@email.com" required>
                            <div class="form-text">Dipakai murid untuk login.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">No. HP / WhatsApp</label>
                            <input type="text" name="handphone" class="form-control" value="{{ old('handphone') }}" placeholder="0812xxxxxxxx" inputmode="tel">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cabang @if (auth()->user()->visibleBranchIds() !== null)<span class="text-danger">*</span>@endif</label>
                            <select name="branch_id" class="form-select">
                                @if (auth()->user()->visibleBranchIds() === null)
                                    <option value="">-- Tanpa cabang --</option>
                                @endif
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(old('branch_id') === $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Menentukan paket yang tampil untuk murid ini.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pengajar tetap <span class="text-muted small">(opsional)</span></label>
                            <select name="teacher_user_id" class="form-select">
                                <option value="">-- Belum ditentukan --</option>
                                @foreach ($teachers as $teacher)
                                    <option value="{{ $teacher->id }}" @selected(old('teacher_user_id') === $teacher->id)>{{ $teacher->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-light border small mb-4">
                        <strong>Setelah disimpan:</strong> akun login otomatis dibuat. Murid cukup membuka halaman login, klik
                        <em>Lupa Password</em>, lalu membuat password lewat email. Kalau email ini sudah terdaftar, data lamanya
                        dipakai (tidak dibuat ganda).
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary" onclick="this.disabled = true; this.form.submit();">Simpan Student</button>
                        <a href="{{ route('course.student.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
