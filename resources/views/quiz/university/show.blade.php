@extends('layouts.frontend')
@section('content')

<div class="middle-content container-xxl p-0">

    <div class="page-meta">
        <nav class="breadcrumb-style-one" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('quiz.university.index') }}">University</a></li>
                @if($data->city)
                    <li class="breadcrumb-item"><a href="{{ route('city.show', $data->city->id) }}">{{ $data->city->name }}</a></li>
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
                        <p class="text-muted mb-1">
                            {{ optional($data->city)->name ?? $data->city }},
                            {{ $data->country }}
                        </p>
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

                <div class="mt-3">
                    <a href="{{ route('quiz.university.index') }}" class="btn btn-secondary">Back</a>
                    <a href="{{ route('quiz.university.edit', $data->id) }}" class="btn btn-primary">Edit</a>
                </div>

            </div>
        </div>
    </div>

</div>

@endsection
