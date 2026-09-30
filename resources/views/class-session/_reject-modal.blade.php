{{-- Modal tolak pengajuan (dipakai tabel menunggu admin & menunggu pengajar). Butuh $session. --}}
<div class="modal fade" id="rejectModal-{{ $session->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('class-session.reject', $session->id) }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title">Tolak Pengajuan</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Alasan (opsional)</label>
                    <textarea name="reason" class="form-control" rows="2"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
</div>
