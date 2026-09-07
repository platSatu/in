@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="mb-0">Recording: {{ $meeting->topic }}</h4>
        <a href="{{ route('zoom.meeting.index') }}" class="btn btn-outline-secondary">Kembali ke Kelola Meeting</a>
    </div>

    @if (session('error') || isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') ?? $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-3 text-muted small">
                    Terakhir disinkronkan dari Zoom:
                    {{ optional($meeting->recordings_synced_at)->format('d M Y, H:i') ?? 'belum pernah' }}
                    &mdash; halaman ini otomatis mengambil data terbaru dari Zoom setiap dibuka.
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>Tipe File</th>
                                <th>Tipe Recording</th>
                                <th>Ukuran</th>
                                <th>Mulai</th>
                                <th>Selesai</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($meeting->recordings as $recording)
                                <tr>
                                    <td class="fw-bold">{{ $recording->file_type }}</td>
                                    <td>{{ $recording->recording_type }}</td>
                                    <td>
                                        @if ($recording->file_size)
                                            {{ number_format($recording->file_size / 1048576, 2) }} MB
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ optional($recording->recording_start)->format('d M Y, H:i') ?? '-' }}</td>
                                    <td>{{ optional($recording->recording_end)->format('d M Y, H:i') ?? '-' }}</td>
                                    <td>{{ $recording->status ?? '-' }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            @if ($recording->play_url)
                                                <a href="{{ $recording->play_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-primary text-nowrap">Play</a>
                                            @endif
                                            @if ($recording->download_url)
                                                <a href="{{ $recording->download_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-success text-nowrap">Download</a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">
                                        Belum ada recording untuk meeting ini. Recording cloud biasanya baru muncul
                                        beberapa saat setelah meeting berakhir dan selesai diproses Zoom.
                                    </td>
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
