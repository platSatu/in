<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Documents - {{ $application->application_no }} | INASTUDY</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --brand: #C8102E; --brand-dark: #a30d25; }
        * { box-sizing: border-box; }
        body {
            background: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
        }
        .topbar { background: #fff; border-bottom: 1px solid #eef1f8; padding: 16px 0; }
        .topbar a { color: #6b7186; text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { color: var(--brand); }
        .page-wrap { max-width: 820px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .card-box h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .card-box .subtitle { color: #6b7186; font-size: 14.5px; margin-bottom: 0; }
        .group-title {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a90a2;
            margin: 26px 0 12px;
        }
        .group-title:first-child { margin-top: 0; }
        .doc-row {
            border: 1px solid #eef1f8;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 12px;
        }
        .doc-row .doc-name { font-weight: 700; font-size: 14.5px; }
        .doc-row .doc-hint { color: #8a90a2; font-size: 12.5px; }
        .badge-status { font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 20px; }
        .badge-not-uploaded { background: #f1f2f6; color: #6b7186; }
        .badge-pending { background: #fff4de; color: #b8860b; }
        .badge-approved { background: #e8f8ee; color: #1a9c53; }
        .badge-rejected { background: #fbe6ea; color: var(--brand); }
        .doc-current-link { font-size: 13px; font-weight: 600; }
        .history-toggle { font-size: 12.5px; color: #6b7186; cursor: pointer; text-decoration: underline; }
        .history-list { font-size: 12.5px; color: #6b7186; margin-top: 8px; padding-left: 0; list-style: none; }
        .btn-submit {
            background: var(--brand);
            border: none;
            color: #fff;
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 700;
            width: 100%;
        }
        .btn-submit:hover { background: var(--brand-dark); color: #fff; }
        .btn-link-secondary { color: #6b7186; font-weight: 600; font-size: 13.5px; text-decoration: none; }
        .btn-link-secondary:hover { color: var(--brand); }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container">
            <a href="{{ route('student-portal.applications.show', $application->id) }}">
                <i class="bi bi-arrow-left"></i> Back to Application Summary
            </a>
        </div>
    </div>

    <div class="page-wrap">

        @if(session('success'))
            <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
        @endif
        @if(session('status'))
            <div class="alert alert-info" style="border-radius:12px;">{{ session('status') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" style="border-radius:12px;">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card-box">
            <h1>Upload Documents</h1>
            <p class="subtitle">
                {{ $application->university->name ?? '-' }} &middot; {{ $application->application_no }}
            </p>
        </div>

        <div class="card-box">
            <p class="mb-4" style="font-size:14px;color:#6b7186;">
                Upload as many documents as you have ready. You don't need to complete everything at once --
                you can come back and upload the rest later, or re-upload a document any time to replace it
                with a newer version.
            </p>

            <form method="POST" action="{{ route('student-portal.applications.documents.update', $application->id) }}" enctype="multipart/form-data">
                @csrf

                @foreach($groupedTypes as $groupLabel => $types)
                    <div class="group-title">{{ $groupLabel }}</div>

                    @foreach($types as $documentType)
                        @php
                            $existingDocument = $existingDocuments->get($documentType->id);
                            $statusKey = $existingDocument->review_status ?? null;
                        @endphp

                        <div class="doc-row">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                <div>
                                    <div class="doc-name">{{ $documentType->label }}</div>
                                    <div class="doc-hint">Allowed: {{ strtoupper($documentType->allowed_extensions) }} &middot; max 5MB</div>

                                    {{--
                                        FIX (10 September 2026): fitur "Download Template" --
                                        beberapa jenis dokumen (contoh pertama: Medical
                                        Certificate) butuh siswa download form kosongnya
                                        dulu, isi/tanda tangan, baru diupload lagi lewat
                                        kolom upload yang sama persis di bawah ini. Tombol
                                        ini cuma muncul kalau template_file_path diisi
                                        (lihat DocumentTypeSeeder) -- jenis dokumen lain
                                        yang tidak punya template tetap tampil normal.
                                    --}}
                                    @if($documentType->template_file_path)
                                        <div class="mt-1">
                                            <a href="{{ asset($documentType->template_file_path) }}" target="_blank" class="doc-current-link">
                                                <i class="bi bi-download"></i> Download Template
                                            </a>
                                        </div>
                                    @endif
                                </div>

                                @if(!$existingDocument)
                                    <span class="badge-status badge-not-uploaded">Not uploaded</span>
                                @elseif($statusKey === 'approved')
                                    <span class="badge-status badge-approved">Approved</span>
                                @elseif($statusKey === 'rejected')
                                    <span class="badge-status badge-rejected">Rejected</span>
                                @else
                                    <span class="badge-status badge-pending">Pending review</span>
                                @endif
                            </div>

                            @if($existingDocument)
                                <div class="mb-2">
                                    <a href="{{ asset($existingDocument->file_path) }}" target="_blank" class="doc-current-link">
                                        <i class="bi bi-eye"></i> View current file
                                    </a>
                                    <span class="doc-hint">&middot; uploaded {{ optional($existingDocument->uploaded_at)->format('d M Y, H:i') }}</span>
                                </div>

                                @if($statusKey === 'rejected' && $existingDocument->review_note)
                                    <div class="doc-hint mb-2" style="color:var(--brand);">
                                        <i class="bi bi-exclamation-circle"></i> {{ $existingDocument->review_note }}
                                    </div>
                                @endif

                                @if($existingDocument->histories->isNotEmpty())
                                    <details class="mb-2">
                                        <summary class="history-toggle">Previous versions ({{ $existingDocument->histories->count() }})</summary>
                                        <ul class="history-list">
                                            @foreach($existingDocument->histories as $history)
                                                <li>
                                                    <a href="{{ asset($history->file_path) }}" target="_blank" class="doc-current-link">
                                                        {{ $history->original_filename ?: 'File' }}
                                                    </a>
                                                    -- replaced {{ optional($history->replaced_at)->format('d M Y, H:i') }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @endif
                            @endif

                            <input type="file" name="documents[{{ $documentType->id }}]"
                                class="form-control form-control-sm @error('documents.' . $documentType->id) is-invalid @enderror">
                            @error('documents.' . $documentType->id)
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    @endforeach
                @endforeach

                <button type="submit" class="btn-submit mt-3">
                    <i class="bi bi-cloud-upload me-1"></i> Save Documents
                </button>
            </form>
        </div>

        <div class="text-center">
            <a href="{{ route('student-portal.applications.show', $application->id) }}" class="btn-link-secondary">
                View Application Summary
            </a>
        </div>

    </div>

</body>

</html>
