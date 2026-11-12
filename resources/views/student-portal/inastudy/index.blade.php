@extends('layouts.frontend')
@section('content')

{{--
    Halaman "InaStudy": widget "My University Applications" milik siswa.
    Daftar kuliah baru HANYA lewat Apply (katalog universitas, wajib bayar Registration Fee).
    Register manual tanpa bayar sudah ditutup untuk siswa (9 November 2026); hanya admin lewat "Add to InaStudy".
--}}
@if($hasStudent)
<div class="row">
    <div class="col-12">
        <div class="widget-content widget-content-area br-8 mb-4">
            <h4 class="mb-3">My University Applications</h4>

            {{-- Cart "Pilihan Kampus" (12 November 2026): pengingat kalau masih ada kampus yang belum di-checkout. --}}
            @if($cartCount = count(session('apply_cart', [])))
                <div class="alert alert-light-danger d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-radius:12px;">
                    <span>You still have <strong>{{ $cartCount }} {{ \Illuminate\Support\Str::plural('university', $cartCount) }}</strong> in your list that you haven't checked out yet.</span>
                    <a href="{{ route('student-portal.apply.cart') }}" class="btn btn-sm btn-danger">Continue to checkout</a>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Application No</th>
                            <th>University</th>
                            <th>Major</th>
                            <th>Status</th>
                            <th style="width:150px;">Documents</th>
                            <th>Submitted</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($myApplications as $myApplication)
                            <tr>
                                <td class="fw-bold">{{ $myApplication->application_no }}</td>
                                <td>{{ optional($myApplication->university)->name ?? '-' }}</td>
                                <td>{{ $myApplication->major_label }}</td>
                                <td><span class="badge bg-info text-capitalize">{{ str_replace('_', ' ', $myApplication->status) }}</span></td>
                                <td>
                                    @php
                                        $docsCount = $myApplication->documents_count ?? 0;
                                        $totalDocumentTypes = $myApplication->documentTypes()->count(); // per kampus (12 November 2026)
                                        $docsPercent = $totalDocumentTypes > 0 ? min(100, round(($docsCount / $totalDocumentTypes) * 100)) : 0;
                                        $isComplete = $totalDocumentTypes > 0 && $docsCount >= $totalDocumentTypes;
                                    @endphp
                                    <div style="font-size:12px;" class="mb-1">{{ $docsCount }} / {{ $totalDocumentTypes }}</div>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar {{ $isComplete ? 'bg-success' : 'bg-primary' }}" role="progressbar" style="width: {{ $docsPercent }}%;" aria-valuenow="{{ $docsPercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    @if ($isComplete)
                                        <span class="badge bg-success mt-1" style="font-size:10px;width:auto;height:auto;border-radius:.25rem;padding:.25em .5em;">Documents Complete</span>
                                    @endif
                                </td>
                                <td>{{ optional($myApplication->submitted_at)->format('Y/m/d') }}</td>
                                <td class="text-center text-nowrap">
                                    <a href="{{ route('student-portal.applications.show', $myApplication->id) }}" class="btn btn-sm btn-outline-primary">Summary</a>
                                    <a href="{{ route('student-portal.applications.form.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-warning">Form</a>
                                    <a href="{{ route('student-portal.applications.documents.edit', $myApplication->id) }}" class="btn btn-sm btn-outline-success">Documents</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="mb-2">Data not found</div>
                                    <a href="{{ route('frontend.university.catalog') }}" class="btn btn-primary btn-sm">Apply to University</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endif


@endsection
