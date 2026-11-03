@extends('layouts.frontend')

@section('content')

{{-- Students > Ina Visa (App\Http\Controllers\Student\InaVisaController): peserta form berjenis "Ina Visa". --}}
<div class="middle-content container-xxl p-0">

    <div class="layout-top-spacing"></div>

    <div class="row">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8 p-4">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="mb-1">Ina Visa</h5>
                        <div class="text-muted small">
                            Peserta yang mengisi form berjenis <b>Ina Visa</b> (atur di Quiz Form &rsaquo; Edit &rsaquo; Jenis Form).
                            Klik nama file untuk membuka, atau <b>Jawaban</b> untuk melihat semua isian peserta.
                        </div>
                    </div>
                    <form method="GET" action="{{ route('student.ina-visa.index') }}" class="m-0 d-flex flex-wrap gap-2">
                        @if ($forms->count() > 1)
                            <select name="form" class="form-select form-select-sm" style="width: 220px;" onchange="this.form.submit()" aria-label="Filter form">
                                <option value="">Semua form Ina Visa</option>
                                @foreach ($forms as $form)
                                    <option value="{{ $form->id }}" @selected($formId === $form->id)>{{ $form->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <div class="input-group flex-nowrap" style="width: 320px; max-width: 100%;">
                            <input type="text" name="search" value="{{ $search }}" class="form-control form-control-sm"
                                placeholder="Cari nama, email, WhatsApp" aria-label="Cari peserta">
                            @if ($search !== '' || $formId !== '')
                                <a href="{{ route('student.ina-visa.index') }}" class="btn btn-sm btn-outline-secondary" title="Hapus filter">&times;</a>
                            @endif
                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-search"></i> Cari</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>WhatsApp</th>
                                <th>Form</th>
                                <th>Paket</th>
                                <th>File</th>
                                <th>Pembayaran</th>
                                <th>Tanggal</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $index => $submission)
                                @php
                                    $student = $submission->student;
                                    $name = $student ? trim($student->first_name.' '.$student->last_name) : '';
                                    $payment = $submission->payment;
                                @endphp
                                <tr>
                                    <td>{{ $submissions->firstItem() + $index }}</td>
                                    <td>
                                        @if ($student)
                                            <div class="fw-bold">{{ $name ?: '-' }}</div>
                                            <div class="text-muted small">{{ $student->email ?: '-' }}</div>
                                        @else
                                            <span class="text-muted fst-italic">Peserta tanpa identitas</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ $student->handphone ?? '-' }}</td>
                                    <td>{{ $submission->form->name ?? '-' }}</td>
                                    <td>
                                        @forelse ($packages->get($submission->id, collect()) as $pkg)
                                            <div class="text-nowrap">
                                                {{ $pkg->option->option_text ?? '-' }}
                                                @if ($pkg->option && $pkg->option->price !== null)
                                                    <span class="text-muted small">· Rp{{ number_format((float) $pkg->option->price, 0, ',', '.') }}</span>
                                                @endif
                                            </div>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @forelse ($files->get($submission->id, collect()) as $file)
                                            <div class="text-nowrap">
                                                <a href="{{ asset($file->answer_text) }}" target="_blank" rel="noopener">
                                                    <i class="bi bi-paperclip"></i>
                                                    {{ \Illuminate\Support\Str::limit($file->question->question_text ?? 'File', 30) }}
                                                </a>
                                            </div>
                                        @empty
                                            <span class="text-muted small">-</span>
                                        @endforelse
                                    </td>
                                    <td class="text-nowrap">
                                        @if (! ($submission->form->requires_payment ?? false))
                                            <span class="text-muted small">Gratis</span>
                                        @elseif ($payment && $payment->status === 'paid')
                                            <span class="badge badge-success">Lunas</span>
                                            <div class="small text-muted">Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}</div>
                                        @else
                                            <span class="badge badge-warning">{{ $payment ? ucfirst($payment->status) : 'Belum bayar' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        {{ optional($submission->created_at)->translatedFormat('d M Y H:i') }}
                                        @if ($submission->is_timeout_partial)
                                            <div><span class="badge badge-light-secondary" title="Tersimpan otomatis karena waktu habis">Waktu habis</span></div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-primary text-nowrap"
                                            data-bs-toggle="modal" data-bs-target="#inaVisaAnswersModal"
                                            data-url="{{ route('student.ina-visa.answers', $submission->id) }}"
                                            data-student-name="{{ $name }}">
                                            <i class="bi bi-card-list"></i> Jawaban
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center">
                                        @if ($forms->isEmpty())
                                            Belum ada form berjenis Ina Visa. Buka Quiz Form &rsaquo; Edit, lalu pilih <b>Jenis Form: Ina Visa</b>.
                                        @elseif ($search !== '')
                                            Tidak ada peserta yang cocok dengan pencarian.
                                        @else
                                            Belum ada peserta Ina Visa.
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $submissions->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>
</div>

<style>
    /* Modal "Jawaban": background putih + teks hitam, sama seperti di halaman submission Quiz Form. */
    #inaVisaAnswersModal .modal-content { background-color: #ffffff !important; color: #1a1a1a !important; }
    #inaVisaAnswersModal .modal-header, #inaVisaAnswersModal .modal-footer { border-color: #e5e7eb !important; }
    #inaVisaAnswersModal .modal-title { color: #1a1a1a !important; }
    #inaVisaAnswersModal .quiz-answer-row { background-color: #ffffff; color: #1a1a1a; border-color: #e5e7eb !important; }
    #inaVisaAnswersModal .text-muted { color: #6c757d !important; }
</style>

{{-- Satu modal dipakai ulang untuk semua baris; isinya diambil lewat fetch() saat dibuka (tidak N+1). --}}
<div class="modal fade" id="inaVisaAnswersModal" tabindex="-1" aria-labelledby="inaVisaAnswersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="inaVisaAnswersModalLabel">Jawaban</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="inaVisaAnswersModalBody">
                <div class="text-center text-muted py-4">Memuat...</div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var modalEl = document.getElementById('inaVisaAnswersModal');
        if (!modalEl) return;

        var modalBody = document.getElementById('inaVisaAnswersModalBody');
        var modalLabel = document.getElementById('inaVisaAnswersModalLabel');

        modalEl.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            if (!button) return;

            var studentName = button.getAttribute('data-student-name') || '';
            modalLabel.textContent = studentName ? ('Jawaban: ' + studentName) : 'Jawaban';
            modalBody.innerHTML = '<div class="text-center text-muted py-4">Memuat...</div>';

            fetch(button.getAttribute('data-url'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (res) {
                    if (!res.ok) throw new Error('Gagal memuat jawaban (' + res.status + ')');
                    return res.text();
                })
                .then(function (html) { modalBody.innerHTML = html; })
                .catch(function () {
                    modalBody.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat jawaban. Silakan coba lagi.</div>';
                });
        });
    });
</script>

@endsection
