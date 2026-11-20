{{--
    Daftar kelas 1 pengajar dalam 1 periode (1 kelas = 1 credit/paket).
    Sejak 20 November 2026 kelas bisa dihitung Per Jam (fee x credit); rekap lama tanpa 'honor' tetap pakai 'fee'.
    Dipakai halaman detail Honor Pengajar & Jadwal pengajar.
    $classes: array hasil TeacherHonorService::recap()['classes'].
--}}
<div class="table-responsive">
    <table class="table mb-0" style="width:100%">
        <thead>
            <tr>
                <th>Kelas</th>
                <th>Jenis</th>
                <th>Pertemuan</th>
                <th>Credit Terpakai</th>
                <th class="text-end">Fee</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($classes as $class)
                <tr>
                    <td>
                        <div class="fw-bold">{{ $class['owner'] }}</div>
                        <div class="text-muted small">{{ $class['package'] }}</div>
                        <details class="mt-1">
                            <summary class="small text-primary" style="cursor:pointer">Lihat pertemuan</summary>
                            <ul class="list-unstyled small mb-0 mt-1">
                                @foreach ($class['sessions'] as $session)
                                    <li>
                                        {{ \Carbon\Carbon::parse($session['at'])->translatedFormat('d M Y, H:i') }}
                                        &middot; {{ $session['student'] }}
                                        &middot; {{ rtrim(rtrim(number_format((float) $session['credit'], 2, ',', '.'), '0'), ',') }} credit
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    </td>
                    <td>{{ $class['class_name'] }}</td>
                    <td>{{ count($class['sessions']) }}x</td>
                    <td>{{ rtrim(rtrim(number_format((float) $class['credit_total'], 2, ',', '.'), '0'), ',') }}</td>
                    <td class="text-end text-nowrap">
                        Rp {{ number_format((float) ($class['honor'] ?? $class['fee']), 0, ',', '.') }}
                        @if (($class['honor_mode'] ?? null) === \App\Models\CourseClass::HONOR_PER_HOUR)
                            <div class="small text-muted">Rp {{ number_format((float) $class['fee'], 0, ',', '.') }}/jam &times; {{ rtrim(rtrim(number_format((float) $class['credit_total'], 2, ',', '.'), '0'), ',') }} jam</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
