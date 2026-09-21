@extends('layouts.frontend')

@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h4 class="mb-0">Data Student</h4>

        <div class="d-flex flex-wrap gap-2">
            {{-- Export ikut filter (search/branch_id/form_id/date) yang lagi aktif
                 di halaman ini — kosongkan filter dulu (klik &times; di sebelah
                 tombol Filter) kalau mau export SEMUA student, atau isi
                 Branch/Form/Tanggal dulu kalau mau export per branch/per
                 form/per tanggal dibuat. File-nya .csv, langsung bisa dibuka di
                 Excel atau di-import ke Google Sheets (menu File > Import di
                 Google Sheets). --}}
            <a href="{{ route('student.student.export', request()->only(['search', 'branch_id', 'form_id', 'date'])) }}"
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
                        <div class="col-md-3">
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

                        <div class="col-md-3 d-flex gap-2">
                            {{-- Padding vertikal disamakan manual dengan .form-control/.form-select
                                 (padding: 0.75rem 1.25rem di main.css) karena style .btn bawaan tema
                                 cuma punya padding 0.4375rem, jadi tanpa ini tombolnya kelihatan lebih
                                 pendek/kecil dibanding input & select di sebelahnya. --}}
                            <div class="w-100">
                                <button class="btn btn-outline-primary w-100" type="submit" style="padding-top: 0.75rem; padding-bottom: 0.75rem;">
                                    Filter
                                </button>
                            </div>

                            @if(request('search') || request('branch_id') || request('form_id') || request('date'))
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
                                <th>Email</th>
                                <th class="text-nowrap">Handphone</th>
                                <th class="text-nowrap">Branch</th>
                                <th>Form</th>
                                <th class="text-nowrap">Kode Sales</th>
                                <th class="text-nowrap">Sales Ditugaskan</th>
                                <th class="text-nowrap">Pembayaran</th>
                                <th class="text-nowrap">Status</th>
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
                                    <td class="fw-bold">{{ $item->first_name }} {{ $item->last_name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td class="text-nowrap">{{ $item->handphone }}</td>
                                    <td class="text-nowrap">{{ $item->companyBranch->name ?? '-' }}</td>
                                    <td>{{ $item->form->name ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $item->sales_id ?? '-' }}</td>
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
                                    <td colspan="13" class="text-center">Belum ada data.</td>
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

</div>

@endsection
