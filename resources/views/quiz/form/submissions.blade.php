@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h4 class="mb-0">{{ $form->name }}</h4>
            <small class="text-muted">
                {{ optional($form->companyBranch)->name ?? '-' }}
                @if ($form->slug && $form->booth_slug)
                    &middot; {{ url('/quiz/' . $form->slug . '/' . $form->booth_slug) }}
                @endif
            </small>
        </div>
        <a href="{{ route('quiz.form.index') }}" class="btn btn-outline-secondary">&larr; Kembali ke daftar form</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- === RINGKASAN === --}}
    <div class="row layout-top-spacing g-3 mb-1">
        <div class="col-md-3 col-sm-6">
            <div class="widget-content widget-content-area br-8 text-center py-3">
                <div class="fs-4 fw-bold">{{ $submissions->count() }}</div>
                <div class="text-muted small">Total Submission</div>
            </div>
        </div>

        @if ($form->requires_payment)
            @php
                $paidPayments = $payments->where('status', 'paid');
                $totalPaidAmount = $paidPayments->sum('amount');
                $pendingCount = $payments->where('status', 'pending')->count();
                $failedOrExpiredCount = $payments->whereIn('status', ['failed', 'expired'])->count();
                $paidWithoutSubmission = $paidPayments->whereNull('form_submission_id')->count();
                $submissionsWithoutPayment = $submissions->filter(fn ($s) => !$s->payment || $s->payment->status !== 'paid')->count();
            @endphp
            <div class="col-md-3 col-sm-6">
                <div class="widget-content widget-content-area br-8 text-center py-3">
                    <div class="fs-4 fw-bold">Rp {{ number_format((float) $totalPaidAmount, 0, ',', '.') }}</div>
                    <div class="text-muted small">Total Terkumpul ({{ $paidPayments->count() }} transaksi paid)</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="widget-content widget-content-area br-8 text-center py-3">
                    <div class="fs-4 fw-bold">{{ $pendingCount }} / {{ $failedOrExpiredCount }}</div>
                    <div class="text-muted small">Pending / Gagal-Kadaluarsa</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="widget-content widget-content-area br-8 text-center py-3">
                    <div class="fs-4 fw-bold {{ ($paidWithoutSubmission + $submissionsWithoutPayment) > 0 ? 'text-danger' : '' }}">
                        {{ $paidWithoutSubmission + $submissionsWithoutPayment }}
                    </div>
                    <div class="text-muted small">Anomali (submit tanpa bayar / bayar tanpa submit)</div>
                </div>
            </div>
        @else
            <div class="col-md-9 col-sm-6">
                <div class="widget-content widget-content-area br-8 text-center py-3 d-flex align-items-center justify-content-center">
                    <span class="text-muted">Form ini gratis (tidak butuh pembayaran).</span>
                </div>
            </div>
        @endif
    </div>

    {{-- === DAFTAR PESERTA / SUBMISSION === --}}
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">
                <h5 class="mb-3">Daftar Peserta (Submission)</h5>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>Handphone</th>
                                <th>Email</th>
                                <th>Waktu Submit</th>
                                @if ($form->requires_payment)
                                    <th class="text-center">Status Pembayaran</th>
                                @endif
                                @if ($form->result_mode !== 'none')
                                    <th class="text-center">Hasil</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($submissions as $index => $submission)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td class="fw-bold">
                                        @if ($submission->student)
                                            {{ trim($submission->student->first_name . ' ' . $submission->student->last_name) }}
                                        @else
                                            <span class="text-muted">Data peserta tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td>{{ optional($submission->student)->handphone ?? '-' }}</td>
                                    <td>{{ optional($submission->student)->email ?? '-' }}</td>
                                    <td>{{ optional($submission->created_at)->format('Y/m/d H:i') }}</td>
                                    @if ($form->requires_payment)
                                        <td class="text-center">
                                            @if ($submission->payment && $submission->payment->status === 'paid')
                                                <span class="badge badge-success">Paid</span>
                                            @elseif ($submission->payment)
                                                <span class="badge badge-warning">{{ ucfirst($submission->payment->status) }}</span>
                                            @else
                                                <span class="badge badge-danger">Tidak ada transaksi</span>
                                            @endif
                                        </td>
                                    @endif
                                    @if ($form->result_mode !== 'none')
                                        <td class="text-center" style="min-width: 260px;">
                                            @if ($form->result_mode === 'auto')
                                                @if ($submission->result)
                                                    <span class="badge badge-success">Skor: {{ rtrim(rtrim((string) $submission->result->score, '0'), '.') ?: '0' }}</span>
                                                @else
                                                    <span class="badge badge-secondary">Belum ada hasil</span>
                                                @endif
                                            @else
                                                {{-- Mode manual: textarea-nya langsung tampil di sini, tidak lewat modal
                                                lagi, supaya admin bisa langsung ketik & simpan hasilnya. --}}
                                                <form action="{{ route('quiz.form.submissions.save-result', $submission->id) }}" method="POST" class="text-start">
                                                    @csrf
                                                    <textarea class="form-control form-control-sm mb-1" name="summary_text"
                                                        rows="2" placeholder="Tulis hasil di sini..." required>{{ old('summary_text', optional($submission->result)->summary_text) }}</textarea>
                                                    <button type="submit" class="btn btn-sm btn-brand w-100">
                                                        {{ $submission->result && $submission->result->summary_text ? 'Update Hasil' : 'Simpan Hasil' }}
                                                    </button>
                                                    @if ($form->use_whatsapp_notification)
                                                        <div class="form-text mb-0">Otomatis dikirim ke WA (sekali per hasil).</div>
                                                    @endif
                                                </form>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 5 + ($form->requires_payment ? 1 : 0) + ($form->result_mode !== 'none' ? 1 : 0) }}" class="text-center">
                                        Belum ada peserta yang submit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- === DAFTAR TRANSAKSI PEMBAYARAN (data pembanding) === --}}
    @if ($form->requires_payment)
        <div class="row layout-top-spacing">
            <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
                <div class="widget-content widget-content-area br-8">
                    <h5 class="mb-1">Daftar Transaksi Pembayaran</h5>
                    <p class="text-muted small mb-3">
                        Dipakai sebagai data pembanding terhadap tabel submission di atas — kalau ada transaksi
                        "Paid" yang kolom "Submission Terhubung"-nya kosong, atau ada peserta submit tanpa
                        transaksi paid, kemungkinan ada anomali/kebocoran yang perlu dicek manual.
                    </p>

                    <div class="table-responsive">
                        <table class="table dt-table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Handphone</th>
                                    <th>Order ID</th>
                                    <th class="text-end">Jumlah</th>
                                    <th class="text-center">Status</th>
                                    <th>Waktu Bayar</th>
                                    <th class="text-center">Submission Terhubung</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($payments as $index => $payment)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td class="fw-bold text-nowrap">{{ $payment->name }}</td>
                                        <td>{{ $payment->handphone }}</td>
                                        <td class="text-nowrap">{{ $payment->order_id }}</td>
                                        <td class="text-end text-nowrap">
                                            Rp {{ number_format((float) $payment->amount, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $statusBadge = match ($payment->status) {
                                                    'paid' => 'badge-success',
                                                    'pending' => 'badge-warning',
                                                    default => 'badge-danger',
                                                };
                                            @endphp
                                            <span class="badge {{ $statusBadge }}">{{ ucfirst($payment->status) }}</span>
                                        </td>
                                        <td>{{ optional($payment->paid_at)->format('Y/m/d H:i') ?? '-' }}</td>
                                        <td class="text-center">
                                            @if ($payment->form_submission_id)
                                                <span class="badge badge-success">Ya</span>
                                            @elseif ($payment->status === 'paid')
                                                <span class="badge badge-danger" title="Paid tapi tidak ada submission terhubung">Tidak (!)</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Belum ada transaksi pembayaran.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>

@endsection
