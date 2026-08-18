@extends('layouts.frontend')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">✏️ Edit Undangan</h4>
            <small class="text-muted">{{ $invitation->name }}</small>
        </div>
        <a href="{{ route('dashboard.invitation.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Kembali
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header fw-semibold">
                    <i class="fas fa-edit me-2 text-primary"></i> Edit Data Undangan
                </div>
                <div class="card-body p-4">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('dashboard.invitation.update', $invitation->id) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $invitation->name) }}" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                            <input type="text" name="handphone"
                                   class="form-control @error('handphone') is-invalid @enderror"
                                   value="{{ old('handphone', $invitation->handphone) }}" required>
                            @error('handphone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Universitas / Instansi</label>
                                <input type="text" name="university"
                                       class="form-control @error('university') is-invalid @enderror"
                                       value="{{ old('university', $invitation->university) }}">
                                @error('university')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Program / Jurusan</label>
                                <input type="text" name="program"
                                       class="form-control @error('program') is-invalid @enderror"
                                       value="{{ old('program', $invitation->program) }}">
                                @error('program')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Jumlah Peserta</label>
                                <input type="number" name="number_of_attendes"
                                       class="form-control @error('number_of_attendes') is-invalid @enderror"
                                       value="{{ old('number_of_attendes', $invitation->number_of_attendes) }}"
                                       min="1">
                                @error('number_of_attendes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status"
                                        class="form-select @error('status') is-invalid @enderror" required>
                                    
                                    <option value="hadir"       {{ old('status', $invitation->status) === 'hadir'       ? 'selected' : '' }}>Hadir</option>
                                    <option value="tidak_hadir" {{ old('status', $invitation->status) === 'tidak_hadir' ? 'selected' : '' }}>Tidak Hadir</option>
                                </select>
                                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Info QR (readonly) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-muted">Kode QR (tidak bisa diubah)</label>
                            <input type="text" class="form-control bg-light"
                                   value="{{ $invitation->qrcode }}" readonly>
                        </div>

                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                            <a href="{{ route('dashboard.invitation.index') }}"
                               class="btn btn-outline-secondary">Batal</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

</div>
@endsection
