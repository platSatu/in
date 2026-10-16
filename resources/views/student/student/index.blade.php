@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-end align-items-center gap-2">

        <div class="d-flex flex-wrap gap-2">
            {{-- Export ikut filter (search/branch_id/form_id/date) yang lagi aktif
                 di halaman ini — kosongkan filter dulu (klik &times; di sebelah
                 tombol Filter) kalau mau export SEMUA student, atau isi
                 Branch/Form/Tanggal dulu kalau mau export per branch/per
                 form/per tanggal dibuat. File-nya .csv, langsung bisa dibuka di
                 Excel atau di-import ke Google Sheets (menu File > Import di
                 Google Sheets). --}}
            <a href="{{ route('student.student.export', request()->only(['search', 'branch_id', 'form_id', 'date', 'progress'])) }}"
                class="btn btn-outline-success"
                title="Export sesuai filter yang sedang aktif. Kosongkan filter untuk export semua student.">
                <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
            </a>

            <a href="{{ route('student.student.create') }}" class="btn btn-primary">
                + Add Student
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tab utama (13 Oktober 2026): Data Student (seperti sebelumnya) & Progress Student
         (student yang sudah apply, per kampus). --}}
    <ul class="nav nav-tabs mb-3" id="studentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="student-data-tab" data-bs-toggle="tab" data-bs-target="#student-data"
                type="button" role="tab" aria-controls="student-data" aria-selected="true">Data Student</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="student-progress-tab" data-bs-toggle="tab" data-bs-target="#student-progress"
                type="button" role="tab" aria-controls="student-progress" aria-selected="false">
                Progress Student
                <span class="badge bg-secondary ms-1">{{ $applicationsByUniversity->flatten(1)->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content" id="studentTabsContent">
        <div class="tab-pane fade show active" id="student-data" role="tabpanel" aria-labelledby="student-data-tab">

    {{-- === RINGKASAN === --}}
    <div class="row layout-top-spacing g-3 mb-1">
        <div class="col-md-3 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #22c55e;">
                <div class="fs-4 fw-bold text-success">{{ $data->total() }}</div>
                <div class="text-muted small">Total Student</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #f59e0b;">
                <div class="fs-4 fw-bold text-warning">{{ $totalBranches }}</div>
                <div class="text-muted small">Total Branch</div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #6259ca;">
                <div class="fs-4 fw-bold text-primary">{{ $totalForms }}</div>
                <div class="text-muted small">Total Form</div>
            </div>
        </div>

        {{-- Ikut filter Tanggal (input "date" di form filter di bawah): jumlah
             student yang CREATED_AT-nya jatuh tepat di tanggal itu. Tidak ikut
             filter search/branch/form lain -- lihat komentar
             $studentsCreatedOnDate di StudentController::index(). Selama
             filter Tanggal belum diisi, kartu ini tampil "-" (bukan 0), supaya
             tidak disangka "0 student dibuat hari ini". --}}
        <div class="col-md-3 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3" style="border-left: 4px solid #ef4444;">
                <div class="fs-4 fw-bold text-danger">{{ $date ? $studentsCreatedOnDate : '-' }}</div>
                <div class="text-muted small">
                    Student Dibuat
                    @if($date)
                        pada {{ \Carbon\Carbon::parse($date)->translatedFormat('d M Y') }}
                    @else
                        (pilih Tanggal)
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('student.student.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Cari</label>
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Cari nama/email/handphone..."
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- Semua Branch --</option>
                                @foreach ($companyBranches as $companyBranch)
                                    <option value="{{ $companyBranch->id }}" {{ $branchId == $companyBranch->id ? 'selected' : '' }}>
                                        {{ $companyBranch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Form</label>
                            <select name="form_id" class="form-select">
                                <option value="">-- Semua Form --</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}" {{ $formId == $form->id ? 'selected' : '' }}>
                                        {{ $form->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Tanggal Dibuat</label>
                            <input
                                type="date"
                                name="date"
                                class="form-control"
                                value="{{ $date }}">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Progress</label>
                            <select name="progress" class="form-select">
                                <option value="">-- Semua Progress --</option>
                                <option value="-" @selected($progress === '-')>Belum diisi</option>
                                @foreach (\App\Models\Student::PROGRESS_LABELS as $value => $label)
                                    <option value="{{ $value }}" @selected($progress === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 d-flex gap-2">
                            {{-- Padding vertikal disamakan manual dengan .form-control/.form-select
                                 (padding: 0.75rem 1.25rem di main.css) karena style .btn bawaan tema
                                 cuma punya padding 0.4375rem, jadi tanpa ini tombolnya kelihatan lebih
                                 pendek/kecil dibanding input & select di sebelahnya. --}}
                            <div class="w-100">
                                <button class="btn btn-outline-primary w-100" type="submit" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
                                    Filter
                                </button>
                            </div>

                            @if(request('search') || request('branch_id') || request('form_id') || request('date') || request('progress'))
                                <a href="{{ route('student.student.index') }}" class="btn btn-outline-danger" title="Reset filter" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
                                    &times;
                                </a>
                            @endif
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Foto</th>
                                <th>Nama</th>
                                <th class="text-nowrap">Handphone</th>
                                <th class="text-nowrap">Branch</th>
                                <th>Form</th>
                                {{-- Kode Sales disembunyikan (1 Oktober 2026): sudah terwakili kolom Sales Ditugaskan. --}}
                                {{-- <th class="text-nowrap">Kode Sales</th> --}}
                                <th class="text-nowrap">Sales Ditugaskan</th>
                                <th class="text-nowrap">Pembayaran</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Progress</th>
                                <th class="text-nowrap">Akun Login</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($data as $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $loop->index }}</td>
                                    <td>
                                        @if($item->images)
                                            <img src="{{ asset($item->images) }}" alt="{{ $item->first_name }}" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold">{{ $item->first_name }} {{ $item->last_name }}</div>
                                        <div class="small text-muted">{{ $item->email }}</div>
                                    </td>
                                    <td class="text-nowrap">
                                        {{-- Klik = buka WhatsApp + progress kosong/Belum di-FU jadi Sudah di-FU (lihat script di bawah). --}}
                                        @if ($item->whatsappNumber())
                                            <a href="https://wa.me/{{ $item->whatsappNumber() }}" target="_blank" rel="noopener"
                                                class="js-wa-link btn btn-sm btn-outline-success text-nowrap" title="Chat WhatsApp"
                                                @if ($canEdit) data-followed-up-url="{{ route('student.student.followed-up', $item->id) }}" @endif
                                                data-student-id="{{ $item->id }}"><i class="bi bi-whatsapp me-1"></i>{{ $item->handphone }}</a>
                                        @else
                                            {{ $item->handphone ?: '-' }}
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $item->companyBranch->name ?? '-' }}</td>
                                    <td>{{ $item->form->name ?? '-' }}</td>
                                    {{-- <td class="text-nowrap">{{ $item->sales_id ?? '-' }}</td> --}}
                                    <td class="text-nowrap">
                                        @if ($item->handledBy)
                                            {{ $item->handledBy->name }}
                                            @if ($item->handledBy->sales_code)
                                                <div class="small text-muted">{{ $item->handledBy->sales_code }}</div>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @php
                                            // Ambil submission TERBARU student ini (sudah di-eager-load & diurutkan
                                            // di StudentController::index()) beserta payment-nya kalau ada.
                                            $latestSubmission = $item->formSubmissions->first();
                                            $payment = $latestSubmission->payment ?? null;
                                        @endphp
                                        @if(!$item->form || !$item->form->requires_payment)
                                            <span class="text-muted">Gratis</span>
                                        @elseif($payment)
                                            <span class="badge {{ $payment->status === 'paid' ? 'bg-success' : ($payment->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger') }} mb-1">
                                                {{ ucfirst($payment->status) }}
                                            </span>
                                            <div class="small text-muted">{{ $payment->order_id }}</div>
                                            <div class="small">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
                                            @if($payment->paid_at)
                                                <div class="small text-muted">{{ $payment->paid_at->format('d M Y, H:i') }}</div>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary">Belum Bayar</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <span class="badge {{ $item->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                            {{ ucfirst($item->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm js-progress" style="min-width: 11rem;"
                                            data-student-id="{{ $item->id }}"
                                            data-url="{{ route('student.student.progress', $item->id) }}"
                                            @disabled(! $canEdit)>
                                            <option value="">-</option>
                                            @foreach (\App\Models\Student::PROGRESS_LABELS as $value => $label)
                                                <option value="{{ $value }}" @selected($item->progress_student === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-nowrap">
                                        @if($item->user_id)
                                            <span class="badge bg-success">Sudah Terdaftar</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Belum Ada</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('student.student.show', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap flex-shrink-0">Detail</a>

                                            <a href="{{ route('student.student.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0">Edit</a>

                                            {{-- Fix (15 September 2026, permintaan user): tombol langsung ke
                                                 progress Formulir + Upload Dokumen InaStudy student ini (halaman
                                                 admin quiz.university-application.show, dipanggil pakai id
                                                 aplikasi -- lihat $item->applications di
                                                 StudentController::index()). Kalau student belum pernah Register
                                                 InaStudy (belum ada baris UniversityApplication sama sekali),
                                                 tombolnya nonaktif -- belum ada halaman yang bisa dituju. Sales
                                                 (scope 'self') otomatis cuma bisa buka aplikasi student yang dia
                                                 tangani sendiri, lihat guard di
                                                 Quiz\UniversityApplicationController::assertVisibleApplication();
                                                 role-nya juga HARUS diberi akses "Aplikasi Kuliah" dulu lewat
                                                 halaman Roles supaya tombol ini tidak 403. --}}
                                            @php $latestApplication = $item->applications->first(); @endphp
                                            @if ($latestApplication)
                                                <a href="{{ route('quiz.university-application.show', $latestApplication->id) }}"
                                                    class="btn btn-sm btn-outline-info text-nowrap flex-shrink-0">Progress InaStudy</a>
                                            @elseif ($canEdit)
                                                {{-- Belum punya aplikasi: admin bisa daftarkan langsung tanpa pembayaran (popup #addInaStudyModal). --}}
                                                <button type="button" class="btn btn-sm btn-outline-info text-nowrap flex-shrink-0"
                                                    data-bs-toggle="modal" data-bs-target="#addInaStudyModal"
                                                    data-add-url="{{ route('student.student.add-to-inastudy', $item->id) }}"
                                                    data-student-name="{{ $item->first_name }} {{ $item->last_name }}"
                                                    data-student-whatsapp="{{ $item->handphone }}">Add to InaStudy</button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-info text-nowrap flex-shrink-0" disabled title="Student ini belum Register InaStudy">Progress InaStudy</button>
                                            @endif

                                            @unless($item->user_id)
                                                {{--
                                                    FIX (permintaan user, 16 September 2026): dulu password
                                                    akun login-nya di-generate acak (Str::random) begitu
                                                    tombol ini diklik, terus ditampilkan sekali lewat flash
                                                    message. Sekarang admin isi sendiri password-nya lewat 1
                                                    modal yang dipakai bersama (BUKAN 1 modal per baris lagi
                                                    -- versi awal sempat begitu, tapi tampilannya jadi rusak
                                                    karena modal-nya ke-nest terlalu dalam di struktur flex
                                                    tabel, jadi ganti ke 1 modal + JS isi action/nama
                                                    dinamis, lihat script #addUserModal di bawah tabel) --
                                                    yang lain (status langsung active, email langsung
                                                    terverifikasi) TIDAK berubah, lihat
                                                    StudentController::addUser().
                                                --}}
                                                <button type="button" class="btn btn-sm btn-outline-success text-nowrap flex-shrink-0 js-add-user-btn"
                                                    data-bs-toggle="modal" data-bs-target="#addUserModal"
                                                    data-add-user-url="{{ route('student.student.add-user', $item->id) }}"
                                                    data-student-name="{{ $item->first_name }} {{ $item->last_name }}">+ User</button>
                                            @endunless

                                            <form action="{{ route('student.student.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus data student ini?');" class="m-0 flex-shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center">Belum ada data.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

        </div>

        {{-- Tab 2: Progress Student -- 1 tab per kampus, isinya student yang sudah apply ke kampus itu. --}}
        <div class="tab-pane fade" id="student-progress" role="tabpanel" aria-labelledby="student-progress-tab">
            <div class="row layout-top-spacing">
                <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                    <div class="widget-content widget-content-area br-8">
                        @if ($applicationsByUniversity->isEmpty())
                            <p class="text-center text-muted py-4 mb-0">Belum ada student yang sudah apply (Registration Fee lunas) ke university.</p>
                        @else
                            <ul class="nav nav-pills flex-nowrap overflow-auto mb-3 pb-1" id="universityTabs" role="tablist">
                                @foreach ($applicationsByUniversity as $universityId => $applications)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link text-nowrap @if ($loop->first) active @endif" id="university-tab-{{ $loop->index }}"
                                            data-bs-toggle="tab" data-bs-target="#university-pane-{{ $loop->index }}" type="button" role="tab"
                                            aria-controls="university-pane-{{ $loop->index }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                            {{ $applications->first()->university->name ?? 'University (sudah dihapus)' }}
                                            <span class="badge rounded-pill bg-danger ms-1">{{ $applications->count() }}</span>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="tab-content">
                                @foreach ($applicationsByUniversity as $universityId => $applications)
                                    <div class="tab-pane fade @if ($loop->first) show active @endif" id="university-pane-{{ $loop->index }}"
                                        role="tabpanel" aria-labelledby="university-tab-{{ $loop->index }}">
                                        <div class="table-responsive">
                                            <table class="table dt-table-hover align-middle" style="width:100%">
                                                <thead>
                                                    <tr>
                                                        <th>No</th>
                                                        <th class="text-nowrap">Application No</th>
                                                        <th>Student</th>
                                                        <th class="text-nowrap">Name Passport</th>
                                                        <th class="text-nowrap">Passport Number</th>
                                                        <th>Major</th>
                                                        <th>Intake</th>
                                                        <th>Status</th>
                                                        <th>Fee</th>
                                                        <th class="text-nowrap">Status Fee</th>
                                                        <th style="width:140px;">Documents</th>
                                                        <th class="text-nowrap">Sales</th>
                                                        <th>Submitted</th>
                                                        <th class="no-content text-center">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($applications as $application)
                                                        @php
                                                            $docsCount = $application->documents_count ?? 0;
                                                            $docsPercent = $totalDocumentTypes > 0 ? min(100, round(($docsCount / $totalDocumentTypes) * 100)) : 0;
                                                            $docsComplete = $totalDocumentTypes > 0 && $docsCount >= $totalDocumentTypes;

                                                            // Fee: Registration (sudah lunas -- syarat masuk tab ini) & Departure
                                                            // (nominal diisi admin, deposit_fee_china_amount). Status dari
                                                            // pembayaran terbaru per jenis (payments sudah urut terbaru dulu).
                                                            $latestPayments = $application->payments->unique('purpose')->keyBy('purpose');
                                                            $registrationPayment = $latestPayments->get(\App\Models\ApplicationPayment::PURPOSE_REGISTRATION_FEE);
                                                            $departurePayment = $latestPayments->get(\App\Models\ApplicationPayment::PURPOSE_DEPARTURE_FEE);
                                                            $feeRows = [
                                                                'Registration' => [
                                                                    'amount' => $registrationPayment->amount ?? $application->registration_fee_amount,
                                                                    'payment' => $registrationPayment,
                                                                ],
                                                                'Departure' => [
                                                                    'amount' => $departurePayment->amount ?? $application->deposit_fee_china_amount,
                                                                    'payment' => $departurePayment,
                                                                ],
                                                            ];
                                                            $feeStatusLabels = [
                                                                'paid' => ['Lunas', 'bg-success'],
                                                                'pending' => ['Menunggu', 'bg-warning text-dark'],
                                                                'failed' => ['Gagal', 'bg-danger'],
                                                                'expired' => ['Kedaluwarsa', 'bg-secondary'],
                                                            ];
                                                        @endphp
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            {{-- Application No diisi manual admin di data Student (students.application_no). --}}
                                                            <td class="text-nowrap">
                                                                <span class="fw-bold">{{ $application->student?->application_no ?: '-' }}</span>
                                                                <div class="small text-muted" title="Nomor otomatis sistem">{{ $application->application_no }}</div>
                                                            </td>
                                                            <td>
                                                                @if ($application->student)
                                                                    <a href="{{ route('student.student.show', $application->student->id) }}" class="fw-bold">
                                                                        {{ trim($application->student->first_name . ' ' . $application->student->last_name) }}
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                                <div class="small text-muted">{{ $application->whatsapp }}</div>
                                                            </td>
                                                            <td>{{ $application->student?->name_pasport ?: '-' }}</td>
                                                            <td class="text-nowrap">{{ $application->student?->number_pasport ?: '-' }}</td>
                                                            <td>{{ $application->major_label }}</td>
                                                            <td class="text-nowrap">{{ $application->intake ?: '-' }} {{ $application->intake_year }}</td>
                                                            <td>
                                                                <span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $application->status) }}</span>
                                                                @if ($application->admission_status)
                                                                    <div class="small text-muted text-capitalize mt-1">{{ str_replace('_', ' ', $application->admission_status) }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="text-nowrap">
                                                                @foreach ($feeRows as $feeLabel => $fee)
                                                                    <div class="small">
                                                                        <span class="text-muted">{{ $feeLabel }}:</span>
                                                                        {{ $fee['amount'] !== null ? 'Rp ' . number_format((float) $fee['amount'], 0, ',', '.') : '-' }}
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                            <td class="text-nowrap">
                                                                @foreach ($feeRows as $feeLabel => $fee)
                                                                    @php
                                                                        [$feeStatusText, $feeStatusClass] = $fee['payment']
                                                                            ? ($feeStatusLabels[$fee['payment']->status] ?? [ucfirst($fee['payment']->status), 'bg-secondary'])
                                                                            : ($fee['amount'] !== null ? ['Belum Bayar', 'bg-light text-dark'] : ['Belum ditagih', 'bg-light text-muted']);
                                                                    @endphp
                                                                    <div class="small mb-1">
                                                                        <span class="text-muted">{{ $feeLabel }}:</span>
                                                                        <span class="badge {{ $feeStatusClass }}">{{ $feeStatusText }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </td>
                                                            <td>
                                                                <div class="small mb-1">{{ $docsCount }} / {{ $totalDocumentTypes }}</div>
                                                                <div class="progress" style="height:6px;">
                                                                    <div class="progress-bar {{ $docsComplete ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $docsPercent }}%;"></div>
                                                                </div>
                                                                @if ($totalInaDocumentTypes > 0)
                                                                    {{-- Dokumen dari INA (Offer Letter, JW, VISA) dihitung terpisah. --}}
                                                                    <div class="small text-muted mt-1">INA: {{ $application->ina_documents_count ?? 0 }} / {{ $totalInaDocumentTypes }}</div>
                                                                @endif
                                                            </td>
                                                            <td class="text-nowrap">{{ $application->handledBy->name ?? '-' }}</td>
                                                            <td class="text-nowrap">{{ $application->submitted_at?->format('Y/m/d') ?? '-' }}</td>
                                                            <td class="text-center">
                                                                <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                                                    <a href="{{ route('quiz.university-application.show', $application->id) }}"
                                                                        class="btn btn-sm btn-outline-info text-nowrap">Progress InaStudy</a>
                                                                    @if ($canEdit)
                                                                        {{-- Lepas status apply (data uji coba): aplikasi jadi Cancelled, data student tetap. --}}
                                                                        <form action="{{ route('student.student.cancel-application', $application->id) }}" method="POST" class="m-0"
                                                                            onsubmit="return confirm('Cancel apply {{ addslashes(trim(($application->student->first_name ?? '') . ' ' . ($application->student->last_name ?? ''))) }}? Aplikasinya ditandai Cancelled dan hilang dari tab ini. Data student tetap ada di tab Data Student.');">
                                                                            @csrf
                                                                            @method('PATCH')
                                                                            <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Cancel Apply</button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Buka tab Progress Student kalau URL berakhiran #progress, dan ingat tab saat pindah.
        document.addEventListener('DOMContentLoaded', function () {
            var progressTab = document.getElementById('student-progress-tab');
            var dataTab = document.getElementById('student-data-tab');
            if (!progressTab || !window.bootstrap) return;
            if (window.location.hash === '#progress') {
                bootstrap.Tab.getOrCreateInstance(progressTab).show();
            }
            progressTab.addEventListener('shown.bs.tab', function () {
                history.replaceState(null, '', window.location.pathname + window.location.search + '#progress');
            });
            dataTab.addEventListener('shown.bs.tab', function () {
                history.replaceState(null, '', window.location.pathname + window.location.search);
            });
        });
    </script>

    {{--
        FIX (permintaan user, 16 September 2026): 1 modal saja dipakai
        bersama untuk semua tombol "+ User" di tabel (bukan 1 modal
        per-baris) -- action form & nama student-nya diisi lewat JS
        (event show.bs.modal) tiap kali modal ini dibuka, dibaca dari
        data-add-user-url/data-student-name di tombol yang diklik (lihat
        class js-add-user-btn di atas). Ini bikin markup jauh lebih ringan
        (1 modal vs bisa puluhan tergantung jumlah baris per halaman) dan
        menghindari modal ke-nest terlalu dalam di struktur flex tabel yang
        bikin tampilannya rusak di percobaan pertama.
    --}}
    <div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content text-start">
                <form id="addUserForm" method="POST" action="">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title mb-0">Buat Akun Login untuk <span id="addUserStudentName"></span></h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Akun ini langsung aktif dan email-nya otomatis dianggap
                            terverifikasi, jadi <span id="addUserStudentNameInline"></span>
                            bisa langsung login pakai email &amp; password ini.
                        </p>
                        <label class="form-label">Password</label>
                        <input type="password" name="password" id="addUserPasswordInput" class="form-control"
                            minlength="8" required placeholder="Minimal 8 karakter">
                        <div class="form-text">Sampaikan password ini ke student secara manual (WhatsApp/email) -- sistem tidak mengirimkannya otomatis.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success btn-sm">Buat Akun</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('addUserModal');
            if (!modal) return;

            modal.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger) return;

                var form = document.getElementById('addUserForm');
                var nameEl = document.getElementById('addUserStudentName');
                var nameInlineEl = document.getElementById('addUserStudentNameInline');
                var passwordInput = document.getElementById('addUserPasswordInput');

                var studentName = trigger.getAttribute('data-student-name') || '';

                form.setAttribute('action', trigger.getAttribute('data-add-user-url') || '');
                nameEl.textContent = studentName;
                nameInlineEl.textContent = studentName;
                passwordInput.value = '';
            });
        })();
    </script>

    @if ($canEdit)
        {{-- Popup "Add to InaStudy": 1 modal dipakai bersama, action & nama diisi JS saat dibuka.
             Aturan sama dengan Register manual di menu InaStudy siswa (tanpa pembayaran). --}}
        <div class="modal fade" id="addInaStudyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content text-start">
                    <form id="addInaStudyForm" method="POST" action="">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title mb-0">Add to InaStudy: <span id="addInaStudyStudentName"></span></h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small mb-3">
                                Aplikasi Kuliah dibuat tanpa Registration Fee, sama seperti Register manual di menu InaStudy.
                                Setelah itu formulir &amp; dokumen bisa dibantu isi lewat tombol Progress InaStudy.
                            </p>
                            @include('partials.inastudy-register-fields', [
                                'prefix' => 'addInaStudy',
                                'universities' => $registerUniversities,
                                'degreeOrder' => $registerDegreeOrder,
                                'withOld' => false,
                            ])
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-info btn-sm">Daftarkan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <script>
        (function () {
            const csrf = @json(csrf_token());

            function send(url, method, body) {
                return fetch(url, {
                    method: method,
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(body || {}),
                    keepalive: true,
                }).then(function (response) {
                    if (!response.ok) throw new Error(response.status);
                    return response.json();
                });
            }

            function setProgress(studentId, value) {
                const select = document.querySelector('.js-progress[data-student-id="' + studentId + '"]');
                if (select) {
                    select.value = value || '';
                    select.dataset.saved = select.value;
                }
            }

            // Dropdown Progress: simpan langsung, kembalikan pilihan lama kalau gagal.
            document.querySelectorAll('.js-progress').forEach(function (select) {
                select.dataset.saved = select.value;
                select.addEventListener('change', function () {
                    select.disabled = true;
                    send(select.dataset.url, 'PATCH', { progress_student: select.value || null })
                        .then(function (data) { setProgress(select.dataset.studentId, data.progress_student); })
                        .catch(function () {
                            select.value = select.dataset.saved;
                            alert('Progress gagal disimpan. Silakan coba lagi.');
                        })
                        .finally(function () { select.disabled = false; });
                });
            });

            // Klik nomor HP: WhatsApp tetap terbuka (link biasa), status dicatat di belakang.
            document.querySelectorAll('.js-wa-link[data-followed-up-url]').forEach(function (link) {
                link.addEventListener('click', function () {
                    send(link.dataset.followedUpUrl, 'POST')
                        .then(function (data) { setProgress(link.dataset.studentId, data.progress_student); })
                        .catch(function () {});
                });
            });

            const modal = document.getElementById('addInaStudyModal');
            if (modal) {
                modal.addEventListener('show.bs.modal', function (event) {
                    const trigger = event.relatedTarget;
                    if (!trigger) return;
                    const form = document.getElementById('addInaStudyForm');
                    form.reset();
                    form.setAttribute('action', trigger.getAttribute('data-add-url') || '');
                    document.getElementById('addInaStudyStudentName').textContent = trigger.getAttribute('data-student-name') || '';
                    document.getElementById('addInaStudyWhatsapp').value = trigger.getAttribute('data-student-whatsapp') || '';
                });
            }
        })();
    </script>

</div>

@endsection
