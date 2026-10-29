@extends('layouts.frontend')

@section('content')

{{-- Students > File Student (App\Http\Controllers\Student\StudentFileController). --}}
<div class="middle-content container-xxl p-0">

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12 layout-spacing">
            <div class="widget-content widget-content-area br-8 p-4">

                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                    <div>
                        <h5 class="mb-1">File Student</h5>
                        <div class="text-muted small">
                            Semua file yang diunggah student (dokumen aplikasi kuliah & foto formulir).
                            Hanya student yang punya file yang ditampilkan.
                        </div>
                    </div>
                    <form method="GET" action="{{ route('student.files.index') }}" class="d-flex gap-2 m-0">
                        <input type="text" name="search" value="{{ $search }}" class="form-control"
                            placeholder="Cari nama, email, atau no. aplikasi" style="min-width: 260px;">
                        <button type="submit" class="btn btn-outline-primary">Cari</button>
                        @if ($search !== '')
                            <a href="{{ route('student.files.index') }}" class="btn btn-outline-secondary" title="Hapus pencarian">&times;</a>
                        @endif
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table dt-table-hover" style="width:100%">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Student</th>
                                <th>No. Aplikasi</th>
                                <th>Branch</th>
                                <th class="text-center">Aplikasi</th>
                                <th class="text-center">File</th>
                                <th>Ukuran</th>
                                <th>Upload Terakhir</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($students as $index => $student)
                                @php($summary = $summaries[$student->id])
                                <tr>
                                    <td>{{ $students->firstItem() + $index }}</td>
                                    <td>
                                        <div class="fw-bold">{{ trim($student->first_name.' '.$student->last_name) ?: '-' }}</div>
                                        <div class="text-muted small">{{ $student->email }}</div>
                                    </td>
                                    <td>{{ $student->application_no ?: '-' }}</td>
                                    <td>{{ $student->companyBranch->name ?? '-' }}</td>
                                    <td class="text-center">{{ $summary['applications'] }}</td>
                                    <td class="text-center"><span class="badge badge-primary">{{ $summary['files'] }}</span></td>
                                    <td class="text-nowrap">{{ $summary['size'] ? \Illuminate\Support\Number::fileSize($summary['size'], 1) : '-' }}</td>
                                    <td class="text-nowrap">
                                        {{ $summary['last'] ? \Illuminate\Support\Carbon::createFromTimestamp($summary['last'], config('app.timezone'))->translatedFormat('d M Y H:i') : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">
                                        {{ $search !== '' ? 'Tidak ada student yang cocok dengan pencarian.' : 'Belum ada student yang mengunggah file.' }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $students->links('pagination::bootstrap-5') }}
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
