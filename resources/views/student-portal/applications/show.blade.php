<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application {{ $application->application_no }} | INASTUDY</title>
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
        .page-wrap { max-width: 720px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .success-icon {
            width: 64px; height: 64px; border-radius: 50%;
            background: #e8f8ee; color: #1a9c53;
            display: flex; align-items: center; justify-content: center;
            font-size: 30px; margin-bottom: 16px;
        }
        .app-no {
            font-family: 'Courier New', monospace;
            font-weight: 800;
            font-size: 1.15rem;
            color: var(--brand);
            background: #fbe6ea;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
        }
        .detail-row {
            display: flex; justify-content: space-between; gap: 12px;
            padding: 10px 0; border-bottom: 1px solid #eef1f8; font-size: 14px;
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-row .label { color: #8a90a2; }
        .detail-row .value { font-weight: 600; text-align: right; }
        .btn-brand {
            background: var(--brand); color: #fff; border: none;
            padding: 12px 20px; border-radius: 12px; font-weight: 700;
            text-decoration: none; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }

        /* === FASE 4: ADMISSION STATUS TRACKER === */
        .status-tracker { display: flex; justify-content: space-between; margin: 10px 0 4px; }
        .status-tracker .status-step { flex: 1; text-align: center; position: relative; font-size: 12px; font-weight: 700; color: #c9cddb; }
        .status-tracker .status-step .dot {
            width: 26px; height: 26px; border-radius: 50%; background: #eef0f5; color: #8a90a2;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 6px;
            font-size: 13px; font-weight: 800;
        }
        .status-tracker .status-step.done .dot { background: #1a9c53; color: #fff; }
        .status-tracker .status-step.done { color: #1a9c53; }
        .status-tracker .status-step.current .dot { background: var(--brand); color: #fff; }
        .status-tracker .status-step.current { color: var(--brand); }
        .doc-download-row {
            display: flex; justify-content: space-between; align-items: center;
            border: 1px solid #eef1f8; border-radius: 12px; padding: 12px 16px; margin-bottom: 10px;
        }
        .doc-download-row .doc-name { font-weight: 700; font-size: 14px; }
        .doc-download-row .doc-hint { color: #8a90a2; font-size: 12px; }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container">
            <a href="{{ route('frontend.university.catalog') }}">
                <i class="bi bi-arrow-left"></i> Back to University List
            </a>
        </div>
    </div>

    <div class="page-wrap">

        @if(session('success'))
            <div class="alert alert-success" style="border-radius:12px;">{{ session('success') }}</div>
        @endif

        <div class="card-box text-center">
            <div class="success-icon mx-auto"><i class="bi bi-check-lg"></i></div>
            <h4 class="fw-bold mb-2">Application Submitted</h4>
            <p class="text-muted mb-3">Your application number is</p>
            <div class="app-no">{{ $application->application_no }}</div>
        </div>

        <div class="card-box">
            <h6 class="fw-bold mb-3">Application Summary</h6>

            <div class="detail-row">
                <span class="label">University</span>
                <span class="value">{{ $application->university->name ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Major</span>
                <span class="value">{{ $application->universityProfile->field ?? '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Degree</span>
                <span class="value">{{ $application->degree ?: '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Intake</span>
                <span class="value">{{ $application->intake ?: '-' }} {{ $application->intake_year }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Duration</span>
                <span class="value">{{ $application->duration ?: '-' }}</span>
            </div>
            @if($application->registration_fee_amount)
                <div class="detail-row">
                    <span class="label">Registration Fee</span>
                    <span class="value">Rp {{ number_format($application->registration_fee_amount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="detail-row">
                <span class="label">Status</span>
                <span class="value text-capitalize">{{ str_replace('_', ' ', $application->status) }}</span>
            </div>
        </div>

        {{--
            FASE 4 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) --
            tracker admission_status. Cuma tampil begitu Registration Fee
            sudah lunas (admission_status terisi, minimal 'under_review' --
            diset otomatis oleh webhook, lihat
            FormPaymentController::onApplicationPaymentPaid()). Sebelum itu,
            card ini disembunyikan total supaya tidak membingungkan siswa
            yang belum bayar.
        --}}
        @if($application->admission_status)
            @php
                $admissionSteps = [
                    'under_review' => 'Under Review',
                    'processing' => 'Processing',
                    'accepted' => 'Accepted',
                ];
                $admissionOrder = array_keys($admissionSteps);
                $currentIndex = array_search($application->admission_status, $admissionOrder, true);
            @endphp
            <div class="card-box">
                <h6 class="fw-bold mb-3">Admission Status</h6>
                <div class="status-tracker">
                    @foreach($admissionSteps as $key => $stepLabel)
                        @php
                            $stepIndex = array_search($key, $admissionOrder, true);
                            $stepClass = $currentIndex === false ? '' : ($stepIndex < $currentIndex ? 'done' : ($stepIndex === $currentIndex ? 'current' : ''));
                        @endphp
                        <div class="status-step {{ $stepClass }}">
                            <div class="dot">
                                @if($stepClass === 'done')
                                    <i class="bi bi-check-lg"></i>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </div>
                            {{ $stepLabel }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="card-box">
            <p class="mb-3" style="font-size:14.5px;color:#6b7186;">
                Please upload your documents so our team can start processing your application.
            </p>
            <a href="{{ route('student-portal.applications.documents.edit', $application->id) }}" class="btn-brand">
                <i class="bi bi-file-earmark-arrow-up"></i> Upload Documents
            </a>
        </div>

        {{--
            FASE 4 -- dokumen yang diupload ADMIN (Offer Letter, Passport),
            siswa cuma bisa lihat/download di sini, tidak pernah upload
            sendiri (lihat DocumentType::scopeAdminProvided() &
            StudentPortal\ApplicationController::show()). Card ini cuma
            tampil begitu ada minimal 1 jenis dokumen admin yang aktif (data
            master DocumentTypeSeeder) -- kalau belum ada satupun yang
            diupload admin, tetap tampil dengan status "Not yet available"
            supaya siswa tahu dokumen ini akan muncul di sini nanti.
        --}}
        @if($adminDocumentTypes->isNotEmpty())
            <div class="card-box">
                <h6 class="fw-bold mb-3">Documents from InaStudy</h6>
                @foreach($adminDocumentTypes as $documentType)
                    @php $adminDocument = $adminDocuments->get($documentType->id); @endphp
                    <div class="doc-download-row">
                        <div>
                            <div class="doc-name">{{ $documentType->label }}</div>
                            @if($adminDocument)
                                <div class="doc-hint">Uploaded {{ optional($adminDocument->uploaded_at)->format('d M Y, H:i') }}</div>
                            @else
                                <div class="doc-hint">Not yet available</div>
                            @endif
                        </div>
                        @if($adminDocument)
                            <a href="{{ asset($adminDocument->file_path) }}" target="_blank" class="btn btn-brand" style="padding:8px 16px;">
                                <i class="bi bi-download"></i> Download
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="card-box">
            <p class="mb-3" style="font-size:14.5px;color:#6b7186;">
                Questions about document requirements or the next steps? Chat with our team on WhatsApp.
            </p>
            @php
                $waMessage = "Hello InaStudy, I just submitted application {$application->application_no}";
                $waNumber = '6281287625661';
            @endphp
            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}" class="btn-brand" target="_blank">
                <i class="bi bi-whatsapp"></i> Chat with our team
            </a>
        </div>

    </div>

</body>

</html>
