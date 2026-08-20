@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form-question-option.update', $data->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="question_id" class="mb-2">Question</label>
                            <select class="form-select @error('question_id') is-invalid @enderror" id="question_id" name="question_id">
                                <option value="">Choose question...</option>
                                @foreach ($questions as $question)
                                    <option value="{{ $question->id }}"
                                        {{ old('question_id', $data->question_id) == $question->id ? 'selected' : '' }}>
                                        {{ \Illuminate\Support\Str::limit($question->question_text, 80) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('question_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-text mb-2">
                        Opsi boleh teks saja, gambar saja, atau dua-duanya — minimal salah satu harus diisi.
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="option_text" class="mb-2">Option Text <span class="text-muted">(opsional kalau sudah ada gambar)</span></label>
                            <input type="text" class="form-control @error('option_text') is-invalid @enderror"
                                id="option_text" name="option_text" placeholder="Enter option text..."
                                value="{{ old('option_text', $data->option_text) }}">
                            @error('option_text')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="image" class="mb-2">Gambar Opsi <span class="text-muted">(opsional, maks 4MB)</span></label>
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

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_other" name="is_other" value="1"
                                    {{ old('is_other', $data->is_other) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_other">
                                    Jadikan opsi "Lainnya" (isian bebas)
                                </label>
                            </div>
                            <div class="form-text">
                                Khusus pertanyaan tipe Multiple Choice — begitu opsi ini dicentang peserta, muncul kolom
                                teks tambahan supaya mereka bisa isi jawaban sendiri.
                            </div>
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
                                    <label for="score">Score (Optional)</label>
                                    <input type="number" class="form-control @error('score') is-invalid @enderror"
                                        id="score" name="score" value="{{ old('score', $data->score) }}">
                                    <div class="form-text">
                                        Dipakai kalau form ini mengaktifkan Mode Hasil "Otomatis" — skor opsi yang dipilih peserta akan dijumlahkan jadi hasil akhir. Kosongkan/biarkan 0 kalau opsi ini tidak berkontribusi ke skor.
                                    </div>
                                    @error('score')
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
                                    <button type="submit" class="btn btn-success w-100">Update Option</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('quiz.form-question-option.index') }}"
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
