@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <ul class="mb-0 ps-3">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.university-application.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search application no / student name / email / phone..." value="{{ $search }}">
                        </div>
                        <div class="col-md-2">
                            <select name="university_id" class="form-select">
                                <option value="">All Universities</option>
                                @foreach ($universities as $university)
                                    <option value="{{ $university->id }}" {{ (string) $universityId === (string) $university->id ? 'selected' : '' }}>
                                        {{ $university->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="intake_year" class="form-select">
                                <option value="">All Intake Years</option>
                                @foreach ($intakeYears as $year)
                                    <option value="{{ $year }}" {{ (string) $intakeYear === (string) $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                @foreach ($statuses as $statusKey => $statusLabel)
                                    <option value="{{ $statusKey }}" {{ $status === $statusKey ? 'selected' : '' }}>
                                        {{ $statusLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            {{-- 18 November 2026: aplikasi yang baru dibayar DP dan masih ada sisa Registration Fee. --}}
                            <select name="balance" class="form-select">
                                <option value="">Semua Pembayaran</option>
                                <option value="outstanding" {{ $balance === 'outstanding' ? 'selected' : '' }}>Masih ada sisa (DP)</option>
                            </select>
                        </div>
                        <div class="col-md-1 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Filter</button>
                        </div>
                    </form>
                </div>

                {{-- Pengingat sisa Registration Fee (18 November 2026). --}}
                @if ($outstandingCount > 0 && $balance !== 'outstanding')
                    <div class="alert alert-light-warning d-flex flex-wrap align-items-center gap-2 mb-3" style="background:#fff8e6; border:1px solid #f5d6b8;">
                        <span><strong>{{ $outstandingCount }} aplikasi</strong> baru dibayar DP dan masih ada sisa Registration Fee.</span>
                        <a href="{{ route('quiz.university-application.index', ['balance' => 'outstanding']) }}" class="btn btn-sm btn-warning ms-auto">Lihat &amp; tagih</a>
                    </div>
                @elseif ($balance === 'outstanding')
                    <div class="small text-muted mb-3">Menampilkan aplikasi yang sudah bayar DP tapi belum lunas. Tagih sisanya lewat tombol WhatsApp di kolom Progress.</div>
                @endif

                {{-- Batal massal (22 Oktober 2026): yang sudah ada pembayaran otomatis dilewati. --}}
                @if ($canEdit)
                    <form method="POST" action="{{ route('quiz.university-application.cancel-many') }}" id="cancelManyForm" class="d-flex flex-wrap gap-2 align-items-center mb-3"
                        onsubmit="return document.querySelectorAll('.js-app-check:checked').length ? confirm('Batalkan semua aplikasi yang dicentang?') : (alert('Centang dulu aplikasi yang mau dibatalkan.'), false);">
                        @csrf
                        <input type="text" name="cancel_reason" class="form-control form-control-sm" style="max-width:320px;" maxlength="255" required placeholder="Alasan batal, mis. apply coba-coba">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Batalkan yang dicentang</button>
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                @if ($canEdit) <th style="width:32px;"><input type="checkbox" class="form-check-input" onclick="document.querySelectorAll('.js-app-check').forEach(c => c.checked = this.checked)"></th> @endif
                                <th>No</th>
                                <th>Application No</th>
                                <th>Student</th>
                                <th>University</th>
                                <th>Major</th>
                                <th>Intake</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th style="width:140px;">Documents</th>
                                <th>Submitted</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    @if ($canEdit)
                                        <td>
                                            {{-- Yang sudah bayar tidak bisa dibatalkan dari sini, jadi tidak bisa dicentang. --}}
                                            @unless ($item->status === \App\Models\UniversityApplication::STATUS_CANCELLED || $item->fee_paid)
                                                <input type="checkbox" name="ids[]" value="{{ $item->id }}" form="cancelManyForm" class="form-check-input js-app-check">
                                            @endunless
                                        </td>
                                    @endif
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">{{ $item->application_no }}</td>
                                    <td>
                                        {{ trim(($item->student->first_name ?? '') . ' ' . ($item->student->last_name ?? '')) ?: '-' }}
                                        <div class="text-muted" style="font-size:12px;">{{ $item->whatsapp }}</div>
                                        @if (($campusCounts[$item->student_id] ?? 0) > 1)
                                            {{-- Apply beberapa kampus: lihat semua kampusnya di halaman detail. --}}
                                            <span class="badge badge-warning mt-1" title="Siswa ini apply {{ $campusCounts[$item->student_id] }} kampus">{{ $campusCounts[$item->student_id] }} kampus</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->university)->name ?? '-' }}</td>
                                    <td>{{ $item->major_label }}</td>
                                    <td>{{ $item->intake ?: '-' }} {{ $item->intake_year }}</td>
                                    <td>
                                        <span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $item->status) }}</span>
                                    </td>
                                    <td class="text-nowrap" style="font-size:12px;">
                                        @php $feeRemaining = $item->fee_paid && $item->registration_fee_amount ? max(0, (int) $item->registration_fee_amount - (int) $item->fee_paid_amount) : 0; @endphp
                                        @if ($item->fee_paid && $feeRemaining > 0)
                                            {{-- DP 50% (18 November 2026): sisa & tombol tagih via WhatsApp, sama dengan di halaman detail. --}}
                                            <span class="badge badge-warning">DP</span>
                                            <div class="mt-1">Sisa <strong>Rp {{ number_format($feeRemaining, 0, ',', '.') }}</strong></div>
                                            @if ($canEdit && $item->status !== \App\Models\UniversityApplication::STATUS_CANCELLED && ! $item->isAwaitingUpgradePayment())
                                                <form method="POST" action="{{ route('quiz.university-application.fees.update', $item->id) }}" class="mt-1">
                                                    @csrf
                                                    @if ($item->registration_fee_due)
                                                        <div class="text-muted">Tagihan aktif</div>
                                                        <button type="submit" name="balance" value="remind" class="btn btn-success btn-sm py-0 px-2 mt-1" style="font-size:11px;"
                                                            onclick="return confirm('Kirim ulang link pelunasan ke WhatsApp siswa?')"><i class="bi bi-whatsapp"></i> Kirim ulang</button>
                                                    @else
                                                        <input type="hidden" name="notify_wa" value="1">
                                                        <button type="submit" name="balance" value="send" class="btn btn-success btn-sm py-0 px-2" style="font-size:11px;"
                                                            onclick="return confirm('Tagih sisa Rp {{ number_format($feeRemaining, 0, ',', '.') }} dan kirim link bayarnya ke WhatsApp siswa?')"><i class="bi bi-whatsapp"></i> Tagih via WA</button>
                                                    @endif
                                                </form>
                                            @endif
                                        @elseif ($item->fee_paid)
                                            <span class="badge badge-success">Sudah bayar</span>
                                        @else
                                            <span class="badge bg-light text-dark border">Belum bayar</span>
                                        @endif
                                        <div class="mt-1 {{ $item->form_submitted ? 'text-success' : 'text-muted' }}">
                                            {{ $item->form_submitted ? 'Formulir terkirim' : 'Formulir belum diisi' }}
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $docsCount = $item->documents_count ?? 0;
                                            $totalDocumentTypes = $item->documentTypes()->count(); // per kampus (12 November 2026)
                                            $docsPercent = $totalDocumentTypes > 0 ? min(100, round(($docsCount / $totalDocumentTypes) * 100)) : 0;
                                            $isComplete = $totalDocumentTypes > 0 && $docsCount >= $totalDocumentTypes;
                                        @endphp
                                        <div style="font-size:12px;" class="mb-1">{{ $docsCount }} / {{ $totalDocumentTypes }}</div>
                                        <div class="progress" style="height:6px;">
                                            <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $docsPercent }}%;" aria-valuenow="{{ $docsPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        @if ($isComplete)
                                            <span class="badge badge-success mt-1" style="font-size:10px;">Documents Complete</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($item->submitted_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('quiz.university-application.show', $item->id) }}" class="btn btn-sm btn-outline-primary text-nowrap">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $canEdit ? 12 : 11 }}" class="text-center">{{ $balance === 'outstanding' ? 'Tidak ada aplikasi yang masih punya sisa Registration Fee.' : 'Belum ada aplikasi kuliah masuk.' }}</td>
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

@endsection
