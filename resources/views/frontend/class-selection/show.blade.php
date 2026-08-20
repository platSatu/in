<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Pilih Kelas | INASTUDY | CHINA EDUCATION CONSULTANT</title>
    <link rel="icon" type="image/png" href="{{ asset('frontend/img/Logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand: #e02424;
            --brand-dark: #8a0e0e;
            --brand-light: #fde3e3;
        }

        body {
            background-color: #f5f7fb;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2b2f38;
            min-height: 100vh;
        }

        .brand-header {
            text-align: center;
            padding-top: 50px;
        }

        .brand-header img {
            height: 60px;
            margin-bottom: 10px;
        }

        .brand-header .brand-name {
            font-weight: 800;
            font-size: 15px;
            letter-spacing: .04em;
            color: var(--brand-dark);
            text-transform: uppercase;
        }

        .selection-card {
            max-width: 620px;
            margin: auto;
            margin-top: 18px;
            margin-bottom: 60px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(20, 30, 60, .1);
            overflow: hidden;
            background: #fff;
        }

        .selection-card .card-body {
            padding: 45px !important;
        }

        .class-option {
            border: 1px solid #e6e8ee;
            border-radius: 14px;
            padding: 16px 18px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .class-option .class-name {
            font-weight: 700;
            margin-bottom: 2px;
        }

        .class-option .class-meta {
            font-size: 13px;
            color: #6b7280;
        }

        .btn-brand {
            background-color: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-brand:hover {
            background-color: var(--brand-dark);
            border-color: var(--brand-dark);
            color: #fff;
        }
    </style>
</head>

<body>

    <div class="brand-header">
        <img src="{{ asset('frontend/img/Logo.png') }}" alt="Logo">
        <div class="brand-name">INASTUDY</div>
    </div>

    <div class="selection-card card">
        <div class="card-body">

            <h4 class="mb-1">Pilih Kelas</h4>
            <p class="text-muted mb-4">
                Halo {{ trim(optional($submission->student)->first_name . ' ' . optional($submission->student)->last_name) ?: '' }},
                silakan pilih jadwal kelas yang sesuai untuk Anda.
            </p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if (session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif

            @error('class_schedule_id')
                <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            @if ($existingEnrollment && $existingEnrollment->classSchedule)
                @php $picked = $existingEnrollment->classSchedule; @endphp
                <div class="class-option" style="border-color: var(--brand); background: var(--brand-light);">
                    <div>
                        <div class="class-name">{{ $picked->name }} @if($picked->level)({{ $picked->level }})@endif</div>
                        <div class="class-meta">
                            <i class="bi bi-calendar-event"></i>
                            {{ optional($picked->class_date)->format('d/m/Y') }}
                            @if($picked->start_time)
                                &middot; {{ substr($picked->start_time, 0, 5) }}
                            @endif
                        </div>
                    </div>
                    <span class="badge bg-success">Terdaftar</span>
                </div>
            @elseif ($schedules->isEmpty())
                <div class="alert alert-warning mb-0">
                    Belum ada jadwal kelas yang tersedia untuk cabang Anda saat ini. Tim kami akan segera
                    menghubungi Anda untuk informasi jadwal selanjutnya.
                </div>
            @else
                <form action="{{ route('frontend.class-selection.store', $submission->id) }}" method="POST">
                    @csrf

                    @foreach ($schedules as $schedule)
                        @php
                            $remaining = max(0, $schedule->capacity - $schedule->active_enrollments_count);
                            $isFull = $remaining <= 0;
                        @endphp
                        <div class="class-option">
                            <div>
                                <div class="class-name">{{ $schedule->name }} @if($schedule->level)({{ $schedule->level }})@endif</div>
                                <div class="class-meta">
                                    <i class="bi bi-calendar-event"></i>
                                    {{ optional($schedule->class_date)->format('d/m/Y') }}
                                    @if($schedule->start_time)
                                        &middot; {{ substr($schedule->start_time, 0, 5) }}
                                    @endif
                                    &middot; {{ $isFull ? 'Penuh' : $remaining . ' slot tersisa' }}
                                </div>
                            </div>
                            <button type="submit" name="class_schedule_id" value="{{ $schedule->id }}"
                                class="btn btn-sm {{ $isFull ? 'btn-outline-secondary' : 'btn-brand' }}"
                                {{ $isFull ? 'disabled' : '' }}>
                                {{ $isFull ? 'Penuh' : 'Pilih' }}
                            </button>
                        </div>
                    @endforeach
                </form>
            @endif

        </div>
    </div>

</body>

</html>
