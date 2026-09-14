@extends('layouts.frontend')
@section('content')

{{--
    Halaman "InaStudy" (14 September 2026, permintaan user) -- DIPISAH dari
    Dashboard supaya menu "Dashboard" di sidebar tetap cuma berisi Academic
    Calendar, sementara widget "My University Applications" + alur Register
    manual (lihat docblock InaStudyController::registerApplication() untuk
    alasan lengkap bypass Registration Fee-nya) sekarang punya halaman
    sendiri yang dituju langsung oleh menu "InaStudy" di sidebar.

    Widget di bawah SELALU tampil untuk siswa (dicek lewat $hasStudent) --
    kalau $myApplications masih kosong, tabel menampilkan baris
    "Data not found" + tombol Register, supaya siswa yang belum pernah apply
    tetap bisa mulai proses langsung dari sini.
--}}
@if($hasStudent)
<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3">My University Applications</h4>

            {{-- Panel Register: default hidden, ditampilkan lewat tombol "Register"
                 di baris "Data not found" di bawah, atau otomatis kalau validasi
                 submit sebelumnya gagal (old('university_id') masih terisi). --}}
            <div id="registerFormPanel" class="border rounded p-3 mb-3" style="{{ $errors->any() && old('university_id') ? '' : 'display:none;' }} background:#f8f9fa;">
                <h6 class="mb-3">Register Aplikasi Kuliah</h6>
                <form method="POST" action="{{ route('inastudy.register') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Universitas</label>
                            <select name="university_id" id="registerUniversitySelect" class="form-select @error('university_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Universitas --</option>
                                @foreach($registerUniversities as $university)
                                    <option value="{{ $university->id }}" {{ old('university_id') == $university->id ? 'selected' : '' }}>{{ $university->name }}</option>
                                @endforeach
                            </select>
                            @error('university_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6" id="registerProfileWrapper" style="{{ old('university_id') ? '' : 'display:none;' }}">
                            <label class="form-label">Jurusan</label>
                            <select name="university_profile_id" id="registerProfileSelect" class="form-select @error('university_profile_id') is-invalid @enderror" required>
                                <option value="">-- Pilih Jurusan --</option>
                            </select>
                            @error('university_profile_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-success btn-sm">Save</button>
                        <button type="button" id="btnCancelRegister" class="btn btn-outline-secondary btn-sm">Cancel</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Application No</th>
                            <th>University</th>
                            <th>Major</th>
                            <th>Status</th>
                            <th style="width:150px;">Documents</th>
                            <th>Submitted</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myApplications as $myApplication)
                            <tr>
                                <td class="fw-bold">{{ $myApplication->application_no }}</td>
                                <td>{{ optional($myApplication->university)->name ?? '-' }}</td>
                                <td>{{ optional($myApplication->universityProfile)->field ?? '-' }}</td>
                                <td><span class="badge bg-info text-capitalize">{{ str_replace('_', ' ', $myApplication->status) }}</span></td>
                                <td>
                                    @php
                                        $docsCount = $myApplication->documents_count ?? 0;
                                        $docsPercent = $totalDocumentTypes > 0 ? min(100, round(($docsCount / $totalDocumentTypes) * 100)) : 0;
                                        $isComplete = $totalDocumentTypes > 0 && $docsCount >= $totalDocumentTypes;
                                    @endphp
                                    <div style="font-size:12px;" class="mb-1">{{ $docsCount }} / {{ $totalDocumentTypes }}</div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $docsPercent }}%;" aria-valuenow="{{ $docsPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    @if ($isComplete)
                                        <span class="badge bg-success mt-1" style="font-size:10px;width:auto;height:auto;border-radius:.25rem;padding:.25em .5em;">Documents Complete</span>
                                    @endif
                                </td>
                                <td>{{ optional($myApplication->submitted_at)->format('Y/m/d') }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('student-portal.applications.show', $myApplication->id) }}" class="btn btn-sm btn-outline-primary">Summary</a>
                                    <a href="{{ route('student-portal.applications.form.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-warning">Form</a>
                                    <a href="{{ route('student-portal.applications.documents.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-success">Documents</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="mb-2">Data not found</div>
                                    <button type="button" id="btnShowRegister" class="btn btn-primary btn-sm">Register</button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif

<script>
(function () {
    const registerUniversities = @json($registerUniversities ?? []);
    const btnShow = document.getElementById('btnShowRegister');
    const panel = document.getElementById('registerFormPanel');
    const btnCancel = document.getElementById('btnCancelRegister');
    const uniSelect = document.getElementById('registerUniversitySelect');
    const profileWrapper = document.getElementById('registerProfileWrapper');
    const profileSelect = document.getElementById('registerProfileSelect');

    if (!panel || !uniSelect || !profileSelect || !profileWrapper) {
        return;
    }

    function populateProfiles(universityId, selectedProfileId) {
        profileSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';

        const university = registerUniversities.find(function (u) { return u.id === universityId; });
        const profiles = university ? (university.profiles || []) : [];

        profiles.forEach(function (profile) {
            const option = document.createElement('option');
            option.value = profile.id;
            option.textContent = profile.degree_title ? (profile.field + ' - ' + profile.degree_title) : profile.field;
            if (selectedProfileId && String(selectedProfileId) === String(profile.id)) {
                option.selected = true;
            }
            profileSelect.appendChild(option);
        });

        profileWrapper.style.display = profiles.length ? '' : 'none';
    }

    if (btnShow) {
        btnShow.addEventListener('click', function () {
            panel.style.display = '';
            panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }

    if (btnCancel) {
        btnCancel.addEventListener('click', function () {
            panel.style.display = 'none';
            uniSelect.value = '';
            profileSelect.innerHTML = '<option value="">-- Pilih Jurusan --</option>';
            profileWrapper.style.display = 'none';
        });
    }

    uniSelect.addEventListener('change', function () {
        populateProfiles(this.value, null);
    });

    // Kalau submit sebelumnya gagal validasi (university_id masih terisi
    // lewat old()), panel-nya sudah otomatis ditampilkan lewat inline style
    // di Blade di atas -- tinggal isi ulang dropdown Jurusan-nya di sini.
    const oldUniversityId = @json(old('university_id'));
    const oldProfileId = @json(old('university_profile_id'));
    if (oldUniversityId) {
        populateProfiles(oldUniversityId, oldProfileId);
    }
})();
</script>

@endsection
