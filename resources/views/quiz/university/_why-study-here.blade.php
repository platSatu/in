{{--
    Input "Why Study Here" (6 Oktober 2026) -- dipakai create & edit
    University. Satu poin per baris, pola sama dengan Entry Requirements di
    University Profile. Variabel: $points (array string). Disimpan oleh
    UniversityController::joinPoints(); kosong = halaman publik memakai
    poin default (University::DEFAULT_WHY_STUDY_HERE).
--}}
@php
    $whyPoints = collect(old('why_study_here', $points ?? []))->values();
    if ($whyPoints->isEmpty()) {
        $whyPoints = collect(['']);
    }
@endphp
<div class="row mb-4">
    <div class="col-sm-12">
        <label class="mb-2">Why Study Here</label>
        <div class="form-text mb-2" style="color:#6c757d;">Satu poin per baris (maks. 10). Kalau dikosongkan, halaman kampus memakai poin default.</div>
        <div id="whyStudyRows">
            @foreach ($whyPoints as $whyIndex => $whyValue)
                <div class="why-study-row d-flex gap-2 mb-2">
                    <input type="text" name="why_study_here[]" maxlength="255"
                        class="form-control @error('why_study_here.' . $whyIndex) is-invalid @enderror"
                        value="{{ $whyValue }}" placeholder="mis. Globally recognized institution">
                    <button type="button" class="btn btn-outline-danger btn-remove-why-study-row">&times;</button>
                    @error('why_study_here.' . $whyIndex)
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            @endforeach
        </div>
        @error('why_study_here')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <button type="button" id="btnAddWhyStudyRow" class="btn btn-outline-primary btn-sm mt-1">+ Tambah Poin</button>
    </div>
</div>

<template id="whyStudyRowTemplate">
    <div class="why-study-row d-flex gap-2 mb-2">
        <input type="text" name="why_study_here[]" maxlength="255" class="form-control" placeholder="mis. Globally recognized institution">
        <button type="button" class="btn btn-outline-danger btn-remove-why-study-row">&times;</button>
    </div>
</template>

<script>
    (function () {
        var container = document.getElementById('whyStudyRows');
        var template = document.getElementById('whyStudyRowTemplate');
        var max = 10;

        document.getElementById('btnAddWhyStudyRow').addEventListener('click', function () {
            if (container.querySelectorAll('.why-study-row').length >= max) return;
            container.appendChild(template.content.firstElementChild.cloneNode(true));
        });

        container.addEventListener('click', function (e) {
            if (!e.target.classList.contains('btn-remove-why-study-row')) return;
            var rows = container.querySelectorAll('.why-study-row');
            // Baris terakhir tidak dihapus, cukup dikosongkan (boleh kosong).
            if (rows.length > 1) {
                e.target.closest('.why-study-row').remove();
            } else {
                rows[0].querySelector('input').value = '';
            }
        });
    })();
</script>
