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
        </div>

        @if($registrationFee)
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
            @if($profile->degrees->isEmpty())
                <div class="alert alert-warning alert-heads-up mb-0">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    This program does not have any Degree/Intake/Duration options configured yet. Please contact our team on WhatsApp to continue your application.
                </div>
            @else
                <form method="POST" action="{{ route('student-portal.apply.store', $profile->id) }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Degree, Intake &amp; Duration</label>
                        <select name="degree_intake_id" class="form-select @error('degree_intake_id') is-invalid @enderror" required>
                            <option value="">Choose...</option>
                            @foreach($profile->degrees as $degreeRow)
                                <option value="{{ $degreeRow->id }}" {{ old('degree_intake_id') === $degreeRow->id ? 'selected' : '' }}>
                                    {{ collect([$degreeRow->degree, $degreeRow->intake, $degreeRow->duration])->filter()->implode(' - ') }}
                                </option>
                            @endforeach
                        </select>
                        @error('degree_intake_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

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

</body>

</html>
