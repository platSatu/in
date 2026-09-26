{{--
    Daftar kelas 1 pengajar dalam 1 periode (1 kelas = 1 credit/paket).
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
                    <td class="text-end text-nowrap">Rp {{ number_format((float) $class['fee'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
