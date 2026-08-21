@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="badge badge-primary" style="font-size: 0.95rem; padding: 0.5rem 0.9rem;">
            Total Form Dibuat: {{ $totalForms }}
        </span>
        <a href="{{ route('quiz.form.create') }}" class="btn btn-primary">+ Add Form</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('quiz.form.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Search form..."
                                value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2 d-grid">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Name</th>
                                <th class="text-nowrap">Company Branch</th>
                                <th>No Booth</th>
                                <th>Description</th>
                                <th class="text-center text-nowrap">Submission</th>
                                <th class="text-center text-nowrap">Payment</th>
                                <th class="text-center text-nowrap">Viewer</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $form)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                        {{ $form->name }}
                                        @if ($form->slug && $form->booth_slug)
                                            @php
                                                $publicFormUrl = url('/quiz/' . $form->slug . '/' . $form->booth_slug);
                                            @endphp
                                            <br>
                                            <small class="text-muted d-inline-flex align-items-center gap-1">
                                                {{-- Link URL sekarang bisa diklik langsung, buka preview di tab baru --}}
                                                <a href="{{ $publicFormUrl }}" target="_blank" rel="noopener"
                                                    class="copy-url-text">{{ $publicFormUrl }}</a>
                                                <button type="button" class="btn btn-sm btn-link p-0 copy-url-btn"
                                                    data-url="{{ $publicFormUrl }}" title="Salin URL">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                                                        viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                        class="feather feather-copy">
                                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                                    </svg>
                                                </button>
                                                {{-- Share: khusus WhatsApp & Email saja (bukan Web Share API generik) —
                                                     link langsung ("wa.me"/"mailto:"), tidak perlu JS sama sekali. --}}
                                                <div class="dropdown d-inline-block">
                                                    <button type="button" class="btn btn-sm btn-link p-0"
                                                        data-bs-toggle="dropdown" aria-expanded="false"
                                                        title="Bagikan link">
                                                        <i class="bi bi-share" style="font-size: 12px;"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li>
                                                            <a class="dropdown-item" target="_blank" rel="noopener"
                                                                href="https://wa.me/?text={{ urlencode($form->name . ' - ' . $publicFormUrl) }}">
                                                                <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item"
                                                                href="mailto:?subject={{ urlencode($form->name) }}&body={{ urlencode($publicFormUrl) }}">
                                                                <i class="bi bi-envelope me-1"></i> Email
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ optional($form->companyBranch)->name ?? '-' }}</td>
                                    <td>
                                        {{ $form->no_booth ?? '-' }}
                                        @if ($form->requires_payment)
                                            <br>
                                            <span class="badge badge-warning">
                                                Rp {{ number_format((float) $form->payment_amount, 0, ',', '.') }}
                                            </span>
                                        @else
                                            <br>
                                            <span class="badge badge-secondary">Gratis</span>
                                        @endif
                                    </td>
                                    <td>{{ $form->description ?? '-' }}</td>
                                    <td class="text-center">
                                        @if ($form->formSubmissions->count() > 0)
                                            <a href="{{ route('quiz.form.submissions', $form->id) }}"
                                                class="d-inline-flex align-items-center gap-1">
                                                {{ $form->formSubmissions->count() }}
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Klik di sini untuk melihat detail"></i>
                                            </a>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($form->requires_payment)
                                            @php $totalPaid = $form->formPayments->where('status', 'paid')->sum('amount'); @endphp
                                            <a href="{{ route('quiz.form.submissions', $form->id) }}"
                                                class="d-inline-flex align-items-center gap-1">
                                                Rp {{ number_format((float) $totalPaid, 0, ',', '.') }}
                                                <i class="bi bi-info-circle text-muted" data-bs-toggle="tooltip"
                                                    data-bs-placement="top"
                                                    title="Klik di sini untuk melihat detail"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format((int) $form->view_count, 0, ',', '.') }}</td>
                                    <td>{{ optional($form->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            {{-- Detail: popup read-only isi pertanyaan+jawaban form ini, tanpa
                                                 pindah halaman (beda dari Preview yang buka wizard publik). Konten
                                                 diambil lewat fetch() ke route('quiz.form.detail', ...), lihat
                                                 script + modal di bagian bawah file ini. --}}
                                            <button type="button" class="btn btn-sm btn-outline-dark text-nowrap flex-shrink-0"
                                                data-bs-toggle="modal" data-bs-target="#formDetailModal"
                                                data-url="{{ route('quiz.form.detail', $form->id) }}"
                                                data-form-name="{{ $form->name }}">
                                                Detail
                                            </button>

                                            <a href="{{ route('quiz.form.edit', $form->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap flex-shrink-0">Edit</a>

                                            <a href="{{ route('quiz.form-question.create', ['form_id' => $form->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap flex-shrink-0">+ Add Questions</a>

                                            <a href="{{ route('quiz.form-question.index', ['form_id' => $form->id]) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap flex-shrink-0">Show Questions</a>

                                            <a href="{{ ($form->slug && $form->booth_slug)
                                                    ? route('frontend.form.wizard.slug', ['branchSlug' => $form->slug, 'boothSlug' => $form->booth_slug])
                                                    : route('frontend.form.wizard', ['form_id' => $form->id]) }}"
                                                target="_blank" rel="noopener"
                                                class="btn btn-sm btn-outline-info text-nowrap flex-shrink-0"
                                                title="Preview URL publik (tanpa login)">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                    class="feather feather-eye">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                Preview
                                            </a>

                                            <form action="{{ route('quiz.form.destroy', $form->id) }}" method="POST"
                                                onsubmit="return confirm('Hapus form ini?');" class="m-0 flex-shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">Belum ada data form.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $data->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

{{--
    Modal "Detail" — dipakai bareng oleh SEMUA baris (satu modal, kontennya
    diganti tiap kali tombol "Detail" beda diklik, lewat event show.bs.modal di
    bawah). Read-only murni: cuma ada tombol Tutup, tidak ada aksi apa pun di
    dalam modal-body-nya (isinya di-render server-side lewat
    quiz/form/_detail-content.blade.php).

    Dipaksa background putih + teks hitam di sini (scoped ke #formDetailModal
    saja) supaya tidak ikut kebawa tema dark mode admin — badge/warna lain
    (bg-success/bg-danger/dst di dalam konten) tetap seperti biasa, cuma warna
    dasar modal & teks polosnya yang di-override.
--}}
<style>
    #formDetailModal .modal-content {
        background-color: #ffffff !important;
        color: #1a1a1a !important;
    }
    #formDetailModal .modal-header,
    #formDetailModal .modal-footer {
        border-color: #e5e7eb !important;
    }
    #formDetailModal .modal-title {
        color: #1a1a1a !important;
    }
    #formDetailModal .quiz-detail-question-card {
        background-color: #ffffff;
        color: #1a1a1a;
        border-color: #e5e7eb !important;
    }
    #formDetailModal .quiz-detail-question-card-nested {
        background-color: #fbfbfd !important;
    }
    #formDetailModal .text-muted {
        color: #6c757d !important;
    }
</style>
<div class="modal fade" id="formDetailModal" tabindex="-1" aria-labelledby="formDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formDetailModalLabel">Detail Form</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="formDetailModalBody">
                <div class="text-center text-muted py-4">Memuat...</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    // PENTING: semua logic di bawah ini SENGAJA dibungkus DOMContentLoaded.
    // Script bootstrap.bundle.min.js dimuat di layout (footer), yaitu SETELAH
    // @yield('content') tempat script ini duduk — kalau kode ini langsung
    // dieksekusi inline tanpa nunggu DOMContentLoaded, `bootstrap` (dipakai di
    // init tooltip) belum ke-define sama sekali dan bikin ReferenceError, yang
    // otomatis menghentikan SISA script block ini juga (termasuk logic modal
    // Detail & copy/share URL di bawahnya) — persis kenapa sebelumnya modal
    // "Detail" macet terus di "Memuat..." (listener show.bs.modal-nya tidak
    // sempat kepasang). DOMContentLoaded baru fire setelah SEMUA script
    // (termasuk yang di footer layout) selesai dieksekusi, jadi `bootstrap`
    // dijamin sudah ada.
    document.addEventListener('DOMContentLoaded', function () {
        // Tooltip Bootstrap tidak auto-aktif di layout admin ini, jadi diinisialisasi
        // manual di sini untuk ikon (i) "Klik di sini untuk melihat detail" di kolom
        // Submission/Payment.
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
            new bootstrap.Tooltip(el);
        });

        var modalEl = document.getElementById('formDetailModal');
        if (modalEl) {
            var modalBody = document.getElementById('formDetailModalBody');
            var modalLabel = document.getElementById('formDetailModalLabel');

            modalEl.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                if (!button) return;

                var url = button.getAttribute('data-url');
                var formName = button.getAttribute('data-form-name') || '';

                modalLabel.textContent = formName ? ('Detail: ' + formName) : 'Detail Form';
                modalBody.innerHTML = '<div class="text-center text-muted py-4">Memuat...</div>';

                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (res) {
                        if (!res.ok) {
                            throw new Error('Gagal memuat detail (status ' + res.status + ').');
                        }
                        return res.text();
                    })
                    .then(function (html) {
                        modalBody.innerHTML = html;
                    })
                    .catch(function () {
                        modalBody.innerHTML = '<div class="alert alert-danger mb-0">Gagal memuat detail form. Silakan coba lagi.</div>';
                    });
            });
        }

        function fallbackCopy(text) {
            var temp = document.createElement('textarea');
            temp.value = text;
            temp.style.position = 'fixed';
            temp.style.opacity = '0';
            document.body.appendChild(temp);
            temp.focus();
            temp.select();
            try { document.execCommand('copy'); } catch (e) {}
            document.body.removeChild(temp);
        }

        document.querySelectorAll('.copy-url-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).catch(function () { fallbackCopy(url); });
                } else {
                    fallbackCopy(url);
                }

                btn.setAttribute('title', 'Tersalin!');
                btn.classList.add('text-success');
                setTimeout(function () {
                    btn.setAttribute('title', 'Salin URL');
                    btn.classList.remove('text-success');
                }, 1500);
            });
        });
        // Share (WhatsApp/Email) sekarang murni link <a href="wa.me/...">/"mailto:..."
        // lewat dropdown Bootstrap — tidak perlu JS tambahan di sini lagi.
    });
</script>

@endsection
