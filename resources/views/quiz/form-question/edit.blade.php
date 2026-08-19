@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-question.update', $data->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="form_id" class="mb-2">Form</label>
                            <select class="form-select @error('form_id') is-invalid @enderror" id="form_id" name="form_id">
                                <option value="">Choose form...</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}"
                                        {{ old('form_id', $data->form_id) == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('form_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-text mb-2">
                        Pertanyaan boleh kombinasi bebas teks / audio / gambar — minimal salah satu harus diisi.
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="question_text" class="mb-2">Question Text <span class="text-muted">(opsional kalau sudah ada audio/gambar)</span></label>
                            <textarea class="form-control @error('question_text') is-invalid @enderror" id="question_text" name="question_text"
                                rows="4" placeholder="Enter question text...">{{ old('question_text', $data->question_text) }}</textarea>
                            @error('question_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="description" class="mb-2">Description <span class="text-muted">(opsional, instruksi tambahan mis. "Pilih yang sesuai:")</span></label>
                            <input type="text" class="form-control @error('description') is-invalid @enderror"
                                id="description" name="description" placeholder="Instruksi tambahan..."
                                value="{{ old('description', $data->description) }}">
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="audio" class="mb-2">Audio <span class="text-muted">(opsional, mp3/wav/ogg/m4a, maks 8MB)</span></label>
                            @if ($data->audio)
                                <div class="mb-2">
                                    <audio controls src="{{ asset($data->audio) }}" style="width:100%; max-width:280px;"></audio>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" id="remove_audio" name="remove_audio" value="1">
                                        <label class="form-check-label small text-danger" for="remove_audio">Hapus audio ini</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" class="form-control @error('audio') is-invalid @enderror"
                                id="audio" name="audio" accept="audio/*">
                            @error('audio')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="image" class="mb-2">Gambar Pertanyaan <span class="text-muted">(opsional, maks 4MB)</span></label>
                            @if ($data->image)
                                <div class="mb-2">
                                    <img src="{{ asset($data->image) }}" alt="" style="max-width:140px; max-height:140px; object-fit:cover; border-radius:8px;" class="d-block mb-1">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                        <label class="form-check-label small text-danger" for="remove_image">Hapus gambar ini</label>
                                    </div>
                                </div>
                            @endif
                            <input type="file" class="form-control @error('image') is-invalid @enderror"
                                id="image" name="image" accept="image/*">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-8 col-lg-8 col-md-7 mt-xxl-0 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-xxl-12 mb-4">
                                    <label for="type">Type</label>
                                    <select class="form-select @error('type') is-invalid @enderror" id="type" name="type">
                                        <option value="">Choose...</option>
                                        <option value="text"
                                            {{ old('type', $data->type) === 'text' ? 'selected' : '' }}>Text (single line)</option>
                                        <option value="textarea"
                                            {{ old('type', $data->type) === 'textarea' ? 'selected' : '' }}>Textarea (long text)</option>
                                        <option value="number"
                                            {{ old('type', $data->type) === 'number' ? 'selected' : '' }}>Number</option>
                                        <option value="date"
                                            {{ old('type', $data->type) === 'date' ? 'selected' : '' }}>Date</option>
                                        <option value="single_choice"
                                            {{ old('type', $data->type) === 'single_choice' ? 'selected' : '' }}>Single Choice (radio / checkbox-style)</option>
                                        <option value="multiple_choice"
                                            {{ old('type', $data->type) === 'multiple_choice' ? 'selected' : '' }}>Multiple Choice (checkbox)</option>
                                        <option value="dropdown"
                                            {{ old('type', $data->type) === 'dropdown' ? 'selected' : '' }}>Dropdown (select, ringkas untuk opsi banyak)</option>
                                        <option value="major"
                                            {{ old('type', $data->type) === 'major' ? 'selected' : '' }}>Major</option>
                                    </select>
                                    <div class="form-text">
                                        Single Choice, Multiple Choice, dan Dropdown ambil pilihan jawabannya dari menu "Options" di pertanyaan ini.
                                    </div>
                                    @error('type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="stage_group">Termasuk Step</label>
                                    <select class="form-select @error('stage_group') is-invalid @enderror" id="stage_group" name="stage_group">
                                        <option value="placement_test"
                                            {{ old('stage_group', $data->stage_group) === 'placement_test' ? 'selected' : '' }}>Placement Test</option>
                                        <option value="personal_data"
                                            {{ old('stage_group', $data->stage_group) === 'personal_data' ? 'selected' : '' }}>Data Pribadi</option>
                                    </select>
                                    <div class="form-text">
                                        Dipakai kalau form ini mengaktifkan step "Data Pribadi" (diatur di halaman Form). Pertanyaan dengan step "Data Pribadi" tidak akan tampil di placement test, begitu juga sebaliknya.
                                    </div>
                                    @error('stage_group')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="required">Required</label>
                                    <select class="form-select @error('required') is-invalid @enderror" id="required" name="required">
                                        <option value="1" {{ old('required', (string) (int) $data->required) === '1' ? 'selected' : '' }}>Yes, wajib diisi</option>
                                        <option value="0" {{ old('required', (string) (int) $data->required) === '0' ? 'selected' : '' }}>No, boleh kosong</option>
                                    </select>
                                    @error('required')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="order">Order</label>
                                    <input type="number" min="0" class="form-control @error('order') is-invalid @enderror"
                                        id="order" name="order" value="{{ old('order', $data->order) }}">
                                    @error('order')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-xxl-12 mb-4">
                                    <label for="status">Status</label>
                                    <select class="form-select @error('status') is-invalid @enderror" id="status" name="status">
                                        <option value="">Choose...</option>
                                        <option value="active"
                                            {{ old('status', $data->status) === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive"
                                            {{ old('status', $data->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                    @error('status')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Update Question</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('quiz.form-question.index') }}"
                                        class="btn btn-outline-secondary w-100">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </form>

</div>

@endsection