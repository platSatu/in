@extends('layouts.frontend')
@section('content')

{{--
    FASE 2 bagian 2 "Absensi" -- form pengajuan pemakaian credit. BELUM
    memotong credit sama sekali (baru terpotong setelah admin approve,
    lihat docblock ClassSessionWorkflowService::adminApprove()).
--}}

<div class="col-lg-6 mx-auto layout-spacing">
    <div class="statbox widget box box-shadow">
        <div class="widget-content widget-content-area">
            <h4 class="mb-1">Ajukan Pemakaian Credit</h4>
            <p class="text-muted mb-4">1 credit = 1 jam kelas. Kalau belajar 2 jam di hari yang sama, ajukan 2 kali terpisah (masing-masing 1 credit).</p>

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

            <form method="POST" action="{{ route('inayule.class-sessions.store') }}">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Pengajar</label>
                    <select name="teacher_user_id" class="form-select" required>
                        <option value="">-- Pilih Pengajar --</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('teacher_user_id') === $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @if ($teachers->isEmpty())
                        <small class="text-muted">Belum ada pengajar terdaftar (role "teacher").</small>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Package</label>
                    <select name="course_package_id" class="form-select" required>
                        <option value="">-- Pilih Package --</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(old('course_package_id') === $package->id)>{{ $package->name }}</option>
                        @endforeach
                    </select>
                    @if ($packages->isEmpty())
                        <small class="text-muted">Belum ada riwayat pembelian package untuk akun ini.</small>
                    @endif
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Credit</label>
                    <select name="credit_amount" class="form-select" required>
                        <option value="">-- Pilih Jumlah --</option>
                        <option value="1" @selected(old('credit_amount') === '1')>1 credit (1 jam)</option>
                        <option value="1.5" @selected(old('credit_amount') === '1.5')>1.5 credit (1.5 jam)</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Catatan (opsional)</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Ajukan</button>
                    <a href="{{ route('inayule.class-sessions.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
