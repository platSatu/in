{{--
    Fragmen HTML modal "Lihat Detail" di activity-log/index.blade.php. Diisi
    lewat fetch() ke route('activity-log.detail', $log->id) begitu tombol
    "Lihat Detail" per baris diklik. Read-only murni, tidak ada <form>/tombol
    aksi apa pun di sini — sama seperti quiz/form/_detail-content.blade.php.
--}}
<div class="activity-log-detail-content">
    <div class="row g-2 mb-3">
        <div class="col-sm-6">
            <div class="text-muted small">Waktu</div>
            <div class="fw-bold">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Dilakukan oleh</div>
            <div class="fw-bold">{{ $log->actor_name ?: '-' }}</div>
            <div class="text-muted" style="font-size: 13px;">{{ $log->actor_email ?: '-' }}</div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">Aktivitas</div>
            <div class="fw-bold">{{ $log->description }}</div>
        </div>
        <div class="col-sm-6">
            <div class="text-muted small">IP Address</div>
            <div class="fw-bold">{{ $log->ip_address ?: '-' }}</div>
        </div>
    </div>

    <hr>

    @if (in_array($log->event, ['login', 'logout']))
        {{-- Login/logout: histori login sudah cukup dijelaskan lewat waktu +
             deskripsi di atas, tidak perlu tabel before/after mentah dari tabel
             history_user_login yang isinya cuma last_login/last_logout/duration. --}}
        <p class="text-muted mb-0">
            {{ $log->event === 'login' ? 'User berhasil login ke sistem pada waktu di atas.' : 'User logout dari sistem pada waktu di atas.' }}
        </p>
    @elseif ($log->event === 'created')
        <h6>Data yang dibuat</h6>
        @include('activity-log._value-list', ['values' => $log->new_values])
    @elseif ($log->event === 'deleted')
        <h6>Data sebelum dihapus</h6>
        @include('activity-log._value-list', ['values' => $log->old_values])
        <div class="alert alert-secondary mt-3 mb-0">
            Setelah dihapus: data ini sudah tidak ada lagi di sistem.
        </div>
    @elseif ($log->event === 'updated')
        @php $changedKeys = $log->changedKeys(); @endphp
        <h6>Perubahan data</h6>
        @if (empty($changedKeys))
            <p class="text-muted mb-0">Tidak ada perubahan nilai yang tercatat untuk update ini.</p>
        @else
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead>
                        <tr>
                            <th style="width: 22%;">Field</th>
                            <th style="width: 39%;">Sebelum</th>
                            <th style="width: 39%;">Sesudah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($changedKeys as $key)
                            <tr>
                                <td class="fw-bold">{{ \Illuminate\Support\Str::headline($key) }}</td>
                                <td style="word-break: break-word;">{{ \App\Helpers\ActivityLogFormatter::displayValue($log->old_values[$key] ?? null) }}</td>
                                <td style="word-break: break-word;">{{ \App\Helpers\ActivityLogFormatter::displayValue($log->new_values[$key] ?? null) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <p class="text-muted mb-0">Tidak ada detail tambahan untuk aktivitas ini.</p>
    @endif
</div>
