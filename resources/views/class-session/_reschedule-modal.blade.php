{{--
    Admin memindahkan jadwal 1 siswa (mis. siswa sakit / ada urusan mendadak).
    Siswa lain di kelas yang sama tidak ikut pindah. Lihat
    ClassSessionWorkflowService::reschedule().
--}}
<div class="modal fade" id="rescheduleModal-{{ $session->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('class-session.reschedule', $session->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Pindah Jadwal</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-start">
                    <p class="mb-2">
                        <strong>{{ $session->student ? trim($session->student->first_name . ' ' . $session->student->last_name) : '-' }}</strong>
                        &middot; {{ optional($session->teacher)->name ?? '-' }}
                    </p>
                    <p class="text-muted small">
                        Jadwal sekarang: {{ optional($session->classStartsAt())->translatedFormat('l, d M Y') }}, {{ $session->classHoursLabel() }}.
                        Lama kelas tetap {{ rtrim(rtrim(number_format((float) $session->credit_amount_requested, 2, ',', '.'), '0'), ',') }} jam.
                    </p>
                    <label class="form-label">Jadwal baru</label>
                    <input type="datetime-local" name="class_start_at" class="form-control" required step="60"
                        min="{{ now()->format('Y-m-d\TH:i') }}"
                        value="{{ optional($session->class_start_at)->format('Y-m-d\TH:i') }}">
                    <small class="text-muted d-block mt-2">Boleh di luar jam buka pengajar, tapi tidak boleh bentrok dengan kelas lain pengajar ini atau melebihi kuota kelas.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Pindahkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
