@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('quiz.university.index') }}">University</a></li>
                {{-- $cityModel dikirim terpisah dari controller (lihat catatan di
                     UniversityController::show()) — kalau kolom `city` data lama
                     berisi teks bebas (bukan UUID kota yang valid), $cityModel
                     akan null dan breadcrumb link ini otomatis disembunyikan
                     (bukan crash), teks kotanya tetap tampil di bawah. --}}
                @if($cityModel)
                    <li class="breadcrumb-item"><a href="{{ route('city.show', $cityModel->id) }}">{{ $cityModel->name }}</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">{{ $data->name }}</li>
            </ol>
        </nav>
    </div>

    <div class="row layout-top-spacing">
        <div class="col-xl-12 layout-spacing">
            <div class="widget-content widget-content-area br-8">

                <div class="row mb-4">
                    @if($data->logo)
                        <div class="col-sm-2">
                            <img src="{{ asset($data->logo) }}" alt="{{ $data->name }}" class="img-fluid" style="max-height: 100px;">
                        </div>
                    @endif
                    <div class="col-sm-10">
                        <h4 class="mb-1">{{ $data->name }}</h4>
                        @php
                            // Sama seperti index — kalau $cityModel tidak ketemu (relasinya
                            // "putus") JANGAN tampilkan UUID mentahnya, cuma tampilkan kalau
                            // memang data lama yang city-nya teks bebas manual (bukan UUID).
                            $rawCityLooksLikeUuid = is_string($data->city)
                                && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $data->city);
                            $cityDisplay = $cityModel->name
                                ?? (! $rawCityLooksLikeUuid ? $data->city : null)
                                ?? '-';
                        @endphp
                        <p class="text-muted mb-1">
                            {{ $cityDisplay }},
                            {{ $data->country }}
                        </p>
                        @if(!$cityModel && $rawCityLooksLikeUuid)
                            <p class="small text-danger mb-1">
                                Referensi City tidak valid (City-nya kemungkinan sudah dihapus) — silakan
                                <a href="{{ route('quiz.university.edit', $data->id) }}">edit university ini</a> dan pilih ulang City-nya.
                            </p>
                        @endif
                        @if($data->major)
                            <p class="mb-1"><strong>Major:</strong> {{ $data->major->name }}</p>
                        @endif
                        @if($data->description)
                            <p class="mb-0">{{ $data->description }}</p>
                        @endif
                    </div>
                </div>

                @if($data->banner)
                    <div class="mb-4">
                        <img src="{{ asset($data->banner) }}" alt="Banner {{ $data->name }}" class="img-fluid rounded">
                    </div>
                @endif

                @if($data->profiles && $data->profiles->count())
                    <hr>
                    <h5 class="mb-3">Profile</h5>
                    <div class="table-responsive mb-4">
                        <table class="table dt-table-hover">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Budget</th>
                                    <th>Language</th>
                                    <th>Scholarship</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data->profiles as $profile)
                                    <tr>
                                        <td>{{ $profile->field }}</td>
                                        <td>
                                            @if($profile->min_budget !== null || $profile->max_budget !== null)
                                                {{ number_format((int) ($profile->min_budget ?? 0)) }} - {{ number_format((int) ($profile->max_budget ?? 0)) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $profile->language ?? '-' }}</td>
                                        <td>{{ $profile->scholarship_available ? 'Available' : 'Not Available' }}</td>
                                        <td>{{ ucfirst($profile->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <hr>
                <h5 class="mb-3">Foto Kampus</h5>

                @php
                    $allPhotos = $data->albums->flatMap(fn ($album) => $album->photos);
                @endphp

                @if($allPhotos->count())
                    <div class="row">
                        @foreach($data->albums as $album)
                            @if($album->photos->count())
                                <div class="col-sm-12 mb-2">
                                    <h6>{{ $album->name }}</h6>
                                </div>
                                @foreach($album->photos as $photo)
                                    <div class="col-sm-3 col-6 mb-4">
                                        <img src="{{ asset($photo->photo) }}" alt="{{ $photo->title ?? $album->name }}"
                                            class="img-fluid rounded mb-1" style="width: 100%; height: 160px; object-fit: cover;">
                                        @if($photo->title)
                                            <div class="small text-muted">{{ $photo->title }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        @endforeach
                    </div>
                @else
                    <p class="text-muted">Belum ada foto untuk university ini.</p>
                @endif

                {{-- Halaman ini adalah "halaman profile" University (menampilkan tabel
                     Profile + grid foto Album) yang dituju setelah Add/Edit Profile,
                     dan tempat kembali dari Album/Foto. Aksi ditaruh sejajar, gaya
                     sama dengan index Quiz. --}}
                <div class="mt-3">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('quiz.university.index') }}" class="btn btn-secondary">Back</a>
                        <a href="{{ route('quiz.university.edit', $data->id) }}" class="btn btn-outline-primary">Edit</a>
                        <a href="{{ route('quiz.university-profile.create', ['university_id' => $data->id]) }}" class="btn btn-outline-success">+ Add Profile</a>
                        <a href="{{ route('quiz.university-album.index', ['university_id' => $data->id]) }}" class="btn btn-outline-secondary">Manage Album</a>
                        <a href="{{ route('quiz.university-album.create', ['university_id' => $data->id]) }}" class="btn btn-outline-success">+ Add Album</a>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
