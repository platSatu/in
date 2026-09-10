<!--
    FASE 3 (Alur Pembayaran 2 Arah Apply Kampus, 10 September 2026) -- Step 1
    SETELAH Registration Fee lunas: Formulir biodata lengkap diisi LANGSUNG
    di web (menggantikan upload file Word/PDF manual untuk DocumentType
    'formulir', lihat DocumentTypeSeeder), field-nya mengikuti contoh form
    Word asli per-kampus ("Nanjing Tech Form") yang dikirim user.

    Bagian "Education Background" (fitur "add row") dikelola lewat JS
    sederhana di bawah -- baris baru di-clone dari <template>, index array
    diberi ulang tiap kali baris ditambah/dihapus supaya urutan
    education[0], education[1], dst selalu rapat (tidak bolong) waktu
    disubmit.

    Setelah Terms & Condition dicentang & disubmit, ApplicationFormController
    ::update() set terms_accepted_at lalu redirect ke Step 2 (Upload
    Documents) -- lihat guard blockIfFormNotSubmitted() di
    ApplicationDocumentController.
-->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir - {{ $application->application_no }} | INASTUDY</title>
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
        .page-wrap { max-width: 900px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .card-box h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .card-box .subtitle { color: #6b7186; font-size: 14.5px; margin-bottom: 0; }
        .section-title {
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #8a90a2;
            margin: 8px 0 16px;
        }
        label.form-label { font-size: 13.5px; font-weight: 600; color: #4a5063; }
        .edu-row { border: 1px solid #eef1f8; border-radius: 12px; padding: 14px; margin-bottom: 12px; position: relative; }
        .edu-row .btn-remove-row {
            position: absolute; top: 10px; right: 10px; border: none; background: none;
            color: #c9cddb; font-size: 18px; line-height: 1; cursor: pointer;
        }
        .edu-row .btn-remove-row:hover { color: var(--brand); }
        .btn-brand {
            background: var(--brand);
            border: none;
            color: #fff;
            padding: 13px 20px;
            border-radius: 12px;
            font-weight: 700;
        }
        .btn-brand:hover { background: var(--brand-dark); color: #fff; }
        .btn-add-row {
            border: 2px dashed #e9ecef;
            background: #fff;
            color: #6b7186;
            font-weight: 700;
            font-size: 13.5px;
            border-radius: 10px;
            padding: 10px 16px;
            width: 100%;
        }
        .btn-add-row:hover { border-color: var(--brand); color: var(--brand); }
        .terms-box {
            border: 1px solid #eef1f8;
            border-radius: 12px;
            padding: 16px 18px;
            max-height: 260px;
            overflow-y: auto;
            font-size: 13px;
            color: #4a5063;
            background: #f8f9fc;
        }
        .terms-box ol { padding-left: 18px; margin-bottom: 0; }
        .terms-box li { margin-bottom: 8px; }
        .photo-preview {
            width: 90px; height: 110px; object-fit: cover; border-radius: 8px;
            border: 1px solid #eef1f8; background: #f8f9fc;
        }
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
            <h1>Formulir Pendaftaran</h1>
            <p class="subtitle">
                {{ $application->university->name ?? '-' }} &middot; {{ $application->application_no }}
            </p>
        </div>

        <form method="POST" action="{{ route('student-portal.applications.form.update', $application->id) }}" enctype="multipart/form-data">
            @csrf

            <div class="card-box">
                <div class="section-title">Personal Information</div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Surname</label>
                        <input type="text" name="surname" class="form-control" value="{{ old('surname', $formDetail->surname ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Given Name</label>
                        <input type="text" name="given_name" class="form-control" value="{{ old('given_name', $formDetail->given_name ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Chinese Name (if any)</label>
                        <input type="text" name="chinese_name" class="form-control" value="{{ old('chinese_name', $formDetail->chinese_name ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Photo</label>
                        <div class="d-flex align-items-center gap-3">
                            @if($formDetail && $formDetail->photo_path)
                                <img src="{{ asset($formDetail->photo_path) }}" class="photo-preview" alt="Current photo">
                            @endif
                            <input type="file" name="photo" class="form-control @error('photo') is-invalid @enderror" accept=".jpg,.jpeg">
                        </div>
                        @error('photo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">-- Select --</option>
                            @foreach(['Male' => 'Male', 'Female' => 'Female'] as $value => $labelText)
                                <option value="{{ $value }}" {{ old('gender', $formDetail->gender ?? '') === $value ? 'selected' : '' }}>{{ $labelText }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nationality</label>
                        <input type="text" name="nationality" class="form-control" value="{{ old('nationality', $formDetail->nationality ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Passport No.</label>
                        <input type="text" name="passport_no" class="form-control" value="{{ old('passport_no', $formDetail->passport_no ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Passport Expiry Date</label>
                        <input type="date" name="passport_expiry_date" class="form-control" value="{{ old('passport_expiry_date', optional($formDetail?->passport_expiry_date)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Telephone No.</label>
                        <input type="text" name="telephone_no" class="form-control" value="{{ old('telephone_no', $formDetail->telephone_no ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($formDetail?->date_of_birth)->format('Y-m-d')) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Place of Birth</label>
                        <input type="text" name="place_of_birth" class="form-control" value="{{ old('place_of_birth', $formDetail->place_of_birth ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Hobby</label>
                        <input type="text" name="hobby" class="form-control" value="{{ old('hobby', $formDetail->hobby ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Parents Name (Father &amp; Mother)</label>
                        <input type="text" name="parents_name" class="form-control" value="{{ old('parents_name', $formDetail->parents_name ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parents Phone Number (Father &amp; Mother)</label>
                        <input type="text" name="parents_phone" class="form-control" value="{{ old('parents_phone', $formDetail->parents_phone ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Parents Occupation (Father/Mother)</label>
                        <input type="text" name="parents_occupation" class="form-control" value="{{ old('parents_occupation', $formDetail->parents_occupation ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-mail Address</label>
                        <input type="email" name="email" class="form-control" value="{{ old('email', $formDetail->email ?? '') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Home Address and Zipcode</label>
                        <textarea name="home_address" class="form-control" rows="2">{{ old('home_address', $formDetail->home_address ?? '') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Religion</label>
                        <input type="text" name="religion" class="form-control" value="{{ old('religion', $formDetail->religion ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Highest Degree Obtained</label>
                        <input type="text" name="highest_degree_obtained" class="form-control" value="{{ old('highest_degree_obtained', $formDetail->highest_degree_obtained ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Field of Study in China</label>
                        <input type="text" name="field_of_study_in_china" class="form-control" value="{{ old('field_of_study_in_china', $formDetail->field_of_study_in_china ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Financial Support Will Be Provided By</label>
                        <input type="text" name="financial_support_by" class="form-control" value="{{ old('financial_support_by', $formDetail->financial_support_by ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Scholarship / Self Sponsored</label>
                        <select name="sponsorship_type" class="form-select">
                            <option value="">-- Select --</option>
                            <option value="scholarship" {{ old('sponsorship_type', $formDetail->sponsorship_type ?? '') === 'scholarship' ? 'selected' : '' }}>Scholarship</option>
                            <option value="self_sponsored" {{ old('sponsorship_type', $formDetail->sponsorship_type ?? '') === 'self_sponsored' ? 'selected' : '' }}>Self Sponsored</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-box">
                <div class="section-title">Education Background</div>
                <p class="text-muted" style="font-size:13px;margin-top:-8px;">
                    Start from Elementary School, Junior High, Senior High / University.
                </p>

                <div id="eduRows"></div>

                <button type="button" class="btn-add-row" id="btnAddEduRow">
                    <i class="bi bi-plus-lg"></i> Add Row
                </button>
            </div>

            <div class="card-box">
                <div class="section-title">Terms &amp; Condition</div>
                <div class="terms-box">
                    <ol>
                        <li>INASTUDY (sebagai agency) berkewajiban membantu pendaftaran setiap calon mahasiswa/i ke universitas yang di minati.</li>
                        <li>INASTUDY (sebagai agency) tidak menjamin beasiswa kepada calon mahasiswa/i ke universitas yang di minati.</li>
                        <li>INASTUDY (sebagai agency) akan membantu mendampingi / menjemput mahasiswa/i ke university.</li>
                        <li>INASTUDY (sebagai agency) tidak mengijinkan mahasiswa/i menitipkan barang kepada team ataupun pihak agency.</li>
                        <li>INASTUDY (sebagai agency) tidak bertanggung jawab kepada mahasiswa/i atas Kesehatan dan Keselamatan kepada mahasiswa/i sebelum dan setelah pelepasan sampai pendampingan di China.</li>
                        <li>INASTUDY (sebagai agency) tidak bertanggung jawab dalam hal, tindak criminal &amp; kehilangan dalam bentuk apa pun selama perjalanan dan sampai ke China.</li>
                        <li>Calon mahasiswa/i wajib membayarkan registrasi fee setelah mengirimkan formulir pendaftaran maximal 3 hari setelah menyerahkan formulir.</li>
                        <li>Biaya Registrasi fee tidak dapat ditukarkan dalam voucher dalam bentuk apapun, dan tidak dapat dikembalikan (non-refundable).</li>
                        <li>Mahasiswa/i wajib melakukan pengurusan visa melalui pihak Inastudy.</li>
                        <li>Mahasiswa/i wajib memesan tiket pesawat melalui pihak Inastudy.</li>
                        <li>Mahasiswa/i berhak mendapatkan surat penerimaan dari Universitas.</li>
                        <li>Mahasiswa/i yang sudah mendaftarkan melalui inastudy, berhak mendapatkan konsultasi secara gratis sampai penerimaan di universitas.</li>
                    </ol>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input @error('terms_accepted') is-invalid @enderror" type="checkbox" name="terms_accepted" value="1" id="termsAccepted"
                        {{ old('terms_accepted', $formDetail && $formDetail->terms_accepted_at ? '1' : '') ? 'checked' : '' }}>
                    <label class="form-check-label" for="termsAccepted" style="font-size:14px;">
                        I have read and agree to the Terms &amp; Condition above.
                    </label>
                    @error('terms_accepted')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <button type="submit" class="btn-brand w-100">
                <i class="bi bi-arrow-right-circle"></i> Save &amp; Continue to Upload Documents
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="{{ route('student-portal.applications.show', $application->id) }}" class="btn-link-secondary">
                View Application Summary
            </a>
        </div>

    </div>

    <template id="eduRowTemplate">
        <div class="edu-row">
            <button type="button" class="btn-remove-row" title="Remove"><i class="bi bi-x-lg"></i></button>
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Level</label>
                    <select class="form-select edu-level" name="">
                        <option value="">-- Select --</option>
                        @foreach(\App\Models\ApplicationEducationBackground::LEVELS as $levelKey => $levelLabel)
                            <option value="{{ $levelKey }}">{{ $levelLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">School / Institution</label>
                    <input type="text" class="form-control edu-school" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control edu-location" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year Start</label>
                    <input type="text" class="form-control edu-year-start" name="">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year End</label>
                    <input type="text" class="form-control edu-year-end" name="">
                </div>
            </div>
        </div>
    </template>

    <script>
        // FASE 3 -- "add row" Education Background. Setiap baris di-clone
        // dari <template> di atas, lalu name atributnya diisi ulang
        // (education[INDEX][field]) tiap kali reindexEduRows() dipanggil
        // (dipicu oleh add/remove) supaya urutan index selalu rapat mulai
        // dari 0 -- server (ApplicationFormController::update()) melewati
        // baris yang semua kolomnya kosong, jadi index yang bolong pun
        // sebenarnya aman, tapi dirapikan di sini biar konsisten.
        const eduRowsContainer = document.getElementById('eduRows');
        const eduRowTemplate = document.getElementById('eduRowTemplate');

        const existingEducation = @json($educationBackgrounds->map(fn ($row) => [
            'level' => $row->level,
            'school_name' => $row->school_name,
            'location' => $row->location,
            'year_start' => $row->year_start,
            'year_end' => $row->year_end,
        ])->values());

        function addEduRow(data) {
            data = data || {};
            const fragment = eduRowTemplate.content.cloneNode(true);
            const rowEl = fragment.querySelector('.edu-row');

            rowEl.querySelector('.edu-level').value = data.level || '';
            rowEl.querySelector('.edu-school').value = data.school_name || '';
            rowEl.querySelector('.edu-location').value = data.location || '';
            rowEl.querySelector('.edu-year-start').value = data.year_start || '';
            rowEl.querySelector('.edu-year-end').value = data.year_end || '';

            rowEl.querySelector('.btn-remove-row').addEventListener('click', function () {
                rowEl.remove();
                reindexEduRows();
            });

            eduRowsContainer.appendChild(rowEl);
        }

        function reindexEduRows() {
            const rows = eduRowsContainer.querySelectorAll('.edu-row');
            rows.forEach(function (row, index) {
                row.querySelector('.edu-level').name = 'education[' + index + '][level]';
                row.querySelector('.edu-school').name = 'education[' + index + '][school_name]';
                row.querySelector('.edu-location').name = 'education[' + index + '][location]';
                row.querySelector('.edu-year-start').name = 'education[' + index + '][year_start]';
                row.querySelector('.edu-year-end').name = 'education[' + index + '][year_end]';
            });
        }

        document.getElementById('btnAddEduRow').addEventListener('click', function () {
            addEduRow();
            reindexEduRows();
        });

        if (existingEducation.length > 0) {
            existingEducation.forEach(function (row) {
                addEduRow(row);
            });
        } else {
            // Form baru (belum pernah diisi) -- mulai dengan 1 baris kosong
            // supaya siswa langsung lihat contoh kolomnya, bukan area kosong
            // total.
            addEduRow();
        }
        reindexEduRows();
    </script>

</body>

</html>
