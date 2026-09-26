@extends('layouts.frontend')
@section('content')

{{--
    Menu "Laporan" (16 September 2026, permintaan user -- "buatkan 1 menu
    baru di dalam course namanya laporan, tampilkan siapa yang beli
    packages") -- READ ONLY, lihat docblock App\Http\Controllers\Course\
    CourseReportController.
--}}

<div class="middle-content container-xxl p-0">


    {{--
        FIX (16 September 2026, permintaan user): 3 kartu ringkasan "bulan
        ini" -- lihat docblock App\Http\Controllers\Course\
        CourseReportController::index() untuk cara hitungnya. SENGAJA tidak
        ikut filter tabel di bawah (search/package/tanggal) -- ini snapshot
        tetap bulan berjalan.
    --}}
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="widget-content widget-content-area br-8 h-100">
                {{--
                    FIX (16 September 2026, permintaan user -- "total
                    pembelian itu harusnya 0 karena omset penjualan
                    packages"): kartu ini OMSET (jumlah Rupiah), bukan
                    jumlah transaksi -- trial gratis (harga Rp 0) otomatis
                    tidak menambah angka ini.
                --}}
                <div class="text-muted mb-1" style="font-size:13px;">Omset Penjualan Bulan Ini</div>
                <div class="fw-bold" style="font-size:24px;">Rp {{ number_format($totalRevenueThisMonth, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="widget-content widget-content-area br-8 h-100">
                <div class="text-muted mb-1" style="font-size:13px;">Student Beli Bulan Ini</div>
                <div class="fw-bold" style="font-size:28px;">{{ $totalStudentsThisMonth }}</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="widget-content widget-content-area br-8 h-100">
                <div class="text-muted mb-1" style="font-size:13px;">Package Terlaris Bulan Ini</div>

                @if($topPackage)
                    <div class="fw-bold">{{ $topPackage->name }}</div>
                    <div class="text-muted" style="font-size:12px;">{{ optional($topPackage->type)->name ?? '-' }}</div>
                    <div class="text-muted" style="font-size:12px;">{{ optional($topPackage->courseClass)->name ?? '-' }}</div>
                    <div class="text-muted" style="font-size:12px;">{{ optional($topPackage->level)->name ?? '-' }}</div>
                    <span class="badge bg-primary mt-2">Dibeli {{ $topPackageCount }}x</span>
                @else
                    <div class="text-muted">Belum ada data</div>
                @endif
            </div>
        </div>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="mb-4">
                    <form method="GET" action="{{ route('course.report.index') }}" class="row g-2">
                        <div class="col-md-4">
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

                        {{-- FIX (16 September 2026, permintaan user -- "tambahkan
                             filter dari tanggal berapa sampai dengan tanggal
                             berapa"): filter rentang tanggal beli. --}}
                        <div class="col-md-2">
                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}" title="Dari tanggal">
                        </div>

                        <div class="col-md-2">
                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}" title="Sampai tanggal">
                        </div>

                        <div class="col-md-1 d-grid">
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
                                    di bawahnya, rata kiri (samain dengan
                                    kolom Package).
                                --}}
                                <td class="fw-bold">
                                    <div>{{ trim((optional($purchase->student)->first_name ?? '') . ' ' . (optional($purchase->student)->last_name ?? '')) ?: '-' }}</div>
                                    <div class="text-muted fw-normal" style="font-size:12px;">{{ optional($purchase->student)->email ?? '-' }}</div>
                                    <div class="text-muted fw-normal" style="font-size:12px;">{{ optional($purchase->student)->handphone ?? '-' }}</div>
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
