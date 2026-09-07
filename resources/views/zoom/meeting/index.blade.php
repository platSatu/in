@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Cari topik meeting..." value="{{ $search }}">
            <button type="submit" class="btn btn-outline-secondary text-nowrap">Cari</button>
        </form>
        <a href="{{ route('zoom.meeting.create') }}" class="btn btn-primary text-nowrap">+ Buat Meeting</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Topik</th>
                                <th>Jadwal</th>
                                <th>Durasi</th>
                                <th>Status</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                        {{ $item->topic }}
                                        <div class="text-muted small">ID: {{ $item->zoom_meeting_id }}</div>
                                    </td>
                                    <td>
                                        {{ optional($item->start_time)->format('d M Y, H:i') ?? '-' }}
                                        <div class="text-muted small">{{ $item->timezone }}</div>
                                    </td>
                                    <td>{{ $item->duration }} menit</td>
                                    <td>
                                        @if ($item->status === 'ended')
                                            <span class="badge badge-secondary">Ended</span>
                                        @elseif ($item->status === 'started')
                                            <span class="badge badge-success">Started</span>
                                        @else
                                            <span class="badge badge-warning">Scheduled</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap flex-nowrap justify-content-center align-items-center gap-2">
                                            @if ($item->start_url)
                                                <a href="{{ $item->start_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-sm btn-outline-success text-nowrap">Start</a>
                                            @endif

                                            @if ($item->join_url)
                                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                                    title="Copy link meeting"
                                                    onclick="copyMeetingLink('{{ $item->join_url }}')">
                                                    <i class="bi bi-link-45deg"></i>
                                                </button>
                                            @endif

                                            <a href="{{ route('zoom.meeting.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <a href="{{ route('zoom.meeting.recordings', $item->id) }}"
                                                class="btn btn-sm btn-outline-info text-nowrap">Recording</a>

                                            @if ($item->status !== 'ended')
                                                <form action="{{ route('zoom.meeting.end', $item->id) }}"
                                                    method="POST" onsubmit="return confirm('Akhiri meeting ini sekarang?');" class="m-0">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit"
                                                        class="btn btn-sm btn-outline-warning text-nowrap">End</button>
                                                </form>
                                            @endif

                                            <form action="{{ route('zoom.meeting.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus meeting ini dari Zoom & sistem?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada meeting. Klik "Buat Meeting" untuk menambah.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

<div class="toast align-items-center text-bg-success border-0 position-fixed bottom-0 end-0 m-3" id="copyLinkToast"
    role="alert" aria-live="assertive" aria-atomic="true" style="z-index: 1080;">
    <div class="d-flex">
        <div class="toast-body">Link meeting berhasil disalin.</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
</div>

<script>
    function copyMeetingLink(url) {
        function showCopiedToast() {
            var toastEl = document.getElementById('copyLinkToast');
            new bootstrap.Toast(toastEl, {delay: 2000}).show();
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(showCopiedToast).catch(function () {
                window.prompt('Salin link berikut secara manual:', url);
            });
        } else {
            window.prompt('Salin link berikut secara manual:', url);
        }
    }
</script>

@endsection
