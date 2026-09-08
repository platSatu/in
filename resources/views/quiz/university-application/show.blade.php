@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <a href="{{ route('quiz.university-application.index') }}" class="btn btn-outline-secondary">&laquo; Back to List</a>
    </div>

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
                                <td class="text-muted">Registration Fee</td>
                                <td>{{ $application->registration_fee_amount ? number_format($application->registration_fee_amount, 0, ',', '.') : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Status</td>
                                <td><span class="badge badge-info text-capitalize">{{ str_replace('_', ' ', $application->status) }}</span></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Submitted</td>
                                <td>{{ optional($application->submitted_at)->format('Y/m/d H:i') }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

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
                                    <th style="width:220px;">Document</th>
                                    <th>File</th>
                                    <th style="width:120px;">Status</th>
                                    <th style="width:160px;">Uploaded</th>
                                    <th class="text-center" style="width:220px;">Action</th>
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
                                        </td>
                                        <td>
                                            {{ $document && $document->uploaded_at ? $document->uploaded_at->format('Y/m/d H:i') : '-' }}
                                            @if ($document && $document->uploadedBy)
                                                <div class="text-muted" style="font-size:11px;">by {{ $document->uploadedBy->name }}</div>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($document)
                                                <div class="d-flex flex-nowrap justify-content-center gap-2">
                                                    <a href="{{ asset($document->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary text-nowrap">
                                                        Preview
                                                    </a>
                                                    <a href="{{ route('quiz.university-application.documents.download', [$application->id, $document->id]) }}" class="btn btn-sm btn-outline-success text-nowrap">
                                                        Download
                                                    </a>
                                                </div>

                                                @if ($document->histories->isNotEmpty())
                                                    <details class="mt-2 text-start">
                                                        <summary class="text-muted" style="font-size:11.5px;cursor:pointer;">
                                                            {{ $document->histories->count() }} previous version(s)
                                                        </summary>
                                                        <ul class="mb-0 ps-3" style="font-size:11.5px;">
                                                            @foreach ($document->histories as $history)
                                                                <li>
                                                                    {{ optional($history->replaced_at)->format('Y/m/d H:i') }} --
                                                                    <a href="{{ asset($history->file_path) }}" target="_blank">Preview</a>
                                                                    /
                                                                    <a href="{{ route('quiz.university-application.documents.history.download', [$application->id, $history->id]) }}">Download</a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </details>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
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
