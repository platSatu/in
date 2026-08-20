@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-8">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('quiz.class-schedule.index') }}">Class Schedule</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Peserta</li>
                    </ol>
                </nav>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="{{ route('quiz.class-schedule.index') }}" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <h5 class="mb-1">{{ $schedule->name }} @if($schedule->level)({{ $schedule->level }})@endif</h5>
                <p class="text-muted mb-4">
                    {{ $schedule->companyBranch->name ?? '-' }}
                    &middot; {{ optional($schedule->class_date)->format('d/m/Y') }}
                    @if($schedule->start_time)
                        &middot; {{ substr($schedule->start_time, 0, 5) }}
                    @endif
                    &middot; {{ $enrollments->count() }} / {{ $schedule->capacity }} terisi
                </p>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Student</th>
                                <th>Email</th>
                                <th>WhatsApp</th>
                                <th>Terdaftar Pada</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($enrollments as $index => $enrollment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ trim(optional($enrollment->student)->first_name . ' ' . optional($enrollment->student)->last_name) ?: '-' }}</td>
                                    <td>{{ optional($enrollment->student)->email ?? '-' }}</td>
                                    <td>{{ optional($enrollment->student)->handphone ?? '-' }}</td>
                                    <td>{{ optional($enrollment->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada peserta yang terdaftar di kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
