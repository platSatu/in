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
                {{--
                    FIX v2 (permintaan user, 16 September 2026): disamakan
                    dengan alur Apply dari halaman publik
                    (frontend.university-profile / ApplyController) yang
                    sekarang 3 langkah -- pilih Kampus, lalu Degree-nya
                    kekuar, lalu Jurusan (Course)-nya keluar. Dulu di sini
                    cuma 2 langkah (Universitas -> Jurusan langsung ke level
                    Major/UniversityProfile). "Degree" & "Jurusan" di bawah
                    di-populate dinamis lewat JS (lihat script di bawah),
                    sama polanya dengan dropdown Jurusan versi lama.
                --}}
                <form method="POST" action="{{ route('inastudy.register') }}" id="registerForm">
                    @csrf
                    @include('partials.inastudy-register-fields', [
                        'prefix' => 'register',
                        'universities' => $registerUniversities,
                        'degreeOrder' => $registerDegreeOrder,
                        'defaultWhatsapp' => $defaultWhatsapp,
                    ])
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
                                <td>{{ $myApplication->major_label }}</td>
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
    // Dropdown Universitas -> Degree -> Jurusan ada di partial
    // partials.inastudy-register-fields; di sini cuma buka/tutup panel.
    const panel = document.getElementById('registerFormPanel');
    const form = document.getElementById('registerForm');
    const btnShow = document.getElementById('btnShowRegister');
    const btnCancel = document.getElementById('btnCancelRegister');

    if (!panel || !form) {
        return;
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
            form.reset();
        });
    }
})();
</script>

@endsection
