@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <a href="{{ route('quiz.university-application.index') }}" class="btn btn-outline-secondary">&laquo; Back to List</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <h5 class="fw-bold mb-3">{{ $application->application_no }}</h5>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0" style="font-size:14px;">
                            <tr>
                                <td class="text-muted" style="width:180px;">Student</td>
                                <td class="fw-bold">{{ trim(($application->student->first_name ?? '') . ' ' . ($application->student->last_name ?? '')) ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Email</td>
                                <td>{{ $application->student->email ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">WhatsApp</td>
                                <td>{{ $application->whatsapp ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Handled By</td>
                                <td>{{ optional($application->handledBy)->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0" style="font-size:14px;">
                            <tr>
                                <td class="text-muted" style="width:180px;">University</td>
                                <td class="fw-bold">{{ optional($application->university)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Major</td>
                                <td>{{ optional($application->universityProfile)->field ?? '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Degree / Intake / Duration</td>
                                <td>{{ collect([$application->degree, $application->intake . ' ' . $application->intake_year, $application->duration])->filter()->implode(' - ') }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td><span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $application->status) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Admission Status</td>
                                <td>
                                    @if ($application->admission_status === 'accepted')
                                        <span class="badge badge-success text-capitalize">Accepted</span>
                                    @elseif ($application->admission_status === 'processing')
                                        <span class="badge badge-warning text-capitalize">Processing</span>
                                    @elseif ($application->admission_status === 'under_review')
                                        <span class="badge badge-secondary text-capitalize">Under Review</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif

                                    {{--
                                        FASE 4 -- form kecil admin ubah admission_status manual.
                                        "Under Review" SENGAJA tidak ada di pilihan -- itu hanya
                                        diset otomatis oleh webhook (lihat
                                        FormPaymentController::onApplicationPaymentPaid()).
                                    --}}
                                    <form method="POST" action="{{ route('quiz.university-application.admission-status.update', $application->id) }}" class="d-flex gap-1 mt-1">
                                        @csrf
                                        <select name="admission_status" class="form-select form-select-sm" style="max-width:180px;" onchange="this.form.submit()">
                                            <option value="">- Belum diatur -</option>
                                            <option value="under_review" {{ $application->admission_status === 'under_review' ? 'selected' : '' }}>Under Review</option>
                                            <option value="processing" {{ $application->admission_status === 'processing' ? 'selected' : '' }}>Processing</option>
                                            <option value="accepted" {{ $application->admission_status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submitted</td>
                                <td>{{ optional($application->submitted_at)->format('Y/m/d H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <hr>

                {{--
                    FASE 2 (Alur Pembayaran 2 Arah Apply Kampus, 10 September
                    2026) -- Registration Fee & Departure Fee KEDUANYA diisi
                    manual oleh admin per-aplikasi lewat form ini (lihat
                    ApplyController::store(), yang sengaja tidak lagi
                    mengisi registration_fee_amount otomatis dari data
                    kampus/major). Departure Fee REUSE kolom
                    deposit_fee_china_amount yang sudah ada sebelumnya --
                    lihat migration add_admission_status_to_university_
                    applications_table untuk penjelasan lengkapnya.
                --}}
                <h6 class="fw-bold mb-3">Payment</h6>

                <form method="POST" action="{{ route('quiz.university-application.fees.update', $application->id) }}" class="row g-3 mb-4">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:13px;">Registration Fee (Rp)</label>
                        <input type="number" min="0" step="1" name="registration_fee_amount" class="form-control"
                            value="{{ old('registration_fee_amount', $application->registration_fee_amount) }}"
                            placeholder="Belum diatur">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-size:13px;">Departure Fee (Rp)</label>
                        <input type="number" min="0" step="1" name="deposit_fee_china_amount" class="form-control"
                            value="{{ old('deposit_fee_china_amount', $application->deposit_fee_china_amount) }}"
                            placeholder="Belum diatur">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm">Save Payment Amounts</button>
                    </div>
                </form>

                @if ($payments->isNotEmpty())
                    <div class="table-responsive mb-2">
                        <table class="table table-bordered align-middle" style="font-size:13px;">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Gateway</th>
                                    <th>Status</th>
                                    <th>Paid At</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($payments as $payment)
                                    <tr>
                                        <td>{{ $payment->order_id }}</td>
                                        <td class="text-capitalize">{{ str_replace('_', ' ', $payment->purpose) }}</td>
                                        <td>{{ number_format($payment->amount, 0, ',', '.') }}</td>
                                        <td class="text-capitalize">{{ $payment->gateway ?? '-' }}</td>
                                        <td>
                                            @if ($payment->status === 'paid')
                                                <span class="badge badge-success">Paid</span>
                                            @elseif ($payment->status === 'failed')
                                                <span class="badge badge-danger">Failed</span>
                                            @elseif ($payment->status === 'expired')
                                                <span class="badge badge-secondary">Expired</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($payment->paid_at)->format('Y/m/d H:i') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted" style="font-size:13px;">No payment transactions yet.</p>
                @endif

                <hr>

                <h6 class="fw-bold mb-3">Submitted Documents</h6>

                @foreach ($groupedTypes as $groupLabel => $types)
                    <div class="text-uppercase text-muted fw-bold mt-3 mb-2" style="font-size:12px;letter-spacing:.04em;">
                        {{ $groupLabel }}
                    </div>

                    <div class="table-responsive mb-2">
                        <table class="table table-bordered align-middle" style="font-size:13.5px;">
                            <thead>
                                <tr>
                                    <th style="width:200px;">Document</th>
                                    <th>File</th>
                                    <th style="width:140px;">Status</th>
                                    <th style="width:150px;">Uploaded</th>
                                    <th class="text-center" style="width:260px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($types as $documentType)
                                    @php $document = $existingDocuments->get($documentType->id); @endphp
                                    <tr>
                                        <td class="fw-bold">{{ $documentType->label }}</td>
                                        <td>{{ $document->original_filename ?? '-' }}</td>
                                        <td>
                                            @if (!$document)
                                                <span class="badge badge-secondary">Not uploaded</span>
                                            @elseif ($document->review_status === 'approved')
                                                <span class="badge badge-success">Approved</span>
                                            @elseif ($document->review_status === 'rejected')
                                                <span class="badge badge-danger">Rejected</span>
                                            @else
                                                <span class="badge badge-warning">Pending</span>
                                            @endif

                                            @if ($document && $document->reviewed_at)
                                                <div class="text-muted mt-1" style="font-size:11px;">
                                                    by {{ optional($document->reviewedBy)->name ?? '-' }}<br>
                                                    {{ $document->reviewed_at->format('Y/m/d H:i') }}
                                                </div>
                                                @if ($document->review_status === 'rejected' && $document->review_note)
                                                    <div class="text-danger mt-1" style="font-size:11px;">&quot;{{ $document->review_note }}&quot;</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td>
                                            {{ $document && $document->uploaded_at ? $document->uploaded_at->format('Y/m/d H:i') : '-' }}
                                            @if ($document && $document->uploadedBy)
                                                <div class="text-muted" style="font-size:11px;">by {{ $document->uploadedBy->name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($document)
                                                <div class="d-flex flex-nowrap justify-content-center gap-2 mb-2">
                                                    <a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('quiz.university-application.documents.download', [$application->id, $document->id]) }}" class="btn btn-sm btn-outline-success text-nowrap">
                                                        Download
                                                    </a>
                                                </div>

                                                {{--
                                                    FASE 4 -- dokumen provided_by='admin' (Offer Letter,
                                                    Passport) TIDAK PERNAH direview lewat Approve/Reject --
                                                    dokumen ini justru DIBUAT oleh admin sendiri (bukan
                                                    diupload siswa), jadi tidak ada yang perlu direview.
                                                    Cukup Preview/Download di atas + Upload/Re-upload di
                                                    bawah.
                                                --}}
                                                @if ($documentType->provided_by !== 'admin')
                                                    <div class="d-flex flex-nowrap justify-content-center gap-2 mb-2">
                                                        <form method="POST" action="{{ route('quiz.university-application.documents.review', [$application->id, $document->id]) }}" class="m-0">
                                                            @csrf
                                                            <input type="hidden" name="action" value="approve">
                                                            <button type="submit" class="btn btn-sm btn-success text-nowrap" {{ $document->review_status === 'approved' ? 'disabled' : '' }}>
                                                                Approve
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-sm btn-danger text-nowrap" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $documentType->id }}">
                                                            Reject
                                                        </button>
                                                    </div>

                                                    <div class="modal fade" id="rejectModal-{{ $documentType->id }}" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content text-start">
                                                                <form method="POST" action="{{ route('quiz.university-application.documents.review', [$application->id, $document->id]) }}">
                                                                    @csrf
                                                                    <input type="hidden" name="action" value="reject">
                                                                    <div class="modal-header">
                                                                        <h6 class="modal-title mb-0">Reject: {{ $documentType->label }}</h6>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <label class="form-label" style="font-size:13px;">Alasan reject (wajib diisi, siswa akan melihat ini)</label>
                                                                        <textarea name="note" class="form-control" rows="3" required></textarea>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                                        <button type="submit" class="btn btn-danger btn-sm">Reject</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endif

                                                @if ($document->histories->isNotEmpty())
                                                    <details class="mt-2 text-start">
                                                        <summary class="text-muted" style="font-size:11.5px;cursor:pointer;">
                                                            {{ $document->histories->count() }} previous version(s)
                                                        </summary>
                                                        <ul class="mb-0 ps-3" style="font-size:11.5px;">
                                                            @foreach ($document->histories as $history)
                                                                <li class="mb-1">
                                                                    {{ optional($history->replaced_at)->format('Y/m/d H:i') }} --
                                                                    <a href="{{ asset($history->file_path) }}" target="_blank">Preview</a>
                                                                    /
                                                                    <a href="{{ route('quiz.university-application.documents.history.download', [$application->id, $history->id]) }}">Download</a>
                                                                    @if ($history->review_status)
                                                                        <div class="text-muted">
                                                                            Reviewed: <span class="text-capitalize">{{ $history->review_status }}</span>
                                                                            @if (optional($history->reviewedBy)->name) by {{ $history->reviewedBy->name }} @endif
                                                                            @if ($history->review_note) -- &quot;{{ $history->review_note }}&quot; @endif
                                                                        </div>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @endif
                                            @else
                                                <span class="text-muted d-block mb-2">-</span>
                                            @endif

                                            <form method="POST" action="{{ route('quiz.university-application.documents.upload', [$application->id, $documentType->id]) }}" enctype="multipart/form-data" class="d-flex flex-nowrap justify-content-center gap-1 mt-2">
                                                @csrf
                                                <input type="file" name="document" class="form-control form-control-sm" style="max-width:150px;" required>
                                                <button type="submit" class="btn btn-sm btn-outline-dark text-nowrap">{{ $document ? 'Re-upload' : 'Upload' }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

            </div>
        </div>
    </div>

</div>

@endsection
