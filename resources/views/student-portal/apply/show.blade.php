<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - {{ $profile->field ?: 'Program' }} - {{ $profile->university->name }} | INASTUDY</title>
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
        .topbar {
            background: #fff;
            border-bottom: 1px solid #eef1f8;
            padding: 16px 0;
        }
        .topbar a { color: #6b7186; text-decoration: none; font-weight: 600; font-size: 14px; }
        .topbar a:hover { color: var(--brand); }
        .page-wrap { max-width: 760px; margin: 0 auto; padding: 32px 16px 60px; }
        .card-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 6px 20px rgba(20,30,60,.05);
            margin-bottom: 20px;
        }
        .card-box h1 { font-size: 1.4rem; font-weight: 800; margin-bottom: 4px; }
        .card-box .subtitle { color: #6b7186; font-size: 14.5px; margin-bottom: 0; }
        .fee-box {
            background: #fbe6ea;
            border-radius: 12px;
            padding: 16px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .fee-box .label { font-size: 13px; color: #8a3040; font-weight: 600; text-transform: uppercase; letter-spacing: .03em; }
        .fee-box .amount { font-size: 1.3rem; font-weight: 800; color: var(--brand); }
        .form-label { font-weight: 600; font-size: 14px; color: #2b2f38; }
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
        .alert-heads-up { border-radius: 12px; }
        /* FIX v2 (permintaan user, 16 September 2026): kotak read-only
           pengganti <select> saat jurusan sudah pasti dari ?course=...
           (lihat $lockedCourse) -- tampilannya sengaja dibuat mirip
           .form-control tapi jelas non-editable (ikon gembok). */
        .locked-value-box {
            background: #f8f9fc;
            border: 1px solid #dfe3ee;
            border-radius: 10px;
            padding: 11px 14px;
            font-weight: 600;
            font-size: 14.5px;
            color: #2b2f38;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .locked-value-box i { color: var(--brand); }
    </style>
</head>

<body>

    <div class="topbar">
        <div class="container">
            <a href="{{ route('frontend.university.profile', $profile->university_id) }}">
                <i class="bi bi-arrow-left"></i> Back to {{ $profile->university->name }}
            </a>
        </div>
    </div>

    <div class="page-wrap">

        @if(session('status'))
            <div class="alert alert-info alert-heads-up">{{ session('status') }}</div>
        @endif

        @if(session('apply_conflict'))
            <div class="alert alert-warning alert-heads-up d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span>{{ session('apply_conflict') }}</span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark">Logout</button>
                </form>
            </div>
        @endif

        <div class="card-box">
            <h1>Apply Now</h1>
            <p class="subtitle">
                {{ $profile->university->name }}
                @if($profile->field) &middot; {{ $profile->field }} @endif
            </p>
            {{--
                FIX v2 (permintaan user, 16 September 2026): kalau siswa klik
                Apply dari salah satu jurusan (course-item) di halaman
                profile, jurusan itu dibawa ke sini lewat ?course=... (lihat
                $lockedCourse di StudentPortal\ApplyController::show()) --
                badge ini menampilkan nama jurusan + Degree-nya. Fallback ke
                $selectedDegree kalau yang dibawa cuma ?degree= (link lama,
                versi sebelum per-jurusan).
            --}}
            @if($lockedCourse)
                <span class="badge-pill" style="display:inline-flex; align-items:center; gap:6px; background:#fbe6ea; color:var(--brand); font-weight:700; font-size:12.5px; padding:6px 12px; border-radius:999px; margin-top:8px;">
                    <i class="bi bi-mortarboard-fill"></i> Applying for: {{ $lockedCourse->course_name ?: ($profile->field ?: 'Program') }}{{ $lockedCourse->degree ? ' ('.$lockedCourse->degree.')' : '' }}
                </span>
            @elseif($selectedDegree)
                <span class="badge-pill" style="display:inline-flex; align-items:center; gap:6px; background:#fbe6ea; color:var(--brand); font-weight:700; font-size:12.5px; padding:6px 12px; border-radius:999px; margin-top:8px;">
                    <i class="bi bi-mortarboard-fill"></i> Applying for: {{ $selectedDegree }}
                </span>
            @endif
        </div>

        {{--
            FIX v2 (permintaan user, 16 September 2026): Registration Fee
            sekarang PRIORITAS ditampilkan dari Course yang di-lock
            ($courseRegistrationFeeAmount, selalu Rupiah -- lihat
            UniversityProfileDegree::registration_fee_amount) -- itu nominal
            yang BENAR-BENAR di-snapshot ke aplikasi begitu form ini
            disubmit (lihat ApplyController::store()). Fallback ke
            $registrationFee (baris Payment fee_type='registration_fee' di
            level Program, punya pilihan lokasi Indonesia/China) cuma kalau
            Course-nya belum diisi Registration Fee sendiri.
        --}}
        @if($courseRegistrationFeeAmount)
            <div class="card-box">
                <div class="fee-box">
                    <div>
                        <div class="label">Registration Fee</div>
                        <div class="text-muted" style="font-size:13px;">Paid separately, our team will guide you after you submit this application.</div>
                    </div>
                    <div class="amount">Rp {{ number_format($courseRegistrationFeeAmount, 0, ',', '.') }}</div>
                </div>
            </div>
        @elseif($registrationFee)
            <div class="card-box">
                <div class="fee-box">
                    <div>
                        <div class="label">Registration Fee</div>
                        <div class="text-muted" style="font-size:13px;">Paid separately, our team will guide you after you submit this application.</div>
                    </div>
                    <div class="amount">
                        @if($registrationFee->location === 'china')
                            元 {{ number_format($registrationFee->amount, 0, ',', '.') }}
                        @else
                            Rp {{ number_format($registrationFee->amount, 0, ',', '.') }}
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="card-box">
            @if($degreeOptions->isEmpty())
                <div class="alert alert-warning alert-heads-up mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This program does not have any Degree/Intake/Duration options configured yet. Please contact our team on WhatsApp to continue your application.
                </div>
            @else
                <form method="POST" action="{{ route('student-portal.apply.store', $profile->id) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Program / Major</label>
                        {{--
                            FIX v2 (permintaan user, 16 September 2026): kalau siswa
                            datang dari tombol Apply per-jurusan (course-item) di
                            halaman profile, jurusannya sudah PASTI (dibawa lewat
                            ?course=..., lihat $lockedCourse di
                            StudentPortal\ApplyController::show()) -- jadi
                            ditampilkan sebagai kotak read-only (BUKAN <select>)
                            supaya tidak bisa diubah lagi dari sini. Value-nya tetap
                            dikirim ke server lewat hidden input, name field-nya
                            SAMA ("degree_intake_id") supaya ApplyController::store()
                            tidak perlu diubah sama sekali. Kalau siswa mau ganti
                            jurusan, harus balik dulu ke halaman profile (link
                            "Back to ..." di atas / tombol back browser).

                            <select> dropdown lama TETAP DIPERTAHANKAN untuk kasus
                            fallback (?course= tidak ada/tidak valid, mis. link lama
                            versi per-Degree atau Course sudah dihapus admin) --
                            dulu label option-nya gabungan "Degree - Intake -
                            Duration" (mis. "Bachelor - September - 4 Years") -- kalau
                            1 Program punya beberapa Course dengan Degree/Intake/
                            Duration yang SAMA, opsi-opsinya jadi kelihatan identik/
                            tidak bisa dibedakan (course_name-nya malah tidak pernah
                            ditampilkan sama sekali). Sekarang select ini fokus ke
                            JURUSAN (course_name) dulu -- Degree ikut ditulis di
                            belakang dalam kurung. Begitu jurusan dipilih, field
                            Intake & Duration di bawah otomatis muncul (readonly, cuma
                            utk konfirmasi -- bukan diisi manual) mengambil nilai dari
                            Course yang dipilih, lewat data-intake/data-duration di tiap
                            <option> + IIFE vanilla JS di bagian bawah file (pola sama
                            dengan IIFE lain di codebase ini, bukan Bootstrap JS).
                        --}}
                        @if($lockedCourse)
                            <div class="locked-value-box">
                                <i class="bi bi-lock-fill"></i>
                                {{ $lockedCourse->course_name ?: ($profile->field ?: 'Program') }}{{ $lockedCourse->degree ? ' (' . $lockedCourse->degree . ')' : '' }}
                            </div>
                            <input type="hidden" name="degree_intake_id" value="{{ $lockedCourse->id }}">
                            <div class="form-text">
                                Fixed to the course you selected. <a href="{{ route('frontend.university.profile', $profile->university_id) }}">Go back</a> to choose a different one.
                            </div>
                        @else
                            <select id="degreeIntakeSelect" name="degree_intake_id" class="form-select @error('degree_intake_id') is-invalid @enderror" required>
                                <option value="">Choose...</option>
                                @foreach($degreeOptions as $degreeRow)
                                    <option value="{{ $degreeRow->id }}"
                                        data-intake="{{ $degreeRow->intake }}"
                                        data-duration="{{ $degreeRow->duration }}"
                                        {{ old('degree_intake_id') === $degreeRow->id ? 'selected' : '' }}>
                                        {{ $degreeRow->course_name ?: ($profile->field ?: 'Program') }}{{ $degreeRow->degree ? ' (' . $degreeRow->degree . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                        @error('degree_intake_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    @if($lockedCourse)
                        <div class="row g-3 mb-3">
                            <div class="col-sm-6">
                                <label class="form-label">Intake</label>
                                <input type="text" class="form-control" value="{{ $lockedCourse->intake }}" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" value="{{ $lockedCourse->duration }}" disabled>
                            </div>
                        </div>
                    @else
                        <div class="row g-3 mb-3" id="degreeIntakeDetails" style="display:none;">
                            <div class="col-sm-6">
                                <label class="form-label">Intake</label>
                                <input type="text" id="degreeIntakeDetailsIntake" class="form-control" value="" disabled>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Duration</label>
                                <input type="text" id="degreeIntakeDetailsDuration" class="form-control" value="" disabled>
                            </div>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Intake Year</label>
                        <input type="number" name="intake_year" class="form-control @error('intake_year') is-invalid @enderror"
                            value="{{ old('intake_year', now()->year) }}" min="{{ now()->year }}" max="{{ now()->year + 5 }}" required>
                        <div class="form-text">The year you plan to start (e.g. {{ now()->year }} intake).</div>
                        @error('intake_year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label">WhatsApp Number</label>
                        <input type="text" name="whatsapp" class="form-control @error('whatsapp') is-invalid @enderror"
                            value="{{ old('whatsapp', $defaultWhatsapp) }}" placeholder="08xxxxxxxxxx" required>
                        <div class="form-text">Our team will contact you on this number regarding your application.</div>
                        @error('whatsapp')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="bi bi-send-check me-1"></i> Submit Application
                    </button>
                </form>
            @endif
        </div>

    </div>

    <script>
        // FIX (permintaan user, 16 September 2026): begitu jurusan (Program /
        // Major) dipilih di #degreeIntakeSelect, otomatis tampilkan &amp; isi
        // #degreeIntakeDetails (Intake & Duration, readonly) dari atribut
        // data-intake/data-duration milik <option> yang dipilih. Dijalankan
        // juga 1x saat halaman baru dibuka (bukan cuma saat event 'change')
        // supaya kalau validasi form gagal & halaman reload dengan
        // old('degree_intake_id') sudah keisi, box Intake/Duration ikut
        // langsung kelihatan terisi -- bukan cuma keisi setelah user
        // mengubah pilihannya secara manual.
        (function () {
            var select = document.getElementById('degreeIntakeSelect');
            var details = document.getElementById('degreeIntakeDetails');
            var intakeInput = document.getElementById('degreeIntakeDetailsIntake');
            var durationInput = document.getElementById('degreeIntakeDetailsDuration');

            if (!select || !details || !intakeInput || !durationInput) {
                return;
            }

            function syncDegreeIntakeDetails() {
                var selectedOption = select.options[select.selectedIndex];

                if (!selectedOption || !selectedOption.value) {
                    details.style.display = 'none';
                    intakeInput.value = '';
                    durationInput.value = '';
                    return;
                }

                intakeInput.value = selectedOption.getAttribute('data-intake') || '';
                durationInput.value = selectedOption.getAttribute('data-duration') || '';
                details.style.display = '';
            }

            select.addEventListener('change', syncDegreeIntakeDetails);
            syncDegreeIntakeDetails();
        })();
    </script>

</body>

</html>
