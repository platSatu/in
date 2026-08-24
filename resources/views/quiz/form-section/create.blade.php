@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-section.store') }}" method="POST" id="formSectionCreateForm">
        @csrf

        <div class="row mb-4 layout-spacing layout-top-spacing">
            <div class="col-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="form_id" class="mb-2">Form</label>

                            @php
                                $lockedFormId = old('form_id', $selectedFormId ?? null);
                                $lockedForm = $lockedFormId ? $forms->firstWhere('id', $lockedFormId) : null;
                            @endphp

                            @if ($lockedForm && !$errors->has('form_id'))
                                <input type="text" class="form-control" value="{{ $lockedForm->name }}" disabled readonly>
                                <input type="hidden" id="form_id" name="form_id" value="{{ $lockedForm->id }}">
                                <div class="form-text mb-2">
                                    Section ini akan dikaitkan ke form di atas.
                                </div>
                                <a href="{{ route('quiz.form-section.create') }}" class="btn btn-sm btn-outline-secondary">
                                    Ganti form
                                </a>
                            @else
                                <select class="form-select @error('form_id') is-invalid @enderror" id="form_id" name="form_id">
                                    <option value="">Choose form...</option>
                                    @foreach ($forms as $form)
                                        <option value="{{ $form->id }}" {{ $lockedFormId == $form->id ? 'selected' : '' }}>
                                            {{ $form->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('form_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Section</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                name="name" value="{{ old('name') }}" placeholder="mis. Section A" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mt-1">
                        <div class="col-md-12">
                            <label class="form-label">Description <span class="text-muted">(opsional)</span></label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                name="description" rows="3" placeholder="Instruksi/keterangan tambahan untuk section ini...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-text mt-3 mb-3">
                        Section baru otomatis ditaruh di urutan paling akhir. Setelah section ini tersimpan, buka
                        halaman "Form Questions" untuk membuat pertanyaan baru di dalamnya, atau edit pertanyaan yang
                        sudah ada untuk memindahkannya ke section ini.
                    </div>

                    <div class="row mt-3">
                        <div class="col-sm-3 mb-3">
                            <button type="submit" class="btn btn-success w-100">Simpan Section</button>
                        </div>
                        <div class="col-sm-3 mb-3">
                            <a href="{{ route('quiz.form-section.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>

</div>

@endsection
