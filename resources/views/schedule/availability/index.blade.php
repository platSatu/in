@extends('layouts.frontend')
@section('content')

{{--
    Jadwal > Ketersediaan Pengajar -- lihat TeacherAvailabilityController.
    Satu kartu = jam buka 1 pengajar di 1 cabang, berlaku setiap minggu.
--}}

<div class="middle-content container-xxl p-0">
    <p class="text-muted mb-3">Atur jam buka setiap pengajar. Siswa hanya bisa memilih jam kelas di dalam jam buka ini.</p>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h6 class="mb-0">Atur Jam Buka Pengajar</h6>
                    <a href="{{ route('schedule.index') }}" class="btn btn-outline-secondary btn-sm">&larr; Kembali ke Jadwal</a>
                </div>

                <form method="GET" action="{{ route('schedule.availability.edit') }}" class="row g-2 align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted mb-1">Pengajar</label>
                        <select name="teacher_user_id" class="form-select" required>
                            <option value="">-- Pilih Pengajar --</option>
                            @foreach ($teachers as $teacher)
                                <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Cabang</label>
                        <select name="branch_id" class="form-select" required>
                            @if ($branches->count() > 1)
                                <option value="">-- Pilih Cabang --</option>
                            @endif
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Atur Jam Buka</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse ($groups as $rows)
            @php($first = $rows->first())
            <div class="col-md-6 col-xl-4 layout-spacing">
                <div class="widget-content widget-content-area br-8 h-100">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0">{{ $first->teacher?->name ?? '-' }}</h6>
                            <small class="text-muted">{{ $first->branch?->name ?? '-' }}</small>
                        </div>
                        <a href="{{ route('schedule.availability.edit', ['teacher_user_id' => $first->teacher_user_id, 'branch_id' => $first->branch_id]) }}"
                            class="btn btn-outline-primary btn-sm">Ubah</a>
                    </div>
                    <ul class="list-unstyled mb-0">
                        @foreach ($rows as $row)
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span>{{ $row->dayName() }}</span>
                                <strong>{{ $row->hoursLabel() }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="col-12">
                <p class="text-center text-muted py-4">Belum ada pengajar yang punya jam buka. Pilih pengajar di atas untuk mulai mengatur.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
