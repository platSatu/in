@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">


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

    @php
        // Validasi form akun Zoom gagal -> tetap buka tab Pengaturan.
        if ($errors->hasBag('zoomSetting')) {
            $tab = 'settings';
        }
        $activeZoomSetting = $zoomSettings->first(fn ($s) => $s->is_active && $s->status === 'active');
    @endphp

    <ul class="nav nav-tabs mb-3" id="zoomMeetingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'meetings' ? 'active' : '' }}" id="tab-meetings-btn"
                data-bs-toggle="tab" data-bs-target="#tab-meetings" data-tab="meetings" type="button" role="tab"
                aria-controls="tab-meetings" aria-selected="{{ $tab === 'meetings' ? 'true' : 'false' }}">
                <i class="bi bi-camera-video"></i> Daftar Meeting
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $tab === 'settings' ? 'active' : '' }}" id="tab-settings-btn"
                data-bs-toggle="tab" data-bs-target="#tab-settings" data-tab="settings" type="button" role="tab"
                aria-controls="tab-settings" aria-selected="{{ $tab === 'settings' ? 'true' : 'false' }}">
                <i class="bi bi-gear"></i> Pengaturan Zoom
                @unless ($activeZoomSetting)
                    <span class="badge badge-danger ms-1">!</span>
                @endunless
            </button>
        </li>
    </ul>

    <div class="tab-content">

    {{-- ================= TAB 1: DAFTAR MEETING ================= --}}
    <div class="tab-pane fade {{ $tab === 'meetings' ? 'show active' : '' }}" id="tab-meetings" role="tabpanel" aria-labelledby="tab-meetings-btn">

    <div class="page-meta mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="search" class="form-control" placeholder="Cari topik meeting..." value="{{ $search }}">
            <button type="submit" class="btn btn-outline-secondary text-nowrap">Cari</button>
        </form>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            @if ($activeZoomSetting)
                <span class="text-muted small">Akun aktif: <strong>{{ $activeZoomSetting->name ?? 'Zoom Account' }}</strong></span>
            @else
                <span class="text-danger small">Belum ada akun Zoom aktif</span>
            @endif
            <a href="{{ route('zoom.meeting.create') }}" class="btn btn-primary text-nowrap">+ Buat Meeting</a>
        </div>
    </div>

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
                                        @if ($item->zoomSetting)
                                            <div class="text-muted small"><i class="bi bi-person-video"></i> {{ $item->zoomSetting->name ?? 'Zoom Account' }}</div>
                                        @endif
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

    {{-- ================= TAB 2: PENGATURAN ZOOM ================= --}}
    <div class="tab-pane fade {{ $tab === 'settings' ? 'show active' : '' }}" id="tab-settings" role="tabpanel" aria-labelledby="tab-settings-btn">

        <div class="page-meta mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                Nyalakan switch pada akun yang ingin dipakai saat <strong>Buat Meeting</strong>.
                Hanya 1 akun yang bisa aktif; menyalakan satu akun otomatis mematikan yang lain.
            </div>
            <button type="button" class="btn btn-primary text-nowrap" onclick="openZoomSettingModal()">+ Tambah Akun Zoom</button>
        </div>

        @unless ($activeZoomSetting)
            <div class="alert alert-warning">
                Belum ada akun Zoom yang aktif, jadi meeting baru belum bisa dibuat. Nyalakan salah satu switch di bawah
                atau tambahkan akun baru.
            </div>
        @endunless

        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="widget-content widget-content-area br-8">
                    <div class="table-responsive">
                        <table class="table dt-table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Akun</th>
                                    <th>Account ID</th>
                                    <th>Client ID</th>
                                    <th>Client Secret</th>
                                    <th>Secret Token</th>
                                    <th>Meeting</th>
                                    <th class="text-center">Aktif untuk Meeting Baru</th>
                                    <th class="no-content text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($zoomSettings as $index => $setting)
                                    @php $isOn = $setting->is_active && $setting->status === 'active'; @endphp
                                    <tr class="{{ $isOn ? 'table-success' : '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-bold">
                                            {{ $setting->name ?? 'Zoom Account' }}
                                            <div class="text-muted small">Diupdate {{ optional($setting->updated_at)->format('d M Y, H:i') }}</div>
                                        </td>
                                        {{-- Kredensial ter-enkripsi di database; di layar cuma ditampilkan sebagian. --}}
                                        <td><code>{{ \Illuminate\Support\Str::mask((string) $setting->account_id, '•', 4) }}</code></td>
                                        <td><code>{{ \Illuminate\Support\Str::mask((string) $setting->client_id, '•', 4) }}</code></td>
                                        <td><code>{{ $setting->client_secret ? '••••••••' : '-' }}</code></td>
                                        <td><code>{{ $setting->secret_token ? '••••••••' : '-' }}</code></td>
                                        <td>{{ $setting->meetings_count }}</td>
                                        <td class="text-center">
                                            <form action="{{ route('zoom.meeting.settings.toggle', $setting->id) }}" method="POST" class="m-0 d-inline-block">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="is_active" value="{{ $isOn ? 0 : 1 }}">
                                                <div class="form-check form-switch d-inline-flex justify-content-center m-0">
                                                    <input class="form-check-input" type="checkbox" role="switch"
                                                        style="width: 2.75em; height: 1.4em; cursor: pointer;"
                                                        title="{{ $isOn ? 'Klik untuk menonaktifkan' : 'Klik untuk memakai akun ini saat buat meeting baru' }}"
                                                        {{ $isOn ? 'checked' : '' }}
                                                        onchange="toggleZoomSetting(this, {{ $isOn ? 'true' : 'false' }})">
                                                </div>
                                            </form>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                                <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                                    data-id="{{ $setting->id }}"
                                                    data-name="{{ $setting->name }}"
                                                    data-action="{{ route('zoom.meeting.settings.update', $setting->id) }}"
                                                    onclick="openZoomSettingModal(this)">Edit</button>

                                                <form action="{{ route('zoom.meeting.settings.destroy', $setting->id) }}" method="POST" class="m-0"
                                                    onsubmit="return confirm('{{ $setting->meetings_count > 0
                                                        ? 'Akun ini dipakai oleh ' . $setting->meetings_count . ' meeting. Setelah dihapus, meeting tersebut akan memakai akun yang aktif (bisa gagal kalau akunnya berbeda). Tetap hapus?'
                                                        : 'Hapus akun Zoom ini?' }}');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">Belum ada akun Zoom. Klik "Tambah Akun Zoom" untuk menambah.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </div>{{-- /.tab-content --}}

</div>

{{-- Modal tambah / edit akun Zoom --}}
@php $zsErrors = $errors->getBag('zoomSetting'); @endphp
<div class="modal fade" id="zoomSettingModal" tabindex="-1" aria-labelledby="zoomSettingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="zoomSettingForm" method="POST" action="{{ old('_zs_action', route('zoom.meeting.settings.store')) }}" autocomplete="off">
                @csrf
                <input type="hidden" name="_method" id="zsMethod" value="{{ old('_zs_mode') === 'edit' ? 'PUT' : 'POST' }}">
                <input type="hidden" name="_zs_mode" id="zsMode" value="{{ old('_zs_mode', 'create') }}">
                <input type="hidden" name="_zs_action" id="zsAction" value="{{ old('_zs_action', route('zoom.meeting.settings.store')) }}">

                <div class="modal-header">
                    <h5 class="modal-title" id="zoomSettingModalLabel">Tambah Akun Zoom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        Kredensial didapat dari app <strong>Server-to-Server OAuth</strong> di
                        <a href="https://marketplace.zoom.us" target="_blank" rel="noopener">marketplace.zoom.us</a>
                        (halaman App Credentials). Semua kredensial disimpan ter-enkripsi di database.
                    </div>
                    <div class="alert alert-secondary small d-none" id="zsEditHint">
                        Kosongkan field kredensial yang tidak ingin diubah.
                    </div>

                    <div class="mb-3">
                        <label for="zs_name" class="form-label">Nama Akun <span class="text-muted">(opsional)</span></label>
                        <input type="text" class="form-control {{ $zsErrors->has('name') ? 'is-invalid' : '' }}" id="zs_name" name="name"
                            placeholder="Contoh: Zoom Akun Utama" value="{{ old('name') }}">
                        @if ($zsErrors->has('name')) <div class="invalid-feedback">{{ $zsErrors->first('name') }}</div> @endif
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="zs_account_id" class="form-label">Account ID</label>
                            <input type="text" class="form-control zs-secret {{ $zsErrors->has('account_id') ? 'is-invalid' : '' }}" id="zs_account_id" name="account_id">
                            @if ($zsErrors->has('account_id')) <div class="invalid-feedback">{{ $zsErrors->first('account_id') }}</div> @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="zs_client_id" class="form-label">Client ID</label>
                            <input type="text" class="form-control zs-secret {{ $zsErrors->has('client_id') ? 'is-invalid' : '' }}" id="zs_client_id" name="client_id">
                            @if ($zsErrors->has('client_id')) <div class="invalid-feedback">{{ $zsErrors->first('client_id') }}</div> @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="zs_client_secret" class="form-label">Client Secret</label>
                            <input type="password" class="form-control zs-secret {{ $zsErrors->has('client_secret') ? 'is-invalid' : '' }}" id="zs_client_secret" name="client_secret" autocomplete="new-password">
                            @if ($zsErrors->has('client_secret')) <div class="invalid-feedback">{{ $zsErrors->first('client_secret') }}</div> @endif
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="zs_secret_token" class="form-label">Secret Token <span class="text-muted">(opsional)</span></label>
                            <input type="password" class="form-control zs-secret {{ $zsErrors->has('secret_token') ? 'is-invalid' : '' }}" id="zs_secret_token" name="secret_token" autocomplete="new-password">
                            <div class="form-text">Untuk verifikasi webhook Zoom, boleh dikosongkan.</div>
                            @if ($zsErrors->has('secret_token')) <div class="invalid-feedback">{{ $zsErrors->first('secret_token') }}</div> @endif
                        </div>
                    </div>

                    <div class="form-check form-switch" id="zsActiveWrap">
                        <input class="form-check-input" type="checkbox" role="switch" id="zs_is_active" name="is_active" value="1"
                            {{ old('_zs_mode') ? (old('is_active') ? 'checked' : '') : ($activeZoomSetting ? '' : 'checked') }}>
                        <label class="form-check-label" for="zs_is_active">Langsung pakai akun ini untuk meeting baru</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Simpan</button>
                </div>
            </form>
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

    // ---- Tab: simpan tab aktif di URL supaya tetap di tab yang sama setelah reload
    document.querySelectorAll('#zoomMeetingTabs [data-bs-toggle="tab"]').forEach(function (btn) {
        btn.addEventListener('shown.bs.tab', function () {
            var url = new URL(window.location.href);
            if (btn.dataset.tab === 'settings') {
                url.searchParams.set('tab', 'settings');
            } else {
                url.searchParams.delete('tab');
            }
            window.history.replaceState(null, '', url.toString());
        });
    });

    // ---- Switch aktif/nonaktif akun Zoom
    function toggleZoomSetting(input, wasOn) {
        var msg = wasOn
            ? 'Nonaktifkan akun ini? Meeting baru tidak bisa dibuat sampai ada akun lain yang aktif.'
            : 'Pakai akun ini untuk membuat meeting baru? Akun yang aktif sebelumnya akan dinonaktifkan.';
        if (!confirm(msg)) {
            input.checked = wasOn;
            return;
        }
        input.disabled = true;
        input.form.submit();
    }

    // ---- Modal tambah / edit akun Zoom
    var zsStoreUrl = @json(route('zoom.meeting.settings.store'));

    function openZoomSettingModal(btn, keepValues) {
        var isEdit = !!(btn && btn.dataset && btn.dataset.action);
        var form = document.getElementById('zoomSettingForm');
        var action = isEdit ? btn.dataset.action : zsStoreUrl;

        form.action = action;
        document.getElementById('zsAction').value = action;
        document.getElementById('zsMethod').value = isEdit ? 'PUT' : 'POST';
        document.getElementById('zsMode').value = isEdit ? 'edit' : 'create';
        document.getElementById('zoomSettingModalLabel').textContent = isEdit ? 'Edit Akun Zoom' : 'Tambah Akun Zoom';
        document.getElementById('zsEditHint').classList.toggle('d-none', !isEdit);
        document.getElementById('zsActiveWrap').classList.toggle('d-none', isEdit);

        if (!keepValues) {
            document.getElementById('zs_name').value = isEdit ? (btn.dataset.name || '') : '';
            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });
        }
        form.querySelectorAll('.zs-secret').forEach(function (el) {
            el.value = '';
            el.placeholder = isEdit ? 'Kosongkan jika tidak diubah' : '';
        });

        bootstrap.Modal.getOrCreateInstance(document.getElementById('zoomSettingModal')).show();
    }

    @if ($zsErrors->any())
        // Validasi gagal: buka lagi modal dengan isian sebelumnya.
        document.addEventListener('DOMContentLoaded', function () {
            var mode = @json(old('_zs_mode', 'create'));
            var fakeBtn = mode === 'edit'
                ? { dataset: { action: @json(old('_zs_action')), name: @json(old('name')) } }
                : null;
            openZoomSettingModal(fakeBtn, true);
        });
    @endif

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
