{{--
    Daftar field => nilai polos, dipakai activity-log/_detail-content.blade.php
    untuk menampilkan snapshot LENGKAP satu baris data (waktu dibuat, atau
    tepat sebelum dihapus) — beda dengan tabel "Sebelum/Sesudah" khusus event
    'updated' yang cuma menyorot field yang berubah.
--}}
@if (empty($values))
    <p class="text-muted mb-0">Tidak ada data yang tercatat.</p>
@else
    <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
            <tbody>
                @foreach ($values as $key => $value)
                    <tr>
                        <td class="fw-bold" style="width: 30%;">{{ \Illuminate\Support\Str::headline($key) }}</td>
                        <td style="word-break: break-word;">{{ \App\Helpers\ActivityLogFormatter::displayValue($value) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
