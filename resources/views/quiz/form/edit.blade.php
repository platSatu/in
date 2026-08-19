@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <form action="{{ route('quiz.form.update', $data->id) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="row mb-4 layout-spacing layout-top-spacing">

            <div class="col-xxl-9 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="widget-content widget-content-area ecommerce-create-section">

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="branch_id" class="mb-2">Company Branch</label>
                            <select class="form-select @error('branch_id') is-invalid @enderror" id="branch_id" name="branch_id">
                                <option value="">-- Select Company Branch --</option>
                                @foreach ($companyBranches as $companyBranch)
                                    <option value="{{ $companyBranch->id }}" {{ old('branch_id', $data->branch_id) == $companyBranch->id ? 'selected' : '' }}>
                                        {{ $companyBranch->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('branch_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="no_booth" class="mb-2">No Booth</label>
                            <input type="text" class="form-control @error('no_booth') is-invalid @enderror"
                                id="no_booth" name="no_booth" placeholder="Contoh: 12 atau A1" value="{{ old('no_booth', $data->no_booth) }}">
                            @error('no_booth')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="slug" class="mb-2">Public URL</label>
                            <input type="text" class="form-control" readonly disabled
                                value="{{ ($data->slug && $data->booth_slug) ? url('/quiz/' . $data->slug . '/' . $data->booth_slug) : '-' }}">
                            <div class="form-text">Dibuat otomatis dari nama branch + no booth, otomatis diperbarui kalau branch/no booth diganti.</div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label class="mb-2 d-block">Requires Payment</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="requires_payment"
                                    name="requires_payment" value="1" {{ old('requires_payment', $data->requires_payment) ? 'checked' : '' }}>
                                <label class="form-check-label" for="requires_payment">
                                    Peserta wajib bayar sebelum lanjut ke placement test
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-8" id="paymentAmountWrapper">
                            <label for="payment_amount" class="mb-2">Payment Amount (Rp)</label>
                            <input type="number" min="0" step="1000"
                                class="form-control @error('payment_amount') is-invalid @enderror"
                                id="payment_amount" name="payment_amount" placeholder="Contoh: 50000"
                                value="{{ old('payment_amount', $data->payment_amount) }}">
                            @error('payment_amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4" id="paymentPositionWrapper">
                        <div class="col-sm-12">
                            <label class="mb-2 d-block">Posisi Pembayaran</label>

                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_position"
                                    id="payment_position_before" value="before_questions"
                                    {{ old('payment_position', $data->payment_position ?? 'before_questions') === 'before_questions' ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_position_before">
                                    Di awal — peserta bayar dulu, baru bisa isi placement test
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_position"
                                    id="payment_position_after" value="after_questions"
                                    {{ old('payment_position', $data->payment_position ?? '') === 'after_questions' ? 'checked' : '' }}>
                                <label class="form-check-label" for="payment_position_after">
                                    Di akhir — peserta isi placement test dulu, baru bayar sebelum submit
                                </label>
                            </div>
                            @error('payment_position')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label class="mb-2 d-block">Callback Link</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_callback_enabled"
                                    name="is_callback_enabled" value="1" {{ old('is_callback_enabled', $data->is_callback_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_callback_enabled">
                                    Jadikan form ini callback
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-8" id="callbackLinkWrapper">
                            <label for="callback_link" class="mb-2">Callback Link (mis. link Zoom)</label>
                            <input type="url"
                                class="form-control @error('callback_link') is-invalid @enderror"
                                id="callback_link" name="callback_link" placeholder="https://zoom.us/j/xxxxxxxxx"
                                value="{{ old('callback_link', $data->callback_link) }}">
                            <div class="form-text" id="callbackLinkNote"></div>
                            @error('callback_link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label class="mb-2 d-block">Notifikasi WhatsApp</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="use_whatsapp_notification"
                                    name="use_whatsapp_notification" value="1" {{ old('use_whatsapp_notification', $data->use_whatsapp_notification) ? 'checked' : '' }}>
                                <label class="form-check-label" for="use_whatsapp_notification">
                                    Gunakan template WhatsApp
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-8" id="whatsappTemplateWrapper">
                            <label for="whatsapp_template_id" class="mb-2">Whatsapp Template</label>
                            <select class="form-select @error('whatsapp_template_id') is-invalid @enderror"
                                id="whatsapp_template_id" name="whatsapp_template_id">
                                <option value="">-- Select Whatsapp Template --</option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}"
                                        {{ old('whatsapp_template_id', $data->whatsapp_template_id) == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('whatsapp_template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label class="mb-2 d-block">Step Data Pribadi</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="has_personal_data_stage"
                                    name="has_personal_data_stage" value="1" {{ old('has_personal_data_stage', $data->has_personal_data_stage) ? 'checked' : '' }}>
                                <label class="form-check-label" for="has_personal_data_stage">
                                    Aktifkan step "Data Pribadi"
                                </label>
                            </div>
                            <div class="form-text">
                                Kalau aktif, peserta akan mengisi pertanyaan bertipe "Data Pribadi" (diatur di halaman pertanyaan form) setelah isi Nama/Email/HP, sebelum lanjut ke pembayaran/placement test.
                            </div>
                        </div>
                        <div class="col-sm-8">
                            <label for="result_mode" class="mb-2">Mode Hasil</label>
                            <select class="form-select @error('result_mode') is-invalid @enderror" id="result_mode" name="result_mode">
                                <option value="none" {{ old('result_mode', $data->result_mode) === 'none' ? 'selected' : '' }}>Tidak ada hasil</option>
                                <option value="auto" {{ old('result_mode', $data->result_mode) === 'auto' ? 'selected' : '' }}>Otomatis (dari skor jawaban)</option>
                                <option value="manual" {{ old('result_mode', $data->result_mode) === 'manual' ? 'selected' : '' }}>Manual (diisi admin)</option>
                            </select>
                            <div class="form-text">
                                Otomatis: skor dijumlah dari nilai (score) tiap opsi jawaban placement test yang dipilih peserta. Manual: admin mengisi hasil sebagai teks bebas per peserta di halaman submission.
                            </div>
                            @error('result_mode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4">
                            <label class="mb-2 d-block">Timer Placement Test</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="timer_enabled"
                                    name="timer_enabled" value="1" {{ old('timer_enabled', $data->timer_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="timer_enabled">
                                    Aktifkan batas waktu pengerjaan
                                </label>
                            </div>
                            <div class="form-text">
                                Batas waktu berlaku HANYA saat peserta mengerjakan step Placement Test (soal-soal). Step Data Pribadi & Pembayaran tidak kena batas waktu ini.
                            </div>
                        </div>
                        <div class="col-sm-8" id="timerSettingsWrapper">
                            <label for="timer_duration_minutes" class="mb-2">Durasi (menit)</label>
                            <input type="number" min="1" max="600"
                                class="form-control @error('timer_duration_minutes') is-invalid @enderror"
                                id="timer_duration_minutes" name="timer_duration_minutes" placeholder="Contoh: 30"
                                value="{{ old('timer_duration_minutes', $data->timer_duration_minutes) }}">
                            @error('timer_duration_minutes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror

                            <div class="form-check form-switch mt-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="timer_auto_save"
                                    name="timer_auto_save" value="1" {{ old('timer_auto_save', $data->timer_auto_save) ? 'checked' : '' }}>
                                <label class="form-check-label" for="timer_auto_save">
                                    Auto-Save saat waktu habis (simpan jawaban apa adanya)
                                </label>
                            </div>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="timer_auto_restart"
                                    name="timer_auto_restart" value="1" {{ old('timer_auto_restart', $data->timer_auto_restart) ? 'checked' : '' }}>
                                <label class="form-check-label" for="timer_auto_restart">
                                    Auto-Refresh saat waktu habis (mulai lagi dari soal pertama)
                                </label>
                            </div>
                            <div class="form-text mt-2">
                                Kalau kedua toggle di atas aktif: jawaban yang sempat terisi disimpan dulu, baru soal direset ke soal pertama. Kalau keduanya nonaktif, timer tetap tampil & habis, tapi tidak ada aksi otomatis.
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-6">
                            <label for="start_date" class="mb-2">Tanggal Mulai (Optional)</label>
                            <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror"
                                id="start_date" name="start_date"
                                value="{{ old('start_date', optional($data->start_date)->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Kosongkan kalau form langsung bisa diakses selama status Active.</div>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-sm-6">
                            <label for="end_date" class="mb-2">Tanggal Selesai (Optional)</label>
                            <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror"
                                id="end_date" name="end_date"
                                value="{{ old('end_date', optional($data->end_date)->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Kosongkan kalau form tidak punya batas akhir waktu.</div>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="name" class="mb-2">Form Name</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                id="name" name="name" placeholder="Form Name" value="{{ old('name', $data->name) }}">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-12">
                            <label for="description" class="mb-2">Description (Optional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="4"
                                placeholder="Form Description">{{ old('description', $data->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-xxl-3 col-xl-12 col-lg-12 col-md-12 col-sm-12">
                <div class="row">
                    <div class="col-xxl-12 col-xl-4 col-lg-4 col-md-5 mt-4 mt-xxl-0">
                        <div class="widget-content widget-content-area ecommerce-create-section">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <button type="submit" class="btn btn-success w-100">Update Form</button>
                                </div>
                                <div class="col-sm-12">
                                    <a href="{{ route('quiz.form.index') }}"
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

<script>
    (function () {
        var checkbox = document.getElementById('requires_payment');
        var wrapper = document.getElementById('paymentAmountWrapper');
        var positionWrapper = document.getElementById('paymentPositionWrapper');
        var amountInput = document.getElementById('payment_amount');

        function sync() {
            wrapper.style.display = checkbox.checked ? '' : 'none';
            positionWrapper.style.display = checkbox.checked ? '' : 'none';
        }

        checkbox.addEventListener('change', function () {
            if (!checkbox.checked) {
                amountInput.value = '';
            }
            sync();
        });
        sync();
    })();

    (function () {
        var callbackCheckbox = document.getElementById('is_callback_enabled');
        var callbackWrapper = document.getElementById('callbackLinkWrapper');
        var callbackInput = document.getElementById('callback_link');
        var callbackNote = document.getElementById('callbackLinkNote');
        var paymentCheckbox = document.getElementById('requires_payment');

        function sync() {
            callbackWrapper.style.display = callbackCheckbox.checked ? '' : 'none';
            if (!callbackCheckbox.checked) {
                callbackInput.value = '';
            }
            callbackNote.textContent = paymentCheckbox.checked
                ? 'Link ini baru ditampilkan/dikirim ke peserta setelah submit selesai DAN pembayaran terkonfirmasi.'
                : 'Form ini tidak butuh pembayaran, jadi link langsung ditampilkan/dikirim ke peserta setelah submit selesai.';
        }

        callbackCheckbox.addEventListener('change', sync);
        paymentCheckbox.addEventListener('change', sync);
        sync();
    })();

    (function () {
        var waCheckbox = document.getElementById('use_whatsapp_notification');
        var waWrapper = document.getElementById('whatsappTemplateWrapper');
        var waSelect = document.getElementById('whatsapp_template_id');

        function sync() {
            waWrapper.style.display = waCheckbox.checked ? '' : 'none';
            if (!waCheckbox.checked) {
                waSelect.value = '';
            }
        }

        waCheckbox.addEventListener('change', sync);
        sync();
    })();

    (function () {
        var checkbox = document.getElementById('timer_enabled');
        var wrapper = document.getElementById('timerSettingsWrapper');
        var durationInput = document.getElementById('timer_duration_minutes');
        var autoSaveCheckbox = document.getElementById('timer_auto_save');
        var autoRestartCheckbox = document.getElementById('timer_auto_restart');

        function sync() {
            wrapper.style.display = checkbox.checked ? '' : 'none';
            if (!checkbox.checked) {
                durationInput.value = '';
                autoSaveCheckbox.checked = false;
                autoRestartCheckbox.checked = false;
            }
        }

        checkbox.addEventListener('change', sync);
        sync();
    })();
</script>

@endsection
