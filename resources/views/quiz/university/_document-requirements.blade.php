{{-- Dokumen Persyaratan kampus (12 November 2026). Hanya dokumen yang dicentang yang muncul di halaman upload student,
     disaring lagi menurut jenjang & bahasa programnya. Tidak ada yang dicentang = kampus belum diatur (semua dokumen tampil). --}}
<style>
    .req-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; }
    .req-group { font-size: 12px; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #888ea8; margin: 1.25rem 0 .5rem; }
    .req-item { border: 1px solid #e0e6ed; border-radius: 12px; padding: .75rem 1rem; margin-bottom: .6rem; transition: border-color .2s, background-color .2s; }
    .req-item.is-on { border-color: rgba(200, 16, 46, .35); background: #fff8f9; }
    .req-item .req-fields { display: none; margin-top: .75rem; }
    .req-item.is-on .req-fields { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .6rem; }
    .req-item .req-fields .req-note { grid-column: span 2; }
    .req-item .form-check-label { font-weight: 600; }
    @media (max-width: 767.98px) {
        .req-item.is-on .req-fields { grid-template-columns: 1fr; }
        .req-item .req-fields .req-note { grid-column: auto; }
    }
</style>

<div class="widget-content widget-content-area ecommerce-create-section mt-4">
    <input type="hidden" name="requirements_form" value="1">

    <div class="req-head">
        <div>
            <h5 class="mb-1">Dokumen Persyaratan</h5>
            <p class="text-muted mb-0" style="font-size: 13px;">
                Centang dokumen yang diminta kampus ini. Student hanya melihat dokumen yang dicentang, sesuai jenjang &amp; bahasa programnya.
                Kalau tidak ada yang dicentang, student melihat semua dokumen.
            </p>
        </div>
        <span class="badge badge-light-danger" id="reqCount"></span>
    </div>

    @foreach ($documentTypes->groupBy('group_label') as $group => $types)
        <div class="req-group">{{ $group }}</div>

        @foreach ($types as $type)
            @php
                $rule = $requirements->get($type->id);
                $old = fn ($field, $default = null) => old("requirements.{$type->id}.{$field}", $default);
                $isOn = (bool) $old('use', $rule ? 1 : 0);
            @endphp
            <div class="req-item {{ $isOn ? 'is-on' : '' }}" data-req>
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" value="1" id="req-{{ $type->id }}"
                            name="requirements[{{ $type->id }}][use]" @checked($isOn) data-req-toggle>
                        <label class="form-check-label" for="req-{{ $type->id }}">{{ $type->label }}</label>
                    </div>
                    <div class="form-check mb-0 req-required" @if (! $isOn) hidden @endif>
                        <input class="form-check-input" type="checkbox" value="1" id="req-required-{{ $type->id }}"
                            name="requirements[{{ $type->id }}][is_required]" @checked($old('is_required', $rule ? (int) $rule->is_required : 1))>
                        <label class="form-check-label fw-normal" for="req-required-{{ $type->id }}">Wajib</label>
                    </div>
                </div>

                <div class="req-fields">
                    <select class="form-select form-select-sm" name="requirements[{{ $type->id }}][degree_scope]" aria-label="Jenjang">
                        <option value="">Semua jenjang</option>
                        @foreach (\App\Models\UniversityDocumentRequirement::DEGREE_SCOPES as $value => $label)
                            <option value="{{ $value }}" @selected($old('degree_scope', $rule?->degree_scope) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select class="form-select form-select-sm" name="requirements[{{ $type->id }}][language_scope]" aria-label="Bahasa program">
                        <option value="">Semua bahasa program</option>
                        @foreach (\App\Models\UniversityDocumentRequirement::LANGUAGE_SCOPES as $value => $label)
                            <option value="{{ $value }}" @selected($old('language_scope', $rule?->language_scope) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="text" class="form-control form-control-sm req-note" maxlength="500"
                        name="requirements[{{ $type->id }}][note]" value="{{ $old('note', $rule?->note) }}"
                        placeholder="Catatan untuk student, mis. HSK 4 dengan skor 210+">

                    <div class="req-note">
                        <label class="form-label small text-muted mb-1">Contoh / template khusus kampus ini (opsional)</label>
                        <input type="file" class="form-control form-control-sm" accept=".pdf,.doc,.docx,.jpg,.jpeg"
                            name="requirements[{{ $type->id }}][template]">
                        @error("requirements.{$type->id}.template")
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    @if ($rule?->template_file_path)
                        <div class="req-note small d-flex flex-wrap align-items-end gap-3">
                            <a href="{{ asset($rule->template_file_path) }}" target="_blank" rel="noopener">Lihat contoh saat ini</a>
                            <label class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" value="1" name="requirements[{{ $type->id }}][remove_template]">
                                <span class="form-check-label fw-normal">Hapus contoh</span>
                            </label>
                        </div>
                    @elseif ($type->template_file_path)
                        <div class="req-note small text-muted d-flex align-items-end">Kalau kosong, student melihat template umum.</div>
                    @endif
                </div>
            </div>
        @endforeach
    @endforeach
</div>

<script>
    (function () {
        var count = document.getElementById('reqCount');
        function refresh() {
            var on = document.querySelectorAll('[data-req-toggle]:checked').length;
            count.textContent = on ? on + ' dokumen diminta' : 'Belum diatur: semua dokumen tampil';
        }
        document.querySelectorAll('[data-req-toggle]').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var item = toggle.closest('[data-req]');
                item.classList.toggle('is-on', toggle.checked);
                item.querySelector('.req-required').hidden = !toggle.checked;
                refresh();
            });
        });
        refresh();
    })();
</script>
