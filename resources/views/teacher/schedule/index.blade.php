@extends('layouts.frontend')
@section('content')

{{--
    "Jadwal" -- sisi pengajar (17 September 2026), halaman tersendiri
    (dipisah dari halaman Approval). Lihat docblock
    App\Http\Controllers\Teacher\ScheduleController untuk keputusan scope:
    ini laporan dari data ClassSession milik pengajar yang login sendiri,
    BUKAN sistem booking slot ke depan.
--}}

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <h5 class="mb-1">Jadwal</h5>
        <p class="text-muted mb-0">Halo {{ auth()->user()->name }}, ini kelas dan jadwal mengajar kamu.</p>
    </div>

    {{-- Honor saya -- lihat TeacherHonorService (1 kelas = 1 credit x fee Course Class). --}}
    @foreach ($runningHonors as $item)
        <div class="widget-content widget-content-area br-8 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h6 class="mb-1">Kelas kamu di periode {{ $item['period']->name }} &middot; {{ optional($item['period']->branch)->name }}</h6>
                    <p class="text-muted small mb-0">{{ $item['period']->start_date->translatedFormat('d M') }} &ndash; {{ $item['period']->end_date->translatedFormat('d M Y') }} &middot; masih berjalan, angka bisa bertambah</p>
                </div>
                <div class="text-end">
                    <div class="text-muted small">Honor sementara</div>
                    <h5 class="mb-0">Rp {{ number_format((float) $item['row']['honor_amount'], 0, ',', '.') }}</h5>
                    <div class="small">{{ $item['row']['class_count'] }} kelas</div>
                </div>
            </div>
            @include('teacher-honor._classes', ['classes' => $item['row']['classes']])
        </div>
    @endforeach

    @if ($closedHonors->isNotEmpty())
        <div class="widget-content widget-content-area br-8 mb-3">
            <h6 class="mb-3">Riwayat honor kamu</h6>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Periode</th>
                            <th>Kelas</th>
                            <th>Honor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($closedHonors as $honor)
                            @php
                                $honorStatus = [
                                    'pending' => ['Sedang dicek', 'badge-warning'],
                                    'approved_for_payout' => ['Disetujui, segera dibayar', 'badge-info'],
                                    'paid' => ['Sudah dibayar', 'badge-success'],
                                ][$honor->status] ?? [$honor->status, 'badge-secondary'];
                            @endphp
                            <tr>
                                <td>{{ optional($honor->period)->name ?? '-' }} &middot; {{ optional(optional($honor->period)->branch)->name }}</td>
                                <td>{{ $honor->class_count }} kelas</td>
                                <td class="text-nowrap">Rp {{ number_format((float) $honor->honor_amount, 0, ',', '.') }}</td>
                                <td><span class="badge {{ $honorStatus[1] }}">{{ $honorStatus[0] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <div class="mb-4">
                    <form method="GET" action="{{ route('teacher.schedule.index') }}" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="from" class="form-control" value="{{ $from->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="to" class="form-control" value="{{ $to->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                        </div>
                    </form>
                </div>

                @forelse ($sessions as $date => $dateSessions)
                    <h6 class="mt-4 mb-2">{{ \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y') }}</h6>
                    <div class="table-responsive mb-3">
                        <table class="table dt-table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    <th>Siswa</th>
                                    <th>Package</th>
                                    <th>Cabang</th>
                                    <th>Credit</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dateSessions as $session)
                                    <tr>
                                        <td>{{ optional($session->requested_at)->format('H:i') }}</td>
                                        <td>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</td>
                                        <td>{{ optional($session->coursePackage)->name ?? '-' }}
                                        @if (optional(optional($session->coursePackage)->courseClass)->name)
                                            <div class="text-muted small">Kelas {{ $session->coursePackage->courseClass->name }}</div>
                                        @endif
                                    </td>
                                        <td>{{ optional($session->branch)->name ?? '-' }}</td>
                                        <td>{{ number_format((float) $session->credit_amount_requested, 2, ',', '.') }}</td>
                                        <td>
                                            @php
                                                $teacherScheduleStatusLabel = [
                                                    'menunggu_guru' => ['Menunggu Anda', 'badge-warning'],
                                                    'ditolak_guru' => ['Ditolak Anda', 'badge-danger'],
                                                    'menunggu_admin' => ['Menunggu Admin', 'badge-warning'],
                                                    'disetujui' => ['Disetujui', 'badge-success'],
                                                    'ditolak_admin' => ['Ditolak Admin', 'badge-danger'],
                                                ][$session->status] ?? [$session->status, 'badge-secondary'];
                                            @endphp
                                            <span class="badge {{ $teacherScheduleStatusLabel[1] }}">{{ $teacherScheduleStatusLabel[0] }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <p class="text-center text-muted py-4">Belum ada jadwal di rentang tanggal ini. Coba ubah tanggalnya ya.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
