@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-0">Activity Log</h5>
            <small class="text-muted">
                Jejak aktivitas admin: siapa login, dan siapa mengubah data apa (termasuk isi data sebelum &amp;
                sesudahnya).
            </small>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('activity-log.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <select name="event" class="form-select" onchange="this.form.submit()">
                                <option value="">Semua Aktivitas</option>
                                <option value="login" {{ $event === 'login' ? 'selected' : '' }}>Login</option>
                                <option value="logout" {{ $event === 'logout' ? 'selected' : '' }}>Logout</option>
                                <option value="created" {{ $event === 'created' ? 'selected' : '' }}>Create</option>
                                <option value="updated" {{ $event === 'updated' ? 'selected' : '' }}>Update</option>
                                <option value="deleted" {{ $event === 'deleted' ? 'selected' : '' }}>Delete</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search user/modul/aktivitas..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                {{--
                    SATU tabel untuk semua jenis aktivitas (login/logout/create/update/
                    delete) sesuai permintaan — dibungkus .table-responsive (pola yang
                    sama persis dipakai di SEMUA halaman index lain di aplikasi ini)
                    supaya di layar sempit tabel ini discroll ke samping, BUKAN kolomnya
                    saling menumpuk/kepotong. Kolom teks panjang (Aktivitas/Data) juga
                    sengaja dibatasi lebar maksimalnya (lihat style="max-width") supaya
                    tidak ada satu baris pun yang memaksa tabel melebar berlebihan cuma
                    gara-gara satu nilai yang kebetulan panjang.
                --}}
                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th class="text-nowrap">Waktu</th>
                                <th>User</th>
                                <th>Aktivitas</th>
                                <th>Modul &amp; Data</th>
                                <th class="no-content text-center">Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                @php
                                    $eventBadge = [
                                        'login' => 'badge-success',
                                        'logout' => 'badge-secondary',
                                        'created' => 'badge-primary',
                                        'updated' => 'badge-warning',
                                        'deleted' => 'badge-danger',
                                    ][$item->event] ?? 'badge-secondary';

                                    $hasDetail = !empty($item->old_values) || !empty($item->new_values);
                                @endphp
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="text-nowrap">{{ optional($item->created_at)->format('Y-m-d H:i:s') }}</td>
                                    <td style="max-width: 220px;">
                                        <div class="fw-bold text-truncate" title="{{ $item->actor_name }}">
                                            {{ $item->actor_name ?: '-' }}
                                        </div>
                                        <div class="text-muted text-truncate" style="font-size: 12px;" title="{{ $item->actor_email }}">
                                            {{ $item->actor_email ?: '-' }}
                                        </div>
                                    </td>
                                    <td style="max-width: 260px;">
                                        <span class="badge {{ $eventBadge }} text-uppercase mb-1">{{ $item->event }}</span>
                                        <div class="text-truncate" title="{{ $item->description }}">{{ $item->description }}</div>
                                    </td>
                                    <td style="max-width: 260px;">
                                        @if ($item->subject_type)
                                            <span class="badge badge-secondary text-nowrap">{{ \Illuminate\Support\Str::headline($item->subject_type) }}</span>
                                            <div class="text-truncate text-muted" style="font-size: 13px;" title="{{ $item->subject_label }}">
                                                {{ $item->subject_label ?: '-' }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($hasDetail)
                                            <button type="button" class="btn btn-sm btn-outline-dark text-nowrap"
                                                data-bs-toggle="modal" data-bs-target="#activityLogDetailModal"
                                                data-url="{{ route('activity-log.detail', $item->id) }}">
                                                Lihat Detail
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center">Belum ada aktivitas yang tercatat.</td>
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

{{--
    Modal detail before/after — pola sama persis dengan #formDetailModal di
    quiz/form/index.blade.php (satu modal dipakai bareng semua baris, isinya
    diganti tiap kali tombol "Lihat Detail" beda diklik lewat fetch()).
--}}
<style>
    #activityLogDetailModal .modal-content {
        background-color: #ffffff !important;
        color: #1a1a1a !important;
    }
    #activityLogDetailModal .modal-header,
    #activityLogDetailModal .modal-footer {
        border-color: #e5e7eb !important;
    }
    #activityLogDetailModal .modal-title {
        color: #1a1a1a !important;
    }
    #activityLogDetailModal .table {
        color: #1a1a1a;
    }
</style>
<div class="modal fade" id="activityLogDetailModal" tabindex="-1" aria-labelledby="activityLogDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="activityLogDetailModalLabel">Detail Aktivitas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="activityLogDetailModalBody">
                <div class="text-center text-muted py-4">Memuat...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Sama alasannya dengan script serupa di quiz/form/index.blade.php: dibungkus
    // DOMContentLoaded supaya `bootstrap` (dimuat di footer layout, SETELAH
    // @yield('content')) sudah pasti ada sebelum listener show.bs.modal dipasang.
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('activityLogDetailModal');
        if (!modalEl) return;

        var modalBody = document.getElementById('activityLogDetailModalBody');

        modalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var url = button.getAttribute('data-url');
            modalBody.innerHTML = '<div class="text-center text-muted py-4">Memuat...</div>';

            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('Gagal memuat detail (status ' + res.status + ').');
                    }
                    return res.text();
                })
                .then(function (html) {
                    modalBody.innerHTML = html;
                })
                .catch(function () {
                    modalBody.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat detail aktivitas. Silakan coba lagi.</div>';
                });
        });
    });
</script>

@endsection
