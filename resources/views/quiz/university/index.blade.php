@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta mb-3 text-end">
        <a href="{{ route('quiz.university.create') }}" class="btn btn-primary">+ Add University</a>
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
                    <form method="GET" action="{{ route('quiz.university.index') }}" class="row g-2">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control"
                                placeholder="Search university/country/city..." value="{{ request('search') }}">
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
                                <th>Country</th>
                                <th>City</th>
                                <th>Description</th>
                                <th>Added on</th>
                                <th class="no-content text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $item)
                                <tr>
                                    <td>{{ $data->firstItem() + $index }}</td>
                                    <td class="fw-bold">
                                      <img src="{{ asset($item->logo) }}" alt="{{ $item->name }}" width="50">
                                    {{ $item->name }}

                                    </td>
                                    <td>{{ $item->country }}</td>
                                    <td>
                                        @php
                                            // Kolom `city` juga jadi nama relasi city() di model University, jadi
                                            // $item->city (magic property) selalu balikin nilai kolom mentah
                                            // (UUID), bukan objek City, walau relasinya sudah di-load() di
                                            // controller. Ambil city-nya lewat getRelation() supaya nama kota
                                            // yang tampil.
                                            //
                                            // Kalau relasinya tidak ketemu (city_id-nya sudah tidak match City
                                            // manapun — misalnya City-nya sudah dihapus, referensinya "putus"),
                                            // JANGAN tampilkan UUID mentahnya (bikin bingung) — tampilkan '-'
                                            // saja. Fallback ke nilai mentah HANYA kalau memang bukan berbentuk
                                            // UUID (berarti data lama yang city-nya masih teks bebas manual).
                                            $cityRelation = $item->getRelation('city');
                                            $rawCity = $item->city;
                                            $rawCityLooksLikeUuid = is_string($rawCity)
                                                && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $rawCity);
                                            $cityDisplay = $cityRelation->name
                                                ?? (! $rawCityLooksLikeUuid ? $rawCity : null)
                                                ?? '-';
                                        @endphp
                                        {{ $cityDisplay }}
                                        @if(!$cityRelation && $rawCityLooksLikeUuid)
                                            <div class="small text-danger" title="City ID di data ini tidak ditemukan di tabel City (kemungkinan City-nya sudah dihapus). Edit university ini dan pilih ulang City-nya.">
                                                referensi City tidak valid
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ \Illuminate\Support\Str::limit($item->description, 60) ?? '-' }}</td>
                                    <td>{{ optional($item->created_at)->format('Y/m/d') }}</td>
                                    <td class="text-center">
                                        {{-- Aksi ditaruh sejajar (flex-row), sama gayanya dengan index Quiz Form/
                                             Student — bukan dropdown 3-titik lagi. "Detail" mengarah ke halaman
                                             show internal (quiz.university.show), yang jadi halaman "profile"
                                             tempat Add Profile/Add Album mengarahkan balik. --}}
                                        <div class="d-flex flex-nowrap justify-content-center align-items-center gap-2">
                                            <a href="{{ route('quiz.university.show', $item->id) }}"
                                                class="btn btn-sm btn-outline-secondary text-nowrap">Detail</a>

                                            <a href="{{ route('quiz.university.edit', $item->id) }}"
                                                class="btn btn-sm btn-outline-primary text-nowrap">Edit</a>

                                            <a href="{{ route('quiz.university-profile.create', ['university_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Profile</a>

                                            <a href="{{ route('quiz.university-album.create', ['university_id' => $item->id]) }}"
                                                class="btn btn-sm btn-outline-success text-nowrap">+ Album</a>

                                            <form action="{{ route('quiz.university.destroy', $item->id) }}"
                                                method="POST" onsubmit="return confirm('Hapus university ini?');" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data university.</td>
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

@endsection