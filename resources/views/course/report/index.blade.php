@extends('layouts.frontend')
@section('content')

{{--
    Menu "Laporan" (16 September 2026, permintaan user -- "buatkan 1 menu
    baru di dalam course namanya laporan, tampilkan siapa yang beli
    packages") -- READ ONLY, lihat docblock App\Http\Controllers\Course\
    CourseReportController.
--}}

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6">
                <nav class="breadcrumb-style-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><span>Course</span></li>
                        <li class="breadcrumb-item active" aria-current="page">Laporan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('course.report.index') }}" class="row g-2">
                        <div class="col-md-5">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                placeholder="Cari nama, email, HP student, atau nama package..."
                                value="{{ $filters['search'] }}">
                        </div>

                        <div class="col-md-3">
                            <select name="course_package_id" class="form-select">
                                <option value="">-- Semua Package --</option>
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}" @selected($filters['course_package_id'] == $package->id)>{{ $package->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select name="source" class="form-select">
                                <option value="">-- Semua Sumber --</option>
                                <option value="trial_claim" @selected($filters['source'] === 'trial_claim')>Trial (Gratis)</option>
                                <option value="deposit_purchase" @selected($filters['source'] === 'deposit_purchase')>Pembelian (Deposit)</option>
                            </select>
                        </div>

                        <div class="col-md-2 d-grid">
                            <button class="btn btn-outline-primary">
                                Cari
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Student</th>
                                <th>Package</th>
                                <th>Harga</th>
                                <th>Credits</th>
                                <th>Terpakai</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>

                        <tbody>

                        @forelse ($purchases as $index => $purchase)

                            <tr>
                                <td>{{ $purchases->firstItem() + $index }}</td>

                                {{--
                                    FIX (16 September 2026, permintaan user):
                                    kolom "Kontak" digabung ke sini -- Nama,
                                    lalu Email & Handphone sebagai small text
                                    center di bawahnya.
                                --}}
                                <td class="fw-bold">
                                    <div>{{ trim((optional($purchase->student)->first_name ?? '') . ' ' . (optional($purchase->student)->last_name ?? '')) ?: '-' }}</div>
                                    <div class="text-center text-muted fw-normal" style="font-size:12px;">{{ optional($purchase->student)->email ?? '-' }}</div>
                                    <div class="text-center text-muted fw-normal" style="font-size:12px;">{{ optional($purchase->student)->handphone ?? '-' }}</div>
                                </td>

                                {{--
                                    FIX (16 September 2026, permintaan user):
                                    Type/Class/Level package ditambahkan
                                    sebagai small text di bawah nama package.
                                --}}
                                <td>
                                    <div class="fw-bold">{{ optional($purchase->coursePackage)->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:12px;">{{ optional(optional($purchase->coursePackage)->type)->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:12px;">{{ optional(optional($purchase->coursePackage)->courseClass)->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:12px;">{{ optional(optional($purchase->coursePackage)->level)->name ?? '-' }}</div>
                                </td>

                                {{-- FIX (16 September 2026, permintaan user): kolom "Sumber" dihapus dari tampilan. --}}

                                <td>Rp {{ number_format((float) $purchase->price_paid, 0, ',', '.') }}</td>

                                <td>{{ rtrim(rtrim(number_format((float) $purchase->credits_granted, 2, ',', '.'), '0'), ',') }}</td>

                                <td>{{ rtrim(rtrim(number_format((float) $purchase->credits_used, 2, ',', '.'), '0'), ',') }}</td>

                                <td>{{ rtrim(rtrim(number_format((float) $purchase->credits_remaining, 2, ',', '.'), '0'), ',') }}</td>

                                <td>
                                    @if($purchase->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($purchase->status) }}</span>
                                    @endif
                                </td>

                                <td>{{ $purchase->created_at?->translatedFormat('d M Y, H:i') ?? '-' }}</td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="9" class="text-center">
                                    No data.
                                </td>
                            </tr>

                        @endforelse

                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $purchases->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
