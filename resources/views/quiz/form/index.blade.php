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
                                                <span class="copy-url-text">{{ $publicFormUrl }}</span>
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
                                            <a href="{{ route('quiz.form.submissions', $form->id) }}">
                                                {{ $form->formSubmissions->count() }}
                                            </a>
                                        @else
                                            0
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($form->requires_payment)
                                            @php $totalPaid = $form->formPayments->where('status', 'paid')->sum('amount'); @endphp
                                            <a href="{{ route('quiz.form.submissions', $form->id) }}"
                                                title="Lihat detail transaksi pembayaran">
                                                Rp {{ number_format((float) $totalPaid, 0, ',', '.') }}
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ number_format((int) $form->view_count, 0, ',', '.') }}</td>
                                    <td>{{ optional($form->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('quiz.form.edit', $form->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <a href="{{ route('quiz.form-question.create', ['form_id' => $form->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Add Questions</a>

                                            <a href="{{ ($form->slug && $form->booth_slug)
                                                    ? route('frontend.form.wizard.slug', ['branchSlug' => $form->slug, 'boothSlug' => $form->booth_slug])
                                                    : route('frontend.form.wizard', ['form_id' => $form->id]) }}"
                                                target="_blank" rel="noopener"
                                                class="btn btn-sm btn-outline-info text-nowrap"
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
                                                onsubmit="return confirm('Hapus form ini?');" class="m-0">
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

<script>
    (function () {
        document.querySelectorAll('.copy-url-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');

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

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(url).catch(function () { fallbackCopy(url); });
                } else {
                    fallbackCopy(url);
                }

                var original = btn.innerHTML;
                btn.setAttribute('title', 'Tersalin!');
                btn.classList.add('text-success');
                setTimeout(function () {
                    btn.setAttribute('title', 'Salin URL');
                    btn.classList.remove('text-success');
                }, 1500);
            });
        });
    })();
</script>

@endsection
