@extends('layouts.frontend')
@section('content')

{{-- Settings > Backup Database (App\Http\Controllers\Settings\DatabaseBackupController). --}}
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

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8 p-4">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="mb-1">Backup Database</h5>
                        <div class="text-muted small">
                            Backup otomatis setiap hari pukul 01.00 WIB. Disimpan {{ $keep }} backup terbaru, yang lebih lama terhapus sendiri.
                        </div>
                    </div>
                    @if ($canBackup)
                        <form method="POST" action="{{ route('settings.backup.store') }}" class="m-0"
                            onsubmit="var b = this.querySelector('button'); b.disabled = true; b.innerText = 'Sedang membuat backup...';">
                            @csrf
                            <button type="submit" class="btn btn-primary">Backup Sekarang</button>
                        </form>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama File</th>
                                <th>Tanggal</th>
                                <th>Ukuran</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($backups as $backup)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="fw-bold">
                                        {{ $backup['name'] }}
                                        @if ($loop->first)
                                            <span class="badge badge-success ms-1">Terbaru</span>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Carbon::createFromTimestamp($backup['time'], config('app.timezone'))->translatedFormat('d M Y H:i') }}</td>
                                    <td>{{ \Illuminate\Support\Number::fileSize($backup['size'], 1) }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('settings.backup.download', $backup['name']) }}"
                                            class="btn btn-sm btn-outline-primary text-nowrap">Download</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">
                                        Belum ada backup.
                                        @if ($canBackup) Klik "Backup Sekarang" untuk membuat yang pertama. @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="alert alert-light border small mt-4 mb-0">
                    File backup berisi seluruh data (student, user, pembayaran). Simpan hasil download di tempat aman dan jangan dibagikan.<br>
                    Cara memulihkan: <code>gunzip -c nama-file.sql.gz | mysql -u USER -p NAMA_DATABASE</code>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
