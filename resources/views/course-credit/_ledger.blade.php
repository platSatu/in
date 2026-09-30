{{--
    Riwayat mutasi credit 1 siswa (hanya baca, tanpa aksi). Dipakai di tab
    "Riwayat Credit" siswa dan halaman admin Riwayat Credit Siswa.
    Butuh $ledger (paginator dari CourseCredit::ledgerFor()).
--}}
@php
    $creditNum = fn ($value) => rtrim(rtrim(number_format((float) $value, 2, ',', '.'), '0'), ',');
    $creditTypes = [
        'purchase' => ['Beli / Dapat Paket', 'badge-success'],
        'session_debit' => ['Kelas', 'badge-primary'],
        'session_refund' => ['Refund', 'badge-info'],
        'trade_in_debit' => ['Tukar (Upgrade/Convert)', 'badge-warning'],
    ];
@endphp

<div class="table-responsive">
    <table class="table align-middle mb-0">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Keterangan</th>
                <th>Paket</th>
                <th class="text-end">Masuk</th>
                <th class="text-end">Keluar</th>
                <th class="text-end">Sisa</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ledger as $row)
                @php([$typeLabel, $typeClass] = $creditTypes[$row->source_type] ?? [$row->source_type, 'badge-secondary'])
                <tr>
                    <td class="text-nowrap">{{ $row->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                    <td><span class="badge {{ $typeClass }}">{{ $typeLabel }}</span></td>
                    <td>{{ $row->description ?? '-' }}</td>
                    <td>{{ $row->packageLabel() }}</td>
                    <td class="text-end text-success">{{ (float) $row->kredit > 0 ? '+' . $creditNum($row->kredit) : '-' }}</td>
                    <td class="text-end text-danger">{{ (float) $row->debit > 0 ? '−' . $creditNum($row->debit) : '-' }}</td>
                    <td class="text-end fw-bold">{{ $creditNum($row->balance) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat credit.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $ledger->links('pagination::bootstrap-5') }}
</div>
